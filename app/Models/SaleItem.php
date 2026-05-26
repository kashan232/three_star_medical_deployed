<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'warehouse_id', 'product_id',
        'uom_id',                              // NEW — FK to product_uoms
        'brand_id', 'category_id', 'sub_category_id', 'unit_id',
        'qty', 'delivered_qty', 'price', 'total',
        'discount_percent', 'discount_amount',
        'gst_percent', 'gst_amount',
        'inc_tax', 'adv_tax',
        'color', 'total_pieces', 'loose_pieces',
        'free_qty', 'free_total_pieces',
        'price_per_piece', 'price_per_m2',
        'uom_name', 'uom_factor', 'size_mode', 'pieces_per_box',
        'lot_number', 'exp_date', 'hs_code',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** The specific UOM packing used for this sale line */
    public function uom()
    {
        return $this->belongsTo(ProductUom::class, 'uom_id');
    }

    public function batches()
    {
        return $this->belongsToMany(ProductBatch::class, 'sale_item_batches', 'sale_item_id', 'product_batch_id')
            ->withPivot('qty_deducted');
    }
}
