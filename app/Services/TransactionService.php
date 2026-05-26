<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\VoucherMaster;

class TransactionService
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * Create a Receipt Voucher from a Posted Sale using V2 standard.
     */
    /**
     * Create a Receipt Voucher from a Posted Sale using V2 standard.
     * Supports split payments (multiple accounts).
     */
    public function createReceiptFromSale(Sale $sale, array $accountIds = [], array $amounts = [])
    {
        \Log::info("TransactionService V2: Called for Sale ID {$sale->id}");

        // 1. Validation
        // Allow both 'post' (used by SIN) and 'posted' (general posted status)
        if (!in_array($sale->sale_status, ['post', 'posted'])) {
            \Log::warning('TransactionService: Sale status is ' . $sale->sale_status . ', expected post or posted. Aborting.');

            return;
        }

        // Filter out empty or invalid entries
        $accountIds = array_filter($accountIds, function ($value) {
            return ! empty($value);
        });

        // Determine if we have valid input arrays, otherwise fallback to Sale Cash
        if (empty($accountIds)) {
            // Fallback: Check if Sale has a cash value (Legacy support or Hidden Input)
            $cash = $sale->cash ?? 0;
            if ($cash > 0) {
                \Log::info("TransactionService: Using fallback Cash amount: $cash");
                $balanceService = app(\App\Services\BalanceService::class);
                $accountIds = [$balanceService->getCashAccountId($sale->branch_id)];
                $amounts = [$cash];
            } else {
                \Log::info('TransactionService: No payment info provided (Credit Sale), aborting receipt creation.');

                return;
            }
        }

        try {
            $balanceService = app(\App\Services\BalanceService::class);
            $customerControlAccountId = $balanceService->getAccountsReceivableId($sale->branch_id);
            $totalPaid = 0;
            $lines = [];

            // 2. Prepare Debit Lines (Money In)
            foreach ($accountIds as $index => $accId) {
                $amount = (float) ($amounts[$index] ?? 0);

                if ($amount > 0) { // Fixed: Only positive amounts
                    $totalPaid += $amount;

                    $lines[] = [
                        'account_id' => $accId,
                        'debit' => $amount,
                        'credit' => 0,
                        'narration' => "Payment received from Invoice #{$sale->invoice_no}",
                    ];
                }
            }

            // Skip if no payment (Credit Sale - customer will pay later)
            if ($totalPaid <= 0) {
                \Log::info('TransactionService: No payment received (Credit Sale), skipping receipt voucher.');

                return;
            }

            // 3. Prepare Credit Line (Customer Control - Money Out / Receivable Reduced)
            $lines[] = [
                'account_id' => $customerControlAccountId,
                'debit' => 0,
                'credit' => $totalPaid,
                'narration' => "Payment for Invoice #{$sale->invoice_no}",
            ];

            // 4. Voucher Header
            $voucherData = [
                'voucher_type' => VoucherMaster::TYPE_RECEIPT,
                'date' => now()->format('Y-m-d'),
                'status' => VoucherMaster::STATUS_POSTED, // Auto-post
                'payment_from' => 'Customer',
                'party_type' => Customer::class,
                'party_id' => $sale->customer_id,
                'branch_id' => $sale->branch_id,
                'remarks' => "Auto-Receipt for Sale Invoice #{$sale->invoice_no}. Total: $totalPaid",
            ];

            // 5. Create via VoucherService
            $voucher = $this->voucherService->createVoucher($voucherData, $lines, auth()->id());

            // 6. SYNC TO LEGACY CUSTOMER LEDGER (Critical for "Customer Balance" view)
            if ($sale->customer_id) {
                // Fetch latest ledger to get current balance
                // Try-catch to ensure consistency
                $lastEntry = \App\Models\CustomerLedger::where('customer_id', $sale->customer_id)
                    ->lockForUpdate() // Lock to prevent race conditions
                    ->orderBy('id', 'desc')
                    ->first();

                $prevBal = $lastEntry ? $lastEntry->closing_balance : 0;
                // Receipt reduces balance (Credit Customer)
                $newBal = $prevBal - $totalPaid;

                \Log::info("Legacy Ledger (Receipt): Customer #{$sale->customer_id}. Prev (Expected 9440 range): {$prevBal} - Paid: {$totalPaid} = New: {$newBal}");

                \App\Models\CustomerLedger::create([
                    'customer_id' => $sale->customer_id,
                    'branch_id' => $sale->branch_id,
                    'admin_or_user_id' => auth()->id() ?? 1,
                    'description' => "Receipt #{$voucher->voucher_no} for Invoice #{$sale->invoice_no}",
                    'previous_balance' => $prevBal, // Before payment
                    'closing_balance' => $newBal,   // After payment
                    'opening_balance' => 0,
                    'source_type' => get_class($voucher),
                    'source_id' => $voucher->id,
                ]);

                // Create CustomerPayment to track for commission
                \App\Models\CustomerPayment::create([
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'admin_or_user_id' => auth()->id() ?? 1,
                    'amount' => $totalPaid,
                    'payment_method' => 'Cash/Bank',
                    'payment_date' => now()->format('Y-m-d'),
                    'note' => "Receipt #{$voucher->voucher_no} for Invoice #{$sale->invoice_no}",
                ]);

                // Update Master Customer Table
                $cust = \App\Models\Customer::find($sale->customer_id);
                if ($cust) {
                    $cust->previous_balance = $newBal;
                    $cust->save();
                }
            }

            \Log::info("TransactionService: V2 Receipt Created: {$voucher->voucher_no} for amount $totalPaid");

            // Clear notifications if payment made
            try {
                app(\App\Services\CreditNotificationService::class)->clearNotifications($sale);
            } catch (\Exception $e) {
                \Log::error('Clear Notification Error: ' . $e->getMessage());
            }

            return $voucher->voucher_no;

        } catch (\Exception $e) {
            \Log::error('TransactionService V2 Error: '.$e->getMessage());
        }
    }

    /**
     * Create a Payment Voucher for a Purchase.
     * Debit: Accounts Payable (Vendor) | Credit: Cash/Bank
     */
    public function createPaymentForPurchase(\App\Models\Purchase $purchase, array $accountIds = [], array $amounts = [])
    {
        \Log::info("TransactionService: Create Payment for Purchase #{$purchase->invoice_no}");

        // Filter valid inputs
        $accountIds = array_filter($accountIds, fn ($val) => ! empty($val));

        if (empty($accountIds)) {
            \Log::info('TransactionService: No payment accounts provided, skipping payment.');

            return;
        }

        try {
            $balanceService = app(\App\Services\BalanceService::class);
            $apAccountId = $balanceService->getAccountsPayableId(); // We need to ensure this method exists

            $totalPaid = 0;
            $lines = [];

            // 1. Prepare Credit Lines (Money Out - Cash/Bank)
            foreach ($accountIds as $index => $accId) {
                $amount = (float) ($amounts[$index] ?? 0);
                if ($amount > 0) {
                    $totalPaid += $amount;
                    $lines[] = [
                        'account_id' => $accId,
                        'debit' => 0,
                        'credit' => $amount, // Money leaving asset
                        'narration' => "Payment for Purchase #{$purchase->invoice_no}",
                    ];
                }
            }

            if ($totalPaid <= 0) {
                return;
            }

            // 2. Prepare Debit Line (Accounts Payable - Liability Decreases)
            $vendorName = '';
            if ($purchase->vendor) {
                $vendorName = $purchase->vendor->name;
            }

            $lines[] = [
                'account_id' => $apAccountId,
                'debit' => $totalPaid,
                'credit' => 0,
                'narration' => "Payment to Vendor {$vendorName}",
            ];

            // 3. Voucher Header
            $voucherData = [
                'voucher_type' => VoucherMaster::TYPE_PAYMENT,
                'date' => now()->format('Y-m-d'),
                'status' => VoucherMaster::STATUS_POSTED,
                'payment_from' => 'Vendor', // Or 'System'
                'party_type' => \App\Models\Vendor::class,
                'party_id' => $purchase->vendor_id,
                'remarks' => "Auto-Payment for Purchase #{$purchase->invoice_no}",
            ];

            // 4. Create Voucher
            $this->voucherService->createVoucher($voucherData, $lines, auth()->id());

            // 5. Update Legacy Vendor Ledger
            $lastLedger = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)->orderBy('id', 'desc')->first();
            $prevBal = $lastLedger ? $lastLedger->closing_balance : 0;

            \App\Models\VendorLedger::create([
                'vendor_id' => $purchase->vendor_id,
                'branch_id' => $purchase->branch_id,
                'admin_or_user_id' => auth()->id(),
                'debit' => $totalPaid,
                'credit' => 0,
                'previous_balance' => $prevBal,
                'opening_balance' => 0,
                'closing_balance' => $prevBal - $totalPaid,
                'description' => "Payment for Purchase #{$purchase->invoice_no}",
                'source_type' => \App\Models\Purchase::class,
                'source_id' => $purchase->id,
            ]);

            // Update Paid Amount in Purchase
            $purchase->paid_amount = $totalPaid;
            $purchase->due_amount = max(0, $purchase->net_amount - $purchase->paid_amount);
            $purchase->save();

            // Clear notifications if payment made
            try {
                app(\App\Services\CreditNotificationService::class)->clearNotifications($purchase);
            } catch (\Exception $e) {
                \Log::error('Clear Notification Error: ' . $e->getMessage());
            }

            \Log::info("Payment Voucher Created for Purchase #{$purchase->invoice_no}");

        } catch (\Exception $e) {
            \Log::error('TransactionService Payment Error: '.$e->getMessage());
            throw $e;
        }
    }

    public function createPurchaseVoucher(\App\Models\Purchase $purchase)
    {
        \Log::info("TransactionService: Create Voucher for Purchase #{$purchase->invoice_no}");

        try {
            $balanceService    = app(\App\Services\BalanceService::class);
            $purchaseAccountId = $balanceService->getPurchaseExpenseId($purchase->branch_id); // "Purchase" COA
            $apAccountId       = $balanceService->getAccountsPayableId($purchase->branch_id);

            $extraCost     = (float) ($purchase->extra_cost ?? 0);
            $freightCharges = (float) ($purchase->freight_charges ?? 0);
            // Pure purchase price = net_amount minus extra_cost and freight_charges
            $purchasePrice = max(0, (float) $purchase->net_amount - $extraCost - $freightCharges);

            $lines = [];

            // 1. Debit "Purchase" account — pure inventory cost only (no extra/freight)
            $lines[] = [
                'account_id' => $purchaseAccountId,
                'debit'      => $purchasePrice,
                'credit'     => 0,
                'narration'  => "Purchase Invoice #{$purchase->invoice_no}",
            ];

            // 2. Credit Accounts Payable full pure purchase price
            $lines[] = [
                'account_id' => $apAccountId,
                'debit'      => 0,
                'credit'     => $purchasePrice,
                'narration'  => "Payable to Vendor " . ($purchase->vendor->name ?? ''),
            ];

            // 3. Voucher Header for pure purchase
            $voucherData = [
                'voucher_type' => \App\Models\VoucherMaster::TYPE_JOURNAL, // Purchase is basic journal
                'date'         => $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d'),
                'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type'   => \App\Models\Vendor::class,
                'party_id'     => $purchase->vendor_id,
                'branch_id'    => $purchase->branch_id,
                'remarks'      => "Purchase Voucher #{$purchase->invoice_no}",
            ];

            // 4. Create the main Purchase Voucher
            $this->voucherService->createVoucher($voucherData, $lines, auth()->id());
            \Log::info("Purchase Voucher Created for #{$purchase->invoice_no}. Price: {$purchasePrice}");

            // 5. If extra_cost > 0, create a SEPARATE Expense voucher for the additional cost
            if ($extraCost > 0) {
                $expenseLines = [];
                $purchaseExpensiveId = $balanceService->getPurchaseExpensiveId($purchase->branch_id);
                
                // Debit Expense Account
                $expenseLines[] = [
                    'account_id' => $purchaseExpensiveId,
                    'debit'      => $extraCost,
                    'credit'     => 0,
                    'narration'  => "Extra Cost on Purchase #{$purchase->invoice_no}",
                ];

                // Credit AP for the extra cost (since it's added to vendor balance)
                $expenseLines[] = [
                    'account_id' => $apAccountId,
                    'debit'      => 0,
                    'credit'     => $extraCost,
                    'narration'  => "Payable Extra Cost to Vendor " . ($purchase->vendor->name ?? ''),
                ];

                // Header for Expense Ticket
                $expenseData = [
                    'voucher_type' => \App\Models\VoucherMaster::TYPE_EXPENSE,
                    'date'         => $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d'),
                    'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                    'party_type'   => \App\Models\Vendor::class,
                    'party_id'     => $purchase->vendor_id,
                    'branch_id'    => $purchase->branch_id,
                    'remarks'      => "Purchase Extra Cost (Freight) for Invoice #{$purchase->invoice_no}",
                ];

                $this->voucherService->createVoucher($expenseData, $expenseLines, auth()->id());
                \Log::info("Separate Expense Voucher Created for #{$purchase->invoice_no}. Extra: {$extraCost}");
                
                // Also create Legacy Expense Voucher record to match
                $this->createExpenseVoucherForExtraCost($purchase, $extraCost, 'Vendor Expenses');
            }

            // 6. If freight_charges > 0, create another SEPARATE Expense voucher
            if ($freightCharges > 0) {
                $freightLines = [];
                $purchaseExpensiveId = $balanceService->getPurchaseExpensiveId($purchase->branch_id);
                
                // Debit Expense Account
                $freightLines[] = [
                    'account_id' => $purchaseExpensiveId,
                    'debit'      => $freightCharges,
                    'credit'     => 0,
                    'narration'  => "Freight Charges on Purchase #{$purchase->invoice_no}",
                ];

                // Credit AP for freight cost
                $freightLines[] = [
                    'account_id' => $apAccountId,
                    'debit'      => 0,
                    'credit'     => $freightCharges,
                    'narration'  => "Payable Freight Charges to Vendor " . ($purchase->vendor->name ?? ''),
                ];

                // Header for Expense Ticket
                $freightData = [
                    'voucher_type' => \App\Models\VoucherMaster::TYPE_EXPENSE,
                    'date'         => $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d'),
                    'status'       => \App\Models\VoucherMaster::STATUS_POSTED,
                    'party_type'   => \App\Models\Vendor::class,
                    'party_id'     => $purchase->vendor_id,
                    'branch_id'    => $purchase->branch_id,
                    'remarks'      => "Purchase Freight Charges for Invoice #{$purchase->invoice_no}",
                ];

                $this->voucherService->createVoucher($freightData, $freightLines, auth()->id());
                \Log::info("Separate Freight Voucher Created for #{$purchase->invoice_no}. Freight: {$freightCharges}");
                
                // Also create Legacy Expense Voucher record to match
                $this->createExpenseVoucherForExtraCost($purchase, $freightCharges, 'Freight Charges');
            }



        } catch (\Exception $e) {
            \Log::error('TransactionService Purchase Voucher Error: ' . $e->getMessage());
        }
    }

    /**
     * Auto-create a legacy ExpenseVoucher record for the extra_cost on a purchase.
     * This makes the expense appear in the existing Expense Voucher listing page.
     * Accounting: Dr. Purchase Expensive (PURCHASE_EXP) — already done in createPurchaseVoucher V2 lines above.
     */
    public function createExpenseVoucherForExtraCost(\App\Models\Purchase $purchase, float $extraCost, string $title = 'Purchase Extra Cost')
    {
        try {
            $balanceService      = app(\App\Services\BalanceService::class);
            $purchaseExpensiveId = $balanceService->getPurchaseExpensiveId($purchase->branch_id);

            $evid = \App\Models\ExpenseVoucher::generateInvoiceNo();

            // Find or create the narration for purchase extra costs
            $narration = \App\Models\Narration::firstOrCreate(
                ['narration'    => $title, 'expense_head' => 'Expense voucher'],
                ['narration'    => $title, 'expense_head' => 'Expense voucher']
            );

            \App\Models\ExpenseVoucher::create([
                'evid'             => $evid,
                'entry_date'       => $purchase->purchase_date ?? now()->toDateString(),
                'type'             => 'vendor',
                'party_id'         => $purchase->vendor_id,
                'remarks'          => "Auto: {$title} for Invoice #{$purchase->invoice_no}",
                'narration_id'     => json_encode([(string) $narration->id]),
                'row_account_head' => json_encode([null]),
                'row_account_id'   => json_encode([$purchaseExpensiveId]),
                'amount'           => json_encode([$extraCost]),
                'total_amount'     => $extraCost,
            ]);

            \Log::info("Auto ExpenseVoucher created for Purchase #{$purchase->invoice_no}, {$title}: {$extraCost}");

        } catch (\Exception $e) {
            \Log::error('Auto ExpenseVoucher Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a Purchase Return Voucher (Debit Note).
     * Debit: Accounts Payable (Vendor) | Credit: Purchase Return / Inventory
     */
    public function createPurchaseReturnVoucher(\App\Models\PurchaseReturn $return)
    {
        \Log::info("TransactionService: Create Voucher for Purchase Return #{$return->return_invoice}");

        try {
            $balanceService = app(\App\Services\BalanceService::class);
            // Use Purchase Expense Account (Contra) or a specific Return Account
            $expenseAccountId = $balanceService->getPurchaseExpenseId($return->branch_id); 
            $apAccountId = $balanceService->getAccountsPayableId($return->branch_id);

            $lines = [];

            // 1. Debit Accounts Payable (Vendor Liability Reduces)
            $lines[] = [
                'account_id' => $apAccountId,
                'debit' => $return->net_amount,
                'credit' => 0,
                'narration' => "Debit Note for Return #{$return->return_invoice}",
            ];

            // 2. Credit Purchase Expense (Inventory Value Reduces)
            $lines[] = [
                'account_id' => $expenseAccountId,
                'debit' => 0,
                'credit' => $return->net_amount,
                'narration' => "Purchase Return #{$return->return_invoice}",
            ];

            // 3. Voucher Header
            // Use Journal Type or a specific 'Debit Note' type if available. 
            // Using TYPE_JOURNAL for general ledger adjustment.
            $voucherData = [
                'voucher_type' => \App\Models\VoucherMaster::TYPE_JOURNAL, 
                'date' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : now()->format('Y-m-d'),
                'status' => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type' => \App\Models\Vendor::class,
                'party_id' => $return->vendor_id,
                'remarks' => $return->remarks ?? "Purchase Return #{$return->return_invoice}",
            ];

            // 4. Create Voucher
            $this->voucherService->createVoucher($voucherData, $lines, auth()->id());

            \Log::info("Purchase Return Voucher Created for Invoice #{$return->return_invoice}");

        } catch (\Exception $e) {
            \Log::error('TransactionService Purchase Return Voucher Error: ' . $e->getMessage());
        }
    }

    /**
     * Create Journal Voucher for Sale Return (Credit Note)
     * Dr. Sales Revenue (Reduces Income)
     * Cr. Accounts Receivable (Reduces Customer Debt)
     */
    public function createSaleReturnVoucher(\App\Models\SaleReturn $return)
    {
        \Log::info("TransactionService: Create Voucher for Sale Return #{$return->return_invoice}");

        try {
            $balanceService = app(\App\Services\BalanceService::class);
            // Sales Revenue Account
            $salesRevenueId = $balanceService->getSalesRevenueId($return->branch_id); 
            $arAccountId = $balanceService->getAccountsReceivableId($return->branch_id);

            $lines = [];

            // 1. Debit Sales Revenue (Income Reduces)
            $lines[] = [
                'account_id' => $salesRevenueId,
                'debit' => $return->net_amount,
                'credit' => 0,
                'narration' => "Credit Note for Return #{$return->return_invoice}",
            ];

            // 2. Credit Accounts Receivable (Customer Debt Reduces)
            $lines[] = [
                'account_id' => $arAccountId,
                'debit' => 0,
                'credit' => $return->net_amount,
                'narration' => "Sale Return #{$return->return_invoice}",
            ];

            // 3. Voucher Header
            $voucherData = [
                'voucher_type' => \App\Models\VoucherMaster::TYPE_JOURNAL, 
                'date' => $return->return_date ? \Carbon\Carbon::parse($return->return_date)->format('Y-m-d') : now()->format('Y-m-d'),
                'status' => \App\Models\VoucherMaster::STATUS_POSTED,
                'party_type' => \App\Models\Customer::class,
                'party_id' => $return->customer_id,
                'branch_id' => $return->branch_id,
                'remarks' => $return->remarks ?? "Sale Return #{$return->return_invoice}",
            ];

            // 4. Create Voucher
            $this->voucherService->createVoucher($voucherData, $lines, auth()->id());

            \Log::info("Sale Return Voucher Created for Invoice #{$return->return_invoice}");

        } catch (\Exception $e) {
            \Log::error('TransactionService Sale Return Voucher Error: ' . $e->getMessage());
        }
    }
    /**
     * Reverse all accounting entries for a Purchase (Permanent Delete)
     */
    public function reversePurchaseAccounting(\App\Models\Purchase $purchase)
    {
        \Log::info("TransactionService: Reversing accounting for Purchase #{$purchase->invoice_no}");

        DB::transaction(function () use ($purchase) {
            // 1. Find and delete VoucherMaster records and their journal entries
            // Vouchers are often linked via remarks containing the invoice number
            $vouchers = VoucherMaster::where('remarks', 'like', "%#{$purchase->invoice_no}%")
                ->orWhere('remarks', 'like', "%Purchase Voucher #{$purchase->invoice_no}%")
                ->orWhere('remarks', 'like', "%Auto-Payment for Purchase #{$purchase->invoice_no}%")
                ->get();

            foreach ($vouchers as $voucher) {
                // Delete Journal Entries one by one to trigger Account balance reversal (booted event)
                $voucher->journalEntries->each(function ($entry) {
                    $entry->delete();
                });

                // ALSO delete Vendor/Customer Ledger entries created by this specific voucher
                \App\Models\VendorLedger::where('source_type', get_class($voucher))
                    ->where('source_id', $voucher->id)
                    ->delete();
                
                // For modern vouchers, also check if they have detail rows
                if (method_exists($voucher, 'details')) {
                    $voucher->details()->delete();
                }

                $voucher->delete();
            }

            // 3. Delete Legacy Expense records
            \App\Models\ExpenseVoucher::where('remarks', 'like', "%#{$purchase->invoice_no}%")->delete();
            \App\Models\ReceiptsVoucher::where('remarks', 'like', "%#{$purchase->invoice_no}%")->delete();
            \App\Models\PaymentVoucher::where('remarks', 'like', "%#{$purchase->invoice_no}%")->delete();

            // 4. Delete Vendor Ledger entries linked to this Purchase OR referencing it
            \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)
                ->where(function ($q) use ($purchase) {
                    $q->where(function ($q2) use ($purchase) {
                        $q2->where('source_type', \App\Models\Purchase::class)
                            ->where('source_id', $purchase->id);
                    })->orWhere('description', 'like', "%#{$purchase->invoice_no}%");
                })
                ->delete();

            // 5. Delete Vendor Payment records
            \App\Models\VendorPayment::where('purchase_id', $purchase->id)->delete();

            // 6. Delete linked Customer Ledgers (in case of sale-to-vendor or contra entries)
            if (\Schema::hasColumn('customer_ledgers', 'source_type')) {
                \App\Models\CustomerLedger::where(function ($q) use ($purchase) {
                    $q->where('source_type', \App\Models\Purchase::class)
                        ->where('source_id', $purchase->id);
                })->orWhere('description', 'like', "%#{$purchase->invoice_no}%")
                    ->delete();
            } 
            
            // 5. Recalculate Ledger for this vendor
            $this->recalculateVendorLedger($purchase->vendor_id);
        });
    }

    /**
     * Recalculate running balances in the legacy VendorLedger table for a specific vendor.
     * This is needed when entries in the middle of time are deleted (like during un-post).
     */
    public function recalculateVendorLedger($vendorId)
    {
        $entries = \App\Models\VendorLedger::where('vendor_id', $vendorId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $runningBalance = 0;
        
        foreach ($entries as $index => $entry) {
            if ($index === 0) {
                 // The first entry might have its own opening_balance if it was the initial record
                 $runningBalance = (float)$entry->opening_balance + (float)$entry->credit - (float)$entry->debit;
            } else {
                 $entry->previous_balance = $runningBalance;
                 $runningBalance += ((float)$entry->credit - (float)$entry->debit);
            }
            $entry->closing_balance = $runningBalance;
            $entry->save();
        }
    }

    /**
     * Reverse all accounting entries for a Sale (Permanent Delete)
     */
    public function reverseSaleAccounting(Sale $sale)
    {
        \Log::info("TransactionService: Reversing accounting for Sale #{$sale->invoice_no}");

        DB::transaction(function () use ($sale) {
            // 1. Find and delete VoucherMaster records (Receipt Vouchers)
            // We search for vouchers that mention the invoice number in remarks
            $vouchers = VoucherMaster::where('branch_id', $sale->branch_id)
                ->where(function($q) use ($sale) {
                    $q->where('remarks', 'like', "%#{$sale->invoice_no}%")
                      ->orWhere('remarks', 'like', "%Invoice #{$sale->invoice_no}%");
                })
                ->get();

            foreach ($vouchers as $voucher) {
                // Delete Journal Entries (triggers balance reversal via model events if configured)
                \App\Models\JournalEntry::where('source_type', VoucherMaster::class)
                    ->where('source_id', $voucher->id)
                    ->delete();
                
                // Delete Ledger Entry linked to this voucher
                \App\Models\CustomerLedger::where('source_type', VoucherMaster::class)
                    ->where('source_id', $voucher->id)
                    ->delete();

                if (method_exists($voucher, 'details')) {
                    $voucher->details()->delete();
                }
                $voucher->delete();
            }

            // 2. Delete Customer Ledger Entries linked directly to the Sale or matching the invoice number
            \App\Models\CustomerLedger::where('customer_id', $sale->customer_id)
                ->where(function ($q) use ($sale) {
                    $q->where(function ($q2) use ($sale) {
                        $q2->where('source_type', \App\Models\Sale::class)
                          ->where('source_id', $sale->id);
                    })
                    ->orWhere('description', 'like', "%#{$sale->invoice_no}%")
                    ->orWhere('description', 'like', "%Invoice #{$sale->invoice_no}%");
                })
                ->delete();

            // 3. Delete Customer Payment records
            \App\Models\CustomerPayment::where('sale_id', $sale->id)->delete();
            
            // 4. Recalculate running balances for legacy customer ledger
            if ($sale->customer_id) {
                $this->recalculateCustomerLedger($sale->customer_id);
            }
        });
    }

    public function recalculateCustomerLedger($customerId)
    {
        $entries = \App\Models\CustomerLedger::where('customer_id', $customerId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();
            
        $runningBalance = 0;
        foreach ($entries as $index => $entry) {
            if ($index === 0) {
                 $runningBalance = (float)$entry->opening_balance + (float)$entry->debit - (float)$entry->credit;
            } else {
                 $entry->previous_balance = $runningBalance;
                 $runningBalance += ((float)$entry->debit - (float)$entry->credit);
            }
            $entry->closing_balance = $runningBalance;
            $entry->save();
        }

        // Sync back to Customer Master record
        $customer = \App\Models\Customer::find($customerId);
        if ($customer) {
            $customer->previous_balance = $runningBalance;
            $customer->save();
        }
    }
}
