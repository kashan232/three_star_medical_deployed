<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\SalesOfficer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Http\Traits\BranchScoped;

class CustomerController extends Controller
{
    use BranchScoped;
    // ////////////
    // 🔹 Load customers list by type
    public function saleindex(Request $request)
    {
        $branchId = $this->getBranchId();
        $type   = $request->type   ?? 'Main Customer';
        $search = $request->search ?? '';

        $query = Customer::where('customer_type', $type)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_id',   'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('customer_name')->get();

        return response()->json($customers);
    }

    // 🔹 Single customer detail
    public function show($id)
    {
        $customer = Customer::findOrFail($id);

        $data = $customer->toArray();
        $data['previous_balance'] = $customer->previous_balance;
        $data['balance_range'] = $customer->balance_range ?? 0;

        // Don't map status to remarks as it confuses manual voucher entries
        $data['remarks'] = '';

        return response()->json($data);
    }

    // //////////

    public function index()
    {
        $branchId = $this->getBranchId();
        $customers = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->withSum(['journalEntries as debit' => function ($query) {
                $query->where('party_type', Customer::class);
            }], 'debit')
            ->withSum(['journalEntries as credit' => function ($query) {
                $query->where('party_type', Customer::class);
            }], 'credit')
            ->latest()->get();
            
        return view('admin_panel.customers.index', compact('customers'));
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->save();

        return redirect()->back()->with('success', 'Customer status updated.');
    }

    // Add this in CustomerController
    public function getCustomerLedger($id)
    {
        $ledger = CustomerLedger::where('customer_id', $id)->latest()->first();

        return response()->json([
            'closing_balance' => $ledger->closing_balance,
        ]);
    }

    public function markInactive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = 'inactive';
        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer marked as inactive.');
    }

    public function inactiveCustomers()
    {
        $customers = Customer::where('status', 'inactive')->latest()->get();

        return view('admin_panel.customers.inactive', compact('customers'));
    }

    public function create()
    {
        $latestId = 'CUST-'.str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $salesOfficers = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        $zones = \App\Models\Zone::orderBy('zone', 'asc')->get();

        return view('admin_panel.customers.create', compact('latestId', 'salesOfficers', 'zones'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'      => 'required|unique:customers',
            'customer_name'    => 'nullable',
            'customer_name_ur' => 'nullable',
            'cnic'             => 'nullable',
            'filer_type'       => 'nullable',
            'zone'             => 'nullable',
            'contact_person'   => 'nullable',
            'mobile'           => 'nullable',
            'email_address'    => 'nullable|email',
            'contact_person_2' => 'nullable',
            'mobile_2'         => 'nullable',
            'email_address_2'  => 'nullable|email',
            'opening_balance'  => 'nullable|numeric',
            'balance_range'    => 'nullable|numeric',
            'credit_terms'     => 'nullable',
            'custom_credit_terms' => 'nullable|numeric',
            'address'          => 'nullable',
            'customer_type'    => 'nullable',
            'sales_officer_id' => 'nullable|exists:hr_employees,id',
        ]);

        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }
        unset($data['custom_credit_terms']);

        // Customer create
        $customer = Customer::create(array_merge($data, [
            'branch_id' => $this->getBranchId() ?? 1,
        ]));

        // Ensure 6 default COA accounts exist whenever a customer is created
        $balanceService = app(\App\Services\BalanceService::class);
        $balanceService->ensureDefaultCOA();

        // Ledger me entry agar opening balance dia gaya ho
        $opening = $data['opening_balance'] ?? 0;

        if ($opening > 0) {
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'branch_id' => $customer->branch_id,
                'admin_or_user_id' => Auth::id(),
                'previous_balance' => $opening,
                'opening_balance' => $opening,           // ✅ yahan set karna zaroori hai
                'closing_balance' => $opening,
            ]);

            // Add Opening Balance to Accounts Receivable
            $arAccountId = $balanceService->getAccountsReceivableId();
            $journalService = app(\App\Services\JournalEntryService::class);

            $journalService->recordEntry(
                $customer,
                $arAccountId,
                $opening, // Debit AR
                0,        // Credit 0
                "Opening Balance for Customer: {$customer->customer_name}",
                now()->toDateString(),
                $customer
            );
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $salesOfficers = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        return view('admin_panel.customers.edit', compact('customer', 'salesOfficers'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        $data = $request->except('_token');

        if (isset($data['credit_terms']) && $data['credit_terms'] === 'custom') {
            $data['credit_terms'] = $data['custom_credit_terms'] ?? null;
        }
        unset($data['custom_credit_terms']);

        $customer->update($data);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    // customer ledger start

    // Customer Ledger View
    public function customer_ledger(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->back();
        }

        $branchId = $this->getBranchId();
        $customers = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId))->orderBy('customer_name')->get();
        $ledgerData = collect([]);

        if ($request->filled('customer_id')) {
            $customerId = $request->customer_id;
            $startDate  = $request->from_date ?? '2000-01-01';
            $endDate    = $request->to_date   ?? date('Y-m-d');
            
            $customerObj = Customer::find($customerId);

            $ledgers = \App\Models\CustomerLedger::where('customer_id', $customerId)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            
            $ledgerData = $ledgers->map(function ($row) use ($customerObj) {
                $prev = (float) $row->previous_balance;
                $close = (float) $row->closing_balance;
                
                $debit = 0;
                $credit = 0;
                
                if ($close > $prev) {
                    $debit = $close - $prev;
                } elseif ($close < $prev) {
                    $credit = $prev - $close;
                }

                return (object) [
                    'created_at'       => $row->created_at->format('Y-m-d'),
                    'customer'         => $customerObj,
                    'description'      => $row->description,
                    'debit'            => $debit,
                    'credit'           => $credit,
                    'closing_balance'  => $close,
                    'previous_balance' => $prev,
                ];
            });

            // Ensure the ledger shows even if there are no new transactions in period but there is a balance
            if ($ledgerData->isEmpty() && $customerObj) {
                $openingEntry = \App\Models\CustomerLedger::where('customer_id', $customerId)
                    ->whereDate('created_at', '<', $startDate)
                    ->orderBy('id', 'desc')
                    ->first();
                $currentPrev = $openingEntry ? (float)$openingEntry->closing_balance : 0;
                
                $ledgerData->push((object) [
                    'created_at'       => $startDate,
                    'customer'         => $customerObj,
                    'description'      => 'Balance Brought Forward',
                    'debit'            => 0,
                    'credit'           => 0,
                    'closing_balance'  => $currentPrev,
                    'previous_balance' => $currentPrev,
                ]);
            }
        }

        return view('admin_panel.customers.customer_ledger', [
            'CustomerLedgers' => $ledgerData,
            'customers'       => $customers,
        ]);
    }

    // customer payment start

    // View all customer payments
    public function customer_payments()
    {
        $payments = CustomerPayment::with('customer')->orderByDesc('id')->get();
        $customers = Customer::all();

        return view('admin_panel.customers.customer_payments', compact('payments', 'customers'));
    }

    /**
     * GET /customer/unpaid-sales?customer_id=X
     * Returns outstanding/partially-paid sales for a customer (for payment form dropdown)
     */
    public function getUnpaidSales(\Illuminate\Http\Request $request)
    {
        $customerId = $request->customer_id;

        $sales = \App\Models\Sale::where('customer_id', $customerId)
            ->where('total_net', '>', 0)
            ->where('invoice_no', 'like', 'SIN-%')
            ->whereIn('sale_status', ['post', 'returned']) // only valid states
            ->orderByDesc('id')
            ->get()
            ->map(function ($sale) {
                // Total payments already made against this sale
                $totalPaid = \App\Models\CustomerPayment::where('sale_id', $sale->id)->sum('amount');
                $totalReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)->sum('net_amount');
                $updatedNet = max(0, $sale->total_net - $totalReturned);
                $dueAmount = max(0, $updatedNet - $totalPaid);

                return [
                    'id'                 => $sale->id,
                    'invoice_no'         => $sale->invoice_no,
                    'total_net'          => number_format($sale->total_net, 2),
                    'total_paid_amount'  => number_format($totalPaid, 2),
                    'due_amount'         => number_format($dueAmount, 2),
                    'due_raw'            => $dueAmount,
                    'total_commission'   => $sale->total_commission ?? 0,
                    'commission_paid'    => $sale->commission_paid ?? 0,
                ];
            })
            ->filter(function ($item) {
                return $item['due_raw'] > 0;
            })
            ->values();

        return response()->json(['sales' => $sales]);
    }

    public function store_customer_payment(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric|min:0',
            'adjustment_type' => 'required|in:plus,minus',
            'payment_method' => 'nullable|string',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $userId = Auth::id();

        $branchId = $this->getBranchId();
        // Save the payment
        $payment = CustomerPayment::create([
            'customer_id' => $request->customer_id,
            'branch_id' => $branchId,
            'sale_id' => $request->sale_id,
            'admin_or_user_id' => $userId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
        ]);

        // Get latest ledger record to calculate new balance
        $latestLedger = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();

        // Default to opening balance if no ledger exists, or 0
        // If no ledger exists, we assume previous balance is opening balance of customer?
        // But checking 'customers' table again is safer.
        $previousBalance = 0;
        if ($latestLedger) {
            $previousBalance = $latestLedger->closing_balance;
        } else {
            $cust = Customer::find($request->customer_id);
            $previousBalance = $cust->opening_balance ?? 0;
        }

        // Calculate new balance
        $newBalance = $request->adjustment_type === 'plus'
            ? $previousBalance + $request->amount
            : $previousBalance - $request->amount;

        // Create NEW ledger record (Preserve History)
        CustomerLedger::create([
            'customer_id' => $request->customer_id,
            'branch_id' => $branchId,
            'admin_or_user_id' => $userId,
            'previous_balance' => $previousBalance,
            'opening_balance' => 0, // This is not an "opening" entry, so 0 or null
            'closing_balance' => $newBalance,
            'description' => 'Payment: '.($request->note ?? $request->payment_method),
        ]);

        return back()->with('success', 'Payment adjusted and ledger updated.');
    }

    public function destroy_payment($id)
    {
        $payment = CustomerPayment::findOrFail($id);

        $customerId = $payment->customer_id;
        $amount = $payment->amount;

        // Latest ledger record for that customer
        $ledger = CustomerLedger::where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->first();
        if ($ledger) {
            $ledger->closing_balance += $amount;
            $ledger->save();
        }

        // Delete the payment entry
        $payment->delete();

        return redirect()->back()->with('success', 'Payment deleted and customer ledger updated successfully.');
    }

    public function getByType(Request $request)
    {
        $type = $request->get('type');

        $customers = Customer::where('customer_type', $type)->get(['id', 'customer_name']);

        return response()->json(['customers' => $customers]);
    }
}
