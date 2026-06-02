<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\PriceLog;
use App\Models\ProductBatch;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;


class SaleController extends Controller
{
    use BranchScoped;

    public function index(Request $request)
    {
        if ($request->mode == 'so') {
            return $this->orderIndex();
        }

        return $this->receiptIndex();
    }

    public function orderIndex()
    {
        $branchId = $this->getBranchId();
        $sales = Sale::with(['customer_relation', 'items.product', 'returns', 'payments'])
            ->whereIn('sale_status', ['draft'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.sale_order.index', compact('sales'));
    }

    public function receiptIndex()
    {
        $branchId = $this->getBranchId();
        $sales = Sale::with(['customer_relation', 'items.product', 'returns', 'payments'])
            ->whereIn('sale_status', ['post', 'un-post'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.sale_receipt_note.index', compact('sales'));
    }

    public function addsale(Request $request)
    {
        $branchId = $this->getBranchId();
        $Customer = Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $Warehouse = Warehouse::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $sales = Sale::with(['customer_relation', 'items.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();
        $nextInvoice = Sale::generateInvoiceNo($branchId, $request->mode == 'so' ? 'SO-' : 'SIN-');

        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        $employees = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        $dcNotesRaw = \App\Models\DeliveryNote::with(['sale', 'customer', 'items.product', 'items.saleItem', 'items.uom'])
            ->whereHas('sale', function($q) {
                $q->where('sale_status', 'delivered');
            })
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $dcNotes = $dcNotesRaw->groupBy('sale_id')->map(function($group) {
            $firstDc = $group->first();

            $virtualDc = new \stdClass();
            $virtualDc->id = $firstDc->id;
            $virtualDc->dc_no = $group->pluck('dc_no')->implode(', ');
            $virtualDc->delivery_date = $firstDc->delivery_date;
            $virtualDc->customer_id = $firstDc->customer_id;
            $virtualDc->sale_id = $firstDc->sale_id;
            $virtualDc->sale = $firstDc->sale;
            $virtualDc->customer = $firstDc->customer;

            $allItems = collect();
            foreach($group as $dc) {
                $allItems = $allItems->concat($dc->items);
            }
            $virtualDc->items = $allItems;
            $virtualDc->net_amount = $group->sum('net_amount');

            return $virtualDc;
        })->values();

        return view('admin_panel.sale.sale_receipt_note.create', compact('Warehouse', 'Customer', 'nextInvoice', 'accounts', 'employees', 'sales', 'dcNotes'));
    }

    public function searchpname(Request $request)
    {
        $q = $request->get('q');
        $warehouseId = $request->get('warehouse_id', 1);

        $products = Product::with(['brand', 'packings'])
            ->leftJoin('warehouse_stocks', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'warehouse_stocks.product_id')
                    ->where('warehouse_stocks.warehouse_id', $warehouseId);
            })
            ->where(function ($query) use ($q) {
                $query->where('products.item_name', 'like', "%{$q}%")
                    ->orWhere('products.item_code', 'like', "%{$q}%")
                    ->orWhere('products.barcode_path', 'like', "%{$q}%");
            })
            ->select(
                'products.*',
                'warehouse_stocks.total_pieces as wh_stock',
                'warehouse_stocks.quantity as wh_box_qty'
            )
            ->limit(50)
            ->get()
            ->map(function ($product) {
                $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                $boxQty = (float) $product->wh_box_qty;
                $calcPieces = $boxQty * $ppb;

                if (abs($calcPieces - $product->wh_stock) > 0.1) {
                    $product->wh_stock = $calcPieces;
                }

                return $product;
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $sale = new Sale;
        if ($request->filled('draft_id')) {
            $sale = Sale::find($request->draft_id) ?? new Sale;
        }

        return $this->processSale($request, $sale);
    }

    public function edit(Sale $sale)
    {
        //
    }

    public function getDcDetails($id)
    {
        $dc = \App\Models\DeliveryNote::with(['items.product.packings', 'items.saleItem', 'customer', 'sale'])->findOrFail($id);
        return response()->json($dc);
    }

    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();
        $products = explode(',', $booking->product);
        $codes = explode(',', $booking->product_code);
        $brands = explode(',', $booking->brand);
        $units = explode(',', $booking->unit);
        $prices = explode(',', $booking->per_price);
        $discounts = explode(',', $booking->per_discount);
        $qtys = explode(',', $booking->qty);
        $totals = explode(',', $booking->per_total);
        $colors_json = json_decode($booking->color, true);

        $items = [];
        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name' => $product->item_name ?? $p,
                'item_code' => $product->item_code ?? ($codes[$index] ?? ''),
                'uom' => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit' => $product->unit_id ?? ($units[$index] ?? ''),
                'price' => floatval($prices[$index] ?? 0),
                'discount' => floatval($discounts[$index] ?? 0),
                'qty' => intval($qtys[$index] ?? 1),
                'total' => floatval($totals[$index] ?? 0),
                'color' => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }

    public function saleretun($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.product.brand', 'customer_relation'])->findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);

        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);
        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed'])
            ->get();

        foreach ($items as &$item) {
            $returned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = explode(',', $return->product_code);
                $returnQtys = explode(',', $return->qty);

                foreach ($returnProductIds as $idx => $code) {
                    if (trim($code) === $item['item_code']) {
                        $returned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $item['already_returned'] = $returned;
            $item['max_returnable'] = max(0, $item['qty'] - $returned);
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'accounts' => $accounts,
            'returnDeadline' => $returnDeadline,
            'isWithinDeadline' => $isWithinDeadline,
            'returnDeadlineDays' => $returnDeadlineDays,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'payment_account_id' => 'required|array|min:1',
            'payment_amount' => 'required|array|min:1',
            'quality_status' => 'nullable|in:good,damaged,defective,pending_inspection',
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);

        if ($returnDeadlineDays == 0) {
            return back()->with('error', 'Returns are currently disabled by store policy.');
        }

        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        if (! $isWithinDeadline) {
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('Super Admin');
            $canApprovePastDeadline = $user->can_approve_past_deadline_returns ?? false;

            if (! $isSuperAdmin && ! $canApprovePastDeadline) {
                $daysLate = now()->diffInDays($returnDeadline);
                return back()->with('error', "Return period expired! This sale is {$daysLate} days past the {$returnDeadlineDays}-day return deadline (Sale Date: {$sale->created_at->format('d-M-Y')}). Only Super Admin can approve past deadline returns.");
            }

            \Log::info("Past deadline return approved by {$user->name} (ID: {$user->id}) for Sale #{$sale->id}");
        }

        $product_ids = $request->product_id ?? [];
        $quantities = $request->qty ?? [];

        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed', 'pending'])
            ->get();

        foreach ($product_ids as $index => $product_id) {
            $returnQty = (float) ($quantities[$index] ?? 0);

            if ($returnQty <= 0) {
                continue;
            }

            $saleItem = $sale->items->where('product_id', $product_id)->first();

            if (! $saleItem) {
                return back()->with('error', "Product ID {$product_id} was not found in the original sale.");
            }

            $previouslyReturned = 0;
            foreach ($alreadyReturned as $return) {
                $returnQtys = explode(',', $return->qty);
                $productCodes = explode(',', $return->product_code);
                foreach ($productCodes as $idx => $code) {
                    $product = \App\Models\Product::where('item_code', trim($code))->first();
                    if ($product && $product->id == $product_id) {
                        $previouslyReturned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $maxReturnable = $saleItem->total_pieces - $previouslyReturned;

            if ($returnQty > $maxReturnable) {
                $productName = $saleItem->product_name ?? "Product #{$product_id}";
                return back()->with('error', "Cannot return {$returnQty} pieces of '{$productName}'. Maximum returnable: {$maxReturnable} pieces (Sold: {$saleItem->total_pieces}, Already Returned: {$previouslyReturned}).");
            }
        }

        DB::beginTransaction();

        try {
            $warehouseId = (int) ($request->input('warehouse_id', 1));

            $product_names = $request->product ?? [];
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $jsonItems = [];
            $total_items = 0;

            foreach ($product_ids as $index => $product_id) {
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0) {
                    continue;
                }

                $jsonItems[] = [
                    'product_id' => $product_id,
                    'qty' => $qty,
                    'price' => $price,
                ];

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $price;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $qty;
                $combined_totals[] = $totals[$index] ?? 0;

                $decodedColor = $colors[$index] ?? [];
                $combined_colors[] = is_array($decodedColor) ? json_encode($decodedColor) : json_encode((array) json_decode($decodedColor, true));

                $total_items += $qty;
            }

            $paymentAccountIds = $request->payment_account_id ?? [];
            $paymentAmounts = $request->payment_amount ?? [];
            $jsonPayments = [];

            foreach ($paymentAccountIds as $idx => $id) {
                if (($paymentAmounts[$idx] ?? 0) > 0) {
                    $jsonPayments[] = ['account_id' => $id, 'amount' => $paymentAmounts[$idx]];
                }
            }

            $saleReturn = new SaleReturn;
            $saleReturn->sale_id = $request->sale_id;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words;
            $saleReturn->total_bill_amount = $request->total_subtotal;
            $saleReturn->total_extradiscount = $request->total_extra_cost;
            $saleReturn->total_net = $request->total_net;
            $saleReturn->cash = $request->cash;
            $saleReturn->card = $request->card;
            $saleReturn->change = $request->change;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note;

            $saleReturn->refund_details = json_encode([
                'items' => $jsonItems,
                'payments' => $jsonPayments,
                'warehouse_id' => $warehouseId,
                'customer_id' => $request->customer,
                'total_net' => $request->total_net,
            ]);

            $autoApproveThreshold = \App\Models\SystemSetting::get('return_auto_approve_threshold', 0);
            $returnAmount = (float) $request->total_net;
            $requireApproval = \App\Models\SystemSetting::get('return_require_approval', true);

            if (($autoApproveThreshold > 0 && $returnAmount <= $autoApproveThreshold) || ! $requireApproval) {
                $saleReturn->return_status = 'approved';
            } else {
                $saleReturn->return_status = 'pending';
            }

            $saleReturn->quality_status = $request->quality_status ?? 'pending_inspection';
            if ($request->quality_status && in_array($request->quality_status, ['good', 'damaged', 'defective'])) {
                $saleReturn->inspected_by = auth()->id();
            }

            $saleReturn->return_deadline = $returnDeadline;
            $saleReturn->is_within_deadline = $isWithinDeadline;

            $saleReturn->save();

            if ($saleReturn->return_status === 'approved') {
                $this->_processApproval($saleReturn);
            }

            DB::commit();

            try {
                \App\Models\SystemNotification::createSaleReturnNotification($saleReturn, $sale);
            } catch (\Exception $e) {
                \Log::error('Notification creation failed: '.$e->getMessage());
            }

            return redirect()->route('sale.index')->with('success', 'Sale return processed successfully with journal entries and payment voucher.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale Return Error: '.$e->getMessage());
            return back()->with('error', 'Sale return failed: '.$e->getMessage());
        }
    }

    public function salereturnview()
    {
        $branchId = $this->getBranchId();
        $SaleReturns = SaleReturn::with(['sale.customer_relation', 'customer_relation'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $SaleReturns->count(),
            'pending' => $SaleReturns->where('return_status', 'pending')->count(),
            'approved' => $SaleReturns->where('return_status', 'approved')->count(),
            'rejected' => $SaleReturns->where('return_status', 'rejected')->count(),
            'completed' => $SaleReturns->where('return_status', 'completed')->count(),
        ];

        return view('admin_panel.sale.return.index', compact('SaleReturns', 'stats'));
    }

    public function approveReturn($id)
    {
        try {
            $return = SaleReturn::findOrFail($id);

            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            DB::beginTransaction();

            if (! empty($return->refund_details)) {
                $this->_processApproval($return);
            } else {
                $return->return_status = 'approved';
                $return->approved_by = auth()->id();
                $return->approved_at = now();
                $return->save();
            }

            DB::commit();

            return back()->with('success', 'Return approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return approval failed: '.$e->getMessage());
            return back()->with('error', 'Failed to approve return: '.$e->getMessage());
        }
    }

    private function _processApproval($saleReturn)
    {
        $data = is_string($saleReturn->refund_details) ? json_decode($saleReturn->refund_details, true) : $saleReturn->refund_details;

        if (! $data) {
            throw new \Exception('Invalid return data for processing');
        }

        $warehouseId = $data['warehouse_id'] ?? 1;
        $items = $data['items'] ?? [];
        $payments = $data['payments'] ?? [];
        $srMovements = [];

        $sale = Sale::find($saleReturn->sale_id);

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qty = (float) $item['qty'];

            if ($qty <= 0) {
                continue;
            }

            $uomId    = $item['uom_id'] ?? null;
            $branchId = \App\Models\Warehouse::where('id', $warehouseId)->value('branch_id') ?? 1;
            StockService::credit($productId, $uomId, $warehouseId, $branchId, $qty);

            $srMovements[] = [
                'product_id' => $productId,
                'type' => 'in',
                'qty' => $qty,
                'ref_type' => 'SR',
                'ref_id' => $saleReturn->id,
                'note' => 'Sale return approved',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($sale && $sale->items) {
                $saleItem = $sale->items->where('product_id', $productId)->first();
                if ($saleItem) {
                    $saleItem->total_pieces = max(0, $saleItem->total_pieces - $qty);
                    $prod = \App\Models\Product::find($productId);
                    $ppb = ($prod && $prod->pieces_per_box > 0) ? (int)$prod->pieces_per_box : 1;

                    $boxes = intdiv((int)$saleItem->total_pieces, $ppb);
                    $remPieces = (int)$saleItem->total_pieces % $ppb;

                    $saleItem->qty = (float)($boxes . '.' . $remPieces);
                    $saleItem->loose_pieces = $remPieces;
                    $saleItem->save();
                }
            }
        }

        if (! empty($srMovements)) {
            DB::table('stock_movements')->insert($srMovements);
        }

        $customer = Customer::find($data['customer_id'] ?? $saleReturn->customer);
        $returnAmount = (float) $saleReturn->total_net;
        $date = now()->format('Y-m-d');
        $journalService = app(\App\Services\JournalEntryService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        $arAccountId = $balanceService->getAccountsReceivableId();
        $salesAccountId = $balanceService->getSalesRevenueId();

        if ($returnAmount > 0) {
            $journalService->recordEntry(
                $saleReturn,
                $salesAccountId,
                $returnAmount,
                0,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date
            );

            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                0,
                $returnAmount,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date,
                $customer
            );
        }

        $totalPaid = 0;
        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            $accountId = $payment['account_id'];

            if ($amount <= 0 || ! $accountId) {
                continue;
            }

            $totalPaid += $amount;

            $journalService->recordEntry(
                $saleReturn,
                $accountId,
                0,
                $amount,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date
            );
        }

        if ($totalPaid > 0) {
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                $totalPaid,
                0,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date,
                $customer
            );

            \App\Models\CustomerPayment::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => auth()->id(),
                'voucher_no' => 'SR-'.$saleReturn->id,
                'payment_date' => $date,
                'payment_method' => 'Cash',
                'amount' => $totalPaid,
                'note' => "Refund for Sale Return #{$saleReturn->id}",
                'type' => 'refund',
            ]);

            try {
                $pvid = \App\Models\PaymentVoucher::generateInvoiceNo();
                $pvAccounts = [];
                $pvAmounts = [];
                foreach ($payments as $p) {
                    if (($p['amount'] ?? 0) > 0) {
                        $pvAccounts[] = $p['account_id'];
                        $pvAmounts[] = $p['amount'];
                    }
                }

                \App\Models\PaymentVoucher::create([
                    'pvid' => $pvid,
                    'party_id' => json_encode([$customer->id]),
                    'type' => json_encode(['customer']),
                    'total_amount' => $totalPaid,
                    'receipt_date' => $date,
                    'entry_date' => $date,
                    'row_account_id' => json_encode($pvAccounts),
                    'row_account_head' => json_encode([]),
                    'amount' => json_encode($pvAmounts),
                    'remarks' => "Refund for Sale Return #{$saleReturn->id}",
                ]);
            } catch (\Exception $e) {
                \Log::error('Refund PV Creation Failed: '.$e->getMessage());
            }
        }

        $saleReturn->return_status = 'approved';
        $saleReturn->approved_by = auth()->id();
        $saleReturn->approved_at = now();
        $saleReturn->save();
    }

    public function rejectReturn(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $return = SaleReturn::findOrFail($id);

            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            $return->return_status = 'rejected';
            $return->approved_by = auth()->id();
            $return->approved_at = now();
            $return->rejection_reason = $request->rejection_reason;
            $return->save();

            return back()->with('success', 'Return rejected successfully!');

        } catch (\Exception $e) {
            \Log::error('Return rejection failed: '.$e->getMessage());
            return back()->with('error', 'Failed to reject return: '.$e->getMessage());
        }
    }

    public function saleReturnDetail($id)
    {
        $saleReturn = SaleReturn::with('customer_relation')->findOrFail($id);
        $sale = Sale::with(['customer_relation', 'items.product'])->findOrFail($saleReturn->sale_id);

        $returnItems = [];
        $productNames = explode(',', $saleReturn->product);
        $productCodes = explode(',', $saleReturn->product_code);
        $brands = explode(',', $saleReturn->brand);
        $units = explode(',', $saleReturn->unit);
        $prices = explode(',', $saleReturn->per_price);
        $discounts = explode(',', $saleReturn->per_discount);
        $quantities = explode(',', $saleReturn->qty);
        $totals = explode(',', $saleReturn->per_total);

        for ($i = 0; $i < count($productNames); $i++) {
            $returnItems[] = [
                'product_name' => $productNames[$i] ?? '',
                'product_code' => $productCodes[$i] ?? '',
                'brand' => $brands[$i] ?? '',
                'unit' => $units[$i] ?? '',
                'price' => $prices[$i] ?? 0,
                'discount' => $discounts[$i] ?? 0,
                'quantity' => $quantities[$i] ?? 0,
                'total' => $totals[$i] ?? 0,
            ];
        }

        $payments = \App\Models\CustomerPayment::where('note', 'like', "%Sale Return #{$saleReturn->id}%")->get();

        $journalEntries = \App\Models\JournalEntry::where('source_type', 'App\Models\SaleReturn')
            ->where('source_id', $saleReturn->id)
            ->with('account')
            ->get();

        $approver = $saleReturn->approved_by ? \App\Models\User::find($saleReturn->approved_by) : null;
        $inspector = $saleReturn->inspected_by ? \App\Models\User::find($saleReturn->inspected_by) : null;

        return view('admin_panel.sale.return.detail', compact(
            'saleReturn',
            'sale',
            'returnItems',
            'payments',
            'journalEntries',
            'approver',
            'inspector'
        ));
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with(['customer_relation.salesOfficer', 'items.batches', 'items.product.unit', 'items.product.brand'])->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        $previousBalance = 0;
        $currentBalance = 0;

        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0)
            ->first();

        if ($journalEntry && $sale->customer_id) {
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            $currentBalance = $previousBalance + $sale->total_net;
        } else {
            $customer = $sale->customer_relation;
            $previousBalance = $customer->opening_balance ?? 0;
        }

        return view('admin_panel.sale.saleinvoice', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function saleedit($id)
    {
        $branchId = $this->getBranchId();

        $sale = Sale::with(['items.product.warehouseStocks', 'customer_relation', 'employee', 'branch', 'payments.account'])->findOrFail($id);

        $customer = Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $warehouse = Warehouse::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        $employees = \App\Models\Hr\Employee::active()
            ->whereHas('designation', function ($q) {
                $q->where('is_sale_officer', 1);
            })
            ->orderBy('first_name')
            ->get();

        $sales = Sale::with(['customer_relation', 'items.product'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        $dcNotes = \App\Models\DeliveryNote::with(['sale', 'customer', 'items.product', 'items.saleItem'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        $nextInvoiceNumber = $sale->invoice_no;

        return view('admin_panel.sale.edit_sale', compact('warehouse', 'customer', 'nextInvoiceNumber', 'accounts', 'sale', 'employees', 'sales', 'dcNotes'));
    }

    public function updatesale(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if (in_array($sale->sale_status, ['posted', 'cancelled', 'returned'])) {
            return redirect()->back()->with('error', 'Cannot edit a '.$sale->sale_status.' sale.');
        }

        return $this->processSale($request, $sale);
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function salereceipt($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\Sale::class)
            ->where('source_id', $sale->id)
            ->where('debit', '>', 0)
            ->first();

        $previousBalance = 0;
        $currentBalance = 0;

        if ($journalEntry && $sale->customer_id) {
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));

            $currentBalance = $previousBalance + $sale->total_net;
        } else {
            $customer = $sale->customer_relation;
            if ($customer) {
                $previousBalance = $customer->previous_balance ?? 0;
            }
            $currentBalance = $previousBalance + $sale->total_net;
        }

        return view('admin_panel.sale.salereceipt', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function postFinal(Request $request)
    {
        $request->merge(['action' => 'post']);

        $id = $request->booking_id ?? $request->draft_id;
        if ($id) {
            $sale = Sale::findOrFail($id);
            if ($sale->sale_status === 'post') {
                return response()->json(['ok' => true, 'msg' => 'Already Posted', 'invoice_url' => route('sales.invoice', $sale->id)]);
            }

            return $this->processSale($request, $sale);
        }

        return $this->processSale($request, new Sale);
    }

    public function unpost($id)
    {
        try {
            DB::beginTransaction();
            $sale = Sale::with('items')->findOrFail($id);

            if ($sale->sale_status !== 'post') {
                return redirect()->back()->with('error', 'Only posted SRNs can be un-posted.');
            }

            if (!auth()->user()->can('sales.unpost') && !auth()->user()->isSuperAdmin()) {
                return redirect()->back()->with('error', 'You do not have permission to un-post.');
            }

            $transactionService = app(\App\Services\TransactionService::class);
            $transactionService->reverseSaleAccounting($sale);

            $sale->update(['sale_status' => 'un-post']);

            DB::commit();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'SRN un-posted successfully and reverted to draft.'
                ]);
            }

            return redirect()->back()->with('success', 'SRN un-posted successfully and reverted to draft.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("SRN Un-post Error: " . $e->getMessage());

            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $sale = Sale::with('items')->findOrFail($id);

            $deliveredSum = $sale->items->sum('delivered_qty');
            if ($deliveredSum > 0) {
                return response()->json([
                    'ok' => false,
                    'msg' => 'Cannot delete sale order. Delivery has already been processed for some or all items.'
                ], 422);
            }

            DB::transaction(function() use ($sale) {
                $sale->items()->delete();
                $sale->delete();
            });

            return response()->json([
                'ok' => true,
                'msg' => 'Sale Order deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'ok' => false,
                'msg' => 'Failed to delete: ' . $e->getMessage()
            ], 500);
        }
    }

    private function processSale(Request $request, Sale $sale)
    {
        $request->validate([
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array|min:1',
            'warehouse_id' => 'required|array',
        ]);

        if ($request->mode == 'so') {
            $status = 'draft';
        } else {
            $status = $request->action === 'post' ? 'post' : 'un-post';
        }

        return DB::transaction(function () use ($request, $sale, $status) {

            $isNew = ! $sale->exists;
            $sale->customer_id    = $request->customer;
            $sale->employee_id    = $request->sales_officer_id;
            $sale->reference      = $request->reference;
            $sale->sale_date      = $request->purchase_date;
            $sale->vendor_bill_no = $request->vendor_bill_no;
            $sale->order_no       = $request->order_no;
            $sale->sale_order_no  = $request->sale_order_no;
            $sale->so_date        = $request->so_date;
            $sale->total_amount_Words = $request->total_amount_Words;
            $sale->sale_status = $status;
            $sale->enable_hs_code = $request->enable_hs_code ? 1 : 0;
            $sale->branch_id   = $this->getBranchId();

            if ($request->filled('credit_days') && $request->credit_days > 0) {
                $creditDays = (int) $request->credit_days;
                $sale->credit_days = $creditDays;
                $baseDate = $sale->sale_date ? \Carbon\Carbon::parse($sale->sale_date) : now();
                $sale->due_date = $baseDate->addDays($creditDays);
            } else {
                $sale->credit_days = null;
                $sale->due_date = null;
            }

            if (!$sale->invoice_no) {
                if ($request->filled('invoice_no')) {
                    $manualInvoice = trim($request->invoice_no);
                    $exists = Sale::where('invoice_no', $manualInvoice)->exists();
                    if ($exists) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'invoice_no' => "Invoice number '{$manualInvoice}' already exists. Please use a different number or leave blank for auto-generation.",
                        ]);
                    }
                    $sale->invoice_no = $manualInvoice;
                } else {
                    $prefix = $request->mode == 'so' ? 'SO-' : 'SRN-';
                    $sale->invoice_no = $this->generateUniqueInvoiceNo($prefix);
                }
            }

            $total_bill = 0;
            $total_gst = 0;
            $total_inc_tax = 0;
            $total_adv_tax = 0;
            $total_items = 0;

            $sale->save();

            if (! $isNew) {
                SaleItem::where('sale_id', $sale->id)->delete();
            }

            \Log::info('Processing Sale: ' . json_encode($request->only(['action', 'mode', 'customer', 'product_id', 'qty', 'loose_pieces'])));

            $productIds = $request->product_id;
            $quantities = $request->qty;
            $warehouses = $request->warehouse_id;
            $discounts = $request->item_disc ?? [];
            $packingNames = $request->pieces_per_box ?? [];
            $uomFactors  = $request->item_uom_factor ?? [];

            foreach ($productIds as $index => $pid) {
                if (! $pid) {
                    continue;
                }

                $qtyInput = (float) ($quantities[$index] ?? 0);
                $looseInput = (float) ($request->loose_pieces[$index] ?? 0);

                if ($qtyInput <= 0 && $looseInput <= 0) {
                    continue;
                }

                $product = Product::findOrFail($pid);
                $uomName = $packingNames[$index] ?? 'Piece';
                $uomFactor = (float) ($uomFactors[$index] ?? 1);

                $inputPrice = (float) ($request->price[$index] ?? $request->price_per_piece[$index] ?? 0);
                $oldMasterSalePrice = (float) $product->sale_price_per_piece;
                $dbPrice = $inputPrice > 0 ? $inputPrice : ($oldMasterSalePrice > 0 ? $oldMasterSalePrice : 0);

                if ($inputPrice > 0 && $inputPrice != $oldMasterSalePrice) {
                    $product->update(['sale_price_per_piece' => $inputPrice]);
                    PriceLog::log($pid, 'sale', $oldMasterSalePrice, $inputPrice, 'SRN', $sale->invoice_no, "Sale price updated via SRN #{$sale->invoice_no}");
                }

                $uomIdRaw = $request->uom_id[$index] ?? null;
                $isNewUom = ($uomIdRaw === 'NEW' || ($request->is_new_uom[$index] ?? '0') === '1');
                $uomName = $isNewUom ? ($request->uom_name[$index] ?? $uomName) : ($packingNames[$index] ?? 'Piece');

                if ($isNewUom) {
                    $uom = \App\Models\ProductUom::create([
                        'product_id' => $pid,
                        'name' => $uomName,
                        'pieces_per_box' => $uomFactor,
                        'sale_price' => $dbPrice * $uomFactor
                    ]);
                    $resolvedUomId = $uom->id;
                } else {
                    $resolvedUomId = (is_numeric($uomIdRaw) && $uomIdRaw > 0) ? $uomIdRaw : null;
                }

                $ppb = (float) ($uomFactors[$index] ?? 1);
                $boxes = (float) ($request->qty[$index] ?? 0);
                $loose = (float) ($request->loose_pieces[$index] ?? 0);
                $totalPieces = ($boxes * $ppb) + $loose;
                $storedQtyBox = $boxes;

                $freeTotalPieces = (float) ($request->free_loose_pieces[$index] ?? 0);
                $storedFreeQty = $ppb > 0 ? (float)($freeTotalPieces / $ppb) : $freeTotalPieces;

                $discount = (float) ($discounts[$index] ?? 0);
                $discType = $request->item_disc_type[$index] ?? 'amount';
                $gstRate   = (float) ($request->gst[$index] ?? 0);
                $incTaxPct = (float) ($request->inc_tax[$index] ?? 0);
                $advTaxPct = (float) ($request->adv_tax[$index] ?? 0);

                $subTotal = (float) ($totalPieces * $dbPrice);
                $calcDiscountAmount = 0;
                $calcDiscountPercent = 0;

                if ($discType === 'percent') {
                    $calcDiscountAmount = ($subTotal * $discount / 100);
                    $calcDiscountPercent = $discount;
                } else {
                    $calcDiscountAmount = $discount;
                    $calcDiscountPercent = $subTotal > 0 ? ($discount / $subTotal) * 100 : 0;
                }

                $postDiscSub = max(0, $subTotal - $calcDiscountAmount);
                $rowGstAmount = $postDiscSub * ($gstRate / 100);
                $incTaxAmt    = $postDiscSub * ($incTaxPct / 100);
                $advTaxAmt    = $postDiscSub * ($advTaxPct / 100);
                $lineTotal    = $postDiscSub + $rowGstAmount - $incTaxAmt - $advTaxAmt;

                $saleItem = new SaleItem;
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $pid;
                $saleItem->warehouse_id = $warehouses[$index] ?? 1;
                $saleItem->product_name = $product->item_name;
                $saleItem->qty = $storedQtyBox;
                $saleItem->total_pieces = $totalPieces;
                $saleItem->uom_id = $resolvedUomId;
                $saleItem->uom_name = $uomName;
                $saleItem->uom_factor = $uomFactor;
                $saleItem->loose_pieces = $loose;
                $saleItem->free_qty = $storedFreeQty;
                $saleItem->free_total_pieces = $freeTotalPieces;
                $saleItem->hs_code = $request->hs_code[$index] ?? $product->hs_code;
                $saleItem->price = $dbPrice;
                $saleItem->discount_percent = $calcDiscountPercent;
                $saleItem->discount_amount = $calcDiscountAmount;
                $saleItem->gst_percent = $gstRate;
                $saleItem->gst_amount  = $rowGstAmount;
                $saleItem->inc_tax     = $incTaxPct;
                $saleItem->adv_tax     = $advTaxPct;
                $saleItem->total = $lineTotal;
                $saleItem->brand_id = $product->brand_id;
                $saleItem->unit_id = $product->unit_id;
                $saleItem->size_mode = $product->size_mode;
                $saleItem->save();

                $batchId = isset($request->batch_id[$index]) ? $request->batch_id[$index] : null;
                if ($batchId) {
                    DB::table('sale_item_batches')->insert([
                        'sale_item_id' => $saleItem->id,
                        'product_batch_id' => $batchId,
                        'qty_deducted' => $totalPieces,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $firstBatch = \App\Models\ProductBatch::where('product_id', $pid)
                        ->orderBy('exp_date', 'asc')
                        ->first();
                    if ($firstBatch) {
                        DB::table('sale_item_batches')->insert([
                            'sale_item_id' => $saleItem->id,
                            'product_batch_id' => $firstBatch->id,
                            'qty_deducted' => $totalPieces,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $total_bill    += $postDiscSub;
                $total_gst     += $rowGstAmount;
                $total_inc_tax += $incTaxAmt;
                $total_adv_tax += $advTaxAmt;
                $total_items   += $totalPieces;
            }

            $sale->total_bill_amount = $total_bill;
            $billDiscVal = (float) ($request->discount ?? 0);
            $billDiscType = $request->discount_type ?? 'amount';

            $calcBillDiscAmount = 0;
            if ($billDiscType === 'percent') {
                $calcBillDiscAmount = $total_bill * ($billDiscVal / 100);
            } else {
                $calcBillDiscAmount = $billDiscVal;
            }

            $sale->total_extradiscount = $calcBillDiscAmount;
            $sale->total_gst = $total_gst;
            $sale->total_inc_tax = $total_inc_tax;
            $sale->total_adv_tax = $total_adv_tax;
            $sale->total_freight  = (float)($request->freight_charges ?? $request->freight ?? $request->sum_freight ?? 0);
            $sale->total_expense  = (float)($request->extra_cost ?? $request->expense ?? $request->sum_expense ?? 0);
            $sale->total_fixed_tax = (float)($request->apply_gst ?? $request->sum_apply_gst ?? 0);

            $netPostDisc     = $total_bill - $calcBillDiscAmount;
            $gstBase         = $netPostDisc + $sale->total_freight + $sale->total_expense;
            $invoiceTotal    = $gstBase + $total_gst;
            $sale->total_net = $invoiceTotal - $total_inc_tax - $total_adv_tax;
            $sale->total_items = $total_items;
            $sale->cash = $request->cash ?? 0;
            $sale->change = ($sale->cash - $sale->total_net);

            $payAccounts = $request->payment_account_id ?? [];
            $payAmounts  = $request->payment_amount ?? [];
            $paymentData = [];
            foreach($payAccounts as $idx => $accId) {
                if ($accId && ($payAmounts[$idx] ?? 0) > 0) {
                    $paymentData[] = [
                        'account_id' => $accId,
                        'amount' => (float)$payAmounts[$idx]
                    ];
                }
            }
            $sale->payment_details = $paymentData;
            $sale->save();

            if ($status === 'post') {
                \Log::info('Proceeding to Auto-Receipt & Ledger logic for Sale #'.$sale->invoice_no);

                $this->updateLedger($sale);

                try {
                    $journalService = app(\App\Services\JournalEntryService::class);
                    $balanceService = app(\App\Services\BalanceService::class);

                    $arAccountId = $balanceService->getAccountsReceivableId($sale->branch_id);
                    $salesAccountId = $balanceService->getSalesRevenueId($sale->branch_id);
                    $date = $sale->created_at->format('Y-m-d');

                    $custForVoucher = $sale->customer_relation ?? \App\Models\Customer::find($sale->customer_id);

                    if ($custForVoucher) {
                        $balanceService->createSaleVoucher(
                            $custForVoucher,
                            $sale->total_net,
                            $sale->invoice_no,
                            $date,
                            $sale->branch_id
                        );
                    }

                    $transactionService = app(\App\Services\TransactionService::class);
                    $transactionService->createReceiptFromSale(
                        $sale,
                        $request->input('payment_account_id', []),
                        $request->input('payment_amount', [])
                    );

                } catch (\Exception $e) {
                    \Log::error('Professional Ledger Posting Error: '.$e->getMessage());
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => true,
                    'booking_id' => $sale->id,
                    'msg' => 'Sale '.ucfirst($status).' Successfully',
                    'invoice_url' => route('sales.invoice', $sale->id),
                ]);
            }

            return redirect()->route('sale.index')->with('success', 'Sale saved as '.$status);
        });
    }

    private function handleStockImpact(Sale $sale, $type = 'out')
    {
        if (! $sale->relationLoaded('items')) {
            $sale->load('items.product');
        }

        $branchId = $sale->branch_id ?? 1;

        foreach ($sale->items as $item) {
            $warehouseId = $item->warehouse_id;
            $productId   = $item->product_id;
            $uomId       = $item->uom_id ?? null;
            $qtyPieces   = (float) ($item->total_pieces ?? 0);

            if ($qtyPieces <= 0) {
                continue;
            }

            if ($type === 'out') {
                $available = StockService::balance($productId, $uomId, $warehouseId);
                if ($available < $qtyPieces) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'error' => 'Insufficient stock for '.($item->product->item_name ?? 'product').'. Available: '.$available.' pcs for this packing.'
                    ]);
                }
                StockService::debit($productId, $uomId, $warehouseId, $branchId, $qtyPieces);

                DB::table('stock_movements')->insert([
                    'product_id'  => $productId,
                    'type'        => 'out',
                    'qty'         => -$qtyPieces,
                    'ref_type'    => 'sale',
                    'ref_id'      => $sale->id,
                    'note'        => 'Sale Posted #'.$sale->invoice_no,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

            } elseif ($type === 'in' || $type === 'return') {
                StockService::credit($productId, $uomId, $warehouseId, $branchId, $qtyPieces);

                DB::table('stock_movements')->insert([
                    'product_id'  => $productId,
                    'type'        => 'in',
                    'qty'         => $qtyPieces,
                    'ref_type'    => 'sale_'.$type,
                    'ref_id'      => $sale->id,
                    'note'        => 'Sale '.ucfirst($type).' #'.$sale->invoice_no,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }
    }

    private function updateLedger(Sale $sale)
    {
        $customer_id = $sale->customer_id;
        if (! $customer_id) {
            return;
        }

        $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
        if (! $ledger) {
            $cust = \App\Models\Customer::find($customer_id);
            $prev_bal = $cust->previous_balance ?? 0;
        } else {
            $prev_bal = $ledger->closing_balance;
        }

        $new_bal = $prev_bal + $sale->total_net;

        \Log::info("Legacy Ledger (Invoice): Customer #{$customer_id}. Prev: {$prev_bal} + Sale: {$sale->total_net} = New: {$new_bal}");

        CustomerLedger::create([
            'customer_id' => $sale->customer_id,
            'branch_id' => $sale->branch_id,
            'admin_or_user_id' => auth()->id() ?? 1,
            'description' => "Sale Confirmed #{$sale->invoice_no}",
            'previous_balance' => $prev_bal,
            'opening_balance' => 0,
            'closing_balance' => $new_bal,
            'source_type' => \App\Models\Sale::class,
            'source_id' => $sale->id,
        ]);

        $cust = \App\Models\Customer::find($customer_id);
        if ($cust) {
            $cust->previous_balance = $new_bal;
            $cust->save();
        }
    }

    private function autoGenerateReceiptVoucher(Sale $sale, Request $request)
    {
        $accountIds = $request->input('receipt_account_id', []);
        $amounts = $request->input('receipt_amount', []);

        if (empty($accountIds) || empty(array_filter($accountIds))) {
            $accountIds = [1];
            $amounts = [$sale->cash];
        }

        $lastRV = \App\Models\ReceiptsVoucher::orderBy('id', 'desc')->first();
        $nextNumber = $lastRV ? (int) filter_var($lastRV->rvid, FILTER_SANITIZE_NUMBER_INT) + 1 : 1;
        $rvid = 'RV-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        \App\Models\ReceiptsVoucher::create([
            'rvid' => $rvid,
            'party_id' => $sale->customer_id,
            'type' => 'customer',
            'total_amount' => $sale->cash,
            'receipt_date' => now()->format('Y-m-d'),
            'row_account_id' => json_encode($accountIds),
            'row_account_head' => json_encode([]),
            'row_amount' => json_encode($amounts),
            'remarks' => 'Auto-generated from Sale Invoice #'.$sale->invoice_no,
            'processed' => true,
        ]);
    }

    private function _getSaleItems($sale)
    {
        return $sale->items->map(function ($item) {
            $firstBatch = $item->batches->first();
            if (!$firstBatch) {
                $firstBatch = \App\Models\ProductBatch::where('product_id', $item->product_id)
                    ->orderBy('exp_date', 'asc')
                    ->first();
            }
            return [
                'product_id' => $item->product_id,
                'item_name' => ($item->product_name ?? $item->product->item_name ?? 'Item') . ' ' . ($item->product->brand->name ?? ''),
                'item_code' => $item->product->item_code ?? '',
                'brand' => $item->product->brand->name ?? '',
                'unit' => $item->product->unit->name ?? '',
                'uom_name' => (function() use ($item) {
                    $factor = (int) ($item->uom_factor ?: (($item->total_pieces > 0 && (float)$item->qty > 0) ? round($item->total_pieces / (float)$item->qty) : 1));
                    return ($factor > 1) ? '1x' . $factor : ($item->product->unit->name ?? 'Piece');
                })(),
                'lot_number' => $firstBatch->batch_number ?? '-',
                'exp_date' => $firstBatch && $firstBatch->exp_date ? $firstBatch->exp_date->format('d/m/Y') : '-',
                'qty' => (int) $item->total_pieces,
                'qty_box' => (float) $item->qty,
                'total_pieces' => (int) $item->total_pieces,
                'loose_pieces' => (int) $item->loose_pieces,
                'free_qty' => (float) $item->free_qty,
                'free_total_pieces' => (float) $item->free_total_pieces,
                'hs_code' => $item->hs_code ?: ($item->product->hs_code ?? ''),
                'price' => (float) $item->price,
                'discount' => (float) $item->discount_percent,
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'total' => (float) $item->total,
                'gst' => (float) $item->gst,
                'gst_percent' => (float) $item->gst,
                'gst_amount' => (float) $item->gst_amount,
                'uom_factor' => (int) ($item->uom_factor ?: (($item->total_pieces > 0 && (float)$item->qty > 0) ? round($item->total_pieces / (float)$item->qty) : 1)),
                'color' => json_decode($item->color, true) ?? [],
                'pieces_per_box' => $item->product->pieces_per_box ?? 1,
                'price_per_piece' => ($item->total_pieces > 0) ? ($item->total / $item->total_pieces) : 0,
                'height' => $item->product->height ?? 0,
                'width' => $item->product->width ?? 0,
                'pieces_per_m2' => $item->product->pieces_per_m2 ?? 0,
                'size_mode' => $item->size_mode ?? $item->product->size_mode ?? 'std',
                'batches' => $item->batches->map(function ($b) {
                    return [
                        'batch_number' => $b->batch_number,
                        'exp_date' => $b->exp_date ? $b->exp_date->format('d-m-Y') : 'N/A',
                        'qty' => $b->pivot->qty_deducted,
                    ];
                })->toArray(),
            ];
        });
    }

    private function generateUniqueInvoiceNo($prefix = 'INV-')
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $lastSale = Sale::where('invoice_no', 'LIKE', $prefix.'%')->orderBy('id', 'desc')->first();

            if (! $lastSale || ! $lastSale->invoice_no) {
                $invoiceNo = $prefix.'0001';
            } else {
                $lastNumber = (int) str_replace($prefix, '', $lastSale->invoice_no);
                $invoiceNo = $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            $exists = Sale::where('invoice_no', $invoiceNo)->exists();

            if (! $exists) {
                return $invoiceNo;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        return $prefix.date('YmdHis');
    }

    public function exportRegistryDocx(Request $request)
    {
        $query = Sale::with(['customer_relation']);

        // ONLY POSTED SALES
        $query->where('sale_status', 'post');

        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->to);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $sales = $query->orderBy('id', 'desc')->get();

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addText(
            'POSTED SALES REGISTRY',
            ['bold' => true, 'size' => 16]
        );

        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin'  => 50,
        ]);

        // Header row
        $table->addRow();
        $table->addCell(800)->addText('ID');
        $table->addCell(2000)->addText('Invoice');
        $table->addCell(3000)->addText('Customer');
        $table->addCell(2000)->addText('Date');
        $table->addCell(2000)->addText('Total');

        // Data rows
        foreach ($sales as $sale) {
            $table->addRow();
            $table->addCell(800)->addText($sale->id ?? '-');
            $table->addCell(2000)->addText($sale->invoice_no ?? '-');
            $table->addCell(3000)->addText($sale->customer_relation->name ?? 'Walk-in');
            $table->addCell(2000)->addText($sale->sale_date ?? '-');
            $table->addCell(2000)->addText(number_format($sale->total_net ?? 0, 2));
        }

        $fileName = 'posted_sales_registry.docx';
        $filePath = storage_path($fileName);

        IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);

        return response()->download($filePath, $fileName)->deleteFileAfterSend(true);
    }
}