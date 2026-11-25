<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$dualService = app(\App\Services\DualPartyLedgerService::class);
$c = App\Models\Customer::find(543);
$v = App\Models\Vendor::find(458);

echo "Customer 543: '{$c->customer_name}', Code: '{$c->customer_id}', CNIC: '{$c->cnic}', Type: '{$c->party_type}'\n";
echo "Vendor 458: '{$v->name}', Code: '{$v->vendor_code}', CNIC: '{$v->cnic}', Type: '{$v->party_type}'\n";

$foundV = $dualService->findTwinVendor($c);
echo "findTwinVendor(Customer 543): " . ($foundV ? "Vendor ID {$foundV->id} '{$foundV->name}'" : "NULL (NOT FOUND!)") . "\n";

$foundC = $dualService->findTwinCustomer($v);
echo "findTwinCustomer(Vendor 458): " . ($foundC ? "Customer ID {$foundC->id} '{$foundC->customer_name}'" : "NULL (NOT FOUND!)") . "\n";
