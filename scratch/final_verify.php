<?php
/**
 * Final Verification: Dual Party Ledger Balance Match Test
 * Tests DIAZONE (Customer 404 / Vendor 390) and other dual parties
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);
$startDate = '2020-01-01';
$endDate   = '2026-12-31';

$tests = [
    ['label' => 'DIAZONE',         'customer_id' => 404, 'vendor_id' => 390],
    ['label' => 'TALHA BROTHERS',  'customer_id' => 543, 'vendor_id' => 458],
];

$allPassed = true;

foreach ($tests as $t) {
    echo "\n========== {$t['label']} ==========\n";

    // --- Customer Ledger ---
    try {
        $reqC = new Request(['customer_id' => $t['customer_id'], 'start_date' => $startDate, 'end_date' => $endDate]);
        $repC = $reporting->fetch_customer_ledger($reqC)->getData(true);
        $custBalance = $repC['closing_balance'] ?? 'ERROR';
        $custTxCount = count($repC['ledger_entries'] ?? []);
        $custLabel   = $repC['balance_label']   ?? '?';
        echo "  Customer Ledger  → Balance: " . number_format((float)$custBalance, 2) . " [{$custLabel}], Rows: {$custTxCount}\n";
    } catch (\Throwable $e) {
        echo "  Customer Ledger  → EXCEPTION: " . $e->getMessage() . "\n";
        $custBalance = null;
    }

    // --- Vendor Ledger ---
    try {
        $reqV = new Request(['vendor_id' => $t['vendor_id'], 'start_date' => $startDate, 'end_date' => $endDate]);
        $repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);
        $vendBalance = $repV['closing_balance'] ?? 'ERROR';
        $vendTxCount = count($repV['ledger_entries'] ?? []);
        $vendLabel   = $repV['balance_label']   ?? '?';
        echo "  Vendor Ledger    → Balance: " . number_format((float)$vendBalance, 2) . " [{$vendLabel}], Rows: {$vendTxCount}\n";
    } catch (\Throwable $e) {
        echo "  Vendor Ledger    → EXCEPTION: " . $e->getMessage() . "\n";
        $vendBalance = null;
    }

    // --- Match Check ---
    if ($custBalance !== null && $vendBalance !== null) {
        $match = (abs((float)$custBalance - (float)$vendBalance) < 0.02);
        echo "  Balance Match    → " . ($match ? "✓ YES" : "✗ NO (diff: " . number_format(abs((float)$custBalance - (float)$vendBalance), 2) . ")") . "\n";
        if (!$match) $allPassed = false;
    } else {
        echo "  Balance Match    → ✗ COULD NOT COMPARE (exception occurred)\n";
        $allPassed = false;
    }
}

echo "\n\n=========================\n";
echo "Overall Result: " . ($allPassed ? "✓ ALL TESTS PASSED" : "✗ SOME TESTS FAILED") . "\n";
echo "=========================\n\n";
