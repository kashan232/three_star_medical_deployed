<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SALE #SIN-0008 (id: 9) and all sales with product 692 ===\n";
$sales = DB::table('sales')->whereIn('id', [4, 9, 12, 14])->get();
echo json_encode($sales, JSON_PRETTY_PRINT) . "\n\n";

$saleItems = DB::table('sale_items')->where('product_id', 692)->get();
echo "=== SALE ITEMS FOR PRODUCT 692 ===\n";
echo json_encode($saleItems, JSON_PRETTY_PRINT) . "\n\n";

echo "=== ALL SALES RECENT ===\n";
$allRecentSales = DB::table('sales')->latest('id')->limit(10)->get();
echo json_encode($allRecentSales, JSON_PRETTY_PRINT) . "\n\n";

echo "=== ALL STOCK MOVEMENTS FOR PRODUCT 692 ===\n";
$movements = DB::table('stock_movements')->where('product_id', 692)->get();
echo json_encode($movements, JSON_PRETTY_PRINT) . "\n\n";

