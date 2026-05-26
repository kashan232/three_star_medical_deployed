<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Purchase;

$p = Purchase::where('status_purchase', 'draft')->latest()->first();
if($p) {
    echo "PO: " . $p->invoice_no . "\n";
    foreach($p->items as $i) {
        echo "Item ID: " . $i->product_id . "\n";
        echo "Qty: " . $i->qty . " (Type: " . gettype($i->qty) . ")\n";
        echo "Loose Qty: " . $i->loose_qty . " (Type: " . gettype($i->loose_qty) . ")\n";
        echo "Total Pieces (Accessor): " . $i->total_pieces . "\n";
        echo "-------------------\n";
    }
} else {
    echo "No draft PO found\n";
}
