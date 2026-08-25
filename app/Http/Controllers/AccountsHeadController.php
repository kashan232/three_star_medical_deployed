<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\BranchScoped;

class AccountsHeadController extends Controller
{
    use BranchScoped;

    public function index()
    {
        $branchId = $this->getBranchId();
        
        $heads = \App\Models\AccountHead::with('parent')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('code')
            ->get();

        $accounts = \App\Models\Account::with(['head.parent', 'branch'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('account_code')
            ->get();

        $branches = $this->isSuperAdmin() ? \App\Models\Branch::all() : [];
        $isSuperAdmin = $this->isSuperAdmin();

        return view('admin_panel.chart_of_accounts', compact('heads', 'accounts', 'branches', 'isSuperAdmin'));
    }

    public function getNextAccountCode($headId)
    {
        $head = \App\Models\AccountHead::find($headId);
        if (!$head) {
            return response()->json(['code' => '', 'type' => 'Debit']);
        }

        $headCode = $head->code;
        $defaultType = (str_starts_with($headCode ?? '', '1-') || str_starts_with($headCode ?? '', '5-') || strtolower($head->type ?? '') === 'asset' || strtolower($head->type ?? '') === 'expense') ? 'Debit' : 'Credit';

        if ($headCode) {
            $highestAcc = \App\Models\Account::where('head_id', $head->id)
                ->orWhere('account_code', 'like', "{$headCode}-%")
                ->orderByDesc('account_code')
                ->first();

            $nextSeq = 1;
            if ($highestAcc && preg_match('/-(\d+)$/', $highestAcc->account_code, $matches)) {
                $nextSeq = ((int)$matches[1]) + 1;
            } else {
                $count = \App\Models\Account::where('head_id', $head->id)->count();
                $nextSeq = $count + 1;
            }
            $accountCode = $headCode . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
        } else {
            $accountCode = 'ACC-' . str_pad(\App\Models\Account::max('id') + 1, 4, '0', STR_PAD_LEFT);
        }

        return response()->json([
            'code' => $accountCode,
            'type' => $defaultType,
            'head' => $head
        ]);
    }

    public function storeHead(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable',
            'code' => 'nullable|string|max:50',
        ];

        if ($this->isSuperAdmin()) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($rules, [
            'branch_id.required' => 'Please select a Target Branch before saving the category.',
        ]);

        $parent = $request->parent_id ? \App\Models\AccountHead::find($request->parent_id) : null;
        $level = $parent ? ($parent->level + 1) : 1;
        $type = $request->type ?? ($parent->type ?? 'Asset');

        $code = $request->code;
        if (empty($code)) {
            if ($parent) {
                $childCount = \App\Models\AccountHead::where('parent_id', $parent->id)->count() + 1;
                $code = $parent->code . '-' . str_pad($childCount, 2, '0', STR_PAD_LEFT);
            } else {
                $code = 'HEAD-' . str_pad((\App\Models\AccountHead::max('id') ?? 0) + 1, 3, '0', STR_PAD_LEFT);
            }
        }

        $branchId = $request->input('branch_id') ?? $this->getBranchId() ?? 1;

        // Check for duplicate category name within this branch
        $existsName = \App\Models\AccountHead::where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->name))])
            ->exists();

        if ($existsName) {
            return back()->withInput()->with('error', "A category named '{$request->name}' already exists in this branch.");
        }

        // Check for duplicate category code within this branch (if code provided)
        if (!empty($code)) {
            $existsCode = \App\Models\AccountHead::where('branch_id', $branchId)
                ->where('code', trim($code))
                ->exists();

            if ($existsCode) {
                return back()->withInput()->with('error', "A category with code '{$code}' already exists in this branch.");
            }
        }

        \App\Models\AccountHead::create([
            'name' => $request->name,
            'code' => $code,
            'parent_id' => $request->parent_id ?? null,
            'level' => $level,
            'type' => $type,
            'branch_id' => $branchId,
        ]);

        return back()->with('success', "Account Category '{$request->name}' added successfully!");
    }

    public function storeAccount(Request $request)
    {
        $rules = [
            'head_id' => 'required|exists:account_heads,id',
            'title' => 'required|string|max:255',
            'opening_balance' => 'required|numeric',
        ];

        if ($this->isSuperAdmin()) {
            $rules['branch_id'] = 'required|exists:branches,id';
        }

        $request->validate($rules, [
            'branch_id.required' => 'Please select a Target Branch before saving the account.',
        ]);

        $head = \App\Models\AccountHead::findOrFail($request->head_id);
        $headCode = $head->code ?? null;

        // Auto-generate standard account code:
        if ($headCode) {
            $highestAcc = \App\Models\Account::where('head_id', $head->id)
                ->orWhere('account_code', 'like', "{$headCode}-%")
                ->orderByDesc('account_code')
                ->first();

            $nextSeq = 1;
            if ($highestAcc && preg_match('/-(\d+)$/', $highestAcc->account_code, $matches)) {
                $nextSeq = ((int)$matches[1]) + 1;
            } else {
                $count = \App\Models\Account::where('head_id', $head->id)->count();
                $nextSeq = $count + 1;
            }
            $accountCode = $headCode . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
        } else {
            $accountCode = 'ACC-' . str_pad((\App\Models\Account::max('id') ?? 0) + 1, 4, '0', STR_PAD_LEFT);
        }

        $type = $request->type;
        if (empty($type)) {
            $type = (str_starts_with($headCode ?? '', '1-') || str_starts_with($headCode ?? '', '5-') || strtolower($head->type ?? '') === 'asset' || strtolower($head->type ?? '') === 'expense') ? 'Debit' : 'Credit';
        }

        $branchId = $request->input('branch_id') ?? $this->getBranchId() ?? 1;

        // Check if an account with the same title already exists in this branch
        $existsAcc = \App\Models\Account::where('branch_id', $branchId)
            ->whereRaw('LOWER(TRIM(title)) = ?', [strtolower(trim($request->title))])
            ->exists();

        if ($existsAcc) {
            return back()->withInput()->with('error', "An account titled '{$request->title}' already exists in this branch.");
        }

        $account = \App\Models\Account::create([
            'head_id' => $head->id,
            'title' => strtoupper(trim($request->title)),
            'account_code' => $accountCode,
            'opening_balance' => (float)$request->opening_balance,
            'type' => $type,
            'status' => $request->has('status') ? 1 : 0,
            'branch_id' => $branchId,
        ]);

        return back()->with('success', "Account '{$account->title}' ({$account->account_code}) created successfully!");
    }

    public function showLedger($id, Request $request)
    {
        $account = \App\Models\Account::findOrFail($id);

        // Fetch Journal Entries for this account
        $query = \App\Models\JournalEntry::where('account_id', $id)
            ->with('party') // Load party if polymorphic
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        // Optional: Filter by Date Range
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('entry_date', [$request->from_date, $request->to_date]);
        }

        $entries = $query->get();

        return view('admin_panel.accounts.ledger', compact('account', 'entries'));
    }

    public function toggleStatus($id)
    {
        $account = \App\Models\Account::findOrFail($id);
        $account->status = ! $account->status;
        $account->save();

        return back()->with('success', 'Account status updated successfully!');
    }

    public function updateAccount(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:Debit,Credit',
            'head_id' => 'required|exists:account_heads,id',
            'opening_balance' => 'required|numeric',
        ]);

        $account = \App\Models\Account::findOrFail($id);
        $account->title = $request->title;
        $account->type = $request->type;
        $account->head_id = $request->head_id;
        $account->opening_balance = $request->opening_balance;
        $account->save();

        return back()->with('success', "Account '{$account->title}' updated successfully!");
    }

    public function setupCOA(\Illuminate\Http\Request $request)
    {
        $keys = $request->input('keys', []);

        if (empty($keys)) {
            return back()->with('error', 'No accounts selected.');
        }

        $balanceService = app(\App\Services\BalanceService::class);

        // This triggers ensureDefaultCOA() for all heads + selected accounts
        // We call each relevant getter based on keys selected
        $keyMap = [
            'cash'              => fn () => $balanceService->getCashAccountId(),
            'ar'                => fn () => $balanceService->getAccountsReceivableId(),
            'ap'                => fn () => $balanceService->getAccountsPayableId(),
            'sales'             => fn () => $balanceService->getSalesRevenueId(),
            'purchase'          => fn () => $balanceService->getPurchaseExpenseId(),
            'purchase_expensive'=> fn () => $balanceService->getPurchaseExpensiveId(),
        ];

        $created = [];
        foreach ($keys as $key) {
            if (isset($keyMap[$key])) {
                ($keyMap[$key])();
                $created[] = $key;
            }
        }

        return back()->with('success', count($created).' critical account(s) have been set up successfully!');
    }

    public function updateHead(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:account_heads,name,' . $id,
        ]);

        $head = \App\Models\AccountHead::findOrFail($id);
        $head->update([
            'name' => $request->name,
        ]);

        return back()->with('success', "Category '{$head->name}' updated successfully!");
    }

    public function destroyHead($id)
    {
        $head = \App\Models\AccountHead::findOrFail($id);
        $linkedAccounts = $head->accounts;

        $hasBalance = false;
        $activeAccountNames = [];
        foreach ($linkedAccounts as $acc) {
            $bal = (float)$acc->calculated_balance;
            if (abs($bal) > 0.01) {
                $hasBalance = true;
                $activeAccountNames[] = $acc->title;
            }
        }

        // Non-super-admins cannot delete heads with active balance accounts
        if ($hasBalance && ! auth()->user()->isSuperAdmin()) {
            $names = implode(', ', $activeAccountNames);
            return back()->with('error', "Cannot delete category '{$head->name}'. Linked account(s) have active balances: {$names}. Only a Super Admin can force-delete.");
        }

        // Delete all linked accounts + all FK-referencing records to avoid constraint violations
        foreach ($linkedAccounts as $acc) {
            $accId = $acc->id;

            // 1. Delete voucher_details referencing this account
            \Illuminate\Support\Facades\DB::table('voucher_details')
                ->where('account_id', $accId)->delete();

            // 2. Delete journal_entries referencing this account
            \App\Models\JournalEntry::where('account_id', $accId)->delete();

            // 3. Nullify cdrs.account_id (nullable column)
            \Illuminate\Support\Facades\DB::table('cdrs')
                ->where('account_id', $accId)->update(['account_id' => null]);

            // 4. Nullify cheques.actual_account_id if column exists
            try {
                \Illuminate\Support\Facades\DB::table('cheques')
                    ->where('actual_account_id', $accId)->update(['actual_account_id' => null]);
            } catch (\Exception $e) {
                // Column may not exist in all deployments — safe to ignore
            }

            $acc->delete();
        }

        $head->delete();

        return back()->with('success', "Category '{$head->name}' and all its linked accounts deleted successfully!");
    }
}
