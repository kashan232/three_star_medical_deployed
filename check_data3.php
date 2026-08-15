<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count1 = DB::table('hr_payroll_details')->count();
echo "Total details: " . $count1 . "\n";
$count2 = DB::table('hr_payroll_details')->where('type', 'commission')->count();
echo "Commission details: " . $count2 . "\n";

$details = DB::table('hr_payroll_details')->get();
foreach($details as $d) {
    echo "ID: {$d->id}, Type: {$d->type}\n";
}
