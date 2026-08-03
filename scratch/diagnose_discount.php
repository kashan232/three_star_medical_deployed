<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ALL SALES IN DB ===\n";
$sales = App\Models\Sale::with('items')->get();
foreach ($sales as $s) {
    echo "Sale ID: {$s->id}, Invoice No: {$s->invoice_no}, Customer ID: {$s->customer_id}, Total Net: {$s->total_net}, Extra Discount: " . ($s->total_extradiscount ?? 0) . "\n";
    foreach ($s->items as $item) {
        echo "  SaleItem ID: {$item->id}, Product ID: {$item->product_id}, Total Pcs: {$item->total_pieces}, Price: {$item->price}, Per Discount: " . ($item->per_discount ?? 0) . ", Line Total: " . ($item->line_total ?? 0) . "\n";
        echo "  Raw item data: " . json_encode($item) . "\n";
    }
}

echo "\n=== ALL DELIVERY NOTES IN DB ===\n";
$dcs = App\Models\DeliveryNote::with('items')->get();
foreach ($dcs as $dc) {
    echo "DC ID: {$dc->id}, DC No: {$dc->dc_no}, Sale ID: " . ($dc->sale_id ?? 'NULL') . ", Subtotal: {$dc->subtotal}\n";
    foreach ($dc->items as $dci) {
        echo "  DCItem ID: {$dci->id}, Product ID: {$dci->product_id}, Qty: {$dci->qty}, Price: {$dci->price}, Line Total: {$dci->line_total}\n";
        echo "  Raw DCItem: " . json_encode($dci) . "\n";
    }
}
