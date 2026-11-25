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
     * Normalize string by collapsing non-breaking spaces, multi-spaces and uppercase.
     */
    public static function normalizeString(?string $str): string
    {
        if (!$str) return '';
        $str = preg_replace('/[\xc2\xa0\s]+/u', ' ', $str);
        return strtoupper(trim($str));
    }

    /**
     * Clean base name by stripping punctuation and normalizing common suffixes/plurals.
     */
    public static function cleanBaseName(?string $str): string
    {
        $str = self::normalizeString($str);
        if ($str === '') return '';
        $str = preg_replace('/[^A-Z0-9 ]/', '', $str);
        $words = array_filter(explode(' ', $str));
        
        $cleanWords = array_map(function($w) {
            if (strlen($w) > 4 && str_ends_with($w, 'S') && !in_array($w, ['PLUS', 'STATUS', 'SERVICES'])) {
                return substr($w, 0, -1);
            }
            return $w;
        }, $words);

        return implode(' ', $cleanWords);
    }

    /**
     * Find twin Vendor for a given Customer.
     */
    public function findTwinVendor(Customer $customer): ?Vendor
    {
        $cCode = trim($customer->customer_id ?? '');
        $cCnic = preg_replace('/[^0-9]/', '', $customer->cnic ?? '');
        $cPhone = preg_replace('/[^0-9]/', '', $customer->mobile ?? '');
        $cEmail = strtolower(trim($customer->email_address ?? ''));
        $cName = self::normalizeString($customer->customer_name ?? '');
        $cBase = self::cleanBaseName($customer->customer_name ?? '');
        $cNum  = preg_match('/(\d+)$/', $cCode, $m) ? (int)$m[1] : null;

        // 1. Direct code exact match (e.g. 1 == 1 or CUST-0001 == CUST-0001 or CUST-0234 == CUST-0234)
        if ($cCode !== '') {
            $v = Vendor::where('vendor_code', $cCode)->first();
            if ($v) return $v;
        }

        // 2. CNIC exact match (when at least 10 digits)
        if (strlen($cCnic) >= 10) {
            $v = Vendor::where(function ($q) use ($customer, $cCnic) {
                $q->where('cnic', $customer->cnic)
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic, '-', ''), ' ', ''), '.', '') = ?", [$cCnic]);
            })->first();
            if ($v) return $v;
        }

        // 3. Exact Name match (ignoring whitespace / case / non-breaking spaces)
        if (strlen($cName) >= 3) {
            $v = Vendor::all()->first(function($item) use ($cName) {
                return self::normalizeString($item->name) === $cName;
            });
            if ($v) return $v;
        }

        // 4. Clean Base Name match (handles extra spaces, punctuation, BROTHER vs BROTHERS, etc.)
        if (strlen($cBase) >= 4) {
            $v = Vendor::all()->first(function($item) use ($cBase) {
                return self::cleanBaseName($item->name) === $cBase;
            });
            if ($v) return $v;
        }

        // 5. Phone / Mobile match (when at least 10 digits)
        if (strlen($cPhone) >= 10 && !preg_match('/^0+$/', $cPhone)) {
            $v = Vendor::all()->first(function($item) use ($cPhone) {
                $vPhone = preg_replace('/[^0-9]/', '', $item->phone ?? '');
                return strlen($vPhone) >= 10 && $vPhone === $cPhone;
            });
            if ($v) return $v;
        }

        // 6. Email match (valid email)
        if (strlen($cEmail) >= 6 && filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
            $v = Vendor::whereRaw('LOWER(TRIM(email)) = ?', [$cEmail])->first();
            if ($v) return $v;
        }

        // 7. Code numeric suffix match + Name similarity / ID match
        if ($cNum && strlen($cBase) >= 4) {
            $pad = str_pad($cNum, 4, '0', STR_PAD_LEFT);
            $v = Vendor::where(function ($q) use ($pad) {
                $q->where('vendor_code', "VND-{$pad}")
                  ->orWhere('vendor_code', "VEND-{$pad}")
                  ->orWhere('vendor_code', "V-{$pad}");
            })->first();

            if ($v && (self::cleanBaseName($v->name) === $cBase || $v->id === $customer->id)) {
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
        $vPhone = preg_replace('/[^0-9]/', '', $vendor->phone ?? '');
        $vEmail = strtolower(trim($vendor->email ?? ''));
        $vName = self::normalizeString($vendor->name ?? '');
        $vBase = self::cleanBaseName($vendor->name ?? '');
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
                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic, '-', ''), ' ', ''), '.', '') = ?", [$vCnic]);
            })->first();
            if ($c) return $c;
        }

        // 3. Exact Name match (ignoring whitespace / case / non-breaking spaces)
        if (strlen($vName) >= 3) {
            $c = Customer::all()->first(function($item) use ($vName) {
                return self::normalizeString($item->customer_name) === $vName;
            });
            if ($c) return $c;
        }

        // 4. Clean Base Name match
        if (strlen($vBase) >= 4) {
            $c = Customer::all()->first(function($item) use ($vBase) {
                return self::cleanBaseName($item->customer_name) === $vBase;
            });
            if ($c) return $c;
        }

        // 5. Phone / Mobile match
        if (strlen($vPhone) >= 10 && !preg_match('/^0+$/', $vPhone)) {
            $c = Customer::all()->first(function($item) use ($vPhone) {
                $cPhone = preg_replace('/[^0-9]/', '', $item->mobile ?? '');
                return strlen($cPhone) >= 10 && $cPhone === $vPhone;
            });
            if ($c) return $c;
        }

        // 6. Email match
        if (strlen($vEmail) >= 6 && filter_var($vEmail, FILTER_VALIDATE_EMAIL)) {
            $c = Customer::whereRaw('LOWER(TRIM(email_address)) = ?', [$vEmail])->first();
            if ($c) return $c;
        }

        // 7. Code numeric suffix match + Name similarity / ID match
        if ($vNum && strlen($vBase) >= 4) {
            $pad = str_pad($vNum, 4, '0', STR_PAD_LEFT);
            $c = Customer::where(function ($q) use ($pad) {
                $q->where('customer_id', "CUST-{$pad}")
                  ->orWhere('customer_id', "C-{$pad}");
            })->first();

            if ($c && (self::cleanBaseName($c->customer_name) === $vBase || $c->id === $vendor->id)) {
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
        if ($vendOpening == 0 && (float)($twinVendor->opening_balance ?? 0) != 0) {
            $hasReal = VendorLedger::where('vendor_id', $twinVendor->id)
                ->whereNull('deleted_at')
                ->where(fn($q) => $q->where('debit', '>', 0)->orWhere('credit', '>', 0))
                ->whereDate('created_at', '<', $startDate)
                ->exists();
            if (!$hasReal) {
                $vendOpening = (float)($twinVendor->opening_balance ?? 0);
            }
        }
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

            // Skip zero-amount placeholder/opening rows if empty description
            if ($dr == 0 && $cr == 0 && (empty(trim($desc)) || strtolower(trim($desc)) === 'purchase / payment')) {
                continue;
            }

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

                // Skip zero-amount placeholder/opening rows if empty description
                if ($dr == 0 && $cr == 0 && (empty(trim($desc)) || strtolower(trim($desc)) === 'purchase / payment')) {
                    continue;
                }

                // Prefix description clearly for user awareness
                $displayDesc = $desc;
                if (!str_starts_with($desc, '[Vendor') && !str_starts_with($desc, '[Purchase')) {
                    $displayDesc = '[Purchase] ' . ($desc ?: 'Purchase / Payment');
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
            if (preg_match('/\b(BPV|CPV|BRV|CRV|JV|SIN|SRN|GRN|INV|PV|RV|SO|PO|PRTN|PR|RVID|PVID)[- ]?([A-Z0-9\-_]+)\b/i', $item->description, $matches)) {
                $ref = strtoupper($matches[0]);
            } elseif (preg_match('/#\s*([A-Za-z0-9\-_]+)/', $item->description, $matches)) {
                $ref = strtoupper($matches[1]);
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
        if ($openingBalance == 0 && (float)($vendor->opening_balance ?? 0) != 0) {
            $hasReal = VendorLedger::where('vendor_id', $vendorId)
                ->whereNull('deleted_at')
                ->where(fn($q) => $q->where('debit', '>', 0)->orWhere('credit', '>', 0))
                ->whereDate('created_at', '<', $startDate)
                ->exists();
            if (!$hasReal) {
                $openingBalance = (float)($vendor->opening_balance ?? 0);
            }
        }

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
            if (preg_match('/\b(BPV|CPV|BRV|CRV|JV|SIN|SRN|GRN|INV|PV|RV|SO|PO|PRTN|PR|RVID|PVID)[- ]?([A-Z0-9\-_]+)\b/i', $desc, $matches)) {
                $ref = strtoupper($matches[0]);
            } elseif (preg_match('/#\s*([A-Za-z0-9\-_]+)/', $desc, $matches)) {
                $ref = strtoupper($matches[1]);
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
