<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ReportingController;

$controller = app(ReportingController::class);

// Test WALKING CUSTOMER (Customer 570 / Vendor 340)
$reqCust = new Request([
    'customer_id' => 570,
    'start_date' => '2020-01-01',
    'end_date' => '2026-12-31'
]);

$reqVend = new Request([
    'vendor_id' => 340,
    'start_date' => '2020-01-01',
    'end_date' => '2026-12-31'
]);

$custData = $controller->fetch_customer_ledger($reqCust)->getData(true);
$vendData = $controller->fetch_vendor_ledger($reqVend)->getData(true);

echo "=== WALKING CUSTOMER (Customer 570) ===\n";
echo "Customer Opening: {$custData['opening_balance']}, Closing: {$custData['closing_balance']}, Dr: {$custData['total_debit']}, Cr: {$custData['total_credit']}\n";
echo "Transactions count: " . count($custData['transactions']) . "\n";
foreach ($custData['transactions'] as $tx) {
    echo "  [{$tx['date']}] {$tx['invoice']} | {$tx['description']} | Dr: {$tx['debit']} | Cr: {$tx['credit']} | Bal: {$tx['balance']}\n";
}

echo "\n=== WALKING CUSTOMER (Vendor 340) ===\n";
echo "Vendor Opening: {$vendData['opening_balance']}, Closing: {$vendData['closing_balance']}, Dr: {$vendData['total_debit']}, Cr: {$vendData['total_credit']}\n";
echo "Transactions count: " . count($vendData['transactions']) . "\n";
foreach ($vendData['transactions'] as $tx) {
    echo "  [{$tx['date']}] {$tx['invoice']} | {$tx['description']} | Dr: {$tx['debit']} | Cr: {$tx['credit']} | Bal: {$tx['balance']}\n";
}
