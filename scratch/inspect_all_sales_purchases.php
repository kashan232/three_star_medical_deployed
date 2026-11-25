<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Vendor;
use App\Models\Sale;
use App\Models\Purchase;

echo "=== ALL PURCHASES ===\n";
foreach (Purchase::all() as $p) {
    $v = Vendor::find($p->vendor_id);
    echo "Purchase #{$p->id} | Inv: {$p->invoice_no} | Date: {$p->purchase_date} | Vendor ID: {$p->vendor_id} | Name: " . ($v ? $v->name : 'N/A') . " | Code: " . ($v ? $v->vendor_code : 'N/A') . " | PartyType: " . ($v ? $v->party_type : 'N/A') . "\n";
    // Search customer with similar name
    if ($v) {
        $cleanVName = trim(preg_replace('/[^a-zA-Z0-9]/', ' ', strtolower($v->name)));
        $custs = Customer::whereRaw("LOWER(customer_name) LIKE ?", ['%' . substr($cleanVName, 0, 5) . '%'])->get();
        foreach ($custs as $c) {
            echo "   Possible matching Customer: ID {$c->id} | {$c->customer_name} | Code: {$c->customer_id} | Type: {$c->party_type}\n";
        }
    }
}

echo "\n=== ALL SALES ===\n";
foreach (Sale::all() as $s) {
    $c = Customer::find($s->customer_id);
    echo "Sale #{$s->id} | Inv: {$s->invoice_no} | Date: {$s->sale_date} | Customer ID: {$s->customer_id} | Name: " . ($c ? $c->customer_name : 'N/A') . " | Code: " . ($c ? $c->customer_id : 'N/A') . " | PartyType: " . ($c ? $c->party_type : 'N/A') . "\n";
    if ($c) {
        $cleanCName = trim(preg_replace('/[^a-zA-Z0-9]/', ' ', strtolower($c->customer_name)));
        $vends = Vendor::whereRaw("LOWER(name) LIKE ?", ['%' . substr($cleanCName, 0, 5) . '%'])->get();
        foreach ($vends as $v) {
            echo "   Possible matching Vendor: ID {$v->id} | {$v->name} | Code: {$v->vendor_code} | Type: {$v->party_type}\n";
        }
    }
}
