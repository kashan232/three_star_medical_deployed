<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== WAREHOUSE STOCKS FOR PRODUCT 692 ===\n";
$whStocks = DB::table('warehouse_stocks')->where('product_id', 692)->get();
echo json_encode($whStocks, JSON_PRETTY_PRINT) . "\n\n";

echo "=== ALL WAREHOUSES ===\n";
$warehouses = DB::table('warehouses')->get();
echo json_encode($warehouses, JSON_PRETTY_PRINT) . "\n\n";

echo "=== PRODUCT BATCHES FOR PRODUCT 692 ===\n";
$batches = DB::table('product_batches')->where('product_id', 692)->get();
echo json_encode($batches, JSON_PRETTY_PRINT) . "\n\n";

echo "=== SALE ITEM BATCHES FOR PRODUCT 692 ===\n";
$sib = DB::table('sale_item_batches')
    ->join('sale_items', 'sale_items.id', '=', 'sale_item_batches.sale_item_id')
    ->where('sale_items.product_id', 692)
    ->select('sale_item_batches.*', 'sale_items.sale_id')
    ->get();
echo json_encode($sib, JSON_PRETTY_PRINT) . "\n\n";

echo "=== DELIVERY NOTES & ITEMS FOR PRODUCT 692 ===\n";
$dnItems = DB::table('delivery_note_items')->where('product_id', 692)->get();
echo json_encode($dnItems, JSON_PRETTY_PRINT) . "\n\n";

