<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cdr;
use App\Models\Customer;
use App\Models\VoucherMaster;
use App\Models\CustomerLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Traits\BranchScoped;

class CdrController extends Controller
{
    use BranchScoped;

    public function index()
    {
        $branchId = $this->getBranchId();
        $cdrs = Cdr::with(['customer', 'bankAccount', 'voucher', 'clearVoucher'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $customers = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('customer_name', 'asc')
            ->get();

        $banks = Account::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereHas('head', function ($q) {
                $q->where('name', 'like', '%Bank%');
            })->get();

        if ($banks->isEmpty()) {
            $banks = Account::all();
        }

        $assetHeads = \App\Models\AccountHead::where('name', 'like', '%Asset%')->pluck('id')->toArray();
        $assetAccounts = Account::whereIn('head_id', $assetHeads)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('title', 'asc')
            ->get();

        $nextCode = $this->generateNextCode();
        $branches = $this->isSuperAdmin() ? \App\Models\Branch::all() : [];

        return view('admin_panel.cdr.index', compact('cdrs', 'customers', 'banks', 'nextCode', 'branches', 'assetAccounts'));
    }

    private function generateNextCode()
    {
        $last = Cdr::latest('id')->first();
        if (!$last) return '001';
        return str_pad($last->id + 1, 3, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'cdr_no'      => 'required',
                'cdr_date'    => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'account_id'  => 'required|exists:accounts,id',
                'amount'      => 'required|numeric|min:0.01',
                'status'      => 'required|in:PENDING,APPROVED,CLEARED',
            ]);

            $data = $request->all();
            $branchId = $request->input('branch_id') ?? $this->getBranchId() ?? 1;
            $data['branch_id'] = $branchId;
            
            // Auto-generate code if not provided
            if (empty($request->code)) {
                $data['code'] = $this->generateNextCode();
            }

            /**
             * 🏦 ACCOUNTING INTEGRATION
             * Requirement: Create Payment Voucher & Update Chart of Accounts
             * Logic: 
             * - Debit: Customer (Accounts Receivable / CDR Security Deposit)
             * - Credit: Bank / Cash Account (source of payment)
             * VoucherService automatically:
             * 1. Creates VoucherMaster & VoucherDetails
             * 2. Posts Journal Entries (GL)
             * 3. Creates ONE synchronized CustomerLedger entry via syncPartyLedger
             * 4. Recalculates Customer balance
             */
            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            
            $customer = Customer::findOrFail($request->customer_id);
            $account  = Account::with('head')->findOrFail($request->account_id);
            $amount   = (float) $request->amount;
            
            $isBank = $account->head && stripos($account->head->name, 'bank') !== false;
            $voucherType = $isBank ? 'bpv' : 'cpv';
            
            $arAccountId = $balanceService->getAccountsReceivableId($branchId);
            $narration = "CDR Deposit for Tender #{$request->cdr_no}";
            
            $v2Lines = [
                [
                    'account_id' => $arAccountId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => $narration
                ],
                [
                    'account_id' => $request->account_id, // Bank or Cash
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => $narration
                ]
            ];

            $voucher = $voucherService->createVoucher([
                'voucher_type' => $voucherType,
                'date'         => $request->cdr_date,
                'status'       => VoucherMaster::STATUS_POSTED,
                'party_type'   => Customer::class,
                'party_id'     => $request->customer_id,
                'remarks'      => $narration,
                'branch_id'    => $branchId,
            ], $v2Lines, auth()->id());

            // Link the voucher to the CDR record
            $data['voucher_id'] = $voucher->id;
            $cdr = Cdr::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR entry created and Payment Voucher posted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create CDR: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        $cdr = Cdr::with(['voucher', 'bankAccount', 'customer'])->findOrFail($id);
        return response()->json($cdr);
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $cdr = Cdr::findOrFail($id);
            
            if ($cdr->status === 'CLEARED') {
                return response()->json(['success' => false, 'message' => 'CLEARED records cannot be modified.'], 422);
            }

            // Handle Quick Status Update from table
            if ($request->has('quick_status')) {
                $request->validate(['status' => 'required|in:PENDING,APPROVED']);
                $cdr->update(['status' => $request->status]);
                DB::commit();
                return response()->json(['success' => true, 'message' => 'Status updated to ' . $request->status]);
            }

            $validated = $request->validate([
                'cdr_no'      => 'required',
                'cdr_date'    => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'account_id'  => 'required|exists:accounts,id',
                'amount'      => 'required|numeric|min:0.01',
                'status'      => 'required|in:PENDING,APPROVED',
            ]);

            $data = $request->all();
            if ($this->isSuperAdmin() && $request->filled('branch_id')) {
                $branchId = $request->branch_id;
            } else {
                $branchId = $cdr->branch_id ?? $this->getBranchId() ?? 1;
            }
            $data['branch_id'] = $branchId;

            $amount = (float) $request->amount;
            $account = Account::with('head')->findOrFail($request->account_id);
            $isBank = $account->head && stripos($account->head->name, 'bank') !== false;
            $voucherType = $isBank ? 'bpv' : 'cpv';

            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            $txService = app(\App\Services\TransactionService::class);
            $arAccountId = $balanceService->getAccountsReceivableId($branchId);

            $narration = "CDR Deposit for Tender #{$request->cdr_no}";

            $v2Lines = [
                [
                    'account_id' => $arAccountId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => $narration
                ],
                [
                    'account_id' => $request->account_id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => $narration
                ]
            ];

            // If existing voucher exists, update it atomically
            $voucher = $cdr->voucher_id ? VoucherMaster::find($cdr->voucher_id) : null;
            if ($voucher) {
                $voucherService->updateVoucher($voucher, [
                    'voucher_type' => $voucherType,
                    'date'         => $request->cdr_date,
                    'status'       => VoucherMaster::STATUS_POSTED,
                    'party_type'   => Customer::class,
                    'party_id'     => $request->customer_id,
                    'remarks'      => $narration,
                    'branch_id'    => $branchId,
                ], $v2Lines, auth()->id());
            } else {
                $newVoucher = $voucherService->createVoucher([
                    'voucher_type' => $voucherType,
                    'date'         => $request->cdr_date,
                    'status'       => VoucherMaster::STATUS_POSTED,
                    'party_type'   => Customer::class,
                    'party_id'     => $request->customer_id,
                    'remarks'      => $narration,
                    'branch_id'    => $branchId,
                ], $v2Lines, auth()->id());
                $data['voucher_id'] = $newVoucher->id;
            }

            // Remove any legacy duplicate unlinked CustomerLedger entries for this CDR
            CustomerLedger::where('customer_id', $cdr->customer_id)
                ->whereNull('source_type')
                ->where('description', "CDR Deposit #{$cdr->cdr_no}")
                ->delete();

            $oldCustomerId = $cdr->customer_id;
            $cdr->update($data);

            // Re-sync customer ledger balances
            $txService->recalculateCustomerLedger($request->customer_id);
            if ($oldCustomerId != $request->customer_id) {
                $txService->recalculateCustomerLedger($oldCustomerId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR entry and linked Payment Voucher updated successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update CDR: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function clear(Request $request, $id)
    {
        $validated = $request->validate([
            'account_id'   => 'required|exists:accounts,id',
            'cleared_date' => 'required|date',
        ]);

        $cdr = Cdr::findOrFail($id);
        
        if ($cdr->status === 'CLEARED') {
            return response()->json(['success' => false, 'message' => 'This CDR is already cleared.'], 422);
        }

        DB::beginTransaction();
        try {
            $account = Account::with('head')->findOrFail($request->account_id);
            $isBank = $account->head && stripos($account->head->name, 'bank') !== false;
            $voucherType = $isBank ? 'brv' : 'crv';

            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            
            $amount = (float) $cdr->amount;
            $arAccountId = $balanceService->getAccountsReceivableId($cdr->branch_id);
            $narration = "CDR Cleared/Refund for Tender #{$cdr->cdr_no}";

            $v2Lines = [
                [
                    'account_id' => $request->account_id, // Selected Bank/Asset
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => $narration
                ],
                [
                    'account_id' => $arAccountId,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => $narration
                ]
            ];

            $clearVoucher = $voucherService->createVoucher([
                'voucher_type' => $voucherType,
                'date'         => $request->cleared_date,
                'status'       => VoucherMaster::STATUS_POSTED,
                'party_type'   => Customer::class,
                'party_id'     => $cdr->customer_id,
                'remarks'      => $narration,
                'branch_id'    => $cdr->branch_id,
            ], $v2Lines, auth()->id());

            // Update CDR status and link clear voucher
            $cdr->update([
                'status'           => 'CLEARED',
                'dated'            => $request->cleared_date,
                'clear_voucher_id' => $clearVoucher->id,
            ]);

            // Clean up any legacy manual clear entry if exists
            CustomerLedger::where('customer_id', $cdr->customer_id)
                ->whereNull('source_type')
                ->where('description', "CDR Cleared #{$cdr->cdr_no}")
                ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR marked as CLEARED and Receipt Voucher created!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear CDR: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $cdr = Cdr::findOrFail($id);
            
            if ($cdr->status === 'CLEARED') {
                return response()->json(['success' => false, 'message' => 'CLEARED records cannot be deleted. Please reverse clearing first.'], 422);
            }

            $voucherService = app(\App\Services\VoucherService::class);
            $txService = app(\App\Services\TransactionService::class);
            $customerId = $cdr->customer_id;

            // Delete linked payment voucher if exists
            if ($cdr->voucher_id) {
                $voucher = VoucherMaster::find($cdr->voucher_id);
                if ($voucher) {
                    $voucherService->deleteVoucher($voucher);
                }
            }

            // Remove any legacy duplicate unlinked CustomerLedger entries for this CDR
            CustomerLedger::where('customer_id', $customerId)
                ->whereNull('source_type')
                ->where('description', "CDR Deposit #{$cdr->cdr_no}")
                ->delete();

            $cdr->delete();

            // Recalculate customer balance
            if ($customerId) {
                $txService->recalculateCustomerLedger($customerId);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR entry and linked voucher deleted successfully!',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete CDR: ' . $e->getMessage(),
            ], 422);
        }
    }
}
