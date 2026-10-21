<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockReconciliationService
{
    /**
     * Reconcile physical warehouse stock, batch quantities, and stock movements for all or specific products.
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
        $updatedMovements = 0;
        $hasBranchOnSm = Schema::hasColumn('stock_movements', 'branch_id');

        foreach ($products as $p) {
            $pId = $p->id;
            $ppb = max(1, (int)($p->pieces_per_box ?? 1));

            // ── 1. Reconcile physical warehouse_stocks and product_batches ──
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

            // ── 2. Reconcile stock_movements for Delivery Notes (DCs) ──
            $dcGroups = DB::table('delivery_note_items')
                ->join('delivery_notes', 'delivery_notes.id', '=', 'delivery_note_items.dc_note_id')
                ->where('delivery_note_items.product_id', $pId)
                ->where('delivery_notes.status', '!=', 'cancelled')
                ->select(
                    'delivery_notes.id as dc_id',
                    'delivery_notes.dc_no',
                    'delivery_notes.delivery_type',
                    'delivery_notes.delivery_date',
                    'delivery_notes.created_at as dc_created_at',
                    'delivery_notes.branch_id as dc_branch_id',
                    DB::raw("SUM(COALESCE(NULLIF(delivery_note_items.total_pieces, 0), delivery_note_items.qty * $ppb)) as total_dc_pieces")
                )
                ->groupBy(
                    'delivery_notes.id',
                    'delivery_notes.dc_no',
                    'delivery_notes.delivery_type',
                    'delivery_notes.delivery_date',
                    'delivery_notes.created_at',
                    'delivery_notes.branch_id'
                )
                ->get();

            foreach ($dcGroups as $dcg) {
                $expectedPieces = (float) $dcg->total_dc_pieces;
                if ($expectedPieces <= 0) continue;

                // Check existing OUT movements for this DC & product
                $existingOut = (float) DB::table('stock_movements')
                    ->where('product_id', $pId)
                    ->where('ref_id', $dcg->dc_id)
                    ->whereIn('ref_type', ['dc', 'DC', 'delivery_note', 'DELIVERY_NOTE', 'donation'])
                    ->where(function ($q) {
                        $q->where('type', 'out')->orWhere('qty', '<', 0);
                    })
                    ->sum(DB::raw('ABS(qty)'));

                if ($existingOut < ($expectedPieces - 0.001)) {
                    $missingPieces = $expectedPieces - $existingOut;
                    $refType = ($dcg->delivery_type === 'donation') ? 'donation' : 'dc';
                    $movementNote = ($dcg->delivery_type === 'donation')
                        ? 'Donation/Sample DC #' . ($dcg->dc_no ?? $dcg->dc_id)
                        : 'DC #' . ($dcg->dc_no ?? $dcg->dc_id);

                    $movDate = $dcg->dc_created_at
                        ? date('Y-m-d H:i:s', strtotime($dcg->dc_created_at))
                        : ($dcg->delivery_date ? $dcg->delivery_date . ' 12:00:00' : now());

                    $ins = [
                        'product_id' => $pId,
                        'type'       => 'out',
                        'qty'        => -$missingPieces,
                        'ref_type'   => $refType,
                        'ref_id'     => $dcg->dc_id,
                        'note'       => $movementNote,
                        'created_at' => $movDate,
                        'updated_at' => $movDate,
                    ];
                    if ($hasBranchOnSm) {
                        $ins['branch_id'] = $dcg->dc_branch_id ?: 1;
                    }

                    DB::table('stock_movements')->insert($ins);
                    $updatedMovements++;
                }
            }

            // ── 3. Reconcile stock_movements for Direct Sales (without active DCs) ──
            $saleGroups = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $pId)
                ->whereIn('sales.sale_status', ['posted', 'post', 'in_delivery', 'delivered'])
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('delivery_notes')
                        ->whereColumn('delivery_notes.sale_id', 'sales.id')
                        ->where('delivery_notes.status', '!=', 'cancelled');
                })
                ->select(
                    'sales.id as sale_id',
                    'sales.invoice_no',
                    'sales.sale_date',
                    'sales.created_at as sale_created_at',
                    'sales.branch_id as sale_branch_id',
                    DB::raw("SUM(COALESCE(NULLIF(sale_items.total_pieces, 0), sale_items.qty * $ppb)) as total_sale_pieces")
                )
                ->groupBy(
                    'sales.id',
                    'sales.invoice_no',
                    'sales.sale_date',
                    'sales.created_at',
                    'sales.branch_id'
                )
                ->get();

            foreach ($saleGroups as $sg) {
                $expectedPieces = (float) $sg->total_sale_pieces;
                if ($expectedPieces <= 0) continue;

                $existingOut = (float) DB::table('stock_movements')
                    ->where('product_id', $pId)
                    ->where('ref_id', $sg->sale_id)
                    ->whereIn('ref_type', ['sale', 'SALE', 'SIN', 'sin', 'out'])
                    ->where(function ($q) {
                        $q->where('type', 'out')->orWhere('qty', '<', 0);
                    })
                    ->sum(DB::raw('ABS(qty)'));

                if ($existingOut < ($expectedPieces - 0.001)) {
                    $missingPieces = $expectedPieces - $existingOut;
                    $movDate = $sg->sale_created_at
                        ? date('Y-m-d H:i:s', strtotime($sg->sale_created_at))
                        : ($sg->sale_date ? $sg->sale_date . ' 12:00:00' : now());

                    $ins = [
                        'product_id' => $pId,
                        'type'       => 'out',
                        'qty'        => -$missingPieces,
                        'ref_type'   => 'sale',
                        'ref_id'     => $sg->sale_id,
                        'note'       => 'Direct Sale #' . ($sg->invoice_no ?? $sg->sale_id),
                        'created_at' => $movDate,
                        'updated_at' => $movDate,
                    ];
                    if ($hasBranchOnSm) {
                        $ins['branch_id'] = $sg->sale_branch_id ?: 1;
                    }

                    DB::table('stock_movements')->insert($ins);
                    $updatedMovements++;
                }
            }

            // ── 4. Reconcile stock_movements for Purchases ──
            $purchaseGroups = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchase_items.product_id', $pId)
                ->where('purchases.status_purchase', '!=', 'draft')
                ->select(
                    'purchases.id as purchase_id',
                    'purchases.invoice_no',
                    'purchases.purchase_date',
                    'purchases.created_at as purch_created_at',
                    'purchases.branch_id as purch_branch_id',
                    DB::raw("SUM(COALESCE(NULLIF(purchase_items.boxes_qty * COALESCE(purchase_items.pieces_per_box, 1), 0), NULLIF(purchase_items.qty * COALESCE(purchase_items.uom_factor, 1), 0), purchase_items.qty, 0)) as total_purch_pieces")
                )
                ->groupBy(
                    'purchases.id',
                    'purchases.invoice_no',
                    'purchases.purchase_date',
                    'purchases.created_at',
                    'purchases.branch_id'
                )
                ->get();

            foreach ($purchaseGroups as $pg) {
                $expectedPieces = (float) $pg->total_purch_pieces;
                if ($expectedPieces <= 0) continue;

                $existingIn = (float) DB::table('stock_movements')
                    ->where('product_id', $pId)
                    ->where('ref_id', $pg->purchase_id)
                    ->whereIn('ref_type', ['PURCHASE', 'purchase', 'GRN', 'grn', 'PUR', 'pur', 'PURCHASE_ITEM', 'INWARD'])
                    ->where(function ($q) {
                        $q->where('type', 'in')->orWhere('qty', '>', 0);
                    })
                    ->sum(DB::raw('ABS(qty)'));

                if ($existingIn < ($expectedPieces - 0.001)) {
                    $missingPieces = $expectedPieces - $existingIn;
                    $movDate = $pg->purch_created_at
                        ? date('Y-m-d H:i:s', strtotime($pg->purch_created_at))
                        : ($pg->purchase_date ? $pg->purchase_date . ' 12:00:00' : now());

                    $ins = [
                        'product_id' => $pId,
                        'type'       => 'in',
                        'qty'        => $missingPieces,
                        'ref_type'   => 'PURCHASE',
                        'ref_id'     => $pg->purchase_id,
                        'note'       => 'Purchase #' . ($pg->invoice_no ?? $pg->purchase_id),
                        'created_at' => $movDate,
                        'updated_at' => $movDate,
                    ];
                    if ($hasBranchOnSm) {
                        $ins['branch_id'] = $pg->purch_branch_id ?: 1;
                    }

                    DB::table('stock_movements')->insert($ins);
                    $updatedMovements++;
                }
            }

            $updatedProducts++;
        }

        return [
            'success'            => true,
            'products_scanned'   => $updatedProducts,
            'stocks_updated'     => $updatedStocks,
            'batches_updated'    => $updatedBatches,
            'movements_updated'  => $updatedMovements,
            'message'            => "Stock synchronization completed. {$updatedStocks} warehouse stock records, {$updatedBatches} batch records, and {$updatedMovements} movement records aligned with transactions."
        ];
    }
}
