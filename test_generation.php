<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

auth()->loginUsingId(1); // Login as admin

$employee = App\Models\Hr\Employee::where('first_name', 'like', '%ishaq%')->first();
$request = new \Illuminate\Http\Request();
$request->merge(['employee_id' => $employee->id, 'month' => '2026-08', 'payroll_type' => 'monthly', 'date' => '2026-08-15']);

$controller = app()->make(App\Http\Controllers\Hr\PayrollController::class);
$response = $controller->generate($request);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getContent() . "\n";

$payroll = App\Models\Hr\Payroll::where('employee_id', $employee->id)->where('month', '2026-08')->first();
echo "Commission after save: " . $payroll->commission . "\n";
$details = App\Models\Hr\PayrollDetail::where('payroll_id', $payroll->id)->where('type', 'commission')->get();
echo "Commission details count: " . $details->count() . "\n";
