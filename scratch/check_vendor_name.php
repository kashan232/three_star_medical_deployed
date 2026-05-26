<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$v = \App\Models\Vendor::find(1);
if ($v) {
    echo "Vendor 1 Name: " . $v->name . "\n";
} else {
    echo "Vendor 1 NOT found!\n";
}
