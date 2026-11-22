<?php

namespace App\Console\Commands;

use App\Models\Hr\Employee;
use App\Models\Hr\LoanPayment;
use App\Models\Hr\Payroll;
use App\Models\Sale;
use App\Models\VoucherMaster;
use App\Services\VoucherService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RevertPayrollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:revert-payroll 
                            {employee? : Employee name or ID (defaults to Tanveer Hussain)}
                            {month? : Payroll month YYYY-MM (defaults to 2026-11)}
                            {--delete : Permanently delete the payroll record instead of reverting to generated}
                            {--force : Force operation without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revert or delete paid payroll records (including linked vouchers and loan payments)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeSearch = $this->argument('employee') ?? 'Tanveer';
        $month = $this->argument('month') ?? '2026-11';
        $shouldDelete = $this->option('delete');
        $force = $this->option('force');

        $this->info("🔍 Searching for payrolls for Employee: '{$employeeSearch}', Month: '{$month}'...");

        $query = Payroll::with('employee')
            ->where(function ($q) use ($employeeSearch) {
                if (is_numeric($employeeSearch)) {
                    $q->where('employee_id', $employeeSearch);
                } else {
                    $q->whereHas('employee', function ($eq) use ($employeeSearch) {
                        $eq->where('first_name', 'like', "%{$employeeSearch}%")
                           ->orWhere('last_name', 'like', "%{$employeeSearch}%")
                           ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$employeeSearch}%");
                    });
                }
            })
            ->where('month', $month);

        $payrolls = $query->get();

        if ($payrolls->isEmpty()) {
            $this->warn("⚠️  No payroll records found for employee '{$employeeSearch}' in month '{$month}'.");
            
            // Check if employee exists
            $employees = Employee::where('first_name', 'like', "%{$employeeSearch}%")
                ->orWhere('last_name', 'like', "%{$employeeSearch}%")
                ->get();
            if ($employees->isNotEmpty()) {
                $this->info("Employee exists: " . $employees->pluck('full_name')->implode(', '));
            } else {
                $this->warn("No employee matching '{$employeeSearch}' found in database.");
            }
            return 0;
        }

        $this->table(
            ['ID', 'Employee', 'Month', 'Type', 'Status', 'Net Salary', 'Voucher ID', 'Payment Date'],
            $payrolls->map(fn($p) => [
                $p->id,
                $p->employee?->full_name ?? 'N/A',
                $p->month,
                $p->payroll_type,
                $p->status,
                number_format($p->net_salary, 2),
                $p->voucher_id ?? 'None',
                $p->payment_date ?? 'N/A',
            ])
        );

        $action = $shouldDelete ? 'PERMANENTLY DELETE' : 'REVERT TO UNPAID';
        if (! $force && ! $this->confirm("Are you sure you want to {$action} these " . $payrolls->count() . " payroll record(s)?")) {
            $this->info("Operation cancelled.");
            return 0;
        }

        $voucherService = app(VoucherService::class);

        foreach ($payrolls as $payroll) {
            DB::beginTransaction();
            try {
                // 1. Revert/Delete voucher if exists
                if ($payroll->voucher_id) {
                    $voucher = VoucherMaster::find($payroll->voucher_id);
                    if ($voucher) {
                        $this->line("  - Reversing & deleting voucher {$voucher->voucher_no} (ID: {$voucher->id})...");
                        $voucherService->deleteVoucher($voucher);
                    }
                }

                // 2. Reverse Loan Payments
                $loanPayments = LoanPayment::where('payroll_id', $payroll->id)->get();
                foreach ($loanPayments as $lp) {
                    $loan = $lp->loan;
                    if ($loan) {
                        $this->line("  - Reversing loan payment of Rs. {$lp->amount} on Loan #{$loan->id}...");
                        $loan->decrement('paid_amount', $lp->amount);
                        $loan->decrement('installments_paid');
                        if ($loan->status === 'paid' && $loan->paid_amount < $loan->amount) {
                            $loan->update(['status' => 'active']);
                        }
                    }
                    $lp->delete();
                }

                // 3. Reverse Sales Commission
                $commissionDetails = $payroll->details()->where('type', 'commission')->get();
                foreach ($commissionDetails as $cd) {
                    if (preg_match('/(?:Sale\s*#|Invoice\s*#|#)([A-Za-z0-9\-]+)/i', $cd->name . ' ' . $cd->description, $matches)) {
                        $invNo = trim($matches[1]);
                        Sale::where('invoice_no', $invNo)
                            ->decrement('commission_paid', floatval($cd->amount));
                    }
                }

                if ($shouldDelete) {
                    $payroll->details()->delete();
                    $payroll->delete();
                    $this->info("✅ Payroll #{$payroll->id} deleted successfully.");
                } else {
                    $payroll->update([
                        'status'            => 'generated',
                        'paid_amount'       => 0,
                        'payment_date'      => null,
                        'payment_method'    => null,
                        'voucher_id'        => null,
                        'salary_account_id' => null,
                        'payment_notes'     => null,
                    ]);
                    $this->info("✅ Payroll #{$payroll->id} reverted to 'generated' (unpaid) successfully.");
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("❌ Error processing payroll #{$payroll->id}: " . $e->getMessage());
            }
        }

        $this->info("🎉 Done!");
        return 0;
    }
}
