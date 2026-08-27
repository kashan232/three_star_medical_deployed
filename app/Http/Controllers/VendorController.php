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
     * Show vendor ledger (journal/dual-party combined)
     */
    public function ledger($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);

        // Get date range from request or default to current month
        $startDate = request('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = request('end_date', now()->endOfMonth()->format('Y-m-d'));
        $branchId = $this->getBranchId();

        $dualService = app(\App\Services\DualPartyLedgerService::class);
        $ledgerData = $dualService->getVendorLedgerData($vendorId, $startDate, $endDate, $branchId);

        return view('admin_panel.vendors.ledger', [
            'vendor'          => $ledgerData['party'],
            'twin_party'      => $ledgerData['twin_party'],
            'is_dual'         => $ledgerData['is_dual'],
            'opening_balance' => $ledgerData['opening_balance'],
            'closing_balance' => $ledgerData['closing_balance'],
            'total_debit'     => $ledgerData['total_debit'],
            'total_credit'    => $ledgerData['total_credit'],
            'transactions'    => $ledgerData['transactions'],
        ]);
    }

    // =========================================================================
    //  VENDOR IMPORT & TEMPLATE DOWNLOAD (CONSOLIDATED)
    // =========================================================================

    public function downloadTemplate()
    {
        $data = [
            // Header Row
            [
                'VENDOR NAME', 'VENDOR CODE', 'EMAIL', 'PHONE', 'ADDRESS',
                'CITY', 'COUNTRY', 'BUSINESS NAME', 'NTN NO', 'CNIC',
                'CONTACT PERSON', 'CREDIT LIMIT', 'OPENING BALANCE', 'CREDIT TERMS'
            ],
            // Sample Row 1
            [
                'Alpha Surgical Supplies', 'VND-0001', 'sales@alphasurgical.com', '0300-9876543', '12-A Industrial Area',
                'Lahore', 'Pakistan', 'Alpha Surgical Ltd', '9876543-2', '42101-9876543-1',
                'Kashif Shaheen', '500000', '15000.00', '30 Days'
            ],
            // Sample Row 2
            [
                'Beta Pharma Distributors', 'VND-0002', 'info@betapharma.com', '0321-4567890', '45 Commercial Market, Tariq Road',
                'Karachi', 'Pakistan', 'Beta Pharma Group', '', '',
                'Muhammad Asif', '1000000', '0.00', 'Cash'
            ]
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $xlsx->setColWidth('A', 30);
        $xlsx->setColWidth('B', 15);
        $xlsx->setColWidth('C', 24);
        $xlsx->setColWidth('D', 16);
        $xlsx->setColWidth('E', 30);
        $xlsx->setColWidth('F', 15);
        $xlsx->setColWidth('G', 15);
        $xlsx->setColWidth('H', 24);
        $xlsx->setColWidth('I', 15);
        $xlsx->setColWidth('J', 18);
        $xlsx->setColWidth('K', 20);
        $xlsx->setColWidth('L', 15);
        $xlsx->setColWidth('M', 18);
        $xlsx->setColWidth('N', 15);

        $tmpPath = storage_path('app/vendor_template_' . uniqid() . '.xlsx');
        $xlsx->saveAs($tmpPath);

        return response()->download($tmpPath, 'vendor_import_template.xlsx', [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
        ])->deleteFileAfterSend(true);
    }

    public function importVendors(Request $request)
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

            $rawHeaders = array_shift($rawRows);
            $normalizedHeaders = array_map(function ($h) {
                return strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '_', (string)$h), '_'));
            }, $rawHeaders);

            $aliases = [
                'vendor_name'     => 'name',
                'vendorname'      => 'name',
                'name'            => 'name',
                'party_name'      => 'name',
                'supplier_name'   => 'name',

                'vendor_code'     => 'vendor_code',
                'vendorcode'      => 'vendor_code',
                'code'            => 'vendor_code',
                'id'              => 'vendor_code',

                'email'           => 'email',
                'email_address'   => 'email',

                'phone'           => 'phone',
                'phone_no'        => 'phone',
                'mobile'          => 'phone',
                'contact'         => 'phone',

                'address'         => 'address',
                'street'          => 'address',

                'city'            => 'city',
                'country'         => 'country',

                'business_name'   => 'business_name',
                'businessname'    => 'business_name',
                'company'         => 'business_name',

                'ntn'             => 'ntn_no',
                'ntn_no'          => 'ntn_no',

                'cnic'            => 'cnic',
                'cnic_no'         => 'cnic',

                'contact_person'  => 'contact_person',
                'contactperson'   => 'contact_person',

                'credit_limit'    => 'credit_limit',
                'limit'           => 'credit_limit',

                'opening_balance' => 'opening_balance',
                'openingbalance'  => 'opening_balance',
                'balance'         => 'opening_balance',

                'credit_terms'    => 'credit_terms',
                'creditterms'     => 'credit_terms',
            ];

            $fieldMap = [];
            foreach ($normalizedHeaders as $idx => $rawKey) {
                $canonical = $aliases[$rawKey] ?? $rawKey;
                if (!isset($fieldMap[$canonical])) {
                    $fieldMap[$canonical] = $idx;
                }
            }

            // ── Require at minimum: name ─────────────────────────────────────
            $validationErrors = [];
            if (!isset($fieldMap['name'])) {
                return response()->json([
                    'status'  => 'error',
                    'type'    => 'column_mismatch',
                    'message' => 'Required column <strong>VENDOR NAME</strong> not found in your file.<br>Please download and use the provided template.',
                ], 400);
            }

            $val = function (string $field, $default = '') use (&$row, &$fieldMap): string {
                if (isset($fieldMap[$field]) && isset($row[$fieldMap[$field]])) {
                    $v = trim((string)$row[$fieldMap[$field]]);
                    return $v === '' ? (string)$default : $v;
                }
                return (string)$default;
            };

            $branchId = $this->getBranchId() ?? 1;
            $userId   = auth()->id();

            $rowCount       = 0;
            $importedCount  = 0;
            $skippedCount   = 0;
            $dummyCount     = 0;
            $duplicateCount = 0;
            $errors         = [];

            $autoFillDummy = $request->boolean('auto_fill_dummy') || $request->input('auto_fill_dummy') == '1';

            \DB::beginTransaction();

            foreach ($rawRows as $row) {
                $rowCount++;

                if (empty(array_filter(array_map('trim', $row)))) {
                    $skippedCount++;
                    continue;
                }

                $vendorName = trim($val('name'));
                $usedDummy  = false;

                if (empty($vendorName)) {
                    if ($autoFillDummy) {
                        $vendorName = "[DUMMY] Vendor Row {$rowCount}";
                        $usedDummy  = true;
                    } else {
                        $validationErrors[] = "Row {$rowCount}: Vendor Name is required.";
                        continue;
                    }
                }

                // ── Duplicate Check 1: vendor_code already exists ──────────────
                $vendorCode = trim($val('vendor_code'));
                if ($vendorCode !== '' && Vendor::where('vendor_code', $vendorCode)->exists()) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowCount}: Vendor Code '{$vendorCode}' already exists — skipped.";
                    continue;
                }

                // ── Duplicate Check 2: same name in same branch ────────────────
                $existsByName = Vendor::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($vendorName)])
                    ->where('branch_id', $branchId)
                    ->exists();
                if ($existsByName) {
                    $duplicateCount++;
                    $errors[] = "Row {$rowCount}: Vendor '{$vendorName}' already exists — skipped.";
                    continue;
                }

                // ── Duplicate Check 3: same phone number ───────────────────────
                $phoneVal = trim($val('phone'));
                if ($phoneVal !== '') {
                    $existsByPhone = Vendor::where('phone', $phoneVal)
                        ->where('branch_id', $branchId)
                        ->exists();
                    if ($existsByPhone) {
                        $duplicateCount++;
                        $errors[] = "Row {$rowCount}: Phone '{$phoneVal}' already registered to another vendor — skipped.";
                        continue;
                    }
                }

                if ($usedDummy) {
                    $dummyCount++;
                }

                // Auto-generate code if empty
                if ($vendorCode === '') {
                    $maxId      = Vendor::max('id') ?: 0;
                    $nextId     = $maxId + $rowCount;
                    $vendorCode = 'VND-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                // Ensure vendor_code uniqueness
                $originalCode = $vendorCode;
                $suffix = 1;
                while (Vendor::where('vendor_code', $vendorCode)->exists()) {
                    $vendorCode = $originalCode . '-' . $suffix++;
                }

                $opening = (float)$val('opening_balance', 0);

                $vendor = Vendor::create([
                    'name'            => $vendorName,
                    'vendor_code'     => $vendorCode,
                    'email'           => $val('email') ?: null,
                    'phone'           => $phoneVal ?: null,
                    'address'         => $val('address') ?: null,
                    'city'            => $val('city') ?: null,
                    'country'         => $val('country') ?: 'Pakistan',
                    'business_name'   => $val('business_name') ?: null,
                    'ntn_no'          => $val('ntn_no') ?: null,
                    'cnic'            => $val('cnic') ?: null,
                    'contact_person'  => $val('contact_person') ?: null,
                    'credit_limit'    => (float)$val('credit_limit', 0),
                    'opening_balance' => $opening,
                    'credit_terms'    => $val('credit_terms') ?: null,
                    'is_active'       => 1,
                    'branch_id'       => $branchId,
                ]);

                // Create ledger entry
                VendorLedger::create([
                    'vendor_id'        => $vendor->id,
                    'branch_id'        => $vendor->branch_id,
                    'admin_or_user_id' => $userId,
                    'opening_balance'  => $opening,
                    'closing_balance'  => $opening,
                    'previous_balance' => $opening,
                ]);

                if ($opening > 0) {
                    try {
                        $balanceService = app(\App\Services\BalanceService::class);
                        $balanceService->ensureDefaultCOA();
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
                    } catch (\Exception $jeEx) {
                        // Ignore if COA not configured
                    }
                }

                $importedCount++;
            }

            // ── Abort if any validation errors (missing name) ─────────────────
            if (!empty($validationErrors)) {
                \DB::rollBack();
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

            \DB::commit();

            return response()->json([
                'status'          => 'success',
                'imported_count'  => $importedCount,
                'skipped_count'   => $skippedCount,
                'dummy_count'     => $dummyCount,
                'duplicate_count' => $duplicateCount,
                'errors'          => $errors,
                'message'         => "Successfully imported {$importedCount} vendors."
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

