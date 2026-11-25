<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Sales: ' . App\Models\Sale::count() . PHP_EOL;
echo 'Purchases: ' . App\Models\Purchase::count() . PHP_EOL;
echo 'CustomerLedger: ' . App\Models\CustomerLedger::count() . PHP_EOL;
echo 'VendorLedger: ' . App\Models\VendorLedger::count() . PHP_EOL;
echo 'JournalEntry: ' . App\Models\JournalEntry::count() . PHP_EOL;
echo 'VoucherMaster: ' . App\Models\VoucherMaster::count() . PHP_EOL;
