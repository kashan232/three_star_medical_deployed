<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "--- PURCHASES ---\n";
foreach (App\Models\Purchase::all() as $p) {
    echo "Purchase ID: {$p->id}, Inv: {$p->invoice_no}, Vendor ID: {$p->vendor_id}, Net: {$p->net_amount}, Status: {$p->status_purchase}\n";
    $v = App\Models\Vendor::find($p->vendor_id);
    if ($v) {
        echo "   Vendor: {$v->name} (Code: {$v->vendor_code}, Type: {$v->party_type})\n";
    }
}

echo "--- SALES ---\n";
foreach (App\Models\Sale::all() as $s) {
    echo "Sale ID: {$s->id}, Inv: {$s->invoice_no}, Customer ID: {$s->customer_id}, Net: {$s->net_amount}, Status: {$s->sale_status}\n";
    $c = App\Models\Customer::find($s->customer_id);
    if ($c) {
        echo "   Customer: {$c->customer_name} (Code: {$c->customer_id}, Type: {$c->party_type})\n";
    }
}
