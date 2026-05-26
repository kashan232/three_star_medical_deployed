<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Cdr;
use App\Models\Customer;
use App\Models\Account;
use App\Models\ProductDiscount;
use App\Models\PriceLog;
use App\Models\DeliveryNote;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Brand;
use App\Http\Traits\BranchScoped;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    use BranchScoped;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            view()->share('activeBranch', $this->getActiveBranch());
            return $next($request);
        });
    }

    protected function getActiveBranch()
    {
        $branchId = $this->getBranchId();
        if ($branchId) {
            return \App\Models\Branch::find($branchId);
        }
        return \App\Models\Branch::first(); // Default to first branch if no ID (usually Head Office)
    }

    public function onhand()
    {
        // Pull every product with its live warehouse stock
        $products = DB::table('products')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units',  'units.id',  '=', 'products.unit_id')
            ->select(
                'products.id',
                'products.item_code',
                'products.item_name',
                'products.size_mode',
                'products.pieces_per_box',
                'products.total_m2',
                'products.price_per_m2',
                'products.sale_price_per_box',
                'products.sale_price_per_piece',
                'products.purchase_price_per_piece',
                'products.purchase_price_per_box',
                DB::raw('COALESCE(brands.name, "-") as brand_name'),
                DB::raw('COALESCE(units.name, "-")  as unit_name')
            )
            ->orderBy('products.item_name')
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $branchId   = $this->getBranchId();

        // ── Warehouse stock breakdown per product ──────────────────────────
        $whStocksQry = DB::table('warehouse_stocks')
            ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
            ->whereIn('warehouse_stocks.product_id', $productIds)
            ->select(
                'warehouse_stocks.product_id',
                'warehouses.warehouse_name',
                'warehouse_stocks.quantity  as boxes',
                'warehouse_stocks.total_pieces as pieces'
            );

        if ($branchId) {
            $whStocksQry->where('warehouse_stocks.branch_id', $branchId);
        }

        $whStocks = $whStocksQry->get()->groupBy('product_id');

        // ── Purchase quantities & amounts ──────────────────────────────────
        $purchMapQry = DB::table('purchase_items')
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, COALESCE(SUM(qty),0) as qty, COALESCE(SUM(line_total),0) as amount');

        if ($branchId) {
            $purchMapQry->whereIn('purchase_id', function ($sub) use ($branchId) {
                $sub->select('id')->from('purchases')->where('branch_id', $branchId);
            });
        }
        $purchMap = $purchMapQry->groupBy('product_id')->get()->keyBy('product_id');

        // ── Sale quantities & amounts ──────────────────────────────────────
        $saleMapQry = DB::table('sale_items')
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, COALESCE(SUM(qty),0) as qty, COALESCE(SUM(total),0) as amount');

        if ($branchId) {
            $saleMapQry->whereIn('sale_id', function ($sub) use ($branchId) {
                $sub->select('id')->from('sales')->where('branch_id', $branchId);
            });
        }
        $saleMap = $saleMapQry->groupBy('product_id')->get()->keyBy('product_id');

        $rows = [];
        $grandOnHand  = 0;
        $grandCostVal = 0;
        $grandSaleVal = 0;
        $outOfStock   = 0;

        foreach ($products as $p) {
            $ppb       = max(1, (int) $p->pieces_per_box);
            $sizeMode  = $p->size_mode ?? 'by_pieces';

            // Live stock from warehouse_stocks
            $whGroup   = $whStocks->get($p->id, collect());
            $totalPcs  = $whGroup->sum('pieces');
            $totalBoxes = $whGroup->sum('boxes');

            // Financials
            $purch  = $purchMap->get($p->id);
            $sale   = $saleMap->get($p->id);
            $pAmt   = (float) ($purch->amount ?? 0);
            $sAmt   = (float) ($sale->amount  ?? 0);

            // Cost & sale value of on-hand stock
            $purchPricePpc = (float) ($p->purchase_price_per_piece ?? 0);
            $purchPricePbx = (float) ($p->purchase_price_per_box   ?? 0);
            $salePricePbx  = (float) ($p->sale_price_per_box       ?? 0);
            $salePricePpc  = (float) ($p->sale_price_per_piece      ?? 0);
            $pricePerM2    = (float) ($p->price_per_m2             ?? 0);
            $totalM2       = (float) ($p->total_m2                 ?? 0);

            // Display qty
            $boxes = floor($totalPcs / $ppb);
            $loose = $totalPcs % $ppb;
            $dotNotation = $boxes . '.' . $loose;

            if ($sizeMode === 'by_size') {
                $displayQty = ($totalM2 > 0 && $ppb > 0)
                    ? round($totalPcs * $totalM2, 2) . ' m²'
                    : $totalPcs . ' pcs';
                $costVal = $pricePerM2 > 0
                    ? round($totalPcs * $totalM2, 2) * $pricePerM2
                    : $totalPcs * $purchPricePpc;
                $saleVal = $pricePerM2 > 0
                    ? round($totalPcs * $totalM2, 2) * $pricePerM2
                    : $totalPcs * $salePricePpc;
            } elseif ($sizeMode === 'by_cartons' || $sizeMode === 'by_carton') {
                $displayQty = $boxes . ' box' . ($loose > 0 ? ' + ' . $loose . ' pcs' : '');
                $costVal = $boxes * $purchPricePbx + $loose * $purchPricePpc;
                $saleVal = $boxes * $salePricePbx  + $loose * $salePricePpc;
            } else {
                $displayQty = $totalPcs . ' pcs';
                $costVal = $totalPcs * ($purchPricePpc ?: ($purchPricePbx / $ppb));
                $saleVal = $totalPcs * ($salePricePpc  ?: ($salePricePbx  / $ppb));
            }

            $grandOnHand  += $totalPcs;
            $grandCostVal += $costVal;
            $grandSaleVal += $saleVal;
            if ($totalPcs <= 0) $outOfStock++;

            $rows[] = (object)[
                'id'              => $p->id,
                'item_code'       => $p->item_code ?? '-',
                'item_name'       => ($p->item_name ?? '-') . ' ' . ($p->brand_name ?? ''),
                'brand_name'      => $p->brand_name,
                'unit_name'       => $p->unit_name,
                'size_mode'       => $sizeMode,
                'total_pieces'    => $totalPcs,
                'total_boxes'     => $totalBoxes,
                'display_qty'     => $displayQty,
                'dot_notation'    => $dotNotation,
                'cost_value'      => round($costVal, 2),
                'sale_value'      => round($saleVal, 2),
                'purchase_amount' => round($pAmt, 2),
                'sale_amount'     => round($sAmt, 2),
                'warehouses'      => $whGroup->map(fn($w) => [
                    'name'    => $w->warehouse_name,
                    'boxes'   => (int) floor($w->pieces / $ppb),
                    'loose'   => (int) ($w->pieces % $ppb),
                    'display' => floor($w->pieces / $ppb) . '.' . ($w->pieces % $ppb),
                    'pieces'  => $w->pieces,
                ])->values(),
                'stock_status'    => $totalPcs <= 0 ? 'out' : ($totalPcs < 20 ? 'low' : 'ok'),
            ];
        }

        $summary = (object)[
            'total_products' => count($rows),
            'out_of_stock'   => $outOfStock,
            'low_stock'      => collect($rows)->where('stock_status', 'low')->count(),
            'grand_on_hand'  => $grandOnHand,
            'cost_value'     => round($grandCostVal, 2),
            'sale_value'     => round($grandSaleVal, 2),
        ];

        return view('admin_panel.reporting.onhand', compact('rows', 'summary'));
    }

    public function item_stock_report()
    {
        $products    = Product::orderBy('item_name')->get();
        $categories  = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands      = \App\Models\Brand::orderBy('name')->get();
        
        $isSuperAdmin = $this->isSuperAdmin();
        $branches    = $isSuperAdmin
            ? DB::table('branches')->select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('admin_panel.reporting.item_stock_report',
            compact('products', 'isSuperAdmin', 'branches', 'categories', 'subCategories', 'brands'));
    }

    // AJAX endpoint to fetch report rows
    public function fetchItemStock(Request $request)
    {           
        try {
            $start        = $request->get('start_date');
            $end          = $request->get('end_date');
            $catId        = $request->get('category_id', 'all');
            $subId        = $request->get('sub_category_id', 'all');
            $brandId      = $request->get('brand_id', 'all');
            $warehouseId  = $request->get('warehouse_id', 'all');
            $productId    = $request->get('product_id', 'all');
            $statusFilter = $request->get('status', 'all');
            $branchId     = $request->get('branch_id', 'all');
            $reportType   = $request->get('report_type', 'summary');

            // Normalise "all" to null
            $warehouseId = ($warehouseId && $warehouseId !== 'all') ? (int)$warehouseId : null;
            $branchId    = ($branchId    && $branchId    !== 'all') ? (int)$branchId    : null;

            // ── 1. Product base query ────────────────────────────────────────
            $query = Product::with(['brand', 'category_relation', 'sub_category_relation', 'unit', 'packings']);
            $query->when($productId && $productId !== 'all', fn($q) => $q->where('id', $productId));
            $query->when($catId    && $catId    !== 'all', fn($q) => $q->where('category_id',     $catId));
            $query->when($subId    && $subId    !== 'all', fn($q) => $q->where('sub_category_id', $subId));
            $query->when($brandId  && $brandId  !== 'all', fn($q) => $q->where('brand_id',        $brandId));
            $products = $query->orderBy('item_name')->get();
            $productIds = $products->pluck('id')->toArray();


            // ── 2. Bulk-fetch live warehouse stock ───────────────────────────
            //    (warehouse_stocks is the single source of truth for current balance)
            $whStockQuery = DB::table('warehouse_stocks')
                ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
                ->whereIn('warehouse_stocks.product_id', $productIds)
                ->select(
                    'warehouse_stocks.product_id',
                    'warehouse_stocks.warehouse_id',
                    'warehouses.warehouse_name',
                    DB::raw('SUM(warehouse_stocks.total_pieces) as total_pieces'),
                    DB::raw('COALESCE(warehouses.branch_id, 0) as branch_id')
                )
                ->groupBy('warehouse_stocks.product_id', 'warehouse_stocks.warehouse_id', 'warehouses.warehouse_name', 'warehouses.branch_id')
                ->having('total_pieces', '>', 0);

            if ($warehouseId) $whStockQuery->where('warehouse_stocks.warehouse_id', $warehouseId);
            if ($branchId)    $whStockQuery->where('warehouses.branch_id', $branchId);
            $whStockAll = $whStockQuery->get()->groupBy('product_id');

            // ── 3. Bulk-fetch stock movements (Initial & Period) ────────────
            // We fetch everything in one query to avoid N+1 issues.
            // stock_movements.qty is +ve for IN, -ve for OUT.
            $hasBranchOnMovements = \Schema::hasColumn('stock_movements', 'branch_id');
            
            $startDt = $start ? $start . ' 00:00:00' : '1970-01-01 00:00:00';
            $endDt   = $end   ? $end   . ' 23:59:59' : '2099-12-31 23:59:59';

            $movementsQuery = DB::table('stock_movements')
                ->whereIn('product_id', $productIds)
                ->select('product_id',
                    // Initial = sum of all qty before start date (Robust handling of type vs qty)
                    DB::raw("SUM(CASE WHEN created_at < '$startDt' THEN (CASE WHEN type = 'out' AND qty > 0 THEN -qty WHEN type='in' AND qty < 0 THEN abs(qty) ELSE qty END) ELSE 0 END) as initial"),
                    
                    // Period Purchased (qty > 0 and ref_type is purchase related)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('PURCHASE', 'GRN', 'PUR', 'in', 'PURCHASE_ITEM') THEN abs(qty) ELSE 0 END) as purchased"),
                    
                    // Period Purchase Return (Should be negative as it reduces stock)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('PR', 'purchase_return', 'PURCHASE_RETURN') THEN -abs(qty) ELSE 0 END) as pur_return"),
                    
                    // Period Sold (Should be negative as it reduces stock)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('sale', 'SALE', 'SIN', 'sale_in', 'out', 'DC', 'DELIVERY_NOTE', 'delivery_note') THEN -abs(qty) ELSE 0 END) as sold"),
                    
                    // Period Sale Return (Should be positive as it adds to stock)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('SR', 'sale_return', 'SALE_RETURN', 'SRN') THEN abs(qty) ELSE 0 END) as sale_return"),
                    
                    // Adjustments (anything else in period)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('INIT', 'OPENING', 'ADJ', 'adjustment') THEN qty ELSE 0 END) as adjusted")
                );

            if ($branchId && $hasBranchOnMovements) {
                $movementsQuery->where('branch_id', $branchId);
            }
            $movementsAll = $movementsQuery->groupBy('product_id')->get()->keyBy('product_id');

            // ── 4. Bulk purchase & sale amounts (Financials) ────────────────
            $pAmtMap = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->whereIn('purchase_items.product_id', $productIds)
                ->when($start && $end, fn($q) => $q->whereBetween('purchases.purchase_date', [$start, $end]))
                ->when($branchId,    fn($q) => $q->where('purchases.branch_id', $branchId))
                ->when($warehouseId, fn($q) => $q->where('purchase_items.warehouse_id', $warehouseId))
                ->selectRaw('purchase_items.product_id, COALESCE(SUM(purchase_items.line_total), 0) as total')
                ->groupBy('purchase_items.product_id')
                ->pluck('total', 'product_id');

            $sAmtMap = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sale_items.product_id', $productIds)
                ->whereIn('sales.sale_status', ['posted', 'completed'])
                ->when($start && $end, fn($q) => $q->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end]))
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->selectRaw('sale_items.product_id, COALESCE(SUM(sale_items.total), 0) as total')
                ->groupBy('sale_items.product_id')
                ->pluck('total', 'product_id');

            // ── 5. Build rows ────────────────────────────────────────────────
            $rows      = [];
            $gPurAmt   = 0;
            $gSaleAmt  = 0;
            $gStockVal = 0;

            foreach ($products as $p) {
                $ppb = max(1, (int)($p->pieces_per_box ?? 1));
                $pid = $p->id;

                // Live balance from warehouse_stocks (Real-time current)
                $whGroup      = $whStockAll->get($pid, collect());
                $liveBalance  = (float)$whGroup->sum('total_pieces');

                // Movement summaries for the period
                $mv = $movementsAll->get($pid) ?: (object)[
                    'initial' => 0, 'purchased' => 0, 'pur_return' => 0, 
                    'sold' => 0, 'sale_return' => 0, 'adjusted' => 0
                ];

                $initial    = (float)$mv->initial;
                $purchased  = (float)$mv->purchased;
                $purReturn  = (float)abs($mv->pur_return);
                $sold       = (float)abs($mv->sold);
                $saleReturn = (float)$mv->sale_return;
                $adjusted   = (float)$mv->adjusted;

                // Calculated period balance: Initial + In - Out
                // Period Balance = initial + purchased - pur_return - sold + sale_return + adjusted
                $periodBalance = $initial + $purchased - $purReturn - $sold + $saleReturn + $adjusted;

                // If no date filters, period balance should match live balance (but calculation is safer for audit)
                // Use period balance for the table columns to ensure they reconcile.
                $displayBalance = (!$start && !$end) ? $liveBalance : $periodBalance;

                // Status (based on period-end balance)
                $status = 'normal';
                if ($displayBalance <= 0) $status = 'out_of_stock';
                elseif ($displayBalance <= ($p->alert_quantity ?? 10)) $status = 'low_stock';

                if ($statusFilter && $statusFilter !== 'all' && $statusFilter !== $status) continue;

                // Financial amounts
                $pAmt     = (float)($pAmtMap->get($pid, 0));
                $sAmt     = (float)($sAmtMap->get($pid, 0));
                $stockVal = $displayBalance * (float)($p->purchase_price_per_piece ?? 0);

                $gPurAmt   += $pAmt;
                $gSaleAmt  += $sAmt;
                $gStockVal += $stockVal;

                // Warehouse breakdown
                $whArrayList = $whGroup->map(fn($w) => [
                    'warehouse_name' => $w->warehouse_name,
                    'display'        => floor($w->total_pieces / $ppb) . '.' . ((int)$w->total_pieces % $ppb) . ' (' . (int)$w->total_pieces . ' pcs)',
                ])->values()->toArray();

                $rows[] = [
                    'id'                       => $pid,
                    'item_code'                => $p->item_code,
                    'item_name'                => ($p->item_name ?? '') . ' ' . ($p->brand?->name ?? ''),
                    'brand'                    => $p->brand?->name ?? '-',
                    'category'                 => $p->category_relation?->name ?? '-',
                    'sub_category'             => $p->sub_category_relation?->name ?? '-',
                    'unit'                     => $p->unit?->name ?? '-',
                    'pieces_per_box'           => $ppb,
                    'initial_stock'            => $initial,
                    'purchased'                => $purchased,
                    'purchase_return_qty'      => $purReturn,
                    'sold'                     => $sold,
                    'sale_return_qty'          => $saleReturn,
                    'adjusted_qty'             => $adjusted,
                    'balance'                  => $displayBalance,
                    'display_balance'          => [
                        'pieces'       => $displayBalance,
                        'boxes'        => (int)floor($displayBalance / $ppb),
                        'loose'        => (int)abs($displayBalance) % $ppb,
                        'dot_notation' => floor($displayBalance / $ppb) . '.' . ((int)abs($displayBalance) % $ppb),
                        'mode'         => str_contains(($p->size_mode ?? ''), 'carton') ? 'by_carton' : ($p->size_mode ?? 'by_piece'),
                    ],
                    'purchase_amount'          => round($pAmt, 2),
                    'sale_amount'              => round($sAmt, 2),
                    'stock_value'              => round($stockVal, 2),
                    'stock_status'             => $status,
                    'warehouses'               => $whArrayList,
                    'sale_price_per_box'       => (float)($p->sale_price_per_box   ?? 0),
                    'sale_price_per_piece'     => (float)($p->sale_price_per_piece  ?? 0),
                    'purchase_price_per_piece' => (float)($p->purchase_price_per_piece ?? 0),
                    'size_mode'                => $p->size_mode,
                    'packings'                 => $p->packings ? $p->packings->map(fn($u) => [
                        'name'           => $u->name,
                        'pieces_per_box' => $u->pieces_per_box,
                    ])->toArray() : [],
                ];
            }

            // ── 6. Ledger data for PDF export ────────────────────────────────
            $ledgerData = [];
            if ($reportType === 'ledger' && $start && $end) {
                foreach ($rows as $row) {
                    $ledgerData[$row['id']] = $this->getProductLedgerData(
                        $row['id'], $start, $end, $branchId, $warehouseId
                    );
                }
            }

            return response()->json([
                'data'           => $rows,
                'grand_total'    => round($gStockVal, 2),
                'grand_purchase' => round($gPurAmt, 2),
                'grand_sale'     => round($gSaleAmt, 2),
                'warehouses'     => Warehouse::select('id', 'warehouse_name')->get(),
                'ledger_data'    => $ledgerData,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    /**
     * Helper: full transactional ledger for a product (detail PDF)
     * Covers: GRN, DC, Sample DC, SIN, Sale Return, Purchase Return, Adjustments
     */
    private function getProductLedgerData($productId, $startDate, $endDate, $branchId = null, $warehouseId = null)
    {
        $hasBranch = \Schema::hasColumn('stock_movements', 'branch_id');

        // ── Opening Balance (all movements BEFORE start date) ─────────────────
        $openingQry = DB::table('stock_movements')
            ->where('product_id', $productId)
            ->where(DB::raw('DATE(created_at)'), '<', $startDate);
        if ($branchId && $hasBranch) $openingQry->where('branch_id', $branchId);
        $openingQty = (float) $openingQry->sum(DB::raw("CASE WHEN (type = 'out' OR type = 'OUT') AND qty > 0 THEN -qty WHEN (type='in' OR type='IN') AND qty < 0 THEN abs(qty) ELSE qty END"));

        // ── Transactions in period ────────────────────────────────────────────
        $txQry = DB::table('stock_movements')
            ->where('product_id', $productId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);
        if ($branchId && $hasBranch) $txQry->where('branch_id', $branchId);
        $movements = $txQry->orderBy('created_at')->get();

        $transactions = [];
        $runningBal   = $openingQty;

        foreach ($movements as $m) {
            $qty = (float) $m->qty;
            
            // Normalize sign based on 'type' column for backward compatibility
            if (($m->type === 'out' || $m->type === 'OUT') && $qty > 0) {
                $qty = -$qty;
            } elseif (($m->type === 'in' || $m->type === 'IN') && $qty < 0) {
                $qty = abs($qty);
            }

            $runningBal += $qty;
            $desc       = $m->note ?? 'STOCK MOVEMENT';
            $ref        = '-';
            $rate       = 0;
            $warehouse  = '';

            // ── GRN / Purchase ─────────────────────────────────────────────
            if (in_array(strtolower($m->ref_type), ['purchase', 'grn', 'pur', 'in'])) {
                $purch = DB::table('purchases')
                    ->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
                    ->leftJoin('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
                    ->where('purchases.id', $m->ref_id)
                    ->select('purchases.invoice_no', 'vendors.name as vendor_name', 'warehouses.warehouse_name')
                    ->first();
                if ($purch) {
                    $vendor    = strtoupper($purch->vendor_name ?? 'VENDOR');
                    $ref       = $purch->invoice_no ?? '-';
                    $warehouse = strtoupper($purch->warehouse_name ?? '');

                    $item = DB::table('purchase_items')
                        ->leftJoin('warehouses as wi', 'purchase_items.warehouse_id', '=', 'wi.id')
                        ->where('purchase_id', $m->ref_id)
                        ->where('product_id', $productId)
                        ->select('purchase_items.price', 'wi.warehouse_name as item_wh')
                        ->first();
                    if ($item) {
                        $rate = (float)$item->price;
                        if ($item->item_wh) $warehouse = strtoupper($item->item_wh);
                    }

                    $cleanRef = preg_replace('/^GRN-?/i', '', $ref);
                    $desc = "(grn-{$cleanRef} , {$vendor}"
                          . ($warehouse ? " , {$warehouse})" : ")");
                }

            // ── Delivery Challan (DC) — sample or regular ──────────────────
            } elseif (in_array(strtolower($m->ref_type), ['dc', 'delivery_note'])) {
                $dc = DB::table('delivery_notes')
                    ->leftJoin('customers', 'delivery_notes.customer_id', '=', 'customers.id')
                    ->where('delivery_notes.id', $m->ref_id)
                    ->select('delivery_notes.dc_no', 'delivery_notes.is_sample',
                             'delivery_notes.sale_id', 'customers.customer_name')
                    ->first();
                if ($dc) {
                    $customer = strtoupper($dc->customer_name ?? 'CUSTOMER');
                    $ref      = $dc->dc_no ?? '-';

                    // Prefer SIN invoice number if DC is linked to a sale
                    if ($dc->sale_id) {
                        $sinNo = DB::table('sales')->where('id', $dc->sale_id)->value('invoice_no');
                        if ($sinNo) $ref = $sinNo;
                    }

                    $dcItem = DB::table('delivery_note_items')
                        ->leftJoin('warehouses', 'delivery_note_items.warehouse_id', '=', 'warehouses.id')
                        ->where('dc_note_id', $m->ref_id)
                        ->where('product_id', $productId)
                        ->select('delivery_note_items.price', 'warehouses.warehouse_name')
                        ->first();
                    if ($dcItem) {
                        $rate = (float)($dcItem->price ?? 0);
                        $warehouse = strtoupper($dcItem->warehouse_name ?? '');
                    }

                    $cleanDcNo = preg_replace('/^DC-?/i', '', $dc->dc_no);
                    if ($dc->is_sample) {
                        $desc = "(sample-{$cleanDcNo} , {$customer}"
                              . ($warehouse ? " , {$warehouse})" : ")");
                    } else {
                        $desc = "(dc-{$cleanDcNo} , {$customer}"
                              . ($warehouse ? " , {$warehouse})" : ")");
                    }
                }

            // ── Sale Invoice (SIN) ─────────────────────────────────────────
            } elseif (in_array(strtolower($m->ref_type), ['sale', 'sin', 'sale_in', 'out'])) {
                $sale = DB::table('sales')
                    ->join('customers', 'sales.customer_id', '=', 'customers.id')
                    ->where('sales.id', $m->ref_id)
                    ->select('sales.invoice_no', 'customers.customer_name')
                    ->first();
                if ($sale) {
                    $customer = strtoupper($sale->customer_name ?? 'CUSTOMER');
                    $sinNo    = $sale->invoice_no ?? '-';
                    $ref      = $sinNo;

                    $item = DB::table('sale_items')
                        ->leftJoin('warehouses', 'sale_items.warehouse_id', '=', 'warehouses.id')
                        ->where('sale_id', $m->ref_id)
                        ->where('product_id', $productId)
                        ->select('price_per_piece', 'price', 'warehouses.warehouse_name')
                        ->first();
                    if ($item) {
                        $rate      = (float)($item->price_per_piece ?: $item->price);
                        $warehouse = strtoupper($item->warehouse_name ?? '');
                    }

                    $cleanSin = preg_replace('/^SIN-?/i', '', $sinNo);
                    $desc = "(sin-{$cleanSin} , {$customer}"
                          . ($warehouse ? " , {$warehouse})" : ")");
                }

            // ── Sale Return ────────────────────────────────────────────────
            } elseif (in_array(strtolower($m->ref_type), ['sr', 'sale_return'])) {
                $ret = DB::table('sale_returns')
                    ->leftJoin('customers', 'sale_returns.customer_id', '=', 'customers.id')
                    ->leftJoin('warehouses', 'sale_returns.warehouse_id', '=', 'warehouses.id')
                    ->where('sale_returns.id', $m->ref_id)
                    ->select('sale_returns.return_invoice', 'customers.customer_name', 'warehouses.warehouse_name')
                    ->first();
                if ($ret) {
                    $customer  = strtoupper($ret->customer_name ?? 'CUSTOMER');
                    $ref       = $ret->return_invoice ?? '-';
                    $warehouse = strtoupper($ret->warehouse_name ?? '');
                    $cleanSr = preg_replace('/^SR-?/i', '', $ref);
                    $desc = "(sr-{$cleanSr} , {$customer}"
                          . ($warehouse ? " , {$warehouse})" : ")");
                }

            // ── Purchase Return ────────────────────────────────────────────
            } elseif (in_array(strtolower($m->ref_type), ['pr', 'purchase_return'])) {
                $pr = DB::table('purchase_returns')
                    ->leftJoin('vendors', 'purchase_returns.vendor_id', '=', 'vendors.id')
                    ->leftJoin('warehouses', 'purchase_returns.warehouse_id', '=', 'warehouses.id')
                    ->where('purchase_returns.id', $m->ref_id)
                    ->select('purchase_returns.return_invoice', 'vendors.name as vendor_name', 'warehouses.warehouse_name')
                    ->first();
                if ($pr) {
                    $vendor    = strtoupper($pr->vendor_name ?? 'VENDOR');
                    $ref       = $pr->return_invoice ?? '-';
                    $warehouse = strtoupper($pr->warehouse_name ?? '');
                    $cleanPr = preg_replace('/^PR-?/i', '', $ref);
                    $desc = "(pr-{$cleanPr} , {$vendor}"
                          . ($warehouse ? " , {$warehouse})" : ")");
                }

            // ── Opening / Adjustment ───────────────────────────────────────
            } elseif (in_array(strtolower($m->ref_type), ['init', 'opening', 'adj', 'adjustment'])) {
                $desc = $m->ref_type === 'ADJ' ? 'STOCK ADJUSTMENT' : 'OPENING STOCK';
                $ref  = '-';
            }

            $transactions[] = [
                'date'    => \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i'),
                'desc'    => strtoupper($desc),
                'ref'     => $ref,
                'rate'    => $rate,
                'debit'   => $qty > 0 ? abs($qty) : 0,   // IN  (stock increases)
                'credit'  => $qty < 0 ? abs($qty) : 0,   // OUT (stock decreases)
                'balance' => $runningBal,
            ];
        }

        return [
            'opening_balance' => $openingQty,
            'closing_balance' => $runningBal,
            'transactions'    => $transactions,
        ];
    }

    public function purchase_report()
    {
        return view('admin_panel.reporting.purchase_report');
    }

    public function fetchPurchaseReport(Request $request)
    {
        $startDate   = $request->start_date;
        $endDate     = $request->end_date;
        $vendorId    = $request->vendor_id;
        $status      = $request->status;
        $warehouseId = $request->warehouse_id;
        $catId       = $request->category_id;
        $subId       = $request->sub_category_id;
        $brandId     = $request->brand_id;
        $productId   = $request->product_id;
        $branchId    = $this->getBranchId();

        $query = DB::table('purchases')
            ->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->leftJoin('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
            ->select(
                'purchases.id',
                'purchases.invoice_no',
                'purchases.purchase_date',
                'purchases.status_purchase',
                'vendors.name as vendor_name',
                'vendors.phone as vendor_phone',
                'warehouses.warehouse_name',
                'purchases.subtotal',
                'purchases.discount',
                'purchases.extra_cost',
                'purchases.net_amount',
                'purchases.paid_amount',
                'purchases.due_amount',
                'purchases.note',
                'purchases.po_ref'
            );

        // Branch filter
        if ($branchId) {
            $query->where('purchases.branch_id', $branchId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
        }
        if ($vendorId && $vendorId !== 'all') {
            $query->where('purchases.vendor_id', $vendorId);
        }
        if ($status && $status !== 'all') {
            $query->where('purchases.status_purchase', $status);
        }
        if ($warehouseId && $warehouseId !== 'all') {
            $query->where('purchases.warehouse_id', $warehouseId);
        }

        // Sub-query logic for product-based filters
        if (($catId && $catId !== 'all') || ($subId && $subId !== 'all') || ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all')) {
            $query->whereIn('purchases.id', function($sub) use ($catId, $subId, $brandId, $productId) {
                $sub->select('purchase_id')->from('purchase_items')
                    ->join('products', 'products.id', '=', 'purchase_items.product_id')
                    ->when($catId && $catId !== 'all', fn($q) => $q->where('products.category_id', $catId))
                    ->when($subId && $subId !== 'all', fn($q) => $q->where('products.sub_category_id', $subId))
                    ->when($brandId && $brandId !== 'all', fn($q) => $q->where('products.brand_id', $brandId))
                    ->when($productId && $productId !== 'all', fn($q) => $q->where('products.id', $productId));
            });
        }

        $purchases = $query->orderBy('purchases.purchase_date', 'desc')->get();

        // Enrich with items and returns
        $purchaseIds = $purchases->pluck('id')->toArray();

        // Items keyed by purchase_id
        $itemsMap = DB::table('purchase_items')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('product_uoms', 'purchase_items.uom_id', '=', 'product_uoms.id')
            ->whereIn('purchase_items.purchase_id', $purchaseIds)
            ->select(
                'purchase_items.purchase_id',
                'products.item_code',
                'products.item_name',
                'brands.name as brand_name',
                'purchase_items.qty',
                'purchase_items.loose_qty',
                'purchase_items.free_qty_pieces',
                'purchase_items.unit',
                'purchase_items.price',
                'purchase_items.item_discount',
                'purchase_items.line_total',
                'purchase_items.size_mode',
                'purchase_items.pieces_per_box',
                'purchase_items.gst_percent',
                'purchase_items.gst_amount',
                'purchase_items.it_percent',
                'purchase_items.adv_tax_percent',
                'products.hs_code',
                'product_uoms.name as table_uom_name',
                'purchase_items.uom_factor'
            )
            ->get()
            ->groupBy('purchase_id');

        // Returns keyed by purchase_id
        $returnsMap = DB::table('purchase_returns')
            ->whereIn('purchase_id', $purchaseIds)
            ->select('purchase_id', DB::raw('SUM(net_amount) as total_returned'), DB::raw('COUNT(*) as return_count'))
            ->groupBy('purchase_id')
            ->get()
            ->keyBy('purchase_id');

        $rows = [];
        $grandSubtotal = 0;
        $grandNet = 0;
        $grandPaid = 0;
        $grandDue = 0;
        $grandReturned = 0;

        foreach ($purchases as $p) {
            $items = $itemsMap->get($p->id, collect());
            $returnRow = $returnsMap->get($p->id);
            $totalReturned = $returnRow ? (float) $returnRow->total_returned : 0;

            $grandSubtotal += (float) $p->subtotal;
            $grandNet += (float) $p->net_amount;
            $grandPaid += (float) $p->paid_amount;
            $grandDue += (float) $p->due_amount;
            $grandReturned += $totalReturned;

            $rows[] = [
                'id' => $p->id,
                'invoice_no' => $p->invoice_no ?? ('-'),
                'po_ref' => $p->po_ref ?? ('-'),
                'purchase_date' => $p->purchase_date,
                'status' => $p->status_purchase,
                'vendor_name' => $p->vendor_name,
                'vendor_phone' => $p->vendor_phone ?? '-',
                'warehouse_name' => $p->warehouse_name ?? '-',
                'subtotal' => (float) $p->subtotal,
                'discount' => (float) $p->discount,
                'extra_cost' => (float) $p->extra_cost,
                'net_amount' => (float) $p->net_amount,
                'paid_amount' => (float) $p->paid_amount,
                'due_amount' => (float) $p->due_amount,
                'note' => $p->note ?? '',
                'total_returned' => $totalReturned,
                'return_count' => $returnRow ? (int) $returnRow->return_count : 0,
                'items' => $items->map(fn ($i) => [
                    'item_code' => $i->item_code,
                    'item_name' => ($i->item_name ?? '') . ' ' . ($i->brand_name ?? ''),
                    'brand_name' => $i->brand_name ?? '-',
                    'qty' => (float)(($i->qty * ($i->uom_factor ?: 1)) + ($i->loose_qty ?? 0)),
                    'free_qty' => (float)($i->free_qty_pieces ?? 0),
                    'unit' => $i->unit ?? '-',
                    'price' => (float) $i->price,
                    'item_discount' => (float) $i->item_discount,
                    'line_total' => (float) $i->line_total,
                    'gst_percent' => (float) ($i->gst_percent ?? 0),
                    'gst_amount' => (float) ($i->gst_amount ?? 0),
                    'it_percent' => (float) ($i->it_percent ?? 0),
                    'adv_tax_percent' => (float) ($i->adv_tax_percent ?? 0),
                    'hs_code' => $i->hs_code ?? '-',
                    'size_mode' => $i->size_mode ?? '-',
                    'uom_name' => $i->table_uom_name ?? '-',
                    'uom_factor' => (float) ($i->uom_factor ?? 1),
                ])->values(),
            ];
        }

        // Dropdown data for filters
        $vendors = DB::table('vendors')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'name')->orderBy('name')->get();
            
        $warehouses = DB::table('warehouses')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'warehouse_name')->orderBy('warehouse_name')->get();

        return response()->json([
            'data' => $rows,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'grand_subtotal' => $grandSubtotal,
            'grand_net' => $grandNet,
            'grand_paid' => $grandPaid,
            'grand_due' => $grandDue,
            'grand_returned' => $grandReturned,
        ]);
    }

    public function sale_report()
    {
        return view('admin_panel.reporting.sale_report');
    }

    public function fetchsaleReport(Request $request)
    {
        $start       = $request->start_date;
        $end         = $request->end_date;
        $customerId  = $request->customer_id;
        $status      = $request->status;
        $warehouseId = $request->warehouse_id;
        $catId       = $request->category_id;
        $subId       = $request->sub_category_id;
        $brandId     = $request->brand_id;
        $productId   = $request->product_id;
        $branchId    = $this->getBranchId();

        // sales table actual columns: total_bill_amount, total_extradiscount, total_net, cash, change
        $query = DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select(
                'sales.id',
                'sales.invoice_no',
                'sales.reference',
                'sales.sale_status',
                'sales.total_bill_amount',   // subtotal
                'sales.total_extradiscount', // discount
                'sales.total_net',
                'sales.cash',                // payment received
                'sales.change',              // change given back
                'sales.created_at',
                'customers.customer_name',
                'customers.mobile as customer_phone'
            );

        // Sub-query logic for product-based filters in Sales
        if (($catId && $catId !== 'all') || ($subId && $subId !== 'all') || ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all')) {
            $query->whereIn('sales.id', function($sub) use ($catId, $subId, $brandId, $productId) {
                $sub->select('sale_id')->from('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->when($catId && $catId !== 'all', fn($q) => $q->where('products.category_id', $catId))
                    ->when($subId && $subId !== 'all', fn($q) => $q->where('products.sub_category_id', $subId))
                    ->when($brandId && $brandId !== 'all', fn($q) => $q->where('products.brand_id', $brandId))
                    ->when($productId && $productId !== 'all', fn($q) => $q->where('products.id', $productId));
            });
        }

        // Branch filter
        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        if ($start && $end) {
            $query->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end]);
        }
        if ($customerId && $customerId !== 'all') {
            $query->where('sales.customer_id', $customerId);
        }
        if ($status && $status !== 'all') {
            $query->where('sales.sale_status', $status);
        }
        // Warehouse filter: filter via sale_items
        if ($warehouseId && $warehouseId !== 'all') {
            $query->whereIn('sales.id', function ($sub) use ($warehouseId) {
                $sub->select('sale_id')->from('sale_items')->where('warehouse_id', $warehouseId);
            });
        }

        $sales = $query->orderBy('sales.created_at', 'desc')->get();
        $saleIds = $sales->pluck('id')->toArray();

        // Sale items keyed by sale_id (with warehouse info)
        $itemsMap = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('warehouses', 'sale_items.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('product_uoms', function($join) {
                $join->on('products.id', '=', 'product_uoms.product_id')
                     ->on('sale_items.uom_factor', '=', 'product_uoms.pieces_per_box');
            })
            ->whereIn('sale_items.sale_id', $saleIds)
            ->select(
                'sale_items.sale_id',
                'products.item_code',
                'products.item_name',
                'brands.name as brand_name',
                'units.name as master_uom',
                'product_uoms.name as master_packing',
                'sale_items.qty',
                'sale_items.total_pieces',
                'sale_items.price',
                'sale_items.price_per_piece',
                'sale_items.total',
                'sale_items.free_qty',
                'sale_items.size_mode',
                'sale_items.uom_name',
                'sale_items.uom_factor',
                'sale_items.gst_percent',
                'sale_items.gst_amount',
                'sale_items.inc_tax',
                'sale_items.adv_tax',
                'sale_items.discount_amount',
                'products.hs_code',
                'warehouses.warehouse_name'
            )
            ->get()
            ->groupBy('sale_id');

        // Sale returns keyed by sale_id
        $returnsMap = DB::table('sale_returns')
            ->whereIn('sale_id', $saleIds)
            ->select(
                'sale_id',
                DB::raw('SUM(net_amount) as total_returned'),
                DB::raw('COUNT(*) as return_count')
            )
            ->groupBy('sale_id')
            ->get()
            ->keyBy('sale_id');

        $rows = [];
        $grandNet = 0;
        $grandPaid = 0;
        $grandReturned = 0;

        foreach ($sales as $s) {
            $items = $itemsMap->get($s->id, collect());
            $returnRow = $returnsMap->get($s->id);
            $totalReturned = $returnRow ? (float) $returnRow->total_returned : 0;

            // cash is total received, change is refunded — net paid = cash - change
            $cashReceived = (float) ($s->cash ?? 0);
            $changeGiven = (float) ($s->change ?? 0);
            $netPaid = max(0, $cashReceived - $changeGiven);
            $netDue = max(0, (float) $s->total_net - $netPaid);

            $grandNet += (float) $s->total_net;
            $grandPaid += $netPaid;
            $grandReturned += $totalReturned;

            // Derive warehouse name from the first item (since warehouse is on items, not header)
            $warehouseName = $items->first()?->warehouse_name ?? '-';

            $rows[] = [
                'id' => $s->id,
                'invoice_no' => $s->invoice_no ?? ('SLE-'.$s->id),
                'reference' => $s->reference ?? '-',
                'sale_status' => $s->sale_status,
                'customer_name' => $s->customer_name ?? 'Walk-in Customer',
                'customer_phone' => $s->customer_phone ?? '-',
                'warehouse_name' => $warehouseName,
                'subtotal' => (float) ($s->total_bill_amount ?? 0),
                'discount' => (float) ($s->total_extradiscount ?? 0),
                'total_net' => (float) $s->total_net,
                'paid' => $netPaid,
                'due' => $netDue,
                'created_at' => $s->created_at,
                'total_returned' => $totalReturned,
                'return_count' => $returnRow ? (int) $returnRow->return_count : 0,
                'items' => $items->map(function ($i) {
                    return [
                        'item_code' => $i->item_code,
                        'item_name' => ($i->item_name ?? '') . ' ' . ($i->brand_name ?? ''),
                        'brand_name' => $i->brand_name ?? '-',
                        'qty' => $i->qty,
                        'total_pieces' => $i->total_pieces,
                        'price' => (float) $i->price,
                        'price_per_piece' => (float) $i->price_per_piece,
                        'total' => (float) $i->total,
                        'size_mode' => $i->size_mode ?? '-',
                        'uom_name' => $i->master_packing ?? $i->uom_name ?? $i->master_uom ?? '-',
                        'master_uom' => $i->master_uom ?? '-',
                        'master_packing' => $i->master_packing ?? '-',
                        'uom_factor' => (float) ($i->uom_factor ?? 1),
                        'gst_percent' => (float) ($i->gst_percent ?? 0),
                        'gst_amount' => (float) ($i->gst_amount ?? 0),
                        'inc_tax_percent' => (float) ($i->inc_tax ?? 0),
                        'adv_tax_percent' => (float) ($i->adv_tax ?? 0),
                        'discount_amount' => (float) ($i->discount_amount ?? 0),
                        'hs_code' => $i->hs_code ?? '-',
                        'warehouse_name' => $i->warehouse_name ?? '-',
                        // Amount calculations for Sales (Base = total / (1 + GST% - WHT% - Adv%))
                        'wht_amount' => (function() use ($i) {
                            $total = (float)$i->total;
                            $gp = (float)$i->gst_percent;
                            $ip = (float)$i->inc_tax;
                            $ap = (float)$i->adv_tax;
                            $divisor = (1 + ($gp/100) - ($ip/100) - ($ap/100));
                            $base = $divisor > 0 ? ($total / $divisor) : 0;
                            return $base * ($ip / 100);
                        })(),
                        'adv_amount' => (function() use ($i) {
                            $total = (float)$i->total;
                            $gp = (float)$i->gst_percent;
                            $ip = (float)$i->inc_tax;
                            $ap = (float)$i->adv_tax;
                            $divisor = (1 + ($gp/100) - ($ip/100) - ($ap/100));
                            $base = $divisor > 0 ? ($total / $divisor) : 0;
                            return $base * ($ap / 100);
                        })(),
                    ];
                })->values(),
            ];
        }

        // Dropdown filter data
        $customers = DB::table('customers')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'customer_name')->orderBy('customer_name')->get();
            
        $warehouses = DB::table('warehouses')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'warehouse_name')->orderBy('warehouse_name')->get();

        return response()->json([
            'data' => $rows,
            'customers' => $customers,
            'warehouses' => $warehouses,
            'grand_net' => $grandNet,
            'grand_paid' => $grandPaid,
            'grand_due' => $grandNet - $grandPaid,
            'grand_returned' => $grandReturned,
        ]);
    }

    public function customer_ledger_report()
    {
        $branchId  = $this->getBranchId();
        $customers = DB::table('customers')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'customer_name')
            ->get();

        return view('admin_panel.reporting.customer_ledger_report', compact('customers'));
    }

    public function fetch_all_customer_ledgers(Request $request)
    {
        $start = $request->start_date;
        $end   = $request->end_date;
        $branchId = $this->getBranchId();

        if (!$start || !$end) {
            return response()->json(['error' => 'Invalid dates'], 400);
        }

        $customers = DB::table('customers')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Optimized fetching: group all relevant ledger entries in two big queries
        $allLastEntries = DB::table('customer_ledgers')
            ->whereDate('created_at', '<', $start)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->whereIn('id', function($query) use ($start) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('customer_ledgers')
                      ->whereDate('created_at', '<', $start)
                      ->groupBy('customer_id');
            })
            ->get()
            ->keyBy('customer_id');

        $allPeriodEntries = DB::table('customer_ledgers')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('customer_id');

        $allData = [];

        foreach ($customers as $customer) {
            $lastEntry = $allLastEntries->get($customer->id);
            $openingBalance = $lastEntry ? (float)$lastEntry->closing_balance : (float)($customer->opening_balance ?? 0);
            
            $periodEntries = $allPeriodEntries->get($customer->id, collect());
            
            $runningBalance = $openingBalance;
            $totalDebit = 0;
            $totalCredit = 0;
            $transactions = [];

            foreach ($periodEntries as $row) {
                $desc = strtolower($row->description ?? '');
                $prev  = (float) ($row->previous_balance ?? 0);
                $close = (float) ($row->closing_balance  ?? 0);
                $diff  = $close - $prev;

                $dr = $diff > 0 ? $diff : 0;
                $cr = $diff < 0 ? abs($diff) : 0;
                
                $runningBalance = $close;
                $totalDebit  += $dr;
                $totalCredit += $cr;

                $ref = '-';
                if (preg_match('/#(inv|pv|sr|jv|rv|re)-?\d+/i', $desc, $m)) $ref = $m[0];

                $transactions[] = [
                    'date'        => \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'),
                    'invoice'     => $ref !== '-' ? strtoupper(str_replace('#', '', $ref)) : '-',
                    'description' => $row->description ?? '',
                    'debit'       => $dr,
                    'credit'      => $cr,
                    'balance'     => $runningBalance,
                ];
            }

            // Only include customers with non-zero opening or activity
            if ($openingBalance != 0 || count($transactions) > 0) {
                $allData[] = [
                    'customer' => [
                        'id'            => $customer->customer_id ?? '-',
                        'name'          => $customer->customer_name,
                        'opening_raw'   => $customer->opening_balance ?? 0,
                    ],
                    'opening_balance' => $openingBalance,
                    'closing_balance' => $runningBalance,
                    'total_debit'     => $totalDebit,
                    'total_credit'    => $totalCredit,
                    'transactions'    => $transactions,
                ];
            }
        }

        return response()->json([
            'all_data' => $allData,
            'period'   => \Carbon\Carbon::parse($start)->format('d/m/Y') . " to " . \Carbon\Carbon::parse($end)->format('d/m/Y'),
            'date'     => now()->format('d/m/Y')
        ]);
    }

    public function fetch_customer_ledger(Request $request)
    {
        $customerId = (int) $request->customer_id;
        $start      = $request->start_date;
        $end        = $request->end_date;
        $catId      = $request->category_id;
        $subId      = $request->sub_category_id;
        $brandId    = $request->brand_id;
        $productId  = $request->product_id;
        $branchId   = $this->getBranchId();

        $customer = DB::table('customers')
            ->where('id', $customerId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        if (!$customer || !$start || !$end) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        // ── 1. Detect Twin Vendor (MUST happen first, before filter queries) ─
        // Only match if customer_id is non-empty to avoid false matches
        $twinVendor = null;
        $custCode = trim($customer->customer_id ?? '');
        $custCnic = trim($customer->cnic ?? '');
        if ($custCode !== '' || $custCnic !== '') {
            $twinVendor = DB::table('vendors')
                ->where(function($q) use ($custCode, $custCnic) {
                    if ($custCode !== '') $q->where('vendor_code', $custCode);
                    if ($custCnic !== '') $q->orWhere(function($q2) use ($custCnic) {
                        $q2->where('cnic', $custCnic)->whereNotNull('cnic')->where('cnic', '!=', '');
                    });
                })->first();
        }

        // ── 2. Opening balance ─────────────────────────────────────────────────
        $lastCustEntry = DB::table('customer_ledgers')
            ->where('customer_id', $customerId)
            ->whereDate('created_at', '<', $start)
            ->orderByDesc('created_at')->orderByDesc('id')->first();
        $openingBalance = $lastCustEntry
            ? (float)$lastCustEntry->closing_balance
            : (float)($customer->opening_balance ?? 0);

        $vendorOpeningBalance = 0;
        if ($twinVendor) {
            $lastVendEntry = DB::table('vendor_ledgers')
                ->where('vendor_id', $twinVendor->id)
                ->whereNull('deleted_at')
                ->whereDate('created_at', '<', $start)
                ->orderByDesc('created_at')->orderByDesc('id')->first();
            $vendorOpeningBalance = $lastVendEntry
                ? (float)$lastVendEntry->closing_balance
                : (float)($twinVendor->opening_balance ?? 0);
        }
        $netOpeningBalance = $openingBalance - $vendorOpeningBalance;

        // ── 3. Product filter: build allowed-invoice sets ─────────────────────
        $hasFilter = ($catId && $catId !== 'all') || ($subId && $subId !== 'all')
                  || ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all');

        $allowSales     = null; // null = no filter active
        $allowPurchases = null;

        if ($hasFilter) {
            $baseFilters = function($q) use ($catId, $subId, $brandId, $productId) {
                if ($catId && $catId !== 'all')    $q->where('products.category_id', $catId);
                if ($subId && $subId !== 'all')    $q->where('products.sub_category_id', $subId);
                if ($brandId && $brandId !== 'all') $q->where('products.brand_id', $brandId);
                if ($productId && $productId !== 'all') $q->where('products.id', $productId);
            };

            // Sales matching this customer + filter
            $sInv = DB::table('sale_items')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.customer_id', $customerId)
                ->tap($baseFilters)->pluck('sales.invoice_no')
                ->map(fn($v) => strtolower($v));

            $srInv = DB::table('sale_return_items')
                ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                ->where('sale_returns.customer_id', $customerId)
                ->tap($baseFilters)->pluck('sale_returns.return_invoice')
                ->map(fn($v) => strtolower($v));

            $allowSales = $sInv->concat($srInv)->unique()->values()->toArray();

            // Purchases matching twin vendor + filter (only if twin exists)
            if ($twinVendor) {
                $pInv = DB::table('purchase_items')
                    ->join('products', 'products.id', '=', 'purchase_items.product_id')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchases.vendor_id', $twinVendor->id)
                    ->tap($baseFilters)->pluck('purchases.invoice_no')
                    ->map(fn($v) => strtolower($v));

                $prInv = DB::table('purchase_return_items')
                    ->join('products', 'products.id', '=', 'purchase_return_items.product_id')
                    ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->where('purchase_returns.vendor_id', $twinVendor->id)
                    ->tap($baseFilters)->pluck('purchase_returns.return_invoice')
                    ->map(fn($v) => strtolower($v));

                $allowPurchases = $pInv->concat($prInv)->unique()->values()->toArray();
            } else {
                $allowPurchases = []; // No twin vendor → no purchase entries expected
            }
        }

        // ── 4. Fetch period entries ───────────────────────────────────────────
        $custEntries = DB::table('customer_ledgers')
            ->where('customer_id', $customerId)
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderBy('created_at')->orderBy('id')
            ->get()->map(fn($r) => (object)((array)$r + ['_src' => 'customer']));

        $vendEntries = collect();
        if ($twinVendor) {
            $vendEntries = DB::table('vendor_ledgers')
                ->where('vendor_id', $twinVendor->id)
                ->whereNull('deleted_at')
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->orderBy('created_at')->orderBy('id')
                ->get()->map(fn($r) => (object)((array)$r + ['_src' => 'vendor']));
        }

        $allEntries = $custEntries->concat($vendEntries)
            ->sortBy(fn($r) => $r->created_at . str_pad($r->id, 10, '0', STR_PAD_LEFT));

        // ── 5. Apply filter strictly ──────────────────────────────────────────
        if ($hasFilter) {
            $allEntries = $allEntries->filter(function($row) use ($allowSales, $allowPurchases) {
                $desc = strtolower($row->description ?? '');

                if ($row->_src === 'customer') {
                    if (empty($allowSales)) return false;
                    foreach ($allowSales as $inv) {
                        // Use word-boundary style: inv must appear as a whole token
                        if (preg_match('/\b' . preg_quote($inv, '/') . '\b/', $desc)) return true;
                    }
                    return false;
                } else {
                    if (empty($allowPurchases)) return false;
                    foreach ($allowPurchases as $inv) {
                        if (preg_match('/\b' . preg_quote($inv, '/') . '\b/', $desc)) return true;
                    }
                    return false;
                }
            });
        }

        // ── 6. Build running balance ──────────────────────────────────────────
        $runningBalance = $netOpeningBalance;
        $totalDebit = 0; $totalCredit = 0;
        $transactions = [];

        foreach ($allEntries as $row) {
            $desc  = $row->description ?? '';
            $ldesc = strtolower($desc);

            if ($row->_src === 'customer') {
                $prev  = (float)($row->previous_balance ?? 0);
                $close = (float)($row->closing_balance  ?? 0);
                $diff  = $close - $prev;
                $dr = max(0.0, $diff);
                $cr = max(0.0, -$diff);
                $runningBalance += $diff;
            } else {
                $vdr = (float)($row->debit  ?? 0);
                $vcr = (float)($row->credit ?? 0);
                if ($vdr == 0 && $vcr == 0) {
                    $d2  = (float)($row->closing_balance ?? 0) - (float)($row->previous_balance ?? 0);
                    $vcr = max(0.0, $d2); $vdr = max(0.0, -$d2);
                }
                $dr = $vdr; $cr = $vcr;
                $runningBalance += ($dr - $cr);
            }

            $totalDebit  += $dr;
            $totalCredit += $cr;

            $ref = '-';
            if (preg_match('/#\s*(sin|srn|grn|inv|pv|sr|so|po|jv|rv|re|pr|prtn|rvid)[- ]?([A-Z0-9\-_]+)/i', $desc, $m)) {
                $ref = $m[0];
            }

            $type = 'journal';
            if (str_contains($ldesc, 'sale') || str_contains($ldesc, 'srn'))     $type = 'sale';
            if (str_contains($ldesc, 'purchase') || str_contains($ldesc, 'grn')) $type = 'purchase';
            if (str_contains($ldesc, 'payment') || str_contains($ldesc, 'receipt') || str_contains($ldesc, 'rvid') || str_contains($ldesc, 'pvid')) $type = 'receipt';
            if (str_contains($ldesc, 'return') || str_contains($ldesc, 'prtn'))  $type = 'return';

            $transactions[] = [
                'date'        => \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'),
                'invoice'     => $ref !== '-' ? strtoupper(str_replace('#', '', trim($ref))) : '-',
                'description' => ($row->_src === 'vendor' ? '[Purchase] ' : '') . $desc,
                'type'        => $type,
                'debit'       => round($dr, 2),
                'credit'      => round($cr, 2),
                'balance'     => round($runningBalance, 2),
            ];
        }

        return response()->json([
            'customer' => [
                'id'               => $customer->id,
                'customer_id'      => $customer->customer_id   ?? '-',
                'customer_name'    => $customer->customer_name,
                'mobile'           => $customer->mobile        ?? '-',
                'address'          => $customer->address       ?? '-',
                'customer_type'    => $customer->customer_type ?? '-',
                'opening_balance'  => $customer->opening_balance ?? 0,
                'has_twin_vendor'  => $twinVendor ? true : false,
                'twin_vendor_name' => optional($twinVendor)->name,
            ],
            'opening_balance' => round($netOpeningBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'total_debit'     => round($totalDebit, 2),
            'total_credit'    => round($totalCredit, 2),
            'transactions'    => $transactions,
            'report_period'   => \Carbon\Carbon::parse($start)->format('d/m/Y') . " to " . \Carbon\Carbon::parse($end)->format('d/m/Y'),
        ]);
    }
    public function profitLoss(Request $request)
    {
        $start = $request->start_date ?? now()->startOfMonth()->toDateString();
        $end   = $request->end_date   ?? now()->toDateString();

        $branchId = $this->getBranchId();

        // ══════════════════════════════════════════════════════════════════
        // 1. REVENUE  (what we charged customers)
        // ══════════════════════════════════════════════════════════════════
        $salesRevenueQry = DB::table('sales')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->where('sale_status', '!=', 'returned');
        if ($branchId) {
            $salesRevenueQry->where('branch_id', $branchId);
        }
        $salesRevenue = (float) $salesRevenueQry->sum('total_net');

        $saleReturnsQry = DB::table('sale_returns')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
        if ($branchId) {
            $saleReturnsQry->where('branch_id', $branchId);
        }
        $saleReturns = (float) $saleReturnsQry->sum('net_amount');

        $netRevenue = max(0, $salesRevenue - $saleReturns);

        // ══════════════════════════════════════════════════════════════════
        // 2. COST OF GOODS SOLD — CORRECT METHOD
        //    COGS = actual pieces sold × purchase price per piece
        //    (NOT total purchases — unsold stock is NOT an expense!)
        //
        //    Example the user described:
        //      You bought 10 items at 1000 each (total purchase = 10,000)
        //      You sold only 3 items in this period
        //      COGS = 3 × 1000 = 3,000   ← correct
        //      Remaining 7 items are still inventory — not counted here
        // ══════════════════════════════════════════════════════════════════
        $cogsRowsQry = DB::table('sale_items')
            ->join('sales',    'sale_items.sale_id',    '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end])
            ->where('sales.sale_status', '!=', 'returned');
        
        if ($branchId) {
            $cogsRowsQry->where('sales.branch_id', $branchId);
        }

        $cogsRows = $cogsRowsQry->selectRaw('
                sale_items.product_id,
                products.item_name,
                products.item_code,
                products.size_mode,
                products.purchase_price_per_piece,
                products.purchase_price_per_box,
                products.pieces_per_box,
                products.price_per_m2,
                products.total_m2,
                SUM(sale_items.total_pieces) as total_pieces_sold,
                SUM(sale_items.qty)          as qty_sold,
                SUM(sale_items.total)        as sale_revenue
            ')
            ->groupBy(
                'sale_items.product_id',
                'products.item_name',
                'products.item_code',
                'products.size_mode',
                'products.purchase_price_per_piece',
                'products.purchase_price_per_box',
                'products.pieces_per_box',
                'products.price_per_m2',
                'products.total_m2'
            )
            ->get();

        // Compute COGS per product line based on size_mode
        $cogsPerProduct = [];
        $totalCOGS = 0;

        foreach ($cogsRows as $row) {
            $ppp   = (float) ($row->purchase_price_per_piece ?? 0);
            $ppb   = (float) ($row->purchase_price_per_box   ?? 0);
            $pcBox = max(1, (int) ($row->pieces_per_box       ?? 1));
            $pm2   = (float) ($row->price_per_m2              ?? 0);
            $m2Box = (float) ($row->total_m2                  ?? 0);
            $piecesS = (float) ($row->total_pieces_sold ?? 0);
            $qtyS    = (float) ($row->qty_sold          ?? 0);
            $sizeMode = $row->size_mode ?? 'by_piece';

            // Derive cost for the pieces sold
            switch ($sizeMode) {
                case 'by_size':
                    // by_size: qty = boxes sold, total_pieces = pieces sold
                    $cost = $pm2 > 0
                        ? $qtyS * $m2Box * $pm2          // boxes × m2/box × price/m2
                        : $piecesS * ($ppp ?: $ppb / $pcBox);
                    break;

                case 'by_cartons':
                case 'by_carton':
                    // qty = cartons (boxes), total_pieces includes all pieces
                    $boxes = floor($piecesS / $pcBox);
                    $loose = fmod($piecesS, $pcBox);
                    $cost  = ($boxes * $ppb) + ($loose * ($ppp ?: $ppb / $pcBox));
                    break;

                default: // by_piece / by_pieces
                    $costPerPiece = $ppp ?: ($ppb / $pcBox);
                    $cost = $piecesS > 0 ? $piecesS * $costPerPiece : $qtyS * $costPerPiece;
                    break;
            }

            $cost = round($cost, 2);
            $totalCOGS += $cost;

            $cogsPerProduct[] = [
                'item_code'    => $row->item_code,
                'item_name'    => $row->item_name,
                'size_mode'    => $sizeMode,
                'pieces_sold'  => $piecesS ?: $qtyS,
                'sale_revenue' => round((float) $row->sale_revenue, 2),
                'cogs'         => $cost,
                'gross_margin' => round((float) $row->sale_revenue, 2) - $cost,
            ];
        }

        // COGS = purely the cost of items actually sold. No deductions.
        // Purchase returns are a separate event — they don't change what was sold.
        $netCOGS = $totalCOGS;

        // Capture total purchases this period (for the detail breakdown — informational)
        $totalPurchasedQry = DB::table('purchases')
            ->whereBetween('purchase_date', [$start, $end])
            ->where('status_purchase', 'post');
        if ($branchId) {
            $totalPurchasedQry->where('branch_id', $branchId);
        }
        $totalPurchasedThisPeriod = (float) $totalPurchasedQry->sum('net_amount');
        $purchasesThisPeriodCount = (int) $totalPurchasedQry->count();

        // Inventory value still in stock (not sold — informational only)
        $inventoryOnHandQry = DB::table('warehouse_stocks')
            ->join('products', 'warehouse_stocks.product_id', '=', 'products.id');
        if ($branchId) {
            $inventoryOnHandQry->where('warehouse_stocks.branch_id', $branchId);
        }
        $inventoryOnHand = (float) $inventoryOnHandQry->selectRaw('SUM(warehouse_stocks.total_pieces * COALESCE(products.purchase_price_per_piece, 0)) as inv_value')
            ->value('inv_value');


        // ══════════════════════════════════════════════════════════════════
        // 3. GROSS PROFIT  =  Net Revenue − COGS
        // ══════════════════════════════════════════════════════════════════
        $grossProfit       = $netRevenue - $netCOGS;
        $grossProfitMargin = $netRevenue > 0 ? round(($grossProfit / $netRevenue) * 100, 2) : 0;

        // ══════════════════════════════════════════════════════════════════
        // 4. OPERATING EXPENSES
        //    4a. Purchase Expensive (extra_cost field on purchases)
        //    4b. Manual expense vouchers
        // ══════════════════════════════════════════════════════════════════
        $purchaseExpenses = (float) DB::table('purchases')
            ->whereBetween('purchase_date', [$start, $end])
            ->where('status_purchase', 'post')
            ->sum('extra_cost');

        $otherExpenses = (float) DB::table('expense_vouchers')
            ->whereBetween(DB::raw('DATE(entry_date)'), [$start, $end])
            ->whereRaw("remarks NOT LIKE '%Auto: Purchase Expensive%'")
            ->sum('total_amount');

        $totalOperatingExpenses = $purchaseExpenses + $otherExpenses;

        // ══════════════════════════════════════════════════════════════════
        // 5. NET PROFIT  =  Gross Profit − Operating Expenses
        // ══════════════════════════════════════════════════════════════════
        $netProfit       = $grossProfit - $totalOperatingExpenses;
        $netProfitMargin = $netRevenue > 0 ? round(($netProfit / $netRevenue) * 100, 2) : 0;

        // ══════════════════════════════════════════════════════════════════
        // 6. DETAIL BREAKDOWNS
        // ══════════════════════════════════════════════════════════════════

        // Sales by period
        $daysDiff    = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));
        $groupFormat = $daysDiff > 60 ? '%Y-%m' : '%Y-%m-%d';
        $groupLabel  = $daysDiff > 60 ? 'Month' : 'Date';

        $salesByPeriodQry = DB::table('sales')
            ->whereBetween(DB::raw('DATE(created_at)'), [$start, $end])
            ->where('sale_status', '!=', 'returned');
        if ($branchId) {
            $salesByPeriodQry->where('branch_id', $branchId);
        }
        $salesByPeriod = $salesByPeriodQry->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as period,
                         COUNT(*) as txn_count,
                         COALESCE(SUM(total_bill_amount), 0) as subtotal,
                         COALESCE(SUM(total_extradiscount), 0) as discount,
                         COALESCE(SUM(total_net), 0) as net_revenue")
            ->groupByRaw("DATE_FORMAT(created_at, '{$groupFormat}')")
            ->orderBy('period')
            ->get();

        // Top products (by sale revenue, including their COGS for margin calc)
        $topProducts = collect($cogsPerProduct)
            ->sortByDesc('sale_revenue')
            ->take(10)
            ->values();

        // Expense voucher breakdown
        $expenseBreakdownQry = DB::table('expense_vouchers')
            ->whereBetween(DB::raw('DATE(entry_date)'), [$start, $end]);
        if ($branchId) {
            $expenseBreakdownQry->where('branch_id', $branchId);
        }
        $expenseBreakdown = $expenseBreakdownQry->selectRaw('remarks, entry_date, total_amount, evid')
            ->orderByDesc('entry_date')
            ->limit(50)
            ->get();

        // Purchase breakdown
        $purchaseBreakdownQry = DB::table('purchases')
            ->join('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->whereBetween('purchases.purchase_date', [$start, $end])
            ->where('purchases.status_purchase', 'post');
        if ($branchId) {
            $purchaseBreakdownQry->where('purchases.branch_id', $branchId);
        }
        $purchaseBreakdown = $purchaseBreakdownQry->selectRaw('purchases.invoice_no, purchases.purchase_date, vendors.name as vendor_name,
                         purchases.subtotal, purchases.discount, purchases.extra_cost, purchases.net_amount')
            ->orderByDesc('purchases.purchase_date')
            ->limit(50)
            ->get();

        return view('admin_panel.reporting.profit_loss', compact(
            'start', 'end',
            'salesRevenue', 'saleReturns', 'netRevenue',
            'totalCOGS', 'netCOGS',
            'grossProfit', 'grossProfitMargin',
            'purchaseExpenses', 'otherExpenses', 'totalOperatingExpenses',
            'netProfit', 'netProfitMargin',
            'cogsPerProduct',
            'totalPurchasedThisPeriod', 'purchasesThisPeriodCount', 'inventoryOnHand',
            'salesByPeriod', 'groupLabel',
            'topProducts', 'expenseBreakdown', 'purchaseBreakdown'
        ));
    }

    public function warehouse_report()
    {
        $branchId = $this->getBranchId();
        $warehouses = DB::table('warehouses')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();
        return view('admin_panel.reporting.warehouse_report', compact('warehouses'));
    }

    public function fetchWarehouseReport(Request $request)
    {
        try {
            $branchId = $this->getBranchId();
            $warehouseId = $request->warehouse_id;
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $catId   = $request->category_id;
            $subId   = $request->sub_category_id;
            $brandId = $request->brand_id;
            $prodId  = $request->product_id;

            // 1. Current stock in selected warehouse
            $stocksQuery = DB::table('warehouse_stocks')
                ->join('products', 'products.id', '=', 'warehouse_stocks.product_id')
                ->leftJoin('product_uoms', 'product_uoms.id', '=', 'warehouse_stocks.uom_id');

            if ($catId && $catId !== 'all')   $stocksQuery->where('products.category_id', $catId);
            if ($subId && $subId !== 'all')   $stocksQuery->where('products.sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $stocksQuery->where('products.brand_id', $brandId);
            if ($prodId && $prodId !== 'all')   $stocksQuery->where('products.id', $prodId);

            if ($branchId) {
                $stocksQuery->where('warehouse_stocks.branch_id', $branchId);
            }

            if ($warehouseId && $warehouseId !== 'all') {
                $stocksQuery->where('warehouse_stocks.warehouse_id', $warehouseId);
            }

            $stocksResult = $stocksQuery
                ->select(
                    'products.id',
                    'products.item_code',
                    'products.item_name',
                    'products.size_mode',
                    'products.pieces_per_box',
                    'products.purchase_price_per_piece',
                    'products.purchase_price_per_box',
                    'products.sale_price_per_piece',
                    'products.sale_price_per_box',
                    'warehouse_stocks.uom_id',
                    'product_uoms.name as uom_name',
                    'product_uoms.pieces_per_box as uom_ppb',
                    'warehouse_stocks.quantity as boxes',
                    'warehouse_stocks.total_pieces as pieces'
                )
                ->get();
                
            $stocksGrouped = $stocksResult->groupBy('id');

            // 2. Purchases linked to this warehouse
            $purchasesQuery = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->join('products', 'products.id', '=', 'purchase_items.product_id')
                ->where('purchases.status_purchase', '!=', 'cancelled');
            
            if ($catId && $catId !== 'all')   $purchasesQuery->where('products.category_id', $catId);
            if ($subId && $subId !== 'all')   $purchasesQuery->where('products.sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $purchasesQuery->where('products.brand_id', $brandId);
            if ($prodId && $prodId !== 'all')   $purchasesQuery->where('products.id', $prodId);
            
            if ($branchId) {
                $purchasesQuery->where('purchases.branch_id', $branchId);
            }

            if ($warehouseId && $warehouseId !== 'all') {
                $purchasesQuery->where('purchases.warehouse_id', $warehouseId);
            }
                
            if ($startDate && $endDate) {
                $purchasesQuery->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
            }
            
            $purchases = $purchasesQuery->select(
                'purchase_items.product_id',
                DB::raw('SUM(purchase_items.qty) as total_qty'),
                DB::raw('SUM(purchase_items.line_total) as total_amount')
            )->groupBy('purchase_items.product_id')->get()->keyBy('product_id');

            // 3. Sales linked to this warehouse
            $salesQuery = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.sale_status', 'completed');

            if ($catId && $catId !== 'all')   $salesQuery->where('products.category_id', $catId);
            if ($subId && $subId !== 'all')   $salesQuery->where('products.sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $salesQuery->where('products.brand_id', $brandId);
            if ($prodId && $prodId !== 'all')   $salesQuery->where('products.id', $prodId);
                
            if ($branchId) {
                $salesQuery->where('sales.branch_id', $branchId);
            }

            if ($warehouseId && $warehouseId !== 'all') {
                $salesQuery->where('sale_items.warehouse_id', $warehouseId);
            }
                
            if ($startDate && $endDate) {
                $salesQuery->whereBetween(DB::raw('DATE(sales.created_at)'), [$startDate, $endDate]);
            }

            $sales = $salesQuery->select(
                'sale_items.product_id',
                DB::raw('SUM(sale_items.qty) as total_qty'),
                DB::raw('SUM(sale_items.total) as total_amount')
            )->groupBy('sale_items.product_id')->get()->keyBy('product_id');
            
            // 4. Stock transfers in
            $transfersInQuery = DB::table('stock_transfers')
                ->join('products', 'products.id', '=', 'stock_transfers.product_id');
            
            if ($catId && $catId !== 'all')   $transfersInQuery->where('products.category_id', $catId);
            if ($subId && $subId !== 'all')   $transfersInQuery->where('products.sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $transfersInQuery->where('products.brand_id', $brandId);
            if ($prodId && $prodId !== 'all')   $transfersInQuery->where('products.id', $prodId);
            if ($branchId) { $transfersInQuery->where('branch_id', $branchId); }
            if ($warehouseId && $warehouseId !== 'all') { $transfersInQuery->where('to_warehouse_id', $warehouseId); }
            if ($startDate && $endDate) { $transfersInQuery->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]); }
            $transfersIn = $transfersInQuery->select('product_id', DB::raw('SUM(quantity) as total_pieces'))->groupBy('product_id')->get()->keyBy('product_id');

            // 5. Stock transfers out
            $transfersOutQuery = DB::table('stock_transfers')
                ->join('products', 'products.id', '=', 'stock_transfers.product_id');

            if ($catId && $catId !== 'all')   $transfersOutQuery->where('products.category_id', $catId);
            if ($subId && $subId !== 'all')   $transfersOutQuery->where('products.sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $transfersOutQuery->where('products.brand_id', $brandId);
            if ($prodId && $prodId !== 'all')   $transfersOutQuery->where('products.id', $prodId);
            if ($branchId) { $transfersOutQuery->where('branch_id', $branchId); }
            if ($warehouseId && $warehouseId !== 'all') { $transfersOutQuery->where('from_warehouse_id', $warehouseId); }
            if ($startDate && $endDate) { $transfersOutQuery->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]); }
            $transfersOut = $transfersOutQuery->select('product_id', DB::raw('SUM(quantity) as total_pieces'))->groupBy('product_id')->get()->keyBy('product_id');

            // Gather all product IDs
            $allProductIds = collect([])
                ->merge($stocksGrouped->keys())
                ->merge($purchases->keys())
                ->merge($sales->keys())
                ->merge($transfersIn->keys())
                ->merge($transfersOut->keys())
                ->unique()->values();

            $allProducts = DB::table('products')->whereIn('id', $allProductIds)->get()->keyBy('id');
            $allProductUoms = DB::table('product_uoms')->whereIn('product_id', $allProductIds)->get()->groupBy('product_id');

            $rows = [];
            $summary = [
                'total_stock_qty' => 0,
                'total_stock_value' => 0,
                'period_purchases_qty' => 0,
                'period_purchases_value' => 0,
                'period_sales_qty' => 0,
                'period_sales_value' => 0,
                'period_transfers_in' => 0,
                'period_transfers_out' => 0,
            ];

            foreach ($allProductIds as $pid) {
                $prod = $allProducts->get($pid);
                if (!$prod) continue;
                
                $stkRows = $stocksGrouped->get($pid, collect([]));
                // Valuation & Breakdown
                $uomParts = [];
                $costValue = 0;
                $currentStockPieces = $stkRows->sum('pieces');
                
                foreach ($stkRows as $row) {
                    $uomPieces = (float)$row->pieces;
                    if ($uomPieces > 0) {
                        $uomName = $row->uom_name ?: 'pcs';
                        $uomParts[] = $uomPieces . ' ' . $uomName;
                        
                        // Use UOM's specific pricing if available, else fallback to piece price
                        $uomPrice = DB::table('product_uoms')->where('id', $row->uom_id)->value('purchase_price');
                        if ($uomPrice > 0) {
                            $uomPpb = max(1, (int)$row->uom_ppb);
                            $costValue += ($uomPieces / $uomPpb) * $uomPrice;
                        } else {
                            $ppb = max(1, (int) ($prod->pieces_per_box ?? 1));
                            $fallbackPrice = (float)($prod->purchase_price_per_piece ?? ($prod->purchase_price_per_box / $ppb));
                            $costValue += $uomPieces * $fallbackPrice;
                        }
                    }
                }
                $ppb = max(1, (int) ($prod->pieces_per_box ?? 1));
                $boxes = floor($currentStockPieces / $ppb);
                $loose = $currentStockPieces % $ppb;
                $stockDisplay = $boxes . '.' . $loose;
                
                // If there's an actual UOM breakdown, we can still show it as a tooltip or secondary info in the frontend
                // for now we set current_stock_display to the dot notation as primary.

                // Summaries for this row
                $purRow = $purchases->get($pid);
                $salRow = $sales->get($pid);
                $tinRow = $transfersIn->get($pid);
                $toutRow = $transfersOut->get($pid);

                $rows[] = [
                    'item_code' => $prod->item_code,
                    'item_name' => $prod->item_name,
                    'current_stock' => $currentStockPieces,
                    'current_stock_display' => $stockDisplay,
                    'stock_value' => $costValue,
                    'purchased_qty' => $purRow ? (float)$purRow->total_qty : 0,
                    'purchased_amount' => $purRow ? (float)$purRow->total_amount : 0,
                    'sold_qty' => $salRow ? (float)$salRow->total_qty : 0,
                    'sold_amount' => $salRow ? (float)$salRow->total_amount : 0,
                    'transferred_in' => $tinRow ? (int)$tinRow->total_pieces : 0,
                    'transferred_out' => $toutRow ? (int)$toutRow->total_pieces : 0,
                    'packings' => $allProductUoms->get($pid, collect([]))->values()->map(fn($u) => ['name' => $u->name, 'pieces_per_box' => (int)$u->pieces_per_box])->all(),
                ];

                $summary['total_stock_qty'] += $currentStockPieces;
                $summary['total_stock_value'] += $costValue;
                $summary['period_purchases_value'] += ($purRow ? (float)$purRow->total_amount : 0);
                $summary['period_sales_value'] += ($salRow ? (float)$salRow->total_amount : 0);
            }

            return response()->json([
                'data' => collect($rows)->sortBy('item_name')->values()->all(),
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    //  VENDOR LEDGER REPORT
    // ─────────────────────────────────────────────────────────────────────
    public function vendor_ledger_report()
    {
        $branchId  = $this->getBranchId();
        $vendors = DB::table('vendors')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('admin_panel.reporting.vendor_ledger_report', compact('vendors'));
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $vendorId  = (int) $request->vendor_id;
        $start     = $request->start_date;
        $end       = $request->end_date;
        $catId     = $request->category_id;
        $subId     = $request->sub_category_id;
        $brandId   = $request->brand_id;
        $productId = $request->product_id;
        $branchId  = $this->getBranchId();

        $vendor = DB::table('vendors')
            ->where('id', $vendorId)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->first();

        if (!$vendor || !$start || !$end) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        // ── 1. Detect Twin Customer (MUST happen first) ───────────────────────
        $twinCustomer = null;
        $vendCode = trim($vendor->vendor_code ?? '');
        $vendCnic = trim($vendor->cnic ?? '');
        if ($vendCode !== '' || $vendCnic !== '') {
            $twinCustomer = DB::table('customers')
                ->where(function($q) use ($vendCode, $vendCnic) {
                    if ($vendCode !== '') $q->where('customer_id', $vendCode);
                    if ($vendCnic !== '') $q->orWhere(function($q2) use ($vendCnic) {
                        $q2->where('cnic', $vendCnic)->whereNotNull('cnic')->where('cnic', '!=', '');
                    });
                })->first();
        }

        // ── 2. Opening balance ─────────────────────────────────────────────────
        $lastVendEntry = DB::table('vendor_ledgers')
            ->where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<', $start)
            ->orderByDesc('created_at')->orderByDesc('id')->first();
        $openingBalance = $lastVendEntry
            ? (float)$lastVendEntry->closing_balance
            : (float)($vendor->opening_balance ?? 0);

        $customerOpeningBalance = 0;
        if ($twinCustomer) {
            $lastCustEntry = DB::table('customer_ledgers')
                ->where('customer_id', $twinCustomer->id)
                ->whereDate('created_at', '<', $start)
                ->orderByDesc('created_at')->orderByDesc('id')->first();
            $customerOpeningBalance = $lastCustEntry
                ? (float)$lastCustEntry->closing_balance
                : (float)($twinCustomer->opening_balance ?? 0);
        }
        $netOpeningBalance = $openingBalance - $customerOpeningBalance;

        // ── 3. Product filter ─────────────────────────────────────────────────
        $hasFilter = ($catId && $catId !== 'all') || ($subId && $subId !== 'all')
                  || ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all');

        $allowPurchases = null;
        $allowSales     = null;

        if ($hasFilter) {
            $baseFilters = function($q) use ($catId, $subId, $brandId, $productId) {
                if ($catId && $catId !== 'all')    $q->where('products.category_id', $catId);
                if ($subId && $subId !== 'all')    $q->where('products.sub_category_id', $subId);
                if ($brandId && $brandId !== 'all') $q->where('products.brand_id', $brandId);
                if ($productId && $productId !== 'all') $q->where('products.id', $productId);
            };

            // Purchases matching this vendor + filter
            $pInv = DB::table('purchase_items')
                ->join('products', 'products.id', '=', 'purchase_items.product_id')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->where('purchases.vendor_id', $vendorId)
                ->tap($baseFilters)->pluck('purchases.invoice_no')
                ->map(fn($v) => strtolower($v));

            $prInv = DB::table('purchase_return_items')
                ->join('products', 'products.id', '=', 'purchase_return_items.product_id')
                ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                ->where('purchase_returns.vendor_id', $vendorId)
                ->tap($baseFilters)->pluck('purchase_returns.return_invoice')
                ->map(fn($v) => strtolower($v));

            $allowPurchases = $pInv->concat($prInv)->unique()->values()->toArray();

            // Sales matching twin customer + filter (only if twin exists)
            if ($twinCustomer) {
                $sInv = DB::table('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.customer_id', $twinCustomer->id)
                    ->tap($baseFilters)->pluck('sales.invoice_no')
                    ->map(fn($v) => strtolower($v));

                $srInv = DB::table('sale_return_items')
                    ->join('products', 'products.id', '=', 'sale_return_items.product_id')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->where('sale_returns.customer_id', $twinCustomer->id)
                    ->tap($baseFilters)->pluck('sale_returns.return_invoice')
                    ->map(fn($v) => strtolower($v));

                $allowSales = $sInv->concat($srInv)->unique()->values()->toArray();
            } else {
                $allowSales = [];
            }
        }

        // ── 4. Fetch period entries ───────────────────────────────────────────
        $vendEntries = DB::table('vendor_ledgers')
            ->where('vendor_id', $vendorId)
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->orderBy('created_at')->orderBy('id')
            ->get()->map(fn($r) => (object)((array)$r + ['_src' => 'vendor']));

        $custEntries = collect();
        if ($twinCustomer) {
            $custEntries = DB::table('customer_ledgers')
                ->where('customer_id', $twinCustomer->id)
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->orderBy('created_at')->orderBy('id')
                ->get()->map(fn($r) => (object)((array)$r + ['_src' => 'customer']));
        }

        $allEntries = $vendEntries->concat($custEntries)
            ->sortBy(fn($r) => $r->created_at . str_pad($r->id, 10, '0', STR_PAD_LEFT));

        // ── 5. Apply filter strictly ──────────────────────────────────────────
        if ($hasFilter) {
            $allEntries = $allEntries->filter(function($row) use ($allowPurchases, $allowSales) {
                $desc = strtolower($row->description ?? '');

                if ($row->_src === 'vendor') {
                    if (empty($allowPurchases)) return false;
                    foreach ($allowPurchases as $inv) {
                        if (preg_match('/\b' . preg_quote($inv, '/') . '\b/', $desc)) return true;
                    }
                    return false;
                } else {
                    if (empty($allowSales)) return false;
                    foreach ($allowSales as $inv) {
                        if (preg_match('/\b' . preg_quote($inv, '/') . '\b/', $desc)) return true;
                    }
                    return false;
                }
            });
        }

        // ── 6. Build running balance ──────────────────────────────────────────
        $runningBalance = $netOpeningBalance;
        $totalDebit = 0; $totalCredit = 0;
        $transactions = [];

        foreach ($allEntries as $row) {
            $desc  = $row->description ?? '';
            $ldesc = strtolower($desc);

            if ($row->_src === 'vendor') {
                $dr = (float)($row->debit  ?? 0);
                $cr = (float)($row->credit ?? 0);
                if ($dr == 0 && $cr == 0) {
                    $d2 = (float)($row->closing_balance ?? 0) - (float)($row->previous_balance ?? 0);
                    $cr = max(0.0, $d2); $dr = max(0.0, -$d2);
                }
                $runningBalance += ($cr - $dr);
            } else {
                $prev  = (float)($row->previous_balance ?? 0);
                $close = (float)($row->closing_balance  ?? 0);
                $diff  = $close - $prev;
                $dr = max(0.0, $diff);
                $cr = max(0.0, -$diff);
                $runningBalance -= $diff;
            }

            $totalDebit  += $dr;
            $totalCredit += $cr;

            $ref = '-';
            if (preg_match('/#\s*(srn|grn|inv|pv|sr|so|po|jv|rv|re|pr|prtn|rvid)[- ]?([A-Z0-9\-_]+)/i', $desc, $m)) {
                $ref = $m[0];
            }

            $type = 'journal';
            if (str_contains($ldesc, 'purchase') || str_contains($ldesc, 'grn'))  $type = 'purchase';
            if (str_contains($ldesc, 'sale') || str_contains($ldesc, 'srn'))      $type = 'sale';
            if (str_contains($ldesc, 'payment') || str_contains($ldesc, 'receipt') || str_contains($ldesc, 'rvid') || str_contains($ldesc, 'pvid')) $type = 'receipt';
            if (str_contains($ldesc, 'return') || str_contains($ldesc, 'prtn'))   $type = 'return';

            $transactions[] = [
                'date'        => \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'),
                'invoice'     => $ref !== '-' ? strtoupper(str_replace('#', '', trim($ref))) : '-',
                'description' => ($row->_src === 'customer' ? '[Sale] ' : '') . $desc,
                'type'        => $type,
                'debit'       => round($dr, 2),
                'credit'      => round($cr, 2),
                'balance'     => round($runningBalance, 2),
            ];
        }

        return response()->json([
            'vendor' => [
                'id'                => $vendor->id,
                'vendor_id'         => $vendor->vendor_code ?? $vendor->id,
                'name'              => $vendor->name,
                'mobile'            => $vendor->phone   ?? '-',
                'address'           => $vendor->address ?? '-',
                'opening_balance'   => $vendor->opening_balance ?? 0,
                'has_twin_customer' => $twinCustomer ? true : false,
                'twin_customer_name'=> optional($twinCustomer)->customer_name,
            ],
            'opening_balance' => round($netOpeningBalance, 2),
            'closing_balance' => round($runningBalance, 2),
            'total_debit'     => round($totalDebit, 2),
            'total_credit'    => round($totalCredit, 2),
            'transactions'    => $transactions,
            'report_period'   => \Carbon\Carbon::parse($start)->format('d/m/Y') . " to " . \Carbon\Carbon::parse($end)->format('d/m/Y'),
        ]);
    }
    public function fetch_all_vendor_ledgers(Request $request)
    {
        $start = $request->start_date;
        $end   = $request->end_date;
        $branchId = $this->getBranchId();

        if (!$start || !$end) {
            return response()->json(['error' => 'Invalid dates'], 400);
        }

        $vendors = DB::table('vendors')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        // Detect all twin customers
        $twinCustomers = [];
        $custCodes = [];
        $custCnics = [];
        foreach ($vendors as $v) {
            $vendCode = trim($v->vendor_code ?? '');
            $vendCnic = trim($v->cnic ?? '');
            if ($vendCode !== '') $custCodes[] = $vendCode;
            if ($vendCnic !== '') $custCnics[] = $vendCnic;
        }

        if (!empty($custCodes) || !empty($custCnics)) {
            $customers = DB::table('customers')
                ->whereIn('customer_id', $custCodes)
                ->orWhere(function($q) use ($custCnics) {
                    $q->whereIn('cnic', $custCnics)->whereNotNull('cnic')->where('cnic', '!=', '');
                })
                ->get();
                
            foreach ($vendors as $v) {
                $vendCode = trim($v->vendor_code ?? '');
                $vendCnic = trim($v->cnic ?? '');
                $twin = $customers->first(function($c) use ($vendCode, $vendCnic) {
                    return ($vendCode !== '' && $c->customer_id === $vendCode) ||
                           ($vendCnic !== '' && $c->cnic === $vendCnic);
                });
                if ($twin) {
                    $twinCustomers[$v->id] = $twin;
                }
            }
        }

        $allLastEntries = DB::table('vendor_ledgers')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '<', $start)
            ->whereIn('vendor_id', $vendors->pluck('id'))
            ->whereIn('id', function($query) use ($start) {
                $query->select(DB::raw('MAX(id)'))
                      ->from('vendor_ledgers')
                      ->whereNull('deleted_at')
                      ->whereDate('created_at', '<', $start)
                      ->groupBy('vendor_id');
            })
            ->get()
            ->keyBy('vendor_id');

        $allPeriodEntries = DB::table('vendor_ledgers')
            ->whereNull('deleted_at')
            ->whereDate('created_at', '>=', $start)
            ->whereDate('created_at', '<=', $end)
            ->whereIn('vendor_id', $vendors->pluck('id'))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('vendor_id');

        // Fetch twin customer ledgers
        $twinCustIds = collect($twinCustomers)->pluck('id')->unique()->toArray();
        $allCustLastEntries = collect();
        $allCustPeriodEntries = collect();
        
        if (!empty($twinCustIds)) {
            $allCustLastEntries = DB::table('customer_ledgers')
                ->whereDate('created_at', '<', $start)
                ->whereIn('customer_id', $twinCustIds)
                ->whereIn('id', function($query) use ($start) {
                    $query->select(DB::raw('MAX(id)'))
                          ->from('customer_ledgers')
                          ->whereDate('created_at', '<', $start)
                          ->groupBy('customer_id');
                })
                ->get()
                ->keyBy('customer_id');

            $allCustPeriodEntries = DB::table('customer_ledgers')
                ->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end)
                ->whereIn('customer_id', $twinCustIds)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('customer_id');
        }

        $allData = [];

        foreach ($vendors as $v) {
            $lastEntry = $allLastEntries->get($v->id);
            $openingBalance = $lastEntry ? (float)$lastEntry->closing_balance : (float)($v->opening_balance ?? 0);
            
            $customerOpeningBalance = 0;
            $twinCust = $twinCustomers[$v->id] ?? null;
            if ($twinCust) {
                $lastCustEntry = $allCustLastEntries->get($twinCust->id);
                $customerOpeningBalance = $lastCustEntry ? (float)$lastCustEntry->closing_balance : (float)($twinCust->opening_balance ?? 0);
            }
            $netOpeningBalance = $openingBalance - $customerOpeningBalance;

            $vendEntries = $allPeriodEntries->get($v->id, collect())->map(fn($r) => (object)((array)$r + ['_src' => 'vendor']));
            $custEntries = collect();
            if ($twinCust) {
                $custEntries = $allCustPeriodEntries->get($twinCust->id, collect())->map(fn($r) => (object)((array)$r + ['_src' => 'customer']));
            }

            $allEntries = $vendEntries->concat($custEntries)->sortBy(fn($r) => $r->created_at . str_pad($r->id, 10, '0', STR_PAD_LEFT));

            $runningBalance = $netOpeningBalance;
            $totalDebit = 0;
            $totalCredit = 0;
            $transactions = [];

            foreach ($allEntries as $row) {
                $desc = $row->description ?? '';
                
                if ($row->_src === 'vendor') {
                    $dr = (float)($row->debit ?? 0);
                    $cr = (float)($row->credit ?? 0);
                    if ($dr == 0 && $cr == 0) {
                        $prev  = (float) ($row->previous_balance ?? 0);
                        $close = (float) ($row->closing_balance  ?? 0);
                        $d2  = $close - $prev;
                        $cr = max(0.0, $d2); $dr = max(0.0, -$d2);
                    }
                    $runningBalance += ($cr - $dr);
                } else {
                    $prev  = (float)($row->previous_balance ?? 0);
                    $close = (float)($row->closing_balance  ?? 0);
                    $diff  = $close - $prev;
                    $dr = max(0.0, $diff);
                    $cr = max(0.0, -$diff);
                    $runningBalance -= $diff;
                }
                
                $totalDebit  += $dr;
                $totalCredit += $cr;

                $ref = '-';
                if (preg_match('/#\s*(srn|grn|inv|pv|sr|so|po|jv|rv|re|pr|prtn|rvid)[- ]?([A-Z0-9\-_]+)/i', $desc, $m)) {
                    $ref = $m[0];
                }

                $transactions[] = [
                    'date'        => \Carbon\Carbon::parse($row->created_at)->format('d/m/Y'),
                    'invoice'     => $ref !== '-' ? strtoupper(str_replace('#', '', trim($ref))) : '-',
                    'description' => ($row->_src === 'customer' ? '[Sale] ' : '') . $desc,
                    'debit'       => round($dr, 2),
                    'credit'      => round($cr, 2),
                    'balance'     => round($runningBalance, 2),
                ];
            }

            if ($netOpeningBalance != 0 || count($transactions) > 0) {
                $allData[] = [
                    'vendor' => [
                        'id'   => $v->id,
                        'name' => $v->name . ($twinCust ? ' (Twin Customer)' : ''),
                    ],
                    'opening_balance' => round($netOpeningBalance, 2),
                    'closing_balance' => round($runningBalance, 2),
                    'total_debit'     => round($totalDebit, 2),
                    'total_credit'    => round($totalCredit, 2),
                    'transactions'    => $transactions,
                ];
            }
        }


        return response()->json([
            'all_data' => $allData,
            'period'   => \Carbon\Carbon::parse($start)->format('d/m/Y') . " to " . \Carbon\Carbon::parse($end)->format('d/m/Y'),
            'date'     => now()->format('d/m/Y')
        ]);
    }

    /**
     * ── GLOBAL SUMMARY REPORT ──
     * Returns a high-level overview of Stock, Sales, and Purchases in a single view
     */
    public function globalSummary()
    {
        $products    = Product::orderBy('item_name')->get();
        $categories  = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands      = \App\Models\Brand::orderBy('name')->get();
        $warehouses  = Warehouse::orderBy('warehouse_name')->get();
        
        $isSuperAdmin = $this->isSuperAdmin();
        $branches    = $isSuperAdmin
            ? DB::table('branches')->select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('admin_panel.reporting.global_summary',
            compact('products', 'isSuperAdmin', 'branches', 'categories', 'subCategories', 'brands', 'warehouses'));
    }

    public function fetchGlobalSummary(Request $request)
    {
        try {
            $start = trim($request->start_date ?? '');
            $end   = trim($request->end_date ?? '');
            $catId = trim($request->category_id ?? '');
            $subId = trim($request->sub_category_id ?? '');
            $brandId = trim($request->brand_id ?? '');
            $warehouseId = trim($request->warehouse_id ?? '');
            $productId = trim($request->product_id ?? '');
            $branchId = trim($request->branch_id ?? $this->getBranchId());

            // 1. Build Product Query
            $prodQ = Product::query();
            if ($catId && $catId !== 'all') $prodQ->where('category_id', $catId);
            if ($subId && $subId !== 'all') $prodQ->where('sub_category_id', $subId);
            if ($brandId && $brandId !== 'all') $prodQ->where('brand_id', $brandId);
            if ($productId && $productId !== 'all') $prodQ->where('id', $productId);

            $products = $prodQ->select('id', 'item_code', 'item_name', 'pieces_per_box', 'purchase_price_per_piece', 'retail_price')->get();
            $productIds = $products->pluck('id')->toArray();

            if (empty($productIds)) {
                return response()->json(['data' => [], 'summary' => []]);
            }

            // 2. Fetch Aggregated Movements for the period
            $startDt = $start . ' 00:00:00';
            $endDt   = $end   . ' 23:59:59';
            $hasBranchOnMovements = \Schema::hasColumn('stock_movements', 'branch_id');

            $movements = DB::table('stock_movements')
                ->whereIn('product_id', $productIds)
                ->when($branchId && $hasBranchOnMovements, fn($q) => $q->where('branch_id', $branchId))
                ->select(
                    'product_id',
                    DB::raw("SUM(CASE WHEN created_at < '$startDt' THEN qty ELSE 0 END) as opening"),
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND qty > 0 AND ref_type IN ('PURCHASE', 'GRN', 'PUR', 'in', 'PURCHASE_ITEM') THEN qty ELSE 0 END) as purchased"),
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND qty < 0 AND ref_type IN ('PR', 'purchase_return', 'PURCHASE_RETURN') THEN ABS(qty) ELSE 0 END) as pur_return"),
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND qty < 0 AND ref_type IN ('sale', 'SALE', 'SIN', 'sale_in', 'out', 'DC', 'DELIVERY_NOTE', 'delivery_note') THEN ABS(qty) ELSE 0 END) as sold"),
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND qty > 0 AND ref_type IN ('SR', 'sale_return', 'SALE_RETURN', 'SRN') THEN qty ELSE 0 END) as sale_return"),
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type IN ('INIT', 'OPENING', 'ADJ', 'adjustment') THEN qty ELSE 0 END) as adjusted")
                )
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            // 3. Fetch Transactional Values (Purchases/Sales)
            // Note: This is an approximation based on period items
            $purchSum = DB::table('purchase_items')
                ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                ->whereIn('purchase_items.product_id', $productIds)
                ->whereBetween('purchases.purchase_date', [$start, $end])
                ->when($branchId, fn($q) => $q->where('purchases.branch_id', $branchId))
                ->when($warehouseId && $warehouseId !== 'all', fn($q) => $q->where('purchase_items.warehouse_id', $warehouseId))
                ->select('purchase_items.product_id', DB::raw('SUM(line_total) as amount'))
                ->groupBy('purchase_items.product_id')
                ->get()->keyBy('product_id');

            $saleSum = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sale_items.product_id', $productIds)
                ->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end])
                ->when($branchId, fn($q) => $q->where('sales.branch_id', $branchId))
                ->when($warehouseId && $warehouseId !== 'all', fn($q) => $q->where('sale_items.warehouse_id', $warehouseId))
                ->select('sale_items.product_id', DB::raw('SUM(total) as amount'))
                ->groupBy('sale_items.product_id')
                ->get()->keyBy('product_id');

            $rows = [];
            $gOpening = 0; $gPurchased = 0; $gSold = 0; $gClosing = 0; $gPurVal = 0; $gSaleVal = 0;

            foreach ($products as $p) {
                $m = $movements->get($p->id);
                $pVal = (float)($purchSum->get($p->id)->amount ?? 0);
                $sVal = (float)($saleSum->get($p->id)->amount ?? 0);
                
                $opening    = (float)($m->opening ?? 0);
                $purchased  = (float)($m->purchased ?? 0);
                $purRet     = (float)($m->pur_return ?? 0);
                $sold       = (float)($m->sold ?? 0);
                $saleRet    = (float)($m->sale_return ?? 0);
                $adjusted   = (float)($m->adjusted ?? 0);

                // Closing = Initial + (In - Out)
                $closing = $opening + $purchased - $purRet - $sold + $saleRet + $adjusted;

                $rows[] = [
                    'item'           => ($p->item_name ?? '') . ' ' . ($p->brand_name ?? ''),
                    'code'           => $p->item_code,
                    'opening'        => $opening,
                    'purchased'      => $purchased - $purRet,
                    'sold'           => $sold - $saleRet,
                    'adjusted'       => $adjusted,
                    'closing'        => $closing,
                    'purchase_value' => $pVal,
                    'sale_value'     => $sVal,
                    'stock_value'    => $closing * ($p->purchase_price_per_piece ?? 0)
                ];

                $gOpening   += $opening; 
                $gPurchased += ($purchased - $purRet); 
                $gSold      += ($sold - $saleRet); 
                $gClosing   += $closing;
                $gPurVal    += $pVal; 
                $gSaleVal   += $sVal;
            }

            return response()->json([
                'success' => true,
                'data' => $rows,
                'summary' => [
                    'opening' => $gOpening,
                    'purchased' => $gPurchased,
                    'sold' => $gSold,
                    'closing' => $gClosing,
                    'purch_value' => $gPurVal,
                    'sale_value' => $gSaleVal,
                    'total_stock_value' => array_sum(array_column($rows, 'stock_value'))
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function cdr_report()
    {
        $customers = Customer::orderBy('customer_name')->get(['id', 'customer_name', 'title']);
        $accounts  = Account::orderBy('title')->get(['id', 'title', 'account_code']);
        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? DB::table('branches')->get(['id', 'name']) : collect();

        return view('admin_panel.reporting.cdr_report', compact('customers', 'accounts', 'isSuperAdmin', 'branches'));
    }

    public function fetchCdrReport(Request $request)
    {
        try {
            $start = $request->start_date;
            $end   = $request->end_date;
            $customerId = $request->customer_id;
            $accountId  = $request->account_id;
            $status     = $request->status;
            $branchId   = $request->branch_id ?? $this->getBranchId();

            $query = Cdr::with(['customer', 'bankAccount']);

            if ($start && $end) {
                $query->whereBetween('cdr_date', [$start, $end]);
            }
            if ($customerId && $customerId !== 'all') {
                $query->where('customer_id', $customerId);
            }
            if ($accountId && $accountId !== 'all') {
                $query->where('account_id', $accountId);
            }
            if ($status && $status !== 'all') {
                $query->where('status', 'like', $status);
            }
            if ($branchId && $branchId !== 'all') {
                $query->where('branch_id', $branchId);
            }

            $rows = $query->orderBy('cdr_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $rows,
                'summary' => [
                    'total_amount' => $rows->sum('amount'),
                    'count' => $rows->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function price_adjustment_report()
    {
		
        $products = Product::orderBy('item_name')->get(['id', 'item_name', 'item_code']);
        $categories = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();

        return view('admin_panel.reporting.price_adjustment_report', compact('products', 'categories', 'subCategories', 'brands'));
    }

    public function fetchPriceAdjustmentReport(Request $request)
    {
        try {
            $start = $request->start_date;
            $end   = $request->end_date;
            $productId = $request->product_id;
            $type = $request->type; // purchase or sale

            $query = PriceLog::with(['product', 'user']);

            if ($start && $end) {
                $query->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            if ($productId && $productId !== 'all') {
                $query->where('product_id', $productId);
            }
            if ($type && $type !== 'all') {
                $query->where('type', $type);
            }

            // Global Filters
            if ($request->category_id && $request->category_id !== 'all') {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }
            if ($request->sub_category_id && $request->sub_category_id !== 'all') {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('sub_category_id', $request->sub_category_id);
                });
            }
            if ($request->brand_id && $request->brand_id !== 'all') {
                $query->whereHas('product', function($q) use ($request) {
                    $q->where('brand_id', $request->brand_id);
                });
            }

            $rows = $query->orderBy('created_at', 'desc')->get();
            
            $mappedRows = $rows->map(function($r) {
                if ($r->product) {
                    $r->product->item_name = ($r->product->item_name ?? '') . ' ' . ($r->product->brand->name ?? '');
                }
                return $r;
            });

            return response()->json([
                'success' => true,
                'data' => $mappedRows
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function dc_report()
    {
        $customers     = Customer::orderBy('customer_name')->get(['id', 'customer_name']);
        $categories    = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands        = \App\Models\Brand::orderBy('name')->get();
        $products      = Product::orderBy('item_name')->get(['id', 'item_name', 'item_code', 'brand_id', 'category_id', 'sub_category_id']);

        return view('admin_panel.reporting.dc_report', compact('customers', 'categories', 'subCategories', 'brands', 'products'));
    }

    public function fetchDcReport(Request $request)
    {
        try {
            $start      = $request->start_date;
            $end        = $request->end_date;
            $customerId = $request->customer_id;
            $brandId    = $request->brand_id;
            $productId  = $request->product_id;

            $query = DeliveryNote::with([
                'customer',
                'sale',
                'items.warehouse',
                'items.product.brand',
                'items.uom',
                'items.saleItem.uom',
            ]);

            if ($start && $end) {
                $query->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']);
            }
            if ($customerId && $customerId !== 'all') {
                $query->where('customer_id', $customerId);
            }

            // Product / catalogue filters
            if (($request->category_id    && $request->category_id    !== 'all') ||
                ($request->sub_category_id && $request->sub_category_id !== 'all') ||
                ($brandId                  && $brandId                  !== 'all') ||
                ($productId                && $productId                !== 'all')) {

                $query->whereHas('items.product', function ($q) use ($request, $brandId, $productId) {
                    if ($request->category_id && $request->category_id !== 'all') {
                        $q->where('category_id', $request->category_id);
                    }
                    if ($request->sub_category_id && $request->sub_category_id !== 'all') {
                        $q->where('sub_category_id', $request->sub_category_id);
                    }
                    if ($brandId && $brandId !== 'all') {
                        $q->where('brand_id', $brandId);
                    }
                    if ($productId && $productId !== 'all') {
                        $q->where('id', $productId);
                    }
                });
            }

            $rows = $query->orderBy('created_at', 'desc')->get();

            $data = $rows->map(function ($r) {
                $totalPieces = 0;
                $whBreakdown = [];
                $itemsDetail = [];

                foreach ($r->items as $item) {
                    $pcs     = (float) ($item->total_pieces ?? ($item->qty * ($item->product->pieces_per_box ?? 1)));
                    $totalPieces += $pcs;

                    $whName = $item->warehouse->warehouse_name ?? 'Unknown';
                    if (!isset($whBreakdown[$whName])) {
                        $whBreakdown[$whName] = 0;
                    }
                    $whBreakdown[$whName] += $pcs;

                    $prod = $item->product;
                    $brandName = $prod->brand->name ?? null;
                    
                    $itemsDetail[] = [
                        'product_id'   => $item->product_id,
                        'product_name' => ($prod->item_name ?? 'Unknown Product') . ($brandName ? ' ' . $brandName : ''),
                        'item_code'    => $prod->item_code ?? '-',
                        'brand'        => $brandName  ?? '-',
                        'hs_code'      => $prod->hs_code ?? '-',
                        'uom_name'     => $item->uom->name ?? ($item->saleItem->uom->name ?? ($item->saleItem->uom_name ?? '-')),
                        'qty_boxes'    => (float) $item->qty,
                        'qty_pieces'   => $pcs,
                        'free_qty'     => (float) ($item->free_qty ?? 0),
                        'price'        => (float) ($item->price      ?? 0),
                        'line_total'   => (float) ($item->line_total ?? 0),
                        'warehouse'    => $whName,
                    ];
                }

                $whSummary = [];
                foreach ($whBreakdown as $name => $sum) {
                    $whSummary[] = "$name ($sum pcs)";
                }

                return [
                    'id'           => $r->id,
                    'created_at'   => $r->created_at,
                    'dc_no'        => $r->dc_no,
                    'sale'         => $r->sale,
                    'customer'     => $r->customer,
                    'customer_name'=> $r->customer->customer_name ?? 'Walk-in',
                    'customer_phone'=> $r->customer->mobile ?? '-',
                    'total_pieces' => $totalPieces,
                    'warehouses'   => implode(', ', $whSummary),
                    'items_count'  => count($r->items),
                    'items_detail' => $itemsDetail,
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}


