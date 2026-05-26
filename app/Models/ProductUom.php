<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUom extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',           // e.g. "1×100", "Carton", "Strip"
        'pieces_per_box', // NEW — how many base pieces per 1 unit of this UOM
        'purchase_price',
        'sale_price',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * All warehouse stock rows that track this specific UOM.
     */
    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class, 'uom_id');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Total pieces of this UOM stocked across all warehouses.
     */
    public function totalStock(): float
    {
        return (float) $this->warehouseStocks()->sum('total_pieces');
    }

    /**
     * Total pieces in a specific warehouse.
     */
    public function stockInWarehouse(int $warehouseId): float
    {
        return (float) $this->warehouseStocks()
            ->where('warehouse_id', $warehouseId)
            ->value('total_pieces') ?? 0;
    }
}
