<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$item = \App\Models\PurchaseItem::find(11);
echo "Raw value: " . $item->mfg_date . "\n";
echo "Format method: " . ($item->mfg_date ? $item->mfg_date->format('Y-m-d') : 'null') . "\n";
