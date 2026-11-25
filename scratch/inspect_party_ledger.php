<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\CustomerLedger;
use App\Models\VendorLedger;

$dualCustomers = Customer::where('party_type', 'like', '%Vendor%')->take(5)->get();
foreach ($dualCustomers as $c) {
    $v = Vendor::where('vendor_code', $c->customer_id)->first();
    if (!$v) {
        $v = Vendor::where('name', $c->customer_name)->first();
    }
    echo "Customer ID: {$c->id}, Code: {$c->customer_id}, Name: {$c->customer_name}, Type: {$c->party_type}\n";
    if ($v) {
        echo "  Found Vendor ID: {$v->id}, Code: {$v->vendor_code}, Name: {$v->name}, Type: {$v->party_type}\n";
        echo "  CustomerLedger entries: " . CustomerLedger::where('customer_id', $c->id)->count() . "\n";
        echo "  VendorLedger entries: " . VendorLedger::where('vendor_id', $v->id)->count() . "\n";
    } else {
        echo "  No matching vendor found by code or name!\n";
    }
}
