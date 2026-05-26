<?php
$user = App\Models\User::first();
auth()->login($user);
$controller = app(App\Http\Controllers\PurchaseController::class);
// Get specific purchase
$p = App\Models\Purchase::find(15);
echo "Purchase ID: {$p->id} | Invoice: {$p->invoice_no}\n";
foreach ($p->items as $item) {
    echo "  DB Item: id={$item->id} | qty={$item->qty} | ppb_at_pur={$item->pieces_per_box} | ppb_prod=" . ($item->product->pieces_per_box ?? 'N/A') . " | mode={$item->size_mode}\n";
    $method = new ReflectionMethod($controller, 'parseNotationToPieces');
    $method->setAccessible(true);
    $ppb = ($item->product->pieces_per_box ?? $item->pieces_per_box ?? 1);
    $calculated = $method->invoke($controller, $item->qty, (float)$ppb, $item->size_mode);
    echo "  Calculated Pieces: $calculated\n";
}
