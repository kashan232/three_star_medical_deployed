<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Services\DualPartyLedgerService;

$dualService = app(DualPartyLedgerService::class);

$dualCustomers = Customer::where('party_type', 'like', '%Vendor%')->get();
$matchedCount = 0;
$unmatched = [];

foreach ($dualCustomers as $c) {
    $v = $dualService->findTwinVendor($c);
    if ($v) {
        $matchedCount++;
    } else {
        $unmatched[] = [
            'id' => $c->id,
            'code' => $c->customer_id,
            'name' => $c->customer_name,
            'cnic' => $c->cnic,
            'party_type' => $c->party_type,
        ];
    }
}

echo "Total Dual Customers: " . $dualCustomers->count() . "\n";
echo "Matched by findTwinVendor: " . $matchedCount . "\n";
echo "Unmatched count: " . count($unmatched) . "\n";
if (count($unmatched) > 0) {
    echo "First 5 unmatched:\n";
    print_r(array_slice($unmatched, 0, 5));
}
