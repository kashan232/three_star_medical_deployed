<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\BranchScoped;
use Barryvdh\DomPDF\Facade\Pdf;
use Shuchkin\SimpleXLSXGen;

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

    /**
     * Shared calculation method for Account Ledger (Web, PDF, Excel)
     */
    protected function prepareLedgerData($id, Request $request)
    {
        $account = \App\Models\Account::with(['head', 'branch'])->findOrFail($id);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        // 1. Base opening balance calculation
        $baseOpening = (float)($account->opening_balance ?? 0);
        $openingBalance = $baseOpening;

        // If from_date is set, calculate prior transactions
        if ($fromDate) {
            $priorTotals = \App\Models\JournalEntry::where('account_id', $id)
                ->where('entry_date', '<', $fromDate)
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $priorDebit = (float)($priorTotals->total_debit ?? 0);
            $priorCredit = (float)($priorTotals->total_credit ?? 0);

            if ($account->type === 'Credit') {
                $openingBalance = $baseOpening + $priorCredit - $priorDebit;
            } else {
                $openingBalance = $baseOpening + $priorDebit - $priorCredit;
            }
        }

        // 2. Fetch Journal Entries for this account
        $query = \App\Models\JournalEntry::where('account_id', $id)
            ->with(['party', 'source'])
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc');

        if ($fromDate && $toDate) {
            $query->whereBetween('entry_date', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where('entry_date', '>=', $fromDate);
        } elseif ($toDate) {
            $query->where('entry_date', '<=', $toDate);
        }

        $entries = $query->get();

        // 3. Process entries with running balance
        $runningBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;

        $processedEntries = [];
        foreach ($entries as $entry) {
            $debit = (float)($entry->debit ?? 0);
            $credit = (float)($entry->credit ?? 0);
            $totalDebit += $debit;
            $totalCredit += $credit;

            if ($account->type === 'Credit') {
                $runningBalance = $runningBalance + $credit - $debit;
                $balanceType = ($runningBalance >= 0) ? 'Cr' : 'Dr';
            } else {
                $runningBalance = $runningBalance + $debit - $credit;
                $balanceType = ($runningBalance >= 0) ? 'Dr' : 'Cr';
            }

            // Extract voucher/invoice number
            $voucherNo = '-';
            if ($entry->source) {
                if (!empty($entry->source->voucher_no)) {
                    $voucherNo = $entry->source->voucher_no;
                } elseif (!empty($entry->source->invoice_no)) {
                    $voucherNo = $entry->source->invoice_no;
                } elseif (!empty($entry->source->invoice_number)) {
                    $voucherNo = $entry->source->invoice_number;
                } elseif (!empty($entry->source->bill_no)) {
                    $voucherNo = $entry->source->bill_no;
                } elseif (!empty($entry->source->reference_no)) {
                    $voucherNo = $entry->source->reference_no;
                }
            }

            // Extract party name
            $partyName = '';
            if ($entry->party) {
                $partyName = $entry->party->name 
                    ?? $entry->party->customer_name 
                    ?? $entry->party->vendor_name 
                    ?? $entry->party->title 
                    ?? $entry->party->business_name 
                    ?? '';
            }

            $entry->computed_voucher_no = $voucherNo;
            $entry->computed_party_name = $partyName;
            $entry->computed_running_balance = $runningBalance;
            $entry->computed_balance_type = $balanceType;

            $processedEntries[] = $entry;
        }

        $closingBalance = $runningBalance;
        $closingBalanceType = ($account->type === 'Credit') 
            ? (($closingBalance >= 0) ? 'Cr' : 'Dr') 
            : (($closingBalance >= 0) ? 'Dr' : 'Cr');

        $openingBalanceType = ($account->type === 'Credit')
            ? (($openingBalance >= 0) ? 'Cr' : 'Dr')
            : (($openingBalance >= 0) ? 'Dr' : 'Cr');

        return [
            'account'             => $account,
            'entries'             => $processedEntries,
            'raw_entries'         => $entries,
            'openingBalance'      => $openingBalance,
            'openingBalanceType'  => $openingBalanceType,
            'runningBalance'      => $runningBalance,
            'closingBalance'      => $closingBalance,
            'closingBalanceType'  => $closingBalanceType,
            'totalDebit'          => $totalDebit,
            'totalCredit'         => $totalCredit,
            'fromDate'            => $fromDate,
            'toDate'              => $toDate,
        ];
    }

    public function showLedger($id, Request $request)
    {
        $data = $this->prepareLedgerData($id, $request);
        return view('admin_panel.accounts.ledger', $data);
    }

    public function exportLedgerPdf($id, Request $request)
    {
        $data = $this->prepareLedgerData($id, $request);
        $account = $data['account'];

        $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $account->account_code ?: $account->title);
        $filename = 'General_Ledger_' . $cleanCode . '_' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('admin_panel.accounts.ledger_pdf', $data)
            ->setPaper('A4', 'portrait');

        return $pdf->download($filename);
    }

    public function exportLedgerExcel($id, Request $request)
    {
        $data = $this->prepareLedgerData($id, $request);
        $account = $data['account'];

        $periodText = ($data['fromDate'] ? date('d-M-Y', strtotime($data['fromDate'])) : 'Beginning') . ' to ' . ($data['toDate'] ? date('d-M-Y', strtotime($data['toDate'])) : date('d-M-Y'));
        $currentBalFormatted = number_format(abs($account->calculated_balance ?? $account->current_balance), 2);
        $currentBalType = ($account->type === 'Credit' ? (($account->calculated_balance ?? $account->current_balance) >= 0 ? 'Cr' : 'Dr') : (($account->calculated_balance ?? $account->current_balance) >= 0 ? 'Dr' : 'Cr'));

        $excelData = [
            // Company Header Banner
            [
                '<style font-size="14" color="#ffffff" bgcolor="#1e3a8a" height="28"><center><b>THREE STARS MEDICAL SUPPLIES</b></center></style>',
                '', '', '', '', '', '', ''
            ],
            // Statement Title Banner
            [
                '<style font-size="11" color="#ffffff" bgcolor="#2563eb" height="22"><center><b>GENERAL LEDGER STATEMENT</b></center></style>',
                '', '', '', '', '', '', ''
            ],
            [''],
            // Metadata Row 1
            [
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Account Title:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><left><b>' . htmlspecialchars($account->title) . '</b></left></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Account Code:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><center><b>' . htmlspecialchars($account->account_code) . '</b></center></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Current Balance:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin" color="#1e40af"><right><b>' . $currentBalFormatted . ' ' . $currentBalType . '</b></right></style>',
                '', ''
            ],
            // Metadata Row 2
            [
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Category / Head:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><left>' . htmlspecialchars($account->head->name ?? 'N/A') . '</left></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Account Type:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><center>' . htmlspecialchars($account->type) . '</center></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Statement Period:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><left>' . $periodText . '</left></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Generated:</b></left></style>',
                '<style bgcolor="#ffffff" border="thin"><center>' . now()->format('d-M-Y h:i A') . '</center></style>'
            ],
            [''],
            // Column Headers
            [
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin" height="24"><center><b>Date</b></center></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><center><b>Voucher / Ref No</b></center></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><left><b>Description / Narration</b></left></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><left><b>Party</b></left></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><right><b>Debit (PKR)</b></right></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><right><b>Credit (PKR)</b></right></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><right><b>Balance (PKR)</b></right></style>',
                '<style font-size="10" color="#ffffff" bgcolor="#1e293b" border="thin"><center><b>Type</b></center></style>'
            ],
            // Opening Balance Row
            [
                '<style bgcolor="#f1f5f9" border="thin"><center>-</center></style>',
                '<style bgcolor="#f1f5f9" border="thin"><center>-</center></style>',
                '<style bgcolor="#f1f5f9" border="thin"><left><b>Opening Balance</b></left></style>',
                '<style bgcolor="#f1f5f9" border="thin"><center>-</center></style>',
                '<style bgcolor="#f1f5f9" border="thin"><right>-</right></style>',
                '<style bgcolor="#f1f5f9" border="thin"><right>-</right></style>',
                '<style bgcolor="#f1f5f9" border="thin" nf="#,##0.00"><right><b>' . (float)abs($data['openingBalance']) . '</b></right></style>',
                '<style bgcolor="#f1f5f9" border="thin"><center><b>' . $data['openingBalanceType'] . '</b></center></style>'
            ]
        ];

        // Transactions
        $rowIndex = 0;
        foreach ($data['entries'] as $entry) {
            $rowIndex++;
            $rowBg = ($rowIndex % 2 === 0) ? '#f8fafc' : '#ffffff';
            $debit = (float)($entry->debit ?? 0);
            $credit = (float)($entry->credit ?? 0);
            $bal = (float)abs($entry->computed_running_balance);

            $debitCell = ($debit > 0)
                ? '<style bgcolor="' . $rowBg . '" border="thin" color="#16a34a" nf="#,##0.00"><right>' . $debit . '</right></style>'
                : '<style bgcolor="' . $rowBg . '" border="thin"><right>-</right></style>';

            $creditCell = ($credit > 0)
                ? '<style bgcolor="' . $rowBg . '" border="thin" color="#dc2626" nf="#,##0.00"><right>' . $credit . '</right></style>'
                : '<style bgcolor="' . $rowBg . '" border="thin"><right>-</right></style>';

            $excelData[] = [
                '<style bgcolor="' . $rowBg . '" border="thin"><center>' . ($entry->entry_date ? $entry->entry_date->format('d-M-Y') : '-') . '</center></style>',
                '<style bgcolor="' . $rowBg . '" border="thin"><center><b>' . htmlspecialchars($entry->computed_voucher_no) . '</b></center></style>',
                '<style bgcolor="' . $rowBg . '" border="thin"><left>' . htmlspecialchars($entry->description ?? '-') . '</left></style>',
                '<style bgcolor="' . $rowBg . '" border="thin" color="#2563eb"><left>' . htmlspecialchars($entry->computed_party_name ?: '-') . '</left></style>',
                $debitCell,
                $creditCell,
                '<style bgcolor="' . $rowBg . '" border="thin" nf="#,##0.00"><right><b>' . $bal . '</b></right></style>',
                '<style bgcolor="' . $rowBg . '" border="thin"><center><b>' . $entry->computed_balance_type . '</b></center></style>',
            ];
        }

        // Totals Row
        $excelData[] = [
            '<style bgcolor="#e2e8f0" border="medium" height="22"><center><b>TOTAL PERIOD</b></center></style>',
            '<style bgcolor="#e2e8f0" border="medium"></style>',
            '<style bgcolor="#e2e8f0" border="medium"></style>',
            '<style bgcolor="#e2e8f0" border="medium"></style>',
            '<style bgcolor="#e2e8f0" border="medium" color="#16a34a" nf="#,##0.00"><right><b>' . (float)$data['totalDebit'] . '</b></right></style>',
            '<style bgcolor="#e2e8f0" border="medium" color="#dc2626" nf="#,##0.00"><right><b>' . (float)$data['totalCredit'] . '</b></right></style>',
            '<style bgcolor="#e2e8f0" border="medium" color="#0f172a" nf="#,##0.00"><right><b>' . (float)abs($data['closingBalance']) . '</b></right></style>',
            '<style bgcolor="#e2e8f0" border="medium"><center><b>(' . $data['closingBalanceType'] . ')</b></center></style>'
        ];

        $cleanCode = preg_replace('/[^A-Za-z0-9_-]/', '_', $account->account_code ?: $account->title);
        $filename = 'General_Ledger_' . $cleanCode . '_' . now()->format('Y-m-d') . '.xlsx';
        
        $totalRows = count($excelData);
        $xlsx = SimpleXLSXGen::fromArray($excelData);
        
        // Merge header banners across all 8 columns (A to H)
        $xlsx->mergeCells('A1:H1')
             ->mergeCells('A2:H2')
             ->mergeCells('A' . $totalRows . ':D' . $totalRows)
             ->setColWidth(1, 15)
             ->setColWidth(2, 18)
             ->setColWidth(3, 38)
             ->setColWidth(4, 25)
             ->setColWidth(5, 16)
             ->setColWidth(6, 16)
             ->setColWidth(7, 18)
             ->setColWidth(8, 10);

        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
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
