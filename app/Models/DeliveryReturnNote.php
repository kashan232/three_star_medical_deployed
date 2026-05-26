<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryReturnNote extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryReturnNoteItem::class);
    }

    public static function generateReturnNo()
    {
        $prefix = 'DRN-';
        $last = self::orderBy('id', 'desc')->first();
        if (!$last) {
            return $prefix . '0001';
        }
        $lastNo = (int) str_replace($prefix, '', $last->return_no);
        return $prefix . str_pad($lastNo + 1, 4, '0', STR_PAD_LEFT);
    }
}
