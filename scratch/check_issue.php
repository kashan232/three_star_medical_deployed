<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = DB::table('vendor_ledgers')
    ->where('description', 'like', '%Purchase Voucher%')
    ->get();

echo "Found " . $rows->count() . " duplicate Purchase Voucher entries in vendor_ledgers:\n";
foreach ($rows as $r) {
    echo "ID: {$r->id} | Vendor: {$r->vendor_id} | Dr: {$r->debit} | Cr: {$r->credit} | Source: {$r->source_type}#{$r->source_id} | Desc: {$r->description}\n";
}
