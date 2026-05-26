<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = App\Models\PriceLog::with(['product','user'])->get();
echo "Count: " . $logs->count() . "\n";
echo json_encode($logs->toArray(), JSON_PRETTY_PRINT);
