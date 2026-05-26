<?php

namespace App\Http\Controllers;

use App\Models\Cheque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\JournalEntryService;
use App\Http\Traits\BranchScoped;

class ChequeController extends Controller
{
    use BranchScoped;
    public function index(Request $request)
    {
        $branchId = $this->getBranchId();

        $query = Cheque::with(['voucherMaster.party', 'actualAccount'])
            ->whereHas('voucherMaster', function ($q) use ($branchId) {
                $q->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            });

        // Optional filtering by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $cheques = $query->orderBy('cheque_date', 'asc')->get();

        return view('admin_panel.vochers.cheque_management', compact('cheques'));
    }

    public function clear(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $cheque = Cheque::with('voucherMaster')->findOrFail($id);

            if ($cheque->status !== 'pending') {
                return back()->with('error', 'Only pending cheques can be cleared.');
            }

            // 1. Find Cheque In Hand account
            $chequeInHand = \App\Models\Account::where('title', 'Cheque In Hand')->first();
            if (!$chequeInHand) {
                return back()->with('error', 'Cheque In Hand account missing in chart of accounts.');
            }

            // 2. Clear out the cheque in DB
            $cheque->status = 'cleared';
            $cheque->cleared_at = now();
            $cheque->save();

            // 3. Journal Entry (Debit Bank, Credit Cheque In Hand)
            $journalService = app(JournalEntryService::class);
            $desc = "Cleared Cheque #{$cheque->cheque_no} from Receipt {$cheque->voucherMaster->voucher_no}";
            
            // Debit Bank Account
            $journalService->recordEntry(
                $cheque->voucherMaster,
                $cheque->actual_account_id, // The target bank
                $cheque->amount, // debit
                0, // credit
                $desc,
                now()->toDateString(),
                null
            );

            // Credit Cheque In Hand
            $journalService->recordEntry(
                $cheque->voucherMaster,
                $chequeInHand->id,
                0, // debit
                $cheque->amount, // credit
                $desc,
                now()->toDateString(),
                null
            );

            DB::commit();

            return back()->with('success', 'Cheque marked as cleared successfully. Journal entry posted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error clearing cheque: ' . $e->getMessage());
        }
    }

    public function bounce(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $cheque = Cheque::with('voucherMaster')->findOrFail($id);

            if ($cheque->status !== 'pending') {
                return back()->with('error', 'Only pending cheques can be bounced.');
            }

            // 1. Find Cheque In Hand account
            $chequeInHand = \App\Models\Account::where('title', 'Cheque In Hand')->first();
            if (!$chequeInHand) {
                return back()->with('error', 'Cheque In Hand account missing in chart of accounts.');
            }

            // 2. Set as bounced
            $cheque->status = 'bounced';
            $cheque->bounced_at = now();
            $cheque->save();

            // 3. Reversing Entry (Debit Customer, Credit Cheque In Hand)
            $journalService = app(JournalEntryService::class);
            $desc = "Bounced Cheque #{$cheque->cheque_no} from Receipt {$cheque->voucherMaster->voucher_no}";
            
            // We need to find the Customer/AR Account ID. Since it's a receipt, the credit side was likely AR.
            // Let's rely on the balance service to get standard AR if party is customer
            $balanceService = app(\App\Services\BalanceService::class);
            
            $arAccountId = null;
            if ($cheque->voucherMaster->party_type === \App\Models\Customer::class) {
                $arAccountId = $balanceService->getAccountsReceivableId();
            } elseif ($cheque->voucherMaster->party_type === \App\Models\Vendor::class) {
                // Highly unlikely for receipts, but possible if refund
                $arAccountId = $balanceService->getAccountsPayableId();
            } else {
                // If it was direct account to account, use the original credit account
                $originalCreditLine = $cheque->voucherMaster->details()->where('credit', '>', 0)->first();
                if ($originalCreditLine) {
                    $arAccountId = $originalCreditLine->account_id;
                }
            }

            if (!$arAccountId) {
                return back()->with('error', 'Could not determine the AR account to reverse.');
            }

            // Debit Customer/AR (Reinstating debt)
            $journalService->recordEntry(
                $cheque->voucherMaster,
                $arAccountId, 
                $cheque->amount, // debit
                0, // credit
                $desc,
                now()->toDateString(),
                $cheque->voucherMaster->party
            );

            // Credit Cheque In Hand (Removing the fake asset)
            $journalService->recordEntry(
                $cheque->voucherMaster,
                $chequeInHand->id,
                0, // debit
                $cheque->amount, // credit
                $desc,
                now()->toDateString(),
                null
            );

            DB::commit();

            return back()->with('success', 'Cheque marked as bounced. Reversal entry posted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error bouncing cheque: ' . $e->getMessage());
        }
    }
}
