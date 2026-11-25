<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;
use App\Services\DualPartyLedgerService;

$dualService = app(DualPartyLedgerService::class);

// Find customers/vendors that have actual sales, purchases, or ledger entries
$customersWithLedger = Customer::whereHas('deliveryReturnNotes') // just checking
    ->orWhereIn('id', CustomerLedger::select('customer_id')->distinct())
    ->get();

echo "Customers with ledger entries: " . $customersWithLedger->count() . "\n";

$foundWithDualTx = 0;
foreach ($customersWithLedger as $c) {
    $v = $dualService->findTwinVendor($c);
    $cLCount = CustomerLedger::where('customer_id', $c->id)->count();
    $vLCount = $v ? VendorLedger::where('vendor_id', $v->id)->count() : 0;
    
    if ($v && ($cLCount > 0 || $vLCount > 0)) {
        echo "Customer [{$c->id}] '{$c->customer_name}' (Code: {$c->customer_id}, Type: {$c->party_type}) -> Vendor [{$v->id}] '{$v->name}' (Code: {$v->vendor_code}, Type: {$v->party_type})\n";
        echo "   CustLedger: $cLCount, VendLedger: $vLCount\n";
        $foundWithDualTx++;
        if ($foundWithDualTx >= 10) break;
    }
}
