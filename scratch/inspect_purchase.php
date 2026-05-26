<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Purchase;

$p = Purchase::find(16);
if ($p) {
    echo "Purchase Date: " . ($p->purchase_date ? $p->purchase_date->toDateString() : 'NULL') . "\n";
    echo "Vendor Credit Terms: " . ($p->vendor ? $p->vendor->credit_terms : 'NULL') . "\n";
    echo "Due Amount: " . $p->due_amount . "\n";
} else {
    echo "Purchase 16 not found\n";
}
