<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = App\Models\Sale::where('invoice_no', 'SIN-20260815204416')->first();
echo "Sale Commission Paid: " . $sale->commission_paid . "\n";
echo "Sale Total Commission: " . $sale->total_commission . "\n";
$payroll = App\Models\Hr\Payroll::where('employee_id', $sale->employee_id)->where('month', '2026-08')->first();
echo "Payroll Commission: " . ($payroll ? $payroll->commission : 'None') . "\n";
$details = App\Models\Hr\PayrollDetail::where('payroll_id', $payroll->id)->get();
echo "Payroll Details Count: " . count($details) . "\n";
foreach($details as $d) {
    echo "  - " . $d->type . ": " . $d->amount . " - " . $d->name . "\n";
}
