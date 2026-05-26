<?php
require 'vendor/autoload.php';
$class = new ReflectionClass('App\Http\Controllers\PurchaseController');
foreach ($class->getMethods() as $m) {
    if ($m->class === 'App\Http\Controllers\PurchaseController') {
        echo $m->name . "\n";
    }
}
