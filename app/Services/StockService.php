<?php

namespace App\Services;

use App\Models\WarehouseStock;
use App\Models\ProductUom;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * StockService — Central UOM-aware inventory ledger.
 *
 * All stock mutations (purchase in, sale out, returns, transfers)
 * MUST go through this service. Direct WarehouseStock writes are forbidden.
 *
 * Stock is tracked per (warehouse_id, product_id, uom_id).
 * uom_id = NULL means the base/unclassified piece unit.
 */
class StockService
{
    /**
     * Add stock (purchase receipt, sale return).
     */
    public static function credit(
        int   $productId,
        ?int  $uomId,
        int   $warehouseId,
        int   $branchId,
        float $pieces
    ): void {
        static::adjust($productId, $uomId, $warehouseId, $branchId, +abs($pieces));
    }

    /**
     * Remove stock (sale dispatch, purchase return).
     */
    public static function debit(
        int   $productId,
        ?int  $uomId,
        int   $warehouseId,
        int   $branchId,
        float $pieces
    ): void {
        static::adjust($productId, $uomId, $warehouseId, $branchId, -abs($pieces));
    }

    /**
     * Core adjustment — positive delta = add, negative = subtract.
     * Must be called inside a DB::transaction() on the caller side.
     */
    public static function adjust(
        int   $productId,
        ?int  $uomId,
        int   $warehouseId,
        int   $branchId,
        float $pieceDelta
    ): void {
        // Resolve pieces_per_box from UOM record, or from product master
        $ppb = static::resolvePpb($productId, $uomId);

        // Lock the specific row for this (warehouse, product, uom) triple
        $query = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate();

        if ($uomId !== null) {
            $query->where('uom_id', $uomId);
        } else {
            $query->whereNull('uom_id');
        }

        $stock = $query->first();

        if ($stock) {
            $newPieces = $stock->total_pieces + $pieceDelta;

            // Guard: never go below 0
            if ($newPieces < 0) {
                $newPieces = 0;
            }

            $stock->total_pieces = $newPieces;
            $stock->quantity     = static::piecesToDisplay($newPieces, $ppb);
            $stock->branch_id    = $branchId;
            $stock->save();
        } else {
            // Only create a new row on CREDIT operations
            if ($pieceDelta <= 0) {
                // Nothing to debit — stock row does not exist; ignore
                return;
            }

            $pieces = abs($pieceDelta);

            WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'branch_id'    => $branchId,
                'product_id'   => $productId,
                'uom_id'       => $uomId,
                'total_pieces' => $pieces,
                'quantity'     => static::piecesToDisplay($pieces, $ppb),
            ]);
        }
    }

    /**
     * Get the current piece balance for a (product, uom, warehouse) triple.
     */
    public static function balance(int $productId, ?int $uomId, int $warehouseId): float
    {
        $query = WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId);

        if ($uomId !== null) {
            $query->where('uom_id', $uomId);
        } else {
            $query->whereNull('uom_id');
        }

        return (float) ($query->value('total_pieces') ?? 0);
    }

    /**
     * Get total pieces across ALL uoms for a product in a warehouse.
     */
    public static function totalBalance(int $productId, int $warehouseId): float
    {
        return (float) WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->sum('total_pieces');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve pieces_per_box: UOM record → product master → 1
     */
    private static function resolvePpb(int $productId, ?int $uomId): int
    {
        if ($uomId) {
            $uom = ProductUom::find($uomId);
            if ($uom && $uom->pieces_per_box > 0) {
                return (int) $uom->pieces_per_box;
            }
        }

        $product = Product::find($productId);
        $ppb = (int) ($product->pieces_per_box ?? 1);
        return $ppb > 0 ? $ppb : 1;
    }

    /**
     * Convert piece count to Box.Loose display format (e.g. 253 pcs @ 100/box = "2.53")
     * If ppb = 1 → just return the piece count as float.
     */
    private static function piecesToDisplay(float $pieces, int $ppb): float
    {
        if ($ppb <= 1) {
            return $pieces;
        }

        $boxes = intdiv((int) $pieces, $ppb);
        $loose = (int) $pieces % $ppb;

        return (float) "{$boxes}.{$loose}";
    }
}
