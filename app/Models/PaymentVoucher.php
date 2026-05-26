<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function generateInvoiceNo()
    {
        $last = \App\Models\VoucherMaster::where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)
            ->where('voucher_no', 'like', 'PVID-%')
            ->orderBy('id', 'desc')
            ->first();
            
        if ($last && preg_match('/PVID-(\d+)/', $last->voucher_no, $matches)) {
            $nextId = (int)$matches[1] + 1;
        } else {
            $nextId = \App\Models\VoucherMaster::where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)->count() + 1;
        }
        return 'PVID-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
