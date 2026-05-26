<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Purchase::latest()->first();
if($p) {
    echo "Latest Purchase: " . $p->id . " Status: " . json_encode($p->toArray()) . "\n";
    foreach($p->items as $i) {
        echo "Item Qty: " . $i->qty . " Free: " . $i->free_qty_pieces . " Prod: " . $i->product_id . " Wh: " . $i->warehouse_id . "\n";
    }
} else {
    echo "No purchases found.\n";
}

echo "Latest Warehouse Stocks:\n";
$ws = App\Models\WarehouseStock::orderBy('id', 'desc')->take(5)->get();
foreach($ws as $w) {
    echo "WS Product: " . $w->product_id . " Wh: " . $w->warehouse_id . " Branch: " . $w->branch_id . " Pieces: " . $w->total_pieces . "\n";
}
