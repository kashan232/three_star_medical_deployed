<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

$all = DB::table('products')->get(['id', 'item_name', 'category_id', 'sub_category_id', 'brand_id']);
echo "TOTAL_COUNT:" . count($all) . "\n";
echo "DATA:" . json_encode($all) . "\n";
