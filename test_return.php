<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$latestReturn = \App\Models\PurchaseReturn::latest()->first();
if (!$latestReturn || !$latestReturn->purchase_id) {
    echo "No recent return with purchase_id found.\n";
    exit;
}

$p = \App\Models\Purchase::find($latestReturn->purchase_id);
if ($p) {
    dump([
        'return_id' => $latestReturn->id,
        'status_purchase' => $p->status_purchase,
        'purchase_net_amount' => $p->net_amount,
        'purchase_extra_cost' => $p->extra_cost,
        'purchase_discount' => $p->discount,
        'purchase_freight' => $p->freight_charges,
        'returns_sum_net_amount' => \App\Models\PurchaseReturn::where('purchase_id', $p->id)->sum('net_amount'),
        'is_fully_returned' => (\App\Models\PurchaseReturn::where('purchase_id', $p->id)->sum('net_amount') >= max(0, (float) $p->net_amount - (float) $p->extra_cost))
    ]);
}
