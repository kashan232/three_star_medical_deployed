<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\VoucherDetail;
use App\Models\VoucherMaster;
use Illuminate\Support\Facades\DB;
use App\Services\JournalEntryService;
use App\Services\TransactionService;

class VoucherService
{
    protected $journalService;

    public function __construct(JournalEntryService $journalService)
    {
        $this->journalService = $journalService;
    }

    /**
     * Create a new Voucher with details and optionally post it immediately.
     */
    public function createVoucher(array $data, array $lines, $user_id = null)
    {
        return DB::transaction(function () use ($data, $lines, $user_id) {
            $branchId = $data['branch_id'] ?? auth()->user()?->getBranchId() ?? 1;
            $voucherType = strtolower($data['voucher_type']);

            // 1. Create Header
            $voucher = VoucherMaster::create([
                'voucher_type' => $voucherType,
                'voucher_no'   => $data['voucher_no'] ?? VoucherMaster::generateVoucherNo($voucherType, $branchId),
                'date'         => $data['date'],
                'status'       => $data['status'] ?? VoucherMaster::STATUS_POSTED,
                'party_type'   => $data['party_type'] ?? null,
                'party_id'     => $data['party_id'] ?? null,
                'cheque_no'    => $data['cheque_no'] ?? null,
                'cheque_date'  => $data['cheque_date'] ?? null,
                'location'     => $data['location'] ?? 'HEAD OFFICE',
                'remarks'      => $data['remarks'] ?? null,
                'created_by'   => $user_id ?? auth()->id(),
                'verified_by'  => $user_id ?? auth()->id(),
                'verified_at'  => now(),
                'branch_id'    => $branchId,
                'fiscal_year'  => $this->getCurrentFiscalYear()
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            // 2. Create Lines
            foreach ($lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                
                $totalDebit += $debit;
                $totalCredit += $credit;

                VoucherDetail::create([
                    'voucher_master_id' => $voucher->id,
                    'account_id'        => $line['account_id'],
                    'party_type'        => $line['party_type'] ?? null,
                    'party_id'          => $line['party_id'] ?? null,
                    'debit'             => $debit,
                    'credit'            => $credit,
                    'narration'         => $line['narration'] ?? null,
                    'reference_no'      => $line['reference_no'] ?? null,
                ]);
            }

            // 3. Update Voucher Totals
            $voucher->total_amount = max($totalDebit, $totalCredit);
            $voucher->save();

            // 4. Validate Balance & Post to Journal
            if ($voucher->status === VoucherMaster::STATUS_POSTED) {
                if (abs($totalDebit - $totalCredit) > 0.05) {
                    throw new \Exception("Voucher is not balanced! Debit: $totalDebit, Credit: $totalCredit");
                }
                
                $voucher->posted_at = now();
                $voucher->save();

                $this->postToJournal($voucher);
            }

            return $voucher;
        });
    }

    /**
     * Update an existing voucher with atomic rollback of old entries and application of new data.
     */
    public function updateVoucher(VoucherMaster $voucher, array $data, array $lines, $user_id = null)
    {
        return DB::transaction(function () use ($voucher, $data, $lines, $user_id) {
            $branchId = $data['branch_id'] ?? $voucher->branch_id ?? 1;
            $oldPartyType = $voucher->party_type;
            $oldPartyId = $voucher->party_id;

            // 1. Reverse Previous Accounting & Ledger Entries
            $this->reverseVoucherAccounting($voucher);

            // 2. Update Header
            $voucherType = strtolower($data['voucher_type'] ?? $voucher->voucher_type);
            $voucher->update([
                'voucher_type' => $voucherType,
                'date'         => $data['date'] ?? $voucher->date,
                'party_type'   => $data['party_type'] ?? $voucher->party_type,
                'party_id'     => $data['party_id'] ?? $voucher->party_id,
                'cheque_no'    => $data['cheque_no'] ?? null,
                'cheque_date'  => $data['cheque_date'] ?? null,
                'location'     => $data['location'] ?? $voucher->location ?? 'HEAD OFFICE',
                'remarks'      => $data['remarks'] ?? $voucher->remarks,
                'modified_by'  => $user_id ?? auth()->id(),
                'status'       => $data['status'] ?? VoucherMaster::STATUS_POSTED,
                'branch_id'    => $branchId,
            ]);

            // 3. Delete Old Lines & Create New Lines
            $voucher->details()->delete();

            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                
                $totalDebit += $debit;
                $totalCredit += $credit;

                VoucherDetail::create([
                    'voucher_master_id' => $voucher->id,
                    'account_id'        => $line['account_id'],
                    'party_type'        => $line['party_type'] ?? null,
                    'party_id'          => $line['party_id'] ?? null,
                    'debit'             => $debit,
                    'credit'            => $credit,
                    'narration'         => $line['narration'] ?? null,
                    'reference_no'      => $line['reference_no'] ?? null,
                ]);
            }

            // 4. Update Totals
            $voucher->total_amount = max($totalDebit, $totalCredit);
            $voucher->save();

            // 5. Post to Journal
            if ($voucher->status === VoucherMaster::STATUS_POSTED) {
                if (abs($totalDebit - $totalCredit) > 0.05) {
                    throw new \Exception("Updated voucher is not balanced! Debit: $totalDebit, Credit: $totalCredit");
                }
                
                $voucher->posted_at = now();
                $voucher->save();

                $this->postToJournal($voucher);
            }

            // 6. Recalculate Ledger for old party if party changed
            if ($oldPartyType && $oldPartyId && ($oldPartyType !== $voucher->party_type || $oldPartyId !== $voucher->party_id)) {
                $txService = app(TransactionService::class);
                if ($oldPartyType === Customer::class && method_exists($txService, 'recalculateCustomerLedger')) {
                    $txService->recalculateCustomerLedger($oldPartyId);
                } elseif ($oldPartyType === Vendor::class && method_exists($txService, 'recalculateVendorLedger')) {
                    $txService->recalculateVendorLedger($oldPartyId);
                }
            }

            return $voucher->fresh(['details.account', 'party', 'createdBy', 'modifiedBy']);
        });
    }

    /**
     * Completely and safely delete a voucher with full ledger & journal reversal.
     */
    public function deleteVoucher(VoucherMaster $voucher)
    {
        return DB::transaction(function () use ($voucher) {
            $partyType = $voucher->party_type;
            $partyId   = $voucher->party_id;

            // 1. Reverse Accounting Entries
            $this->reverseVoucherAccounting($voucher);

            // 2. Delete Details
            $voucher->details()->delete();

            // 3. Delete Master
            $voucher->delete();

            // 4. Recalculate Party Ledgers
            if ($partyType && $partyId) {
                $txService = app(TransactionService::class);
                if ($partyType === Customer::class && method_exists($txService, 'recalculateCustomerLedger')) {
                    $txService->recalculateCustomerLedger($partyId);
                } elseif ($partyType === Vendor::class && method_exists($txService, 'recalculateVendorLedger')) {
                    $txService->recalculateVendorLedger($partyId);
                }
            }

            return true;
        });
    }

    /**
     * Reverse Journal Entries and Customer/Vendor Ledgers for this voucher.
     */
    public function reverseVoucherAccounting(VoucherMaster $voucher)
    {
        // 1. Reverse & Delete Journal Entries
        $this->journalService->reverseEntriesForSource($voucher);

        // 2. Identify affected Customers and delete Customer Ledger entries
        $custIds = CustomerLedger::where('source_type', VoucherMaster::class)
            ->where('source_id', $voucher->id)
            ->pluck('customer_id')
            ->unique();

        CustomerLedger::where('source_type', VoucherMaster::class)
            ->where('source_id', $voucher->id)
            ->delete();

        // 3. Identify affected Vendors and delete Vendor Ledger entries
        $vendIds = VendorLedger::where('source_type', VoucherMaster::class)
            ->where('source_id', $voucher->id)
            ->pluck('vendor_id')
            ->unique();

        VendorLedger::where('source_type', VoucherMaster::class)
            ->where('source_id', $voucher->id)
            ->delete();

        $txService = app(TransactionService::class);
        foreach ($custIds as $cId) {
            if (method_exists($txService, 'recalculateCustomerLedger')) {
                $txService->recalculateCustomerLedger($cId);
            }
        }
        foreach ($vendIds as $vId) {
            if (method_exists($txService, 'recalculateVendorLedger')) {
                $txService->recalculateVendorLedger($vId);
            }
        }
    }

    /**
     * Convert Voucher Details into Journal Entries and update party ledgers.
     */
    public function postToJournal(VoucherMaster $voucher)
    {
        $vType = strtolower($voucher->voucher_type);
        $txService = app(TransactionService::class);

        foreach ($voucher->details as $detail) {
            $includeParty = true;

            // In Cash/Bank Receiving (CRV/BRV/Receipt):
            // Debit is Cash/Bank (No Party)
            // Credit is Party (Receivable/Customer) -> Include Party
            if (in_array($vType, ['crv', 'brv', 'receipt']) && $detail->debit > 0) {
                $includeParty = false;
            }

            // In Cash/Bank Payment (CPV/BPV/Payment/Expense):
            // Debit is Party/Expense (Include Party if party exists)
            // Credit is Cash/Bank (No Party)
            if (in_array($vType, ['cpv', 'bpv', 'payment', 'expense']) && $detail->credit > 0) {
                $includeParty = false;
            }

            // Exclude Party from Cash/Bank/Expense head accounts if necessary
            $account = Account::with('head')->find($detail->account_id);
            if ($account && $account->head) {
                $headName = strtolower($account->head->name);
                if (in_array($headName, ['cash & cash equivalents', 'bank accounts', 'cash in hand'])) {
                    $includeParty = false;
                }
            }

            $entryParty = $detail->party ?? ($includeParty ? $voucher->party : null);

            $this->journalService->recordEntry(
                $voucher,
                $detail->account_id,
                $detail->debit,
                $detail->credit,
                $detail->narration ?? $voucher->remarks,
                $voucher->date->format('Y-m-d'),
                $entryParty
            );

            // If this line item has a direct Customer or Vendor attached (e.g. in JV):
            if ($detail->party_type && $detail->party_id) {
                $lineParty = $detail->party;
                $createdAt = $voucher->date ? \Carbon\Carbon::parse($voucher->date)->setTime(date('H'), date('i'), date('s')) : now();
                $userId = $voucher->created_by ?? auth()->id() ?? 1;

                if ($detail->party_type === Customer::class && $lineParty) {
                    $lastLedger = CustomerLedger::where('customer_id', $detail->party_id)
                        ->orderBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    $prevBal = $lastLedger ? (float)$lastLedger->closing_balance : (float)($lineParty->opening_balance ?? 0);
                    $newClosing = $prevBal + (float)$detail->debit - (float)$detail->credit;

                    CustomerLedger::create([
                        'customer_id'      => $detail->party_id,
                        'branch_id'        => $voucher->branch_id ?? 1,
                        'admin_or_user_id' => $userId,
                        'description'      => $voucher->voucher_no . ': ' . ($detail->narration ?? $voucher->remarks ?? 'JV Entry'),
                        'debit'            => (float)$detail->debit,
                        'credit'           => (float)$detail->credit,
                        'previous_balance' => $prevBal,
                        'closing_balance'  => $newClosing,
                        'opening_balance'  => 0,
                        'source_type'      => VoucherMaster::class,
                        'source_id'        => $voucher->id,
                        'created_at'       => $createdAt,
                    ]);
                    if (method_exists($txService, 'recalculateCustomerLedger')) {
                        $txService->recalculateCustomerLedger($detail->party_id);
                    }
                } elseif ($detail->party_type === Vendor::class && $lineParty) {
                    $lastLedger = VendorLedger::where('vendor_id', $detail->party_id)
                        ->orderBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    $prevBal = $lastLedger ? (float)$lastLedger->closing_balance : (float)($lineParty->opening_balance ?? 0);
                    $newClosing = $prevBal + (float)$detail->credit - (float)$detail->debit;

                    VendorLedger::create([
                        'vendor_id'        => $detail->party_id,
                        'branch_id'        => $voucher->branch_id ?? 1,
                        'admin_or_user_id' => $userId,
                        'description'      => $voucher->voucher_no . ': ' . ($detail->narration ?? $voucher->remarks ?? 'JV Entry'),
                        'debit'            => (float)$detail->debit,
                        'credit'           => (float)$detail->credit,
                        'previous_balance' => $prevBal,
                        'closing_balance'  => $newClosing,
                        'opening_balance'  => 0,
                        'source_type'      => VoucherMaster::class,
                        'source_id'        => $voucher->id,
                        'created_at'       => $createdAt,
                    ]);
                    if (method_exists($txService, 'recalculateVendorLedger')) {
                        $txService->recalculateVendorLedger($detail->party_id);
                    }
                }
            }
        }

        // Also sync CustomerLedger / VendorLedger for single-party vouchers (CRV, BRV, CPV, BPV) if no line-level party was posted
        if ($voucher->party && $voucher->details->whereNotNull('party_type')->isEmpty()) {
            $this->syncPartyLedger($voucher);
        }
    }

    /**
     * Synchronize legacy CustomerLedger / VendorLedger tables
     */
    private function syncPartyLedger(VoucherMaster $voucher)
    {
        $party = $voucher->party;
        if (! $party) return;

        $vType = strtolower($voucher->voucher_type);
        $amount = (float)$voucher->total_amount;
        $userId = $voucher->created_by ?? auth()->id() ?? 1;

        $createdAt = $voucher->date ? \Carbon\Carbon::parse($voucher->date)->setTime(date('H'), date('i'), date('s')) : now();

        if ($party instanceof Customer) {
            // CRV or BRV received from customer -> Credit Customer
            $isCredit = in_array($vType, ['crv', 'brv', 'receipt']);
            $debitAmount = $isCredit ? 0 : $amount;
            $creditAmount = $isCredit ? $amount : 0;

            $lastLedger = CustomerLedger::where('customer_id', $party->id)
                ->orderBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), 'desc')
                ->orderBy('id', 'desc')
                ->first();
            $prevBal = $lastLedger ? (float)$lastLedger->closing_balance : (float)($party->opening_balance ?? 0);
            $newClosing = $prevBal + $debitAmount - $creditAmount;

            CustomerLedger::create([
                'customer_id'      => $party->id,
                'branch_id'        => $voucher->branch_id ?? 1,
                'admin_or_user_id' => $userId,
                'description'      => $voucher->voucher_no . ': ' . ($voucher->remarks ?? strtoupper($vType)),
                'debit'            => $debitAmount,
                'credit'           => $creditAmount,
                'previous_balance' => $prevBal,
                'closing_balance'  => $newClosing,
                'opening_balance'  => 0,
                'source_type'      => VoucherMaster::class,
                'source_id'        => $voucher->id,
                'created_at'       => $createdAt,
            ]);

            $txService = app(TransactionService::class);
            if (method_exists($txService, 'recalculateCustomerLedger')) {
                $txService->recalculateCustomerLedger($party->id);
            }
        } elseif ($party instanceof Vendor) {
            // CPV or BPV paid to vendor -> Debit Vendor
            $isDebit = in_array($vType, ['cpv', 'bpv', 'payment', 'expense']);
            $debitAmount = $isDebit ? $amount : 0;
            $creditAmount = $isDebit ? 0 : $amount;

            $lastLedger = VendorLedger::where('vendor_id', $party->id)
                ->orderBy(\Illuminate\Support\Facades\DB::raw('DATE(created_at)'), 'desc')
                ->orderBy('id', 'desc')
                ->first();
            $prevBal = $lastLedger ? (float)$lastLedger->closing_balance : (float)($party->opening_balance ?? 0);
            $newClosing = $prevBal + $creditAmount - $debitAmount;

            VendorLedger::create([
                'vendor_id'        => $party->id,
                'branch_id'        => $voucher->branch_id ?? 1,
                'admin_or_user_id' => $userId,
                'description'      => $voucher->voucher_no . ': ' . ($voucher->remarks ?? strtoupper($vType)),
                'debit'            => $debitAmount,
                'credit'           => $creditAmount,
                'previous_balance' => $prevBal,
                'closing_balance'  => $newClosing,
                'opening_balance'  => 0,
                'source_type'      => VoucherMaster::class,
                'source_id'        => $voucher->id,
                'created_at'       => $createdAt,
            ]);

            $txService = app(TransactionService::class);
            if (method_exists($txService, 'recalculateVendorLedger')) {
                $txService->recalculateVendorLedger($party->id);
            }
        }
    }

    private function getCurrentFiscalYear()
    {
        $year = (int) date('Y');
        $month = (int) date('m');
        return ($month >= 7) ? "{$year}-" . ($year + 1) : ($year - 1) . "-{$year}";
    }
}
