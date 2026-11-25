<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;

$reporting = app(ReportingController::class);

// Find a pure vendor (only Vendor, not Customer)
$pureVendor = \DB::table('vendors')
    ->where('party_type', 'Vendor')   // pure vendor
    ->whereNotNull('id')
    ->first();

echo "=== PURE VENDOR TEST ===\n";
if ($pureVendor) {
    echo "Testing: {$pureVendor->name} (ID: {$pureVendor->id})\n";
    $reqV = new Request(['vendor_id' => $pureVendor->id, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
    $repV = $reporting->fetch_vendor_ledger($reqV)->getData(true);
    echo "Opening: "      . number_format((float)($repV['opening_balance'] ?? 0), 2) . "\n";
    echo "Closing: "      . number_format((float)($repV['closing_balance'] ?? 0), 2) . "\n";
    echo "Transactions: " . count($repV['transactions'] ?? []) . "\n";
    echo "has_twin: "     . (($repV['vendor']['has_twin_customer'] ?? false) ? 'YES (dual)' : 'NO (pure vendor ✓)') . "\n";
} else {
    echo "No pure vendor found (all vendors are dual?)\n";
    // Count by party_type
    $types = \DB::table('vendors')->select('party_type', \DB::raw('count(*) as cnt'))->groupBy('party_type')->get();
    foreach ($types as $t) {
        echo "  party_type='" . $t->party_type . "' => {$t->cnt}\n";
    }
}

echo "\n=== DUAL PARTY (DIAZONE - Vendor 390) ===\n";
$reqV2 = new Request(['vendor_id' => 390, 'start_date' => '2020-01-01', 'end_date' => '2026-12-31']);
$repV2 = $reporting->fetch_vendor_ledger($reqV2)->getData(true);
echo "has_twin: "    . (($repV2['vendor']['has_twin_customer'] ?? false) ? 'YES ✓ (dual)' : 'NO') . "\n";
echo "Opening: "     . number_format((float)($repV2['opening_balance'] ?? 0), 2) . "\n";
echo "Closing: "     . number_format((float)($repV2['closing_balance'] ?? 0), 2) . "\n";
echo "Transactions: " . count($repV2['transactions'] ?? []) . "\n";
