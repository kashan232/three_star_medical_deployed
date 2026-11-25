<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\StockReconciliationService;
use Illuminate\Support\Facades\DB;

echo "=== DRY RUN / TESTING RECONCILIATION FOR PRODUCT 692 ===\n";

DB::beginTransaction();

try {
    $res = StockReconciliationService::reconcile(692);
    echo "Reconciliation result: " . json_encode($res) . "\n\n";

    echo "=== WAREHOUSE STOCKS AFTER RECONCILE ===\n";
    $ws = DB::table('warehouse_stocks')->where('product_id', 692)->get();
    echo json_encode($ws, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== PRODUCT BATCHES AFTER RECONCILE ===\n";
    $pb = DB::table('product_batches')->where('product_id', 692)->get();
    echo json_encode($pb, JSON_PRETTY_PRINT) . "\n\n";

    echo "=== STOCK MOVEMENTS AFTER RECONCILE ===\n";
    $sm = DB::table('stock_movements')->where('product_id', 692)->get();
    echo json_encode($sm, JSON_PRETTY_PRINT) . "\n\n";

    $totalNetPieces = DB::table('warehouse_stocks')->where('product_id', 692)->sum('total_pieces');
    echo "Total Warehouse Stock Pieces: $totalNetPieces Pcs\n";

    // Test product search modal result format
    $searchStock = DB::table('warehouse_stocks')
        ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
        ->where('warehouse_stocks.product_id', 692)
        ->where('warehouses.branch_id', 1)
        ->sum('warehouse_stocks.total_pieces');
    echo "Modal Display Stock: $searchStock Pcs\n";

    // Rollback test
    DB::rollBack();
    echo "\n[Test Completed - Rolled back successfully, database not modified yet]\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during reconcile: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
