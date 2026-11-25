<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\VendorLedger;
use App\Models\CustomerLedger;
use App\Models\JournalEntry;
use App\Models\VoucherMaster;
use App\Services\TransactionService;
use App\Services\VoucherService;
use App\Services\BalanceService;
use App\Services\DualPartyLedgerService;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    auth()->loginUsingId(1);
    $txService = app(TransactionService::class);
    $voucherService = app(VoucherService::class);
    $balanceService = app(BalanceService::class);
    $dualService = app(DualPartyLedgerService::class);
    $purchaseCtrl = app(PurchaseController::class);
    $reportingCtrl = app(ReportingController::class);

    $vendor = Vendor::findOrFail(458); // TALHA BROTHER
    $customer = Customer::firstOrFail();
    $cashAccId = $balanceService->getCashAccountId(1);
    $apAccId = $balanceService->getAccountsPayableId(1);
    $purchaseExpId = $balanceService->getPurchaseExpenseId(1);

    // ── TEST 1: Purchase Confirmation (Single entry in VendorLedger + proper GL) ──
    $initialVLBars = VendorLedger::where('vendor_id', $vendor->id)->count();
    $initialVendorBal = $balanceService->getVendorBalance($vendor->id);

    $p1 = Purchase::create([
        'branch_id' => 1,
        'warehouse_id' => 1,
        'vendor_id' => $vendor->id,
        'purchase_date' => '2026-11-24',
        'invoice_no' => 'GRN-TEST-9001',
        'subtotal' => 25000,
        'discount' => 0,
        'extra_cost' => 0,
        'freight_charges' => 0,
        'net_amount' => 25000,
        'paid_amount' => 0,
        'due_amount' => 25000,
        'status_purchase' => 'post',
    ]);

    $refMethod = new ReflectionMethod(PurchaseController::class, 'approvePurchase');
    $refMethod->setAccessible(true);
    $refMethod->invoke($purchaseCtrl, $p1);

    $afterVLBars = VendorLedger::where('vendor_id', $vendor->id)->count();
    $newEntries = VendorLedger::where('vendor_id', $vendor->id)
        ->where('description', 'like', '%GRN-TEST-9001%')
        ->get();

    assert($afterVLBars - $initialVLBars === 1, "Expected 1 new VendorLedger entry for p1, got " . ($afterVLBars - $initialVLBars));
    assert($newEntries->count() === 1, "Expected 1 entry matching GRN-TEST-9001, got " . $newEntries->count());
    assert((float)$newEntries->first()->credit === 25000.0, "Expected credit 25000");

    $balAfterP1 = $balanceService->getVendorBalance($vendor->id);
    assert(abs(($balAfterP1 - $initialVendorBal) - 25000.0) < 0.01, "Expected BalanceService vendor bal +25000, got " . ($balAfterP1 - $initialVendorBal));

    // Check ReportingController and DualPartyLedgerService
    $repRes = $reportingCtrl->fetch_vendor_ledger(new Request([
        'vendor_id' => $vendor->id,
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-30',
    ]));
    $repData = $repRes->getData(true);
    $repMatches = array_filter($repData['transactions'], fn($t) => str_contains($t['description'], 'GRN-TEST-9001'));
    assert(count($repMatches) === 1, "ReportingController should show 1 row for GRN-TEST-9001, got " . count($repMatches));

    $dualData = $dualService->getVendorLedgerData($vendor->id, '2026-11-01', '2026-11-30', 1);
    $dualMatches = $dualData['transactions']->filter(fn($t) => str_contains($t->description, 'GRN-TEST-9001'));
    assert($dualMatches->count() === 1, "DualPartyLedgerService should show 1 row for GRN-TEST-9001, got " . $dualMatches->count());
    echo "[PASS] Test 1: Purchase confirmation creates exactly 1 VendorLedger entry & accurate GL balance.\n";

    // ── TEST 2: Purchase with Extra Cost + Freight + Immediate Payment ──
    $p2 = Purchase::create([
        'branch_id' => 1,
        'warehouse_id' => 1,
        'vendor_id' => $vendor->id,
        'purchase_date' => '2026-11-24',
        'invoice_no' => 'GRN-TEST-9002',
        'subtotal' => 10000,
        'discount' => 0,
        'extra_cost' => 1000,
        'freight_charges' => 500,
        'net_amount' => 11500,
        'paid_amount' => 0,
        'due_amount' => 11500,
        'status_purchase' => 'post',
    ]);
    $refMethod->invoke($purchaseCtrl, $p2);
    $txService->createPaymentForPurchase($p2, [$cashAccId], [5000]);

    $p2Entries = VendorLedger::where('vendor_id', $vendor->id)
        ->where('description', 'like', '%GRN-TEST-9002%')
        ->get();
    assert($p2Entries->count() === 2, "Expected 2 entries (1 purchase + 1 payment) for p2, got " . $p2Entries->count());
    assert((float)$p2Entries->sum('credit') === 11500.0, "Expected total credit 11500");
    assert((float)$p2Entries->sum('debit') === 5000.0, "Expected total debit 5000");

    $balAfterP2 = $balanceService->getVendorBalance($vendor->id);
    assert(abs(($balAfterP2 - $balAfterP1) - 6500.0) < 0.01, "Expected BalanceService +6500 after 11500 purchase - 5000 payment, got " . ($balAfterP2 - $balAfterP1));
    echo "[PASS] Test 2: Purchase with Extra Cost, Freight & Immediate Payment has zero duplicates.\n";

    // ── TEST 3: Purchase Return ──
    $warehouseId = \App\Models\Warehouse::value('id');
    $pr = PurchaseReturn::create([
        'branch_id' => 1,
        'warehouse_id' => $warehouseId,
        'vendor_id' => $vendor->id,
        'return_invoice' => 'PRTN-TEST-9001',
        'return_date' => '2026-11-24',
        'net_amount' => 2000,
    ]);
    $txService->createPurchaseReturnVoucher($pr);
    $prEntries = VendorLedger::where('vendor_id', $vendor->id)
        ->where('description', 'like', '%PRTN-TEST-9001%')
        ->get();
    assert($prEntries->count() === 0, "createPurchaseReturnVoucher should NOT insert duplicate/phantom VendorLedger row, got " . $prEntries->count());
    $balAfterPR = $balanceService->getVendorBalance($vendor->id);
    assert(abs(($balAfterP2 - $balAfterPR) - 2000.0) < 0.01, "Expected BalanceService -2000 after purchase return");
    echo "[PASS] Test 3: Purchase Return does not create phantom Credit in VendorLedger.\n";

    // ── TEST 4: Sale Receipt (createReceiptFromSale) ──
    $sale = Sale::whereIn('sale_status', ['post', 'posted'])->whereNotNull('customer_id')->first();
    if ($sale) {
        $beforeCL = CustomerLedger::where('customer_id', $sale->customer_id)->count();
        $txService->createReceiptFromSale($sale, [$cashAccId], [1234]);
        $afterCL = CustomerLedger::where('customer_id', $sale->customer_id)->count();
        assert($afterCL - $beforeCL === 1, "createReceiptFromSale should create exactly 1 CustomerLedger entry, got " . ($afterCL - $beforeCL));
        echo "[PASS] Test 4: Sale Receipt creates exactly 1 CustomerLedger entry.\n";
    } else {
        echo "[SKIP] Test 4: No posted sale found to test receipt.\n";
    }

    // ── TEST 6: Unpost / Reverse Purchase Accounting ──
    $txService->reversePurchaseAccounting($p1);
    $p1Remaining = VendorLedger::where('vendor_id', $vendor->id)
        ->where('description', 'like', '%GRN-TEST-9001%')
        ->count();
    assert($p1Remaining === 0, "reversePurchaseAccounting should clean all ledger rows for p1");
    echo "[PASS] Test 6: Reverse purchase accounting cleanly removes ledger & voucher entries.\n";

    // ── TEST 5: Manual Vouchers (CPV with line party, CRV single-party header, JV with line party) ──
    $beforeManualVL = VendorLedger::where('vendor_id', $vendor->id)->count();
    $voucherService->createVoucher([
        'voucher_type' => 'cpv',
        'date' => '2026-11-24',
        'status' => 'posted',
        'party_type' => Vendor::class,
        'party_id' => $vendor->id,
        'remarks' => 'Manual CPV test',
        'branch_id' => 1,
    ], [
        ['account_id' => $apAccId, 'party_type' => Vendor::class, 'party_id' => $vendor->id, 'debit' => 3000, 'credit' => 0, 'narration' => 'CPV line'],
        ['account_id' => $cashAccId, 'debit' => 0, 'credit' => 3000, 'narration' => 'Cash out'],
    ], 1);
    $afterManualVL = VendorLedger::where('vendor_id', $vendor->id)->count();
    assert($afterManualVL - $beforeManualVL === 1, "Manual CPV should create 1 VendorLedger entry, got " . ($afterManualVL - $beforeManualVL));

    $beforeSingleCL = CustomerLedger::where('customer_id', $customer->id)->count();
    $arAccId = $balanceService->getAccountsReceivableId(1);
    $voucherService->createVoucher([
        'voucher_type' => 'crv',
        'date' => '2026-11-24',
        'status' => 'posted',
        'party_type' => Customer::class,
        'party_id' => $customer->id,
        'remarks' => 'Single-party CRV test',
        'branch_id' => 1,
    ], [
        ['account_id' => $cashAccId, 'debit' => 1500, 'credit' => 0, 'narration' => 'Cash in'],
        ['account_id' => $arAccId, 'debit' => 0, 'credit' => 1500, 'narration' => 'AR credit'],
    ], 1);
    $afterSingleCL = CustomerLedger::where('customer_id', $customer->id)->count();
    assert($afterSingleCL - $beforeSingleCL === 1, "Single-party CRV (no line party) should create 1 CustomerLedger entry, got " . ($afterSingleCL - $beforeSingleCL));

    $beforeJvVL = VendorLedger::where('vendor_id', $vendor->id)->count();
    $voucherService->createVoucher([
        'voucher_type' => 'jv',
        'date' => '2026-11-24',
        'status' => 'posted',
        'remarks' => 'Manual JV test',
        'branch_id' => 1,
    ], [
        ['account_id' => $purchaseExpId, 'debit' => 500, 'credit' => 0, 'narration' => 'Adj exp'],
        ['account_id' => $apAccId, 'party_type' => Vendor::class, 'party_id' => $vendor->id, 'debit' => 0, 'credit' => 500, 'narration' => 'Adj vendor'],
    ], 1);
    $afterJvVL = VendorLedger::where('vendor_id', $vendor->id)->count();
    assert($afterJvVL - $beforeJvVL === 1, "Manual JV with line party should create 1 VendorLedger entry, got " . ($afterJvVL - $beforeJvVL));
    echo "[PASS] Test 5: Manual CPV, single-party CRV, and manual JV all create exact single ledger entries.\n";

    echo "\nALL TESTS PASSED SUCCESSFULLY!\n";
} catch (\Throwable $e) {
    echo "TEST FAILED: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
} finally {
    DB::rollBack();
}
