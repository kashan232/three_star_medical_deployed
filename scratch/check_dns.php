<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$dns = DB::table('delivery_notes')->where('sale_id', 9)->get();
echo "Delivery Notes for Sale 9: " . count($dns) . "\n";
echo json_encode($dns, JSON_PRETTY_PRINT) . "\n\n";

$allDns = DB::table('delivery_notes')->get();
echo "All Delivery Notes: " . count($allDns) . "\n";
foreach ($allDns as $d) {
    echo "DN ID: {$d->id} | DC No: {$d->dc_no} | Sale ID: {$d->sale_id} | Status: {$d->status}\n";
}
