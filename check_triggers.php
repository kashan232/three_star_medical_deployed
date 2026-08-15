<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$triggers = DB::select('SHOW TRIGGERS');
foreach ($triggers as $t) {
    echo $t->Trigger . "\n";
}
