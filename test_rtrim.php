<?php
function parseNotationToPieces($qty, $ppb, $sizeMode)
{
    $ppb = $ppb > 0 ? (float) $ppb : 1.0;
    
    if ($sizeMode === 'by_size') {
        return (float) $qty;
    }

    if ($sizeMode !== 'by_carton' && $sizeMode !== 'by_cartons') {
        return (float) $qty * $ppb;
    }

    // CLEANER VERSION
    $qtyStr = rtrim(rtrim((string) $qty, '0'), '.');
    if (! str_contains($qtyStr, '.')) {
        return (float) $qtyStr * $ppb;
    }

    $parts = explode('.', $qtyStr, 2);
    $boxes = (int) $parts[0];
    $pieces = (int) ($parts[1] ?? 0);

    return ($boxes * $ppb) + $pieces;
}

$tests = [
    ['qty' => '5.100', 'ppb' => 2, 'mode' => 'by_cartons'], // User's case
    ['qty' => '5.1', 'ppb' => 2, 'mode' => 'by_cartons'],
    ['qty' => 5.1, 'ppb' => 2, 'mode' => 'by_cartons'],
];

foreach ($tests as $t) {
    $res = parseNotationToPieces($t['qty'], $t['ppb'], $t['mode']);
    echo "Qty: {$t['qty']} | PPB: {$t['ppb']} => Pieces: $res\n";
}
