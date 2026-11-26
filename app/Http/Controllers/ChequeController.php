<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\Account;
use App\Models\Branch;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Vendor;
use App\Services\ChequeService;
use Illuminate\Http\Request;

class ChequeController extends Controller
{
    use BranchScoped;

    public function __construct(protected ChequeService $chequeService)
    {
    }

    public function index(Request $request)
    {
        $branchId = $this->getBranchId();

        // Base branch-scoped query
        $baseQuery = Cheque::with([
            'voucherMaster.party',
            'voucherMaster.details.account',
            'clearanceVoucher',
            'bounceVoucher',
            'actualAccount',
            'clearingAccount',
            'party',
            'branch',
        ])->where(function ($q) use ($branchId) {
            if ($branchId) {
                $q->where('branch_id', $branchId)
                    ->orWhere(function ($sub) use ($branchId) {
                        $sub->whereNull('branch_id')
                            ->whereHas('voucherMaster', fn ($vm) => $vm->where('branch_id', $branchId));
                    });
            }
        });

        // Compute KPI stats across branch before table filters
        $allBranchCheques = (clone $baseQuery)->get();
        $today = now()->startOfDay();

        $pendingReceived = $allBranchCheques->filter(
            fn ($c) => $c->type === Cheque::TYPE_RECEIVED && in_array($c->status, [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED], true)
        );
        $pendingIssued = $allBranchCheques->filter(
            fn ($c) => $c->type === Cheque::TYPE_ISSUED && in_array($c->status, [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED], true)
        );
        $dueCheques = $allBranchCheques->filter(fn ($c) => $c->isDue());
        $clearedCheques = $allBranchCheques->where('status', Cheque::STATUS_CLEARED);
        $bouncedCheques = $allBranchCheques->whereIn('status', [Cheque::STATUS_BOUNCED, Cheque::STATUS_CANCELLED]);

        $stats = [
            'total_count'              => $allBranchCheques->count(),
            'pending_received_count'   => $pendingReceived->count(),
            'pending_received_amount'  => (float) $pendingReceived->sum('amount'),
            'pending_issued_count'     => $pendingIssued->count(),
            'pending_issued_amount'    => (float) $pendingIssued->sum('amount'),
            'due_count'                => $dueCheques->count(),
            'due_amount'               => (float) $dueCheques->sum('amount'),
            'cleared_count'            => $clearedCheques->count(),
            'cleared_amount'           => (float) $clearedCheques->sum('amount'),
            'bounced_count'            => $bouncedCheques->count(),
            'bounced_amount'           => (float) $bouncedCheques->sum('amount'),
        ];

        // Apply user filters
        $query = clone $baseQuery;

        $direction = $request->input('type', 'all');
        if (in_array($direction, [Cheque::TYPE_RECEIVED, Cheque::TYPE_ISSUED], true)) {
            $query->where('type', $direction);
        }

        $status = $request->input('status', 'all');
        if ($status === 'due') {
            $query->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED])
                ->whereDate('cheque_date', '<=', $today->toDateString());
        } elseif ($status === 'active') {
            $query->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED]);
        } elseif (in_array($status, [
            Cheque::STATUS_PENDING,
            Cheque::STATUS_DEPOSITED,
            Cheque::STATUS_CLEARED,
            Cheque::STATUS_BOUNCED,
            Cheque::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($request->filled('bank_account_id') && $request->bank_account_id !== 'all') {
            $query->where('actual_account_id', (int) $request->bank_account_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('cheque_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('cheque_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('cheque_no', 'like', "%{$term}%")
                    ->orWhere('bank_name', 'like', "%{$term}%")
                    ->orWhere('remarks', 'like', "%{$term}%")
                    ->orWhereHas('voucherMaster', fn ($vm) => $vm->where('voucher_no', 'like', "%{$term}%"));
            });
        }

        $cheques = $query->orderByRaw("CASE WHEN status IN ('pending','deposited') THEN 0 ELSE 1 END ASC")
            ->orderBy('cheque_date', 'asc')
            ->orderByDesc('id')
            ->get();

        // Filter search on resolved party name as well if search was supplied
        if ($request->filled('search')) {
            $needle = strtolower(trim($request->search));
            $cheques = $cheques->filter(function ($c) use ($needle) {
                return str_contains(strtolower((string) $c->cheque_no), $needle)
                    || str_contains(strtolower((string) $c->bank_name), $needle)
                    || str_contains(strtolower((string) $c->remarks), $needle)
                    || str_contains(strtolower((string) ($c->voucherMaster?->voucher_no ?? '')), $needle)
                    || str_contains(strtolower($c->party_display_name), $needle);
            })->values();
        }

        // Load Bank Accounts, Parties, Expense Accounts for modals
        $bankAccounts = Account::where('status', 1)
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function ($q) {
                $q->where('account_code', 'like', '1-02-052%')
                    ->orWhere('title', 'like', '%Bank%')
                    ->orWhereHas('head', fn ($h) => $h->where('name', 'like', '%Bank%'));
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('title')
            ->get()
            ->map(function ($bank) use ($allBranchCheques) {
                $bank->live_balance = (float) $bank->calculated_balance;
                $bank->pending_issued_total = (float) $allBranchCheques
                    ->where('type', Cheque::TYPE_ISSUED)
                    ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED])
                    ->where('actual_account_id', $bank->id)
                    ->sum('amount');
                $bank->pending_received_total = (float) $allBranchCheques
                    ->where('type', Cheque::TYPE_RECEIVED)
                    ->whereIn('status', [Cheque::STATUS_PENDING, Cheque::STATUS_DEPOSITED])
                    ->where('actual_account_id', $bank->id)
                    ->sum('amount');
                return $bank;
            });

        $customers = Customer::where(function ($q) {
                $q->whereNull('status')->orWhere('status', 1)->orWhere('status', 'active');
            })
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('customer_name')
            ->get();

        $vendors = Vendor::where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $expenseAccounts = Account::where('status', 1)
            ->where(fn ($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function ($q) {
                $q->where('account_code', 'like', '5-%')
                    ->orWhereHas('head', fn ($h) => $h->where('type', 'Expense')->orWhere('name', 'like', '%Expense%'));
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('title')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? Branch::all() : [];
        $userBranch = auth()->user()?->branch ?? Branch::find($branchId ?? 1);

        return view('admin_panel.vochers.cheque_management', compact(
            'cheques',
            'stats',
            'bankAccounts',
            'customers',
            'vendors',
            'expenseAccounts',
            'isSuperAdmin',
            'branches',
            'userBranch',
            'direction',
            'status'
        ));
    }

    /**
     * Issue or Receive a cheque directly from Cheque Management.
     * Post-dated & low/zero bank balance cheques are explicitly permitted.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'            => 'required|in:issued,received',
            'target_id'       => 'required|string',
            'bank_account_id' => 'required|exists:accounts,id',
            'cheque_no'       => 'required|string|max:100',
            'issue_date'      => 'required|date',
            'cheque_date'     => 'required|date',
            'bank_name'       => 'nullable|string|max:150',
            'amount'          => 'required|numeric|min:0.01',
            'remarks'         => 'nullable|string|max:500',
            'branch_id'       => 'nullable|exists:branches,id',
        ]);

        try {
            $cheque = $this->chequeService->createDirectChequeVoucher([
                ...$validated,
                'branch_id' => $request->input('branch_id') ?? $this->getBranchId() ?? 1,
            ], auth()->id());

            $label = $cheque->type === Cheque::TYPE_ISSUED ? 'Issued' : 'Received';
            return redirect()->route('cheques.index', ['type' => $cheque->type])
                ->with('success', "{$label} Cheque #{$cheque->cheque_no} recorded and Voucher {$cheque->voucherMaster?->voucher_no} posted successfully!");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function deposit(Request $request, $id)
    {
        $request->validate([
            'actual_account_id' => 'required|exists:accounts,id',
        ]);

        try {
            $cheque = $this->chequeService->depositCheque((int) $id, (int) $request->actual_account_id);
            return back()->with('success', "Cheque #{$cheque->cheque_no} marked as deposited in {$cheque->actualAccount?->title}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error depositing cheque: ' . $e->getMessage());
        }
    }

    public function clear(Request $request, $id)
    {
        $request->validate([
            'actual_account_id' => 'nullable|exists:accounts,id',
            'clearance_date'    => 'nullable|date',
            'notes'             => 'nullable|string|max:300',
        ]);

        try {
            $cheque = $this->chequeService->clearCheque(
                (int) $id,
                $request->filled('actual_account_id') ? (int) $request->actual_account_id : null,
                $request->input('clearance_date'),
                $request->input('notes'),
                auth()->id()
            );

            return back()->with('success', "Cheque #{$cheque->cheque_no} cleared successfully! Clearance Voucher {$cheque->clearanceVoucher?->voucher_no} posted.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error clearing cheque: ' . $e->getMessage());
        }
    }

    public function bounce(Request $request, $id)
    {
        $request->validate([
            'bounce_reason' => 'nullable|string|max:255',
            'bounce_date'   => 'nullable|date',
        ]);

        try {
            $cheque = $this->chequeService->bounceCheque(
                (int) $id,
                $request->input('bounce_reason'),
                $request->input('bounce_date'),
                auth()->id()
            );

            return back()->with('success', "Cheque #{$cheque->cheque_no} marked as bounced. Reversal Voucher {$cheque->bounceVoucher?->voucher_no} posted & party ledger updated.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error bouncing cheque: ' . $e->getMessage());
        }
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancel_reason' => 'nullable|string|max:255',
            'cancel_date'   => 'nullable|date',
        ]);

        try {
            $cheque = $this->chequeService->cancelCheque(
                (int) $id,
                $request->input('cancel_reason'),
                $request->input('cancel_date'),
                auth()->id()
            );

            return back()->with('success', "Cheque #{$cheque->cheque_no} cancelled and reversal entry {$cheque->bounceVoucher?->voucher_no} posted.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error cancelling cheque: ' . $e->getMessage());
        }
    }

    public function undoClear(Request $request, $id)
    {
        try {
            $cheque = $this->chequeService->undoClearance((int) $id);
            return back()->with('success', "Clearance reversed for Cheque #{$cheque->cheque_no}. Cheque is back to Pending.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error reversing clearance: ' . $e->getMessage());
        }
    }
}
