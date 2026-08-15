<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = App\Models\Sale::where('invoice_no', 'SIN-20260815204416')->first();
if (!$sale) {
    echo "Sale not found\n";
    exit;
}

$payment = App\Models\CustomerPayment::where('sale_id', $sale->id)->first();
if (!$payment) {
    echo "Payment not found\n";
    exit;
}

try {
    app(App\Services\PayrollCalculationService::class)->generateInstantCommission($payment);
    echo "Done running generateInstantCommission\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
