<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockEntryService
{
    /**
     * Add stock to a warehouse.
     *
     * @return WarehouseStock
     *
     * @throws Exception
     */
    public function addStock(int $warehouseId, int $productId, int $totalPieces, int $totalBox = 0, ?string $remarks = null, ?int $uomId = null)
    {
        return DB::transaction(function () use ($warehouseId, $productId, $totalPieces, $totalBox, $remarks, $uomId) {
            // 1. Validation
            $warehouse = Warehouse::findOrFail($warehouseId);
            $product = Product::findOrFail($productId);

            if ($totalPieces < 0) { 
                throw new \InvalidArgumentException('Quantity must be non-negative.');
            }

            // 2. Get or Create Warehouse Stock Record (UOM-aware)
            $stock = WarehouseStock::firstOrNew([
                'warehouse_id' => $warehouseId,
                'product_id'   => $productId,
                'uom_id'       => $uomId,
            ]);

            // Initialize if new
            if (! $stock->exists) {
                $stock->quantity = 0; // Legacy field, treated as Box Quantity
                $stock->total_pieces = 0;
                $stock->branch_id = $warehouse->branch_id;
            }

            // 3. Update Stock
            $stock->total_pieces += $totalPieces;
            $stock->quantity     += $totalBox;
            $stock->remarks       = $remarks;

            $stock->save();

            // 4. Log Movement (Using existing columns only)
            DB::table('stock_movements')->insert([
                'product_id' => $productId,
                'type'       => 'in',
                'qty'        => $totalPieces,
                'ref_type'   => 'MANUAL_ADD',
                'ref_id'     => $stock->id,
                'note'       => $remarks ?: 'Manual stock addition (UOM: ' . ($uomId ? $product->uom?->name : 'Base') . ')',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $stock;
        });
    }
}
