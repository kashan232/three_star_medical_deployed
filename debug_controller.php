<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Hr\Payroll;
use App\Models\Hr\Employee;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emp = Employee::where('first_name', 'like', '%sae%')->first();
if(!$emp) { echo "Emp not found"; exit; }

$payroll = Payroll::where('employee_id', $emp->id)->where('payroll_type', 'monthly')->first();
if(!$payroll) { echo "Payroll not found"; exit; }

// Use Auth facade to login as admin to bypass auth check in details()
$admin = \App\Models\User::first();
auth()->login($admin);

$controller = app()->make(\App\Http\Controllers\Hr\PayrollController::class);
$response = $controller->details($payroll->id);

file_put_contents(__DIR__.'/debug_output.json', $response->getContent());
echo "Saved to debug_output.json\n";
