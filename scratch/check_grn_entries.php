<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VendorLedger;
use App\Models\Purchase;

$invoice = 'GRN-0001';
$entries = VendorLedger::where('description', 'like', "%GRN-0001%")->get();
if ($entries->isEmpty()) {
    $entries = VendorLedger::where('description', 'like', "%Purchase%")->latest()->limit(10)->get();
}

foreach ($entries as $e) {
    echo "ID: {$e->id}, Vendor: {$e->vendor_id}, Source: {$e->source_type} #{$e->source_id}, Desc: {$e->description}, Date: {$e->created_at}\n";
}

$purchase = Purchase::where('invoice_no', $invoice)->first();
if ($purchase) {
    echo "Purchase found: ID {$purchase->id}, Vendor {$purchase->vendor_id}, Status {$purchase->status_purchase}\n";
} else {
    echo "Purchase NOT found!\n";
}
