<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function log($productId, $type, $oldPrice, $newPrice, $refType = 'MANUAL', $refNo = null, $description = null)
    {
        if ($oldPrice == $newPrice) {
            return null;
        }

        return self::create([
            'product_id' => $productId,
            'type' => $type, // purchase or sale
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'ref_type' => $refType,
            'ref_no' => $refNo,
            'description' => $description,
            'user_id' => auth()->id(),
        ]);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
