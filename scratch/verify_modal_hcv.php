<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$branchId = 1;

$p = Product::query()
    ->with(['category_relation', 'sub_category_relation', 'brand', 'packings', 'unit'])
    ->withSum(['warehouseStocks' => function ($q) use ($branchId) {
        $q->whereHas('warehouse', function ($wh) use ($branchId) {
            $wh->where('branch_id', $branchId);
        });
    }], 'total_pieces')
    ->where('item_code', 'A3')
    ->first();

if ($p) {
    $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);
    echo "Product: {$p->item_name}\n";
    echo "Code: {$p->item_code}\n";
    echo "Brand: " . ($p->brand->name ?? '-') . "\n";
    echo "Category: " . ($p->category_relation->name ?? '-') . "\n";
    echo "Stock shown in Modal / Table: {$stockPieces} Pcs\n";
} else {
    echo "Product not found!\n";
}
