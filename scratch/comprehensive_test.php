<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use App\Services\DualPartyLedgerService;
use App\Models\Customer;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);
$dualSvc   = app(DualPartyLedgerService::class);

echo "============================================================\n";
echo "   COMPREHENSIVE DUAL-PARTY LEDGER BALANCE MATCH TEST\n";
echo "============================================================\n\n";

// Pick 5 dual-party customers with transactions
$dualCustomers = Customer::where('party_type', 'like', '%Vendor%')
    ->whereExists(function($q) {
        $q->select(\DB::raw(1))->from('customer_ledgers')->whereColumn('customer_id', 'customers.id');
    })
    ->limit(5)
    ->get();

$passed = 0; $failed = 0;

foreach ($dualCustomers as $cust) {
    // Find twin vendor
    $twinVendor = $dualSvc->findTwinVendor($cust);
    if (!$twinVendor) {
        echo "  [{$cust->customer_name}] No twin found — skip\n";
        continue;
    }

    $reqC = new Request(['customer_id' => $cust->id, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
    $reqV = new Request(['vendor_id'   => $twinVendor->id, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);

    $repC = $reporting->fetch_customer_ledger($reqC)->getData(true);
    $repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);

    $cBal = (float)($repC['closing_balance'] ?? 0);
    $vBal = (float)($repV['closing_balance'] ?? 0);
    $cTx  = count($repC['transactions'] ?? []);
    $vTx  = count($repV['transactions'] ?? []);
    $match = abs($cBal - $vBal) < 0.02 && $cTx === $vTx;

    if ($match) {
        echo "  ✓ {$cust->customer_name} (C:{$cust->id}/V:{$twinVendor->id}) — Balance: " . number_format($cBal,2) . " | Rows: {$cTx}\n";
        $passed++;
    } else {
        echo "  ✗ {$cust->customer_name} (C:{$cust->id}/V:{$twinVendor->id})\n";
        echo "      Customer: Balance=" . number_format($cBal,2) . " Rows={$cTx}\n";
        echo "      Vendor  : Balance=" . number_format($vBal,2) . " Rows={$vTx}\n";
        $failed++;
    }
}

echo "\n------------------------------------------------------------\n";
echo "PASSED: {$passed} | FAILED: {$failed}\n";
echo "------------------------------------------------------------\n";
