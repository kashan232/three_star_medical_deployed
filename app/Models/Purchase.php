<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'extra_cost' => 'decimal:2',
        'total_gst'  => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'is_gst_invoice' => 'boolean',
    ];

    /**
     * Total Discount = sum(Item Discounts) + Bill Discount
     */
    public function getDiscountAmountAttribute()
    {
        $itemDiscounts = $this->items->sum(function($item) {
            return (float) $item->line_discount_amount;
        });
        
        $subtotal = (float)$this->subtotal;
        $billDisc = (float)$this->discount;
        $billDiscType = $this->discount_type;
        $billDiscAmount = ($billDiscType === 'percent') ? ($subtotal * $billDisc / 100) : $billDisc;
        
        return $itemDiscounts + $billDiscAmount;
    }

    /**
     * Gross Total = Total amount before any discounts
     */
    public function getGrossTotalAttribute()
    {
        return $this->items->sum(function($item) {
            return (float)$item->line_total + (float)$item->line_discount_amount;
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function payments()
    {
        return $this->hasMany(VendorPayment::class, 'purchase_id');
    }

    /**
     * Generate next purchase invoice number.
     * Format:  PO-XXXXX   e.g.  PO-00001
     */
    public static function generateInvoiceNo(string $prefix = 'PO-', ?int $branchId = null): string
    {
        $query = static::where('invoice_no', 'like', "{$prefix}%")
            ->orderByDesc('id');

        // Scope by branch for independent sequences
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $last = $query->value('invoice_no');

        $num = $last ? (int) substr($last, strlen($prefix)) : 0;

        return $prefix . str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }
}
