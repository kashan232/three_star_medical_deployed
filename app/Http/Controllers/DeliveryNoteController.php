<?php

namespace App\Http\Controllers;

use App\Http\Traits\BranchScoped;
use App\Models\CustomerLedger;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeliveryNoteController extends Controller
{
    use BranchScoped;

    // ─────────────────────────────────────────────
    //  INDEX — show all SOs that are in_delivery
    // ─────────────────────────────────────────────
    public function index()
    {
        $branchId = $this->getBranchId();

        $dcNotes = DeliveryNote::with([
            'sale',
            'customer',
            'items.product',
        ])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest()
            ->get();

        return view('admin_panel.sale.delivery_note.index', compact('dcNotes'));
    }

    public function print($id)
    {
        $dc = DeliveryNote::with(['sale', 'customer', 'items.product.brand', 'items.uom', 'branch'])->findOrFail($id);
        return view('admin_panel.sale.delivery_note.print', compact('dc'));
    }

    public function create()
    {
        $branchId  = $this->getBranchId();
        $branch    = \App\Models\Branch::find($branchId);
        $branchName = $branch ? $branch->name : 'Head Office';
        $nextDcNo  = 'Auto (e.g. 1-01, 1-02, 2-01)'; 
        $accounts  = app(\App\Services\BalanceService::class)->getPaymentAccounts();
        $warehouses = \App\Models\Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();
        $customers = \App\Models\Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $products = \App\Models\Product::with('packings')->orderBy('item_name')->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'item_name' => $p->item_name,
                'item_code' => $p->item_code,
                'pieces_per_box' => $p->pieces_per_box,
                'unit_name' => $p->unit?->name ?? ($p->unit_name ?? 'Piece'),
                'packings' => $p->packings->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'pieces_per_box' => $u->pieces_per_box
                ])
            ]);

        return view('admin_panel.sale.delivery_note.create', compact(
            'nextDcNo', 'accounts', 'branchName', 'branchId', 'warehouses', 'customers', 'products'
        ));
    }

    // ─────────────────────────────────────────────
    //  STORE — save DC Note
    // ─────────────────────────────────────────────
    public function store(Request $request)
    {
        try {
            $request->validate([
                'sale_id'        => 'required_without:is_sample|nullable|exists:sales,id',
                'customer_id_manual' => 'required_if:is_sample,1|nullable|exists:customers,id',
                'delivery_date'  => 'nullable|date',
                'product_id'     => 'required|array|min:1',
                'product_id.*'   => 'required|exists:products,id',
                'qty'            => 'required|array',
                'qty.*'          => 'required', 
                'warehouse_id'   => 'required|array',
                'warehouse_id.*' => 'required|exists:warehouses,id',
            ], [
                'warehouse_id.*.required' => 'Please select a warehouse for every product line.',
                'product_id.required'     => 'You must add at least one product to the Delivery Note.',
                'product_id.*.required'   => 'Product selection is missing for one or more lines.',
                'qty.*.required'          => 'Delivery quantity is required for all items.',
            ]);

            $branchId   = $this->getBranchId() ?? (int)($request->branch_id ?? 1);
            $isSample   = $request->has('is_sample');
            $sale       = !$isSample ? Sale::with('items')->findOrFail($request->sale_id) : null;

            DB::transaction(function () use ($request, $sale, $branchId, $isSample) {
                $dcNo = DeliveryNote::generateDcNo($isSample ? null : $sale->id, $branchId);
                $subtotal = 0;

                $dcNote = DeliveryNote::create([
                    'dc_no'         => $dcNo,
                    'sale_id'       => $isSample ? null : $sale->id,
                    'branch_id'     => $branchId,
                    'customer_id'   => $isSample ? $request->customer_id_manual : $sale->customer_id,
                    'delivery_date' => $request->delivery_date ?? now()->toDateString(),
                    'note'          => $request->note,
                    'is_sample'     => $isSample,
                    'enable_hs_code'=> $request->has('enable_hs_code'),
                    'status'        => 'completed',
                    'subtotal'      => 0, 'net_amount' => 0, 'paid_amount' => 0,
                ]);

                if (!$isSample) $sale->update(['sale_status' => 'in_delivery']);

                $productIds    = $request->product_id    ?? [];
                $uomIds        = $request->uom_id        ?? [];
                $qtys          = $request->qty           ?? [];
                $prices        = $request->price         ?? [];
                $warehouseIds  = $request->warehouse_id  ?? [];
                $batchIds      = $request->batch_id      ?? [];
                $saleItemIds   = $request->sale_item_id  ?? [];
                $freeQtys      = $request->free_qty      ?? [];

                foreach ($productIds as $i => $pid) {
                    if (!$pid) continue;
                    $product = \App\Models\Product::find($pid);
                    $ppb = (int)($product->pieces_per_box ?? 1);
                    $pieces = $this->parseQtyToPieces($qtys[$i], $ppb);
                    $freePieces = (int)($freeQtys[$i] ?? 0);
                    $totalPiecesToDeduct = $pieces + $freePieces;

                    if ($totalPiecesToDeduct <= 0) continue;

                    $saleItemId = $saleItemIds[$i] ?? null;
                    if ($saleItemId) {
                        $saleItem = SaleItem::find($saleItemId);
                        $remPieces = $saleItem->total_pieces - $saleItem->delivered_qty;
                        if ($pieces > $remPieces) throw new \Exception("Limit exceeded for {$product->item_name}. Max allowed: {$remPieces}.");
                    }

                    $price = (float)($prices[$i] ?? 0);
                    $lineTotal = $pieces * $price;
                    $subtotal += $lineTotal;

                    $warehouseId = (int)$warehouseIds[$i];
                    $selectedBatchId = (int)($batchIds[$i] ?? 0) ?: null;
                    $deductions = \App\Http\Controllers\ProductBatchController::deductFromBatches($pid, $totalPiecesToDeduct, $warehouseId, $selectedBatchId, $branchId);

                    $pBatchId = null; $pLot = null; $pMfg = null; $pExp = null;
                    if (!empty($deductions)) {
                        $b = \App\Models\ProductBatch::find($deductions[0]['batch_id']);
                        $pBatchId = $b->id; $pLot = $b->batch_number; $pMfg = $b->mfg_date; $pExp = $b->exp_date;
                    }

                    $dcItem = DeliveryNoteItem::create([
                        'dc_note_id' => $dcNote->id, 'sale_item_id' => $saleItemId, 'product_id' => $pid,
                        'uom_id' => $uomIds[$i] ?? null, 'warehouse_id' => $warehouseId, 'batch_id' => $pBatchId,
                        'lot_number' => $pLot, 'mfg_date' => $pMfg, 'exp_date' => $pExp, 'qty' => $pieces,
                        'total_pieces' => $pieces, 'free_qty' => $freePieces, 'price' => $price, 'line_total' => $lineTotal,
                    ]);

                    if (!empty($deductions)) {
                        foreach ($deductions as $d) {
                            DB::table('sale_item_batches')->insert([
                                'sale_item_id' => $saleItemId, 
                                'delivery_note_item_id' => $dcItem->id,
                                'product_batch_id' => $d['batch_id'], 
                                'qty_deducted' => $d['qty'],
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    }

                    \App\Services\StockService::debit($pid, $dcItem->uom_id, $warehouseId, $branchId, $totalPiecesToDeduct);
                    
                    DB::table('stock_movements')->insert([
                        'product_id' => $pid, 'type' => 'out', 'qty' => -$totalPiecesToDeduct, 'ref_type' => 'dc',
                        'ref_id' => $dcNote->id, 'note' => 'DC #'.$dcNote->dc_no, 'created_at' => now(), 'updated_at' => now(),
                    ]);

                    if ($saleItemId) SaleItem::where('id', $saleItemId)->increment('delivered_qty', $pieces);
                }

                $dcNote->update(['subtotal' => $subtotal, 'net_amount' => $subtotal]);
                if (!$isSample) $this->syncSaleStatus($sale);
            });

            return redirect()->route('delivery.note.index')->with('success', 'Delivery Note created successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $branchId = $this->getBranchId();
        $dc = DeliveryNote::with(['items.product.unit', 'items.uom', 'customer', 'sale.items'])->findOrFail($id);
        if ($dc->status === 'cancelled') return redirect()->route('delivery.note.index')->with('error', 'Cannot edit cancelled DC.');

        $branch    = \App\Models\Branch::find($branchId);
        $branchName = $branch ? $branch->name : 'Head Office';
        $warehouses = \App\Models\Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))->get();
        $customers = \App\Models\Customer::when($branchId, fn ($q) => $q->where('branch_id', $branchId))->get();
        $products = \App\Models\Product::with('packings')->orderBy('item_name')->get()
            ->map(fn($p) => [
                'id' => $p->id, 'item_name' => $p->item_name, 'item_code' => $p->item_code,
                'pieces_per_box' => $p->pieces_per_box, 'unit_name' => $p->unit?->name ?? 'Piece',
                'packings' => $p->packings->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'pieces_per_box' => $u->pieces_per_box])
            ]);

        return view('admin_panel.sale.delivery_note.edit', compact('dc', 'branchName', 'branchId', 'warehouses', 'customers', 'products'));
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'delivery_date' => 'nullable|date', 'product_id' => 'required|array|min:1',
                'product_id.*' => 'required|exists:products,id', 'qty' => 'required|array',
                'warehouse_id' => 'required|array', 'warehouse_id.*' => 'required|exists:warehouses,id',
            ], [
                'warehouse_id.*.required' => 'Please select a warehouse for every product line.',
                'product_id.required'     => 'You must have at least one product to update the Delivery Note.',
                'qty.*.required'          => 'Delivery quantity is required for all items.',
            ]);

            $dcNote = DeliveryNote::with('items')->findOrFail($id);
            if ($dcNote->status === 'cancelled') throw new \Exception('Cannot update cancelled DC.');

            DB::transaction(function () use ($request, $dcNote) {
                $this->revertDCItems($dcNote);
                $dcNote->items()->delete();

                $branchId = $dcNote->branch_id; $isSample = $dcNote->is_sample; $sale = $dcNote->sale;
                $pIds = $request->product_id; $uIds = $request->uom_id; $qtys = $request->qty;
                $prices = $request->price; $wIds = $request->warehouse_id; $bIds = $request->batch_id;
                $siIds = $request->sale_item_id; $fQtys = $request->free_qty;

                $subtotal = 0;
                foreach ($pIds as $i => $pid) {
                    if (!$pid) continue;
                    $product = \App\Models\Product::find($pid);
                    $pieces = $this->parseQtyToPieces($qtys[$i], (int)($product->pieces_per_box ?? 1));
                    $freePcs = (int)($fQtys[$i] ?? 0); $totalPcs = $pieces + $freePcs;
                    if ($totalPcs <= 0) continue;

                    $siId = $siIds[$i] ?? null;
                    if ($siId) {
                        $si = SaleItem::find($siId);
                        if ($pieces > ($si->total_pieces - $si->delivered_qty)) throw new \Exception("Limit exceeded for {$product->item_name}.");
                    }

                    $lineTotal = $pieces * (float)($prices[$i] ?? 0);
                    $subtotal += $lineTotal;

                    $deductions = \App\Http\Controllers\ProductBatchController::deductFromBatches($pid, $totalPcs, (int)$wIds[$i], (int)($bIds[$i] ?? 0) ?: null, $branchId);
                    $pB = null; $pL = null; $pM = null; $pE = null;
                    if (!empty($deductions)) {
                        $b = \App\Models\ProductBatch::find($deductions[0]['batch_id']);
                        $pB = $b->id; $pL = $b->batch_number; $pM = $b->mfg_date; $pE = $b->exp_date;
                    }

                    $dcItem = DeliveryNoteItem::create([
                        'dc_note_id' => $dcNote->id, 'sale_item_id' => $siId, 'product_id' => $pid,
                        'uom_id' => $uIds[$i] ?? null, 'warehouse_id' => $wIds[$i], 'batch_id' => $pB,
                        'lot_number' => $pL, 'mfg_date' => $pM, 'exp_date' => $pE, 'qty' => $pieces,
                        'total_pieces' => $pieces, 'free_qty' => $freePcs, 'price' => (float)($prices[$i] ?? 0), 'line_total' => $lineTotal,
                    ]);

                    if (!empty($deductions)) {
                        foreach ($deductions as $d) {
                            DB::table('sale_item_batches')->insert([
                                'sale_item_id' => $siId, 
                                'delivery_note_item_id' => $dcItem->id,
                                'product_batch_id' => $d['batch_id'], 
                                'qty_deducted' => $d['qty'],
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    }

                    \App\Services\StockService::debit($pid, $dcItem->uom_id, (int)$wIds[$i], $branchId, $totalPcs);
                    DB::table('stock_movements')->insert([
                        'product_id' => $pid, 'type' => 'out', 'qty' => -$totalPcs, 'ref_type' => 'dc',
                        'ref_id' => $dcNote->id, 'note' => 'DC Update #'.$dcNote->dc_no, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    if ($siId) SaleItem::where('id', $siId)->increment('delivered_qty', $pieces);
                }

                $dcNote->update([
                    'delivery_date' => $request->delivery_date ?? $dcNote->delivery_date, 'note' => $request->note,
                    'subtotal' => $subtotal, 'net_amount' => $subtotal,
                ]);
                if ($sale) $this->syncSaleStatus($sale);
            });
            return redirect()->route('delivery.note.index')->with('success', 'DC updated successfully.');
        } catch (\Exception $e) { return back()->withInput()->withErrors(['error' => $e->getMessage()]); }
    }

    public function cancel($id)
    {
        try {
            $dcNote = DeliveryNote::with('items')->findOrFail($id);
            if ($dcNote->status === 'cancelled') return back()->with('error', 'Already cancelled.');
            DB::transaction(function () use ($dcNote) {
                $this->revertDCItems($dcNote);
                $dcNote->update(['status' => 'cancelled']);
                if ($dcNote->sale) $this->syncSaleStatus($dcNote->sale);
            });
            return redirect()->route('delivery.note.index')->with('success', 'DC cancelled and stock returned.');
        } catch (\Exception $e) { return back()->with('error', $e->getMessage()); }
    }

    private function revertDCItems(DeliveryNote $dcNote)
    {
        foreach ($dcNote->items as $item) {
            $totalPcs = (int)($item->total_pieces + $item->free_qty);
            if ($totalPcs <= 0) continue;

            $batchDeductions = DB::table('sale_item_batches')->where('delivery_note_item_id', $item->id)->get();
            foreach ($batchDeductions as $bd) {
                \App\Http\Controllers\ProductBatchController::returnToBatch($bd->product_batch_id, $bd->qty_deducted);
            }
            DB::table('sale_item_batches')->where('delivery_note_item_id', $item->id)->delete();

            \App\Services\StockService::credit($item->product_id, $item->uom_id, $item->warehouse_id, $dcNote->branch_id, $totalPcs);
            DB::table('stock_movements')->insert([
                'product_id' => $item->product_id, 'type' => 'in', 'qty' => $totalPcs, 'ref_type' => 'dc_cancel',
                'ref_id' => $dcNote->id, 'note' => 'Reversal of DC #'.$dcNote->dc_no, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($item->sale_item_id) SaleItem::where('id', $item->sale_item_id)->decrement('delivered_qty', $item->total_pieces);
        }
    }

    private function syncSaleStatus(Sale $sale)
    {
        $items = SaleItem::where('sale_id', $sale->id)->get();
        $deliveredCount = 0;
        $fully = $items->every(function ($si) use (&$deliveredCount) {
            if ($si->delivered_qty > 0) $deliveredCount++;
            return (int)$si->delivered_qty >= (int)$si->total_pieces && (int)$si->total_pieces > 0;
        });
        if ($fully) $sale->update(['sale_status' => 'delivered']);
        elseif ($deliveredCount > 0) $sale->update(['sale_status' => 'in_delivery']);
        else $sale->update(['sale_status' => 'draft']);
    }

    public function getSaleOrders()
    {
        $branchId = $this->getBranchId();
        $sales = Sale::with(['customer_relation', 'items.product'])->whereIn('sale_status', ['draft', 'in_delivery'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->latest()->get()
            ->map(fn ($s) => [
                'id' => $s->id, 'invoice_no' => $s->invoice_no, 'sale_date' => $s->created_at ? $s->created_at->format('Y-m-d') : 'N/A',
                'customer_name' => $s->customer_relation->customer_name ?? 'N/A', 'customer_id' => $s->customer_id,
                'total_net' => $s->total_net, 'items_count' => $s->items->count(),
            ]);
        return response()->json($sales);
    }

    public function getSaleOrderItems($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.uom', 'customer_relation'])->findOrFail($id);
        $items = $sale->items->filter(fn($i) => ($i->total_pieces - ($i->delivered_qty ?? 0)) > 0)->map(function($i) {
            $ppb = $i->product->pieces_per_box > 0 ? (int)$i->product->pieces_per_box : 1;
            $rem = max(0, (int)$i->total_pieces - (int)$i->delivered_qty);
            $wStock = \App\Models\WarehouseStock::where('product_id', $i->product_id)->where('warehouse_id', $i->warehouse_id)->first();
            return [
                'sale_item_id' => $i->id, 'product_id' => $i->product_id, 'product_name' => $i->product->item_name ?? 'N/A',
                'product_code' => $i->product->item_code ?? '', 'hs_code' => $i->product->hs_code ?? '', 'ppb' => $ppb,
                'so_total_pieces' => $i->total_pieces, 'remaining_pieces' => $rem, 'price' => $i->price,
                'warehouse_id' => $i->warehouse_id, 'warehouse_stock' => $wStock ? (int)$wStock->total_pieces : 0,
                'uom' => $i->uom->name ?? ($i->uom_name ?? ($i->product->unit->name ?? 'Piece')),
            ];
        });
        return response()->json(['sale' => ['id' => $sale->id, 'invoice_no' => $sale->invoice_no, 'customer_name' => $sale->customer_relation->customer_name ?? 'N/A', 'customer_id' => $sale->customer_id], 'items' => $items->values()]);
    }

    public function getNextDcNo(Request $request)
    {
        $saleId = (int)$request->sale_id ?: null;
        $branchId = $this->getBranchId() ?? (int)($request->branch_id ?? 1);
        return response()->json(['dc_no' => DeliveryNote::generateDcNo($saleId, $branchId)]);
    }

    private function parseQtyToPieces($input, $ppb)
    {
        if (strpos($input, '.') !== false) {
            $parts = explode('.', $input); $boxes = (int)$parts[0]; $loose = (int)($parts[1] ?? 0);
            return ($boxes * $ppb) + $loose;
        }
        return (int)$input;
    }
}
