<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = App\Models\Hr\PayrollDetail::where('type', 'commission')->count();
echo "Commission Details Count: " . $count . "\n";

$details = App\Models\Hr\PayrollDetail::where('type', 'commission')->get();
foreach($details as $d) {
    echo "  - ID: {$d->id}, Payroll: {$d->payroll_id}, Amount: {$d->amount}, Name: {$d->name}\n";
}
