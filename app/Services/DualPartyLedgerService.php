<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Vendor;
use App\Models\VendorLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DualPartyLedgerService
{
    /**
     * Find twin Vendor for a given Customer.
     */
    public function findTwinVendor(Customer $customer): ?Vendor
    {
        $cCode = trim($customer->customer_id ?? '');
        $cCnic = preg_replace('/[^0-9]/', '', $customer->cnic ?? '');
        $cName = strtoupper(trim(preg_replace('/\s+/', ' ', $customer->customer_name ?? '')));
        $cNum  = preg_match('/(\d+)$/', $cCode, $m) ? (int)$m[1] : null;

        // 1. Direct code exact match (e.g. CUST-0001 == CUST-0001)
        if ($cCode !== '') {
            $v = Vendor::where('vendor_code', $cCode)->first();
            if ($v) return $v;
        }

        // 2. CNIC exact match (when at least 10 digits)
        if (strlen($cCnic) >= 10) {
            $v = Vendor::where(function ($q) use ($customer, $cCnic) {
                $q->where('cnic', $customer->cnic)
                  ->orWhereRaw("REPLACE(REPLACE(cnic, '-', ''), ' ', '') = ?", [$cCnic]);
            })->first();
            if ($v) return $v;
        }

        // 3. Exact Name match
        if (strlen($cName) >= 3) {
            $v = Vendor::whereRaw('UPPER(TRIM(name)) = ?', [$cName])->first();
            if ($v) return $v;
        }

        // 4. Code numeric suffix match + Name similarity / ID match
        if ($cNum && strlen($cName) >= 3) {
            $v = Vendor::where(function ($q) use ($cNum) {
                $pad = str_pad($cNum, 4, '0', STR_PAD_LEFT);
                $q->where('vendor_code', "VND-{$pad}")
                  ->orWhere('vendor_code', "VEND-{$pad}")
                  ->orWhere('vendor_code', "V-{$pad}");
            })->first();

            if ($v && (strtoupper(trim($v->name)) === $cName || $v->id === $customer->id)) {
                return $v;
            }
        }

        return null;
    }

    /**
     * Find twin Customer for a given Vendor.
     */
    public function findTwinCustomer(Vendor $vendor): ?Customer
    {
        $vCode = trim($vendor->vendor_code ?? '');
        $vCnic = preg_replace('/[^0-9]/', '', $vendor->cnic ?? '');
        $vName = strtoupper(trim(preg_replace('/\s+/', ' ', $vendor->name ?? '')));
        $vNum  = preg_match('/(\d+)$/', $vCode, $m) ? (int)$m[1] : null;

        // 1. Direct code exact match
        if ($vCode !== '') {
            $c = Customer::where('customer_id', $vCode)->first();
            if ($c) return $c;
        }

        // 2. CNIC exact match
        if (strlen($vCnic) >= 10) {
            $c = Customer::where(function ($q) use ($vendor, $vCnic) {
                $q->where('cnic', $vendor->cnic)
                  ->orWhereRaw("REPLACE(REPLACE(cnic, '-', ''), ' ', '') = ?", [$vCnic]);
            })->first();
            if ($c) return $c;
        }

        // 3. Exact Name match
        if (strlen($vName) >= 3) {
            $c = Customer::whereRaw('UPPER(TRIM(customer_name)) = ?', [$vName])->first();
            if ($c) return $c;
        }

        // 4. Code numeric suffix match + Name similarity / ID match
        if ($vNum && strlen($vName) >= 3) {
            $c = Customer::where(function ($q) use ($vNum) {
                $pad = str_pad($vNum, 4, '0', STR_PAD_LEFT);
                $q->where('customer_id', "CUST-{$pad}")
                  ->orWhere('customer_id', "C-{$pad}");
            })->first();

            if ($c && (strtoupper(trim($c->customer_name)) === $vName || $c->id === $vendor->id)) {
                return $c;
            }
        }

        return null;
    }

    /**
     * Get unified combined ledger data for a Customer.
     */
    public function getCustomerLedgerData(int $customerId, ?string $startDate = null, ?string $endDate = null, ?int $branchId = null): array
    {
        $startDate = $startDate ?? '2000-01-01';
        $endDate   = $endDate   ?? date('Y-m-d');

        $customer = Customer::find($customerId);
        if (!$customer) {
            return [
                'party'           => null,
                'twin_party'      => null,
                'is_dual'         => false,
                'opening_balance' => 0,
                'closing_balance' => 0,
                'total_debit'     => 0,
                'total_credit'    => 0,
                'transactions'    => collect([]),
            ];
        }

        $twinVendor = $this->findTwinVendor($customer);
        $isDual     = $twinVendor !== null;

        // 1. Calculate Opening Balance before $startDate
        // Customer side opening
        $lastCustEntry = CustomerLedger::where('customer_id', $customerId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '<', $startDate)
            ->orderBy(DB::raw('DATE(created_at)'), 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $custOpening = $lastCustEntry ? (float)$lastCustEntry->closing_balance : (float)($customer->opening_balance ?? 0);

        // Vendor side opening (if dual)
        $vendOpening = 0.0;
        if ($twinVendor) {
            $lastVendEntry = VendorLedger::where('vendor_id', $twinVendor->id)
                ->whereNull('deleted_at')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '<', $startDate)
                ->orderBy(DB::raw('DATE(created_at)'), 'desc')
                ->orderBy('id', 'desc')
                ->first();
            $vendOpening = $lastVendEntry ? (float)$lastVendEntry->closing_balance : (float)($twinVendor->opening_balance ?? 0);
        }

        // Net opening: Customer Receivable (+) minus Vendor Payable (-)
        $netOpeningBalance = $custOpening - $vendOpening;

        // 2. Fetch Customer transactions in date range
        $custLedgers = CustomerLedger::where('customer_id', $customerId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $mergedEntries = collect([]);

        foreach ($custLedgers as $row) {
            $prev  = (float) $row->previous_balance;
            $close = (float) $row->closing_balance;
            $dr    = (float) ($row->debit ?? 0);
            $cr    = (float) ($row->credit ?? 0);

            if ($dr == 0 && $cr == 0) {
                if ($close > $prev) {
                    $dr = $close - $prev;
                } elseif ($close < $prev) {
                    $cr = $prev - $close;
                }
            }

            $desc = $row->description ?? '';
            $type = $this->classifyTransactionType($desc, 'customer');

            $mergedEntries->push((object) [
                'id'          => $row->id,
                'created_at'  => $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : $startDate,
                'date'        => $row->created_at ? $row->created_at->format('Y-m-d') : $startDate,
                'description' => $desc,
                'debit'       => $dr,
                'credit'      => $cr,
                'source'      => 'customer',
                'type'        => $type,
                'raw_entry'   => $row,
            ]);
        }

        // 3. Fetch Vendor transactions in date range (if dual)
        if ($twinVendor) {
            $vendLedgers = VendorLedger::where('vendor_id', $twinVendor->id)
                ->whereNull('deleted_at')
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy(DB::raw('DATE(created_at)'), 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($vendLedgers as $row) {
                $prev  = (float) $row->previous_balance;
                $close = (float) $row->closing_balance;
                $vdr   = (float) ($row->debit ?? 0);
                $vcr   = (float) ($row->credit ?? 0);

                if ($vdr == 0 && $vcr == 0) {
                    if ($close > $prev) {
                        $vcr = $close - $prev; // Vendor credit increase = we owe them
                    } elseif ($close < $prev) {
                        $vdr = $prev - $close; // Vendor debit increase = we paid them
                    }
                }

                // In Combined Statement:
                // Payment to Vendor / Return to Vendor decreases what we owe -> DEBIT (+)
                // Purchase bill from Vendor increases what we owe -> CREDIT (-)
                $dr = $vdr;
                $cr = $vcr;

                $desc = $row->description ?? '';
                $type = $this->classifyTransactionType($desc, 'vendor');

                // Prefix description clearly for user awareness
                $displayDesc = $desc;
                if (!str_starts_with($desc, '[Vendor') && !str_starts_with($desc, '[Purchase')) {
                    $displayDesc = '[Vendor / Purchase] ' . ($desc ?: 'Purchase / Payment');
                }

                $mergedEntries->push((object) [
                    'id'          => $row->id,
                    'created_at'  => $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : $startDate,
                    'date'        => $row->created_at ? $row->created_at->format('Y-m-d') : $startDate,
                    'description' => $displayDesc,
                    'debit'       => $dr,
                    'credit'      => $cr,
                    'source'      => 'vendor',
                    'type'        => $type,
                    'raw_entry'   => $row,
                ]);
            }
        }

        // 4. Chronological sort
        $sortedEntries = $mergedEntries->sortBy(function ($item) {
            return $item->created_at . str_pad($item->id, 10, '0', STR_PAD_LEFT);
        })->values();

        // 5. Calculate Running Balance
        $runningBalance = $netOpeningBalance;
        $totalDebit     = 0.0;
        $totalCredit    = 0.0;

        $processedTransactions = $sortedEntries->map(function ($item) use (&$runningBalance, &$totalDebit, &$totalCredit, $customer) {
            $dr = (float) $item->debit;
            $cr = (float) $item->credit;

            $totalDebit  += $dr;
            $totalCredit += $cr;

            $runningBalance += ($dr - $cr);

            // Ref invoice extraction
            $ref = '—';
            if (preg_match('/#(inv|sin|grn|pv|cpv|crv|sr|so|po|jv|rv|re|pr|prtn|rvid)[- ]?([A-Z0-9\-_]+)/i', $item->description, $matches)) {
                $ref = strtoupper($matches[0]);
            } elseif (preg_match('/\b(INV|SIN|GRN|CPV|CRV|PRTN|SRN)-\d+\b/i', $item->description, $matches)) {
                $ref = strtoupper($matches[0]);
            }

            return (object) [
                'id'               => $item->id,
                'created_at'       => $item->date,
                'datetime'         => $item->created_at,
                'customer'         => $customer,
                'description'      => $item->description,
                'ref'              => $ref,
                'debit'            => $dr,
                'credit'           => $cr,
                'running_balance'  => $runningBalance,
                'closing_balance'  => $runningBalance,
                'previous_balance' => $runningBalance - ($dr - $cr),
                'source'           => $item->source,
                'type'             => $item->type,
            ];
        });

        // 6. Push balance brought forward if no transactions but has balance
        if ($processedTransactions->isEmpty()) {
            $processedTransactions->push((object) [
                'id'               => 0,
                'created_at'       => $startDate,
                'datetime'         => $startDate . ' 00:00:00',
                'customer'         => $customer,
                'description'      => 'Balance Brought Forward',
                'ref'              => '—',
                'debit'            => 0,
                'credit'           => 0,
                'running_balance'  => $netOpeningBalance,
                'closing_balance'  => $netOpeningBalance,
                'previous_balance' => $netOpeningBalance,
                'source'           => 'customer',
                'type'             => 'journal',
            ]);
        }

        return [
            'party'           => $customer,
            'twin_party'      => $twinVendor,
            'is_dual'         => $isDual,
            'opening_balance' => $netOpeningBalance,
            'closing_balance' => $runningBalance,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'transactions'    => $processedTransactions,
        ];
    }

    /**
     * Get unified combined ledger data for a Vendor.
     */
    public function getVendorLedgerData(int $vendorId, ?string $startDate = null, ?string $endDate = null, ?int $branchId = null): array
    {
        $startDate = $startDate ?? '2000-01-01';
        $endDate   = $endDate   ?? date('Y-m-d');

        $vendor = Vendor::find($vendorId);
        if (!$vendor) {
            return [
                'party'           => null,
                'twin_party'      => null,
                'is_dual'         => false,
                'opening_balance' => 0,
                'closing_balance' => 0,
                'total_debit'     => 0,
                'total_credit'    => 0,
                'transactions'    => collect([]),
            ];
        }

        $twinCustomer = $this->findTwinCustomer($vendor);

        if ($twinCustomer) {
            // For a dual party, calculate the exact same combined ledger
            $custData = $this->getCustomerLedgerData($twinCustomer->id, $startDate, $endDate, $branchId);
            return [
                'party'           => $vendor,
                'twin_party'      => $twinCustomer,
                'is_dual'         => true,
                'opening_balance' => $custData['opening_balance'],
                'closing_balance' => $custData['closing_balance'],
                'total_debit'     => $custData['total_debit'],
                'total_credit'    => $custData['total_credit'],
                'transactions'    => $custData['transactions'],
            ];
        }

        // Single Vendor (not a customer) -> standard vendor ledger
        $lastVendEntry = VendorLedger::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '<', $startDate)
            ->orderBy(DB::raw('DATE(created_at)'), 'desc')
            ->orderBy('id', 'desc')
            ->first();
        $openingBalance = $lastVendEntry ? (float)$lastVendEntry->closing_balance : (float)($vendor->opening_balance ?? 0);

        $vendLedgers = VendorLedger::where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->orderBy(DB::raw('DATE(created_at)'), 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = $openingBalance;
        $totalDebit     = 0.0;
        $totalCredit    = 0.0;

        $transactions = $vendLedgers->map(function ($row) use (&$runningBalance, &$totalDebit, &$totalCredit, $vendor) {
            $prev  = (float) $row->previous_balance;
            $close = (float) $row->closing_balance;
            $dr    = (float) ($row->debit ?? 0);
            $cr    = (float) ($row->credit ?? 0);

            if ($dr == 0 && $cr == 0) {
                if ($close > $prev) {
                    $cr = $close - $prev;
                } elseif ($close < $prev) {
                    $dr = $prev - $close;
                }
            }

            $totalDebit  += $dr;
            $totalCredit += $cr;
            $runningBalance += ($cr - $dr); // For vendor: Credit increases payable, Debit decreases

            $desc = $row->description ?? '';
            $type = $this->classifyTransactionType($desc, 'vendor');

            $ref = '—';
            if (preg_match('/#(inv|sin|grn|pv|cpv|crv|sr|so|po|jv|rv|re|pr|prtn|rvid)[- ]?([A-Z0-9\-_]+)/i', $desc, $matches)) {
                $ref = strtoupper($matches[0]);
            }

            return (object) [
                'id'               => $row->id,
                'created_at'       => $row->created_at ? $row->created_at->format('Y-m-d') : date('Y-m-d'),
                'datetime'         => $row->created_at ? $row->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                'vendor'           => $vendor,
                'description'      => $desc,
                'ref'              => $ref,
                'debit'            => $dr,
                'credit'           => $cr,
                'running_balance'  => $runningBalance,
                'closing_balance'  => $close,
                'previous_balance' => $prev,
                'source'           => 'vendor',
                'type'             => $type,
            ];
        });

        return [
            'party'           => $vendor,
            'twin_party'      => null,
            'is_dual'         => false,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'total_debit'     => $totalDebit,
            'total_credit'    => $totalCredit,
            'transactions'    => $transactions,
        ];
    }

    /**
     * Classify description into standard transaction types.
     */
    private function classifyTransactionType(string $desc, string $source): string
    {
        $ldesc = strtolower($desc);

        if (str_contains($ldesc, 'return') || str_contains($ldesc, 'prtn') || str_contains($ldesc, 'srn')) {
            return 'return';
        }
        if (str_contains($ldesc, 'receipt') || str_contains($ldesc, 'crv') || str_contains($ldesc, 'rvid')) {
            return 'receipt';
        }
        if (str_contains($ldesc, 'payment') || str_contains($ldesc, 'cpv') || str_contains($ldesc, 'pvid')) {
            return 'payment';
        }
        if (str_contains($ldesc, 'sale') || str_contains($ldesc, 'sin') || str_contains($ldesc, 'inv')) {
            return 'sale';
        }
        if (str_contains($ldesc, 'purchase') || str_contains($ldesc, 'grn') || str_contains($ldesc, 'po')) {
            return 'purchase';
        }

        return $source === 'customer' ? 'journal' : 'purchase';
    }
}
