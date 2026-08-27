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
        $openingBalance = 0;
        $closingBalance = 0;
        $totalDebit = 0;
        $totalCredit = 0;
        $isDual = false;
        $twinVendor = null;

        if ($request->filled('customer_id')) {
            $customerId = (int) $request->customer_id;
            $startDate  = $request->from_date ?? '2000-01-01';
            $endDate    = $request->to_date   ?? date('Y-m-d');

            $dualService = app(\App\Services\DualPartyLedgerService::class);
            $res = $dualService->getCustomerLedgerData($customerId, $startDate, $endDate, $branchId);

            $ledgerData     = $res['transactions'];
            $openingBalance = $res['opening_balance'];
            $closingBalance = $res['closing_balance'];
            $totalDebit     = $res['total_debit'];
            $totalCredit    = $res['total_credit'];
            $isDual         = $res['is_dual'];
            $twinVendor     = $res['twin_party'];
        }

        return view('admin_panel.customers.customer_ledger', [
            'CustomerLedgers' => $ledgerData,
            'customers'       => $customers,
            'openingBalance'  => $openingBalance,
            'closingBalance'  => $closingBalance,
            'totalDebit'      => $totalDebit,
            'totalCredit'     => $totalCredit,
            'isDual'          => $isDual,
            'twinVendor'      => $twinVendor,
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
        $customers = Customer::where('customer_type', $type)->get(['id', 'customer_name']);

        return response()->json(['customers' => $customers]);
    }

    // =========================================================================
    //  CUSTOMER IMPORT & TEMPLATE DOWNLOAD (CONSOLIDATED)
    // =========================================================================

    public function downloadTemplate()
    {
        $data = [
            // Header Row
            [
                'CUSTOMER NAME', 'CUSTOMER ID', 'CUSTOMER TYPE', 'CNIC', 'MOBILE',
                'EMAIL', 'CONTACT PERSON', 'ZONE', 'ADDRESS', 'CITY',
                'FILER TYPE', 'NTN NO', 'GST NO', 'DSL NO', 'DRAP NO',
                'OPENING BALANCE', 'CREDIT TERMS', 'CREDIT LIMIT', 'SALES OFFICER', 'TARGET BRANCH'
            ],
            // Sample Row 1
            [
                'Alpha Pharmacy & Healthcare', 'CUST-0001', 'Main Customer', '42101-1234567-1', '0300-1234567',
                'info@alphapharm.com', 'Ali Raza', 'Lahore Central', '123 Main Commercial Market', 'Lahore',
                'Filer', '1234567-8', '12-34-5678-910-11', 'DSL-9876', 'DRAP-5432',
                '5000.00', '30 Days', '150000.00', 'Kashif Ali', 'Main Branch'
            ],
            // Sample Row 2
            [
                'Beta Medical Traders', 'CUST-0002', 'Distributor', '42201-9876543-2', '0321-7654321',
                'sales@betamed.com', 'Tariq Mehmood', 'Karachi South', '45 Shahrah-e-Faisal', 'Karachi',
                'Non-Filer', '7654321-0', '', '', '',
                '0.00', 'Cash', '50000.00', '', 'Shop Branch'
            ]
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $xlsx->setColWidth('A', 30);
        $xlsx->setColWidth('B', 15);
        $xlsx->setColWidth('C', 18);
        $xlsx->setColWidth('D', 18);
        $xlsx->setColWidth('E', 16);
        $xlsx->setColWidth('F', 24);
        $xlsx->setColWidth('G', 20);
        $xlsx->setColWidth('H', 18);
        $xlsx->setColWidth('I', 30);
        $xlsx->setColWidth('J', 15);
        $xlsx->setColWidth('K', 14);
        $xlsx->setColWidth('L', 15);
        $xlsx->setColWidth('M', 18);
        $xlsx->setColWidth('N', 14);
        $xlsx->setColWidth('O', 14);
        $xlsx->setColWidth('P', 18);
        $xlsx->setColWidth('Q', 15);
        $xlsx->setColWidth('R', 18);
        $xlsx->setColWidth('S', 20);
        $xlsx->setColWidth('T', 20);

        $tmpPath = storage_path('app/customer_template_' . uniqid() . '.xlsx');
        $xlsx->saveAs($tmpPath);

        return response()->download($tmpPath, 'customer_import_template.xlsx', [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    public function importCustomers(Request $request)
    {
        $request->validate(['file' => 'required|file']);

        try {
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'No valid spreadsheet file was uploaded.',
                ], 400);
            }

            $file      = $request->file('file');
            $extension = strtolower($file->getClientOriginalExtension());

            if (!in_array($extension, ['csv', 'xlsx'])) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'Only CSV (.csv) and Excel (.xlsx) files are supported.',
                ], 400);
            }

            // ── Parse file into $rawRows ──────────────────────────────────────
            $rawRows = [];
            if ($extension === 'xlsx') {
                if ($xlsx = \Shuchkin\SimpleXLSX::parse($file->getRealPath())) {
                    $rawRows = $xlsx->rows();
                } else {
                    return response()->json([
                        'status'  => 'error',
                        'type'    => 'format_error',
                        'message' => 'Unable to parse the Excel file: ' . \Shuchkin\SimpleXLSX::parseError(),
                    ], 400);
                }
            } else {
                $handle = fopen($file->getRealPath(), 'r');
                if (!$handle) {
                    return response()->json([
                        'status'  => 'error',
                        'type'    => 'format_error',
                        'message' => 'Unable to open the uploaded file.',
                    ], 400);
                }

                $bom = fread($handle, 3);
                if ($bom !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                    rewind($handle);
                }

                $firstLine = fgets($handle);
                rewind($handle);
                $bom2 = fread($handle, 3);
                if ($bom2 !== chr(0xEF) . chr(0xBB) . chr(0xBF)) {
                    rewind($handle);
                }

                $commaCount     = substr_count($firstLine, ',');
                $semicolonCount = substr_count($firstLine, ';');
                $tabCount       = substr_count($firstLine, "\t");

                $delimiter = ',';
                if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
                    $delimiter = ';';
                } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
                    $delimiter = "\t";
                }

                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    $rawRows[] = $row;
                }
                fclose($handle);
            }

            if (empty($rawRows)) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'format_error',
                    'message' => 'Spreadsheet has no content.',
                ], 400);
            }

            // ── Normalize header row ──────────────────────────────────────────
            $rawHeaders = array_shift($rawRows);
            $normalizedHeaders = array_map(function ($h) {
                return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', (string)$h), '_'));
            }, $rawHeaders);

            $aliases = [
                'customer_name'  => 'customer_name',
                'customername'   => 'customer_name',
                'name'           => 'customer_name',
                'party_name'     => 'customer_name',

                'customer_id'    => 'customer_id',
                'customerid'     => 'customer_id',
                'id'             => 'customer_id',
                'code'           => 'customer_id',
                'cust_id'        => 'customer_id',

                'customer_type'  => 'customer_type',
                'customertype'   => 'customer_type',
                'type'           => 'customer_type',
                'party_type'     => 'customer_type',

                'cnic'           => 'cnic',
                'cnic_no'        => 'cnic',
                'cnic_number'    => 'cnic',

                'mobile'         => 'mobile',
                'mobile_no'      => 'mobile',
                'phone'          => 'mobile',
                'cell'           => 'mobile',
                'contact_no'     => 'mobile',

                'email'          => 'email_address',
                'email_address'  => 'email_address',

                'contact_person' => 'contact_person',
                'contactperson'  => 'contact_person',
                'person'         => 'contact_person',

                'zone'           => 'zone',
                'area'           => 'zone',
                'region'         => 'zone',

                'address'        => 'address',
                'street'         => 'address',

                'city'           => 'city',

                'filer_type'     => 'filer_type',
                'filertype'      => 'filer_type',
                'filer'          => 'filer_type',
                'tax_status'     => 'filer_type',

                'ntn'            => 'ntn_no',
                'ntn_no'         => 'ntn_no',

                'gst'            => 'gst_no',
                'gst_no'         => 'gst_no',
                'strn'           => 'gst_no',

                'dsl'            => 'dsl_no',
                'dsl_no'         => 'dsl_no',

                'drap'           => 'drap_no',
                'drap_no'        => 'drap_no',

                'opening_balance' => 'opening_balance',
                'openingbalance'  => 'opening_balance',
                'balance'         => 'opening_balance',

                'credit_terms'   => 'credit_terms',
                'creditterms'    => 'credit_terms',

                'credit_limit'   => 'credit_limit',
                'limit'          => 'credit_limit',
                'balance_range'  => 'credit_limit',

                'sales_officer'  => 'sales_officer',
                'officer'        => 'sales_officer',

                'target_branch'  => 'target_branch',
                'branch'         => 'target_branch',
            ];

            $fieldMap = [];
            foreach ($normalizedHeaders as $idx => $rawKey) {
                $canonical = $aliases[$rawKey] ?? $rawKey;
                if (!isset($fieldMap[$canonical])) {
                    $fieldMap[$canonical] = $idx;
                }
            }

            // ── Require at minimum: customer_name ────────────────────────────
            if (!isset($fieldMap['customer_name'])) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'column_mismatch',
                    'message' => 'Required column <strong>CUSTOMER NAME</strong> not found in your file.<br>Please download and use the provided template.',
                ], 400);
            }

            $val = function (string $field, $default = '') use (&$row, &$fieldMap): string {
                if (isset($fieldMap[$field]) && isset($row[$fieldMap[$field]])) {
                    $v = trim((string)$row[$fieldMap[$field]]);
                    return $v === '' ? (string)$default : $v;
                }
                return (string)$default;
            };

            // ── Verify if target branches exist or need confirmation ──────────
            $missingBranches = [];
            $targetBranchIdx = $fieldMap['target_branch'] ?? null;
            if ($targetBranchIdx !== null) {
                $uniqueBranchNames = [];
                foreach ($rawRows as $row) {
                    if (empty(array_filter(array_map('trim', $row)))) {
                        continue;
                    }
                    if (isset($row[$targetBranchIdx])) {
                        $bName = trim((string)$row[$targetBranchIdx]);
                        if ($bName !== '') {
                            $uniqueBranchNames[strtolower($bName)] = $bName;
                        }
                    }
                }

                foreach ($uniqueBranchNames as $lowerName => $originalName) {
                    $exists = \App\Models\Branch::where('name', 'LIKE', $originalName)->exists();
                    if (!$exists) {
                        $missingBranches[] = $originalName;
                    }
                }
            }

            // If missing branches found and user hasn't authorized creating them
            $createMissing = $request->boolean('create_missing_branches') || $request->input('create_missing_branches') == '1';
            if (!empty($missingBranches) && !$createMissing) {
                return response()->json([
                    'status'           => 'confirm_branch',
                    'missing_branches' => $missingBranches,
                    'message'          => 'New branch found that is not exist in database: ' . implode(', ', $missingBranches) . '.',
                ]);
            }

            // ── Verify required fields (Customer Name) and need for dummy filling ──
            $autoFillDummy = $request->boolean('auto_fill_dummy') || $request->input('auto_fill_dummy') == '1';
            $validationErrors = [];
            $tempRowCount = 0;
            foreach ($rawRows as $row) {
                $tempRowCount++;
                if (empty(array_filter(array_map('trim', $row)))) {
                    continue;
                }
                
                // Read value using fieldMap
                $cName = '';
                if (isset($fieldMap['customer_name']) && isset($row[$fieldMap['customer_name']])) {
                    $cName = trim((string)$row[$fieldMap['customer_name']]);
                }

                if (empty($cName) && !$autoFillDummy) {
                    $validationErrors[] = "Row {$tempRowCount}: Customer Name is required.";
                }
            }

            // If missing required fields and user hasn't confirmed dummy fill yet
            if (!empty($validationErrors)) {
                $shown = array_slice($validationErrors, 0, 10);
                $more  = count($validationErrors) > 10 ? '<br>... and ' . (count($validationErrors) - 10) . ' more errors.' : '';
                return response()->json([
                    'status'        => 'error',
                    'type'          => 'format_error',
                    'can_auto_fill' => true,
                    'message'       => 'Import aborted. Fix the following errors and re-upload:<br>'
                                       . implode('<br>', $shown) . $more,
                ], 400);
            }

            $branchId      = $this->getBranchId() ?? 1;
            $userId        = Auth::id();

            $rowCount      = 0;
            $importedCount = 0;
            $skippedCount  = 0;
            $dummyCount    = 0;
            $errors        = [];

            $duplicateCount = 0;

            \DB::beginTransaction();

            foreach ($rawRows as $row) {
                $rowCount++;

                if (empty(array_filter(array_map('trim', $row)))) {
                    $skippedCount++;
                    continue;
                }

                $customerName = trim($val('customer_name'));
                $usedDummy    = false;

                if (empty($customerName)) {
                    if ($autoFillDummy) {
                        $customerName = "[DUMMY] Customer Row {$rowCount}";
                        $usedDummy    = true;
                    } else {
                        $errors[] = "Row {$rowCount}: Customer Name is required — row skipped.";
                        continue;
                    }
                }

                // ── Resolve Target Branch ─────────────────────────────────────
                $rowBranch = trim($val('target_branch'));
                $rowBranchId = $branchId; // Fallback to active branch
                if ($rowBranch !== '') {
                    $br = \App\Models\Branch::where('name', 'LIKE', $rowBranch)->first();
                    if ($br) {
                        $rowBranchId = $br->id;
                    } else if ($createMissing) {
                        // Automatically create the branch
                        $code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $rowBranch), 0, 3));
                        if (empty($code)) {
                            $code = 'BR' . rand(10, 99);
                        }
                        // Ensure unique branch code
                        $originalCode = $code;
                        $suffix = 1;
                        while (\App\Models\Branch::where('branch_code', $code)->exists()) {
                            $code = $originalCode . $suffix++;
                        }

                        $newBr = \App\Models\Branch::create([
                            'name'        => $rowBranch,
                            'branch_code' => $code,
                            'address'     => 'Imported Location',
                            'number'      => '0000-0000000',
                            'is_active'   => 1,
                            'user_id'     => $userId ?? 1,
                        ]);
                        $rowBranchId = $newBr->id;
                    }
                }

                // ── Resolve Sales Officer ──────────────────────────────────────
                $rowOfficerName = trim($val('sales_officer'));
                $salesOfficerId = null;
                if ($rowOfficerName !== '') {
                    $emp = \App\Models\Hr\Employee::where(function($q) use ($rowOfficerName) {
                        $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $rowOfficerName . '%'])
                          ->orWhere('first_name', 'LIKE', '%' . $rowOfficerName . '%');
                    })->first();
                    if ($emp) {
                        $salesOfficerId = $emp->id;
                    }
                }

                $creditLimit = (float)$val('credit_limit', 0);

                // ── Duplicate Check 1: customer_id already exists ──────────────
                $customerId = trim($val('customer_id'));
                if ($customerId !== '') {
                    $existingCust = Customer::where('customer_id', $customerId)->first();
                    if ($existingCust) {
                        if ($existingCust->branch_id == $rowBranchId) {
                            $duplicateCount++;
                            $errors[] = "Row {$rowCount}: Customer ID '{$customerId}' already exists in target branch — skipped.";
                            continue;
                        } else {
                            // Exists but in a different branch. Generate unique customer_id suffix to allow it
                            $originalId = $customerId;
                            $suffix = 1;
                            while (Customer::where('customer_id', $customerId)->exists()) {
                                $customerId = $originalId . '-' . $suffix++;
                            }
                        }
                    }
                }

                // ── Duplicate Check 2: same customer_name in same branch ───────
                $existsByName = Customer::whereRaw('LOWER(TRIM(customer_name)) = ?', [strtolower($customerName)])
                    ->where('branch_id', $rowBranchId)
                    ->exists();
                if ($existsByName) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowCount}: Customer '{$customerName}' already exists in target branch — skipped.";
                    continue;
                }

                // ── Duplicate Check 3: same mobile number ─────────────────────
                $mobileVal = trim($val('mobile'));
                if ($mobileVal !== '') {
                    $existsByMobile = Customer::where('mobile', $mobileVal)
                        ->where('branch_id', $rowBranchId)
                        ->exists();
                    if ($existsByMobile) {
                        $duplicateCount++;
                        $errors[] = "Row {$rowCount}: Mobile '{$mobileVal}' already registered to another customer in target branch — skipped.";
                        continue;
                    }
                }

                if ($usedDummy) {
                    $dummyCount++;
                }

                // ── Auto-generate customer_id if empty ────────────────────────
                if ($customerId === '') {
                    $maxId      = Customer::max('id') ?: 0;
                    $nextId     = $maxId + $rowCount;
                    $customerId = 'CUST-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                // Ensure customer_id uniqueness with suffix
                $originalId = $customerId;
                $suffix = 1;
                while (Customer::where('customer_id', $customerId)->exists()) {
                    $customerId = $originalId . '-' . $suffix++;
                }

                $opening = (float)$val('opening_balance', 0);
                $customerType = trim($val('customer_type', 'Main Customer')) ?: 'Main Customer';

                $partyType = 'Customer';
                $lowerType = strtolower($customerType);
                if (str_contains($lowerType, 'vendor') || str_contains($lowerType, 'both')) {
                    $partyType = 'Vendor/Customer';
                }

                $customer = Customer::create([
                    'customer_id'      => $customerId,
                    'customer_name'    => $customerName,
                    'customer_type'    => $customerType,
                    'cnic'             => $val('cnic') ?: null,
                    'mobile'           => $val('mobile') ?: null,
                    'email_address'    => $val('email_address') ?: null,
                    'contact_person'   => $val('contact_person') ?: null,
                    'zone'             => $val('zone') ?: null,
                    'address'          => $val('address') ?: null,
                    'city'             => $val('city') ?: null,
                    'filer_type'       => $val('filer_type', 'Non-Filer') ?: 'Non-Filer',
                    'ntn_no'           => $val('ntn_no') ?: null,
                    'gst_no'           => $val('gst_no') ?: null,
                    'dsl_no'           => $val('dsl_no') ?: null,
                    'drap_no'          => $val('drap_no') ?: null,
                    'opening_balance'  => $opening,
                    'credit_terms'     => $val('credit_terms') ?: null,
                    'balance_range'    => $creditLimit,
                    'sales_officer_id' => $salesOfficerId,
                    'status'           => 'active',
                    'is_active'        => 1,
                    'branch_id'        => $rowBranchId,
                    'party_type'       => $partyType,
                ]);

                // Sync to Vendor if dual type
                if ($partyType === 'Vendor/Customer') {
                    $vendorUpdateData = [
                        'name'            => $customerName,
                        'cnic'            => $val('cnic') ?: null,
                        'address'         => $val('address') ?: null,
                        'phone'           => $val('mobile') ?: null,
                        'email'           => $val('email_address') ?: null,
                        'party_type'      => 'Vendor/Customer',
                        'branch_id'       => $rowBranchId,
                        'is_active'       => 1,
                        'city'            => $val('city') ?: null,
                        'country'         => 'Pakistan',
                        'ntn_no'          => $val('ntn_no') ?: null,
                        'gst_no'          => $val('gst_no') ?: null,
                        'dsl_no'          => $val('dsl_no') ?: null,
                        'drap_no'         => $val('drap_no') ?: null,
                        'opening_balance' => 0,
                        'contact_person'  => $val('contact_person') ?: null,
                    ];

                    $vendor = \App\Models\Vendor::where('vendor_code', $customerId)->first();
                    if ($vendor) {
                        $vendor->update($vendorUpdateData);
                    } else {
                        $vendor = \App\Models\Vendor::create(array_merge($vendorUpdateData, ['vendor_code' => $customerId]));
                        \App\Models\VendorLedger::create([
                            'vendor_id'        => $vendor->id,
                            'branch_id'        => $vendor->branch_id,
                            'admin_or_user_id' => $userId,
                            'opening_balance'  => 0,
                            'closing_balance'  => 0,
                            'previous_balance' => 0,
                        ]);
                    }
                }



                // Create initial ledger & journal entry if opening balance > 0
                if ($opening > 0) {
                    CustomerLedger::create([
                        'customer_id'      => $customer->id,
                        'branch_id'        => $customer->branch_id,
                        'admin_or_user_id' => $userId,
                        'previous_balance' => $opening,
                        'opening_balance'  => $opening,
                        'closing_balance'  => $opening,
                        'description'      => 'Imported Opening Balance',
                    ]);

                    try {
                        $balanceService = app(\App\Services\BalanceService::class);
                        $balanceService->ensureDefaultCOA();
                        $arAccountId = $balanceService->getAccountsReceivableId();
                        $journalService = app(\App\Services\JournalEntryService::class);

                        $journalService->recordEntry(
                            $customer,
                            $arAccountId,
                            $opening,
                            0,
                            "Opening Balance for Customer: {$customer->customer_name}",
                            now()->toDateString(),
                            $customer
                        );
                    } catch (\Exception $jeEx) {
                        // Log or soft ignore if COA not configured
                    }
                }

                $importedCount++;
            }


            \DB::commit();

            return response()->json([
                'status'          => 'success',
                'imported_count'  => $importedCount,
                'skipped_count'   => $skippedCount,
                'dummy_count'     => $dummyCount,
                'duplicate_count' => $duplicateCount,
                'errors'          => $errors,
                'message'         => "Successfully imported {$importedCount} customers."
                    . ($duplicateCount > 0 ? " ({$duplicateCount} duplicates skipped)" : "")
                    . ($dummyCount > 0 ? " ({$dummyCount} with dummy data)" : ""),
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'type'    => 'exception',
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
