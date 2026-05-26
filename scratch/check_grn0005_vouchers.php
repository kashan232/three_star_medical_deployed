<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\VoucherMaster::where('remarks', 'like', '%GRN-0005%')->count();
echo "Vouchers for GRN-0005: " . $count . "\n";
foreach (\App\Models\VoucherMaster::where('remarks', 'like', '%GRN-0005%')->get() as $v) {
    echo "ID: " . $v->id . " | Type: " . $v->voucher_type . " | Remarks: " . $v->remarks . "\n";
}
