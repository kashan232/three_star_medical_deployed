<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$v = \DB::table('vendors')->whereNull('party_type')->first();
echo "Vendor cols: " . json_encode(array_keys((array)$v)) . "\n";
$v2 = \DB::table('vendors')->first();
echo "First vendor: " . json_encode((array)$v2) . "\n";
