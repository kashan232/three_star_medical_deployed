<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\StockReconciliationService;
use Illuminate\Support\Facades\DB;

echo "=== EXECUTING STOCK RECONCILIATION FOR PRODUCT 692 (HCV DEVICE) ===\n";

DB::beginTransaction();

try {
    $res = StockReconciliationService::reconcile(692);
    echo "Reconciliation result: " . json_encode($res) . "\n\n";

    $totalNetPieces = DB::table('warehouse_stocks')->where('product_id', 692)->sum('total_pieces');
    echo "Total Warehouse Stock Pieces: $totalNetPieces Pcs\n";

    $searchStock = DB::table('warehouse_stocks')
        ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
        ->where('warehouse_stocks.product_id', 692)
        ->where('warehouses.branch_id', 1)
        ->sum('warehouse_stocks.total_pieces');
    echo "Product Modal Display Stock: $searchStock Pcs\n";

    DB::commit();
    echo "\n[SUCCESS: Database committed successfully!]\n";
} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during reconcile: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
