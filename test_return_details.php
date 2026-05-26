<?php
$user = App\Models\User::first();
auth()->login($user);
$controller = app(App\Http\Controllers\PurchaseController::class);
// Get all approved purchases and check their details
$purchases = App\Models\Purchase::where('status_purchase', 'approved')->get();
foreach ($purchases as $p) {
    echo "Purchase ID: {$p->id} | Invoice: {$p->invoice_no}\n";
    $resp = $controller->getPurchaseDetails($p->id);
    $data = json_decode($resp->getContent(), true);
    if ($data['success']) {
        foreach ($data['items'] as $item) {
            echo "  Item: {$item['item_name']} | Qty (Pieces): {$item['qty']} | PPB: {$item['pieces_per_box']} | Mode: {$item['size_mode']}\n";
        }
    }
    echo "-------------------\n";
}
