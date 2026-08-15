<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
    Illuminate\Support\Facades\DB::insert("insert into sales (customer_id, employee_id, commission_percentage, reference, sale_date, vendor_bill_no, order_no, sale_order_no, so_date, total_amount_Words, sale_status, enable_hs_code, branch_id, mode, created_by, credit_days, due_date, invoice_no, updated_at, created_at, total_commission, commission_paid, commission_share_ratio, total_gst, total_freight, total_expense, total_fixed_tax, total_inc_tax, total_adv_tax) values (1, 1, 2, 'ref', '2026-08-15', 'vb', 'on', '000000', '2026-08-15', 'FIFTY THOUSAND RUPEES ONLY', 'post', 1, 1, 'sin', 1, 0, '2026-08-15', 'SIN-TEST', '2026-08-15 16:09:41', '2026-08-15 16:09:41', 0, 0, 0.5, 0, 0, 0, 0, 0, 0)");
    echo "Success\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
