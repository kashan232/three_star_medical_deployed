<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\DualPartyLedgerService;
use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$dualService = app(DualPartyLedgerService::class);
$reporting = app(ReportingController::class);

// Customer 404 (DIAZONE) and Vendor 390 (DIAZONE)
$cId = 404;
$vId = 390;

echo "=== DualPartyLedgerService on DIAZONE ===\n";
$cData = $dualService->getCustomerLedgerData($cId, '2020-01-01', '2026-12-31');
echo "Customer 404 Ledger Tx Count: " . $cData['transactions']->count() . "\n";
echo "Customer Closing Balance: " . $cData['closing_balance'] . "\n";
foreach ($cData['transactions'] as $tx) {
    echo "  [{$tx->created_at}] {$tx->ref} | {$tx->description} | Dr: {$tx->debit} | Cr: {$tx->credit} | Bal: {$tx->running_balance}\n";
}

$vData = $dualService->getVendorLedgerData($vId, '2020-01-01', '2026-12-31');
echo "\nVendor 390 Ledger Tx Count: " . $vData['transactions']->count() . "\n";
echo "Vendor Closing Balance: " . $vData['closing_balance'] . "\n";
foreach ($vData['transactions'] as $tx) {
    echo "  [{$tx->created_at}] {$tx->ref} | {$tx->description} | Dr: {$tx->debit} | Cr: {$tx->credit} | Bal: {$tx->running_balance}\n";
}

echo "\n=== ReportingController on DIAZONE ===\n";
$reqC = new Request(['customer_id' => $cId, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repC = $reporting->fetch_customer_ledger($reqC)->getData(true);
echo "Report Customer 404 Tx Count: " . count($repC['transactions']) . ", Closing: " . $repC['closing_balance'] . "\n";

$reqV = new Request(['vendor_id' => $vId, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);
echo "Report Vendor 390 Tx Count: " . count($repV['transactions']) . ", Closing: " . $repV['closing_balance'] . "\n";
