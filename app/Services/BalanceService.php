<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\VoucherDetail;
use App\Models\VoucherMaster;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Get customer balance from journal entries
     * Positive = Customer owes money (Dr)
     * Negative = Customer has advance/credit (Cr)
     */
    public function getCustomerBalance(int $customerId): float
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return 0;
        }

        // Opening balance from customer master
        $openingBalance = (float) ($customer->opening_balance ?? 0);

        // Sum of all journal entries for this customer
        $journalBalance = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customerId)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return $openingBalance + $journalBalance;
    }

    /**
     * Get customer balance before a specific date
     */
    public function getCustomerBalanceBeforeDate(int $customerId, string $date): float
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return 0;
        }

        $openingBalance = (float) ($customer->opening_balance ?? 0);

        $journalBalance = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customerId)
            ->where('entry_date', '<', $date)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return $openingBalance + $journalBalance;
    }

    /**
     * Get customer ledger entries for a date range
     */
    public function getCustomerLedger(int $customerId, string $startDate, string $endDate): array
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return [
                'customer' => null,
                'opening_balance' => 0,
                'transactions' => [],
            ];
        }

        // Get opening balance (balance before start date)
        $openingBalance = $this->getCustomerBalanceBeforeDate($customerId, $startDate);

        // Get journal entries in range
        $entries = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customerId)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += ($entry->debit - $entry->credit);

            return [
                'id' => $entry->id,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $runningBalance,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
            ];
        });

        return [
            'customer' => $customer,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get vendor balance from journal entries
     * Positive = We owe vendor (Cr)
     * Negative = Vendor owes us (Dr) - rare
     */
    public function getVendorBalance(int $vendorId): float
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if (! $vendor) {
            return 0;
        }

        // Opening balance from vendor master
        $openingBalance = (float) ($vendor->opening_balance ?? 0);

        // Sum of all journal entries for this vendor
        // For vendors: Credit increases balance (we owe more)
        //              Debit decreases balance (we pay)
        $journalBalance = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance');

        $journalBalance = $journalBalance ?? 0;

        return $openingBalance + $journalBalance;
    }

    /**
     * Get vendor balance before a specific date
     */
    public function getVendorBalanceBeforeDate(int $vendorId, string $date): float
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if (! $vendor) {
            return 0;
        }

        $openingBalance = (float) ($vendor->opening_balance ?? 0);

        $journalBalance = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->where('entry_date', '<', $date)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance');

        $journalBalance = $journalBalance ?? 0;

        return $openingBalance + $journalBalance;
    }

    /**
     * Get Financial Summary for Dashboard
     */
    public function getFinancialSummary(string $startDate, string $endDate, ?int $branchId = null): array
    {
        // 1. Sales Revenue (Credit minus Debit)
        $salesHeadId = \App\Models\AccountHead::where('name', 'Income')->value('id') ?? 3;
        $sales = JournalEntry::whereHas('account', function ($q) use ($salesHeadId) {
            $q->where('head_id', $salesHeadId);
        })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->selectRaw('SUM(credit) - SUM(debit) as net_sales')
            ->value('net_sales') ?? 0;

        // 2. Purchase Expense (Debit minus Credit)
        $expenseHeadId = \App\Models\AccountHead::where('name', 'Expenses')->value('id') ?? 4;
        $purchases = JournalEntry::whereHas('account', function ($q) use ($expenseHeadId) {
            $q->where('head_id', $expenseHeadId);
        })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->selectRaw('SUM(debit) - SUM(credit) as net_purchases')
            ->value('net_purchases') ?? 0;

        // 3. Total Receivables (Money people owe us)
        $arAccount = \App\Models\Account::where('title', 'Accounts Receivable')->first();
        $receivables = 0;
        if ($arAccount) {
            $receivablesQry = JournalEntry::where('account_id', $arAccount->id);
            if ($branchId) {
                $receivablesQry->where('branch_id', $branchId);
            }
            $receivables = $receivablesQry->selectRaw('SUM(debit) - SUM(credit) as balance')->value('balance') ?? 0;
            // Add opening if calculated_balance logic in Account model isn't enough OR if we want live branch-specific balance
        }

        // 4. Total Payables (Money we owe vendors)
        $apAccount = \App\Models\Account::where('title', 'Accounts Payable')->first();
        $payables = 0;
        if ($apAccount) {
            $payablesQry = JournalEntry::where('account_id', $apAccount->id);
            if ($branchId) {
                $payablesQry->where('branch_id', $branchId);
            }
            $payables = $payablesQry->selectRaw('SUM(credit) - SUM(debit) as balance')->value('balance') ?? 0;
        }

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'receivables' => $receivables,
            'payables' => $payables,
            'net_cash_flow' => $sales - $purchases, // Rough estimate
        ];
    }

    /**
     * Get vendor ledger entries for a date range
     */
    public function getVendorLedger(int $vendorId, string $startDate, string $endDate): array
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if (! $vendor) {
            return [
                'vendor' => null,
                'opening_balance' => 0,
                'transactions' => [],
            ];
        }

        // Get opening balance (balance before start date)
        $openingBalance = $this->getVendorBalanceBeforeDate($vendorId, $startDate);

        // Get journal entries in range
        $entries = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $entries->map(function ($entry) use (&$runningBalance) {
            // For vendors: Credit increases, Debit decreases
            $runningBalance += ($entry->credit - $entry->debit);

            return [
                'id' => $entry->id,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $runningBalance,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
            ];
        });

        return [
            'vendor' => $vendor,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    /**
     * Create a Receipt Voucher using VoucherMaster + JournalEntry
     */
    public function createReceiptVoucher(
        Customer $customer,
        float $amount,
        int $cashAccountId,
        string $date,
        ?string $description = null,
        $source = null
    ): VoucherMaster {
        return DB::transaction(function () use ($customer, $amount, $cashAccountId, $date, $description) {

            // 1. Generate voucher number
            $voucherNo = $this->generateVoucherNo('receipt');

            // 2. Create VoucherMaster
            $voucher = VoucherMaster::create([
                'voucher_type' => VoucherMaster::TYPE_RECEIPT,
                'voucher_no' => $voucherNo,
                'date' => $date,
                'party_type' => Customer::class,
                'party_id' => $customer->id,
                'total_amount' => $amount,
                'remarks' => $description ?? "Receipt from {$customer->customer_name}",
                'status' => VoucherMaster::STATUS_POSTED,
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // 3. Create VoucherDetails (Dr Cash, Cr Receivable)
            $receivableAccountId = $this->getAccountsReceivableId();

            // Debit Cash/Bank
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $cashAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => 'Cash/Bank received',
            ]);

            // Credit Accounts Receivable
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $receivableAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => 'Customer payment received',
            ]);

            // 4. Create Journal Entries
            $journalService = app(JournalEntryService::class);

            // Dr Cash
            $journalService->recordEntry(
                $voucher,
                $cashAccountId,
                $amount,
                0,
                $description ?? "Receipt #{$voucherNo}",
                $date
            );

            // Cr Receivable (with Customer party)
            $journalService->recordEntry(
                $voucher,
                $receivableAccountId,
                0,
                $amount,
                $description ?? "Receipt #{$voucherNo}",
                $date,
                $customer
            );

            return $voucher;
        });
    }

    /**
     * Create a Sale Invoice Voucher
     */
    public function createSaleVoucher(
        Customer $customer,
        float $amount,
        string $invoiceNo,
        string $date,
        ?int $branchId = null
    ): VoucherMaster {
        return DB::transaction(function () use ($customer, $amount, $invoiceNo, $date, $branchId) {

            $voucherNo = $this->generateVoucherNo('journal', $branchId);

            $voucher = VoucherMaster::create([
                'voucher_type' => VoucherMaster::TYPE_JOURNAL,
                'voucher_no' => $voucherNo,
                'date' => $date,
                'party_type' => Customer::class,
                'party_id' => $customer->id,
                'branch_id' => $branchId,
                'total_amount' => $amount,
                'remarks' => "Sale Invoice #{$invoiceNo}",
                'status' => VoucherMaster::STATUS_POSTED,
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            $receivableAccountId = $this->getAccountsReceivableId($branchId);
            $salesAccountId = $this->getSalesRevenueId($branchId);

            // Dr Receivable
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $receivableAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => "Sale Invoice #{$invoiceNo}",
            ]);

            // Cr Sales Revenue
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $salesAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => "Sale Invoice #{$invoiceNo}",
            ]);

            // Journal Entries
            $journalService = app(JournalEntryService::class);

            // Dr Receivable with customer party
            $journalService->recordEntry(
                $voucher,
                $receivableAccountId,
                $amount,
                0,
                "Sale Invoice #{$invoiceNo}",
                $date,
                $customer
            );

            // Cr Sales
            $journalService->recordEntry(
                $voucher,
                $salesAccountId,
                0,
                $amount,
                "Sale Invoice #{$invoiceNo}",
                $date
            );

            return $voucher;
        });
    }

    /**
     * Generate unique voucher number
     */
    private function generateVoucherNo(string $type, ?int $branchId = null): string
    {
        $prefix = match ($type) {
            'receipt' => 'RV',
            'payment' => 'PV',
            'expense' => 'EV',
            'journal' => 'JV',
            default => 'V',
        };

        $year = date('Y');
        $lastVoucher = VoucherMaster::where('voucher_type', $type)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('voucher_no', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVoucher) {
            $lastNum = (int) substr($lastVoucher->voucher_no, -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return "{$prefix}-{$year}-".str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-create default Account Heads and critical Accounts if missing.
     * Skips Equity — only creates: Current Assets, Current Liabilities, Income, Expenses.
     * Returns array of head IDs keyed by type.
     */
    public function ensureDefaultCOA(?int $branchId = null): array
    {
        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }

        $branchId = $branchId ?? 1; // Fallback to head office

        // Define the 4 heads we need (NO Equity)
        $headMap = [
            'asset' => 'Current Assets',
            'liability' => 'Current Liabilities',
            'income' => 'Income',
            'expense' => 'Expenses',
        ];

        $headIds = [];

        foreach ($headMap as $key => $name) {
            $head = \App\Models\AccountHead::firstOrCreate(
                ['name' => $name, 'branch_id' => $branchId],
                ['name' => $name, 'branch_id' => $branchId]
            );

            $headIds[$key] = $head->id;
        }

        // Define the 5 critical accounts
        $criticalAccounts = [
            [
                'title' => 'Cash in Hand',
                'account_code' => 'CASH',
                'type' => 'Debit',
                'head_id' => $headIds['asset'],
                'search' => ['title', 'like', '%Cash%'],
            ],
            [
                'title' => 'Accounts Receivable',
                'account_code' => 'AR',
                'type' => 'Debit',
                'head_id' => $headIds['asset'],
                'search' => ['title', 'like', '%Receivable%'],
            ],
            [
                'title' => 'Accounts Payable',
                'account_code' => 'AP',
                'type' => 'Credit',
                'head_id' => $headIds['liability'],
                'search' => ['title', 'like', '%Payable%'],
            ],
            [
                'title' => 'Sales Revenue',
                'account_code' => 'SALES',
                'type' => 'Credit',
                'head_id' => $headIds['income'],
                'search' => ['account_code', 'SALES'],
            ],
            [
                'title' => 'Purchase',
                'account_code' => 'PURCHASE',
                'type' => 'Debit',
                'head_id' => $headIds['expense'],
                'search' => ['account_code', 'PURCHASE'],
            ],
            [
                'title' => 'Purchase Expensive',
                'account_code' => 'PURCHASE_EXP',
                'type' => 'Debit',
                'head_id' => $headIds['expense'],
                'search' => ['account_code', 'PURCHASE_EXP'],
            ],
        ];

        foreach ($criticalAccounts as $def) {
            $existing = Account::where($def['search'][0], $def['search'][1], $def['search'][2] ?? $def['search'][1])
                ->where('branch_id', $branchId)
                ->first();

            if (! $existing) {
                $acc = Account::create([
                    'title' => $def['title'],
                    'account_code' => $def['account_code'],
                    'type' => $def['type'],
                    'head_id' => $def['head_id'],
                    'opening_balance' => 0,
                    'status' => 1,
                    'branch_id' => $branchId,
                ]);

                $acc->account_code = $def['account_code'];
                $acc->save();
                \Log::info("COA Auto-Setup: Created account '{$def['title']}' under head ID {$def['head_id']}");
            } elseif (is_null($existing->head_id)) {
                // Fix existing account that has no head assigned
                $existing->head_id = $def['head_id'];
                $existing->save();
                \Log::info("COA Auto-Setup: Fixed head for '{$existing->title}'");
            }
        }

        return $headIds;
    }

    /**
     * Get Accounts Receivable account ID
     */
    public function getAccountsReceivableId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('title', 'like', '%Receivable%')
                    ->orWhere('account_code', 'AR');
            })
            ->first();

        return $account->id;
    }

    /**
     * Get Sales Revenue account ID
     */
    public function getSalesRevenueId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('account_code', 'SALES')
                    ->orWhere('title', 'like', '%Sales%');
            })
            ->first();

        return $account->id;
    }

    /**
     * Get Cash account ID
     */
    public function getCashAccountId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('title', 'like', '%Cash%')
                    ->orWhere('account_code', 'CASH');
            })
            ->first();

        return $account->id;
    }

    /**
     * Get Accounts Payable account ID (Liability)
     */
    public function getAccountsPayableId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('title', 'like', '%Payable%')
                    ->orWhere('account_code', 'AP');
            })
            ->first();

        return $account->id;
    }

    /**
     * Get the main Purchase account ID (tracks purchase price only — no extra cost)
     */
    public function getPurchaseExpenseId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where('account_code', 'PURCHASE')
            ->first();

        if (! $account) {
            $account = Account::where('title', 'like', '%Cost of Goods%')
                ->orWhere(function ($q) {
                    $q->where('title', 'like', '%Purchase%')
                        ->where('account_code', '!=', 'PURCHASE_EXP');
                })
                ->first();
        }

        return $account->id;
    }

    /**
     * Get the Purchase Expensive account ID (tracks extra/additional costs on purchases)
     */
    public function getPurchaseExpensiveId(?int $branchId = null): int
    {
        $this->ensureDefaultCOA($branchId);

        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        $account = Account::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('account_code', 'PURCHASE_EXP')
                    ->orWhere('title', 'Purchase Expensive');
            })
            ->first();

        return $account->id;
    }

    /**
     * Format balance with Dr/Cr indicator
     */
    public static function formatBalance(float $balance): string
    {
        $formatted = number_format(abs($balance), 2);
        $suffix = $balance >= 0 ? 'Dr' : 'Cr';

        return "{$formatted} {$suffix}";
    }

    /**
     * Get accounts suitable for payments (Cash / Bank type).
     * Uses head names 'Current Assets' or heads containing 'Cash' or 'Bank'
     * instead of hardcoded head_id = [1, 2].
     */
    public function getPaymentAccounts(?int $branchId = null)
    {
        if ($branchId === null && auth()->check()) {
            $branchId = auth()->user()->getBranchId();
        }
        $branchId = $branchId ?? 1;

        // First ensure COA heads exist
        $this->ensureDefaultCOA($branchId);

        // Get head IDs for Cash/Bank/Asset-type heads by name for this branch
        $cashBankHeadIds = \App\Models\AccountHead::where('branch_id', $branchId)
            ->where(function ($q) {
                $q->where('name', 'like', '%Cash%')
                    ->orWhere('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Asset%')   // catches 'Assets', 'Current Assets', 'Fixed Assets'
                    ->orWhere('name', 'Current Assets');
            })
            ->pluck('id')
            ->toArray();

        if (! empty($cashBankHeadIds)) {
            $accounts = Account::whereIn('head_id', $cashBankHeadIds)
                ->where('branch_id', $branchId)
                ->where('status', 1)
                ->orderBy('title')
                ->get();

            if ($accounts->isNotEmpty()) {
                return $accounts;
            }
        }

        // Fallback: accounts with Cash or Bank in title for this branch
        return Account::where('branch_id', $branchId)
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('title', 'like', '%Cash%')
                    ->orWhere('title', 'like', '%Bank%');
            })
            ->orderBy('title')
            ->get();

    }
}
