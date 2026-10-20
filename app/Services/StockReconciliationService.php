<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;

class StockReconciliationService
{
    /**
     * Reconcile physical warehouse stock and batch quantities for all or specific products.
     *
     * @param int|null $productId
     * @return array
     */
    public static function reconcile(?int $productId = null): array
    {
        $products = Product::when($productId, fn($q) => $q->where('id', $productId))->get();
        $warehouses = Warehouse::all();
        $updatedProducts = 0;
        $updatedStocks = 0;
        $updatedBatches = 0;

        foreach ($products as $p) {
            $pId = $p->id;
            $ppb = max(1, (int)($p->pieces_per_box ?? 1));

            foreach ($warehouses as $wh) {
                $whId = $wh->id;
                $branchId = $wh->branch_id ?? 1;

                // 1. Total Purchases in this warehouse
                $purchased = (float) DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchase_items.product_id', $pId)
                    ->where('purchase_items.warehouse_id', $whId)
                    ->where('purchases.status_purchase', '!=', 'draft')
                    ->sum(DB::raw("COALESCE(NULLIF(purchase_items.boxes_qty * COALESCE(purchase_items.pieces_per_box, 1), 0), NULLIF(purchase_items.qty * COALESCE(purchase_items.uom_factor, 1), 0), purchase_items.qty, 0)"));

                // 2. Direct Sales (without active DCs)
                $sold = (float) DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $pId)
                    ->where('sale_items.warehouse_id', $whId)
                    ->whereIn('sales.sale_status', ['posted', 'post', 'in_delivery', 'delivered'])
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('delivery_notes')
                            ->whereColumn('delivery_notes.sale_id', 'sales.id')
                            ->where('delivery_notes.status', '!=', 'cancelled');
                    })
                    ->sum(DB::raw("COALESCE(NULLIF(sale_items.total_pieces, 0), sale_items.qty * $ppb)"));

                // 3. Delivery Notes (DCs)
                $delivered = (float) DB::table('delivery_note_items')
                    ->join('delivery_notes', 'delivery_notes.id', '=', 'delivery_note_items.dc_note_id')
                    ->where('delivery_note_items.product_id', $pId)
                    ->where('delivery_note_items.warehouse_id', $whId)
                    ->where('delivery_notes.status', '!=', 'cancelled')
                    ->sum(DB::raw("COALESCE(NULLIF(delivery_note_items.total_pieces, 0), delivery_note_items.qty * $ppb)"));

                // 4. Sale Returns
                $saleReturns = (float) DB::table('sale_return_items')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->where('sale_return_items.product_id', $pId)
                    ->where('sale_return_items.warehouse_id', $whId)
                    ->where('sale_returns.status', 'posted')
                    ->sum('sale_return_items.qty');

                // 5. Purchase Returns
                $purchReturns = (float) DB::table('purchase_return_items')
                    ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->where('purchase_return_items.product_id', $pId)
                    ->where('purchase_return_items.warehouse_id', $whId)
                    ->sum('purchase_return_items.qty');

                // 6. Opening stock batches
                $openingStock = (float) DB::table('product_batches')
                    ->where('product_id', $pId)
                    ->where('warehouse_id', $whId)
                    ->where(function ($q) {
                        $q->where('source_type', 'opening_stock')
                          ->orWhereRaw("batch_number REGEXP '^[0]+$'");
                    })
                    ->sum(DB::raw("COALESCE(NULLIF(qty_received, 0), qty_remaining)"));

                // Real calculated net pieces
                $netRealPieces = max(0, $openingStock + $purchased - $sold - $delivered + $saleReturns - $purchReturns);

                // Update / Sync warehouse_stocks
                $whStocks = WarehouseStock::where('warehouse_id', $whId)
                    ->where('product_id', $pId)
                    ->get();

                if ($whStocks->isNotEmpty()) {
                    $currentPieces = (float) $whStocks->sum('total_pieces');
                    if (abs($currentPieces - $netRealPieces) > 0.001) {
                        $firstRow = $whStocks->first();
                        $firstRow->total_pieces = $netRealPieces;
                        $firstRow->quantity = StockService::piecesToDisplay($netRealPieces, $ppb);
                        $firstRow->branch_id = $branchId;
                        $firstRow->save();

                        foreach ($whStocks->slice(1) as $otherRow) {
                            $otherRow->total_pieces = 0;
                            $otherRow->quantity = '0';
                            $otherRow->save();
                        }
                        $updatedStocks++;
                    }
                } elseif ($netRealPieces > 0) {
                    WarehouseStock::create([
                        'warehouse_id' => $whId,
                        'product_id'   => $pId,
                        'uom_id'       => null,
                        'branch_id'    => $branchId,
                        'total_pieces' => $netRealPieces,
                        'quantity'     => StockService::piecesToDisplay($netRealPieces, $ppb),
                    ]);
                    $updatedStocks++;
                }

                // Sync product_batches in this warehouse
                $batches = ProductBatch::where('product_id', $pId)
                    ->where('warehouse_id', $whId)
                    ->orderBy('exp_date', 'asc')
                    ->get();

                if ($batches->isNotEmpty()) {
                    if ($netRealPieces <= 0) {
                        foreach ($batches as $b) {
                            if ($b->qty_remaining > 0 || $b->status !== 'consumed') {
                                $b->qty_remaining = 0;
                                $b->status = 'consumed';
                                $b->save();
                                $updatedBatches++;
                            }
                        }
                    } else {
                        // Align batches according to netRealPieces (FIFO/FEFO)
                        $allocated = 0;
                        foreach ($batches as $b) {
                            $received = (float)$b->qty_received > 0 ? (float)$b->qty_received : (float)$b->qty_remaining;
                            $needed = max(0, $netRealPieces - $allocated);
                            $newRemaining = min($received, $needed);
                            $allocated += $newRemaining;

                            $newStatus = ($newRemaining <= 0) ? 'consumed' : (
                                ($b->exp_date && \Carbon\Carbon::parse($b->exp_date)->lt(now())) ? 'expired' : 'active'
                            );

                            if (abs((float)$b->qty_remaining - $newRemaining) > 0.001 || $b->status !== $newStatus) {
                                $b->qty_remaining = $newRemaining;
                                $b->status = $newStatus;
                                $b->save();
                                $updatedBatches++;
                            }
                        }
                    }
                }
            }
            $updatedProducts++;
        }

        return [
            'success'          => true,
            'products_scanned' => $updatedProducts,
            'stocks_updated'   => $updatedStocks,
            'batches_updated'  => $updatedBatches,
            'message'          => "Stock synchronization completed. {$updatedStocks} warehouse stock records and {$updatedBatches} batch records aligned with ledger transactions."
        ];
    }
}
