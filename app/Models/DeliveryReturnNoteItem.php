<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryReturnNoteItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function deliveryReturnNote()
    {
        return $this->belongsTo(DeliveryReturnNote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryNoteItem()
    {
        return $this->belongsTo(DeliveryNoteItem::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    public function getQtyFormattedAttribute()
    {
        $parts = explode('.', $this->qty);
        $boxes = (int)($parts[0] ?? 0);
        $loose = isset($parts[1]) ? (int)$parts[1] : 0;
        
        $ppb = ($this->product && $this->product->pieces_per_box > 0) ? (int)$this->product->pieces_per_box : 1;
        
        if ($ppb == 1) {
            return $boxes . ' Pcs';
        }
        
        $res = [];
        if ($boxes > 0) $res[] = $boxes . ' Box';
        if ($loose > 0) $res[] = $loose . ' Pcs';
        
        return count($res) > 0 ? implode(', ', $res) : '0 Pcs';
    }
}
