<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'mfg_date'      => 'date:Y-m-d',
        'exp_date'      => 'date:Y-m-d',
        'qty_received' => 'decimal:2',
        'qty_remaining' => 'decimal:2',
    ];

    // ===================== Relationships =====================

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class);
    }

    // ===================== Scopes =====================

    /** Batches that are active (not consumed or already expired) */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->where('qty_remaining', '>', 0)
            ->where('exp_date', '>', now()->toDateString());
    }

    /** Batches expiring within $days days */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->where('qty_remaining', '>', 0)
            ->whereBetween('exp_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }

    /** Already expired batches with remaining stock */
    public function scopeExpired($query)
    {
        return $query->where('exp_date', '<', now()->toDateString())
            ->where('qty_remaining', '>', 0);
    }

    // ===================== Helpers =====================

    public function getIsNonExpiringAttribute(): bool
    {
        return $this->exp_date && Carbon::parse($this->exp_date)->year >= 2090;
    }

    public function getDaysToExpiryAttribute(): int
    {
        if ($this->is_non_expiring) {
            return 99999;
        }
        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->exp_date)->startOfDay(), false);
    }

    public function getExpiryStatusAttribute(): string
    {
        if ($this->is_non_expiring) {
            return 'no_expiry';
        }
        $days = $this->days_to_expiry;
        if ($days < 0) {
            return 'expired';
        }
        if ($days <= 90) {
            return 'critical';
        }
        if ($days <= 180) {
            return 'warning';
        }

        return 'ok';
    }

    public function getIsNoBatchAttribute(): bool
    {
        $b = strtoupper(trim($this->batch_number ?? ''));
        return in_array($b, ['NO-BATCH', 'NO BATCH', 'DEFAULT', 'N/A', 'NONE', '']);
    }

    public function getFormattedBatchNumberAttribute(): string
    {
        return $this->is_no_batch ? 'No Batch' : ($this->batch_number ?? 'No Batch');
    }

    public function getExpiryBadgeClassAttribute(): string
    {
        return match ($this->expiry_status) {
            'no_expiry' => 'bg-success text-white',
            'expired' => 'bg-danger',
            'critical' => 'bg-warning text-dark',
            'warning' => 'bg-info text-dark',
            default => 'bg-success',
        };
    }
}
