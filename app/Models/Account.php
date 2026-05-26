<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function head()
    {
        return $this->belongsTo(AccountHead::class, 'head_id');
    }

    /**
     * Get dynamically calculated, real-time balance.
     * Respects Account Type (Debit = Asset/Expense, Credit = Liability/Equity/Income)
     */
    public function getCalculatedBalanceAttribute()
    {
        $opening = (float)($this->opening_balance ?? 0);
        
        $debits = \App\Models\JournalEntry::where('account_id', $this->id)->sum('debit');
        $credits = \App\Models\JournalEntry::where('account_id', $this->id)->sum('credit');

        if ($this->type === 'Credit') {
            // Income / Liability: Balance increases with Credits, decreases with Debits (like Sale Returns)
            return $opening + $credits - $debits;
        } else {
            // Asset / Expense: Balance increases with Debits, decreases with Credits (like Purchase Returns)
            return $opening + $debits - $credits;
        }
    }
}
