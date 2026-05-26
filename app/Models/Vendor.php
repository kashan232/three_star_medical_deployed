<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'opening_balance',
        'vendor_code', 'party_type', 'is_active', 'title', 'business_name', 'ntn_no', 'cnic', 'url',
        'credit_terms', 'payment_mode', 'credit_limit', 'commission_percent', 'wh_tax', 'margin_percent',
        'city', 'country', 'fax', 'contact_person', 'contact_person_designation', 'contact_person_mobile',
        'contact_person_whatsapp', 'contact_person_2', 'contact_person_2_designation', 'contact_person_2_mobile',
        'contact_person_2_whatsapp',
        'shipping_address', 'shipping_city', 'shipping_country', 'shipping_phone', 'shipping_fax', 'shipping_email',
        'gst_no', 'dsl_no', 'drap_no', 'ftn_no',
        'branch_id', 'bank_name', 'cheque_no', 'cheque_date'
    ];

    use HasFactory;

    public function ledger()
    {
        return $this->hasOne(VendorLedger::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Polymorphic relationship to journal entries
     */
    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'party');
    }
}
