<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "VendorLedger count: " . \App\Models\VendorLedger::count() . "\n";
echo "Purchase count: " . \App\Models\Purchase::count() . "\n";
echo "JournalEntry count: " . \App\Models\JournalEntry::count() . "\n";
echo "VoucherMaster count: " . \App\Models\VoucherMaster::count() . "\n";
