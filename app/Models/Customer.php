<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'customer_id', 'customer_name', 'customer_name_ur', 'cnic', 'filer_type', 'zone',
        'contact_person', 'mobile', 'email_address', 'contact_person_2', 'mobile_2',
        'email_address_2', 'opening_balance', 'balance_range', 'address', 'status',
        'customer_type', 'previous_balance', 'sales_officer_id',
        'party_type', 'is_active', 'abr', 'title', 'business_name', 'url', 'ntn_no', 'dsl_no', 'ftn_no',
        'city', 'country', 'fax', 'credit_terms', 'payment_mode', 'category', 'credit_status',
        'loyalty_group', 'default_price', 'v1_mc', 'v2_mc', 'van_no', 'cng', 'card_expiry',
        'contact_person_designation', 'contact_person_whatsapp', 'contact_person_2_designation', 'contact_person_2_whatsapp',
        'shipping_address', 'shipping_city', 'shipping_country', 'shipping_phone', 'shipping_fax', 'shipping_email',
        'gst_no', 'drap_no', 'branch_id', 'bank_name', 'cheque_no', 'cheque_date'
    ];

    public function salesOfficer()
    {
        return $this->belongsTo(\App\Models\Hr\Employee::class, 'sales_officer_id');
    }

    /**
     * Polymorphic relationship to journal entries
     */
    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'party');
    }

    /**
     * Get current balance from BalanceService
     */
    public function getPreviousBalanceAttribute()
    {
        // If previous_balance column exists and has value, use it
        if (isset($this->attributes['previous_balance'])) {
            return $this->attributes['previous_balance'];
        }
        
        // Otherwise calculate from journal entries
        $balanceService = app(\App\Services\BalanceService::class);
        return $balanceService->getCustomerBalance($this->id);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function deliveryReturnNotes()
    {
        return $this->hasMany(DeliveryReturnNote::class, 'customer_id');
    }
}
