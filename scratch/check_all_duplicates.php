<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING DUPLICATE WAREHOUSE_STOCKS ROWS ACROSS ALL PRODUCTS ===\n";
$duplicates = DB::table('warehouse_stocks')
    ->select('warehouse_id', 'product_id', DB::raw('count(*) as count'), DB::raw('sum(total_pieces) as sum_pieces'))
    ->groupBy('warehouse_id', 'product_id')
    ->having('count', '>', 1)
    ->get();

echo "Found " . count($duplicates) . " warehouse-product pairs with multiple rows:\n";
foreach ($duplicates as $d) {
    echo "  WH ID: {$d->warehouse_id} | Product ID: {$d->product_id} | Rows: {$d->count} | Total Pieces: {$d->sum_pieces}\n";
    $rows = DB::table('warehouse_stocks')->where('warehouse_id', $d->warehouse_id)->where('product_id', $d->product_id)->get();
    foreach ($rows as $r) {
        echo "    -> Row ID: {$r->id} | UOM ID: " . ($r->uom_id ?? 'NULL') . " | Qty: {$r->quantity} | Pieces: {$r->total_pieces}\n";
    }
}
