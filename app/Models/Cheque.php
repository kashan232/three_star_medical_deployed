<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cheque_date' => 'date',
        'cleared_at' => 'datetime',
        'bounced_at' => 'datetime',
    ];

    public function voucherMaster()
    {
        return $this->belongsTo(VoucherMaster::class, 'voucher_master_id');
    }

    public function actualAccount()
    {
        return $this->belongsTo(Account::class, 'actual_account_id');
    }
}
