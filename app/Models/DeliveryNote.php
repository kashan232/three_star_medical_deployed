<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DeliveryNote extends Model
{
    protected $guarded = [];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(DeliveryNoteItem::class, 'dc_note_id');
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class, 'dc_note_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Generate DC number in format {saleSeq}{dcCount padded to 3}.
     *
     * saleSeq = sequential position of this sale_id among all sales that have had DCs
     *           (determined by the order in which each sale_id first appeared in delivery_notes).
     * dcCount = number of DCs already created for this sale + 1.
     *
     * Examples:
     *   Sale A first DC  → 1001
     *   Sale A second DC → 1002
     *   Sale B first DC  → 2001
     *   Sale B second DC → 2002
     *
     * @param  int  $saleId   The sale_id this DC belongs to (required for new format).
     * @param  int|null $branchId  Branch scope (kept for backward compat, not used in numbering).
     */
    public static function generateDcNo(?int $saleId, ?int $branchId = null): string
    {
        if ($saleId === null) {
            $dcCount = self::whereNull('sale_id')->count();
            return '0-' . str_pad($dcCount + 1, 2, '0', STR_PAD_LEFT);
        }

        // 1. Find the sequential rank of this sale_id among all sales that have DCs
        //    (ordered by the earliest DC id for each sale_id).
        $saleRank = DB::table('delivery_notes')
            ->whereNotNull('sale_id')
            ->select('sale_id')
            ->groupBy('sale_id')
            ->orderBy(DB::raw('MIN(id)'))
            ->pluck('sale_id')
            ->search($saleId);      // returns 0-based index or false

        if ($saleRank === false) {
            // This sale_id has NO existing DCs yet → it will be the NEXT new sale
            $saleRank = DB::table('delivery_notes')
                ->whereNotNull('sale_id')
                ->distinct()
                ->count('sale_id');  // count of distinct sales already in the table
        }

        $saleSeq = $saleRank + 1;   // 1-based

        // 2. Count existing DCs for this specific sale_id
        $dcCount = self::where('sale_id', $saleId)->count();

        $nextDc = $dcCount + 1;     // next sequence for this sale

        // Format: {saleSeq}-{dcCount padded to 2}
        // e.g. saleSeq=1, dc=1 → "1-01"  |  saleSeq=2, dc=1 → "2-01"  |  1-02, 3-01 etc.
        return $saleSeq . '-' . str_pad($nextDc, 2, '0', STR_PAD_LEFT);
    }
}
