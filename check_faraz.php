<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$employee = App\Models\Hr\Employee::where('first_name', 'like', '%faraz%')->with('designation')->first();
if ($employee) {
    echo "Employee Found: " . $employee->full_name . "\n";
    echo "Status: " . $employee->status . "\n";
    echo "Active Scope Check: " . (App\Models\Hr\Employee::active()->where('id', $employee->id)->exists() ? 'Yes' : 'No') . "\n";
    if ($employee->designation) {
        echo "Designation: " . $employee->designation->name . "\n";
        echo "is_sale_officer: " . $employee->designation->is_sale_officer . "\n";
    } else {
        echo "Designation: NONE\n";
    }
} else {
    echo "Employee not found.\n";
}
