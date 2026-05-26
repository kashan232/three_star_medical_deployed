<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

// Delete the old general recipient setting
Setting::where('key', 'credit_notification_recipients')->delete();

// Add Sale Notification Recipients
Setting::firstOrCreate(
    ['key' => 'sale_notification_recipients'],
    [
        'value' => '[]',
        'type' => 'json',
        'group' => 'notifications',
        'label' => 'Sale Credit Notification Recipients',
        'description' => 'Users who will receive alerts for customer credit limits and overdue sales.',
    ]
);

// Add Purchase Notification Recipients
Setting::firstOrCreate(
    ['key' => 'purchase_notification_recipients'],
    [
        'value' => '[]',
        'type' => 'json',
        'group' => 'notifications',
        'label' => 'Purchase Credit Notification Recipients',
        'description' => 'Users who will receive alerts for vendor credit terms and overdue purchases.',
    ]
);

echo "Settings updated successfully.\n";
