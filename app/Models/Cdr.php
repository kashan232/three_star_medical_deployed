<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cdr extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'city', 'cdr_no', 'cdr_date', 'fiscal_year', 
        'customer_id', 'account_id', 'percentage', 
        'amount', 'status', 'dated', 'branch_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
