<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use App\Services\DualPartyLedgerService;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);

// Find a pure vendor (not a customer)
$pureVendor = \DB::table('vendors')
    ->where(function($q) {
        $q->whereNull('party_type')
          ->orWhere('party_type', 'not like', '%Customer%');
    })
    ->whereNotNull('id')
    ->first();

echo "=== PURE VENDOR TEST ===\n";
if ($pureVendor) {
    echo "Testing: {$pureVendor->vendor_name} (ID: {$pureVendor->id})\n";
    $reqV = new Request(['vendor_id' => $pureVendor->id, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
    $repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);
    echo "Opening: " . number_format((float)($repV['opening_balance'] ?? 0), 2) . "\n";
    echo "Closing: " . number_format((float)($repV['closing_balance'] ?? 0), 2) . "\n";
    echo "Transactions: " . count($repV['transactions'] ?? []) . "\n";
    echo "has_twin_customer: " . (isset($repV['vendor']['has_twin_customer']) ? ($repV['vendor']['has_twin_customer'] ? 'TRUE (dual)' : 'false (pure vendor)') : 'key not found') . "\n";
    
    // Show first few transactions
    foreach (array_slice($repV['transactions'] ?? [], 0, 3) as $i => $tx) {
        echo "  Tx[$i]: Dr=" . number_format((float)($tx['debit'] ?? 0),2) . " Cr=" . number_format((float)($tx['credit'] ?? 0),2) . " Bal=" . number_format((float)($tx['balance'] ?? 0),2) . "\n";
    }
} else {
    echo "No pure vendor found.\n";
}

echo "\n=== DUAL PARTY VENDOR TEST (DIAZONE vendor 390) ===\n";
$reqV2 = new Request(['vendor_id' => 390, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repV2 = $reporting->fetch_vendor_ledger($reqV2)->getData(true);
echo "has_twin_customer: " . (isset($repV2['vendor']['has_twin_customer']) ? ($repV2['vendor']['has_twin_customer'] ? 'TRUE ✓ (dual)' : 'false') : 'key not found') . "\n";
echo "Opening: " . number_format((float)($repV2['opening_balance'] ?? 0), 2) . "\n";
echo "Closing: " . number_format((float)($repV2['closing_balance'] ?? 0), 2) . "\n";
echo "Transactions: " . count($repV2['transactions'] ?? []) . "\n";
