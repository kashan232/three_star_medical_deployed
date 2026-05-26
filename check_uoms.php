<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->boot();

$rows = \Illuminate\Support\Facades\DB::table('product_uoms')
    ->join('products', 'products.id', '=', 'product_uoms.product_id')
    ->select('product_uoms.id', 'product_uoms.product_id', 'products.item_name', 'product_uoms.name', 'product_uoms.pieces_per_box', 'products.pieces_per_box as prod_ppb')
    ->get();

foreach ($rows as $r) {
    echo "UOM#{$r->id} | Product#{$r->product_id} ({$r->item_name}) | packing_name={$r->name} | packing_ppb={$r->pieces_per_box} | product_ppb={$r->prod_ppb}\n";
}
