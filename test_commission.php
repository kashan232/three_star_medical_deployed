<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sale = \App\Models\Sale::where('invoice_no', 'SIN-0011')->first();
if (!$sale) {
    echo "Sale SIN-0011 not found.\n";
    exit;
}

echo "Sale ID: {$sale->id}\n";
echo "Commission Percentage: {$sale->commission_percentage}\n";
echo "Total Commission: {$sale->total_commission}\n";
echo "Commission Paid: {$sale->commission_paid}\n";

$payroll = \App\Models\Hr\Payroll::where('employee_id', $sale->employee_id)->latest()->first();
if ($payroll) {
    echo "Payroll ID: {$payroll->id}\n";
    echo "Payroll Month: {$payroll->month}\n";
    echo "Payroll Commission: {$payroll->commission}\n";
} else {
    echo "No payroll found.\n";
}

$payments = \App\Models\CustomerPayment::where('sale_id', $sale->id)->get();
echo "Payments Count: " . $payments->count() . "\n";
foreach($payments as $p) {
    echo "Payment: {$p->amount}\n";
}
