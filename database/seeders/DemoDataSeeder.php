<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DemoDataSeeder
 * ─────────────────────────────────────────────────────────────────────────
 *  Inserts a complete, balanced demo dataset for testing reports & ledgers:
 *
 *   • Chart of Accounts  (5 account_heads + 14 accounts)
 *   • 2 Vendors
 *   • 5 Customers
 *   • 10 Products  (size_mode = by_pieces, pieces_per_box = 1)
 *   • 5 Purchases  → purchase_items → stock_movements → warehouse_stocks
 *                  → journal_entries (Inventory Dr / AP Cr + AP Dr / Cash Cr)
 *                  → voucher_master + voucher_details  (Payment Voucher)
 *   • 5 Sales      → sale_items → stock_movements → warehouse_stocks
 *                  → journal_entries (AR Dr / Sales Cr + Cash Dr / AR Cr)
 *                  → voucher_master + voucher_details  (Receipt Voucher)
 *
 *  Run:   php artisan db:seed --class=DemoDataSeeder
 * ─────────────────────────────────────────────────────────────────────────
 */
class DemoDataSeeder extends Seeder
{
    private int $warehouseId;

    private int $branchId;

    private string $baseDate = '2026-02-01';

    // Core Account IDs populated during COA seed
    private int $acCash;

    private int $acAR;

    private int $acAP;

    private int $acSales;

    private int $acDiscount;

    private int $acInventory;

    // ─────────────────────────────────────────────────────────────────────

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->ensureBranchAndWarehouse();
        $this->seedChartOfAccounts();
        $vendors = $this->seedVendors();
        $customers = $this->seedCustomers();
        $products = $this->seedProducts();
        $this->seedPurchases($vendors, $products);
        $this->seedSales($customers, $products);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->newLine();
        $this->command->info('✅  DemoDataSeeder completed!');
        $this->command->table(
            ['Entity', 'Count'],
            [
                ['Chart of Accounts', '5 heads + 14 accounts'],
                ['Vendors',           '2'],
                ['Customers',         '5'],
                ['Products',          '10 (by_pieces, with stock)'],
                ['Purchases',         '5 + Journal Entries + Payment Vouchers'],
                ['Sales',             '5 + Journal Entries + Receipt Vouchers'],
            ]
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Branch & Warehouse
    // ─────────────────────────────────────────────────────────────────────

    private function ensureBranchAndWarehouse(): void
    {
        // Reuse the first existing branch (avoid unique user_id constraint)
        $branch = DB::table('branches')->orderBy('id')->first();
        if ($branch) {
            $this->branchId = $branch->id;
        } else {
            $this->branchId = DB::table('branches')->insertGetId([
                'name' => 'Main Branch',
                'address' => 'Karachi HQ',
                'number' => '021-111-0000',
                'user_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $wh = DB::table('warehouses')->where('warehouse_name', 'Main Store')->first();
        if ($wh) {
            $this->warehouseId = $wh->id;
        } else {
            $this->warehouseId = DB::table('warehouses')->insertGetId([
                'branch_id' => $this->branchId,
                'warehouse_name' => 'Main Store',
                'creater_id' => 1,
                'location' => 'Karachi',
                'remarks' => 'Primary demo stock store',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->line("  Branch #{$this->branchId} | Warehouse #{$this->warehouseId}");
    }

    // ─────────────────────────────────────────────────────────────────────
    // Chart of Accounts
    // ─────────────────────────────────────────────────────────────────────

    private function seedChartOfAccounts(): void
    {
        $headDefs = [
            ['code' => 'AH-1000', 'name' => 'Assets',      'type' => 'Asset',     'level' => 1],
            ['code' => 'AH-2000', 'name' => 'Liabilities',  'type' => 'Liability', 'level' => 1],
            ['code' => 'AH-3000', 'name' => 'Equity',       'type' => 'Equity',    'level' => 1],
            ['code' => 'AH-4000', 'name' => 'Revenue',      'type' => 'Revenue',   'level' => 1],
            ['code' => 'AH-5000', 'name' => 'Expenses',     'type' => 'Expense',   'level' => 1],
        ];

        $headIds = [];
        foreach ($headDefs as $h) {
            $row = DB::table('account_heads')->where('code', $h['code'])->first();
            if ($row) {
                $headIds[$h['code']] = $row->id;
            } else {
                $headIds[$h['code']] = DB::table('account_heads')->insertGetId([
                    'code' => $h['code'],
                    'name' => $h['name'],
                    'type' => $h['type'],
                    'level' => $h['level'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $accountDefs = [
            ['1001', 'AH-1000', 'Cash in Hand',           'Debit',   500000.00],
            ['1002', 'AH-1000', 'Accounts Receivable',    'Debit',        0.00],
            ['1003', 'AH-1000', 'Inventory - Stock',       'Debit',        0.00],
            ['1004', 'AH-1000', 'Bank Account',            'Debit',  1000000.00],
            ['2001', 'AH-2000', 'Accounts Payable',        'Credit',       0.00],
            ['2002', 'AH-2000', 'Sales Tax Payable',       'Credit',       0.00],
            ['3001', 'AH-3000', "Owner's Capital",         'Credit', 1500000.00],
            ['4001', 'AH-4000', 'Sales Revenue',           'Credit',       0.00],
            ['4002', 'AH-4000', 'Sales Discount Allowed',  'Debit',        0.00],
            ['5001', 'AH-5000', 'Cost of Goods Sold',      'Debit',        0.00],
            ['5002', 'AH-5000', 'Purchase Discount',       'Credit',       0.00],
            ['5003', 'AH-5000', 'Freight & Transport',     'Debit',        0.00],
            ['5004', 'AH-5000', 'Salaries Expense',        'Debit',        0.00],
            ['5005', 'AH-5000', 'Rent Expense',            'Debit',        0.00],
        ];

        $codeToId = [];
        foreach ($accountDefs as [$code, $headKey, $title, $type, $opening]) {
            $row = DB::table('accounts')->where('account_code', $code)->first();
            if ($row) {
                $codeToId[$code] = $row->id;
            } else {
                $codeToId[$code] = DB::table('accounts')->insertGetId([
                    'head_id' => $headIds[$headKey],
                    'account_code' => $code,
                    'title' => $title,
                    'type' => $type,
                    'opening_balance' => $opening,
                    'current_balance' => $opening,
                    'status' => 1,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->acCash = $codeToId['1001'];
        $this->acAR = $codeToId['1002'];
        $this->acInventory = $codeToId['1003'];
        $this->acAP = $codeToId['2001'];
        $this->acSales = $codeToId['4001'];
        $this->acDiscount = $codeToId['4002'];

        $this->command->line('  ✔ Chart of Accounts (5 heads + 14 accounts)');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Vendors
    // ─────────────────────────────────────────────────────────────────────

    private function seedVendors(): array
    {
        $defs = [
            ['name' => 'Al-Razzaq Enterprises',    'phone' => '0300-1111001', 'email' => 'alrazzaq@demo.com',   'address' => 'Karachi', 'opening_balance' => '0'],
            ['name' => 'Pak Traders International', 'phone' => '0321-2222002', 'email' => 'paktraders@demo.com', 'address' => 'Lahore',  'opening_balance' => '0'],
        ];

        $ids = [];
        foreach ($defs as $d) {
            $row = DB::table('vendors')->where('name', $d['name'])->first();
            $ids[] = $row ? $row->id : DB::table('vendors')->insertGetId(array_merge($d, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        $this->command->line('  ✔ 2 Vendors');

        return $ids;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Customers
    // ─────────────────────────────────────────────────────────────────────

    private function seedCustomers(): array
    {
        $defs = [
            ['customer_id' => 'CUST-001', 'customer_name' => 'Ahmed & Sons Trading',  'mobile' => '0300-5551001', 'address' => 'Karachi',    'customer_type' => 'Retail',    'opening_balance' => 0, 'status' => 'active'],
            ['customer_id' => 'CUST-002', 'customer_name' => 'Bilal Wholesale Depot', 'mobile' => '0321-5552002', 'address' => 'Lahore',     'customer_type' => 'Wholesale', 'opening_balance' => 0, 'status' => 'active'],
            ['customer_id' => 'CUST-003', 'customer_name' => 'City Mart',             'mobile' => '0333-5553003', 'address' => 'Faisalabad', 'customer_type' => 'Retail',    'opening_balance' => 0, 'status' => 'active'],
            ['customer_id' => 'CUST-004', 'customer_name' => 'Dano Distribution',    'mobile' => '0345-5554004', 'address' => 'Islamabad',  'customer_type' => 'Wholesale', 'opening_balance' => 0, 'status' => 'active'],
            ['customer_id' => 'CUST-005', 'customer_name' => 'Eastern General Store', 'mobile' => '0312-5555005', 'address' => 'Rawalpindi', 'customer_type' => 'Retail',    'opening_balance' => 0, 'status' => 'active'],
        ];

        $ids = [];
        foreach ($defs as $d) {
            $row = DB::table('customers')->where('customer_name', $d['customer_name'])->first();
            $ids[] = $row ? $row->id : DB::table('customers')->insertGetId(array_merge($d, [
                'created_at' => now(), 'updated_at' => now(),
            ]));
        }

        $this->command->line('  ✔ 5 Customers');

        return $ids;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Products
    // ─────────────────────────────────────────────────────────────────────

    private function seedProducts(): array
    {
        $catId = DB::table('categories')->value('id')
                   ?? DB::table('categories')->insertGetId(['category_name' => 'General Goods', 'created_at' => now(), 'updated_at' => now()]);
        $brandId = DB::table('brands')->value('id')
                   ?? DB::table('brands')->insertGetId(['brand_name' => 'Generic Brand', 'created_at' => now(), 'updated_at' => now()]);
        $unitId = DB::table('units')->value('id')
                   ?? DB::table('units')->insertGetId(['unit_name' => 'Piece', 'created_at' => now(), 'updated_at' => now()]);

        // [item_code, item_name, buy_price, sell_price, initial_pieces]
        $defs = [
            ['PRD-001', 'Surf Excel Detergent 1kg',  180, 230, 200],
            ['PRD-002', 'Ariel Powder 500g',          140, 185, 150],
            ['PRD-003', 'Nestle Nescafe 200g',        680, 820,  80],
            ['PRD-004', 'Knorr Seasoning 100g',        55,  75, 300],
            ['PRD-005', 'Tapal Danedar Tea 200g',     165, 215, 120],
            ['PRD-006', 'Colgate Toothpaste 150ml',    88, 120, 250],
            ['PRD-007', 'Lux Soap Bar (Pack of 3)',   210, 270, 180],
            ['PRD-008', 'Lifebuoy Hand Wash 500ml',   195, 255, 100],
            ['PRD-009', 'Dettol Antiseptic 100ml',    148, 195,  90],
            ['PRD-010', 'Sunsilk Shampoo 200ml',      255, 330, 110],
        ];

        $ids = [];
        foreach ($defs as [$code, $name, $buy, $sell, $initQty]) {
            $row = DB::table('products')->where('item_code', $code)->whereNull('deleted_at')->first();
            if ($row) {
                $productId = $row->id;
            } else {
                $productId = DB::table('products')->insertGetId([
                    'category_id' => $catId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,
                    'item_code' => $code,
                    'item_name' => $name,
                    'size_mode' => 'by_pieces',
                    'pieces_per_box' => 1,
                    'purchase_price_per_piece' => $buy,
                    'sale_price_per_box' => $sell,
                    'total_m2' => 0,
                    'price_per_m2' => 0,
                    'boxes_quantity' => $initQty,
                    'piece_quantity' => $initQty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Opening stock movement
                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'type' => 'adjustment',
                    'qty' => $initQty,
                    'ref_type' => 'INIT',
                    'note' => 'Opening stock — demo seeder',
                    'created_at' => $this->baseDate,
                    'updated_at' => $this->baseDate,
                ]);
            }

            // Upsert opening warehouse stock
            $this->stockUpsert($productId, $initQty);
            $ids[] = $productId;
        }

        $this->command->line('  ✔ 10 Products with opening stock');

        return $ids;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Purchases
    // ─────────────────────────────────────────────────────────────────────

    private function seedPurchases(array $vIds, array $pIds): void
    {
        // [vendor_idx, +days, [[prod_idx, qty, buy_price], ...], paid, discount, extra_cost]
        $defs = [
            [0,  0, [[0, 50, 180], [1, 40, 140], [5, 60, 88]],  15000,    0,   0],
            [1,  3, [[2, 20, 680], [3, 80,  55]],                 5000,  500,   0],
            [0,  6, [[4, 30, 165], [6, 45, 210]],                 9450,    0, 200],
            [1, 10, [[7, 25, 195], [8, 30, 148]],                 4000,  300,   0],
            [0, 14, [[9, 35, 255], [0, 20, 180], [3, 50, 55]],    8000,    0, 150],
        ];

        foreach ($defs as $n => [$vIdx, $dayOffset, $items, $paid, $discount, $extra]) {
            $vendorId = $vIds[$vIdx];
            $pDate = Carbon::parse($this->baseDate)->addDays($dayOffset)->toDateString();
            $invoiceNo = 'PO-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = array_sum(array_map(fn ($i) => $i[1] * $i[2], $items));
            $net = $subtotal - $discount + $extra;
            $due = max(0, $net - $paid);

            $purchaseId = DB::table('purchases')->insertGetId([
                'branch_id' => $this->branchId,
                'warehouse_id' => $this->warehouseId,
                'vendor_id' => $vendorId,
                'invoice_no' => $invoiceNo,
                'purchase_date' => $pDate,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'extra_cost' => $extra,
                'net_amount' => $net,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'status_purchase' => 'approved',
                'note' => "Demo purchase - {$invoiceNo}",
                'created_at' => $pDate,
                'updated_at' => $pDate,
            ]);

            foreach ($items as [$pIdx, $qty, $price]) {
                $productId = $pIds[$pIdx];
                $lineTotal = $qty * $price;

                DB::table('purchase_items')->insert([
                    'purchase_id' => $purchaseId,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'price' => $price,
                    'item_discount' => 0,
                    'line_total' => $lineTotal,
                    'unit' => 'pcs',
                    'size_mode' => 'by_pieces',
                    'pieces_per_box' => 1,
                    'created_at' => $pDate,
                    'updated_at' => $pDate,
                ]);

                $this->stockAdd($productId, $qty);

                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'type' => 'in',
                    'qty' => $qty,
                    'ref_type' => 'Purchase',
                    'ref_id' => $purchaseId,
                    'note' => "Purchase {$invoiceNo}",
                    'created_at' => $pDate,
                    'updated_at' => $pDate,
                ]);
            }

            /*
             * Journal Entries - Purchase:
             *   Dr Inventory / Cr Accounts Payable  (goods received)
             *   Dr Accounts Payable / Cr Cash       (payment if any)
             */
            $src = 'App\Models\Purchase';
            $pty = 'App\Models\Vendor';

            $this->je($purchaseId, $src, $this->acInventory, $pDate, $net, 0, "Inventory inward - {$invoiceNo}", $vendorId, $pty);
            $this->je($purchaseId, $src, $this->acAP, $pDate, 0, $net, "Vendor payable - {$invoiceNo}", $vendorId, $pty);

            if ($paid > 0) {
                $this->je($purchaseId, $src, $this->acAP, $pDate, $paid, 0, "AP cleared - {$invoiceNo}", $vendorId, $pty);
                $this->je($purchaseId, $src, $this->acCash, $pDate, 0, $paid, "Cash paid - {$invoiceNo}", $vendorId, $pty);
            }

            /* Payment Voucher */
            if ($paid > 0) {
                $pvNo = 'PV-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
                $vmId = DB::table('voucher_masters')->insertGetId([
                    'voucher_type' => 'payment',
                    'voucher_no' => $pvNo,
                    'status' => 'posted',
                    'date' => $pDate,
                    'fiscal_year' => '2025-2026',
                    'party_type' => $pty,
                    'party_id' => $vendorId,
                    'total_amount' => $paid,
                    'remarks' => "Payment to vendor for {$invoiceNo}",
                    'posted_at' => $pDate,
                    'created_at' => $pDate,
                    'updated_at' => $pDate,
                ]);

                DB::table('voucher_details')->insert([
                    ['voucher_master_id' => $vmId, 'account_id' => $this->acAP,   'debit' => $paid, 'credit' => 0,    'narration' => "AP cleared - {$invoiceNo}", 'created_at' => $pDate, 'updated_at' => $pDate],
                    ['voucher_master_id' => $vmId, 'account_id' => $this->acCash, 'debit' => 0,     'credit' => $paid, 'narration' => 'Cash disbursed',            'created_at' => $pDate, 'updated_at' => $pDate],
                ]);
            }

            $this->command->line("   ↳ Purchase {$invoiceNo}  |  Net: Rs {$net}  |  Paid: Rs {$paid}  |  Due: Rs {$due}");
        }

        $this->command->line('  ✔ 5 Purchases + Journal Entries + Payment Vouchers');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Sales
    // ─────────────────────────────────────────────────────────────────────

    private function seedSales(array $cIds, array $pIds): void
    {
        // [customer_idx, +days, [[prod_idx, qty, sell_price], ...], cash_received, discount]
        $defs = [
            [0,  2, [[0, 10, 230], [1,  8, 185], [5, 15, 120]],   5000, 200],
            [1,  5, [[2,  5, 820], [3, 20,  75]],                  5600,   0],
            [2,  8, [[4, 12, 215], [6, 10, 270]],                  5150, 150],
            [3, 11, [[7,  8, 255], [8, 12, 195]],                  3750,   0],
            [4, 15, [[9, 15, 330], [0, 10, 230], [3, 25, 75]],     8400, 300],
        ];

        foreach ($defs as $n => [$cIdx, $dayOffset, $items, $cashIn, $discount]) {
            $customerId = $cIds[$cIdx];
            $sDateStr = Carbon::parse($this->baseDate)->addDays($dayOffset)->toDateTimeString();
            $sDate = substr($sDateStr, 0, 10);
            $invoiceNo = 'SLE-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);

            $subtotal = array_sum(array_map(fn ($i) => $i[1] * $i[2], $items));
            $totalNet = $subtotal - $discount;
            $netPaid = min($cashIn, $totalNet);
            $change = max(0, $cashIn - $totalNet);
            $due = max(0, $totalNet - $netPaid);
            $totalPcs = array_sum(array_column($items, 1));

            $saleId = DB::table('sales')->insertGetId([
                'customer_id' => $customerId,
                'invoice_no' => $invoiceNo,
                'reference' => 'DEMO-SEED',
                'sale_status' => 'posted',
                'total_bill_amount' => $subtotal,
                'total_extradiscount' => $discount,
                'total_net' => $totalNet,
                'total_items' => $totalPcs,
                'cash' => $cashIn,
                'change' => $change,
                'created_at' => $sDateStr,
                'updated_at' => $sDateStr,
            ]);

            foreach ($items as [$pIdx, $qty, $price]) {
                $productId = $pIds[$pIdx];
                $lineTotal = $qty * $price;

                DB::table('sale_items')->insert([
                    'sale_id' => $saleId,
                    'product_id' => $productId,
                    'warehouse_id' => $this->warehouseId,
                    'qty' => $qty,
                    'total_pieces' => $qty,
                    'loose_pieces' => 0,
                    'price' => $price,
                    'price_per_piece' => $price,
                    'price_per_m2' => 0,
                    'total' => $lineTotal,
                    'size_mode' => 'by_pieces',
                    'created_at' => $sDateStr,
                    'updated_at' => $sDateStr,
                ]);

                $this->stockDeduct($productId, $qty);

                DB::table('stock_movements')->insert([
                    'product_id' => $productId,
                    'type' => 'out',
                    'qty' => $qty,
                    'ref_type' => 'Sale',
                    'ref_id' => $saleId,
                    'note' => "Sale {$invoiceNo}",
                    'created_at' => $sDateStr,
                    'updated_at' => $sDateStr,
                ]);
            }

            /*
             * Journal Entries - Sale:
             *   Dr AR / Cr Sales Revenue    (invoice)
             *   Dr Sales Discount           (if given)
             *   Dr Cash / Cr AR             (collection)
             */
            $src = 'App\Models\Sale';
            $pty = 'App\Models\Customer';

            $this->je($saleId, $src, $this->acAR, $sDate, $totalNet, 0, "Sale invoice - {$invoiceNo}", $customerId, $pty);
            $this->je($saleId, $src, $this->acSales, $sDate, 0, $subtotal, "Sales revenue - {$invoiceNo}", $customerId, $pty);

            if ($discount > 0) {
                $this->je($saleId, $src, $this->acDiscount, $sDate, $discount, 0, "Discount allowed - {$invoiceNo}", $customerId, $pty);
            }

            if ($netPaid > 0) {
                $this->je($saleId, $src, $this->acCash, $sDate, $netPaid, 0, "Cash received - {$invoiceNo}", $customerId, $pty);
                $this->je($saleId, $src, $this->acAR, $sDate, 0, $netPaid, "AR cleared - {$invoiceNo}", $customerId, $pty);
            }

            /* Receipt Voucher */
            if ($netPaid > 0) {
                $rvNo = 'RV-'.str_pad($n + 1, 4, '0', STR_PAD_LEFT);
                $vmId = DB::table('voucher_masters')->insertGetId([
                    'voucher_type' => 'receipt',
                    'voucher_no' => $rvNo,
                    'status' => 'posted',
                    'date' => $sDate,
                    'fiscal_year' => '2025-2026',
                    'party_type' => $pty,
                    'party_id' => $customerId,
                    'total_amount' => $netPaid,
                    'remarks' => "Receipt from customer for {$invoiceNo}",
                    'posted_at' => $sDate,
                    'created_at' => $sDate,
                    'updated_at' => $sDate,
                ]);

                DB::table('voucher_details')->insert([
                    ['voucher_master_id' => $vmId, 'account_id' => $this->acCash, 'debit' => $netPaid, 'credit' => 0,       'narration' => 'Cash received from customer', 'created_at' => $sDate, 'updated_at' => $sDate],
                    ['voucher_master_id' => $vmId, 'account_id' => $this->acAR,   'debit' => 0,        'credit' => $netPaid, 'narration' => "AR cleared - {$invoiceNo}",    'created_at' => $sDate, 'updated_at' => $sDate],
                ]);
            }

            $this->command->line("   ↳ Sale {$invoiceNo}  |  Net: Rs {$totalNet}  |  Paid: Rs {$netPaid}  |  Due: Rs {$due}");
        }

        $this->command->line('  ✔ 5 Sales + Journal Entries + Receipt Vouchers');
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Insert a journal entry line and keep the account's running balance up-to-date.
     */
    private function je(
        int $sourceId,
        string $sourceType,
        int $accountId,
        string $date,
        float $debit,
        float $credit,
        string $description,
        ?int $partyId = null,
        ?string $partyType = null
    ): void {
        DB::table('journal_entries')->insert([
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'account_id' => $accountId,
            'entry_date' => $date,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
            'party_type' => $partyType,
            'party_id' => $partyId,
            'is_reconciled' => false,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        DB::table('accounts')
            ->where('id', $accountId)
            ->update(['current_balance' => DB::raw("current_balance + {$debit} - {$credit}")]);
    }

    /** Upsert warehouse stock (absolute set — used for opening balance). */
    private function stockUpsert(int $productId, int $qty): void
    {
        $existing = DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->where('warehouse_id', $this->warehouseId)
            ->first();

        if ($existing) {
            DB::table('warehouse_stocks')->where('id', $existing->id)->update([
                'quantity' => $qty,
                'total_pieces' => $qty,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('warehouse_stocks')->insert([
                'warehouse_id' => $this->warehouseId,
                'product_id' => $productId,
                'quantity' => $qty,
                'total_pieces' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /** Increment warehouse stock on purchase inward. */
    private function stockAdd(int $productId, int $qty): void
    {
        DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->where('warehouse_id', $this->warehouseId)
            ->update([
                'quantity' => DB::raw("quantity + {$qty}"),
                'total_pieces' => DB::raw("total_pieces + {$qty}"),
                'updated_at' => now(),
            ]);
    }

    /** Decrement warehouse stock on sale outward. */
    private function stockDeduct(int $productId, int $qty): void
    {
        DB::table('warehouse_stocks')
            ->where('product_id', $productId)
            ->where('warehouse_id', $this->warehouseId)
            ->update([
                'quantity' => DB::raw("GREATEST(0, quantity - {$qty})"),
                'total_pieces' => DB::raw("GREATEST(0, total_pieces - {$qty})"),
                'updated_at' => now(),
            ]);
    }
}
