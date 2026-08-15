<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find missing commissions
$sales = App\Models\Sale::where('commission_paid', '>', 0)->get();
foreach ($sales as $sale) {
    $employee = App\Models\Hr\Employee::find($sale->employee_id);
    if (!$employee) continue;

    // Find the payroll for the month of the sale
    $month = \Carbon\Carbon::parse($sale->sale_date)->format('Y-m');
    
    $payroll = App\Models\Hr\Payroll::where('employee_id', $employee->id)
        ->where('month', $month)
        ->where('payroll_type', 'monthly')
        ->first();

    if ($payroll) {
        // Check if there is a commission detail for this amount
        $hasCommission = App\Models\Hr\PayrollDetail::where('payroll_id', $payroll->id)
            ->where('type', 'commission')
            ->where('amount', $sale->commission_paid)
            ->exists();

        if (!$hasCommission) {
            echo "Missing commission detail for Sale ID {$sale->id}, Employee {$employee->full_name}. Paid: {$sale->commission_paid}. Restoring...\n";
            App\Models\Hr\PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'type' => 'commission',
                'name' => "Sales Commission (Sale #{$sale->invoice_no})",
                'amount' => $sale->commission_paid,
                'description' => "Invoice #{$sale->invoice_no}: Restored Commission",
            ]);
            
            // Re-sum
            $totalComm = App\Models\Hr\PayrollDetail::where('payroll_id', $payroll->id)
                ->where('type', 'commission')
                ->sum('amount');
            
            $payroll->commission = $totalComm;
            $payroll->gross_salary = $payroll->basic_salary + $payroll->allowances + $payroll->manual_allowances + $totalComm;
            $payroll->net_salary = max(0, $payroll->gross_salary - $payroll->deductions - $payroll->attendance_deductions - $payroll->manual_deductions);
            $payroll->save();
        }
    }
}

echo "Done restoring missing commissions.\n";
