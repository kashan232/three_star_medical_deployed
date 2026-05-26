<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

use App\Http\Traits\BranchScoped;

class StockTransferController extends Controller
{
    use BranchScoped;

    public function index()
    {
        $branchId = $this->getBranchId();
        $transfers = StockTransfer::with('fromWarehouse', 'toWarehouse', 'product')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        return view('admin_panel.warehouses.stock_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $branchId = $this->getBranchId();
        $warehouses = Warehouse::all();
        
        if ($branchId) {
            $warehouses = Warehouse::where('branch_id', $branchId)->get();
        }

        $products = Product::all();

        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|integer',
            'product_id' => 'required|array',
            'product_id.*' => 'required|integer|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|integer|min:1',
        ]);

        $fromWarehouse = $request->from_warehouse_id;
        $toWarehouse = $request->to_warehouse_id;
        $toShop = $request->to_shop ? true : false;
        $remarks = $request->remarks;

        // Aggregate quantities by product
        $groupped = [];
        foreach ($request->product_id as $index => $pid) {
            $qty = (int) ($request->quantity[$index] ?? 0);
            if ($qty > 0) {
                if (! isset($groupped[$pid])) {
                    $groupped[$pid] = 0;
                }
                $groupped[$pid] += $qty;
            }
        }

        if ($fromWarehouse == $toWarehouse && ! $toShop) {
            return back()->with('error', 'Source and Destination warehouses cannot be the same.');
        }

        try {
            $branchId = $this->getBranchId() ?? 1; // Default to 1 if super admin has no branch context in trait

            \Illuminate\Support\Facades\DB::transaction(function () use ($fromWarehouse, $toWarehouse, $toShop, $remarks, $groupped, $branchId) {

                foreach ($groupped as $productId => $totalQty) {
                    // Check source stock for TOTAL quantity
                    $sourceStock = WarehouseStock::where('warehouse_id', $fromWarehouse)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    $productName = Product::find($productId)->item_name ?? 'Product #'.$productId;

                    if (! $sourceStock || $sourceStock->quantity < $totalQty) {
                        throw new \Exception("Insufficient stock for {$productName}. Requested Total: {$totalQty}, Available: ".($sourceStock->quantity ?? 0));
                    }

                    // Reduce stock from source
                    $sourceStock->quantity -= $totalQty;
                    $sourceStock->save();

                    // Setup destination
                    if (! $toShop && $toWarehouse) {
                        $destStock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_id' => $toWarehouse,
                                'product_id' => $productId,
                            ],
                            [
                                'branch_id' => $branchId, // Ensure branch_id is set
                                'quantity' => 0,
                                'price' => $sourceStock->price ?? 0,
                            ]
                        );
                        $destStock->quantity += $totalQty;
                        
                        // Sync branch_id if missing
                        if (!$destStock->branch_id) {
                            $destStock->branch_id = $branchId;
                        }

                        $destStock->save();
                    }

                    // Record Transfer
                    $transfer = StockTransfer::create([
                        'branch_id' => $branchId,
                        'from_warehouse_id' => $fromWarehouse,
                        'to_warehouse_id' => $toShop ? null : $toWarehouse,
                        'to_shop' => $toShop,
                        'product_id' => $productId,
                        'quantity' => $totalQty,
                        'remarks' => $remarks,
                    ]);

                    // Log Movement (OUT)
                    \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                        'product_id' => $productId,
                        'type' => 'out',
                        'qty' => -1 * abs($totalQty),
                        'ref_type' => 'TRANSFER_OUT',
                        'ref_id' => $transfer->id,
                        'note' => 'Transfer to '.($toShop ? 'Shop' : "Warehouse $toWarehouse"),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Log Movement (IN)
                    if (! $toShop && $toWarehouse) {
                        \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                            'product_id' => $productId,
                            'type' => 'in',
                            'qty' => abs($totalQty),
                            'ref_type' => 'TRANSFER_IN',
                            'ref_id' => $transfer->id,
                            'note' => "Transfer from Warehouse $fromWarehouse",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    // UC7: Update batch warehouse_id so FEFO finds them in the new warehouse
                    if ($toWarehouse) {
                        // Move active batches for this product from source to destination warehouse (FEFO order)
                        $remainingToMove = $totalQty;
                        $batchesToMove = ProductBatch::available()
                            ->where('product_id', $productId)
                            ->where('warehouse_id', $fromWarehouse)
                            ->orderBy('exp_date', 'asc')
                            ->get();

                        foreach ($batchesToMove as $batch) {
                            if ($remainingToMove <= 0) break;
                            if ($batch->qty_remaining <= $remainingToMove) {
                                // Move entire batch
                                $batch->warehouse_id = $toWarehouse;
                                $batch->save();
                                $remainingToMove -= $batch->qty_remaining;
                            } else {
                                // Split: move part of this batch — keep original, create new for destination
                                $newBatch = $batch->replicate();
                                $newBatch->warehouse_id = $toWarehouse;
                                $newBatch->qty_received = $remainingToMove;
                                $newBatch->qty_remaining = $remainingToMove;
                                $newBatch->save();

                                $batch->qty_remaining -= $remainingToMove;
                                $batch->save();
                                $remainingToMove = 0;
                            }
                        }
                    }
                }
            });

        } catch (\Exception $e) {
            return back()->with('error', 'Transfer Failed: '.$e->getMessage());
        }

        return redirect()->route('stock_transfers.index')->with('success', 'Stock transferred successfully.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        // Optional: reverse the transfer if needed
        return back()->with('error', 'Deleting transfers not allowed.');
    }

    public function getStockQuantity(Request $request)
    {
        $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'quantity' => $stock ? $stock->quantity : 0,
        ]);
    }

    public function getProductsByWarehouse(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        if (! $warehouseId) {
            return response()->json([]);
        }

        $products = Product::join('warehouse_stocks', 'products.id', '=', 'warehouse_stocks.product_id')
            ->where('warehouse_stocks.warehouse_id', $warehouseId)
            ->where('warehouse_stocks.quantity', '>', 0)
            ->select('products.id', 'products.item_name')
            ->get();

        return response()->json($products);
    }
}

// delvivery challan
// convet out per  stock ledger maintain
