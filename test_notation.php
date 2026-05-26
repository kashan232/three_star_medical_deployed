<?php
$controller = new App\Http\Controllers\PurchaseController();
$ref = new ReflectionMethod($controller, 'parseNotationToPieces');
$ref->setAccessible(true);

$tests = [
    ['qty' => 5.1, 'ppb' => 10, 'mode' => 'by_cartons'],
    ['qty' => '5.1', 'ppb' => 10, 'mode' => 'by_cartons'],
    ['qty' => 5.10, 'ppb' => 10, 'mode' => 'by_cartons'],
    ['qty' => '5.10', 'ppb' => 10, 'mode' => 'by_cartons'],
    ['qty' => 5.1, 'ppb' => 2, 'mode' => 'by_cartons'],
];

foreach ($tests as $t) {
    $res = $ref->invoke($controller, $t['qty'], $t['ppb'], $t['mode']);
    echo "Qty: {$t['qty']} | PPB: {$t['ppb']} | Mode: {$t['mode']} => Total Pieces: $res\n";
}
