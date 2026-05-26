<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Traits\BranchScoped;

class SaleReturnController extends Controller
{
    use BranchScoped;

    public function createBlank()
    {
        $customers = \App\Models\Customer::orderBy('customer_name')->get();
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();
        $warehouses = \App\Models\Warehouse::all();
        return view('admin_panel.sale.sale_return.create', ['sale' => null, 'customers' => $customers, 'accounts' => $accounts, 'saleItems' => [], 'warehouses' => $warehouses]);
    }

    public function getSaleDetails($id)
    {
        $sale = Sale::with(['customer_relation', 'items.product.brand'])->findOrFail($id);

        $pastReturns = SaleReturn::where('sale_id', $id)
            ->with('items')
            ->get();

        $returnedQtyMap = [];
        foreach ($pastReturns as $sr) {
            foreach ($sr->items as $srItem) {
                if (! isset($returnedQtyMap[$srItem->product_id])) {
                    $returnedQtyMap[$srItem->product_id] = 0;
                }
                $returnedQtyMap[$srItem->product_id] += $srItem->qty;
            }
        }

        $items = [];
        foreach ($sale->items as $item) {
            $product = $item->product;
            $alreadyReturned = $returnedQtyMap[$item->product_id] ?? 0;

            $original = $item->total_pieces ?? $item->qty ?? 0;
            $max_returnable = max(0, $original - $alreadyReturned);

            $brandName = '';
            if ($product && $product->brand && is_object($product->brand)) {
                $brandName = $product->brand->name ?? '';
            } elseif ($product) {
                $brandName = $product->brand_name ?? '';
            }
            
            // Try to find lot/mfg/exp from Delivery Note or Sale Item Batches
            $dcItem = \App\Models\DeliveryNoteItem::where('sale_item_id', $item->id)->first();
            
            $batchNo = $dcItem->lot_number ?? '';
            $mfg     = $dcItem->mfg_date ?? '';
            $exp     = $dcItem->exp_date ?? '';

            if (empty($batchNo) || empty($mfg)) {
                $sib = \DB::table('sale_item_batches')
                    ->join('product_batches', 'sale_item_batches.product_batch_id', '=', 'product_batches.id')
                    ->where('sale_item_batches.sale_item_id', $item->id)
                    ->select('product_batches.batch_number', 'product_batches.mfg_date', 'product_batches.exp_date')
                    ->first();
                
                if ($sib) {
                    $batchNo = $batchNo ?: $sib->batch_number;
                    $mfg     = $mfg ?: $sib->mfg_date;
                    $exp     = $exp ?: $sib->exp_date;
                }
            }

            // Final format for HTML date input (Y-m-d)
            $mfg_fmt = '';
            if ($mfg) {
                try { $mfg_fmt = \Carbon\Carbon::parse($mfg)->format('Y-m-d'); } catch(\Exception $e) { $mfg_fmt = substr($mfg, 0, 10); }
            }
            $exp_fmt = '';
            if ($exp) {
                try { $exp_fmt = \Carbon\Carbon::parse($exp)->format('Y-m-d'); } catch(\Exception $e) { $exp_fmt = substr($exp, 0, 10); }
            }

            $items[] = [
                'product_id' => $item->product_id,
                'sale_item_id' => $item->id,
                'item_name' => $product->product_name ?? $product->item_name ?? 'Unknown',
                'item_code' => $product->product_code ?? $product->item_code ?? '',
                'brand' => $brandName,
                'warehouse_id' => $item->warehouse_id ?? $sale->warehouse_id ?? 1,
                'batch_no' => $batchNo ?: ($item->lot_number ?? $item->batch_no ?? ''),
                'mfg_date' => $mfg_fmt,
                'exp_date' => $exp_fmt,
                'pieces_per_box' => (int) ($item->pieces_per_box ?? $product->pieces_per_box ?? $product->packet_size ?? 1) ?: 1,
                'uom_name' => $item->uom_name ?? $product->unit ?? 'pc',
                'price' => (float) ($item->price_per_piece > 0 ? $item->price_per_piece : ($item->price > 0 ? $item->price : ($dcItem->price ?? 0))),
                'discount' => $item->discount_amount ?? 0,
                'discount_type' => 'amount', 
                'gst_percent' => $item->gst_percent ?? 0,
                'original_qty' => $original,
                'returned_qty' => $alreadyReturned,
                'max_returnable' => $max_returnable,
                'size_mode' => $item->size_mode ?? $product->size_mode ?? 'by_pieces',
                'pieces_per_m2' => $item->pieces_per_m2 ?? $product->m2_of_box ?? 0,
                'unit' => $item->unit ?? $product->unit ?? 'pc',
            ];
        }

        return response()->json([
            'success' => true,
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'customer_id' => $sale->customer_id,
                'customer_name' => optional($sale->customer_relation)->customer_name ?? 'Unknown Customer',
                'warehouse_id' => $sale->warehouse_id ?? 1,
                'sale_date' => $sale->sale_date ?? $sale->created_at->format('d M, Y h:i A'),
            ],
            'items' => $items,
        ]);
    }
    public function getSrns(Request $request)
    {
        $branchId = $this->getBranchId();

        $sales = Sale::with(['customer_relation'])
            ->where('sale_status', 'post')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($s) {
                return [
                    'id'            => $s->id,
                    'invoice_no'    => $s->invoice_no,
                    'customer_name' => optional($s->customer_relation)->customer_name ?? 'Unknown',
                    'sale_date'     => $s->sale_date ?? $s->created_at->format('d M, Y h:i A'),
                    'net_amount'    => $s->total_net ?? $s->total_bill_amount ?? 0,
                    'is_fully_returned' => $s->sale_status === 'returned',
                ];
            });

        return response()->json($sales);
    }

    public function getDcns(Request $request)
    {
        $branchId = $this->getBranchId();

        $dcns = \App\Models\DeliveryNoteItem::with(['dcNote.sale.customer_relation', 'product'])
            ->whereHas('dcNote', function($q) use ($branchId) {
                // $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId));
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $dc = $item->dcNote;
                $sale = $dc ? $dc->sale : null;
                return [
                    'id'            => $dc ? $dc->id : null,
                    'sale_id'       => $sale ? $sale->id : null, 
                    'dc_no'         => $dc ? $dc->dc_no : 'N/A',
                    'po_number'     => $sale ? $sale->invoice_no : 'N/A',
                    'customer_name' => $sale ? (optional($sale->customer_relation)->customer_name ?? 'Unknown') : 'Unknown',
                    'product_name'  => $item->product ? ($item->product->item_name ?? 'Unknown') : 'Unknown',
                    'qty'           => $item->qty ?? 0,
                    'amount'        => $item->line_total ?? 0,
                    'date'          => $dc ? $dc->created_at->format('d M, Y') : '',
                ];
            });

        return response()->json($dcns);
    }

    public function showReturnForm(\Illuminate\Http\Request $request, $id)
    {
        $sale = Sale::with(['customer_relation', 'items.product.brand'])->findOrFail($id);
        $accounts = app(\App\Services\BalanceService::class)->getPaymentAccounts();

        // Calculate already returned quantities
        $pastReturns = SaleReturn::where('sale_id', $id)
            ->with('items')
            ->get();

        $returnedQtyMap = [];
        foreach ($pastReturns as $sr) {
            foreach ($sr->items as $srItem) {
                if (! isset($returnedQtyMap[$srItem->product_id])) {
                    $returnedQtyMap[$srItem->product_id] = 0;
                }
                $returnedQtyMap[$srItem->product_id] += $srItem->qty;
            }
        }

        // Format sale items with complete product data
        $sale->items->each(function ($item) use ($returnedQtyMap) {
            $product = $item->product;
            $alreadyReturned = $returnedQtyMap[$item->product_id] ?? 0;

            // Add product details
            $item->item_name = $product->product_name ?? $product->item_name ?? 'Unknown';
            $item->item_code = $product->product_code ?? $product->item_code ?? '';

            // Fix brand - get name from relationship
            if ($product->brand && is_object($product->brand)) {
                $item->brand = $product->brand->name ?? '';
            } else {
                $item->brand = $product->brand_name ?? '';
            }

            // Ensure pieces_per_box is numeric and valid
            $item->pieces_per_box = (int) ($product->pieces_per_box ?? $product->packet_size ?? 1);
            if ($item->pieces_per_box <= 0) {
                $item->pieces_per_box = 1;
            }

            $item->size_mode = $product->size_mode ?? 'by_pieces';
            $item->pieces_per_m2 = $product->m2_of_box ?? 0;
            $item->unit = $item->unit ?? 'pc';

            // Quantity calculations
            $item->qty = $item->total_pieces ?? $item->qty ?? 0;
            $item->original_qty = $item->qty;
            $item->returned_qty = $alreadyReturned;
            $item->max_returnable = max(0, $item->qty - $alreadyReturned);

            // Pricing (use sale price, not purchase price)
            $item->price = $item->price ?? $item->per_price ?? 0;
            $item->discount = $item->discount ?? $item->per_discount ?? 0;
        });

        // If specific Delivery Note is requested, filter the items and update max quantities
        $dcNote = null;
        if ($request->has('dc_id')) {
            $dcNote = \App\Models\DeliveryNote::with('items')->find($request->query('dc_id'));
            if ($dcNote) {
                $dcItemsMap = [];
                foreach ($dcNote->items as $dcItem) {
                    if (!isset($dcItemsMap[$dcItem->product_id])) {
                        $dcItemsMap[$dcItem->product_id] = 0;
                    }
                    $dcItemsMap[$dcItem->product_id] += $dcItem->total_pieces;
                }
                
                // Filter items to only keep those in the DC
                $filteredItems = collect([]);
                foreach ($sale->items as $item) {
                    if (isset($dcItemsMap[$item->product_id])) {
                        // Limit original qty to what was actually delivered in THIS DC!
                        $item->original_qty = $dcItemsMap[$item->product_id];
                        $item->returned_qty = $returnedQtyMap[$item->product_id] ?? 0; // The total past returns
                        $item->max_returnable = max(0, $item->original_qty - $item->returned_qty);
                        $filteredItems->push($item);
                    }
                }
                // Override sale items with filtered list
                $sale->setRelation('items', $filteredItems);
            }
        }

        $purchaseItems = $sale->items;
        return view('admin_panel.sale.sale_return.create', compact('sale', 'customers', 'accounts', 'returnedQtyMap', 'dcNote', 'purchaseItems'));
    }

    /**
     * Process the sale return
     */
    public function processSaleReturn(Request $request)
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
            'sale_id' => 'nullable|exists:sales,id',
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array',
            'qty.*' => 'required|numeric|min:0',
            'price' => 'required|array',
            'price.*' => 'required|numeric|min:0',
            'batch_no' => 'nullable|array',
            'mfg_date' => 'nullable|array',
            'exp_date' => 'nullable|array',
            'item_disc_val' => 'nullable|array',
            'extra_discount' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
            'payment_account_id' => 'nullable|array',
            'payment_amount' => 'nullable|array',
        ], [], $attributes);

        // Prevent identical duplicate submissions within a short timeframe (e.g. user double-clicks or browser form resubmission)
        if (!empty($validated['sale_id'])) {
            $duplicate = SaleReturn::where('sale_id', $validated['sale_id'])
                ->where('created_at', '>=', Carbon::now()->subSeconds(15))
                ->exists();
            if ($duplicate) {
                return redirect()->route('sale.return.index')->with('success', 'Sale return processed successfully. (Duplicate request ignored)');
            }
        }

        DB::beginTransaction();

        try {
            $branchId = $this->getBranchId();
            
            // Generate Return Invoice Number
            $lastReturn = SaleReturn::orderBy('id', 'desc')->first();
            $nextInvoice = $lastReturn
                ? 'SR-'.str_pad((int) str_replace('SR-', '', $lastReturn->return_invoice) + 1, 4, '0', STR_PAD_LEFT)
                : 'SR-0001';

            // Create Sale Return Header
            $return = SaleReturn::create([
                'sale_id' => $validated['sale_id'] ?? null,
                'return_invoice' => $nextInvoice,
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'return_date' => $validated['return_date'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'posted',
                'branch_id' => $branchId ?? 1,
            ]);

            $sale = $validated['sale_id'] ? Sale::find($validated['sale_id']) : null;
            $now = Carbon::now();
            $movements = [];
            $subtotal = 0;
            $totalItemDiscount = 0;

            // Process Each Return Item
            foreach ($request->product_id as $idx => $productId) {
                $qty = (float) $request->qty[$idx]; // Total pieces
                if ($qty <= 0) {
                    continue;
                }

                $price = (float) $request->price[$idx];
                $itemDisc = (float) ($request->item_discount[$idx] ?? 0);
                $lineTotal = ($qty * $price) - $itemDisc;

                // Get product for PPB calculation
                $product = Product::find($productId);
                $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

                // Calculate boxes and loose pieces
                $boxes = floor($qty / $ppb);
                $loosePieces = $qty % $ppb;

                // Create Return Item
                SaleReturnItem::create([
                    'sale_return_id' => $return->id,
                    'product_id' => $productId,
                      'batch_no' => $request->batch_no[$idx] ?? null,
                      'mfg_date' => $request->mfg_date[$idx] ?? null,
                      'exp_date' => $request->exp_date[$idx] ?? null,
                    'warehouse_id' => $validated['warehouse_id'],
                    'qty' => $qty,
                    'boxes' => $boxes + ($loosePieces / $ppb), // Decimal boxes
                    'loose_pieces' => $loosePieces,
                    'price' => $price,
                    'item_discount' => $itemDisc,
                    'unit' => 'pc',
                    'line_total' => $lineTotal,
                ]);

                // Update Stock (INCREMENT - goods coming back)
                $stock = WarehouseStock::where('warehouse_id', $validated['warehouse_id'])
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    // Robust calculation
                    $currentTotalPieces = $stock->quantity * $ppb;
                    $newTotalPieces = $currentTotalPieces + $qty;

                    $stock->total_pieces = $newTotalPieces;
                    $stock->quantity = $newTotalPieces / $ppb;
                    
                    if (!$stock->branch_id) {
                        $stock->branch_id = $branchId ?? 1;
                    }

                    $stock->save();
                } else {
                    // Create new stock entry
                    WarehouseStock::create([
                        'warehouse_id' => $validated['warehouse_id'],
                        'branch_id' => $branchId ?? 1,
                        'product_id' => $productId,
                        'total_pieces' => $qty,
                        'quantity' => $qty / $ppb,
                        'price' => 0,
                    ]);
                }

                // Stock Movement (IN - goods returned to warehouse)
                $movements[] = [
                    'product_id' => $productId,
                    'type' => 'in',
                    'qty' => $qty,
                    'ref_type' => 'SALE_RETURN',
                    'ref_id' => $return->id,
                    'note' => "Return #{$nextInvoice}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // UC3: Restore batch qty (but don't re-activate expired batches)
                // Find original sale items to get the batch used
                if ($sale) {
                    $origSaleItem = \App\Models\SaleItem::where('sale_id', $sale->id)
                        ->where('product_id', $productId)
                        ->first();

                    if ($origSaleItem) {
                        $saleItemBatches = DB::table('sale_item_batches')
                            ->where('sale_item_id', $origSaleItem->id)
                            ->get();

                        foreach ($saleItemBatches as $sib) {
                            $restoreQty = min($sib->qty_deducted, $qty); // don't restore more than returned
                            $batch = ProductBatch::find($sib->product_batch_id);
                            if ($batch) {
                                $batch->qty_remaining += $restoreQty;
                                // UC3: If batch is expired, keep it expired — don't bring back to active!
                                if ($batch->exp_date < now()->toDateString()) {
                                    $batch->status = 'expired'; // expired stays expired
                                } elseif ($batch->qty_remaining > 0) {
                                    $batch->status = 'active'; // not expired and has qty = active again
                                }
                                $batch->save();
                                $qty -= $restoreQty; // track how much we've restored
                                if ($qty <= 0) break;
                            }
                        }
                    }
                }

                $subtotal += $lineTotal;
                $totalItemDiscount += $itemDisc;
            }

            // Bulk Insert Stock Movements
            if (! empty($movements)) {
                DB::table('stock_movements')->insert($movements);
            }

            $netAmount = ($subtotal - $totalItemDiscount) - ($request->extra_discount ?? 0);

            // Handle Refund Payment – create a Payment Voucher ONLY when amount > 0
            $totalPaid    = 0;
            $pvAccountIds = [];
            $pvAmounts    = [];

            if (! empty($request->payment_account_id)) {
                foreach ($request->payment_account_id as $idx => $accId) {
                    $amt = (float) ($request->payment_amount[$idx] ?? 0);
                    if ($accId && $amt > 0) {
                        $totalPaid    += $amt;
                        $pvAccountIds[] = $accId;
                        $pvAmounts[]    = $amt;
                    }
                }
            }

            if ($totalPaid > 0) {
                // Create legacy Payment Voucher record (payment_vouchers table)
                $pvid = \App\Models\PaymentVoucher::generateInvoiceNo();
                \App\Models\PaymentVoucher::create([
                    'pvid'             => $pvid,
                    'party_id'         => json_encode([$validated['customer_id']]),
                    'type'             => json_encode(['customer']),
                    'total_amount'     => $totalPaid,
                    'receipt_date'     => $validated['return_date'],
                    'entry_date'       => $validated['return_date'],
                    'row_account_id'   => json_encode($pvAccountIds),
                    'row_account_head' => json_encode([]),
                    'amount'           => json_encode($pvAmounts),
                    'remarks'          => "Refund for Sale Return #{$nextInvoice}",
                ]);
            }

            // Update Return Totals
            $return->update([
                'bill_amount' => $subtotal,
                'item_discount' => $totalItemDiscount,
                'net_amount' => $netAmount,
                'paid' => $totalPaid,
                'balance' => $netAmount - $totalPaid,
            ]);

            // Update Sale Status (if full return)
            if ($sale) {
                // $return is already saved and has net_amount, so we can sum all returns for this sale
                $totalReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)->sum('net_amount');
                if ($totalReturned >= $sale->total_net && $sale->total_net > 0) {
                    $sale->update(['sale_status' => 'returned']);
                }
            }

            // NOTE: No Journal/Receipt Voucher created on sale return.
            // A Payment Voucher is only created above if the user entered a payment amount.

            // ─── Update Customer Ledger ────────────────────────────────────────
            // Step 1: Sale Return entry — balance reduces by netAmount (customer owes us less)
            $ledger = \App\Models\CustomerLedger::where('customer_id', $validated['customer_id'])
                ->latest('id')->first();

            $prev_bal = $ledger
                ? (float) $ledger->closing_balance
                : (float) (\App\Models\Customer::find($validated['customer_id'])->previous_balance ?? 0);

            $after_return_bal = $prev_bal - $netAmount;

            \App\Models\CustomerLedger::create([
                'customer_id'      => $validated['customer_id'],
                'branch_id'        => $branchId ?? 1,
                'admin_or_user_id' => auth()->id() ?? 1,
                'description'      => "Sale Return #{$nextInvoice}",
                'previous_balance' => $prev_bal,
                'closing_balance'  => $after_return_bal,
                'opening_balance'  => 0,
                'source_type'      => \App\Models\SaleReturn::class,
                'source_id'        => $return->id,
            ]);

            // Step 2: Payment entry — if cash refunded, balance reduces further by totalPaid
            $final_bal = $after_return_bal;
            if ($totalPaid > 0) {
                $final_bal = $after_return_bal - $totalPaid;

                \App\Models\CustomerLedger::create([
                    'customer_id'      => $validated['customer_id'],
                    'branch_id'        => $branchId ?? 1,
                    'admin_or_user_id' => auth()->id() ?? 1,
                    'description'      => "Payment Voucher - Refund for Return #{$nextInvoice}",
                    'previous_balance' => $after_return_bal,
                    'closing_balance'  => $final_bal,
                    'opening_balance'  => 0,
                    'source_type'      => \App\Models\PaymentVoucher::class,
                    'source_id'        => $pvid ?? null,
                ]);
            }

            // Update Customer master balance
            $cust = \App\Models\Customer::find($validated['customer_id']);
            if ($cust) {
                $cust->previous_balance = $final_bal;
                $cust->save();
            }

            DB::commit();

            return redirect()->route('sale.return.index')->with('success', 'Sale return processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error processing return: '.$e->getMessage());
        }
    }

    /**
     * Display all sale returns
     */
    public function saleReturnIndex()
    {
        $branchId = $this->getBranchId();
        $returns = SaleReturn::with(['customer', 'sale', 'warehouse'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        // Calculate updated financial details
        $returns->each(function ($return) {
            if ($return->sale) {
                $sale = $return->sale;

                $return->original_net_amount = (float) $sale->total_net;
                $return->original_paid_amount = (float) $sale->cash;

                $totalReturned = SaleReturn::where('sale_id', $sale->id)->sum('net_amount');

                $return->new_net_amount  = max(0, (float) $sale->total_net - $totalReturned);
                $return->new_due_amount  = max(0, (float) $sale->total_net - (float) $sale->cash - $return->net_amount);
                $return->total_returned  = (float) $totalReturned;
            } else {
                $return->original_net_amount = null;
                $return->original_paid_amount = null;
                $return->new_net_amount  = null;
                $return->new_due_amount  = null;
                $return->total_returned  = null;
            }
        });

        return view('admin_panel.sale.sale_return_note.index', compact('returns'));
    }

    /**
     * View a specific sale return
     */
    public function viewReturn($id)
    {
        $return = SaleReturn::with(['customer', 'sale', 'items.product'])->findOrFail($id);

        return view('admin_panel.sale.sale_return.show', compact('return'));
    }
}
