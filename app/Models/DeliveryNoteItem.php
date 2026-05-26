<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    protected $guarded = [];

    public function dcNote()
    {
        return $this->belongsTo(DeliveryNote::class, 'dc_note_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function saleItem()
    {
        return $this->belongsTo(SaleItem::class, 'sale_item_id');
    }

    public function uom()
    {
        return $this->belongsTo(ProductUom::class, 'uom_id');
    }
}
