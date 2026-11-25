<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT TABLE_NAME, COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME IN ('warehouse_stock_id', 'stock_id') AND TABLE_SCHEMA = DATABASE()");

echo "Tables referencing warehouse stock:\n";
foreach ($tables as $t) {
    echo "Table: {$t->TABLE_NAME}, Column: {$t->COLUMN_NAME}\n";
}
