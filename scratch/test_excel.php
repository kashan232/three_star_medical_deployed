<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$acc = App\Models\Account::first();
if ($acc) {
    echo "Testing account ID: " . $acc->id . " - " . $acc->title . PHP_EOL;
    $ctrl = app(App\Http\Controllers\AccountsHeadController::class);
    $req = new Illuminate\Http\Request();
    
    $resExcel = $ctrl->exportLedgerExcel($acc->id, $req);
    echo "Excel Status: " . $resExcel->getStatusCode() . " | Bytes: " . strlen($resExcel->getContent()) . PHP_EOL;
    file_put_contents(__DIR__ . '/test_output.xlsx', $resExcel->getContent());
    echo "Saved test_output.xlsx successfully!" . PHP_EOL;
}
