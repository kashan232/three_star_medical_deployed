<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$product = DB::table('products')->where('item_code', 'A3')->orWhere('item_name', 'like', '%HCV%')->first();
if (!$product) {
    echo "Product not found!\n";
    exit;
}

echo "=== PRODUCT ===\n";
echo json_encode($product, JSON_PRETTY_PRINT) . "\n\n";

$productId = $product->id;

// Check tables with product_id
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME = 'product_id' AND TABLE_SCHEMA = DATABASE()");
echo "=== TABLES WITH product_id ===\n";
foreach ($tables as $t) {
    $tbl = $t->TABLE_NAME;
    $cnt = DB::table($tbl)->where('product_id', $productId)->count();
    if ($cnt > 0) {
        echo "Table: $tbl (count: $cnt)\n";
        $records = DB::table($tbl)->where('product_id', $productId)->get();
        echo json_encode($records, JSON_PRETTY_PRINT) . "\n";
    }
}
