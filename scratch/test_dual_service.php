<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Services\DualPartyLedgerService;

$dualService = app(DualPartyLedgerService::class);

// Let's test on Customer ID 1 (A.H HEALTH CARE)
$c = Customer::find(1);
$v = $dualService->findTwinVendor($c);

echo "Customer 1: " . ($c ? $c->customer_name : 'null') . "\n";
echo "Twin Vendor: " . ($v ? "ID {$v->id} - {$v->name} (Code: {$v->vendor_code})" : 'NOT FOUND') . "\n";

if ($v) {
    $twinBack = $dualService->findTwinCustomer($v);
    echo "Twin back to Customer: " . ($twinBack ? "ID {$twinBack->id} - {$twinBack->customer_name}" : 'NOT FOUND') . "\n";
    
    $cData = $dualService->getCustomerLedgerData($c->id, '2020-01-01', '2026-12-31');
    echo "getCustomerLedgerData count: " . $cData['transactions']->count() . "\n";
    echo "  Opening: {$cData['opening_balance']}, Closing: {$cData['closing_balance']}, Total Dr: {$cData['total_debit']}, Total Cr: {$cData['total_credit']}\n";
    
    $vData = $dualService->getVendorLedgerData($v->id, '2020-01-01', '2026-12-31');
    echo "getVendorLedgerData count: " . $vData['transactions']->count() . "\n";
    echo "  Opening: {$vData['opening_balance']}, Closing: {$vData['closing_balance']}, Total Dr: {$vData['total_debit']}, Total Cr: {$vData['total_credit']}\n";
}
