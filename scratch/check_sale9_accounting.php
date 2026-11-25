<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== JOURNAL ENTRIES FOR SALE 9 ===\n";
$jes = DB::table('journal_entries')->where('source_type', 'App\\Models\\Sale')->where('source_id', 9)->get();
echo json_encode($jes, JSON_PRETTY_PRINT) . "\n\n";

echo "=== VOUCHERS FOR SALE 9 ===\n";
$vs = DB::table('vouchers')->where('reference', 'like', '%SIN-0008%')->orWhere('remarks', 'like', '%SIN-0008%')->get();
echo json_encode($vs, JSON_PRETTY_PRINT) . "\n\n";

echo "=== CUSTOMER LEDGER FOR SALE 9 ===\n";
$cls = DB::table('customer_ledgers')->where('sale_id', 9)->orWhere('invoice_no', 'like', '%SIN-0008%')->get();
echo json_encode($cls, JSON_PRETTY_PRINT) . "\n\n";
