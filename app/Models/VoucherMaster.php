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
        'cheque_date' => 'date',
        'posted_at' => 'datetime',
        'verified_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_CANCELLED = 'cancelled';

    // Type Constants
    const TYPE_CRV = 'crv'; // Cash Receiving Voucher
    const TYPE_BRV = 'brv'; // Bank Receiving Voucher
    const TYPE_CPV = 'cpv'; // Cash Payment Voucher
    const TYPE_BPV = 'bpv'; // Bank Payment Voucher
    const TYPE_JV  = 'jv';  // Journal Voucher
    
    // Legacy Type Constants
    const TYPE_RECEIPT = 'receipt';
    const TYPE_PAYMENT = 'payment';
    const TYPE_EXPENSE = 'expense';
    const TYPE_JOURNAL = 'journal';
    const TYPE_CONTRA  = 'contra';

    /**
     * Branch this voucher belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate next voucher number scoped to a branch and type.
     * Format: CRV-0001, BRV-0001, CPV-0001, BPV-0001, JV-0001
     */
    public static function generateVoucherNo(string $type, ?int $branchId = null): string
    {
        $typeMap = [
            'crv' => 'CRV',
            'brv' => 'BRV',
            'cpv' => 'CPV',
            'bpv' => 'BPV',
            'jv'  => 'JV',
            'receipt' => 'CRV',
            'payment' => 'CPV',
            'expense' => 'CPV',
            'journal' => 'JV',
            'contra'  => 'JV',
        ];

        $prefixCode = $typeMap[strtolower($type)] ?? strtoupper(substr($type, 0, 3));
        $branchCode = ($branchId && $branchId > 1) ? 'B'.$branchId.'-' : '';
        $prefix = $prefixCode.'-'.$branchCode;

        $last = static::where('voucher_no', 'like', "{$prefixCode}-%")
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('id')
            ->value('voucher_no');

        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $num = (int)$m[1];
        } else {
            $num = 0;
        }

        return $prefixCode.'-'.str_pad($num + 1, 5, '0', STR_PAD_LEFT);
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

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
