<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\Purchase;
use App\Models\Vendor;
use App\Models\VendorBilty;
use App\Models\VendorLedger;
use App\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    use BranchScoped;

    // Show all vendors
    public function index()
    {
        $branchId = $this->getBranchId();
        $vendors = Vendor::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->withSum(['journalEntries as debit' => function ($query) {
                $query->where('party_type', Vendor::class);
            }], 'debit')
            ->withSum(['journalEntries as credit' => function ($query) {
                $query->where('party_type', Vendor::class);
            }], 'credit')
            ->get();

        return view('admin_panel.vendors.index', compact('vendors'));
    }

    // Store or update vendor information
    public function store(Request $request)
    {
        $data = $request->except('_token', 'opening_balance');
        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }
        unset($data['custom_credit_terms']);

        if ($request->id) {
            // Update existing vendor (prevent balance update)
            Vendor::findOrFail($request->id)->update($data);
        } else {
            $createData = $request->except('_token');
            if (isset($createData['credit_terms']) && $createData['credit_terms'] === 'custom') {
                $createData['credit_terms'] = $createData['custom_credit_terms'] ?? null;
            }
            unset($createData['custom_credit_terms']);

            // Create a new vendor and ledger entry — inject branch_id
            $vendor = Vendor::create(array_merge(
                $createData,
                ['branch_id' => $this->getBranchId() ?? 1]
            ));

            $opening = $request->opening_balance ?? 0;

            // Ensure 6 default COA accounts exist whenever a vendor is created
            $balanceService = app(\App\Services\BalanceService::class);
            $balanceService->ensureDefaultCOA();

            // Create ledger entry
            VendorLedger::create([
                'vendor_id' => $vendor->id,
                'branch_id' => $vendor->branch_id,
                'admin_or_user_id' => Auth::id(),
                'opening_balance' => $opening,
                'closing_balance' => $opening,
                'previous_balance' => $opening,
            ]);

            if ($opening > 0) {
                // Add Opening Balance to Accounts Payable
                $apAccountId = $balanceService->getAccountsPayableId();
                $journalService = app(\App\Services\JournalEntryService::class);

                $journalService->recordEntry(
                    $vendor,
                    $apAccountId,
                    0,        // Debit 0
                    $opening, // Credit AP
                    "Opening Balance for Vendor: {$vendor->name}",
                    now()->toDateString(),
                    $vendor
                );
            }
        }

        return back()->with('success', 'Saved Successfully');
    }

    // Soft delete vendor and related ledger entry
    public function delete($id)
    {
        // Find the vendor by id, along with the related ledger entry using the 'ledger' relationship
        $vendor = Vendor::with('ledger')->findOrFail($id);

        // The vendor's ledger will be automatically deleted due to cascading delete
        $vendor->delete(); // Soft delete vendor

        return back()->with('success', 'Deleted Successfully');
    }

    // Show vendor ledger for the authenticated user
    public function vendors_ledger()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $branchId = $this->getBranchId();
            // Get all ledgers
            $VendorLedgers = VendorLedger::when($branchId, fn($q) => $q->where('branch_id', $branchId))->with('vendor')->get();

            // Recalculate balances from Journal Entries
            $balanceService = app(\App\Services\BalanceService::class);

            foreach ($VendorLedgers as $ledger) {
                // Calculate actual closing balance from journal entries
                // Note: BalanceService::getVendorBalance returns positive for Credit (Payable)
                $ledger->formatted_closing_balance = $balanceService->getVendorBalance($ledger->vendor_id);
            }

            return view('admin_panel.vendors.vendors_ledger', compact('VendorLedgers'));
        } else {
            return redirect()->back();
        }
    }

    // Show all vendor payments
    public function vendor_payments()
    {
        $userId = Auth::id();
        $branchId = $this->getBranchId();

        $payments = VendorPayment::with('vendor')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('payment_date')
            ->get();

        $vendors = Vendor::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();
        $purchases = \App\Models\Purchase::with(['returns', 'payments'])
            ->where('status_purchase', '!=', 'draft')
            ->get()
            ->map(function ($purchase) {
                $totalReturned = $purchase->returns->sum('net_amount');
                $totalPaid = $purchase->payments->sum('amount');

                $updatedNet = max(0, $purchase->net_amount - $totalReturned);
                $dueAmount = max(0, $updatedNet - $totalPaid);

                $purchase->due_amount = $dueAmount;

                return $purchase;
            })
            ->filter(function ($purchase) {
                return $purchase->due_amount > 0;
            })
            ->values();

        return view('admin_panel.vendors.vendor_payments', compact('payments', 'vendors', 'purchases'));
    }

    // Store vendor payment and update ledger
    public function store_vendor_payment(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        $branchId = $this->getBranchId();

        // Save the vendor payment
        VendorPayment::create([
            'vendor_id' => $request->vendor_id,
            'branch_id' => $branchId,
            'purchase_id' => $request->purchase_id,
            'admin_or_user_id' => Auth::id(),
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        // Update purchase if provided
        if ($request->purchase_id) {
            $purchase = \App\Models\Purchase::find($request->purchase_id);
            if ($purchase) {
                // If it's a payment (-), money leaves us. If it's a return (+), money comes to us.
                // Usually vendor payment is paying a bill.
                $amountToApply = ($request->adjustment_type === 'minus') ? $request->amount : -$request->amount;
                $purchase->paid_amount += $amountToApply;
                $purchase->due_amount = max(0, $purchase->net_amount - $purchase->paid_amount);
                $purchase->save();
            }
        }

        // Update vendor ledger - Create a NEW historical entry
        $lastLedger = VendorLedger::where('vendor_id', $request->vendor_id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id', 'desc')
            ->first();
        
        $prevBal = $lastLedger ? $lastLedger->closing_balance : 0;
        $adjustmentAmount = $request->amount;
        
        // plus = vendor owes us more (Debit), minus = we owe vendor less (Debit)
        // Wait, 'adjustment_type' in this UI: 
        // plus = we pay them? No, usually plus means increase balance.
        // If it's a vendor payment screen:
        // adjustment_type minus = we are paying them (decreases payable).
        // adjustment_type plus = they refunded us? or we increased debt.
        
        $isDebit = ($request->adjustment_type === 'minus'); // Paying vendor = Debit
        
        VendorLedger::create([
            'vendor_id' => $request->vendor_id,
            'branch_id' => $branchId,
            'admin_or_user_id' => Auth::id(),
            'debit' => $isDebit ? $adjustmentAmount : 0,
            'credit' => $isDebit ? 0 : $adjustmentAmount,
            'previous_balance' => $prevBal,
            'opening_balance' => 0,
            'closing_balance' => $isDebit ? ($prevBal - $adjustmentAmount) : ($prevBal + $adjustmentAmount),
            'description' => "Manual Payment/Adjustment: " . ($request->note ?? 'N/A'),
            'source_type' => VendorPayment::class,
            'source_id' => $request->vendor_id, // Or the payment ID
        ]);

        return redirect()->back()->with('success', 'Vendor payment recorded.');
    }

    // Show all vendor bilties
    public function vendor_bilties()
    {
        $bilties = VendorBilty::with(['vendor', 'purchase'])->orderByDesc('id')->get();
        $vendors = Vendor::all();
        $purchases = Purchase::all();

        return view('admin_panel.vendors.vendor_bilties', compact('bilties', 'vendors', 'purchases'));
    }

    // Store vendor bilty information
    public function store_vendor_bilty(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'bilty_no' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'transporter_name' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        VendorBilty::create($request->all());

        return back()->with('success', 'Vendor bilty saved successfully.');
    }

    // Get vendor balance by vendor id
    public function getVendorBalance($id)
    {
        $branchId = $this->getBranchId();
        $ledger = VendorLedger::where('vendor_id', $id)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        return response()->json([
            'closing_balance' => $ledger ? $ledger->closing_balance : 0,
        ]);
    }

    /**
     * Show vendor ledger (journal-based)
     */
    public function ledger($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);

        // Get date range from request or default to current month
        $startDate = request('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = request('end_date', now()->endOfMonth()->format('Y-m-d'));

        $balanceService = app(\App\Services\BalanceService::class);
        $ledgerData = $balanceService->getVendorLedger($vendorId, $startDate, $endDate);

        return view('admin_panel.vendors.ledger', $ledgerData);
    }
}
