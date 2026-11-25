<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);

echo "============================================\n";
echo "  DUAL PARTY LEDGER - FULL VERIFICATION\n";
echo "============================================\n\n";

$tests = [
    ['label' => 'DIAZONE',        'cid' => 404, 'vid' => 390],
    ['label' => 'TALHA BROTHERS', 'cid' => 543, 'vid' => 458],
];

foreach ($tests as $t) {
    echo "---------- {$t['label']} ----------\n";

    $reqC = new Request(['customer_id' => $t['cid'], 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
    $repC = $reporting->fetch_customer_ledger($reqC)->getData(true);

    $reqV = new Request(['vendor_id' => $t['vid'], 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
    $repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);

    $cBal = (float)($repC['closing_balance'] ?? 0);
    $vBal = (float)($repV['closing_balance'] ?? 0);
    $cTx  = count($repC['transactions'] ?? []);
    $vTx  = count($repV['transactions'] ?? []);
    $cOp  = (float)($repC['opening_balance'] ?? 0);
    $vOp  = (float)($repV['opening_balance'] ?? 0);

    echo "CUSTOMER: OB=" . number_format($cOp,2) . "  Closing=" . number_format($cBal,2) . "  Rows={$cTx}\n";
    echo "VENDOR  : OB=" . number_format($vOp,2) . "  Closing=" . number_format($vBal,2) . "  Rows={$vTx}\n";

    // Compare transactions row by row
    $cTxArr = $repC['transactions'] ?? [];
    $vTxArr = $repV['transactions'] ?? [];
    $txMatch = (count($cTxArr) === count($vTxArr));
    if ($txMatch) {
        foreach ($cTxArr as $i => $row) {
            $vRow = $vTxArr[$i] ?? [];
            if (abs((float)($row['balance'] ?? 0) - (float)($vRow['balance'] ?? 0)) > 0.02) {
                $txMatch = false;
                echo "  ROW MISMATCH at index $i: Cust balance=" . ($row['balance'] ?? '?') . " Vend balance=" . ($vRow['balance'] ?? '?') . "\n";
                break;
            }
        }
    }

    $balMatch = abs($cBal - $vBal) < 0.02;
    $opMatch  = abs($cOp - $vOp) < 0.02;

    echo "Opening  Match: " . ($opMatch  ? "✓" : "✗ MISMATCH") . "\n";
    echo "TX Count Match: " . ($txMatch  ? "✓ ({$cTx} rows same)" : "✗ C={$cTx} vs V={$vTx}") . "\n";
    echo "Closing  Match: " . ($balMatch ? "✓ " . number_format($cBal,2) : "✗  C=" . number_format($cBal,2) . " V=" . number_format($vBal,2)) . "\n\n";

    // Print all vendor transactions to show what's being returned
    echo "VENDOR TRANSACTIONS:\n";
    foreach ($vTxArr as $i => $row) {
        echo "  [{$i}] " . ($row['date'] ?? '') . " | " . ($row['invoice'] ?? '') . " | Dr=" . number_format((float)($row['debit'] ?? 0),2) . " Cr=" . number_format((float)($row['credit'] ?? 0),2) . " Bal=" . number_format((float)($row['balance'] ?? 0),2) . "\n";
    }
    echo "\n";
}
