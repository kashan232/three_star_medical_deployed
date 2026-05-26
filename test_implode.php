<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$item = DB::table('purchase_items')->orderBy('id', 'desc')->first();
echo "Latest Item:\n";
echo "Purchase ID: {$item->purchase_id}\n";
echo "Lot: {$item->batch_no}, Mfg: {$item->mfg_date}, Exp: {$item->exp_date}\n";

$purchase = \App\Models\Purchase::with('items')->find($item->purchase_id);
$batchNos = $purchase->items->pluck('batch_no')->filter()->unique()->implode(', ');
$mfgMapped = $purchase->items->pluck('mfg_date')->map(fn($d) => $d ? $d->format('Y-m-d') : null)->filter()->unique()->implode(', ');
$expMapped = $purchase->items->pluck('exp_date')->map(fn($d) => $d ? $d->format('Y-m-d') : null)->filter()->unique()->implode(', ');

echo "\n--- Purchase Summaries ---\n";
echo "Batch: '$batchNos'\n";
echo "Mfg Mapped: '$mfgMapped'\n";
echo "Exp Mapped: '$expMapped'\n";
