<?php

// app/Models/CustomerPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    protected $fillable = [
        'customer_id',
        'sale_id',
        'dc_note_id',
        'admin_or_user_id',
        'amount',
        'commission_triggered',
        'payment_method',
        'payment_date',
        'account_id',
        'description',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    protected static function booted()
    {
        static::created(function ($payment) {
            if ($payment->sale_id && ! $payment->commission_triggered) {
                // Call payroll calculation service
                app(\App\Services\PayrollCalculationService::class)->generateInstantCommission($payment);

                // Mark triggered to avoid loops
                $payment->commission_triggered = true;
                $payment->saveQuietly(); // avoid re-triggering events
            }
        });
    }
}
