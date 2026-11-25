<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$deleted = DB::table('warehouse_stocks')->where('id', 1532)->where('total_pieces', 0)->delete();
echo "Duplicate row 1532 deleted ($deleted rows affected)\n";

$remaining = DB::table('warehouse_stocks')->where('product_id', 692)->get();
echo "Remaining warehouse_stocks for product 692:\n" . json_encode($remaining, JSON_PRETTY_PRINT) . "\n";
