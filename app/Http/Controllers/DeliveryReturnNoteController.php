<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DeliveryReturnNote;
use App\Models\DeliveryReturnNoteItem;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductBatch;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliveryReturnNoteController extends Controller
{
    use BranchScoped;

    public function index()
    {
        $branchId = $this->getBranchId();
        $returns = DeliveryReturnNote::with(['customer', 'items.product', 'deliveryNote'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.delivery_return_note.index', compact('returns'));
    }

    public function create()
    {
        $branchId = $this->getBranchId();
        $customers = Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $nextReturnNo = DeliveryReturnNote::generateReturnNo();
        $warehouses = \App\Models\Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();

        return view('admin_panel.sale.delivery_return_note.create', compact('customers', 'nextReturnNo', 'warehouses'));
    }

    public function getDeliveriesByCustomer($customerId)
    {
        $branchId = $this->getBranchId();
        
        // Show DeliveryNotes where the linked Sale status is NOT 'posted'
        // 'posted' means a Sale Invoice Note has been created.
        $deliveries = DeliveryNote::with(['sale', 'items.product'])
            ->where('customer_id', $customerId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get()
            ->map(function($dc) {
                return [
                    'id' => $dc->id,
                    'dc_no' => $dc->dc_no,
                    'so_no' => $dc->sale->invoice_no ?? 'N/A',
                    'date' => $dc->delivery_date,
                    'items_count' => $dc->items->count(),
                ];
            });

        return response()->json($deliveries);
    }

    public function getDeliveryItems($id)
    {
        $delivery = DeliveryNote::with(['items.product', 'items.saleItem', 'sale'])->findOrFail($id);
        
        $items = collect($delivery->items)->map(function($item) {
            $ppb = $item->product->pieces_per_box > 0 ? (int)$item->product->pieces_per_box : 1;
            
            $alreadyReturned = DeliveryReturnNoteItem::where('delivery_note_item_id', $item->id)->sum('total_pieces');
            $remaining = $item->total_pieces - $alreadyReturned;

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->item_name ?? 'N/A',
                'product_code' => $item->product->item_code ?? '',
                'delivered_pieces' => $item->total_pieces,
                'remaining_pieces' => $remaining,
                'total_pieces' => $item->total_pieces,
                'qty_notation' => $item->qty, // boxes.loose
                'price' => $item->price,
                'warehouse_id' => $item->warehouse_id,
                'batch_id' => $item->batch_id,
                'lot_number' => $item->lot_number,
                'ppb' => $ppb,
            ];
        });

        return response()->json([
            'delivery' => $delivery,
            'items' => $items
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'return_date' => 'required|date',
            'delivery_note_id' => 'nullable|exists:delivery_notes,id',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'warehouse_id' => 'required|array',
            'batch_id' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $branchId = $this->getBranchId() ?? 1;
            $returnNo = DeliveryReturnNote::generateReturnNo();

            $returnNote = DeliveryReturnNote::create([
                'return_no' => $returnNo,
                'delivery_note_id' => $request->delivery_note_id,
                'customer_id' => $request->customer_id,
                'branch_id' => $branchId,
                'return_date' => $request->return_date,
                'remarks' => $request->remarks,
                'sale_id' => $request->sale_id, // If it comes from form
            ]);

            $totalBill = 0;

            foreach ($request->product_id as $i => $productId) {
                $qtyStr = $request->qty[$i] ?? '0';
                $warehouseId = $request->warehouse_id[$i];
                $price = (float)($request->price[$i] ?? 0);
                $dcItemId = $request->dc_item_id[$i] ?? null;

                $product = Product::find($productId);
                $ppb = ($product && $product->pieces_per_box > 0) ? (int)$product->pieces_per_box : 1;

                // Parse pieces
                $parts = explode('.', $qtyStr);
                $boxes = (int)($parts[0] ?? 0);
                $loose = isset($parts[1]) ? (int)$parts[1] : 0;
                $pieces = ($boxes * $ppb) + $loose;

                if ($pieces <= 0) continue;

                $lineTotal = $pieces * $price;
                $totalBill += $lineTotal;

                $batchId = $request->batch_id[$i] ?? null;

                // Create Item
                DeliveryReturnNoteItem::create([
                    'delivery_return_note_id' => $returnNote->id,
                    'product_id' => $productId,
                    'delivery_note_item_id' => $dcItemId,
                    'batch_id' => $batchId,
                    'qty' => $qtyStr,
                    'total_pieces' => $pieces,
                    'price' => $price,
                    'line_total' => $lineTotal,
                ]);

                // 1. Update Warehouse Stock (Increase)
                $stock = WarehouseStock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->first();

                if ($stock) {
                    $stock->total_pieces += $pieces;
                    $newBoxes = intdiv((int)$stock->total_pieces, $ppb);
                    $newPieces = (int)$stock->total_pieces % $ppb;
                    $stock->quantity = (float)($newBoxes . '.' . $newPieces);
                    $stock->save();
                } else {
                    WarehouseStock::create([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $productId,
                        'total_pieces' => $pieces,
                        'quantity' => (float)($boxes . '.' . $loose),
                        'branch_id' => $branchId,
                    ]);
                }

                // 1.1 Update Batch Stock (Increase)
                if ($batchId) {
                    ProductBatch::where('id', $batchId)->increment('qty_remaining', $pieces);
                }

                // 2. Decrement delivered_qty on SaleItem
                if ($dcItemId) {
                    $dcItem = DeliveryNoteItem::find($dcItemId);
                    if ($dcItem && $dcItem->sale_item_id) {
                        $saleItem = SaleItem::find($dcItem->sale_item_id);
                        if ($saleItem) {
                            $saleItem->decrement('delivered_qty', $pieces);
                            
                            // Re-calculate Sale status
                            $sale = Sale::find($saleItem->sale_id);
                            if ($sale && $sale->sale_status == 'posted') {
                                // If it was posted (fully delivered), it's now back to in_delivery
                                $sale->update(['sale_status' => 'in_delivery']);
                            }
                        }
                    }
                }

                // 3. Stock Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'type' => 'in',
                    'qty' => $pieces,
                    'ref_type' => 'drn',
                    'ref_id' => $returnNote->id,
                    'note' => 'Delivery Return #' . $returnNo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $returnNote->update([
                'bill_amount' => $totalBill,
                'net_amount' => $totalBill,
            ]);

            DB::commit();

            return redirect()->route('delivery.return.index')->with('success', 'Delivery Return Note created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
