<?php

$path = __DIR__ . '/../app/Services/StockService.php';
$content = file_get_contents($path);

$old = '        if (!$stock && $pieceDelta < 0) {
            // Fallback: search for any stock row for this (warehouse, product) regardless of uom_id
            $stock = WarehouseStock::where(\'warehouse_id\', $warehouseId)
                ->where(\'product_id\', $productId)
                ->where(\'total_pieces\', \'>\', 0)
                ->lockForUpdate()
                ->first();
        }';

$new = '        if (!$stock) {
            // Fallback: search for any existing stock row for this (warehouse, product) regardless of uom_id
            $stock = WarehouseStock::where(\'warehouse_id\', $warehouseId)
                ->where(\'product_id\', $productId)
                ->when($pieceDelta < 0, fn($q) => $q->where(\'total_pieces\', \'>\', 0))
                ->lockForUpdate()
                ->first();
        }';

// normalize CRLF
$normContent = str_replace("\r\n", "\n", $content);
$normOld = str_replace("\r\n", "\n", $old);
$normNew = str_replace("\r\n", "\n", $new);

if (strpos($normContent, $normOld) !== false) {
    $normContent = str_replace($normOld, $normNew, $normContent);
    // write back with original ending style if needed
    file_put_contents($path, $normContent);
    echo "SUCCESS: StockService updated!\n";
} else {
    echo "FAILED: Target not found in StockService\n";
}
