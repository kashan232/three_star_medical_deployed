<?php

// app/Models/Sale.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_details' => 'array',
    ];

    /**
     * Branch this sale belongs to.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function customer()
    {
        return $this->customer_relation();
    }

    public function product_relation()
    {
        return $this->belongsTo(Product::class, 'product', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Hr\Employee::class, 'employee_id');
    }

    /**
     * Payments linked to this specific sale
     */
    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'sale_id');
    }

    public static function generateInvoiceNo(?int $branchId = null, $prefix = 'INV-')
    {
        $query = self::where('invoice_no', 'LIKE', $prefix.'%')
            ->orderBy('id', 'desc');

        // Scope to this branch only
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $lastSale = $query->first();

        if (! $lastSale || ! $lastSale->invoice_no) {
            return $prefix.'0001';
        }

        $numericPart = str_replace($prefix, '', $lastSale->invoice_no);
        $lastNumber = (int) $numericPart;

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    public function deliveryReturnNotes()
    {
        return $this->hasMany(DeliveryReturnNote::class, 'sale_id');
    }

    /**
     * Accessor for due amount (Total Net - Cash/Paid)
     */
    public function getDueAmountAttribute()
    {
        return max(0, (float)$this->total_net - (float)$this->cash);
    }
}
