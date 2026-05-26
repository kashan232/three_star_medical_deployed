<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cdr;
use App\Models\Customer;
use Illuminate\Http\Request;

use App\Http\Traits\BranchScoped;

class CdrController extends Controller
{
    use BranchScoped;

    public function index()
    {
        $branchId = $this->getBranchId();
        $cdrs = Cdr::with(['customer', 'bankAccount'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $customers = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('customer_name', 'asc') // Changed from 'title' as Customer seems to have customer_name
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
        \DB::beginTransaction();
        try {
            $validated = $request->validate([
                'cdr_no' => 'required',
                'cdr_date' => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric',
                'status' => 'required|in:PENDING,APPROVED,CLEARED',
            ]);

            $data = $request->all();
            $data['branch_id'] = $request->input('branch_id') ?? $this->getBranchId() ?? 1;
            
            // Auto-generate code if not provided
            if (empty($request->code)) {
                $data['code'] = $this->generateNextCode();
            }

            $cdr = Cdr::create($data);


            /**
             * 🏦 ACCOUNTING INTEGRATION
             * Requirement: Create Payment Voucher & Update Chart of Accounts
             * Logic: 
             * - Debit: Customer (Accounts Receivable or Security Deposit)
             * - Credit: Bank Account (source of payment)
             */
            
            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            
            $customer = Customer::find($request->customer_id);
            $amount = (float) $request->amount;
            
            // 1. Create V2 VoucherMaster & Details
            $arAccountId = $balanceService->getAccountsReceivableId();
            
            $v2Lines = [
                [
                    'account_id' => $arAccountId,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => "CDR Deposit for Tender #{$request->cdr_no}"
                ],
                [
                    'account_id' => $request->account_id, // Bank
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => "Payment for CDR #{$request->cdr_no}"
                ]
            ];

            $voucherService->createVoucher([
                'voucher_type' => 'payment',
                'date'         => $request->cdr_date,
                'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type'   => Customer::class,
                'party_id'     => $request->customer_id,
                'remarks'      => "Auto-generated Payment Voucher for CDR #{$request->cdr_no}",
            ], $v2Lines, auth()->id());

            // 2. 📘 Legacy Ledger Sync: Customer
            $latestLedger = \App\Models\CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();
            $prevBal = $latestLedger ? $latestLedger->closing_balance : ($customer->opening_balance ?? 0);
            $newBal = $prevBal + $amount; // Payment TO department/customer increases our receivable from them

            \App\Models\CustomerLedger::create([
                'customer_id'      => $request->customer_id,
                'admin_or_user_id' => auth()->id() ?? 1,
                'description'      => "CDR Deposit #{$request->cdr_no}",
                'previous_balance' => $prevBal,
                'closing_balance'  => $newBal,
                'opening_balance'  => 0,
                'created_at'       => \Carbon\Carbon::parse($request->cdr_date)->setHour(now()->hour)->setMinute(now()->minute),
            ]);

            // Update Customer Master for quick view
            $customer->previous_balance = $newBal;
            $customer->save();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR entry created and Payment Voucher posted successfully!',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create CDR: '.$e->getMessage(),
            ], 422);
        }
    }

    public function edit($id)
    {
        $cdr = Cdr::findOrFail($id);

        return response()->json($cdr);
    }

    public function update(Request $request, $id)
    {
        try {
            $cdr = Cdr::findOrFail($id);
            
            if ($cdr->status === 'CLEARED') {
                return response()->json(['success' => false, 'message' => 'CLEARED records cannot be modified.'], 422);
            }

            // Handle Quick Status Update from table
            if ($request->has('quick_status')) {
                $request->validate(['status' => 'required|in:PENDING,APPROVED,CLEARED']);
                $cdr->update(['status' => $request->status]);
                return response()->json(['success' => true, 'message' => 'Status updated to ' . $request->status]);
            }

            $validated = $request->validate([
                'cdr_no' => 'required',
                'cdr_date' => 'required|date',
                'customer_id' => 'required|exists:customers,id',
                'account_id' => 'required|exists:accounts,id',
                'amount' => 'required|numeric',
                'status' => 'required|in:PENDING,APPROVED,CLEARED',
            ]);

            $data = $request->all();
            if ($this->isSuperAdmin() && $request->filled('branch_id')) {
                $data['branch_id'] = $request->branch_id;
            }
            $cdr->update($data);

            return response()->json([
                'success' => true,
                'message' => 'CDR entry updated successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update CDR: '.$e->getMessage(),
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

        \DB::beginTransaction();
        try {
            // Update CDR status
            $cdr->update([
                'status' => 'CLEARED',
                'dated'  => $request->cleared_date,
            ]);

            /**
             * 🏦 ACCOUNTING INTEGRATION (CLEARING)
             * Logic: 
             * - Debit: Bank/Asset Account (Money received back)
             * - Credit: Accounts Receivable (or Security Deposit)
             */
            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            
            $customer = $cdr->customer;
            $amount = (float) $cdr->amount;
            $arAccountId = $balanceService->getAccountsReceivableId($cdr->branch_id);
            
            $v2Lines = [
                [
                    'account_id' => $request->account_id, // Selected Bank/Asset
                    'debit'      => $amount,
                    'credit'     => 0,
                    'narration'  => "CDR Refund/Cleared for Tender #{$cdr->cdr_no}"
                ],
                [
                    'account_id' => $arAccountId,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'narration'  => "Credit for Cleared CDR #{$cdr->cdr_no}"
                ]
            ];

            $voucherService->createVoucher([
                'voucher_type' => 'receipt',
                'date'         => $request->cleared_date,
                'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type'   => Customer::class,
                'party_id'     => $cdr->customer_id,
                'remarks'      => "Auto-generated Receipt Voucher for Cleared CDR #{$cdr->cdr_no}",
            ], $v2Lines, auth()->id());

            // Update Legacy Customer Ledger
            $latestLedger = \App\Models\CustomerLedger::where('customer_id', $cdr->customer_id)->latest()->first();
            $prevBal = $latestLedger ? $latestLedger->closing_balance : ($customer->opening_balance ?? 0);
            $newBal = $prevBal - $amount; // Money coming back reduces the receivable

            \App\Models\CustomerLedger::create([
                'customer_id'      => $cdr->customer_id,
                'admin_or_user_id' => auth()->id() ?? 1,
                'description'      => "CDR Cleared #{$cdr->cdr_no}",
                'previous_balance' => $prevBal,
                'closing_balance'  => $newBal,
                'opening_balance'  => 0,
                'created_at'       => \Carbon\Carbon::parse($request->cleared_date)->setHour(now()->hour)->setMinute(now()->minute),
            ]);

            // Update Customer master balance
            $customer->previous_balance = $newBal;
            $customer->save();

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'CDR marked as CLEARED and Receipt Voucher created!',
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear CDR: '.$e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $cdr = Cdr::findOrFail($id);
            
            if ($cdr->status === 'CLEARED') {
                return response()->json(['success' => false, 'message' => 'CLEARED records cannot be deleted.'], 422);
            }

            $cdr->delete();

            return response()->json([
                'success' => true,
                'message' => 'CDR entry deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete CDR: '.$e->getMessage(),
            ], 422);
        }
    }
}
