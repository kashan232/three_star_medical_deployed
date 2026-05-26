<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$keys = ['sale_notification_recipients', 'purchase_notification_recipients'];

foreach ($keys as $key) {
    $s = Setting::where('key', $key)->first();
    if ($s) {
        echo "Key: $key\n";
        echo "Raw Value: " . $s->value . "\n";
        echo "Type: " . $s->type . "\n";
        $decoded = json_decode($s->value, true);
        echo "Decoded: " . json_encode($decoded) . "\n";
        echo "-------------------\n";
    } else {
        echo "Key: $key NOT FOUND\n";
    }
}
