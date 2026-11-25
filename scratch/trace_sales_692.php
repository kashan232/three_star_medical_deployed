<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$productId = 692;

$items = DB::table('sale_items')->where('product_id', $productId)->get();

echo "=== SALE ITEMS FOR PRODUCT $productId ===\n";
foreach ($items as $item) {
    $sale = DB::table('sales')->where('id', $item->sale_id)->first();
    echo "Item ID: {$item->id} | Sale ID: {$item->sale_id} | Inv: {$sale->invoice_no} | Status: {$sale->sale_status} | Mode: {$sale->mode}\n";
    echo "  Qty: {$item->qty} | Total Pieces: {$item->total_pieces} | WH: {$item->warehouse_id}\n";
    echo "  Sale Created: {$sale->created_at} | Updated: {$sale->updated_at}\n";
    
    // Batches
    $batches = DB::table('sale_item_batches')->where('sale_item_id', $item->id)->get();
    echo "  Batches: " . json_encode($batches) . "\n";
    
    // Movements for this sale
    $movs = DB::table('stock_movements')->where('ref_id', $sale->id)->where('product_id', $productId)->get();
    echo "  Movements for this sale & product:\n";
    foreach ($movs as $m) {
        echo "    Mov ID: {$m->id} | Type: {$m->type} | RefType: {$m->ref_type} | Qty: {$m->qty} | Note: {$m->note} | Created: {$m->created_at}\n";
    }
    echo "--------------------------------------------------------\n";
}

echo "\n=== ALL MOVEMENTS FOR PRODUCT $productId ===\n";
$allMovs = DB::table('stock_movements')->where('product_id', $productId)->get();
foreach ($allMovs as $m) {
    echo "Mov ID: {$m->id} | Type: {$m->type} | RefType: {$m->ref_type} | RefID: {$m->ref_id} | Qty: {$m->qty} | Note: {$m->note} | Created: {$m->created_at}\n";
}

echo "\n=== WAREHOUSE STOCKS FOR PRODUCT $productId ===\n";
$whStocks = DB::table('warehouse_stocks')->where('product_id', $productId)->get();
foreach ($whStocks as $ws) {
    echo "WS ID: {$ws->id} | WH ID: {$ws->warehouse_id} | UOM ID: {$ws->uom_id} | Qty: {$ws->quantity} | Total Pieces: {$ws->total_pieces} | Created: {$ws->created_at} | Updated: {$ws->updated_at}\n";
}

echo "\n=== PRODUCT BATCHES FOR PRODUCT $productId ===\n";
$batches = DB::table('product_batches')->where('product_id', $productId)->get();
foreach ($batches as $b) {
    echo "Batch ID: {$b->id} | Batch#: {$b->batch_number} | Qty Rec: {$b->qty_received} | Qty Rem: {$b->qty_remaining} | Status: {$b->status} | Updated: {$b->updated_at}\n";
}
