<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);

// DIAZONE Customer 404
$reqC = new Request(['customer_id' => 404, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repC = $reporting->fetch_customer_ledger($reqC)->getData(true);

echo "=== CUSTOMER LEDGER KEYS ===\n";
echo implode(', ', array_keys($repC)) . "\n\n";

echo "closing_balance: " . ($repC['closing_balance'] ?? 'NOT SET') . "\n";
echo "balance_label: "   . ($repC['balance_label']   ?? 'NOT SET') . "\n";
echo "is_dual_party: "   . (isset($repC['is_dual_party']) ? var_export($repC['is_dual_party'], true) : 'NOT SET') . "\n";
echo "opening_balance: " . ($repC['opening_balance'] ?? 'NOT SET') . "\n";
echo "total_debit: "     . ($repC['total_debit']     ?? 'NOT SET') . "\n";
echo "total_credit: "    . ($repC['total_credit']    ?? 'NOT SET') . "\n";

// Find transaction key
$txKeys = array_filter(array_keys($repC), fn($k) => str_contains(strtolower($k), 'entr') || str_contains(strtolower($k), 'trans') || str_contains(strtolower($k), 'ledger') || str_contains(strtolower($k), 'row') || str_contains(strtolower($k), 'data'));
echo "\nPossible TX keys: " . implode(', ', $txKeys) . "\n";
foreach ($txKeys as $k) {
    if (is_array($repC[$k])) {
        echo "  [{$k}] count: " . count($repC[$k]) . "\n";
        if (count($repC[$k]) > 0) {
            echo "  First row keys: " . implode(', ', array_keys($repC[$k][0])) . "\n";
            print_r($repC[$k][0]);
            break;
        }
    }
}

echo "\n\n=== ALL TOP-LEVEL DATA ===\n";
foreach ($repC as $k => $v) {
    if (!is_array($v)) echo "  $k => $v\n";
    else echo "  $k => [array, count=" . count($v) . "]\n";
}
