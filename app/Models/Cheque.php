<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    public const TYPE_RECEIVED = 'received';
    public const TYPE_ISSUED   = 'issued';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_DEPOSITED = 'deposited';
    public const STATUS_CLEARED   = 'cleared';
    public const STATUS_BOUNCED   = 'bounced';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'cheque_date'    => 'date',
        'issue_date'     => 'date',
        'clearance_date' => 'date',
        'deposited_at'   => 'datetime',
        'cleared_at'     => 'datetime',
        'bounced_at'     => 'datetime',
        'cancelled_at'   => 'datetime',
        'amount'         => 'decimal:2',
    ];

    public function voucherMaster()
    {
        return $this->belongsTo(VoucherMaster::class, 'voucher_master_id');
    }

    public function clearanceVoucher()
    {
        return $this->belongsTo(VoucherMaster::class, 'clearance_voucher_id');
    }

    public function bounceVoucher()
    {
        return $this->belongsTo(VoucherMaster::class, 'bounce_voucher_id');
    }

    public function actualAccount()
    {
        return $this->belongsTo(Account::class, 'actual_account_id');
    }

    public function clearingAccount()
    {
        return $this->belongsTo(Account::class, 'clearing_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function party()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function bouncedBy()
    {
        return $this->belongsTo(User::class, 'bounced_by');
    }

    /**
     * Whether this cheque is active (not cleared/bounced/cancelled) and due today or overdue.
     */
    public function isDue(): bool
    {
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_DEPOSITED], true)) {
            return false;
        }

        return $this->cheque_date && $this->cheque_date->startOfDay()->lte(now()->startOfDay());
    }

    /**
     * Days until due (positive = future days, 0 = due today, negative = overdue days).
     */
    public function daysUntilDue(): ?int
    {
        if (! $this->cheque_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->cheque_date->copy()->startOfDay(), false);
    }

    /**
     * Resolve display name for the party or target account.
     */
    public function getPartyDisplayNameAttribute(): string
    {
        $party = $this->party ?? $this->voucherMaster?->party;
        if ($party) {
            return $party->customer_name ?? $party->name ?? $party->title ?? ('#' . $party->id);
        }

        if ($this->voucherMaster) {
            $counterDetail = $this->voucherMaster->details
                ->first(fn ($d) => (int) $d->account_id !== (int) $this->clearing_account_id);
            if ($counterDetail && $counterDetail->account) {
                return $counterDetail->account->title;
            }
        }

        return '-';
    }

    /**
     * Resolve party category label (Customer, Vendor, Expense/Account).
     */
    public function getPartyCategoryAttribute(): string
    {
        $pType = $this->party_type ?? $this->voucherMaster?->party_type;
        if ($pType === Customer::class) {
            return 'Customer';
        }
        if ($pType === Vendor::class) {
            return 'Vendor';
        }

        return 'Account / Expense';
    }
}
