<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = DB::select('SHOW COLUMNS FROM hr_payroll_details');
foreach ($columns as $c) {
    echo $c->Field . ' - ' . $c->Type . "\n";
}
