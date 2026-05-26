<?php

namespace App\Services;

use App\Models\VoucherMaster;
use App\Models\VoucherDetail;
use Illuminate\Support\Facades\DB;
use App\Services\JournalEntryService;

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
            
            $branchId = $data['branch_id'] ?? (auth()->check() ? auth()->user()->getBranchId() : 1);

            // 1. Create Header
            $voucher = VoucherMaster::create([
                'voucher_type' => $data['voucher_type'],
                'voucher_no' => $this->generateVoucherNo($data['voucher_type'], $branchId),
                'date' => $data['date'],
                'status' => $data['status'] ?? VoucherMaster::STATUS_DRAFT,
                'party_type' => $data['party_type'] ?? null,
                'party_id' => $data['party_id'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $user_id,
                'branch_id' => $branchId,
                'fiscal_year' => $this->getCurrentFiscalYear()
            ]);

            $totalDebit = 0;
            $totalCredit = 0;

            // 2. Create Lines
            foreach ($lines as $line) {
                // Determine if this line is Debit or Credit based on type/amount
                // Expected Input format: ['account_id' => 1, 'debit' => 100, 'credit' => 0, 'narration' => '...']
                
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                
                $totalDebit += $debit;
                $totalCredit += $credit;

                VoucherDetail::create([
                    'voucher_master_id' => $voucher->id,
                    'account_id' => $line['account_id'],
                    'debit' => $debit,
                    'credit' => $credit,
                    'narration' => $line['narration'] ?? null,
                ]);
            }

            // 3. Update Voucher Totals
            $voucher->total_amount = max($totalDebit, $totalCredit); // Usually they are equal
            $voucher->save();

            // 4. Validate Balance (if posting)
            if ($voucher->status === VoucherMaster::STATUS_POSTED) {
                if (abs($totalDebit - $totalCredit) > 0.05) { // Allow tiny floating point diff
                    throw new \Exception("Voucher is not balanced! Debit: $totalDebit, Credit: $totalCredit");
                }
                
                // 5. Post to Journal
                $this->postToJournal($voucher);
            }

            return $voucher;
        });
    }

    /**
     * Post an existing draft voucher to the General Ledger.
     */
    public function postVoucher(VoucherMaster $voucher)
    {
        if ($voucher->status === VoucherMaster::STATUS_POSTED) return;

        DB::transaction(function () use ($voucher) {
            // Re-validate balance
            $debits = $voucher->details()->sum('debit');
            $credits = $voucher->details()->sum('credit');
            
            if (abs($debits - $credits) > 0.05) {
                throw new \Exception("Cannot post unbalanced voucher.");
            }

            $voucher->status = VoucherMaster::STATUS_POSTED;
            $voucher->posted_at = now();
            $voucher->save();

            $this->postToJournal($voucher);
        });
    }

    /**
     * Convert Voucher Details into Journal Entries
     */
    private function postToJournal(VoucherMaster $voucher)
    {
        foreach ($voucher->details as $detail) {
            
            $includeParty = true;

            // Logic to prevent linking Customer/Vendor to the Cash side of the transaction
            // Receipt: Dr Cash (No Party), Cr Receivable (Party)
            if ($voucher->voucher_type === VoucherMaster::TYPE_RECEIPT && $detail->debit > 0) {
                $includeParty = false;
            }
            
            // Payment: Dr Payable (Party), Cr Cash (No Party)
            if ($voucher->voucher_type === VoucherMaster::TYPE_PAYMENT && $detail->credit > 0) {
                $includeParty = false;
            }

            // Also exclude Party from Income/Expense accounts so it only applies to AR/AP
            $account = \App\Models\Account::with('head')->find($detail->account_id);
            if ($account && $account->head) {
                $headName = strtolower($account->head->name);
                if (in_array($headName, ['income', 'expenses', 'expense'])) {
                    $includeParty = false;
                }
            }

            $this->journalService->recordEntry(
                $voucher,
                $detail->account_id,
                $detail->debit,
                 $detail->credit,
                $detail->narration ?? $voucher->remarks,
                $voucher->date->format('Y-m-d'),
                $includeParty ? $voucher->party : null
            );
        }
    }

    private function generateVoucherNo($type, $branchId = null)
    {
        // Simple generation: Type-Year-Index
        // e.g. RV-2026-0005
        $prefix = strtoupper(substr($type, 0, 2)); // RV, PV, EX, JO
        if ($type == 'contra') $prefix = 'CT';
        if ($type == 'journal') $prefix = 'JV';
        
        $year = date('Y');
        
        // Find last voucher of this type in this year, scoped to branch
        $lastVoucher = VoucherMaster::where('voucher_type', $type)
            ->where('voucher_no', 'like', "{$prefix}-{$year}-%")
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id', 'desc')
            ->first();
            
        if ($lastVoucher) {
            // Extract number part (Last 4 digits)
            $parts = explode('-', $lastVoucher->voucher_no);
            $nextNum = (int)end($parts) + 1;
        } else {
            $nextNum = 1;
        }
        
        return sprintf('%s-%s-%04d', $prefix, $year, $nextNum);
    }
    
    private function getCurrentFiscalYear()
    {
        // TODO: helper
        return '2025-2026'; 
    }
}
