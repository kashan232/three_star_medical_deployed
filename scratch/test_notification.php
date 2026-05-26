<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Purchase;
use App\Services\CreditNotificationService;

$purchaseId = 16; // Change this to an existing purchase ID
$purchase = Purchase::find($purchaseId);

if ($purchase) {
    echo "Testing notification for Purchase #{$purchase->invoice_no}...\n";
    $service = app(CreditNotificationService::class);
    $service->checkPurchaseOverdue($purchase);
    echo "Done.\n";
} else {
    echo "Purchase not found.\n";
}
