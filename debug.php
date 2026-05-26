<?php

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$emp = App\Models\Hr\Employee::where('first_name', 'like', '%sae%')->first();
if(!$emp) { echo "Emp not found"; exit; }

$payroll = App\Models\Hr\Payroll::where('employee_id', $emp->id)->where('payroll_type', 'monthly')->first();
if(!$payroll) { echo "Payroll not found"; exit; }

$salaryStructure = $emp->activeSalaryStructure ?? $emp->salaryStructure;

$allSales = \App\Models\Sale::where('employee_id', $emp->id)->where('total_net', '>', 0)->get();

$aggSaleTotal  = 0;
$aggMaxComm    = 0;
$aggCustPaid   = 0;
$aggRemaining  = 0;
$liveTotalComm = 0;
$liveCommissionDetails = [];

foreach ($allSales as $sale) {
    $saleTotal            = floatval($sale->total_net);
    $maxCommission        = floatval($sale->total_commission);
    $alreadyPaid          = floatval($sale->commission_paid);

    if ($maxCommission <= 0) {
        if ($salaryStructure && $salaryStructure->commission_tiers && count($salaryStructure->commission_tiers) > 0) {
            $maxCommission = $salaryStructure->calculateTieredCommission($saleTotal);
        } elseif ($salaryStructure && $salaryStructure->commission_percentage > 0) {
            $maxCommission = ($saleTotal * $salaryStructure->commission_percentage) / 100;
        }
    }

    if ($maxCommission <= 0) {
        continue;
    }

    // Compute payments for this sale up to the end of payroll month
    $endOfMonth = \Carbon\Carbon::parse($payroll->month . '-01')->endOfMonth()->format('Y-m-d');
    $totalPaymentsOnSale = DB::table('customer_payments')
        ->where('sale_id', $sale->id)
        ->where('payment_date', '<=', $endOfMonth)
        ->sum('amount');

    $paymentRatio = $saleTotal > 0 ? min(1, $totalPaymentsOnSale / $saleTotal) : 0;
    $earnedSoFar = round($paymentRatio * $maxCommission, 2);
    $newCommission = max(0, $earnedSoFar - $alreadyPaid);
    $remaining = max(0, $maxCommission - ($alreadyPaid + $newCommission));

    $aggSaleTotal += $saleTotal;
    $aggMaxComm   += $maxCommission;
    $aggCustPaid  += $totalPaymentsOnSale;
    $aggRemaining += $remaining;
    $liveTotalComm += $newCommission;

    $liveCommissionDetails[] = [
        'name'        => "Sale #{$sale->invoice_no}",
        'amount'      => $newCommission,
        'description' => "Sale {$sale->invoice_no}: " . number_format($saleTotal, 2) . " total",
        'meta'        => [
            'sale_total'           => $saleTotal,
            'max_commission'       => $maxCommission,
            'customer_paid_total'  => $totalPaymentsOnSale,
            'paid_so_far'          => $alreadyPaid,
            'current_commission'   => $newCommission,
            'remaining_commission' => $remaining,
            'text_desc'            => "Sale {$sale->invoice_no}: " . number_format($saleTotal, 2) . " total, " . round($paymentRatio * 100, 1) . "% paid",
        ],
    ];
}

$commissionMetrics = [
    'is_aggregated'      => true,
    'total_commission'   => $aggMaxComm,
    'paid_so_far'        => max(0, $aggMaxComm - $aggRemaining - $liveTotalComm),
    'current_commission' => $liveTotalComm,
    'remaining_commission' => $aggRemaining,
    'customer_paid_total' => $aggCustPaid,
    'sale_total'         => $aggSaleTotal,
    'payment_ratio'      => $aggSaleTotal > 0 ? round(($aggCustPaid / $aggSaleTotal) * 100, 1) . '%' : 'N/A',
    'customer_name'      => 'Multiple Sales (' . count($liveCommissionDetails) . ')',
];
echo "Metrics:\n";
echo json_encode($commissionMetrics, JSON_PRETTY_PRINT) . "\n";
echo "Details:\n";
echo json_encode($liveCommissionDetails, JSON_PRETTY_PRINT) . "\n";
