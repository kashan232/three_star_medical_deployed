<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$sale = App\Models\Sale::latest()->first();
echo "Testing Notification for Sale #" . $sale->invoice_no . " (ID: " . $sale->id . ")\n";

$service = app(App\Services\CreditNotificationService::class);
$service->checkSaleOverdue($sale);

echo "Check complete. Check system_notifications table.\n";
$latestNotif = App\Models\SystemNotification::latest()->first();
if ($latestNotif) {
    echo "Latest Notification: " . $latestNotif->title . "\n";
    echo "Message: " . $latestNotif->message . "\n";
} else {
    echo "No notification created.\n";
}
