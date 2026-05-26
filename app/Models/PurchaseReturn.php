<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
    'purchase_id',
    'vendor_id',
    'warehouse_id',
    'branch_id',
    'return_invoice',
    'return_date',
    'return_reason',
    'transport',
    'vehicle_no',
    'driver_name',
    'delivery_person',
    'bill_amount',
    'item_discount',
    'extra_discount',
    'net_amount',
    'paid',
    'balance',
    'remarks',
];
 public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // ✅ Warehouse Relationship
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // ✅ Return Items
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    // Dynamic Summaries for Index Page
    public function getBatchSummaryAttribute()
    {
        $batches = $this->items->pluck('batch_no')->filter(fn($v) => !empty($v) && $v != '-')->unique();
        return $batches->isNotEmpty() ? $batches->implode(', ') : '-';
    }

    public function getMfgSummaryAttribute()
    {
        $dates = $this->items->pluck('mfg_date')->filter(fn($v) => !empty($v) && $v != '-')->unique()->map(function($d) {
            return \Carbon\Carbon::parse($d)->format('m/y');
        });
        return $dates->isNotEmpty() ? $dates->implode(', ') : '-';
    }

    public function getExpSummaryAttribute()
    {
        $dates = $this->items->pluck('exp_date')->filter(fn($v) => !empty($v) && $v != '-')->unique()->map(function($d) {
            return \Carbon\Carbon::parse($d)->format('m/y');
        });
        return $dates->isNotEmpty() ? $dates->implode(', ') : '-';
    }

    public function getNewNetAmountAttribute()
    {
        if (!$this->purchase) return 0;
        return max(0, (float)$this->purchase->net_amount - (float)$this->net_amount);
    }
}
