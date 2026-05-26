<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$batch = DB::table('product_batches')->where('id', 4)->first();
if ($batch) {
    echo "Found batch. Product ID: {$batch->product_id}\n";
    $itemId = DB::table('purchase_items')->insertGetId([
        'purchase_id' => 4,
        'product_id' => $batch->product_id,
        'warehouse_id' => $batch->warehouse_id,
        'qty' => 1,
        'price' => 100,
        'line_total' => 100,
        'batch_no' => $batch->batch_number,
        'mfg_date' => $batch->mfg_date,
        'exp_date' => $batch->exp_date,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    DB::table('product_batches')->where('id', $batch->id)->update([
        'purchase_item_id' => $itemId,
        'status' => 'held'
    ]);
    
    // Also mark the purchase 4 back to draft so the user can see/edit it again
    DB::table('purchases')->where('id', 4)->update(['status_purchase' => 'draft']);
    
    echo "Restored Purchase 4 item and set GRN back to draft!\n";
}
