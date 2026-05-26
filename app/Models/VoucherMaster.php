<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherMaster extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';

    const STATUS_POSTED = 'posted';

    const STATUS_CANCELLED = 'cancelled';

    // Type Constants
    const TYPE_RECEIPT = 'receipt';

    const TYPE_PAYMENT = 'payment';

    const TYPE_EXPENSE = 'expense';

    const TYPE_JOURNAL = 'journal';

    const TYPE_CONTRA = 'contra';

    /**
     * Branch this voucher belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate next voucher number scoped to a branch and type.
     * Format: PV-B1-0001 (Payment Voucher, Branch 1, #0001)
     */
    public static function generateVoucherNo(string $type, ?int $branchId = null): string
    {
        $typeCode = strtoupper(substr($type, 0, 2)); // e.g. 'PY' for payment, 'RC' for receipt
        $branchCode = $branchId ? 'B'.$branchId.'-' : '';
        $prefix = $typeCode.'-'.$branchCode;

        $last = static::where('voucher_no', 'like', "{$prefix}%")
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('id')
            ->value('voucher_no');

        $num = $last ? (int) substr($last, strlen($prefix)) : 0;

        return $prefix.str_pad($num + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function details()
    {
        return $this->hasMany(VoucherDetail::class, 'voucher_master_id');
    }

    // Polymorphic relation to Party (Customer, Vendor, etc.)
    public function party()
    {
        return $this->morphTo();
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
