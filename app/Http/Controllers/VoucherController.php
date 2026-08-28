<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\ExpenseVoucher;
use App\Models\Narration;
use App\Models\PaymentVoucher;
use App\Models\ReceiptsVoucher;
use App\Models\VendorLedger;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Http\Traits\BranchScoped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    use BranchScoped;
    public function index($type)
    {
        $branchId  = $this->getBranchId();
        // Sirf selected type ka data laa lo
        $vouchers  = Voucher::where('voucher_type', $type)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest()->get();
        $narration = Narration::where('expense_head', $type)->get();

        return view('admin_panel.accounts.expenses', [
            'vouchers'  => $vouchers,
            'type'      => $type,
            'narration' => $narration,
        ]);
    }

    public function store(Request $request)
    {
        // Validate that arrays are present and match in length
        $request->validate([
            'date' => 'required',
            'type' => 'required',
            'person' => 'required',
            'narration' => 'required',
            'amount' => 'required',
        ]);

        // Loop through each row and create a voucher
        foreach ($request->date as $index => $date) {
            Voucher::create([
                'voucher_type' => $request->sub_head,
                'sales_officer' => auth()->user()->name,
                'date' => $date,
                'type' => $request->type[$index],
                'person' => $request->person[$index],
                'sub_head' => $request->sub_head[$index] ?? null,
                'narration' => $request->narration[$index],
                'amount' => $request->amount[$index],
                'status' => 'draft',
            ]);
        }

        return back()->with('success', 'Vouchers saved successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Voucher $voucher)
    {
        //
    }

    public function receipt($id)
    {
        $voucher = Voucher::findOrFail($id);

        $customerName = $voucher->person; // Default
        $customerAddress = '-';
        $closingBalance = 0;

        // yahan accounts bhi show karwayn all heads
        // bank cash
        if ($voucher->type === 'Main Customer' && $voucher->mainCustomer) {
            $customerName = $voucher->mainCustomer->customer_name;
            $customerAddress = $voucher->mainCustomer->address;
            $closingBalance = $voucher->mainCustomer->closing_balance;
        } elseif ($voucher->type === 'Sub Customer' && $voucher->subCustomer) {
            $customerName = $voucher->subCustomer->customer_name;
            $customerAddress = $voucher->subCustomer->address;
            $closingBalance = $voucher->subCustomer->closing_balance;
        }

        return view('admin_panel.accounts.receipt', compact('voucher', 'customerName', 'customerAddress', 'closingBalance'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Voucher $voucher)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Voucher $voucher)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Voucher $voucher)
    {
        //
    }

    public function all_recepit_vochers()
    {
        $branchId = $this->getBranchId();
        // V2: Fetch Receipt AND Journal Vouchers (to show Sales Invoices + Receipts)
        $receipts = \App\Models\VoucherMaster::whereIn('voucher_type', [
            \App\Models\VoucherMaster::TYPE_RECEIPT,
            \App\Models\VoucherMaster::TYPE_JOURNAL,
        ])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['party', 'createdBy']) // Eager load party and creator
            ->orderBy('id', 'DESC')
            ->get();

        foreach ($receipts as $voucher) {
            $typeLabel = '-';
            $partyName = '-';

            if ($voucher->party) {
                // Determine Label from Class Name
                $class = get_class($voucher->party);
                if (str_contains($class, 'Customer')) {
                    $typeLabel = 'Customer';
                    $partyName = $voucher->party->customer_name ?? $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Vendor')) {
                    $typeLabel = 'Vendor';
                    $partyName = $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Account')) {
                    $typeLabel = 'Account';
                    $partyName = $voucher->party->title ?? '-';
                } else {
                    $typeLabel = class_basename($class);
                    $partyName = $voucher->party->name ?? '-';
                }
            }

            // Attach for View
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;

            // Map old fields to new fields for View compatibility (or update View)
            // View uses: rvid, receipt_date, entry_date
            $voucher->rvid = $voucher->voucher_no;
            $voucher->receipt_date = $voucher->date->format('Y-m-d');
            $voucher->entry_date = $voucher->created_at->format('Y-m-d');

            // Fix: Map total_amount to amount for View compatibility
            if (! isset($voucher->amount)) {
                $voucher->amount = $voucher->total_amount;
            }
        }

        return view('admin_panel.vochers.all_recepit_vochers', compact('receipts'));
    }

    public function print($id)
    {
        \Log::info('Print Voucher Requested. ID: '.$id);

        // 1. Try V2 VoucherMaster First
        $voucherV2 = \App\Models\VoucherMaster::where('voucher_type', 'receipt')->find($id);

        if ($voucherV2) {
            \Log::info('Found V2 Voucher: '.$voucherV2->voucher_no);

            // Lazy load relationships to avoid eager loading weirdness
            $voucherV2->load(['details.account', 'party', 'createdBy']);

            // -- Adapter for V2 to V1 View --
            $voucher = (object) [
                'rvid' => $voucherV2->voucher_no,
                'receipt_date' => $voucherV2->date->format('Y-m-d'),
                'total_amount' => $voucherV2->amount,
                'remarks' => $voucherV2->remarks,
                'type' => 'unknown', // Default
                'created_at' => $voucherV2->created_at,
            ];

            if (! isset($voucher->total_amount)) {
                $voucher->total_amount = $voucherV2->total_amount;
            }

            $rows = [];
            foreach ($voucherV2->details as $detail) {
                $ref = '-';
                if (preg_match('/#([A-Za-z0-9\-_]+)/', $detail->narration, $matches)) {
                    $ref = $matches[1];
                }

                $rows[] = [
                    'narration' => $detail->narration,
                    'reference' => $ref,
                    'account_head' => $detail->account->account_head_id ?? '-',
                    'account_name' => $detail->account->title ?? '-',
                    'account_code' => $detail->account->account_code ?? '-',
                    'amount' => $detail->credit > 0 ? $detail->credit : $detail->debit,
                    'debit' => $detail->debit,
                    'credit' => $detail->credit,
                ];
            }

            // Party Logic
            $party = $voucherV2->party;
            $previousBalance = 0;

            if ($party) {
                if ($party instanceof \App\Models\Customer) {
                    $voucher->type = ($party->customer_type == 'Walking Customer') ? 'walkin' : 'customer';

                    // Ensure fields expected by view exist
                    $party->name = $party->customer_name; // Fallback
                    $party->address = $party->address ?? '-';
                    $party->mobile = $party->mobile ?? '-';

                    $previousBalance = \App\Models\CustomerLedger::where('customer_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Vendor) {
                    $voucher->type = 'vendor';
                    $party->address = $party->address ?? '-';
                    $party->phone = $party->phone ?? '-'; // View uses phone

                    $previousBalance = \App\Models\VendorLedger::where('vendor_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Account) {
                    $voucher->type = '1'; // Numeric triggers Account Block
                    $party->name = $party->title;
                    $party->phone = $party->account_code;
                    $party->head_name = $party->accountHead->name ?? 'Account';

                    $previousBalance = $party->opening_balance;
                }
            } else {
                $previousBalance = 0;
            }

            return view('admin_panel.vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
        }

        // 2. Fallback to V1 Legacy (Original Code)
        $voucher = ReceiptsVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // Rows build
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float) ($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ If type is numeric → means from Account Head
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object) [
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            // ✅ If vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ If walkin
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    public function getAccountsByHead($headId)
    {
        $branchId = $this->getBranchId();
        $head = AccountHead::find($headId);
        $headName = $head ? $head->name : 'Account';

        $accounts = Account::where('head_id', $headId)
            ->where('status', 1)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Format for "Party | Account" display and attach real-time dynamic balance
        foreach ($accounts as $acc) {
            $acc->title = $headName . ' | ' . $acc->title;
            $bal = (float)($acc->calculated_balance ?? $acc->current_balance ?? $acc->opening_balance ?? 0);
            $acc->balance = $bal;
            $acc->current_balance = $bal;
            $acc->opening_balance = $bal;
        }

        return response()->json($accounts);
    }


    public function getOpeningBalance($type, $id)
    {
        if ($type === 'customer' || $type === 'walkin') {
            $customer = Customer::find($id);

            // echo "<pre>";
            // print_r($customer);
            // echo "<pre>";
            // dd();
            return response()->json([
                'opening_balance' => $customer->opening_balance ?? 0,
            ]);
        }

        // Account case
        $account = AccountHead::find($id);

        return response()->json([
            'opening_balance' => $account->opening_balance ?? 0,
        ]);
    }

    public function recepit_vochers()
    {
        $branchId = $this->getBranchId();
        $narrations = \App\Models\Narration::where('expense_head', 'Receipts Voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();


        // echo "<pre>";
        // print_r($AccountHeads) ;
        // echo "<pre>";
        // dd();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ReceiptsVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'RVID-'.str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.reciepts_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_rec_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $rvid = $request->rvid ?: \App\Models\ReceiptsVoucher::generateRVID(auth()->id());
            $narrationIds = [];

            foreach ($request->narration_id ?? [] as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && ! empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Receipts Voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Receipts Voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration' => $manualText,
                    ]);

                    $narrationIds[] = (string) $new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string) $narrId; // force string format
                }
            }

            // ─── Validate required fields before any DB writes ────────────────
            if (empty($request->vendor_type)) {
                throw new \Exception('Party Type (Customer/Vendor) is required.');
            }
            if (empty($request->vendor_id)) {
                throw new \Exception('Please select a Party (Customer/Vendor/Account).');
            }
            if (empty($request->receipt_date)) {
                throw new \Exception('Receipt Date is required.');
            }
            $totalAmt = (float) $request->total_amount;
            if ($totalAmt <= 0) {
                throw new \Exception('Total amount must be greater than zero.');
            }
            $filledAccounts = array_filter($request->row_account_id ?? [], fn($v) => !empty($v));
            if (empty($filledAccounts)) {
                throw new \Exception('At least one debit account (Cash/Bank) must be selected.');
            }

            // ✅ V2 VOUCHER INTEGRATION (Primary Logic Now)
            // No inner try-catch — any failure rolls back the full transaction cleanly.
            \Log::info('V2 Integration Start. Type: '.$request->vendor_type.', ID: '.$request->vendor_id);

            $vType      = strtolower($request->vendor_type);
            $partyType  = null;
            $balanceService = app(\App\Services\BalanceService::class);

            if ($vType == 'customer' || $vType == 'walkin') {
                $partyType      = \App\Models\Customer::class;
                $creditAccountId = $balanceService->getAccountsReceivableId();
            } elseif ($vType == 'vendor') {
                $partyType      = \App\Models\Vendor::class;
                $creditAccountId = $balanceService->getAccountsPayableId();
            } else {
                $partyType      = \App\Models\Account::class;
                $creditAccountId = (int) $request->vendor_id;
            }

            if (empty($creditAccountId)) {
                throw new \Exception("Could not find the required Chart of Accounts entry for type '{$vType}'. Please run Chart of Accounts Setup from the Accounts menu first.");
            }

            $v2Lines           = [];
            $firstTargetBankId = null;
            $chequeInHandId    = null;

            if ($request->payment_mode === 'cheque') {
                $chequeInHand   = \App\Models\Account::where('title', 'Cheque In Hand')->first();
                $chequeInHandId = $chequeInHand?->id;
            }

            // DEBIT SIDE (Cash/Bank/Cheque) - From Row Inputs
            foreach ($request->row_account_id as $idx => $accId) {
                if (empty($accId)) continue;
                $amt = (float) ($request->amount[$idx] ?? 0);
                if ($amt <= 0) continue;

                if ($request->payment_mode === 'cheque' && $chequeInHandId) {
                    if (! $firstTargetBankId) $firstTargetBankId = $accId;
                    $v2Lines[] = [
                        'account_id' => $chequeInHandId,
                        'debit'      => $amt,
                        'credit'     => 0,
                        'narration'  => $request->narration_text[$idx] ?? 'Cheque Receipt (' . $request->cheque_no . ')',
                    ];
                } else {
                    $v2Lines[] = [
                        'account_id' => $accId,
                        'debit'      => $amt,
                        'credit'     => 0,
                        'narration'  => $request->narration_text[$idx] ?? 'Receipt',
                    ];
                }
            }

            // CREDIT SIDE (Accounts Receivable / Payable) - Total Amount
            $v2Lines[] = [
                'account_id' => $creditAccountId,
                'debit'      => 0,
                'credit'     => $totalAmt,
                'narration'  => 'Receipt from '.$request->vendor_type,
            ];

            if (count($v2Lines) < 2) {
                throw new \Exception('No valid journal lines were generated. Please check the amounts entered.');
            }

            $createdVoucher = app(\App\Services\VoucherService::class)->createVoucher([
                'voucher_type' => 'receipt',
                'date'         => $request->receipt_date,
                'status'       => 'posted',
                'party_type'   => $partyType,
                'party_id'     => $request->vendor_id,
                'remarks'      => (($request->remarks && strtolower($request->remarks) !== 'active') ? $request->remarks : "Manual Receipt Voucher") . " (Ref: $rvid)",
            ], $v2Lines, auth()->id());

            if ($request->payment_mode === 'cheque' && $firstTargetBankId) {
                \App\Models\Cheque::create([
                    'voucher_master_id' => $createdVoucher->id,
                    'cheque_no'         => $request->cheque_no,
                    'cheque_date'       => $request->cheque_date,
                    'bank_name'         => $request->bank_name,
                    'status'            => 'pending',
                    'amount'            => $totalAmt,
                    'actual_account_id' => $firstTargetBankId,
                ]);
            }

            \Log::info('V2 Voucher Created Successfully. Voucher ID: '.$createdVoucher->id);

            // 🚀 Customer Payment Allocation
            if (($vType == 'customer' || $vType == 'walkin') && $totalAmt > 0) {

                if ($request->sale_id) {
                    \App\Models\CustomerPayment::create([
                        'customer_id'      => $request->vendor_id,
                        'sale_id'          => $request->sale_id,
                        'admin_or_user_id' => auth()->id() ?? 1,
                        'amount'           => $totalAmt,
                        'payment_method'   => 'Manual Voucher',
                        'payment_date'     => $request->receipt_date,
                        'note'             => ($request->remarks && strtolower($request->remarks) !== 'active' ? $request->remarks : "Manual Receipt Voucher #$rvid") . " linked to Sale #".$request->sale_id,
                    ]);
                } else {
                    // Auto-allocate to unpaid sales (oldest first)
                    $unpaidSales = \App\Models\Sale::where('customer_id', $request->vendor_id)
                        ->where('total_net', '>', 0)
                        ->whereIn('sale_status', ['post', 'returned'])
                        ->orderBy('id', 'asc')
                        ->get();

                    $remainingToAllocate = $totalAmt;
                    foreach ($unpaidSales as $sale) {
                        if ($remainingToAllocate <= 0) break;

                        $totalPaid     = \App\Models\CustomerPayment::where('sale_id', $sale->id)->sum('amount');
                        $totalReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)->sum('net_amount');
                        $updatedNet    = max(0, $sale->total_net - $totalReturned);
                        $actualDue     = max(0, $updatedNet - $totalPaid);

                        if ($actualDue > 0) {
                            $allocate = min($actualDue, $remainingToAllocate);
                            \App\Models\CustomerPayment::create([
                                'customer_id'      => $request->vendor_id,
                                'sale_id'          => $sale->id,
                                'admin_or_user_id' => auth()->id() ?? 1,
                                'amount'           => $allocate,
                                'payment_method'   => 'Manual Voucher',
                                'payment_date'     => $request->receipt_date,
                                'note'             => "Auto-allocated via Receipt Voucher #$rvid",
                            ]);
                            $remainingToAllocate -= $allocate;
                        }
                    }

                    // Unallocated amount becomes advance payment
                    if ($remainingToAllocate > 0) {
                        \App\Models\CustomerPayment::create([
                            'customer_id'      => $request->vendor_id,
                            'sale_id'          => null,
                            'admin_or_user_id' => auth()->id() ?? 1,
                            'amount'           => $remainingToAllocate,
                            'payment_method'   => 'Manual Voucher',
                            'payment_date'     => $request->receipt_date,
                            'note'             => "Advance via Receipt Voucher #$rvid",
                        ]);
                    }
                }

                // 📘 Legacy Ledger Sync: Customer
                $latestLedger = \App\Models\CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                $prevBal      = $latestLedger
                    ? $latestLedger->closing_balance
                    : (\App\Models\Customer::find($request->vendor_id)?->opening_balance ?? 0);
                $newBal = $prevBal - $totalAmt;

                \App\Models\CustomerLedger::create([
                    'customer_id'      => $request->vendor_id,
                    'branch_id'        => $this->getBranchId() ?? 1,
                    'admin_or_user_id' => auth()->id() ?? 1,
                    'description'      => ($request->remarks && strtolower($request->remarks) !== 'active') ? $request->remarks : "Manual Receipt Voucher #$rvid",
                    'previous_balance' => $prevBal,
                    'closing_balance'  => $newBal,
                    'opening_balance'  => 0,
                    'source_type'      => \App\Models\VoucherMaster::class,
                    'source_id'        => $createdVoucher->id,
                    'created_at'       => $request->receipt_date
                        ? \Carbon\Carbon::parse($request->receipt_date)->setHour(now()->hour)->setMinute(now()->minute)
                        : now(),
                ]);

                // Update Customer Master closing balance
                $cust = \App\Models\Customer::find($request->vendor_id);
                if ($cust) {
                    $cust->previous_balance = $newBal;
                    $cust->save();
                }
            }

            // 📘 Legacy Ledger Sync: Vendor (Receipt from Vendor reduces our payable)
            if ($vType == 'vendor' && $totalAmt > 0) {
                $latestLedger = \App\Models\VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();
                $prevBal      = $latestLedger ? $latestLedger->closing_balance : 0;
                $newBal       = $prevBal - $totalAmt;

                \App\Models\VendorLedger::create([
                    'vendor_id'        => $request->vendor_id,
                    'branch_id'        => $this->getBranchId() ?? 1,
                    'admin_or_user_id' => auth()->id() ?? 1,
                    'date'             => now(),
                    'description'      => ($request->remarks && strtolower($request->remarks) !== 'active') ? $request->remarks : "Manual Receipt Voucher #$rvid",
                    'opening_balance'  => 0,
                    'debit'            => 0,
                    'credit'           => $totalAmt,
                    'previous_balance' => $prevBal,
                    'closing_balance'  => $newBal,
                    'source_type'      => \App\Models\VoucherMaster::class,
                    'source_id'        => $createdVoucher->id,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Receipt Voucher saved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Receipt Voucher Save Error: '.$e->getMessage().' | Line: '.$e->getLine().' | File: '.basename($e->getFile()));
            return back()->withInput()->with('error', $e->getMessage());
        }
    }


    public function Payment_vochers()
    {
        $branchId  = $this->getBranchId();
        $narrations = \App\Models\Narration::where('expense_head', 'Payment voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();


        // Next PVID based on current branch
        $nextPVID = \App\Models\PaymentVoucher::generateInvoiceNo();

        return view('admin_panel.vochers.payment_vochers.payment_vouchers', compact('narrations', 'AccountHeads', 'nextPVID'));
    }

    public function store_Pay_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $pvid = PaymentVoucher::generateInvoiceNo();
            $narrationIds = [];

            // Narration handling (assuming multiple narrations from table)
            if ($request->narration_id) {
                foreach ($request->narration_id as $index => $narrId) {
                    $manualText = $request->narration_text[$index] ?? null;
                    if (empty($narrId) && ! empty($manualText)) {
                        $new = \App\Models\Narration::create([
                            'expense_head' => 'Payment voucher',
                            'narration' => $manualText,
                        ]);
                        $narrationIds[] = (string) $new->id;
                    } else {
                        $narrationIds[] = (string) $narrId;
                    }
                }
            }

            // In this new design:
            // Header = Source (Account) -> row_account_id (Single)
            // Table = Destination (Party) -> vendor_type[], vendor_id[] (Multiple)

            $voucherData = [
                'pvid' => $pvid,
                'receipt_date' => $request->receipt_date,
                'entry_date' => $request->entry_date,

                // Store Header Source as single values
                'row_account_head' => $request->header_account_head,
                'row_account_id' => $request->header_account_id,
                'remarks' => ($request->remarks && strtolower($request->remarks) !== 'active') ? $request->remarks : "Manual Payment Voucher #$pvid",

                // Store Table Destinations as JSON
                'type' => json_encode($request->vendor_type),
                'party_id' => json_encode($request->vendor_id),
                'narration_id' => json_encode($narrationIds),
                'reference_no' => json_encode($request->reference_no),
                'discount_value' => json_encode($request->discount_value),
                'rate' => json_encode($request->rate),
                'amount' => json_encode($request->amount),
                'total_amount' => $request->total_amount,
            ];

            // PaymentVoucher::create($voucherData); // Disabled legacy duplicate

            $totalAmount = (float) $request->total_amount;

            // --- V2 VoucherMaster Integration ---
            $partyType = null;
            $partyId   = null;

            // Let's use the FIRST valid destination party as the master party for list views
            if (!empty($request->vendor_id) && !empty($request->vendor_type)) {
                $firstType = $request->vendor_type[0] ?? null;
                if ($firstType === 'vendor') {
                    $partyType = \App\Models\Vendor::class;
                    $partyId   = $request->vendor_id[0] ?? null;
                } elseif ($firstType === 'customer' || $firstType === 'walkin') {
                    $partyType = \App\Models\Customer::class;
                    $partyId   = $request->vendor_id[0] ?? null;
                } else {
                    $partyType = \App\Models\Account::class;
                    $partyId   = $request->vendor_id[0] ?? null;
                }
            }

            $v2Voucher = \App\Models\VoucherMaster::create([
                'voucher_type' => \App\Models\VoucherMaster::TYPE_PAYMENT,
                'voucher_no'   => $pvid,
                'date'         => $request->receipt_date ?? now(),
                'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type'   => $partyType,
                'party_id'     => $partyId,
                'total_amount' => $totalAmount,
                'remarks'      => ($request->remarks && strtolower($request->remarks) !== 'active') ? $request->remarks : "Manual Payment Voucher #$pvid",
                'created_by'   => auth()->id(),
                'branch_id'    => $this->getBranchId() ?? 1,
                'posted_at'    => now(),
            ]);

            // Create V2 Details for Source (CREDIT since money is leaving Cash/Bank)
            if ($request->header_account_id) {
                \App\Models\VoucherDetail::create([
                    'voucher_master_id' => $v2Voucher->id,
                    'account_id'        => $request->header_account_id,
                    'debit'             => 0,
                    'credit'            => $totalAmount,
                    'narration'         => 'Payment Source',
                ]);
            }

            /**
             * STEP 1: Header Source (Account) -> MINUS (Money Leaving)
             */
            if ($request->header_account_id) {
                $sourceAccount = \App\Models\Account::find($request->header_account_id);
                if ($sourceAccount) {
                    $sourceAccount->opening_balance = $sourceAccount->opening_balance - $totalAmount;
                    $sourceAccount->save();
                }

                if (strtolower($request->payment_mode ?? '') === 'cheque') {
                    \App\Models\Cheque::create([
                        'voucher_master_id' => $v2Voucher->id,
                        'cheque_no' => $request->cheque_no,
                        'cheque_date' => $request->cheque_date ?? now()->toDateString(),
                        'bank_name' => $request->bank_name,
                        'status' => 'pending',
                        'amount' => $totalAmount,
                        'actual_account_id' => $request->header_account_id
                    ]);
                }
            }

            /**
             * STEP 2: Table Destinations (Parties) → Update Ledgers + Create Journal Entries
             */
            $journalService = app(\App\Services\JournalEntryService::class);
            $balanceService  = app(\App\Services\BalanceService::class);

            // The primary source model is now V2 VoucherMaster
            $savedSource = $v2Voucher;

            if ($request->vendor_id && $request->amount) {
                foreach ($request->vendor_id as $index => $partyId) {
                    $type      = $request->vendor_type[$index] ?? null;
                    $rowAmount = isset($request->amount[$index]) ? (float) $request->amount[$index] : 0;
                    $rowDesc   = "Payment Voucher #$pvid";

                    if ($rowAmount <= 0) {
                        continue;
                    }

                    if ($type === 'vendor') {
                        // ── Legacy VendorLedger (balance summary table) ──────────────────────
                        // Payment to vendor REDUCES what we owe them (Payable decreases)
                        $ledger = VendorLedger::where('vendor_id', $partyId)->latest()->first();
                        $bal    = $ledger ? (float) $ledger->closing_balance : 0;
                        $newBal = $bal - $rowAmount; // Paying reduces our payable balance

                        VendorLedger::create([
                            'vendor_id'        => $partyId,
                            'branch_id'        => $this->getBranchId() ?? 1,
                            'admin_or_user_id' => auth()->id(),
                            'opening_balance'  => 0,
                            'previous_balance' => $bal,
                            'closing_balance'  => $newBal,
                            'debit'            => $rowAmount,
                            'credit'           => 0,
                            'description'      => $rowDesc,
                            'source_type'      => \App\Models\VoucherMaster::class,
                            'source_id'        => $v2Voucher->id,
                        ]);

                        // --- Auto-allocate to unpaid purchases ---
                        $unpaidPurchases = \App\Models\Purchase::with(['returns', 'payments'])
                            ->where('vendor_id', $partyId)
                            ->where('status_purchase', '!=', 'draft')
                            ->orderBy('purchase_date', 'asc')
                            ->get();

                        $remainingToAllocate = $rowAmount;
                        foreach ($unpaidPurchases as $p) {
                            if ($remainingToAllocate <= 0) break;

                            $totalReturned = $p->returns->sum('net_amount');
                            $totalPaid = $p->payments->sum('amount');
                            $updatedNet = max(0, $p->net_amount - $totalReturned);
                            $actualDue = max(0, $updatedNet - $totalPaid);

                            if ($actualDue > 0) {
                                $allocate = min($actualDue, $remainingToAllocate);
                                \App\Models\VendorPayment::create([
                                    'vendor_id'        => $partyId,
                                    'purchase_id'      => $p->id,
                                    'admin_or_user_id' => auth()->id(),
                                    'payment_date'     => $request->receipt_date ?? now()->toDateString(),
                                    'amount'           => $allocate,
                                    'payment_method'   => 'Payment Voucher',
                                    'note'             => "Auto-allocated via Payment Voucher #$pvid",
                                ]);
                                
                                $p->paid_amount += $allocate;
                                $p->due_amount = max(0, $p->net_amount - $p->paid_amount);
                                $p->save();

                                $remainingToAllocate -= $allocate;
                            }
                        }

                        // Any unallocated amount becomes advance (null purchase_id)
                        if ($remainingToAllocate > 0) {
                            \App\Models\VendorPayment::create([
                                'vendor_id'        => $partyId,
                                'purchase_id'      => null,
                                'admin_or_user_id' => auth()->id(),
                                'payment_date'     => $request->receipt_date ?? now()->toDateString(),
                                'amount'           => $remainingToAllocate,
                                'payment_method'   => 'Payment Voucher',
                                'note'             => "Advance via Payment Voucher #$pvid",
                            ]);
                        }
                        // -----------------------------------------

                        // ── Journal Entry (powers vendor ledger detail page) ─────────────────
                        // When we pay a vendor: Debit Accounts Payable (reduces liability)
                        // The party-linked entry is what BalanceService reads
                        if ($savedSource) {
                            $vendor = Vendor::find($partyId);
                            if ($vendor) {
                                $apAccountId = $balanceService->getAccountsPayableId();

                                \App\Models\VoucherDetail::create([
                                    'voucher_master_id' => $v2Voucher->id,
                                    'account_id'        => $apAccountId,
                                    'debit'             => $rowAmount,
                                    'credit'            => 0,
                                    'narration'         => $request->narration_text[$index] ?? 'Payment to Vendor',
                                ]);

                                // Debit Accounts Payable (reduces liability — we paid the vendor)
                                $journalService->recordEntry(
                                    $savedSource,
                                    $apAccountId,
                                    $rowAmount, // debit
                                    0,          // credit
                                    $rowDesc,
                                    $request->receipt_date ?? now()->toDateString(),
                                    $vendor     // link to vendor party
                                );
                            }
                        }

                    } elseif ($type === 'customer') {
                        // Payment to customer (refund) — reduces their receivable
                        $ledger = CustomerLedger::where('customer_id', $partyId)->latest()->first();
                        $bal    = $ledger ? (float) $ledger->closing_balance : 0;
                        $newBal = $bal - $rowAmount;

                        CustomerLedger::create([
                            'customer_id'      => $partyId,
                            'branch_id'        => $this->getBranchId() ?? 1,
                            'admin_or_user_id' => auth()->id(),
                            'previous_balance' => $bal,
                            'opening_balance'  => 0,
                            'closing_balance'  => $newBal,
                            'description'      => $rowDesc,
                            'source_type'      => \App\Models\VoucherMaster::class,
                            'source_id'        => $v2Voucher->id,
                        ]);

                        // Journal Entry for customer refund
                        if ($savedSource) {
                            $customer = Customer::find($partyId);
                            if ($customer) {
                                $arAccountId = $balanceService->getAccountsReceivableId();

                                \App\Models\VoucherDetail::create([
                                    'voucher_master_id' => $v2Voucher->id,
                                    'account_id'        => $arAccountId,
                                    'debit'             => $rowAmount,
                                    'credit'            => 0,
                                    'narration'         => $request->narration_text[$index] ?? 'Refund to Customer',
                                ]);

                                $journalService->recordEntry(
                                    $savedSource,
                                    $arAccountId,
                                    0,          // debit
                                    $rowAmount, // credit — reduces receivable
                                    $rowDesc,
                                    $request->receipt_date ?? now()->toDateString(),
                                    $customer
                                );
                            }
                        }

                    } elseif ($type) {
                        // Account destination
                        $acc = Account::find($partyId);
                        if ($acc) {
                            $acc->opening_balance = $acc->opening_balance + $rowAmount;
                            $acc->save();

                            \App\Models\VoucherDetail::create([
                                'voucher_master_id' => $v2Voucher->id,
                                'account_id'        => $partyId,
                                'debit'             => $rowAmount,
                                'credit'            => 0,
                                'narration'         => $request->narration_text[$index] ?? 'Payment Destination',
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return back()->with('success', 'Payment Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function all_Payment_vochers()
    {
        $branchId = $this->getBranchId();
        // V2: Fetch from VoucherMaster where type is PAYMENT
        $receipts = \App\Models\VoucherMaster::where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['party', 'createdBy']) // Eager load party and creator
            ->orderBy('id', 'DESC')
            ->get();


        foreach ($receipts as $voucher) {
            $typeLabel = '-';
            $partyName = '-';

            if ($voucher->party) {
                // Determine Label from Class Name
                $class = get_class($voucher->party);
                if (str_contains($class, 'Customer')) {
                    $typeLabel = 'Customer';
                    $partyName = $voucher->party->customer_name ?? $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Vendor')) {
                    $typeLabel = 'Vendor';
                    $partyName = $voucher->party->name ?? '-';
                } elseif (str_contains($class, 'Account')) {
                    $typeLabel = 'Account';
                    $partyName = $voucher->party->title ?? '-';
                } else {
                    $typeLabel = class_basename($class);
                    $partyName = $voucher->party->name ?? '-';
                }
            }

            // Attach for View
            $voucher->type_label = $typeLabel;
            $voucher->party_name = $partyName;

            // Map fields for View compatibility
            $voucher->pvid = $voucher->voucher_no;
            $voucher->receipt_date = $voucher->date->format('Y-m-d');
            $voucher->entry_date = $voucher->created_at->format('Y-m-d');

            // Fix for view expecting 'amount' field
            if (! isset($voucher->amount)) {
                $voucher->amount = $voucher->total_amount;
            }
        }

        return view('admin_panel.vochers.payment_vochers.all_payment_vochers', compact('receipts'));
    }

    public function Paymentprint($id)
    {
        // 1. Try V2 VoucherMaster First
        $voucherV2 = \App\Models\VoucherMaster::where('voucher_type', 'payment')->find($id);

        if ($voucherV2) {
            // Lazy load relationships
            $voucherV2->load(['details.account', 'party', 'createdBy']);

            // -- Adapter for V2 to V1 View --
            $voucher = (object) [
                'pvid' => $voucherV2->voucher_no,
                'receipt_date' => $voucherV2->date->format('Y-m-d'),
                'total_amount' => $voucherV2->amount,
                'remarks' => $voucherV2->remarks,
                'type' => 'unknown', // Default
                'created_at' => $voucherV2->created_at,
            ];

            if (! isset($voucher->total_amount)) {
                $voucher->total_amount = $voucherV2->total_amount;
            }

            $rows = [];
            foreach ($voucherV2->details as $detail) {
                // Determine account name/code/head
                $headName = $detail->account->accountHead->name ?? '-';
                $accName = $detail->account->title ?? '-';
                $accCode = $detail->account->account_code ?? '-';

                $ref = '-';
                if (preg_match('/#([A-Za-z0-9\-_]+)/', $detail->narration, $matches)) {
                    $ref = $matches[1];
                }

                $rows[] = [
                    'narration' => $detail->narration,
                    'reference' => $ref,
                    'account_head' => $headName,
                    'account_name' => $accName,
                    'account_code' => $accCode,
                    'amount' => $detail->debit > 0 ? $detail->debit : $detail->credit,
                    'debit' => $detail->debit,
                    'credit' => $detail->credit,
                ];
            }

            // Party Logic
            $party = $voucherV2->party;
            $previousBalance = 0;

            if ($party) {
                if ($party instanceof \App\Models\Customer) {
                    $voucher->type = ($party->customer_type == 'Walking Customer') ? 'walkin' : 'customer';

                    $party->name = $party->customer_name;
                    $party->address = $party->address ?? '-';
                    $party->mobile = $party->mobile ?? '-'; // View uses mobile/phone? View uses mobile for customer

                    $previousBalance = \App\Models\CustomerLedger::where('customer_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Vendor) {
                    $voucher->type = 'vendor';
                    $party->address = $party->address ?? '-';
                    $party->phone = $party->phone ?? '-'; // View uses phone

                    $previousBalance = \App\Models\VendorLedger::where('vendor_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Account) {
                    $voucher->type = '1'; // Numeric triggers Account Block
                    $party->name = $party->title;
                    $party->phone = $party->account_code; // View uses phone
                    $party->head_name = $party->accountHead->name ?? 'Account';

                    $previousBalance = $party->opening_balance;
                }
            }

            return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
        }

        $voucher = \App\Models\PaymentVoucher::findOrFail($id);

        // Decode JSON arrays
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Build detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float) ($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party setup — dynamic based on type
        $party = null;
        $previousBalance = 0;

        // ✅ Account Head type (numeric)
        if (is_numeric($voucher->type)) {
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object) [
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;

            // ✅ Vendor
        } elseif ($voucher->type === 'vendor') {
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Customer
        } elseif ($voucher->type === 'customer') {
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;

            // ✅ Walking customer
        } elseif ($voucher->type === 'walkin') {
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.payment_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    public function partyList(Request $request)
    {
        $type = $request->type;
        $data = [];

        try {
            $balanceService = app(\App\Services\BalanceService::class);

            if ($type == 'vendor') {
                $apTitle = 'Accounts Payable';
                try {
                    $apAccount = Account::find($balanceService->getAccountsPayableId());
                    if ($apAccount) $apTitle = $apAccount->title;
                } catch (\Exception $e) {
                    // AP account not configured yet, use default title
                }

                $vendors = \Illuminate\Support\Facades\DB::table('vendors')->select('id', 'name as text', 'phone as mobile', 'address', 'opening_balance')->get();
                foreach ($vendors as $vendor) {
                    try {
                        $vendor->closing_balance = $balanceService->getVendorBalance($vendor->id);
                    } catch (\Exception $e) {
                        $vendor->closing_balance = $vendor->opening_balance ?? 0;
                    }
                    $vendor->text = $vendor->text . ' | ' . $apTitle;
                    $data[] = $vendor;
                }
            } elseif ($type == 'customer') {
                $arTitle = 'Accounts Receivable';
                try {
                    $arAccount = Account::find($balanceService->getAccountsReceivableId());
                    if ($arAccount) $arTitle = $arAccount->title;
                } catch (\Exception $e) {
                    // AR account not configured yet, use default title
                }

                $customers = \App\Models\Customer::where(function ($query) {
                        $query->where('customer_type', '!=', 'Walking Customer')
                              ->orWhereNull('customer_type');
                    })
                    ->get(['id', 'customer_name', 'mobile', 'address', 'status', 'opening_balance']);

                foreach ($customers as $customer) {
                    try {
                        $customer->closing_balance = $balanceService->getCustomerBalance($customer->id);
                    } catch (\Exception $e) {
                        $customer->closing_balance = $customer->opening_balance ?? 0;
                    }

                    // Format for Frontend
                    $customer->text = $customer->customer_name . ' | ' . $arTitle;
                    $customer->remarks = $customer->status;
                    $data[] = $customer;
                }
            } elseif ($type == 'walkin') {
                $arTitle = 'Accounts Receivable';
                try {
                    $arAccount = Account::find($balanceService->getAccountsReceivableId());
                    if ($arAccount) $arTitle = $arAccount->title;
                } catch (\Exception $e) {
                    // AR account not configured yet, use default title
                }

                $customers = \App\Models\Customer::where('customer_type', 'Walking Customer')
                    ->get(['id', 'customer_name', 'mobile', 'address', 'status', 'opening_balance']);

                foreach ($customers as $customer) {
                    try {
                        $customer->closing_balance = $balanceService->getCustomerBalance($customer->id);
                    } catch (\Exception $e) {
                        $customer->closing_balance = $customer->opening_balance ?? 0;
                    }

                    // Format for Frontend
                    $customer->text = $customer->customer_name . ' | ' . $arTitle;
                    $customer->remarks = $customer->status;
                    $data[] = $customer;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Party List Error: '.$e->getMessage());

            return response()->json([]); // Return empty on error to avoid breaking JS
        }

        return response()->json($data);
    }

    public function expense_vochers()
    {
        $branchId = $this->getBranchId();
        $narrations = \App\Models\Narration::where('expense_head', 'Expense voucher')
            ->pluck('narration', 'id');
        $AccountHeads = AccountHead::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();

        // Last RVID nikalna
        $lastVoucher = \App\Models\ExpenseVoucher::latest('id')->first();

        // Next ID generate karna
        $nextId = $lastVoucher ? $lastVoucher->id + 1 : 1;
        $nextRvid = 'EVID-'.str_pad($nextId, 3, '0', STR_PAD_LEFT);

        return view('admin_panel.vochers.expense_vochers.expense_vouchers', compact('narrations', 'AccountHeads', 'nextRvid'));
    }

    public function store_expense_vochers(Request $request)
    {
        DB::beginTransaction();
        try {
            $evid = ExpenseVoucher::generateInvoiceNo();
            $narrationIds = [];

            foreach ($request->narration_id as $index => $narrId) {
                $manualText = $request->narration_text[$index] ?? null;
                $manualType = $request->narration_type_text[$index] ?? 'Manual';

                if (empty($narrId) && ! empty($manualText)) {
                    // Auto expense_head set based on voucher type
                    $expenseHead = 'Expense voucher';
                    if (stripos($manualType, 'Receipt') !== false || $request->voucher_type == 'receipt') {
                        $expenseHead = 'Expense voucher';
                    }

                    $new = \App\Models\Narration::create([
                        'expense_head' => $expenseHead,
                        'narration' => $manualText,
                    ]);

                    $narrationIds[] = (string) $new->id; // store as string → ["7"]
                } else {
                    $narrationIds[] = (string) $narrId; // force string format
                }
            }
            $voucherData = [
                'evid' => $evid,
                'entry_date' => $request->entry_date,
                'type' => $request->vendor_type,
                'party_id' => $request->vendor_id,
                'tel' => $request->tel,
                'remarks' => $request->remarks,
                'reference_no' => json_encode([$request->ref_no_header]),
                'narration_id' => json_encode($narrationIds),
                'row_account_head' => json_encode($request->row_account_head),
                'row_account_id' => json_encode($request->row_account_id),
                'amount' => json_encode($request->amount),
                'total_amount' => $request->total_amount,
            ];

            // Create legacy ExpenseVoucher record (powers the list view)
            $savedExpense = ExpenseVoucher::create($voucherData);

            // Resolve V2 credit account & party
            $creditAccountId = null;
            $partyType = null;
            $partyId = $request->vendor_id;

            if ($request->vendor_type === 'vendor') {
                $balanceService = app(\App\Services\BalanceService::class);
                $creditAccountId = $balanceService->getAccountsPayableId();
                $partyType = \App\Models\Vendor::class;
            } elseif ($request->vendor_type === 'customer') {
                $balanceService = app(\App\Services\BalanceService::class);
                $creditAccountId = $balanceService->getAccountsReceivableId();
                $partyType = \App\Models\Customer::class;
            } else {
                $creditAccountId = (int) $request->vendor_id;
                $partyType = \App\Models\Account::class;
            }

            // Also create a VoucherMaster record (V2 system)
            $branchId = $this->getBranchId();
            $voucherMaster = \App\Models\VoucherMaster::create([
                'voucher_type' => 'expense',
                'voucher_no'   => $evid,
                'date'         => $request->entry_date ?? now()->toDateString(),
                'status'       => 'posted',
                'remarks'      => $request->remarks,
                'total_amount' => $request->total_amount ?? 0,
                'branch_id'    => $branchId,
                'created_by'   => auth()->id(),
                'party_type'   => $partyType,
                'party_id'     => $partyId,
                'posted_at'    => now(),
            ]);

            // Save V2 Details - Credit Side (Source of funds)
            if ($creditAccountId) {
                \App\Models\VoucherDetail::create([
                    'voucher_master_id' => $voucherMaster->id,
                    'account_id'        => $creditAccountId,
                    'debit'             => 0,
                    'credit'            => $request->total_amount ?? 0,
                    'narration'         => $request->remarks ?? 'Expense Credit Source',
                ]);
            }

            // Save V2 Details - Debit Side (Destination Expense accounts)
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float) $request->amount[$index] : 0;
                    if ($rowAmount > 0 && $accId) {
                        \App\Models\VoucherDetail::create([
                            'voucher_master_id' => $voucherMaster->id,
                            'account_id'        => $accId,
                            'debit'             => $rowAmount,
                            'credit'            => 0,
                            'narration'         => $request->remarks ?? 'Expense Row Detail',
                        ]);
                    }
                }
            }

            $amount = (float) $request->total_amount;

            /**
             * STEP 1: Expense Accounts (row side) → PLUS
             */
            if ($request->row_account_id && $request->amount) {
                foreach ($request->row_account_id as $index => $accId) {
                    $rowAmount = isset($request->amount[$index]) ? (float) $request->amount[$index] : 0;

                    if ($rowAmount > 0) {
                        $rowAccount = Account::find($accId);
                        if ($rowAccount) {
                            $rowAccount->opening_balance = $rowAccount->opening_balance + $rowAmount; // PLUS
                            $rowAccount->save();
                        }
                    }
                }
            }

            /**
             * STEP 2: Party side → MINUS
             */
            if ($request->vendor_type === 'vendor') {
                $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    VendorLedger::create([
                        'vendor_id' => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'date' => now(),
                        'description' => "Expense Voucher #$evid",
                        'opening_balance' => 0,
                        'debit' => 0,
                        'credit' => $amount,
                        'previous_balance' => 0,
                        'closing_balance' => -$amount,
                    ]);
                }
            } elseif ($request->vendor_type === 'customer') {
                $ledger = CustomerLedger::where('customer_id', $request->vendor_id)->latest()->first();
                if ($ledger) {
                    $ledger->previous_balance = $ledger->closing_balance;
                    $ledger->closing_balance = $ledger->closing_balance - $amount; // MINUS
                    $ledger->save();
                } else {
                    CustomerLedger::create([
                        'customer_id' => $request->vendor_id,
                        'admin_or_user_id' => auth()->id(),
                        'previous_balance' => 0,
                        'opening_balance' => 0,
                        'closing_balance' => -$amount,
                    ]);
                }
            } else {
                // yahan vendor_type numeric (1,2,3) hai → matlab Account ID
                $account = Account::find($request->vendor_id);
                if ($account) {
                    $account->opening_balance = $account->opening_balance - $amount; // MINUS
                    $account->save();
                }
            }

            DB::commit();

            return redirect()->route('all_expense_vochers')->with('success', 'Expense Voucher saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', $e->getMessage());
        }
    }

    public function all_expense_vochers()
    {
        $branchId = $this->getBranchId();

        // ── Legacy ExpenseVoucher records ─────────────────────────────────────
        $legacyRecords = \App\Models\ExpenseVoucher::with('voucherMaster.createdBy')->orderBy('id', 'DESC')->get();

        foreach ($legacyRecords as $voucher) {
            $partyName = '-';
            $typeLabel = '-';

            if (is_numeric($voucher->type)) {
                $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
                $account     = DB::table('accounts')->where('id', $voucher->party_id)->first();
                $typeLabel   = $accountHead->name ?? 'Account';
                $partyName   = $account->title ?? '-';
            } elseif ($voucher->type === 'vendor') {
                $vendor    = DB::table('vendors')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Vendor';
                $partyName = $vendor->name ?? '-';
            } elseif ($voucher->type === 'customer') {
                $customer  = DB::table('customers')->where('id', $voucher->party_id)->first();
                $typeLabel = 'Customer';
                $partyName = $customer->customer_name ?? '-';
            } elseif ($voucher->type === 'walkin') {
                $walkin    = DB::table('customers')->where('id', $voucher->party_id)
                                ->where('customer_type', 'Walking Customer')->first();
                $typeLabel = 'Walk-in';
                $partyName = $walkin->customer_name ?? '-';
            }

            $voucher->type_label  = $typeLabel;
            $voucher->party_name  = $partyName;

            // Fetch line account heads
            $lineHeads = json_decode($voucher->row_account_head, true);
            $headNames = [];
            if (is_array($lineHeads)) {
                $lineHeads = array_filter($lineHeads);
                if (!empty($lineHeads)) {
                    $headNames = DB::table('account_heads')
                        ->whereIn('id', $lineHeads)
                        ->pluck('name')
                        ->toArray();
                }
            }
            $voucher->line_account_heads = !empty($headNames) ? implode(', ', array_unique($headNames)) : '-';

            // Normalize fields for blade
            $voucher->evid        = $voucher->evid;
            $voucher->entry_date  = $voucher->entry_date;
            $voucher->total_amount= $voucher->total_amount;
            $voucher->_source     = 'legacy';
        }

        $receipts = $legacyRecords;
        $allHeads = \App\Models\AccountHead::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();

        return view('admin_panel.vochers.expense_vochers.all_expense_vochers', compact('receipts', 'allHeads'));
    }

    public function expenseprint($id)
    {
        // 1. Try V2 VoucherMaster First
        $voucherV2 = \App\Models\VoucherMaster::where('voucher_type', 'expense')->find($id);

        if ($voucherV2) {
            $voucherV2->load(['details.account', 'party', 'createdBy']);

            // Adapter for V2 to V1 View
            $voucher = (object) [
                'evid' => $voucherV2->voucher_no,
                'receipt_date' => $voucherV2->date->format('Y-m-d'),
                'total_amount' => $voucherV2->amount,
                'remarks' => $voucherV2->remarks,
                'type' => 'unknown',
                'created_at' => $voucherV2->created_at,
            ];

            if (! isset($voucher->total_amount)) {
                $voucher->total_amount = $voucherV2->total_amount;
            }

            $rows = [];
            foreach ($voucherV2->details as $detail) {
                $ref = '-';
                if (preg_match('/#([A-Za-z0-9\-_]+)/', $detail->narration, $matches)) {
                    $ref = $matches[1];
                }

                $rows[] = [
                    'narration' => $detail->narration,
                    'reference' => $ref,
                    'account_head' => $detail->account->accountHead->name ?? '-',
                    'account_name' => $detail->account->title ?? '-',
                    'account_code' => $detail->account->account_code ?? '-',
                    'amount' => $detail->debit > 0 ? $detail->debit : $detail->credit,
                    'debit' => $detail->debit,
                    'credit' => $detail->credit,
                ];
            }

            // Party Logic
            $party = $voucherV2->party;
            $previousBalance = 0;

            if ($party) {
                if ($party instanceof \App\Models\Customer) {
                    $voucher->type = ($party->customer_type == 'Walking Customer') ? 'walkin' : 'customer';
                    $party->name = $party->customer_name;
                    $party->address = $party->address ?? '-';
                    $party->mobile = $party->mobile ?? '-';

                    $previousBalance = \App\Models\CustomerLedger::where('customer_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Vendor) {
                    $voucher->type = 'vendor';
                    $party->address = $party->address ?? '-';
                    $party->phone = $party->phone ?? '-';

                    $previousBalance = \App\Models\VendorLedger::where('vendor_id', $party->id)
                        ->where('created_at', '<', $voucherV2->created_at)
                        ->orderBy('id', 'desc')
                        ->value('closing_balance') ?? ($party->opening_balance ?? 0);

                } elseif ($party instanceof \App\Models\Account) {
                    $voucher->type = '1';
                    $party->name = $party->title;
                    $party->phone = $party->account_code;
                    $party->head_name = $party->accountHead->name ?? 'Account';
                    $previousBalance = $party->opening_balance;
                }
            }

            return view('admin_panel.vochers.expense_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
        }

        $voucher = \App\Models\ExpenseVoucher::findOrFail($id);

        // Decode JSON arrays safely
        $narrations = json_decode($voucher->narration_id, true) ?? [];
        $references = json_decode($voucher->reference_no, true) ?? [];
        $accountHeads = json_decode($voucher->row_account_head, true) ?? [];
        $accounts = json_decode($voucher->row_account_id, true) ?? [];
        $amounts = json_decode($voucher->amount, true) ?? [];

        // 🧾 Prepare detailed rows
        $rows = [];
        foreach ($narrations as $index => $narrId) {
            $narration = DB::table('narrations')->where('id', $narrId)->value('narration');
            $ref = $references[$index] ?? null;
            $accountHead = DB::table('account_heads')->where('id', $accountHeads[$index] ?? null)->value('name');
            $account = DB::table('accounts')->where('id', $accounts[$index] ?? null)->first();
            $amount = (float) ($amounts[$index] ?? 0);

            $rows[] = [
                'narration' => $narration,
                'reference' => $ref,
                'account_head' => $accountHead,
                'account_name' => $account->title ?? null,
                'account_code' => $account->account_code ?? null,
                'amount' => $amount,
            ];
        }

        // 🧩 Party Setup Based on Type
        $party = null;
        $previousBalance = 0;

        if (is_numeric($voucher->type)) {
            // ✅ Account Head type (numeric)
            $accountHead = DB::table('account_heads')->where('id', $voucher->type)->first();
            $account = DB::table('accounts')->where('id', $voucher->party_id)->first();

            if ($account) {
                $party = (object) [
                    'name' => $account->title ?? '—',
                    'address' => '—',
                    'phone' => $account->account_code ?? '—',
                    'head_name' => $accountHead->name ?? '—',
                ];
            }

            $previousBalance = $account->opening_balance ?? 0;
        } elseif ($voucher->type === 'vendor') {
            // ✅ Vendor Type
            $party = DB::table('vendors')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('vendor_ledgers')
                ->where('vendor_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'customer') {
            // ✅ Customer Type
            $party = DB::table('customers')->where('id', $voucher->party_id)->first();
            $previousBalance = DB::table('customer_ledgers')
                ->where('customer_id', $voucher->party_id)
                ->orderByDesc('id')
                ->value('closing_balance') ?? 0;
        } elseif ($voucher->type === 'walkin') {
            // ✅ Walking Customer
            $party = DB::table('customers')
                ->where('id', $voucher->party_id)
                ->where('customer_type', 'Walking Customer')
                ->first();
        }

        return view('admin_panel.vochers.expense_vochers.print', compact('voucher', 'rows', 'party', 'previousBalance'));
    }

    public function journal_voucher(Request $request)
    {
        return redirect()->route('vouchers.create_page', ['type' => 'jv']);
    }

    public function store_journal_voucher(Request $request)
    {
        $request->validate([
            'voucher_date'     => 'required|date',
            'rows.*.account_id'=> 'required|exists:accounts,id',
            'rows.*.debit'     => 'required|numeric|min:0',
            'rows.*.credit'    => 'required|numeric|min:0',
        ]);

        // Ensure debits == credits
        $totalDebit  = collect($request->rows)->sum('debit');
        $totalCredit = collect($request->rows)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()->withInput()->with('error', 'Journal entry is not balanced. Total Debit must equal Total Credit.');
        }

        DB::beginTransaction();
        try {
            $lines = [];
            foreach ($request->rows as $row) {
                $lines[] = [
                    'account_id' => $row['account_id'],
                    'debit'      => (float) $row['debit'],
                    'credit'     => (float) $row['credit'],
                    'narration'  => $row['narration'] ?? 'Journal Entry',
                ];
            }

            app(\App\Services\VoucherService::class)->createVoucher([
                'voucher_type' => 'journal',
                'date'         => $request->voucher_date,
                'status'       => 'posted',
                'remarks'      => $request->remarks,
            ], $lines, auth()->id());

            DB::commit();

            return redirect()->route('journal.voucher')->with('success', 'Journal Voucher saved and posted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function fetchReceiptVouchers(Request $request)
    {

        // Fetch all accounts for the dropdown
        $accounts = \Illuminate\Support\Facades\DB::table('accounts')
            ->select('id', 'title', 'head_id')
            ->orderBy('title')
            ->get()
            ->map(function ($account) {
                // Get account head name
                $headName = \Illuminate\Support\Facades\DB::table('account_heads')
                    ->where('id', $account->head_id)
                    ->value('name');

                return [
                    'id' => $account->id,
                    'title' => $account->title,
                    'head_name' => $headName,
                    'display_name' => ($headName ? $headName.' - ' : '').$account->title,
                ];
            });

        return response()->json([
            'accounts' => $accounts,
        ]);
    }

    // =========================================================================
    // UNIFIED ERP VOUCHERS: CRV, BRV, CPV, BPV, JV
    // =========================================================================

    public function voucherList($type = 'all')
    {
        $branchId = $this->getBranchId();
        $query = \App\Models\VoucherMaster::with(['details.account', 'party', 'createdBy', 'modifiedBy'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('id');

        if ($type !== 'all') {
            $mappedTypes = match(strtolower($type)) {
                'crv' => ['crv', 'receipt'],
                'brv' => ['brv'],
                'cpv' => ['cpv', 'payment', 'expense'],
                'bpv' => ['bpv'],
                'jv'  => ['jv', 'journal'],
                default => [$type],
            };
            $query->whereIn('voucher_type', $mappedTypes);
        }

        $vouchers = $query->get();
        $currentType = strtolower($type);

        return view('admin_panel.vochers.unified_index', compact('vouchers', 'currentType'));
    }

    public function createVoucherPage($type = 'crv')
    {
        $branchId = $this->getBranchId();
        $type = strtolower($type);

        // Fetch Cash Accounts (1-02-040)
        $cashAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '1-02-040%')
                  ->orWhere('title', 'like', '%Cash%');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Fetch Bank Accounts (1-02-052)
        $bankAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '1-02-052%')
                  ->orWhere('title', 'like', '%Bank%');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Fetch Expense Accounts (5-01, 5-02)
        $expenseAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '5-%')
                  ->orWhereHas('head', fn($h) => $h->where('type', 'Expense')->orWhere('name', 'like', '%Expense%'));
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Fetch Customers & Vendors (Active Only)
        $customers = Customer::where(function($q) {
                $q->whereNull('status')->orWhere('status', 1)->orWhere('status', 'active');
            })
            ->where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('customer_name')
            ->get();

        $vendors = Vendor::where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        // Fetch All Accounts (Active Only)
        $allAccounts = Account::with('head')
            ->where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('title')
            ->get();

        $isSuperAdmin = auth()->check() && (auth()->user()->usertype === 'super_admin' || auth()->user()->hasRole('Super Admin') || (method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin()));
        $branches = $isSuperAdmin ? \App\Models\Branch::all() : [];
        $userBranch = auth()->user()?->branch ?? \App\Models\Branch::find($branchId ?? 1);

        return view('admin_panel.vochers.unified_create', compact(
            'type', 'cashAccounts', 'bankAccounts', 'expenseAccounts', 'customers', 'vendors', 'allAccounts',
            'branches', 'isSuperAdmin', 'userBranch'
        ));
    }

    public function storeVoucherAction(Request $request, $type)
    {
        $selectedBranchId = $request->branch_id ?? $this->getBranchId() ?? 1;
        $branchId = (int)$selectedBranchId;
        $branch = \App\Models\Branch::find($branchId);
        $locationName = $branch ? $branch->name : ($request->location ?? 'HEAD OFFICE');
        $type = strtolower($type);
        $voucherService = app(\App\Services\VoucherService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        $firstAccountId = Account::first()?->id ?? 1;
        $arAccountId = $balanceService->getAccountsReceivableId($branchId) ?: $firstAccountId;
        $apAccountId = $balanceService->getAccountsPayableId($branchId) ?: $firstAccountId;

        $headerData = [
            'voucher_type' => $type,
            'date'         => $request->date ?? date('Y-m-d'),
            'location'     => $locationName,
            'cheque_no'    => $request->cheque_no ?? null,
            'cheque_date'  => $request->cheque_date ?? null,
            'remarks'      => $request->remarks,
            'branch_id'    => $branchId,
            'status'       => 'posted',
        ];

        $lines = [];

        if ($type === 'crv') {
            $amount = (float)$request->amount;
            $partyType = $request->party_type ?? 'customer';
            $targetStr = $request->party_id ?? $request->customer_id ?? '';

            if ($partyType === 'vendor' || str_starts_with((string)$targetStr, 'vendor_')) {
                $vId = (int)str_replace('vendor_', '', (string)$targetStr);
                $headerData['party_type'] = Vendor::class;
                $headerData['party_id']   = $vId;
                $creditAccountId          = $apAccountId;
            } else {
                $cId = (int)str_replace('customer_', '', (string)$targetStr);
                $headerData['party_type'] = Customer::class;
                $headerData['party_id']   = $cId;
                $creditAccountId          = $arAccountId;
            }

            $lines[] = [
                'account_id' => $request->cash_account_id,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
            $lines[] = [
                'account_id' => $creditAccountId,
                'party_type' => $headerData['party_type'],
                'party_id'   => $headerData['party_id'],
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
        } elseif ($type === 'brv') {
            $amount = (float)$request->amount;
            $partyType = $request->party_type ?? 'customer';
            $targetStr = $request->party_id ?? $request->customer_id ?? '';

            if ($partyType === 'vendor' || str_starts_with((string)$targetStr, 'vendor_')) {
                $vId = (int)str_replace('vendor_', '', (string)$targetStr);
                $headerData['party_type'] = Vendor::class;
                $headerData['party_id']   = $vId;
                $creditAccountId          = $apAccountId;
            } else {
                $cId = (int)str_replace('customer_', '', (string)$targetStr);
                $headerData['party_type'] = Customer::class;
                $headerData['party_id']   = $cId;
                $creditAccountId          = $arAccountId;
            }

            $lines[] = [
                'account_id' => $request->bank_account_id,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
            $lines[] = [
                'account_id' => $creditAccountId,
                'party_type' => $headerData['party_type'],
                'party_id'   => $headerData['party_id'],
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
        } elseif ($type === 'cpv') {
            $amount = (float)$request->amount;
            $cashAccount = Account::find($request->cash_account_id);
            if ($cashAccount && (float)$cashAccount->calculated_balance < $amount) {
                $avail = number_format((float)$cashAccount->calculated_balance, 2);
                $reqAmt = number_format($amount, 2);
                return back()->withInput()->with('error', "Insufficient Cash Balance! '{$cashAccount->title}' has available balance of Rs. {$avail}, but requested payment is Rs. {$reqAmt}. Payment cannot exceed available balance.");
            }

            $targetStr = $request->target_id;
            
            if (str_starts_with($targetStr, 'vendor_')) {
                $vId = (int)str_replace('vendor_', '', $targetStr);
                $headerData['party_type'] = Vendor::class;
                $headerData['party_id']   = $vId;
                $targetAccountId = $apAccountId;
            } elseif (str_starts_with($targetStr, 'customer_')) {
                $cId = (int)str_replace('customer_', '', $targetStr);
                $headerData['party_type'] = Customer::class;
                $headerData['party_id']   = $cId;
                $targetAccountId = $arAccountId;
            } else {
                $targetAccountId = (int)str_replace('account_', '', $targetStr);
            }

            $lines[] = [
                'account_id' => $targetAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
            $lines[] = [
                'account_id' => $request->cash_account_id,
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
        } elseif ($type === 'bpv') {
            $amount = (float)$request->amount;
            $bankAccount = Account::find($request->bank_account_id);
            if ($bankAccount && (float)$bankAccount->calculated_balance < $amount) {
                $avail = number_format((float)$bankAccount->calculated_balance, 2);
                $reqAmt = number_format($amount, 2);
                return back()->withInput()->with('error', "Insufficient Bank Balance! '{$bankAccount->title}' has available balance of Rs. {$avail}, but requested payment is Rs. {$reqAmt}. Payment cannot exceed available balance.");
            }

            $targetStr = $request->target_id;
            
            if (str_starts_with($targetStr, 'vendor_')) {
                $vId = (int)str_replace('vendor_', '', $targetStr);
                $headerData['party_type'] = Vendor::class;
                $headerData['party_id']   = $vId;
                $targetAccountId = $apAccountId;
            } elseif (str_starts_with($targetStr, 'customer_')) {
                $cId = (int)str_replace('customer_', '', $targetStr);
                $headerData['party_type'] = Customer::class;
                $headerData['party_id']   = $cId;
                $targetAccountId = $arAccountId;
            } else {
                $targetAccountId = (int)str_replace('account_', '', $targetStr);
            }

            $lines[] = [
                'account_id' => $targetAccountId,
                'debit'      => $amount,
                'credit'     => 0,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
            $lines[] = [
                'account_id' => $request->bank_account_id,
                'debit'      => 0,
                'credit'     => $amount,
                'narration'  => $request->line_narration ?? $request->remarks,
            ];
        } elseif ($type === 'jv') {
            foreach ($request->rows as $r) {
                $itemType = $r['item_type'] ?? 'account';
                $targetId = $r['account_id'] ?? $r['target_id'] ?? '';
                $partyType = null;
                $partyId = null;
                $lineAccountId = null;

                $debit = (float)($r['debit'] ?? 0);
                $credit = (float)($r['credit'] ?? 0);

                if ($itemType === 'customer' || str_starts_with($targetId, 'customer_')) {
                    $cId = (int)str_replace('customer_', '', $targetId);
                    $partyType = Customer::class;
                    $partyId = $cId;
                    $lineAccountId = $arAccountId;
                } elseif ($itemType === 'vendor' || str_starts_with($targetId, 'vendor_')) {
                    $vId = (int)str_replace('vendor_', '', $targetId);
                    $partyType = Vendor::class;
                    $partyId = $vId;
                    $lineAccountId = $apAccountId;
                } else {
                    $accId = (int)str_replace('account_', '', $targetId);
                    $lineAccountId = $accId;

                    // Restriction Check: If Cash or Bank account or Debit asset is Credited (deducted/paid) in JV
                    if ($credit > 0 && $accId) {
                        $deductAcc = Account::find($accId);
                        if ($deductAcc && $deductAcc->type === 'Debit' && (float)$deductAcc->calculated_balance < $credit) {
                            $avail = number_format((float)$deductAcc->calculated_balance, 2);
                            $reqAmt = number_format($credit, 2);
                            return back()->withInput()->with('error', "Insufficient Balance in '{$deductAcc->title}'! Available balance is Rs. {$avail}, but requested deduction is Rs. {$reqAmt}. Cannot withdraw more than available balance.");
                        }
                    }
                }

                if ($lineAccountId || $partyId) {
                    $lines[] = [
                        'account_id' => $lineAccountId ?: ($arAccountId ?? 1),
                        'party_type' => $partyType,
                        'party_id'   => $partyId,
                        'debit'      => $debit,
                        'credit'     => $credit,
                        'narration'  => $r['narration'] ?? $request->remarks,
                    ];
                }
            }
        }

        $voucher = $voucherService->createVoucher($headerData, $lines, auth()->id());

        return redirect()->route('vouchers.list', ['type' => $type])
            ->with('success', strtoupper($type) . " voucher ({$voucher->voucher_no}) created and posted successfully!");
    }

    public function editVoucherPage($id)
    {
        $branchId = $this->getBranchId();
        $voucher = \App\Models\VoucherMaster::with(['details.account', 'details.party', 'party'])->findOrFail($id);

        $cashAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '1-02-040%')
                  ->orWhere('title', 'like', '%Cash%');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $bankAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '1-02-052%')
                  ->orWhere('title', 'like', '%Bank%');
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $expenseAccounts = Account::where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->where(function($q) {
                $q->where('account_code', 'like', '5-%')
                  ->orWhereHas('head', fn($h) => $h->where('type', 'Expense')->orWhere('name', 'like', '%Expense%'));
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $customers = Customer::where(function($q) {
                $q->whereNull('status')->orWhere('status', 1)->orWhere('status', 'active');
            })
            ->where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('customer_name')
            ->get();

        $vendors = Vendor::where(function($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get();

        $allAccounts = Account::with('head')
            ->where('status', 1)
            ->where(fn($q) => $q->whereNull('is_active')->orWhere('is_active', 1))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('title')
            ->get();

        $isSuperAdmin = auth()->check() && (auth()->user()->usertype === 'super_admin' || auth()->user()->hasRole('Super Admin') || (method_exists(auth()->user(), 'isSuperAdmin') && auth()->user()->isSuperAdmin()));
        $branches = $isSuperAdmin ? \App\Models\Branch::all() : [];
        $userBranch = $voucher->branch ?? auth()->user()?->branch ?? \App\Models\Branch::find($branchId ?? 1);

        return view('admin_panel.vochers.unified_edit', compact(
            'voucher', 'allAccounts', 'cashAccounts', 'bankAccounts', 'expenseAccounts', 'customers', 'vendors',
            'branches', 'isSuperAdmin', 'userBranch'
        ));
    }

    public function updateVoucherAction(Request $request, $id)
    {
        $voucher = \App\Models\VoucherMaster::findOrFail($id);
        $voucherService = app(\App\Services\VoucherService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        $selectedBranchId = $request->branch_id ?? $voucher->branch_id ?? $this->getBranchId() ?? 1;
        $branch = \App\Models\Branch::find($selectedBranchId);
        $locationName = $branch ? $branch->name : ($request->location ?? $voucher->location ?? 'HEAD OFFICE');

        $firstAccountId = Account::first()?->id ?? 1;
        $arAccountId = $balanceService->getAccountsReceivableId((int)$selectedBranchId) ?: $firstAccountId;
        $apAccountId = $balanceService->getAccountsPayableId((int)$selectedBranchId) ?: $firstAccountId;

        $headerData = [
            'voucher_type' => $request->voucher_type ?? $voucher->voucher_type,
            'date'         => $request->date,
            'location'     => $locationName,
            'branch_id'    => $selectedBranchId,
            'cheque_no'    => $request->cheque_no ?? null,
            'cheque_date'  => $request->cheque_date ?? null,
            'remarks'      => $request->remarks,
            'status'       => 'posted',
        ];

        $lines = [];
        foreach ($request->rows as $r) {
            $itemType = $r['item_type'] ?? 'account';
            $targetId = $r['account_id'] ?? $r['target_id'] ?? '';
            $partyType = null;
            $partyId = null;
            $lineAccountId = null;

            if ($itemType === 'customer' || str_starts_with($targetId, 'customer_')) {
                $cId = (int)str_replace('customer_', '', $targetId);
                $partyType = Customer::class;
                $partyId = $cId;
                $lineAccountId = $arAccountId;
            } elseif ($itemType === 'vendor' || str_starts_with($targetId, 'vendor_')) {
                $vId = (int)str_replace('vendor_', '', $targetId);
                $partyType = Vendor::class;
                $partyId = $vId;
                $lineAccountId = $apAccountId;
            } else {
                $accId = (int)str_replace('account_', '', $targetId);
                $lineAccountId = $accId;
            }

            $debit = (float)($r['debit'] ?? 0);
            $credit = (float)($r['credit'] ?? 0);

            if ($lineAccountId || $partyId) {
                if ($lineAccountId && $credit > 0) {
                    $deductAcc = Account::find($lineAccountId);
                    if ($deductAcc && $deductAcc->type === 'Debit') {
                        $existingLineCredit = $voucher->details->where('account_id', $lineAccountId)->sum('credit');
                        $effectiveAvail = (float)$deductAcc->calculated_balance + $existingLineCredit;
                        if ($credit > $effectiveAvail) {
                            $avail = number_format($effectiveAvail, 2);
                            $reqAmt = number_format($credit, 2);
                            return back()->withInput()->with('error', "Insufficient Balance in '{$deductAcc->title}'! Available balance is Rs. {$avail}, but requested deduction is Rs. {$reqAmt}.");
                        }
                    }
                }

                $lines[] = [
                    'account_id' => $lineAccountId ?: ($arAccountId ?? 1),
                    'party_type' => $partyType,
                    'party_id'   => $partyId,
                    'debit'      => $debit,
                    'credit'     => $credit,
                    'narration'  => $r['narration'] ?? $request->remarks,
                ];
            }
        }

        $voucherService->updateVoucher($voucher, $headerData, $lines, auth()->id());

        return redirect()->route('vouchers.list', ['type' => $voucher->voucher_type])
            ->with('success', "Voucher {$voucher->voucher_no} updated and re-posted successfully with full ledger synchronization!");
    }

    public function destroyVoucherAction($id)
    {
        $voucher = \App\Models\VoucherMaster::findOrFail($id);
        $type = $voucher->voucher_type;
        $vNo = $voucher->voucher_no;

        $voucherService = app(\App\Services\VoucherService::class);
        $voucherService->deleteVoucher($voucher);

        return redirect()->route('vouchers.list', ['type' => $type])
            ->with('success', "Voucher {$vNo} deleted and all accounting entries cleanly reversed!");
    }

    public function unifiedPrint($id)
    {
        $voucher = \App\Models\VoucherMaster::with(['details.account', 'details.party', 'party', 'createdBy', 'modifiedBy', 'verifiedBy', 'branch'])
            ->findOrFail($id);

        $locationName = $voucher->location ?? ($voucher->branch->name ?? 'HEAD OFFICE');
        $vType = strtolower($voucher->voucher_type ?? 'jv');

        $rows = [];
        foreach ($voucher->details as $d) {
            $acc = $d->account;
            $code = $acc->account_code ?? '-';
            $title = $acc->title ?? '-';

            // 1. Check if this detail line has an explicit line-level party
            $lineParty = $d->party;

            // 2. If line has no direct party, determine if it should inherit from the header party
            if (!$lineParty && $voucher->party) {
                // In CRV / BRV / Receipt: Credit side is the Customer/Party paying
                if (in_array($vType, ['crv', 'brv', 'receipt']) && (float)$d->credit > 0) {
                    $lineParty = $voucher->party;
                }
                // In CPV / BPV / Payment / Expense: Debit side is the Vendor/Party receiving payment
                elseif (in_array($vType, ['cpv', 'bpv', 'payment', 'expense']) && (float)$d->debit > 0) {
                    $lineParty = $voucher->party;
                }
            }

            if ($lineParty) {
                if ($lineParty instanceof Customer) {
                    $code = '1-02-051-' . str_pad($lineParty->id, 5, '0', STR_PAD_LEFT);
                    $title = $lineParty->customer_name;
                } elseif ($lineParty instanceof Vendor) {
                    $code = '2-02-010-' . str_pad($lineParty->id, 5, '0', STR_PAD_LEFT);
                    $title = $lineParty->name;
                }
            } else {
                // Standard COA formatting for cash/bank accounts
                if ($code === 'cash-001-00001') {
                    $code = '1-02-040-00001';
                } elseif ($code === 'bank-001-00001') {
                    $code = '1-02-052-00001';
                }
            }

            // Append Location to Title for official look
            if (!empty($locationName) && !str_contains($title, "({$locationName})")) {
                $title .= " ({$locationName})";
            }

            $rows[] = [
                'account_code' => $code,
                'account_name' => $title,
                'narration'    => $d->narration ?? $voucher->remarks ?? '-',
                'debit'        => (float)$d->debit,
                'credit'       => (float)$d->credit,
            ];
        }

        return view('admin_panel.vochers.unified_print', compact('voucher', 'rows'));
    }
}
