<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$purchase = \App\Models\Purchase::with('items')->find(3);

$batchNos = $purchase->items->pluck('batch_no')->filter()->unique()->implode(', ');
$mfgDates = $purchase->items->pluck('mfg_date')->filter()->unique()->implode(', ');
$expDates = $purchase->items->pluck('exp_date')->filter()->unique()->implode(', ');

echo "\n--- Raw Pluck Output ---\n";
echo "Batch: '$batchNos'\n";
echo "Mfg: '$mfgDates'\n";
echo "Exp: '$expDates'\n";
