<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payroll = App\Models\Hr\Payroll::first();
if (!$payroll) {
    echo "No payroll found.\n";
    exit;
}

try {
    $detail = App\Models\Hr\PayrollDetail::create([
        'payroll_id' => $payroll->id,
        'type' => 'commission',
        'name' => "Test Commission",
        'amount' => 100,
        'description' => "Test description"
    ]);
    echo "Created detail ID: " . $detail->id . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
