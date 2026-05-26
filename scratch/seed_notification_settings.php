<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;

$settings = [
    [
        'key' => 'credit_notification_recipients',
        'value' => json_encode([]),
        'type' => 'json',
        'group' => 'notifications',
        'label' => 'Credit Notification Recipients',
        'description' => 'Select users who will receive real-time notifications for overdue credit terms and limits.',
    ]
];

foreach ($settings as $s) {
    Setting::updateOrCreate(['key' => $s['key']], $s);
}

echo "Settings seeded successfully!\n";
