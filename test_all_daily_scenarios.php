<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Hr\Employee;
use App\Models\Hr\SalaryStructure;
use App\Models\Hr\Attendance;
use App\Models\Hr\Payroll;
use App\Models\Hr\PayrollDetail;
use App\Models\Hr\Leave;
use App\Models\Hr\Loan;
use App\Models\Hr\LoanPayment;
use App\Models\Account;
use App\Services\PayrollCalculationService;
use App\Http\Controllers\Hr\PayrollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "====================================================\n";
echo "   COMPREHENSIVE DAILY SALARY & PAYROLL TEST SUITE   \n";
echo "====================================================\n\n";

$service = app(PayrollCalculationService::class);

// Let's create or use a dedicated test employee: e.g. Employee 4 (TALHA ZAFAR ALVI) or Employee 5
$testEmp = Employee::find(4);
if (!$testEmp) {
    echo "Employee 4 not found!\n";
    exit(1);
}
echo "Testing with Employee: {$testEmp->full_name} (ID: {$testEmp->id})\n";

// ── STEP 1: CREATE A PER-DAY SALARY STRUCTURE ──
echo "\n--- STEP 1: Create 'PER DAY' Salary Structure Template ---\n";
// Check if template exists or create fresh
$template = SalaryStructure::whereNull('employee_id')
    ->where('name', 'PER DAY TEST')
    ->first();

if (!$template) {
    $template = SalaryStructure::create([
        'name' => 'PER DAY TEST',
        'employee_id' => null,
        'salary_type' => 'salary',
        'base_salary' => 0,
        'daily_wages' => 1000.00,
        'use_daily_wages' => true,
        'commission_percentage' => null,
        'leave_salary_per_day' => 1000.00,
        'absent_deduction_type' => 'manual',
        'carry_forward_deductions' => true,
        'attendance_deduction_policy' => [
            'late_rules' => [
                ['min_minutes' => 15, 'max_minutes' => 30, 'type' => 'fixed', 'amount' => 100],
                ['min_minutes' => 31, 'max_minutes' => 60, 'type' => 'fixed', 'amount' => 200],
                ['min_minutes' => 61, 'max_minutes' => null, 'type' => 'percentage', 'amount' => 50], // 50% of daily wage
            ],
            'early_rules' => [
                ['min_minutes' => 15, 'max_minutes' => 30, 'type' => 'fixed', 'amount' => 100],
                ['min_minutes' => 31, 'max_minutes' => null, 'type' => 'fixed', 'amount' => 250],
            ]
        ],
    ]);
    echo "Created Template ID: {$template->id} ('{$template->name}')\n";
} else {
    $template->update([
        'daily_wages' => 1000.00,
        'use_daily_wages' => true,
        'carry_forward_deductions' => true,
        'attendance_deduction_policy' => [
            'late_rules' => [
                ['min_minutes' => 15, 'max_minutes' => 30, 'type' => 'fixed', 'amount' => 100],
                ['min_minutes' => 31, 'max_minutes' => 60, 'type' => 'fixed', 'amount' => 200],
                ['min_minutes' => 61, 'max_minutes' => null, 'type' => 'percentage', 'amount' => 50],
            ],
            'early_rules' => [
                ['min_minutes' => 15, 'max_minutes' => 30, 'type' => 'fixed', 'amount' => 100],
                ['min_minutes' => 31, 'max_minutes' => null, 'type' => 'fixed', 'amount' => 250],
            ]
        ],
    ]);
    echo "Found & Updated Template ID: {$template->id}\n";
}

// ── STEP 2: ASSIGN SALARY STRUCTURE TO EMPLOYEE ──
echo "\n--- STEP 2: Assign Salary Structure to Employee {$testEmp->id} ---\n";
// Remove any previous assignment
SalaryStructure::where('employee_id', $testEmp->id)->delete();

$assignedStruct = SalaryStructure::create(array_merge(
    $template->toArray(),
    [
        'employee_id' => $testEmp->id,
        'parent_structure_id' => $template->id,
        'effective_date' => '2026-11-01',
    ]
));
unset($assignedStruct['id']);
echo "Assigned Structure ID: {$assignedStruct->id} to Employee {$testEmp->id}\n";
$testEmp->refresh();
echo "Verified Active Structure: " . ($testEmp->activeSalaryStructure ? $testEmp->activeSalaryStructure->name : 'NONE') . "\n";

// Reset pending deductions
$testEmp->update(['pending_deductions' => 0]);

// Clean up any test payrolls, attendances, leaves, loans for test dates
$testDates = ['2026-11-21', '2026-11-22', '2026-11-23', '2026-11-24', '2026-11-25'];
Payroll::where('employee_id', $testEmp->id)->whereIn('month', $testDates)->delete();
Attendance::where('employee_id', $testEmp->id)->whereIn('date', $testDates)->delete();
Leave::where('employee_id', $testEmp->id)->whereIn('start_date', $testDates)->delete();
Loan::where('employee_id', $testEmp->id)->where('notes', 'TEST_LOAN')->delete();

// ── TEST 1: NORMAL FULL DAY (PRESENT, NO LATE/EARLY) ──
echo "\n--- TEST 1: Normal Full Day (Present, No Late/Early) ---\n";
$att1 = Attendance::create([
    'employee_id' => $testEmp->id,
    'date' => '2026-11-21',
    'status' => 'present',
    'clock_in' => '09:00:00',
    'clock_out' => '18:00:00',
    'is_late' => false,
    'late_minutes' => 0,
    'is_early_leave' => false,
    'early_leave_minutes' => 0,
    'total_hours' => 9.0,
]);
$calc1 = $service->calculateDailyPayroll($testEmp, $att1);
echo "Earning: {$calc1['basic_salary']}, Deduction: {$calc1['attendance_deductions']}, Net: {$calc1['net_salary']}\n";
assert($calc1['basic_salary'] == 1000, "Test 1 Failed: basic_salary should be 1000");
assert($calc1['net_salary'] == 1000, "Test 1 Failed: net_salary should be 1000");
assert($calc1['attendance_deductions'] == 0, "Test 1 Failed: deductions should be 0");
echo "✅ TEST 1 PASSED!\n";

// ── TEST 2: LATE CHECK-IN & EARLY LEAVE RULES DEDUCTIONS ──
echo "\n--- TEST 2: Late Check-in (20m -> Rs.100) + Early Leave (45m -> Rs.250) ---\n";
$att2 = Attendance::create([
    'employee_id' => $testEmp->id,
    'date' => '2026-11-22',
    'status' => 'present',
    'clock_in' => '09:20:00',
    'clock_out' => '17:15:00',
    'is_late' => true,
    'late_minutes' => 20,
    'is_early_leave' => true,
    'early_leave_minutes' => 45,
    'total_hours' => 7.9,
]);
$calc2 = $service->calculateDailyPayroll($testEmp, $att2);
echo "Earning: {$calc2['basic_salary']}, Deductions: {$calc2['attendance_deductions']}, Net: {$calc2['net_salary']}\n";
echo "Deduction breakdown:\n";
foreach ($calc2['deduction_details'] as $d) {
    echo "  - {$d['name']}: Rs. {$d['amount']}\n";
}
// Late 20m falls into rule 15-30m: Rs. 100
// Early 45m falls into rule 31m+: Rs. 250
// Total deductions: Rs. 350 -> Net: 1000 - 350 = 650
assert($calc2['attendance_deductions'] == 350, "Test 2 Failed: deductions should be 350");
assert($calc2['net_salary'] == 650, "Test 2 Failed: net_salary should be 650");
echo "✅ TEST 2 PASSED!\n";

// ── TEST 3: PERCENTAGE LATE DEDUCTION (>60m -> 50% = Rs. 500) ──
echo "\n--- TEST 3: Percentage Late Rule (>60m -> 50% = Rs. 500) ---\n";
$att3 = Attendance::create([
    'employee_id' => $testEmp->id,
    'date' => '2026-11-23',
    'status' => 'late',
    'clock_in' => '10:15:00',
    'clock_out' => '18:00:00',
    'is_late' => true,
    'late_minutes' => 75,
    'is_early_leave' => false,
    'early_leave_minutes' => 0,
    'total_hours' => 7.75,
]);
$calc3 = $service->calculateDailyPayroll($testEmp, $att3);
echo "Earning: {$calc3['basic_salary']}, Deductions: {$calc3['attendance_deductions']}, Net: {$calc3['net_salary']}\n";
assert($calc3['attendance_deductions'] == 500, "Test 3 Failed: deductions should be 500 (50% of 1000)");
assert($calc3['net_salary'] == 500, "Test 3 Failed: net_salary should be 500");
echo "✅ TEST 3 PASSED!\n";

// ── TEST 4: CARRY FORWARD DEDUCTION SCENARIO ──
echo "\n--- TEST 4: Carry Forward Deductions Scenario ---\n";
// Suppose employee has prior pending_deductions = 600
// Today employee is late >60m (50% = 500) + early 45m (250) = 750 day deduction
// Total deduction = 750 + 600 pending = 1350!
// Since day earning is 1000, net salary is 0, and 350 is carried forward to next day!
$testEmp->update(['pending_deductions' => 600]);
$calc4 = $service->calculateDailyPayroll($testEmp, $att2); // att2 has 350 day deduction
// Total deduction = 350 + 600 = 950 <= 1000 -> Net: 50, new pending: 0
echo "Case 4A (Deductions 950 <= 1000): Net: {$calc4['net_salary']}, Carried forward to next: {$calc4['carried_forward_to_next']}\n";
assert($calc4['net_salary'] == 50, "Test 4A Failed: net should be 50");
assert($calc4['carried_forward_to_next'] == 0, "Test 4A Failed: pending should be 0");

// Case 4B: pending 800 + day deduction 350 = 1150 > 1000!
$testEmp->update(['pending_deductions' => 800]);
$calc4b = $service->calculateDailyPayroll($testEmp, $att2);
echo "Case 4B (Deductions 1150 > 1000): Net: {$calc4b['net_salary']}, Carried forward to next: {$calc4b['carried_forward_to_next']}\n";
assert($calc4b['net_salary'] == 0, "Test 4B Failed: net should be 0");
assert($calc4b['carried_forward_to_next'] == 150, "Test 4B Failed: carry forward should be 150");
echo "✅ TEST 4 PASSED!\n";

// Reset pending deductions
$testEmp->update(['pending_deductions' => 0]);

// ── TEST 5: LEAVE SCENARIOS (PAID LEAVE VS UNPAID LEAVE) ──
echo "\n--- TEST 5: Leave Scenarios ---\n";
// Case 5A: Approved PAID Leave (deduct_salary = false)
$leavePaid = Leave::create([
    'employee_id' => $testEmp->id,
    'leave_type' => 'Casual',
    'start_date' => '2026-11-24',
    'end_date' => '2026-11-24',
    'status' => 'approved',
    'deduct_salary' => false,
    'reason' => 'Family event',
]);
$attLeave = Attendance::create([
    'employee_id' => $testEmp->id,
    'date' => '2026-11-24',
    'status' => 'leave',
]);
$calc5a = $service->calculateDailyPayroll($testEmp, $attLeave);
echo "Case 5A (Approved Paid Leave): Earning: {$calc5a['basic_salary']}, Deduction: {$calc5a['attendance_deductions']}, Net: {$calc5a['net_salary']}\n";
assert($calc5a['basic_salary'] == 1000, "Test 5A Failed: paid leave earning should be 1000");
assert($calc5a['attendance_deductions'] == 0, "Test 5A Failed: deductions should be 0");
assert($calc5a['net_salary'] == 1000, "Test 5A Failed: net should be 1000");

// Case 5B: Approved UNPAID Leave (deduct_salary = true)
$leaveUnpaid = Leave::create([
    'employee_id' => $testEmp->id,
    'leave_type' => 'Unpaid',
    'start_date' => '2026-11-25',
    'end_date' => '2026-11-25',
    'status' => 'approved',
    'deduct_salary' => true,
    'reason' => 'Personal work',
]);
$attUnpaid = Attendance::create([
    'employee_id' => $testEmp->id,
    'date' => '2026-11-25',
    'status' => 'leave',
]);
$calc5b = $service->calculateDailyPayroll($testEmp, $attUnpaid);
echo "Case 5B (Approved Unpaid Leave): Earning: {$calc5b['basic_salary']}, Deduction: {$calc5b['attendance_deductions']}, Net: {$calc5b['net_salary']}\n";
assert($calc5b['basic_salary'] == 0, "Test 5B Failed: unpaid leave earning should be 0");
assert($calc5b['net_salary'] == 0, "Test 5B Failed: unpaid leave net should be 0");
echo "✅ TEST 5 PASSED!\n";

// ── TEST 6: LOAN DEDUCTION SCENARIO ──
echo "\n--- TEST 6: Active Loan Deduction Scenario ---\n";
// Create a loan of Rs. 3,000 for this employee, installment Rs. 200
$testLoan = Loan::create([
    'employee_id' => $testEmp->id,
    'loan_type' => 'salary_deduction',
    'amount' => 3000.00,
    'installment_amount' => 200.00,
    'total_installments' => 15,
    'installments_paid' => 0,
    'paid_amount' => 0.00,
    'start_month' => '2026-11',
    'status' => 'approved',
    'notes' => 'TEST_LOAN',
]);
echo "Created Test Loan #{$testLoan->id} (Amount: 3000, Installment: 200)\n";

// Calculate daily payroll for 2026-11-21 (att1):
$calc6 = $service->calculateDailyPayroll($testEmp, $att1);
echo "Earning: {$calc6['basic_salary']}, Loan Deduction: {$calc6['deductions']}, Net: {$calc6['net_salary']}\n";
assert($calc6['deductions'] == 200, "Test 6 Failed: loan deduction should be 200");
assert($calc6['net_salary'] == 800, "Test 6 Failed: net salary should be 800 (1000 - 200)");
echo "Loan details in breakdown:\n";
foreach ($calc6['deduction_details'] as $dd) {
    echo "  - {$dd['name']}: Rs. {$dd['amount']}\n";
}
echo "✅ TEST 6 PASSED!\n";

// ── TEST 7: GENERATE DAILY PAYROLL RECORD IN DATABASE & SAVE DETAILS ──
echo "\n--- TEST 7: Full Generation, Detail Persistence & Loan Balance Update ---\n";
$payroll = Payroll::create(array_merge(
    ['employee_id' => $testEmp->id, 'auto_generated' => false],
    \Illuminate\Support\Arr::except($calc6, ['allowance_details', 'deduction_details', 'new_pending_deductions'])
));
echo "Created Payroll #{$payroll->id} (Month: {$payroll->month}, Type: {$payroll->payroll_type}, Net: {$payroll->net_salary})\n";

$service->savePayrollDetails($payroll, $calc6['allowance_details'], $calc6['deduction_details']);
$savedDetails = PayrollDetail::where('payroll_id', $payroll->id)->get();
echo "Saved Payroll Details Count: {$savedDetails->count()}\n";
foreach ($savedDetails as $sd) {
    echo "  - Type: {$sd->type}, Name: {$sd->name}, Amount: {$sd->amount}\n";
}
assert($savedDetails->count() > 0, "Test 7 Failed: details not saved");

// Check that LoanPayment was automatically recorded and loan paid_amount was updated!
$testLoan->refresh();
echo "Loan Status after Payroll Detail Save: Paid: Rs. {$testLoan->paid_amount}, Remaining: Rs. {$testLoan->remaining_amount}, Paid Installments: {$testLoan->installments_paid}\n";
assert($testLoan->paid_amount == 200, "Test 7 Failed: loan paid amount should be 200");
echo "✅ TEST 7 PASSED!\n";

// ── TEST 8: PROCESS PAYMENT (MARK PAID) ──
echo "\n--- TEST 8: Process Payment (Mark as Paid) ---\n";
$cashAccount = Account::where('status', 1)->first();
$payrollController = app(PayrollController::class);
$user = \App\Models\User::first();
auth()->login($user);

$payRequest = new Request([
    'payment_method' => 'cash',
    'cash_account_id' => $cashAccount->id,
    'payment_date' => '2026-11-22',
    'notes' => 'Test daily payroll payment',
]);

$response = $payrollController->processPayment($payRequest, $payroll);
$payroll->refresh();
echo "Payroll Status: {$payroll->status}, Paid Amount: {$payroll->paid_amount}, Voucher ID: {$payroll->voucher_id}\n";
assert($payroll->status === 'paid', "Test 8 Failed: status should be paid");
assert($payroll->paid_amount == 800, "Test 8 Failed: paid amount should be 800");

// Check loan payment was not duplicated
$loanPaymentsCount = LoanPayment::where('payroll_id', $payroll->id)->count();
echo "Loan payments for this payroll: {$loanPaymentsCount} (must be 1)\n";
assert($loanPaymentsCount === 1, "Test 8 Failed: LoanPayment must not be duplicated");
echo "✅ TEST 8 PASSED!\n";

// ── TEST 9: VERIFY DETAILS API RESPONSE (MODAL POPUP) ──
echo "\n--- TEST 9: Verify Details API (modal popup data) ---\n";
$detailsResponse = $payrollController->details($payroll);
$detailsData = json_decode($detailsResponse->getContent(), true);
echo "Structure Info: " . json_encode($detailsData['structure_info']) . "\n";
echo "Attendance Breakdown: " . json_encode($detailsData['attendance_breakdown']) . "\n";
assert($detailsData['structure_info']['use_daily_wages'] == true, "Test 9 Failed: use_daily_wages should be true");
assert($detailsData['structure_info']['daily_wages'] == 1000, "Test 9 Failed: daily_wages should be 1000");
echo "✅ TEST 9 PASSED!\n";

// ── TEST 10: MONTHLY PAYROLL CALCULATION FOR DAILY WAGE WORKER ──
echo "\n--- TEST 10: Monthly Payroll Calculation for Daily Wage Employee ---\n";
// Let's test calculateMonthlyPayroll for November 2026
$monthlyCalc = $service->calculateMonthlyPayroll($testEmp, '2026-11');
echo "Monthly Base Salary: {$monthlyCalc['basic_salary']}\n";
echo "Monthly Attendance Deductions: {$monthlyCalc['attendance_deductions']}\n";
echo "Monthly Gross: {$monthlyCalc['gross_salary']}\n";
echo "Monthly Net: {$monthlyCalc['net_salary']}\n";
echo "Days Present in Nov: " . ($monthlyCalc['attendance_breakdown']['days_present'] ?? 0) . "\n";
echo "Days Paid Leave in Nov: " . ($monthlyCalc['attendance_breakdown']['days_paid_leave'] ?? 0) . "\n";
$earnedDaysTotal = ($monthlyCalc['attendance_breakdown']['days_present'] ?? 0) + ($monthlyCalc['attendance_breakdown']['days_paid_leave'] ?? 0);
echo "Total Earned Days in Nov: {$earnedDaysTotal} × Rs. 1,000 = Rs. " . ($earnedDaysTotal * 1000) . "\n";
assert($monthlyCalc['basic_salary'] == ($earnedDaysTotal * 1000), "Test 10 Failed: monthly base salary should be days * 1000");
echo "✅ TEST 10 PASSED!\n";

echo "\n====================================================\n";
echo "   🎉 ALL 10 TESTS COMPLETED & PASSED SUCCESSFULLY!  \n";
echo "====================================================\n";
