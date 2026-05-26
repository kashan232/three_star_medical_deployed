<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'branch_id',
        'product_id',
        'uom_id',        // NEW — NULL = base/unclassified unit
        'quantity',      // Box.Loose display format
        'boxes_quantity',
        'total_pieces',  // Authoritative raw piece count
        'remarks',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /** Alias kept for backward compat */
    public function stockWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** The specific UOM this stock row tracks (null = piece/base unit) */
    public function uom()
    {
        return $this->belongsTo(ProductUom::class, 'uom_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    /**
     * Filter to a specific UOM (null = unclassified/base rows).
     */
    public function scopeForUom($query, ?int $uomId)
    {
        if ($uomId === null) {
            return $query->whereNull('uom_id');
        }
        return $query->where('uom_id', $uomId);
    }

    /**
     * Filter to a specific warehouse.
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    // Legacy many-to-many kept for any old code that uses it
    public function products()
    {
        return $this->belongsToMany(Product::class, 'warehouse_stocks', 'warehouse_id', 'product_id')
            ->withPivot('quantity', 'price', 'remarks');
    }
}
