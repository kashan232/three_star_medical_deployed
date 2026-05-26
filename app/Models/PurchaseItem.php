<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'uom_factor' => 1,
        'size_mode' => 'by_pieces',
        'length' => '',
        'width' => '',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'item_discount' => 'decimal:2',
        'line_total'    => 'decimal:2',
        'gst_percent'   => 'decimal:2',
        'gst_amount'    => 'decimal:2',
        'it_percent'    => 'decimal:2',
        'adv_tax_percent' => 'decimal:2',
        'mfg_date'      => 'date:Y-m-d',
        'exp_date'      => 'date:Y-m-d',
    ];

    
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product()  { return $this->belongsTo(Product::class); }
    public function uom()      { return $this->belongsTo(ProductUom::class, 'uom_id'); }

    public function batch()
    {
        return $this->hasOne(ProductBatch::class, 'purchase_item_id');
    }

    /**
     * Get the total pieces calculation for this item.
     * Centralizes the logic used across controllers and reports.
     */
    public function getTotalPiecesAttribute()
    {
        $ppb = (float)($this->uom_factor > 0 ? $this->uom_factor : 1);
        $sizeMode = $this->size_mode ?? 'by_pieces';
        $qty = $this->qty;
        $loose = (float)($this->loose_qty ?? 0);

        if ($sizeMode === 'by_m2') {
            return (float) $qty;
        }

        $total_pieces = 0;
        $qtyStr = (string)$qty;

        if (strpos($qtyStr, '.') !== false) {
            $parts = explode('.', $qtyStr);
            $boxes = (int)$parts[0];
            $pieces = (int)$parts[1];
            $total_pieces = ($boxes * $ppb) + $pieces;
        } else {
            $total_pieces = ((float)$qty * $ppb);
        }

        return $total_pieces + $loose;
    }

    /**
     * Calculate the absolute discount amount for this specific line.
     */
    public function getLineDiscountAmountAttribute()
    {
        $disc = (float) $this->item_discount;
        $type = $this->item_discount_type ?? 'amount';
        
        if ($type === 'percent') {
            if ($disc >= 100) return (float) $this->line_total; 
            // Reversed calculation from discounted total
            $val = (float) $this->line_total;
            $gross = $val / (1 - ($disc / 100));
            return $gross - $val;
        }
        
        return $disc;
    }
}
