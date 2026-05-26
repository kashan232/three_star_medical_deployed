<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;

use App\Http\Traits\BranchScoped;
use App\Models\Inwardgatepass;
use App\Models\Product;
use App\Models\PriceLog;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;


class PurchaseController extends Controller
{
    use BranchScoped;

    // Stock updates are now handled via App\Services\StockService.
    // StockService::credit($productId, $uomId, $warehouseId, $branchId, $pieces)
    // StockService::debit($productId, $uomId, $warehouseId, $branchId, $pieces)

    public function orderIndex()
    {
        $branchId = $this->getBranchId();
        $Purchase = Purchase::with(['vendor', 'warehouse', 'items.product'])
            ->where('status_purchase', 'draft')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $Purchase = $this->enrichPurchaseData($Purchase);

        return view('admin_panel.purchase.purchase_order.index', compact('Purchase'));
    }

    public function grnIndex()
    {
        $branchId = $this->getBranchId();
        $Purchase = Purchase::with(['vendor', 'warehouse', 'items.product', 'returns', 'payments'])
            ->whereIn('status_purchase', ['post', 'un-post', 'Returned', 'Partial Return'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $Purchase = $this->enrichPurchaseData($Purchase);

        return view('admin_panel.purchase.goods_receipt_note.index', compact('Purchase'));
    }

    public function unpost($id)
    {
        try {
            DB::beginTransaction();
            $purchase = Purchase::with('items.product')->findOrFail($id);

            if ($purchase->status_purchase !== 'post') {
                return response()->json(['success' => false, 'message' => 'Only posted GRNs can be un-posted.'], 422);
            }

            // check permissions (handled by middleware usually, but extra check here)
            if (!auth()->user()->can('purchases.unpost') && !auth()->user()->isSuperAdmin()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to un-post.'], 403);
            }

            // 0. Check for Returns
            if ($purchase->returns()->exists()) {
                return response()->json(['success' => false, 'message' => 'Cannot un-post. There are existing returns for this purchase.'], 422);
            }

            // 1. Check Batch Utilization
            foreach ($purchase->items as $item) {
                if (! empty($item->batch_no)) {
                    $batch = ProductBatch::where('product_id', $item->product_id)
                        ->where('batch_number', $item->batch_no)
                        ->where('branch_id', $purchase->branch_id)
                        ->first();
                    
                    if ($batch && $batch->qty_remaining < $batch->qty_received) {
                        return response()->json([
                            'success' => false, 
                            'message' => "Cannot un-post. Batch '{$item->batch_no}' for product '{$item->product->item_name}' has already been partially used/sold."
                        ], 422);
                    }
                }
            }

            // 2. Reverse Inventory (Stock)
            foreach ($purchase->items as $item) {
                $ppb = (float)($item->pieces_per_box > 0 ? $item->pieces_per_box : 1);
                $totalPieces = $this->parseNotationToPieces($item->qty, $ppb, $item->size_mode, $item->loose_qty) + ($item->free_qty_pieces ?? 0);
                
                StockService::debit(
                    $item->product_id,
                    $item->uom_id ?? null,
                    $item->warehouse_id ?: $purchase->warehouse_id,
                    $purchase->branch_id,
                    $totalPieces
                );

                // DELETE the batch entirely so it disappears from reports
                // Using the direct relationship is more reliable than matching by strings
                if ($item->batch) {
                    $item->batch->delete();
                } elseif (! empty($item->batch_no)) {
                    // Fallback for any legacy records or manual matches
                    ProductBatch::where('product_id', $item->product_id)
                        ->where('batch_number', $item->batch_no)
                        ->where('warehouse_id', $item->warehouse_id ?: $purchase->warehouse_id)
                        ->where('branch_id', $purchase->branch_id)
                        ->delete();
                }
            }

            // 3. Delete Stock Movements
            DB::table('stock_movements')
                ->where('ref_type', 'PURCHASE')
                ->where('ref_id', $id)
                ->delete();

            // 4. Reverse Accounting (Vouchers, Ledger, Payments, etc.)
            $transactionService = app(\App\Services\TransactionService::class);
            $transactionService->reversePurchaseAccounting($purchase);

            // 5. Update Status and Reset Payment Tracking
            // We keep the invoice_no as is, but changing status to draft makes it a PO again conceptually
            $purchase->update([
                'status_purchase' => 'un-post',
                'paid_amount'     => 0,
                'due_amount'      => $purchase->net_amount
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'GRN un-posted successfully.']);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("GRN Un-post Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function enrichPurchaseData($collection)
    {
        $collection->each(function ($purchase) {
            $totalReturned = $purchase->returns ? $purchase->returns->sum('net_amount') : 0;
            // Use payments relationship if available, otherwise fallback to stored paid_amount column
            $totalPaid = ($purchase->payments && $purchase->payments->count() > 0) 
                ? $purchase->payments->sum('amount') 
                : (float) $purchase->paid_amount;

            $purchase->paid_amount = $totalPaid;
            $purchase->due_amount = max(0, $purchase->net_amount - $totalPaid);

            $returnableBase = max(0, (float) $purchase->net_amount - (float) $purchase->extra_cost);
            $purchase->total_returned = (float) $totalReturned;
            $purchase->updated_net_amount = max(0, $returnableBase - $totalReturned);
            $purchase->updated_due_amount = max(0, $purchase->due_amount - $totalReturned);

            // Use a small epsilon or rounding for return status checks to handle float precision
            $roundedReturned = round($totalReturned, 2);
            $roundedBase = round($returnableBase, 2);

            $purchase->is_fully_returned = ($roundedReturned >= $roundedBase && $roundedBase > 0) || ($purchase->status_purchase == 'Returned');
            $purchase->has_partial_return = $roundedReturned > 0 && $roundedReturned < $roundedBase;

            // Batch Info Summary
            $batchNos = $purchase->items->pluck('batch_no')->filter()->unique()->implode(', ');
            $mfgDates = $purchase->items->pluck('mfg_date')->map(fn($d) => $d ? (\Carbon\Carbon::parse($d)->format('Y-m-d')) : null)->filter()->unique()->implode(', ');
            $expDates = $purchase->items->pluck('exp_date')->map(fn($d) => $d ? (\Carbon\Carbon::parse($d)->format('Y-m-d')) : null)->filter()->unique()->implode(', ');

            $purchase->batch_summary = $batchNos ?: '-';
            $purchase->mfg_summary = $mfgDates ?: '-';
            $purchase->exp_summary = $expDates ?: '-';

            // Original Quantity (Sum of all item pieces) - Explicit calculation to be safe
            $totalPieces = 0;
            foreach ($purchase->items as $item) {
                $totalPieces += (float) $item->total_pieces;
            }
            $purchase->total_original_pieces = $totalPieces;
        });

        return $collection;
    }

    public function index(Request $request)
    {
        if ($request->mode == 'po') {
            return $this->orderIndex();
        }

        return $this->grnIndex();
    }

    public function checkLotUniqueness(Request $request)
    {
        $productId = $request->product_id;
        $batchNo = $request->lot_no;

        $exists = ProductBatch::where('product_id', $productId)
            ->where('batch_number', $batchNo)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    public function addBill($gatepassId)
    {
        // Fetch the gatepass along with its related items and products
        $gatepass = InwardGatepass::with('items.product')->findOrFail($gatepassId);

        // Pass the gatepass data to the view
        return view('admin_panel.inward.add_bill', compact('gatepass'));
    }

    public function add_purchase(Request $request)
    {
        $branchId = $this->getBranchId() ?? (\App\Models\Warehouse::first()->branch_id ?? 1);
        $branch = \App\Models\Branch::find($branchId);
        $branchName = $branch ? $branch->name : 'Head Office';

        $Purchase = Purchase::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $Vendor = Vendor::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $Warehouse = \App\Models\Warehouse::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();
        $nextInvoice = Purchase::generateInvoiceNo($request->mode == 'po' ? 'PO-' : 'GRN-', $branchId);

        $warehouses = $Warehouse;
        $vendors = $Vendor;

        $viewData = compact('Vendor', 'Warehouse', 'Purchase', 'accounts', 'nextInvoice', 'branchName', 'branchId', 'warehouses', 'vendors');

        if ($request->mode == 'po') {
            return view('admin_panel.purchase.purchase_order.create', $viewData);
        }

        return view('admin_panel.purchase.goods_receipt_note.create', $viewData);
    }

    private function approvePurchase(Purchase $purchase)
    {
        // 1. Stock Movements & Warehouse Stock
        // We need to re-iterate items because we need product_id and qty
        // But the previous logic used $validated arrays which might process duplicates or specific logic.
        // However, since the PurchaseItems are already saved in DB for 'draft' or 'new',
        // we should rely on the SAVED items for approval to ensure consistency.

        $branchId = $purchase->branch_id;
        $warehouseId = $purchase->warehouse_id;

        // Check for Gatepass link (if linked, no stock movement needed usually, logic from store method)
        $hasGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

        if (! $hasGatepass) {
            $movRows = [];
            foreach ($purchase->items as $item) {
                $ppb = $item->pieces_per_box > 0 ? $item->pieces_per_box : 1;

                // Paid qty pieces (using centralized accessor)
                $paidPieces = $item->total_pieces;

                // Free qty pieces (stored as total in DB during store method)
                $freePieces = (float)($item->free_qty_pieces ?? 0);

                $stockQty = $paidPieces + $freePieces;

                // movements (+)
                $movRows[] = [
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $stockQty,
                    'ref_type' => 'PURCHASE',
                    'ref_id' => $purchase->id,
                    'note' => 'Purchase Confirmed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // stocks — UOM-aware credit
                StockService::credit(
                    $item->product_id,
                    $item->uom_id ?? null,
                    $item->warehouse_id ?: $warehouseId,
                    $branchId,
                    $stockQty
                );

                // Create or reactivate a held batch
                if (! empty($item->batch_no)) {
                    \App\Models\ProductBatch::updateOrCreate(
                        [
                            'product_id'   => $item->product_id,
                            'batch_number' => $item->batch_no,
                            'branch_id'    => $branchId,
                            'warehouse_id' => $item->warehouse_id ?: $warehouseId,
                        ],
                        [
                            'mfg_date'         => $item->mfg_date,
                            'exp_date'         => $item->exp_date,
                            'qty_received'     => $stockQty,
                            'qty_remaining'    => $stockQty,
                            'status'           => 'active',
                            'purchase_item_id' => $item->id,
                        ]
                    );
                }

            }

            if (! empty($movRows)) {
                DB::table('stock_movements')->insert($movRows);
            }
        }

        // 2. Vendor Ledger
        $netAmount = $purchase->net_amount;
        $prevClosing = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)
            ->value('closing_balance') ?? 0;

        \App\Models\VendorLedger::create([
            'vendor_id' => $purchase->vendor_id,
            'branch_id' => $purchase->branch_id,
            'admin_or_user_id' => auth()->id(),
            'previous_balance' => $prevClosing,
            'opening_balance' => 0,
            'debit' => 0,
            'credit' => $netAmount,
            'closing_balance' => $prevClosing + $netAmount,
            'description' => "Purchase Confirmed #{$purchase->invoice_no}",
            'source_type' => \App\Models\Purchase::class,
            'source_id' => $purchase->id,
        ]);

        // 3. Accounting
        try {
            // A. Create Purchase Voucher to update Chart of Accounts
            $transactionService = app(\App\Services\TransactionService::class);
            $transactionService->createPurchaseVoucher($purchase);

            // B. Record Payment (This part is tricky if payments were passed solely in Request)
            // If payments were saved in a temp table or if we re-collect them, good.
            // BUT: distinct feature request "Confirm Purchase" usually implies later approval.
            // If payments were part of the initial 'store' request, they are lost if we didn't save them.
            // The user said "confirm purchase... don't create vouchers... just save...".
            // So if 'Draft', we did NOT run accounting. The payment inputs were ignored?
            // If we want to support payments on Confirm, we would need to store them or ask again.
            // For now, we will Assume no immediate payments on 'Draft -> Confirm' via separate button,
            // UNLESS we are in the immediate 'store' flow where Request data is available.

            // To handle both cases (immediate approve vs later approve), we can pass optional payment data.
            // But strict signature: approvePurchase(Purchase $purchase, array $paymentData = [])

        } catch (\Exception $e) {
            \Log::error('Purchase Accounting Error: '.$e->getMessage());
        }
    }



    public function confirm($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            if ($purchase->status_purchase !== 'draft') {
                return; // already approved or invalid state
            }

            // Run approval logic
            $this->approvePurchase($purchase);

            // Update status and generate GRN number if it was a PO
            $updateData = ['status_purchase' => 'post'];
            if (str_starts_with($purchase->invoice_no, 'PO-')) {
                $updateData['invoice_no'] = Purchase::generateInvoiceNo('GRN-', $purchase->branch_id);
            }
            $purchase->update($updateData);
            $this->triggerCreditChecks($purchase);
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase confirmed successfully.',
                'invoice_url' => route('purchase.invoice', $id),
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->route('purchase.grn.index')->with('success', 'Purchase confirmed successfully.');
    }

    private function triggerCreditChecks(Purchase $purchase)
    {
        try {
            $notificationService = app(\App\Services\CreditNotificationService::class);
            $notificationService->checkPurchaseOverdue($purchase);
        } catch (\Exception $e) {
            \Log::error('Purchase Credit Notification Error: ' . $e->getMessage());
        }
    }

    public function store(Request $request, $gatepassId = null)
    {
        // (A) Gatepass fetch if provided
        $gatepass = null;
        if ($gatepassId) {
            $gatepass = \App\Models\InwardGatepass::with('purchase')->findOrFail($gatepassId);
            if ($gatepass->purchase) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Gatepass already has a bill.'], 422);
                }

                return back()->with('error', 'This gatepass already has an associated bill.');
            }
        }

        // (B) Validation
        // Pre-sanitize uom_id: 'NEW' or any non-numeric value must become null
        // so the exists:product_uoms,id rule does not reject them.
        // The store logic below reads the raw $request->uom_id for 'NEW' handling.
        if ($request->has('uom_id') && is_array($request->uom_id)) {
            $request->merge([
                'uom_id' => array_map(
                    fn($v) => (is_numeric($v) && (int)$v > 0) ? (int)$v : null,
                    $request->uom_id
                )
            ]);
        }
        $attributes = [];
        if ($request->has('product_id') && is_array($request->product_id)) {
            foreach ($request->product_id as $i => $id) {
                $row = $i + 1;
                $attributes["product_id.$i"]    = "Row $row Product";
                $attributes["qty.$i"]           = "Row $row Quantity";
                $attributes["price.$i"]         = "Row $row Price";
                $attributes["item_discount.$i"] = "Row $row Discount";
                $attributes["unit.$i"]          = "Row $row Unit";
                $attributes["uom_id.$i"]        = "Row $row UOM";
                $attributes["loose_qty.$i"]     = "Row $row Loose Qty";
            }
        }

        try {
            $validated = $request->validate([
                'invoice_no' => 'nullable|string',
                'vendor_id' => 'required|exists:vendors,id',
                'purchase_date' => 'nullable|date',
                'branch_id' => 'nullable|exists:branches,id',
                'warehouse_id' => 'required|exists:warehouses,id',
                'note' => 'nullable|string',
                'discount' => 'nullable|numeric|min:0',
                'extra_cost' => 'nullable|numeric|min:0',
                'freight_charges' => 'nullable|numeric|min:0',
                'product_id' => 'array',
                'product_id.*' => 'nullable|exists:products,id',
                'qty' => 'array',
                'qty.*' => 'nullable|required_with:product_id.*|numeric|min:0',
                'loose_qty' => 'array',
                'loose_qty.*' => 'nullable|numeric|min:0',
                'price' => 'array',
                'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
                'unit' => 'array',
                'unit.*' => 'nullable|required_with:product_id.*|string',
                'item_discount.*' => 'nullable|numeric|min:0',
                'item_warehouse_id' => 'array',
                'item_warehouse_id.*' => 'nullable|exists:warehouses,id',
                'packing_id' => 'array',
                'packing_name' => 'array',
                'item_uom_factor' => 'array',
                'uom_id' => 'array',
                'uom_id.*' => 'nullable|exists:product_uoms,id',
                'purchase_order_no' => 'nullable|string',
            ], [], $attributes);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'active' => false, 'errors' => $e->errors(), 'message' => 'Validation Error'], 422);
            }
            throw $e;
        }

        // Wrap in transaction... to allow returning $purchase outside
        $purchase = DB::transaction(function () use ($validated, $request, $gatepass) {

            // Status Logic
            $status = ($request->action === 'save_only') ? 'draft' : 'post';
            $prefix = ($status === 'draft') ? 'PO-' : 'GRN-';

            $existingDraft = null;
            if ($request->filled('draft_id')) {
                $existingDraft = Purchase::find($request->draft_id);
            }

            // Determine branchId: use user's own branch or super admin's selected branch
            $branchId = auth()->user()->getBranchId() ?? (int) ($validated['branch_id'] ?? 1);

            // invoice number scoped per branch
            $invoiceNo = (! empty($validated['invoice_no'])
                && $validated['invoice_no'] !== '000000'
                && ! str_starts_with($validated['invoice_no'], 'PINV-')
                && ! str_starts_with($validated['invoice_no'], 'PO-')
                && ! str_starts_with($validated['invoice_no'], 'GRN-'))
                ? $validated['invoice_no']
                : ($existingDraft && str_starts_with($existingDraft->invoice_no, $prefix) ? $existingDraft->invoice_no : Purchase::generateInvoiceNo($prefix, $branchId));

            $warehouseId = (int) $validated['warehouse_id'];

            if ($existingDraft) {
                $purchase = $existingDraft;
                // Delete old items so we can recreate them
                $purchase->items()->delete();
                $purchase->update([
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'purchase_date' => $validated['purchase_date'] ?? now(),
                    'invoice_no' => $invoiceNo,
                    'note' => $validated['note'] ?? null,
                    'subtotal' => 0,
                    'discount' => 0,
                    'extra_cost' => 0,
                    'net_amount' => 0,
                    'due_amount' => 0,
                    'status_purchase' => $status,
                    'enable_hs_code' => $request->enable_hs_code ? 1 : 0,
                    'po_ref' => $request->purchase_order_no,
                ]);
            } else {
                // create header
                $purchase = Purchase::create([
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'vendor_id' => $validated['vendor_id'] ?? null,
                    'purchase_date' => $validated['purchase_date'] ?? now(),
                    'invoice_no' => $invoiceNo,
                    'note' => $validated['note'] ?? null,
                    'subtotal' => 0,
                    'discount' => 0,
                    'extra_cost' => 0,
                    'net_amount' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'status_purchase' => $status,
                    'enable_hs_code' => $request->enable_hs_code ? 1 : 0,
                    'po_ref' => $request->purchase_order_no,
                ]);
            }

            $subtotal = 0;
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];
            $itemDiscTypes = $request->item_discount_type ?? [];

            // Snapshot fields
            $sizeModes = $request->size_mode ?? [];
            $packingIds = $request->packing_id ?? [];
            $packingNames = $request->pieces_per_box ?? []; // This is the visible select2 text
            $ppbs = $request->item_uom_factor ?? []; // This is the multiplier
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];
            $lotNos = $request->lot_no ?? [];
            $expDates = $request->expiry ?? [];
            $mfgDates = $request->mfg_date ?? [];
            $freeQtys = $request->free_qty ?? [];  // raw free qty values
            $gstPercents = $request->gst_percent ?? $request->st_tax_percent ?? [];
            $gstAmounts = $request->gst_amount ?? $request->st_tax_amount ?? [];
            $itPercents = $request->it_percent ?? [];
            $advPercents = $request->adv_tax_percent ?? [];

            $totalGst    = 0;  // GST only (added)
            $totalWht    = 0;  // Income Tax / WHT (deducted)
            $totalAdv    = 0;  // Advance Tax (deducted)
            foreach ($pids as $i => $pid) {
                $pid = (int) ($pid ?? 0);
                $qtyInput = $qtys[$i] ?? 0;
                $qty = (float) $qtyInput; 
                $price = (float) ($prices[$i] ?? 0);
                $disc = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? 'Piece';
                if (! $pid || $price < 0) {
                    continue;
                }

                // Handle Packing (UOM) Logic
                $uomName = $request->uom_name[$i] ?? 'Piece';
                $rawFactor = $ppbs[$i] ?? null;
                $uomFactor = ($rawFactor !== null && $rawFactor !== '' && (float)$rawFactor > 0)
                    ? (float)$rawFactor
                    : 1;
                $uomIdRaw = $request->uom_id[$i] ?? null;
                $isNewUom = ($uomIdRaw === 'NEW' || ($request->is_new_uom[$i] ?? '0') === '1');
                $curSizeMode = $sizeModes[$i] ?? 'by_pieces';

                // On-the-fly Creation/Update
                $uom = null;
                if ($isNewUom) {
                    $uom = \App\Models\ProductUom::create([
                        'product_id' => $pid,
                        'name' => $uomName,
                        'pieces_per_box' => $uomFactor,
                        'purchase_price' => $price
                    ]);
                    $uomId = $uom->id;
                } elseif ($uomIdRaw && is_numeric($uomIdRaw)) {
                    $uomId = $uomIdRaw;
                    $uom = \App\Models\ProductUom::find($uomId);
                    if ($uom) {
                        $oldPurchasePrice = (float) $uom->purchase_price;
                        $oldSalePrice = (float) $uom->sale_price;

                        // Update prices permanently as requested
                        $uom->update([
                            'purchase_price' => $price,
                            'pieces_per_box' => $uomFactor,
                        ]);

                        // Log Price Change for Purchase
                        PriceLog::log($pid, 'purchase', $oldPurchasePrice, $price, 'GRN', $invoiceNo, "Price updated via Purchase #$invoiceNo");

                        // Re-read factor with null-safe fallback
                        $uomFactor = max(1, (float) ($uom->pieces_per_box ?? $uomFactor));
                    }
                } else {
                    $uomId = null; // Base Piece
                    // Check if we should update product's base purchase price too
                    $product = Product::find($pid);
                    if ($product) {
                        $oldPrice = (float) $product->purchase_price_per_piece;
                        if ($oldPrice != $price) {
                            $product->update(['purchase_price_per_piece' => $price]);
                            PriceLog::log($pid, 'purchase', $oldPrice, $price, 'GRN', $invoiceNo, "Base price updated via Purchase #$invoiceNo");
                        }
                    }
                }

                // Final guarantee: uom_factor must never be null or zero
                $uomFactor = (float) ($uomFactor ?: 1);
                $uomFactor = $uomFactor > 0 ? $uomFactor : 1;
                
                $ppb = $uomFactor;

                $looseInput = $looseQtys[$i] ?? 0;
                $totalPieces = $this->parseNotationToPieces($qtyInput, $ppb, $curSizeMode, $looseInput);

                // Regardless of mode, the user expects 'price' to be the Rate Per Piece (as per Turn 7 req)
                $grossTotal = $totalPieces * $price;

                // Discount logic: treat as Discount Per Piece (matching Disc/PC label in UI)
                $curDiscType = $itemDiscTypes[$i] ?? 'amount';
                // Per user request: Flat discount amount, not per piece
                $lineDiscAmount = ($curDiscType === 'percent') ? ($grossTotal * $disc / 100) : $disc;

                $lineTotal = max(0, $grossTotal - $lineDiscAmount);

                // ─── Resolve free qty pieces (same decimal box.piece notation as qty) ───
                $freeQtyInput = $freeQtys[$i] ?? 0;
                $freeLooseInput = $request->free_qty_pieces[$i] ?? 0;
                $freeQtyPieces = $this->parseNotationToPieces($freeQtyInput, $ppb, $curSizeMode, $freeLooseInput);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pid,
                    'warehouse_id' => $request->item_warehouse_id[$i] ?? $validated['warehouse_id'],
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $disc,
                    'item_discount_type' => $itemDiscTypes[$i] ?? 'amount',
                    'qty' => $qtyInput,
                    'uom_name' => $uomName,
                    'uom_factor' => $uomFactor,
                    'uom_id' => $uomId,
                    'free_qty' => $freeQtyInput,
                    'free_qty_pieces' => $freeQtyPieces,
                    'line_total' => $lineTotal,   // price only on paid qty, free is free
                    'hs_code' => $request->hs_code[$i] ?? null,

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $ppb,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $boxesQtys[$i] ?? 0,
                    'loose_qty' => $looseQtys[$i] ?? 0,
                    'length' => (string) ($lengths[$i] ?? ''),
                    'width' => (string) ($widths[$i] ?? ''),
                    'batch_no' => $lotNos[$i] ?? null,
                    'mfg_date' => $mfgDates[$i] ?? null,
                    'exp_date' => $expDates[$i] ?? null,
                    'gst_percent' => (float) ($gstPercents[$i] ?? 0),
                    'gst_amount' => (float) ($gstAmounts[$i] ?? 0),
                    'it_percent' => (float) ($itPercents[$i] ?? 0),
                    'adv_tax_percent' => (float) ($advPercents[$i] ?? 0),
                ]);

                $subtotal += $lineTotal;
                // GST amount comes from the frontend-calculated field (% applied on line subtotal)
                $rowGst = (float) ($gstAmounts[$i] ?? 0);
                // WHT and Advance Tax are percentages applied on line subtotal (post-discount)
                $rowWht = $lineTotal * ((float)($itPercents[$i]  ?? 0) / 100);
                $rowAdv = $lineTotal * ((float)($advPercents[$i] ?? 0) / 100);
                $totalGst += $rowGst;
                $totalWht += $rowWht;
                $totalAdv += $rowAdv;
            }

            // totals
            $discount = (float) ($request->discount ?? 0);
            $discountType = $request->bill_discount_type ?? 'amount';
            $billDiscAmount = ($discountType === 'percent') ? ($subtotal * $discount / 100) : $discount;

            $extraCost = (float) ($request->extra_cost ?? 0);
            $freightCharges = (float) ($request->freight_charges ?? 0);

            // Pakistan Standard Net Amount:
            // Step 1: Net (post-discount) = subtotal - billDisc
            // Step 2: GST Base = net + freight + extraCost  → GST is ADDED
            // Step 3: Invoice Total = gstBase + GST
            // Step 4: Net Payable = Invoice Total - WHT - Adv
            $netPostDisc   = $subtotal - $billDiscAmount;
            $gstBase       = $netPostDisc + $freightCharges + $extraCost;
            // Recalculate GST on gst_base at summary level if no per-line GST; else use sum
            // Per spec: we use the per-line GST amounts already summed
            $invoiceTotal  = $gstBase + $totalGst;
            // WHT and Adv Tax are applied on net (no freight, no GST base)
            $netAmount     = $invoiceTotal - $totalWht - $totalAdv;

            $paidAmount = 0;
            if ($status !== 'draft' && $request->filled('payment_amount')) {
                $paidAmount = collect($request->payment_amount)->map(fn ($v) => (float) $v)->sum();
            }

            $dueAmount = max(0, $netAmount - $paidAmount);

            $purchase->update([
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'discount_type'    => $discountType,
                'extra_cost'       => $extraCost,
                'freight_charges'  => $freightCharges,
                'total_gst'        => $totalGst,
                'wht_amount'       => $totalWht,
                'adv_tax_amount'   => $totalAdv,
                'net_amount'       => $netAmount,
                'is_gst_invoice'   => $request->has('is_gst_invoice') ? 1 : 0,
            ]);

            // If NOT draft, run full approval
            if ($status === 'post') {
                $purchase->load('items'); // Load items for approval logic logic

                $this->approvePurchase($purchase); // Basic Stock + Ledger + Voucher

                // B. Record Payment (Only available in immediate Request)
                try {
                    $transactionService = app(\App\Services\TransactionService::class);
                    $paymentAccountIds = $request->input('payment_account_id', []);
                    $paymentAmounts = $request->input('payment_amount', []);

                    if (! empty(array_filter($paymentAccountIds))) {
                        $transactionService->createPaymentForPurchase(
                            $purchase,
                            $paymentAccountIds,
                            $paymentAmounts
                        );
                    }
                } catch (\Exception $e) { /* Logged already */
                }
            }

            // link gatepass -> purchase (and keep status)
            if ($gatepass) {
                $gatepass->purchase_id = $purchase->id;
                $gatepass->status = 'linked';
                $gatepass->save();
            }

            // Product master prices are not updated here anymore.
            // Latest purchase price is fetched branch-wise during selection.
            $purchase->load('items');

            if ($status === 'post') {
                $this->triggerCreditChecks($purchase);
            }

            return $purchase;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase saved successfully.',
                'invoice_url' => route('purchase.invoice', $purchase->id),
                'redirect_url' => route('Purchase.home'),
                'print_preview' => $request->has('print_preview'),
            ]);
        }

        if ($request->has('print_preview')) {
            return redirect()->route('purchase.invoice', $purchase->id)->with('success', 'Purchase saved successfully.');
        }

        return redirect()->route('Purchase.home')->with('success', 'Purchase saved successfully.');
    }

    // public function store(Request $request)
    // {
    //     // ✅ Validation
    //     $validated = $request->validate([
    //         'invoice_no'     => 'nullable|string',
    //         'vendor_id'      => 'nullable|exists:vendors,id',
    //         'purchase_date'  => 'nullable|date',
    //         'warehouse_id'   => 'nullable|exists:warehouses,id',
    //         'note'           => 'nullable|string',
    //         'discount'       => 'nullable|numeric|min:0',
    //         'extra_cost'     => 'nullable|numeric|min:0',

    //         // Purchase Items
    //         'product_id'       => 'nullable|array',
    //         'product_id.*'     => 'nullable|exists:products,id',
    //         'qty'              => 'nullable|array',
    //         'qty.*'            => 'nullable|numeric|min:1',
    //         'price'            => 'nullable|array',
    //         'price.*'          => 'nullable|numeric|min:0',
    //         'unit'             => 'nullable|array',
    //         'unit.*'           => 'nullable|string',
    //         'item_discount'    => 'nullable|array',
    //         'item_discount.*'  => 'nullable|numeric|min:0',
    //     ]);

    //     DB::transaction(function () use ($validated, $request) {

    //         // 🧾 Generate Next Invoice No
    //         $lastInvoice = Purchase::latest()->value('invoice_no');
    //         $nextInvoice = $lastInvoice
    //             ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //             : 'INV-00001';

    //         // ✍️ Create Purchase with temporary values
    //         $purchase = Purchase::create([
    //             'branch_id'     => auth()->user()->id,
    //             'warehouse_id'  => $validated['warehouse_id'],
    //             'vendor_id'     => $validated['vendor_id'] ?? null,
    //             'purchase_date' => $validated['purchase_date'] ?? now(),
    //             'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //             'note'          => $validated['note'] ?? null,
    //             'subtotal'      => 0,
    //             'discount'      => 0,
    //             'extra_cost'    => 0,
    //             'net_amount'    => 0,
    //             'paid_amount'   => 0,
    //             'due_amount'    => 0,
    //         ]);

    //         $subtotal = 0;

    //         // 🧾 Purchase Items
    //         $productIds = $validated['product_id'] ?? [];
    //         foreach ($productIds as $index => $productId) {
    //             $qty   = $validated['qty'][$index] ?? null;
    //             $price = $validated['price'][$index] ?? null;

    //             if (empty($productId) || empty($qty) || empty($price)) {
    //                 continue;
    //             }

    //             $disc = $validated['item_discount'][$index] ?? 0; // ✅ Correct name
    //             $unit = $validated['unit'][$index] ?? null;

    //             $lineTotal = ($price * $qty) - $disc;

    //             PurchaseItem::create([
    //                 'purchase_id'   => $purchase->id,
    //                 'product_id'    => $productId,
    //                 'unit'          => $unit,
    //                 'price'         => $price,
    //                 'item_discount' => $disc,
    //                 'qty'           => $qty,
    //                 'line_total'    => $lineTotal,
    //             ]);

    //             $subtotal += $lineTotal;

    //             // 📦 Update Stock
    //             $stock = Stock::where('branch_id', auth()->user()->id)
    //                 ->where('warehouse_id', $validated['warehouse_id'])
    //                 ->where('product_id', $productId)
    //                 ->first();

    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             } else {
    //                 Stock::create([
    //                     'branch_id'     => auth()->user()->id,
    //                     'warehouse_id'  => $validated['warehouse_id'],
    //                     'product_id'    => $productId,
    //                     'qty'           => $qty,
    //                 ]);
    //             }
    //         }

    //         // 💵 Final Calculations (use values from request safely)
    //         $discount   = $request->discount ?? 0;
    //         $extraCost  = $request->extra_cost ?? 0;
    //         $netAmount  = ($subtotal - $discount) + $extraCost;

    //         $purchase->update([
    //             'subtotal'    => $subtotal,
    //             'discount'    => $discount,
    //             'extra_cost'  => $extraCost,
    //             'net_amount'  => $netAmount,
    //             'due_amount'  => $netAmount,
    //         ]);

    //         // 📘 Vendor Ledger Update
    //         $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //             ->value('closing_balance') ?? 0;

    //         $newClosingBalance = $previousBalance + $netAmount;

    //         VendorLedger::updateOrCreate(
    //             ['vendor_id' => $validated['vendor_id']],
    //             [
    //                 'vendor_id'         => $validated['vendor_id'],
    //                 'admin_or_user_id'  => auth()->id(),
    //                 'previous_balance'  => $subtotal,
    //                 'closing_balance'   => $newClosingBalance,
    //             ]
    //         );
    //     });

    //     return back()->with('success', 'Purchase saved successfully!');
    // }

    // public function store(Request $request)
    // {

    //         $validated = $request->validate([
    //             'invoice_no'     => 'nullable|string',
    //             'vendor_id'      => 'nullable|exists:vendors,id',
    //             // 'branch_id'      => 'required|exists:branches,id',
    //             'purchase_date'  => 'nullable|date',
    //             'warehouse_id'   => 'nullable|exists:warehouses,id',
    //             'note'           => 'nullable|string',
    //     'discount'       => 'nullable|numeric|min:0',
    //     'extra_cost'     => 'nullable|numeric|min:0',

    //             // Purchase Items
    //             'product_id'     => 'nullable|array',
    //             'product_id.*'   => 'nullable|exists:products,id',
    //             'qty'            => 'nullable|array',
    //             'qty.*'          => 'nullable|numeric|min:1',
    //             'price'          => 'nullable|array',
    //             'price.*'        => 'nullable|numeric|min:0',
    //             'unit'           => 'nullable|array',
    //             'unit.*'         => 'nullable|string',
    //             'item_discount'  => 'nullable|array',
    //             'item_discount.*'=> 'nullable|numeric|min:0',
    //         ]);
    // DB::transaction(function () use ($validated) {

    //     $lastInvoice = Purchase::latest()->value('invoice_no');

    //     $nextInvoice = $lastInvoice
    //         ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //         : 'INV-00001';

    //     // 1️⃣ Create purchase
    //     $purchase = Purchase::create([
    //         'branch_id'     => Auth()->user()->id,
    //         'warehouse_id'  => $validated['warehouse_id'],
    //         'vendor_id'     => $validated['vendor_id'] ?? null,
    //         'purchase_date' => $validated['purchase_date'] ?? now(),
    //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //         'note'          => $validated['note'] ?? null,
    //         'subtotal'      => $validated['subtotal'] ?? 0,
    //         'discount'      => $validated['discount'] ?? 0,
    //         'extra_cost'    => $validated['extra_cost'] ?? 0,
    //         'net_amount'    => $validated['net_amount'] ?? 0,
    //         'paid_amount'   => 0,
    //         'due_amount'    => 0,

    //     ]);

    //     $subtotal = 0;

    //     // 2️⃣ Loop & filter rows
    //     $productIds = $validated['product_id'] ?? [];
    //     foreach ($productIds as $index => $productId) {
    //         $qty   = $validated['qty'][$index] ?? null;
    //         $price = $validated['price'][$index] ?? null;

    //         // Skip row if any essential field is empty
    //         if (empty($productId) || empty($qty) || empty($price)) {
    //             continue;
    //         }

    //         $disc = $validated['item_disc'][$index] ?? 0;
    //         $unit = $validated['unit'][$index] ?? null;

    //         $lineTotal = ($price * $qty) - $disc;

    //         // Save item
    //         PurchaseItem::create([
    //             'purchase_id'   => $purchase->id,
    //             'product_id'    => $productId,
    //             'unit'          => $unit,
    //             'price'         => $price,
    //             'item_discount' => $disc,
    //             'qty'           => $qty,
    //             'line_total'    => $lineTotal,
    //         ]);

    //         $subtotal += $lineTotal;

    //         // 3️⃣ Update stock
    //         $stock = Stock::where('branch_id', Auth()->user()->id)
    //             ->where('warehouse_id', $validated['warehouse_id'])
    //             ->where('product_id', $productId)
    //             ->first();

    //         if ($stock) {
    //             $stock->qty += $qty;
    //             $stock->save();
    //         } else {
    //             Stock::create([
    //                 'branch_id'     => Auth()->user()->id,
    //                 'warehouse_id'  => $validated['warehouse_id'],
    //                 'product_id'    => $productId,
    //                 'qty'           => $qty,
    //             ]);
    //         }
    //     }

    //     // 4️⃣ Update totals
    //     $purchase->update([
    //         'subtotal'    => $subtotal,
    //         'net_amount'  => $subtotal,
    //         'due_amount'  => $subtotal,
    //     ]);

    //     // 5️⃣ Vendor ledger
    //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //         ->value('closing_balance') ?? 0;

    //     $newClosingBalance = $previousBalance + $subtotal;

    //     VendorLedger::updateOrCreate(
    //         ['vendor_id' => $validated['vendor_id']],
    //         [
    //             'vendor_id' => $validated['vendor_id'],
    //             'admin_or_user_id' => Auth::id(),
    //             'previous_balance' => $subtotal,
    //             'closing_balance' => $newClosingBalance,
    //         ]
    //     );

    // });

    // // DB::transaction(function () use ($validated) {

    // // $lastInvoice = Purchase::latest()->value('invoice_no');

    // // // Agar last invoice mila to +1 karo, warna start karo INV-00001
    // // $nextInvoice = $lastInvoice
    // //     ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    // //     : 'INV-00001';

    // //     // 1️⃣ Save main Purchase
    // //     $purchase = Purchase::create([

    // //         'branch_id'     => Auth()->user()->id,
    // //         'warehouse_id'  => $validated['warehouse_id'],
    // //         'vendor_id'     => $validated['vendor_id'] ?? null,
    // //         'purchase_date' => $validated['purchase_date'] ?? now(),
    // //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    // //         'note'          => $validated['note'] ?? null,
    // //         'subtotal'      => 0,
    // //         'discount'      => 0,
    // //         'extra_cost'    => 0,
    // //         'net_amount'    => 0,
    // //         'paid_amount'   => 0,
    // //         'due_amount'    => 0,
    // //     ]);

    // //     $subtotal = 0;

    // //     // 2️⃣ Loop purchase items
    // //     foreach ($validated['product_id'] as $index => $productId) {
    // //         $qty     = $validated['qty'][$index];
    // //         $price   = $validated['price'][$index];
    // //         $disc    = $validated['item_discount'][$index] ?? 0;
    // //         $lineTotal = ($price * $qty) - $disc;

    // //         // Save purchase item
    // //         PurchaseItem::create([
    // //             'purchase_id'   => $purchase->id,
    // //             'product_id'    => $productId,
    // //             'unit'          => $validated['unit'][$index] ?? null,
    // //             'price'         => $price,
    // //             'item_discount' => $disc,
    // //             'qty'           => $qty,
    // //             'line_total'    => $lineTotal,
    // //         ]);

    // //         $subtotal += $lineTotal;

    // //         // 3️⃣ Update stock
    // //         $stock = Stock::where('branch_id',  Auth()->user()->id,)
    // //             ->where('warehouse_id', $validated['warehouse_id'])
    // //             ->where('product_id', $productId)
    // //             ->first();

    // //         if ($stock) {
    // //             $stock->qty += $qty;
    // //             $stock->save();
    // //         } else {
    // //             Stock::create([
    // //                 'branch_id'     => Auth()->user()->id,
    // //                 'warehouse_id'  => $validated['warehouse_id'],
    // //                 'product_id'    => $productId,
    // //                 'qty'           => $qty,
    // //             ]);
    // //         }
    // //     }

    // //     // 4️⃣ Update totals in purchase
    // //     $purchase->update([
    // //         'subtotal'    => $subtotal,
    // //         'net_amount'  => $subtotal,
    // //         'due_amount'  => $subtotal,
    // //     ]);

    // //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    // //         ->value('closing_balance') ?? 0; // If no previous balance, start from 0
    // //     // Calculate new balances

    // //     $newPreviousBalance = $subtotal;

    // //     $newClosingBalance = $previousBalance + $subtotal;
    // //     $userId = Auth::id();

    // //     // Update or create distributor ledger
    // //     VendorLedger::updateOrCreate(
    // //         ['vendor_id' => $validated['vendor_id']],
    // //         [
    // //             'vendor_id' => $validated['vendor_id'],
    // //             'admin_or_user_id' => $userId,
    // //             'previous_balance' => $newPreviousBalance,
    // //             'closing_balance' => $newClosingBalance,
    // //         ]
    // //     );

    // });

    //     return redirect()->back()->with('success', 'Purchase saved successfully!');
    // }

    public function edit($id)
    {
        $purchase = Purchase::with(['items.product.packings', 'items.uom', 'items.batch'])->findOrFail($id);

        if ($purchase->status_purchase != 'un-post' && $purchase->status_purchase != 'draft') {
            return redirect()->route('Purchase.home')->with('error', 'Cannot edit purchase. Status is not draft or un-posted.');
        }

        $branchId = $purchase->branch_id ?? $this->getBranchId();
        $branch = \App\Models\Branch::find($branchId);
        $branchName = $branch ? $branch->name : 'Head Office';
        $nextInvoice = $purchase->invoice_no;

        $Vendor = Vendor::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();
        $Warehouse = Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();
        
        $warehouses = $Warehouse;
        $vendors = $Vendor;
        $Purchase = Purchase::where('status_purchase', 'draft')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Filter accounts to only show Cash (1) and Bank (2) heads
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        // If it's a PO (draft) or Un-posted GRN, use the modern PO/GRN edit view
        if ($purchase->status_purchase == 'un-post' || $purchase->status_purchase == 'draft') {
            return view('admin_panel.purchase.purchase_order.edit', compact('purchase', 'Vendor', 'Warehouse', 'accounts', 'warehouses', 'vendors', 'branchId', 'branchName', 'nextInvoice', 'Purchase'));
        }

        return view('admin_panel.purchase.edit', compact('purchase', 'Vendor', 'Warehouse', 'accounts', 'warehouses', 'vendors', 'branchId', 'branchName', 'nextInvoice', 'Purchase'));
    }

    public function update(Request $request, $id)
    {
        // Pre-sanitize uom_id before validation: convert 'NEW' / non-numeric to null
        if ($request->has('uom_id') && is_array($request->uom_id)) {
            $request->merge([
                'uom_id' => array_map(
                    fn($v) => (is_numeric($v) && (int)$v > 0) ? (int)$v : null,
                    $request->uom_id
                )
            ]);
        }
        $attributes = [];
        if ($request->has('product_id') && is_array($request->product_id)) {
            foreach ($request->product_id as $i => $id) {
                $row = $i + 1;
                $attributes["product_id.$i"]    = "Row $row Product";
                $attributes["qty.$i"]           = "Row $row Quantity";
                $attributes["price.$i"]         = "Row $row Price";
                $attributes["item_discount.$i"] = "Row $row Discount";
                $attributes["unit.$i"]          = "Row $row Unit";
                $attributes["uom_id.$i"]        = "Row $row UOM";
                $attributes["loose_qty.$i"]     = "Row $row Loose Qty";
            }
        }

        $validated = $request->validate([
            'invoice_no' => 'nullable|string',
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'extra_cost' => 'nullable|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'product_id' => 'array',
            'product_id.*' => 'nullable|exists:products,id',
            'qty' => 'array',
            'qty.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'loose_qty' => 'array',
            'loose_qty.*' => 'nullable|numeric|min:0',
            'price' => 'array',
            'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'unit' => 'array',
            'unit.*' => 'nullable|required_with:product_id.*|string',
            'item_discount.*' => 'nullable|numeric|min:0',
            'uom_id' => 'array',
            'uom_id.*' => 'nullable|exists:product_uoms,id',
            'hs_code' => 'array',
            'hs_code.*' => 'nullable|string',
        ], [], $attributes);

        DB::transaction(function () use ($validated, $request, $id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            $branchId = (int) ($validated['branch_id'] ?? $purchase->branch_id ?? 1);
            $warehouseId = (int) ($validated['warehouse_id'] ?? $purchase->warehouse_id);

            // Map old totals per product for Stock Delta Logic
            $oldMap = $purchase->items->groupBy('product_id')->map(fn ($g) => (float) $g->sum('qty'));

            // Delete any batches linked to these items to avoid orphans
            \App\Models\ProductBatch::whereIn('purchase_item_id', $purchase->items->pluck('id'))->delete();

            // Delete old items
            $purchase->items()->delete();

            $subtotal = 0;
            $newMap = collect();

            // Arrays from request
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];
            $itemDiscTypes = $request->item_discount_type ?? [];

            // Snapshot fields (Raw Request)
            $sizeModes = $request->size_mode ?? [];
            $ppbs = $request->pieces_per_box ?? [];
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];
            $gstPercents = $request->gst_percent ?? $request->gst ?? $request->st_tax_percent ?? [];
            $gstAmounts = $request->gst_amount ?? $request->st_tax_amount ?? [];
            $itPercents = $request->it_percent ?? [];
            $advPercents = $request->adv_tax_percent ?? [];
            $uomIds = $request->uom_id ?? [];
            $lotNos = $request->lot_no ?? [];
            $expDates = $request->expiry ?? [];
            $mfgDates = $request->mfg_date ?? [];

            $totalGst = 0;
            foreach ($pids as $i => $pid) {
                $pid = (int) ($pid ?? 0);
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);

                if (! $pid || $qty <= 0) {
                    continue;
                }

                $disc = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? null;
                $uomId = $uomIds[$i] ?? null;

                // Handle Packing (UOM) Logic
                $uom = null;
                if ($uomId) {
                    $uom = \App\Models\ProductUom::find($uomId);
                }

                $ppb = 1;
                $uomName = 'Piece';
                if ($uom) {
                    $uomName = $uom->name;
                    $ppb = $uom->pieces_per_box ?: 1;
                    // Update prices permanently if needed, but for PO, we just use the price
                }

                // --- Calculation Logic (Matches store()) ---
                $curSizeMode = $sizeModes[$i] ?? null;
                $curPPM2 = (float) ($ppm2[$i] ?? 0);

                $ppb = isset($ppbs[$i]) && $ppbs[$i] > 0 ? (float) $ppbs[$i] : 1;

                $totalPieces = 0;
                if ($curSizeMode === 'by_cartons' || $curSizeMode === 'by_carton') {
                    $qtyStr = (string) $qty;
                    if (str_contains($qtyStr, '.')) {
                        $parts = explode('.', $qtyStr);
                        $boxes = (int) $parts[0];
                        $pieces = (int) $parts[1];
                        $totalPieces = ($boxes * $ppb) + $pieces;
                    } else {
                        $totalPieces = $qty * $ppb;
                    }
                } else {
                    if ($curSizeMode === 'by_size') {
                        $totalPieces = $qty;
                    } else {
                        $totalPieces = $qty * $ppb;
                    }
                }

                // Regardless of mode, the user expects 'price' to be the Rate Per Piece (as per Turn 7 req)
                $grossTotal = $totalPieces * $price;
                
                $curDiscType = $itemDiscTypes[$i] ?? 'amount';
                $lineDiscAmount = ($curDiscType === 'percent') ? ($grossTotal * $disc / 100) : $disc;

                $lineTotal = $grossTotal - $lineDiscAmount;
                // ------------------------------------------

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $pid,
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $disc,
                    'item_discount_type' => $curDiscType,
                    'qty' => $qty,
                    'line_total' => $lineTotal,

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $ppb,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $boxesQtys[$i] ?? 0,
                    'loose_qty' => $looseQtys[$i] ?? 0,
                    'length' => (string) ($lengths[$i] ?? ''),
                    'width' => (string) ($widths[$i] ?? ''),
                    'gst_percent' => (float) ($gstPercents[$i] ?? 0),
                    'gst_amount' => (float) ($gstAmounts[$i] ?? 0),
                    'it_percent' => (float) ($itPercents[$i] ?? 0),
                    'adv_tax_percent' => (float) ($advPercents[$i] ?? 0),
                    'uom_id' => $uomId,
                    'uom_name' => $uomName,
                    'uom_factor' => $ppb,
                    'hs_code' => $request->hs_code[$i] ?? null,
                    'batch_no' => $lotNos[$i] ?? null,
                    'mfg_date' => $mfgDates[$i] ?? null,
                    'exp_date' => $expDates[$i] ?? null,
                ]);

                $subtotal += $lineTotal;
                
                $rowGst = (float) ($gstAmounts[$i] ?? 0);
                $rowOtherTax = $lineTotal * ((float)($itPercents[$i] ?? 0) + (float)($advPercents[$i] ?? 0)) / 100;
                
                $totalGst += ($rowGst + $rowOtherTax);
                $newMap[$pid] = ($newMap[$pid] ?? 0) + $qty;
            }

            // header update
            $purchase->update([
                'vendor_id' => $validated['vendor_id'] ?? $purchase->vendor_id,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'purchase_date' => $validated['purchase_date'] ?? $purchase->purchase_date,
                'invoice_no' => $validated['invoice_no'] ?? $purchase->invoice_no,
                'note' => $validated['note'] ?? $purchase->note,
                'enable_hs_code' => $request->enable_hs_code ? 1 : 0,
                'is_gst_invoice' => $request->has('is_gst_invoice') ? 1 : 0,
            ]);

            // totals
            $discount = (float) ($request->discount ?? 0);
            $discountType = $request->bill_discount_type ?? 'amount';
            $billDiscAmount = ($discountType === 'percent') ? ($subtotal * $discount / 100) : $discount;

            $extraCost = (float) ($request->extra_cost ?? 0);
            $freightCharges = (float) ($request->freight_charges ?? 0);

            // Net Amount logic: Subtotal - Discount + Extra Cost + Freight Charges + Total GST
            $netAmount = ($subtotal - $billDiscAmount) + $extraCost + $freightCharges + $totalGst;

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'discount_type' => $discountType,
                'extra_cost' => $extraCost,
                'freight_charges' => $freightCharges,
                'total_gst' => $totalGst,
                'net_amount' => $netAmount,
            ]);

            // Recalculate Due based on net - paid
            $paid = $purchase->paid_amount;
            $purchase->update(['due_amount' => $netAmount - $paid]);

            // Product master prices are not updated here anymore.
            $purchase->load('items');

            // If NOT draft, run full approval
            $status = $request->input('status_purchase', 'draft');
            if ($status === 'post') {
                // Update status and generate GRN number if it was a PO
                $updateData = ['status_purchase' => 'post'];
                if (str_starts_with($purchase->invoice_no, 'PO-')) {
                    $updateData['invoice_no'] = Purchase::generateInvoiceNo('GRN-', $purchase->branch_id);
                }
                $purchase->update($updateData);

                $this->approvePurchase($purchase); // Basic Stock + Ledger + Voucher

                // B. Record Payment (Only available in immediate Request)
                try {
                    $transactionService = app(\App\Services\TransactionService::class);
                    $paymentAccountIds = $request->input('payment_account_id', []);
                    $paymentAmounts = $request->input('payment_amount', []);

                    if (! empty(array_filter($paymentAccountIds))) {
                        $transactionService->createPaymentForPurchase(
                            $purchase,
                            $paymentAccountIds,
                            $paymentAmounts
                        );
                    }
                } catch (\Exception $e) { 
                    \Log::error("Payment Creation Error in Update: ". $e->getMessage());
                }
            }
        });

        return redirect()->route('Purchase.home')->with('success', 'Purchase updated successfully!');
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);

            $branchId = (int) ($purchase->branch_id ?? 1);
            $warehouseId = (int) ($purchase->warehouse_id);

            // linked to gatepass? then NO stock changes
            $isLinkedToGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

            if (! $isLinkedToGatepass) {
                $movs = [];
                $now = now();

                foreach ($purchase->items as $it) {
                    $pid = (int) $it->product_id;
                    $qty = (float) $it->qty;

                    $movs[] = [
                        'product_id' => $pid,
                        'type' => 'out',
                        'qty' => $qty,
                        'ref_type' => 'PURCHASE_DELETE',
                        'ref_id' => $purchase->id,
                        'note' => 'Delete purchase (reverse)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // stocks rollback — UOM-aware debit
                    $uomIdForRollback = $item->uom_id ?? null;
                    StockService::debit($pid, $uomIdForRollback, $warehouseId, $branchId, $qty);
                }

                if (! empty($movs)) {
                    DB::table('stock_movements')->insert($movs);
                }
            }

            // Delete any batches linked to these items to avoid orphans
            \App\Models\ProductBatch::whereIn('purchase_item_id', $purchase->items->pluck('id'))->delete();

            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function Invoice($id)
    {
        $purchase = Purchase::with([
            'items.product.brand',
            'items.product.unit',
            'items.uom',
            'vendor',
            'warehouse',
        ])->findOrFail($id);

        return view('admin_panel.purchase.Invoice', compact('purchase'));
    }

    public function receipt($id)
    {
        $purchase = Purchase::with(['items.product', 'vendor'])->findOrFail($id);

        return view('admin_panel.purchase.receipt', compact('purchase'));
    }

    public function grnReport($id)
    {
        $purchase = Purchase::with(['items.product', 'items.uom', 'vendor', 'warehouse'])->findOrFail($id);

        return view('admin_panel.purchase.grn_report', compact('purchase'));
    }

    public function grnDownload($id)
    {
        $purchase = Purchase::with(['items.product', 'items.uom', 'vendor', 'warehouse'])->findOrFail($id);
        $pdf = Pdf::loadView('admin_panel.purchase.grn_report', compact('purchase'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf->download("GRN-{$purchase->invoice_no}.pdf");
    }

    // purchase_reutun

    public function showReturnForm($id = null)
    {
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();
        $vendors = \App\Models\Vendor::all();
        $warehouses = \App\Models\Warehouse::all();

        if (! $id) {
            $purchase = null;
            $purchaseItems = [];

            return view('admin_panel.purchase.purchase_return.create', compact('purchase', 'accounts', 'purchaseItems', 'vendors', 'warehouses'));
        }

        $data = $this->prepareReturnData($id);
        if (isset($data['error'])) {
            return redirect()->route('purchase.return.index')->with('error', $data['error']);
        }

        $purchase = $data['purchase'];
        $purchaseItems = $data['purchaseItems'];

        return view('admin_panel.purchase.purchase_return.create', compact('purchase', 'accounts', 'purchaseItems', 'vendors', 'warehouses'));
    }

    public function getPurchaseDetails($id)
    {
        $data = $this->prepareReturnData($id);
        if (isset($data['error'])) {
            return response()->json(['success' => false, 'message' => $data['error']], 422);
        }

        return response()->json([
            'success' => true,
            'purchase' => [
                'id' => $data['purchase']->id,
                'invoice_no' => $data['purchase']->invoice_no,
                'vendor_id' => $data['purchase']->vendor_id,
                'vendor_name' => optional($data['purchase']->vendor)->name ?? 'Unknown Vendor',
                'warehouse_id' => $data['purchase']->warehouse_id,
                'purchase_date' => $data['purchase']->created_at->format('d M, Y h:i A'),
                'is_gst_invoice' => (int) ($data['purchase']->is_gst_invoice ?? 1),
            ],
            'items' => $data['purchaseItems'],
        ]);
    }

    private function prepareReturnData($id)
    {
        $purchase = Purchase::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);

        $pastReturns = \App\Models\PurchaseReturn::where('purchase_id', $id)
            ->with('items')
            ->get();

        $returnedQtyMap = []; // Total Pieces mapped by product_id + batch_no + warehouse_id
        foreach ($pastReturns as $pr) {
            foreach ($pr->items as $prItem) {
                $batchKey = $prItem->batch_no ?? 'no-batch';
                $whKey = $prItem->warehouse_id ?? 0;
                $mapKey = "{$prItem->product_id}_{$batchKey}_{$whKey}";

                if (! isset($returnedQtyMap[$mapKey])) {
                    $returnedQtyMap[$mapKey] = 0;
                }
                
                // Find matching purchase item to get the ppb/mode used during that specific purchase
                $match = $purchase->items->where('product_id', $prItem->product_id)
                    ->where('batch_no', $prItem->batch_no)
                    ->where('warehouse_id', $prItem->warehouse_id)
                    ->first();
                
                // Fallback if not matched by batch
                if (!$match) {
                    $match = $purchase->items->where('product_id', $prItem->product_id)->first();
                }

                $itemPpb = $match ? (float)($match->pieces_per_box ?? 1) : 1;
                $itemSizeMode = $match ? ($match->size_mode ?? 'by_pieces') : 'by_pieces';
                
                // qty is always stored as pieces (since the fix). For legacy records with decimal notation, parse it.
                $prItemQty = (float) $prItem->qty;
                $prItemLoose = (float) ($prItem->loose_qty ?? 0);
                // If loose_qty was stored separately (legacy), add it
                $returnedQtyMap[$mapKey] += $prItemQty + $prItemLoose;
            }
        }

        $purchaseItems = [];
        $hasReturnableItems = false;

        foreach ($purchase->items as $item) {
            // Use the SNAPSHOT ppb stored on the purchase item — NOT the product master.
            // The product master may have changed after the original purchase.
            $ppb = (float) ($item->pieces_per_box ?? $item->uom_factor ?? 1);
            if ($ppb <= 0) $ppb = 1;
            // Fallback to product if snapshot was never set
            if ($ppb == 1 && optional($item->product)->pieces_per_box > 1) {
                $ppb = (float) $item->product->pieces_per_box;
            }

            $sizeMode = $item->size_mode ?? optional($item->product)->size_mode ?? 'by_pieces';

            $itemBatchKey = $item->batch_no ?? 'no-batch';
            $itemWhKey = $item->warehouse_id ?? 0;
            $itemMapKey = "{$item->product_id}_{$itemBatchKey}_{$itemWhKey}";

            $alreadyReturnedPieces = (float)($returnedQtyMap[$itemMapKey] ?? 0);
            // Use stored total_pieces if available — it's the authoritative piece count
            $totalBoughtPieces = (float) ($item->total_pieces ?? $this->parseNotationToPieces($item->qty, $ppb, $sizeMode, $item->loose_qty));
            
            $remainingPieces = max(0.000, $totalBoughtPieces - $alreadyReturnedPieces);

            if ($remainingPieces > 0.001) {
                $hasReturnableItems = true;
            }

            $purchaseItems[] = [
                'product_id'       => $item->product_id,
                'item_name'        => optional($item->product)->item_name ?? 'Unknown Product',
                'brand'            => optional(optional($item->product)->brand)->name ?? '',
                'item_code'        => optional($item->product)->item_code ?? '',
                'pieces_per_box'   => (int) $ppb,
                'size_mode'        => $sizeMode,
                'pieces_per_m2'    => $item->pieces_per_m2 ?? optional($item->product)->pieces_per_m2 ?? 0,
                'price'            => (float) $item->price,
                'original_qty'     => $totalBoughtPieces,
                'returned_qty'     => $alreadyReturnedPieces,
                'qty'              => $remainingPieces,
                'max_returnable'   => $remainingPieces,
                'unit'             => $item->unit ?? 'pc',
                'discount'         => (float) ($item->item_discount ?? 0),
                'discount_type'    => $item->item_discount_type ?? 'amount',
                'gst_percent'      => (float) ($item->gst_percent ?? 0),
                'it_percent'       => (float) ($item->it_percent ?? 0),
                'adv_tax_percent'  => (float) ($item->adv_tax_percent ?? 0),
                'batch_no'         => $item->batch_no ?? '',
                'mfg_date'         => $item->mfg_date ? (\Carbon\Carbon::parse($item->mfg_date)->format('Y-m-d')) : '',
                'exp_date'         => $item->exp_date ? (\Carbon\Carbon::parse($item->exp_date)->format('Y-m-d')) : '',
                'warehouse_id'     => $item->warehouse_id,
                'purchase_item_id' => $item->id,
                'uom_name'         => $item->uom_name ?? ($ppb > 1 ? '1x'.(int)$ppb : 'Piece'),
            ];
        }

        if (! $hasReturnableItems && $purchase->status_purchase == 'Returned') {
            return ['error' => 'This purchase has clearly been fully returned already.'];
        }

        return [
            'purchase' => $purchase,
            'purchaseItems' => $purchaseItems,
        ];
    }

    // store return
    public function storeReturn(Request $request)
    {
        $attributes = [];
        if ($request->has('product_id') && is_array($request->product_id)) {
            foreach ($request->product_id as $i => $id) {
                $row = $i + 1;
                $attributes["product_id.$i"]    = "Row $row Product";
                $attributes["qty.$i"]           = "Row $row Quantity";
                $attributes["price.$i"]         = "Row $row Price";
                $attributes["item_disc_val.$i"] = "Row $row Discount";
            }
        }

        $validated = $request->validate([
            'purchase_id'       => 'nullable|exists:purchases,id',
            'vendor_id'         => 'required|exists:vendors,id',
            'warehouse_id'      => 'required|array',
            'warehouse_id.*'    => 'required|exists:warehouses,id',
            'return_date'       => 'required|date',
            'return_reason'     => 'nullable|string|max:255',
            'product_id'        => 'required|array',
            'product_id.*'      => 'required|exists:products,id',
            'qty'               => 'nullable|array',
            'qty.*'             => 'nullable|numeric|min:0',
            'price'             => 'required|array',
            'purchase_item_id'  => 'nullable|array',
            'item_disc_val'     => 'nullable|array',
            'item_disc_type'    => 'nullable|array',
            'payment_account_id'=> 'nullable|array',
            'payment_amount'    => 'nullable|array',
        ], [], $attributes);

        // Prevent identical duplicate submissions within a short timeframe
        if (! empty($validated['purchase_id'])) {
            $duplicate = PurchaseReturn::where('purchase_id', $validated['purchase_id'])
                ->where('created_at', '>=', now()->subSeconds(15))
                ->exists();
            if ($duplicate) {
                return redirect()->route('purchase.return.index')->with('success', 'Purchase return processed successfully. (Duplicate request ignored)');
            }
        }

        try {
            DB::beginTransaction();

            // 1. Generate Return Invoice #
            $lastReturn = PurchaseReturn::latest()->first();
            $nextInvoice = 'PRTN-'.str_pad(optional($lastReturn)->id + 1 ?? 1, 5, '0', STR_PAD_LEFT);

            // 2. Create Purchase Return Record
            $purchase = $request->purchase_id ? Purchase::find($request->purchase_id) : null;
            $remarks = $request->return_reason;
            if ($purchase) {
                $remarks .= ' (Ref Invoice: '.$purchase->invoice_no.')';
            }

            // Precisely determine the correct branch_id so it never defaults to null
            $warehouse = \App\Models\Warehouse::find($validated['warehouse_id']);
            $determinedBranchId = 1;
            if ($purchase && $purchase->branch_id) {
                $determinedBranchId = $purchase->branch_id;
            } elseif ($warehouse && $warehouse->branch_id) {
                $determinedBranchId = $warehouse->branch_id;
            } else {
                $determinedBranchId = $this->getBranchId() ?? 1;
            }

            $return = PurchaseReturn::create([
                'purchase_id' => $purchase ? $purchase->id : null,
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'][0] ?? ($purchase->warehouse_id ?? 1),
                'return_invoice' => $nextInvoice,
                'return_date' => $validated['return_date'],
                'return_reason' => $validated['return_reason'],
                'remarks' => $remarks,
                'branch_id' => (int) $determinedBranchId,
                'bill_amount' => 0, // calculated below
                'item_discount' => 0,
                'extra_discount' => $request->extra_discount ?? 0,
                'net_amount' => 0,
                'paid' => 0,
                'balance' => 0,
            ]);

            $subtotal = 0;
            $totalItemDiscount = 0;
            $movements = [];
            $now = now();

            // 3. Process Items & Stock
            foreach ($validated['product_id'] as $index => $productId) {
            // Qty is sent as total pieces directly from frontend
                $qtyVal = (float)($validated['qty'][$index] ?? 0);
                $price  = (float)($validated['price'][$index] ?? 0);

                if ($qtyVal <= 0) {
                    continue;
                }

                // Find original item to get snapshots
                $origItem = \App\Models\PurchaseItem::where('purchase_id', $purchase->id ?? 0)
                    ->where('product_id', $productId)
                    ->first();

                // Fallback to Product defaults if no original item
                $product = Product::find($productId);
                $ppb = $origItem ? ($origItem->pieces_per_box ?? 1) : ($product->pieces_per_box ?? 1);
                $sizeMode = $origItem ? ($origItem->size_mode ?? 'by_pieces') : ($product->size_mode ?? 'by_pieces');
                $ppm2 = $origItem ? ($origItem->pieces_per_m2 ?? 0) : ($product->pieces_per_m2 ?? 0);

                // Piece-based math: Qty is already in pieces from frontend
                $stockQty = (float) $qtyVal;
                $lineTotal = round($stockQty * $price, 2);

                $itemDisc = (float)($request->item_disc_val[$index] ?? $request->item_disc[$index] ?? 0);
                $discType = $request->item_disc_type[$index] ?? $request->item_discount_type[$index] ?? 'amount';
                
                // If it's a percentage discount, we need to calculate it for the return row
                if ($discType === 'percent') {
                    $itemDisc = round($lineTotal * $itemDisc / 100, 2);
                }

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id'         => $productId,
                    'warehouse_id'       => $validated['warehouse_id'][$index],
                    'qty'                => $qtyVal,  // Always pieces
                    'loose_qty'          => 0,
                    'price'              => $price,
                    'item_discount'      => $itemDisc,
                    'item_discount_type' => $discType,
                    'unit'               => 'pc',
                    'line_total'         => $lineTotal,
                    'batch_no'           => $request->batch_no[$index] ?? null,
                    'mfg_date' => $request->mfg_date[$index] ?? null,
                    'exp_date' => $request->exp_date[$index] ?? null,
                ]);

                // Update Stock — UOM-aware debit for Purchase Return
                $actionBranchId    = (int) ($return->branch_id ?: 1);
                $actionWarehouseId = (int) $validated['warehouse_id'][$index];
                $uomIdForReturn    = $origItem->uom_id ?? null;

                StockService::debit($productId, $uomIdForReturn, $actionWarehouseId, $actionBranchId, $stockQty);

                // UC6: Sync batch qty — deduct from FEFO batches for this product+warehouse
                // (Update logic: If specific batch is provided, deduct from it first)
                $targetBatchNo = $request->batch_no[$index] ?? null;
                $remainingToDeduct = (float) $stockQty;

                // 1. Prioritize deduction from the specific batch created by this Purchase Item
                $purchaseItemId = $request->purchase_item_id[$index] ?? null;
                if ($purchaseItemId) {
                    $origBatch = ProductBatch::where('purchase_item_id', $purchaseItemId)
                        ->where('qty_remaining', '>', 0)
                        ->first();
                    if ($origBatch) {
                        $take = min($origBatch->qty_remaining, $remainingToDeduct);
                        $origBatch->qty_remaining = max(0, $origBatch->qty_remaining - $take);
                        if ($origBatch->qty_remaining <= 0) $origBatch->status = 'consumed';
                        $origBatch->save();
                        $remainingToDeduct -= $take;
                    }
                }

                // 2. If still remaining, try by Batch Number (name) in this warehouse
                if ($remainingToDeduct > 0 && $targetBatchNo) {
                    $namedBatch = ProductBatch::where('product_id', $productId)
                        ->where('warehouse_id', $actionWarehouseId)
                        ->where('batch_number', $targetBatchNo)
                        ->where('qty_remaining', '>', 0)
                        ->first();
                    if ($namedBatch) {
                        $take = min($namedBatch->qty_remaining, $remainingToDeduct);
                        $namedBatch->qty_remaining = max(0, $namedBatch->qty_remaining - $take);
                        if ($namedBatch->qty_remaining <= 0) $namedBatch->status = 'consumed';
                        $namedBatch->save();
                        $remainingToDeduct -= $take;
                    }
                }

                // 3. Fallback to FEFO across ALL batches with stock (ignoring 'available' scope for returns)
                if ($remainingToDeduct > 0) {
                    $batchesToDeduct = ProductBatch::where('product_id', $productId)
                        ->where('warehouse_id', $actionWarehouseId)
                        ->where('qty_remaining', '>', 0)
                        ->orderBy('exp_date', 'asc')
                        ->get();

                    foreach ($batchesToDeduct as $batchRow) {
                        if ($remainingToDeduct <= 0) break;
                        $take = min($batchRow->qty_remaining, $remainingToDeduct);
                        $batchRow->qty_remaining = max(0, $batchRow->qty_remaining - $take);
                        if ($batchRow->qty_remaining <= 0) $batchRow->status = 'consumed';
                        $batchRow->save();
                        $remainingToDeduct -= $take;
                    }
                }

                // Prepare Stock Movement
                $movements[] = [
                    'product_id' => $productId,
                    'type' => 'out', // Return OUT to vendor
                    'qty' => -$stockQty, // Store negative for OUT movements to sync with Reports
                    'ref_type' => 'PURCHASE_RETURN',
                    'ref_id' => $return->id,
                    'note' => "Return #{$nextInvoice}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $subtotal += $lineTotal;
                $totalItemDiscount += $itemDisc;
            }

            // Bulk Insert Movements
            if (! empty($movements)) {
                DB::table('stock_movements')->insert($movements);
            }

            $netAmount = ($subtotal - $totalItemDiscount) - ($request->extra_discount ?? 0);

            // 4. Handle Refund Payment
            $totalPaid = 0;
            if (! empty($request->payment_account_id)) {
                $transactionService = app(\App\Services\TransactionService::class);
                // We create a RECEIPT Voucher for the refund received
                // Currently doing it manually or via Service if supported?
                // For simplicity, we create Receipt Voucher via loop below or Service if exists.
                // Service createReceiptVoucher takes Customer, so we might need Vendor support or use Journal.

                // Let's just update the return record 'paid' amount and handle Ledger manually for simplicity OR call service if adapted.
                // The User asked for "apply it everything... create journal entries".
                // If refund is received, it's Cash DEBIT, Vendor CREDIT (Wait.. Refund means Cash In).
                // Yes: Dr Cash, Cr Vendor (to offset the Return Debit Note).

                // Let's stick to simple ledger updates for Refund part unless we upgrade Service for Vendor Receipts.
                // The requested flow: Return -> Reduces Payable. Refund -> Increases Payable (since they paid us back).

                $voucherService = app(\App\Services\VoucherService::class);
                $apId = app(\App\Services\BalanceService::class)->getAccountsPayableId();

                foreach ($request->payment_account_id as $idx => $accId) {
                    $amt = (float) ($request->payment_amount[$idx] ?? 0);
                    if ($accId && $amt > 0) {
                        $totalPaid += $amt;

                        $voucherService->createVoucher(
                            [
                                'voucher_type' => \App\Models\VoucherMaster::TYPE_RECEIPT,
                                'date' => $validated['return_date'],
                                'status' => 'posted',
                                'party_type' => \App\Models\Vendor::class,
                                'party_id' => $validated['vendor_id'],
                                'remarks' => "Refund for Return #{$nextInvoice}",
                            ],
                            [
                                // Dr Cash
                                [
                                    'account_id' => $accId,
                                    'debit' => $amt,
                                    'credit' => 0,
                                    'narration' => 'Cash Refund Received',
                                ],
                                // Cr Accounts Payable (Vendor)
                                [
                                    'account_id' => $apId,
                                    'debit' => 0,
                                    'credit' => $amt,
                                    'narration' => 'Refund from Vendor - Return #'.$nextInvoice,
                                ],
                            ]
                        );
                    }
                }
            }

            $return->update([
                'bill_amount' => $subtotal,
                'item_discount' => $totalItemDiscount,
                'net_amount' => $netAmount,
                'paid' => $totalPaid,
                'balance' => $netAmount - $totalPaid,
            ]);

            // Update Purchase Status
            if ($purchase) {
                // Calculate if anything's left to return
                $returnableBase = max(0, (float) $purchase->net_amount - (float) $purchase->extra_cost);
                $totalReturned = \App\Models\PurchaseReturn::where('purchase_id', $purchase->id)->sum('net_amount');

                $newStatus = ($totalReturned >= $returnableBase) ? 'Returned' : 'Partial Return';
                $purchase->update(['status_purchase' => $newStatus]);
            }

            // 5. Update Accounting & Vendor Ledger (Detailed Tracking)
            $transactionService = app(\App\Services\TransactionService::class);
            $balanceService = app(\App\Services\BalanceService::class);

            // A. Create General Ledger Voucher for Return (Dr AP, Cr Purchase Return)
            // This updates the Chart of Accounts (COA)
            if (method_exists($transactionService, 'createPurchaseReturnVoucher')) {
                $transactionService->createPurchaseReturnVoucher($return);
            }

            // B. Detailed Vendor Ledger Entries (Legacy Tracking)
            // We record the "Return" as a Debit to the Vendor (liability decreases)
            $lastLedger = \App\Models\VendorLedger::where('vendor_id', $validated['vendor_id'])->orderBy('id', 'desc')->first();
            $currentBal = $lastLedger ? $lastLedger->closing_balance : 0;

            \App\Models\VendorLedger::create([
                'vendor_id' => $validated['vendor_id'],
                'branch_id' => $return->branch_id,
                'admin_or_user_id' => auth()->id(),
                'debit' => $netAmount,
                'credit' => 0,
                'description' => "Purchase Return #{$nextInvoice}".($purchase ? " (Ref: {$purchase->invoice_no})" : ''),
                'previous_balance' => $currentBal,
                'opening_balance' => 0,
                'closing_balance' => $currentBal - $netAmount,
                'source_type' => PurchaseReturn::class,
                'source_id' => $return->id,
            ]);

            // C. If there was a Cash/Bank refund (Paid amount), we Record the Credit Entry
            if ($totalPaid > 0) {
                $lastLedger = \App\Models\VendorLedger::where('vendor_id', $validated['vendor_id'])->orderBy('id', 'desc')->first();
                $currentBal = $lastLedger ? $lastLedger->closing_balance : 0;

                \App\Models\VendorLedger::create([
                    'vendor_id' => $validated['vendor_id'],
                    'branch_id' => $return->branch_id,
                    'admin_or_user_id' => auth()->id(),
                    'debit' => 0,
                    'credit' => $totalPaid,
                    'description' => "Refund Received for Return #{$nextInvoice}",
                    'previous_balance' => $currentBal,
                    'opening_balance' => 0,
                    'closing_balance' => $currentBal + $totalPaid,
                    'source_type' => PurchaseReturn::class,
                    'source_id' => $return->id,
                ]);
            }

            DB::commit();

            return redirect()->route('purchase.return.index')->with('success', 'Purchase return processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error processing return: '.$e->getMessage());
        }
    }

    public function purchaseReturnIndex()
    {
        $branchId = $this->getBranchId();
        $returns = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'purchase', 'items.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        // Calculate updated financial details for each return
        $returns->each(function ($return) {
            if ($return->purchase) {
                $purchase = $return->purchase;

                // Original Purchase Amounts
                $return->original_net_amount = $purchase->net_amount;
                $return->original_paid_amount = $purchase->paid_amount;
                $return->original_due_amount = $purchase->due_amount;

                // The returnable base = net_amount minus extra_cost
                $returnableBase = max(0, (float) $purchase->net_amount - (float) $purchase->extra_cost);

                // Calculate total item-value returned for this purchase
                $totalReturned = \App\Models\PurchaseReturn::where('purchase_id', $purchase->id)
                    ->sum('net_amount');

                // New amounts after return(s)
                $return->new_net_amount = max(0, $returnableBase - $totalReturned);
                $return->new_due_amount = max(0, $purchase->due_amount - $return->net_amount);
                $return->total_returned = $totalReturned;
            }

            // Batch Info Summary
            $batchNos = $return->items->pluck('batch_no')->filter()->unique()->implode(', ');
            $mfgDates = $return->items->pluck('mfg_date')->filter()->unique()->implode(', ');
            $expDates = $return->items->pluck('exp_date')->filter()->unique()->implode(', ');

            $return->batch_summary = $batchNos ?: '-';
            $return->mfg_summary = $mfgDates ?: '-';
            $return->exp_summary = $expDates ?: '-';
        });

        return view('admin_panel.purchase.purchase_return.index', compact('returns'));
    }

    public function viewReturn($id)
    {
        $return = \App\Models\PurchaseReturn::with([
            'vendor',
            'warehouse',
            'items.product',
            'purchase.items', // Load original purchase items for size_mode / ppb snapshots
        ])->findOrFail($id);

        // Build a lookup: product_id => original purchase item
        $origItemMap = [];
        if ($return->purchase) {
            foreach ($return->purchase->items as $pi) {
                $origItemMap[$pi->product_id] = $pi;
            }
        }

        return view('admin_panel.purchase.purchase_return.show', compact('return', 'origItemMap'));
    }

    public function getApprovedInvoices(Request $request)
    {
        $branchId = $this->getBranchId();
        $vendorId = $request->vendor_id;

        $purchases = Purchase::with(['vendor', 'returns'])
            ->where('status_purchase', 'post')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
            ->latest()
            ->limit(100)
            ->get();

        $data = $purchases->map(function ($p) {
            $totalReturned = $p->returns->sum('net_amount');
            $returnableBase = max(0, (float) $p->net_amount - (float) $p->extra_cost);
            $isFullyReturned = $totalReturned >= $returnableBase;

            return [
                'id' => $p->id,
                'invoice_no' => $p->invoice_no,
                'vendor_name' => $p->vendor->name ?? 'N/A',
                'purchase_date' => \Carbon\Carbon::parse($p->purchase_date)->format('d M, Y'),
                'net_amount' => number_format($p->net_amount, 2),
                'is_fully_returned' => $isFullyReturned,
            ];
        });

        return response()->json($data);
    }
    private function parseNotationToPieces($qty, $ppb, $sizeMode, $looseQty = 0)
    {
        $ppb = $ppb > 0 ? (float) $ppb : 1.0;
        $looseQty = (float) ($looseQty ?? 0);
        
        $qtyStr = rtrim(rtrim((string) $qty, '0'), '.');
        
        if ($sizeMode === 'by_size') {
            return (float) $qty + $looseQty;
        }

        // Support both Cartesian dual-input and legacy decimal notation (e.g. 1.5 for 1 box + 5 pcs)
        if (str_contains($qtyStr, '.')) {
            $parts = explode('.', $qtyStr, 2);
            $boxes = (int) $parts[0];
            $pieces = (int) ($parts[1] ?? 0);
            return ($boxes * $ppb) + $pieces + $looseQty;
        }

        return ((float)$qty * $ppb) + $looseQty;
    }

    /**
     * Get the last purchase price for a product (for GRN auto-fill).
     * Simple direct query — no correlated subquery complexity.
     */
    public function getLastPurchasePrice($productId)
    {
        $branchId = $this->getBranchId();

        $query = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->where('purchase_items.price', '>', 0)
            ->where('purchases.status_purchase', 'post') // Only confirmed GRNs
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id');

        // Filter by branch only if user is not super admin
        if ($branchId) {
            $query->where('purchases.branch_id', $branchId);
        }

        $row = $query->select(
            'purchase_items.price', 
            'purchase_items.batch_no', 
            'purchase_items.mfg_date', 
            'purchase_items.exp_date', 
            'purchases.purchase_date'
        )->first();

        return response()->json([
            'price'    => $row ? (float) $row->price : 0,
            'batch_no' => $row ? $row->batch_no : '',
            'mfg_date' => $row ? ($row->mfg_date ? date('Y-m-d', strtotime($row->mfg_date)) : '') : '',
            'exp_date' => $row ? ($row->exp_date ? date('Y-m-d', strtotime($row->exp_date)) : '') : '',
            'date'     => $row ? $row->purchase_date : null,
        ]);
    }
}
