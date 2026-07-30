<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
auth()->login($user);

$controller = app(\App\Http\Controllers\ReportingController::class);

echo "=== TEST: GROUPED PRODUCT LEDGER (CATEGORY 9) ===\n";
$req = \Illuminate\Http\Request::create('/report/product-ledger/fetch', 'GET', [
    'category_id' => 9,
    'start_date'  => '2026-01-01',
    'end_date'    => '2026-07-29',
]);
$res = json_decode($controller->fetchProductLedger($req)->getContent(), true);

echo "Success: " . ($res['success'] ? 'YES' : 'NO') . "\n";
echo "Is Consolidated: " . ($res['is_consolidated'] ? 'YES' : 'NO') . "\n";
echo "Product Groups Count: " . count($res['products_data']) . "\n\n";

foreach (array_slice($res['products_data'], 0, 3) as $i => $pGroup) {
    $p = $pGroup['product'];
    echo "--- Product #" . ($i+1) . " --- \n";
    echo "Code: " . $p['item_code'] . "\n";
    echo "Name: " . $p['item_name'] . "\n";
    echo "Company: " . $p['brand_name'] . "\n";
    echo "Category: " . $p['category_name'] . "\n";
    echo "Opening: " . $pGroup['opening_balance'] . "\n";
    echo "Qty IN: " . $pGroup['total_qty_in'] . "\n";
    echo "Qty OUT: " . $pGroup['total_qty_out'] . "\n";
    echo "Closing: " . $pGroup['closing_balance'] . "\n";
    echo "Rows Count: " . count($pGroup['rows']) . "\n\n";
}
