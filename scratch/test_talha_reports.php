<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);

$reqC = new Request(['customer_id' => 543, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repC = $reporting->fetch_customer_ledger($reqC)->getData(true);
echo "TALHA BROTHERS - Customer 543 Tx Count: " . count($repC['transactions']) . ", Closing: " . $repC['closing_balance'] . ", Has Twin: " . ($repC['customer']['has_twin_vendor'] ? 'YES' : 'NO') . "\n";
foreach ($repC['transactions'] as $tx) {
    echo "  [{$tx['date']}] {$tx['invoice']} | {$tx['description']} | Dr: {$tx['debit']} | Cr: {$tx['credit']} | Bal: {$tx['balance']}\n";
}

$reqV = new Request(['vendor_id' => 458, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);
echo "\nTALHA BROTHER - Vendor 458 Tx Count: " . count($repV['transactions']) . ", Closing: " . $repV['closing_balance'] . ", Has Twin: " . ($repV['vendor']['has_twin_customer'] ? 'YES' : 'NO') . "\n";
foreach ($repV['transactions'] as $tx) {
    echo "  [{$tx['date']}] {$tx['invoice']} | {$tx['description']} | Dr: {$tx['debit']} | Cr: {$tx['credit']} | Bal: {$tx['balance']}\n";
}
