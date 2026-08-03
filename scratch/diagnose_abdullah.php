<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CUSTOMERS (SEARCH: ABDULLAH) ===\n";
$customers = App\Models\Customer::where('customer_name', 'like', '%Abdullah%')->get();
if ($customers->isEmpty()) {
    echo "No customer found with name Abdullah. Searching all customers...\n";
    $customers = App\Models\Customer::all();
}
foreach ($customers as $c) {
    echo "Customer ID: {$c->id}, Code: {$c->customer_id}, Name: {$c->customer_name}\n";
    
    echo "  -- SALES --\n";
    $sales = App\Models\Sale::where('customer_id', $c->id)->get();
    foreach ($sales as $s) {
        echo "    Sale ID: {$s->id}, Invoice: {$s->invoice_no}, Status: {$s->sale_status}, Total Net: {$s->total_net}, Employee ID: {$s->employee_id}\n";
    }

    echo "  -- DELIVERY NOTES (DCs) --\n";
    $dcs = DB::table('delivery_notes')->where('customer_id', $c->id)->get();
    foreach ($dcs as $dc) {
        echo "    DC ID: {$dc->id}, DC No: " . ($dc->delivery_note_no ?? $dc->dc_no ?? 'N/A') . ", Sale ID: " . ($dc->sale_id ?? 'NULL') . ", Status: " . ($dc->status ?? 'N/A') . "\n";
    }

    echo "  -- CUSTOMER LEDGERS --\n";
    $ledgers = DB::table('customer_ledgers')->where('customer_id', $c->id)->get();
    echo "    Total Ledger entries: " . $ledgers->count() . "\n";
    foreach ($ledgers as $l) {
        echo "    Ledger ID: {$l->id}, Debit: " . ($l->debit ?? 0) . ", Credit: " . ($l->credit ?? 0) . ", Amount: " . ($l->amount ?? 0) . ", Desc: " . ($l->description ?? 'N/A') . ", Date: " . ($l->created_at ?? 'N/A') . "\n";
    }
}

echo "\n=== ALL DELIVERY NOTES IN DB ===\n";
$allDcs = DB::table('delivery_notes')->get();
echo "Total Delivery Notes: " . $allDcs->count() . "\n";
foreach ($allDcs as $dc) {
    echo "  DC ID: {$dc->id}, Customer ID: {$dc->customer_id}, Sale ID: " . ($dc->sale_id ?? 'NULL') . ", Status: " . ($dc->status ?? 'N/A') . "\n";
}

echo "\n=== RECENT STOCK MOVEMENTS ===\n";
if (Schema::hasTable('stock_movements')) {
    $movements = DB::table('stock_movements')->orderBy('id', 'desc')->limit(10)->get();
    foreach ($movements as $m) {
        echo "  Movement ID: {$m->id}, Product ID: {$m->product_id}, Type: " . ($m->type ?? $m->movement_type ?? 'N/A') . ", Qty: " . ($m->qty ?? $m->quantity ?? 0) . ", Ref: " . ($m->reference ?? $m->ref_uuid ?? 'N/A') . "\n";
    }
}
