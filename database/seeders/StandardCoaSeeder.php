<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountHead;
use App\Models\Account;
use App\Models\Branch;

class StandardCoaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        if ($branches->isEmpty()) {
            $branches = collect([(object)['id' => 1]]);
        }

        foreach ($branches as $branch) {
            $branchId = $branch->id;

            // ==========================================
            // 1. LEVEL 1: TOP-LEVEL HEADS
            // ==========================================
            $headAssets = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-00-000'],
                ['name' => 'Assets', 'type' => 'Asset', 'level' => 1]
            );

            $headLiabilities = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '2-00-000'],
                ['name' => 'Liabilities', 'type' => 'Liability', 'level' => 1]
            );

            $headEquity = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '3-00-000'],
                ['name' => 'Equity / Capital', 'type' => 'Equity', 'level' => 1]
            );

            $headRevenue = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '4-00-000'],
                ['name' => 'Revenue / Income', 'type' => 'Revenue', 'level' => 1]
            );

            $headExpenses = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '5-00-000'],
                ['name' => 'Expenses', 'type' => 'Expense', 'level' => 1]
            );

            // ==========================================
            // 2. LEVEL 2: SUB-HEADS / CONTROL ACCOUNTS
            // ==========================================
            
            // Assets Sub-heads
            $subFixedAssets = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-01'],
                ['name' => 'Non-Current / Fixed Assets', 'type' => 'Asset', 'level' => 2, 'parent_id' => $headAssets->id]
            );

            $subCurrentAssets = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-02'],
                ['name' => 'Current Assets', 'type' => 'Asset', 'level' => 2, 'parent_id' => $headAssets->id]
            );

            $subCashInHand = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-02-040'],
                ['name' => 'Cash & Cash Equivalents', 'type' => 'Asset', 'level' => 3, 'parent_id' => $subCurrentAssets->id]
            );

            $subDebtors = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-02-051'],
                ['name' => 'Trade Debtors / Customers / CDR', 'type' => 'Asset', 'level' => 3, 'parent_id' => $subCurrentAssets->id]
            );

            $subBanks = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-02-052'],
                ['name' => 'Bank Accounts', 'type' => 'Asset', 'level' => 3, 'parent_id' => $subCurrentAssets->id]
            );

            $subInventory = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '1-02-060'],
                ['name' => 'Stock & Inventory', 'type' => 'Asset', 'level' => 3, 'parent_id' => $subCurrentAssets->id]
            );

            // Liabilities Sub-heads
            $subCurrentLiabilities = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '2-02'],
                ['name' => 'Current Liabilities', 'type' => 'Liability', 'level' => 2, 'parent_id' => $headLiabilities->id]
            );

            $subCreditors = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '2-02-010'],
                ['name' => 'Trade Creditors / Vendors / Suppliers', 'type' => 'Liability', 'level' => 3, 'parent_id' => $subCurrentLiabilities->id]
            );

            $subTaxesPayable = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '2-02-020'],
                ['name' => 'Accrued Expenses & Taxes Payable', 'type' => 'Liability', 'level' => 3, 'parent_id' => $subCurrentLiabilities->id]
            );

            // Equity Sub-heads
            $subCapital = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '3-01'],
                ['name' => 'Capital & Reserves', 'type' => 'Equity', 'level' => 2, 'parent_id' => $headEquity->id]
            );

            // Revenue Sub-heads
            $subSalesRev = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '4-01-001'],
                ['name' => 'Sales Revenue', 'type' => 'Revenue', 'level' => 2, 'parent_id' => $headRevenue->id]
            );

            $subOtherIncome = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '4-02-001'],
                ['name' => 'Other Income & Commission', 'type' => 'Revenue', 'level' => 2, 'parent_id' => $headRevenue->id]
            );

            // Expense Sub-heads
            $subCOGS = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '5-01-001'],
                ['name' => 'Cost of Goods Sold / Purchases', 'type' => 'Expense', 'level' => 2, 'parent_id' => $headExpenses->id]
            );

            $subAdminExpenses = AccountHead::firstOrCreate(
                ['branch_id' => $branchId, 'code' => '5-02'],
                ['name' => 'Administrative & Operating Expenses', 'type' => 'Expense', 'level' => 2, 'parent_id' => $headExpenses->id]
            );

            // ==========================================
            // 3. LEVEL 4: DETAIL ACCOUNTS (LEDGER LEVEL)
            // ==========================================
            
            // Cash in Hand
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-040-00001'],
                ['title' => 'CASH IN HAND', 'head_id' => $subCashInHand->id, 'type' => 'Debit', 'status' => 1]
            );

            // Standard Banks
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-052-00001'],
                ['title' => 'ASKARI COMMERCIAL BANK', 'head_id' => $subBanks->id, 'type' => 'Debit', 'status' => 1]
            );

            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-052-00002'],
                ['title' => 'FAYSAL BANK LIMITED', 'head_id' => $subBanks->id, 'type' => 'Debit', 'status' => 1]
            );

            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-052-00003'],
                ['title' => 'HABIB BANK LIMITED (HBL)', 'head_id' => $subBanks->id, 'type' => 'Debit', 'status' => 1]
            );

            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-052-00004'],
                ['title' => 'MEEZAN BANK LIMITED', 'head_id' => $subBanks->id, 'type' => 'Debit', 'status' => 1]
            );

            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-052-00005'],
                ['title' => 'BANK AL HABIB LIMITED', 'head_id' => $subBanks->id, 'type' => 'Debit', 'status' => 1]
            );

            // Accounts Receivable (Control)
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '1-02-051-00001'],
                ['title' => 'ACCOUNTS RECEIVABLE (TRADE DEBTORS)', 'head_id' => $subDebtors->id, 'type' => 'Debit', 'status' => 1]
            );

            // Accounts Payable (Control)
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '2-02-010-00001'],
                ['title' => 'ACCOUNTS PAYABLE (TRADE CREDITORS)', 'head_id' => $subCreditors->id, 'type' => 'Credit', 'status' => 1]
            );

            // Sales Revenue
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '4-01-001-00001'],
                ['title' => 'TOTAL SALES (REVENUE)', 'head_id' => $subSalesRev->id, 'type' => 'Credit', 'status' => 1]
            );

            // Purchase / Direct Expense
            Account::firstOrCreate(
                ['branch_id' => $branchId, 'account_code' => '5-01-001-00001'],
                ['title' => 'PURCHASE / COST OF GOODS SOLD', 'head_id' => $subCOGS->id, 'type' => 'Debit', 'status' => 1]
            );

            // Standard Operating Expenses
            $standardExpenses = [
                ['code' => '5-02-001-00001', 'title' => 'SALARIES & WAGES EXPENSE'],
                ['code' => '5-02-002-00001', 'title' => 'OFFICE RENT & UTILITIES EXPENSE'],
                ['code' => '5-02-003-00001', 'title' => 'TRAVELLING, CONVEYANCE & FUEL EXPENSE'],
                ['code' => '5-02-004-00001', 'title' => 'PRINTING, STATIONERY & SUPPLIES'],
                ['code' => '5-02-005-00001', 'title' => 'REPAIR & MAINTENANCE EXPENSE'],
                ['code' => '5-02-006-00001', 'title' => 'ENTERTAINMENT & REFRESHMENT EXPENSE'],
                ['code' => '5-02-007-00001', 'title' => 'BANK CHARGES & TAXES EXPENSE'],
                ['code' => '5-02-008-00001', 'title' => 'MARKETING, COMMISSION & PROMOTION'],
                ['code' => '5-02-009-00001', 'title' => 'MISCELLANEOUS EXPENSE'],
            ];

            foreach ($standardExpenses as $exp) {
                Account::firstOrCreate(
                    ['branch_id' => $branchId, 'account_code' => $exp['code']],
                    ['title' => $exp['title'], 'head_id' => $subAdminExpenses->id, 'type' => 'Debit', 'status' => 1]
                );
            }
        }
    }
}
