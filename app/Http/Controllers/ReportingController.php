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

    private function buildOnhandData()
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
                DB::raw('0.00 as total_m2'),
                DB::raw('0.00 as price_per_m2'),
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

        return compact('rows', 'summary');
    }

    public function onhand()
    {
        $data = $this->buildOnhandData();
        return view('admin_panel.reporting.onhand', $data);
    }

    public function exportOnhandExcel(Request $request)
    {
        $data = $this->buildOnhandData();
        
        $exportData = [['#', 'Item Code', 'Item Name', 'Brand', 'Unit', 'Display Qty', 'Total Pieces', 'Cost Value', 'Sale Value', 'Stock Status', 'Warehouses']];

        foreach ($data['rows'] as $i => $r) {
            $whDetails = collect($r->warehouses)->map(function($w) {
                return $w['name'] . ': ' . $w['display'];
            })->implode(', ');
            
            $exportData[] = [
                $i + 1,
                $r->item_code,
                $r->item_name,
                $r->brand_name ?? '-',
                $r->unit_name ?? '-',
                $r->display_qty,
                $r->total_pieces,
                $r->cost_value,
                $r->sale_value,
                strtoupper($r->stock_status),
                $whDetails
            ];
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($exportData);
        $filename = 'Onhand_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportOnhandPdf(Request $request)
    {
        $data = $this->buildOnhandData();
        
        $filename = 'Onhand_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .total-row { font-weight:bold; background-color:#e2e8f0; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>INVENTORY ON-HAND REPORT | Generated: ' . now()->format('d M Y, h:i A') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Item Code</th>
                        <th width="20%">Item Name</th>
                        <th width="10%">Brand</th>
                        <th width="15%">Display Qty</th>
                        <th width="10%" class="num">Cost Value</th>
                        <th width="10%" class="num">Sale Value</th>
                        <th width="15%">Warehouses</th>
                    </tr>';
                    
        foreach ($data['rows'] as $i => $r) {
            $whDetails = collect($r->warehouses)->map(function($w) {
                return $w['name'] . ': ' . $w['display'];
            })->implode('<br>');
            
            $html .= '<tr>
                        <td>'.($i+1).'</td>
                        <td>'.$r->item_code.'</td>
                        <td>'.$r->item_name.'</td>
                        <td>'.($r->brand_name ?? '-').'</td>
                        <td>'.$r->display_qty.'</td>
                        <td class="num">'.number_format($r->cost_value, 2).'</td>
                        <td class="num">'.number_format($r->sale_value, 2).'</td>
                        <td>'.$whDetails.'</td>
                      </tr>';
        }
        
        $html .= '<tr class="total-row">
                    <td colspan="5" class="num">Totals</td>
                    <td class="num">'.number_format($data['summary']->cost_value, 2).'</td>
                    <td class="num">'.number_format($data['summary']->sale_value, 2).'</td>
                    <td></td>
                  </tr>';
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    public function item_stock_report()
    {
        Warehouse::ensureShopWarehousesExists();

        $products    = Product::orderBy('item_name')->get();
        $categories  = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands      = \App\Models\Brand::orderBy('name')->get();
        
        $isSuperAdmin = $this->isSuperAdmin();
        $branches    = $isSuperAdmin
            ? DB::table('branches')->select('id', 'name')->orderBy('name')->get()
            : collect();

        $branchId = $this->getBranchId();
        // All locations (shops + warehouses) for the filter dropdown
        $allLocations = Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderByRaw("FIELD(type,'shop','warehouse')")
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name', 'type']);

        return view('admin_panel.reporting.item_stock_report',
            compact('products', 'isSuperAdmin', 'branches', 'categories', 'subCategories', 'brands', 'allLocations'));
    }

    // AJAX endpoint to fetch report rows
    private function buildItemStockData(Request $request)
    {           
        try {
            Warehouse::ensureShopWarehousesExists();

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

            $user = auth()->user();
            if ($user && !$user->isSuperAdmin()) {
                $branchId = $user->getBranchId();
            } else {
                $branchId = ($branchId && $branchId !== 'all') ? (int)$branchId : null;
            }

            // Normalise "all" to null
            $warehouseId = ($warehouseId && $warehouseId !== 'all') ? (int)$warehouseId : null;

            $query->when($productId && $productId !== 'all', function ($q) use ($productId) {
                if (is_array($productId)) {
                    $q->whereIn('id', $productId);
                } elseif (is_string($productId) && strpos($productId, ',') !== false) {
                    $ids = array_filter(array_map('intval', explode(',', $productId)));
                    $q->whereIn('id', $ids);
                } else {
                    $q->where('id', $productId);
                }
            });
            $query->when($catId    && $catId    !== 'all', fn($q) => $q->where('category_id',     $catId));
            $query->when($subId    && $subId    !== 'all', fn($q) => $q->where('sub_category_id', $subId));
            $query->when($brandId  && $brandId  !== 'all', fn($q) => $q->where('brand_id',        $brandId));
            $products = $query->orderBy('item_name')->get();
            $productIds = $products->pluck('id')->toArray();


            // ── 2. Bulk-fetch live warehouse stock ───────────────────────────
            //    (warehouse_stocks is the single source of truth for current balance)
            $whStockQuery = DB::table('warehouse_stocks')
                ->join('warehouses', 'warehouses.id', '=', 'warehouse_stocks.warehouse_id')
                ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
                ->whereIn('warehouse_stocks.product_id', $productIds)
                ->select(
                    'warehouse_stocks.product_id',
                    'warehouse_stocks.warehouse_id',
                    'warehouses.warehouse_name',
                    'branches.name as branch_name',
                    DB::raw('SUM(warehouse_stocks.total_pieces) as total_pieces'),
                    DB::raw('COALESCE(warehouses.branch_id, 0) as branch_id')
                )
                ->groupBy('warehouse_stocks.product_id', 'warehouse_stocks.warehouse_id', 'warehouses.warehouse_name', 'warehouses.branch_id', 'branches.name')
                ->having('total_pieces', '>', 0);

            if ($warehouseId) $whStockQuery->where('warehouse_stocks.warehouse_id', $warehouseId);
            if ($branchId)    $whStockQuery->where('warehouses.branch_id', $branchId);
            $whStockAll = $whStockQuery->get()->groupBy('product_id');

            // ── 2b. Bulk-fetch batch/lot stock (product_batches) ─────────────
            $batchQuery = DB::table('product_batches')
                ->whereIn('product_id', $productIds)
                ->where('qty_remaining', '>', 0)
                ->select(
                    'product_id',
                    'batch_number',
                    'qty_remaining',
                    'exp_date',
                    'mfg_date',
                    'status',
                    'source_type'
                )
                ->orderBy('exp_date');
            if ($warehouseId) $batchQuery->where('warehouse_id', $warehouseId);
            if ($branchId)    $batchQuery->where('branch_id', $branchId);
            $batchAll = $batchQuery->get()->groupBy('product_id');

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
                    
                    // Period Donated (Should be positive representing stock out)
                    DB::raw("SUM(CASE WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type = 'donation' THEN abs(qty) WHEN created_at BETWEEN '$startDt' AND '$endDt' AND ref_type = 'donation_cancel' THEN -abs(qty) ELSE 0 END) as donated"),
                    
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
                ->whereIn('sales.sale_status', ['posted', 'post', 'completed', 'in_delivery', 'delivered'])
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
                    'sold' => 0, 'sale_return' => 0, 'donated' => 0, 'adjusted' => 0
                ];

                $initial    = (float)$mv->initial;
                $purchased  = (float)$mv->purchased;
                $purReturn  = (float)abs($mv->pur_return);
                $sold       = (float)abs($mv->sold);
                $donated    = (float)abs($mv->donated ?? 0);
                $saleReturn = (float)$mv->sale_return;
                $adjusted   = (float)$mv->adjusted;

                // Calculated period balance: Initial + In - Out
                // Period Balance = initial + purchased - pur_return - sold + sale_return - donated + adjusted
                $periodBalance = $initial + $purchased - $purReturn - $sold + $saleReturn - $donated + $adjusted;

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
                    'branch_name'    => $w->branch_name,
                    'display'        => floor($w->total_pieces / $ppb) . '.' . ((int)$w->total_pieces % $ppb) . ' (' . (int)$w->total_pieces . ' pcs)',
                ])->values()->toArray();

                $rows[] = [
                    'id'                       => $pid,
                    'item_code'                => $p->item_code,
                    'item_name'                => ($p->item_name ?? '') . ' ' . ($p->brand?->name ?? ''),
                    'brand'                    => $p->brand?->name ?? '-',
                    'branch_names'             => $whGroup->pluck('branch_name')->filter()->unique()->implode(', ') ?: '-',
                    'category'                 => $p->category_relation?->name ?? '-',
                    'sub_category'             => $p->sub_category_relation?->name ?? '-',
                    'unit'                     => $p->unit?->name ?? '-',
                    'pieces_per_box'           => $ppb,
                    'initial_stock'            => $initial,
                    'purchased'                => $purchased,
                    'purchase_return_qty'      => $purReturn,
                    'sold'                     => $sold,
                    'donated'                  => $donated,
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
                    'batches'                  => (function() use ($batchAll, $pid, $displayBalance) {
                        $mapped = $batchAll->get($pid, collect())->map(fn($b) => [
                            'batch_number'  => $b->batch_number,
                            'qty_remaining' => (float)$b->qty_remaining,
                            'exp_date'      => $b->exp_date,
                            'mfg_date'      => $b->mfg_date,
                            'status'        => $b->status,
                            'source_type'   => $b->source_type,
                        ])->values()->toArray();

                        $sumBatchQty = array_sum(array_column($mapped, 'qty_remaining'));
                        $unbatchedQty = $displayBalance - $sumBatchQty;
                        if ($unbatchedQty > 0.001) {
                            $mapped[] = [
                                'batch_number'  => 'Unbatched Stock',
                                'qty_remaining' => (float)$unbatchedQty,
                                'exp_date'      => null,
                                'mfg_date'      => null,
                                'status'        => 'normal',
                                'source_type'   => 'unbatched_stock',
                            ];
                        }
                        return $mapped;
                    })(),
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

            return [
                'data'           => $rows,
                'grand_total'    => round($gStockVal, 2),
                'grand_purchase' => round($gPurAmt, 2),
                'grand_sale'     => round($gSaleAmt, 2),
                'warehouses'     => Warehouse::when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->orderByRaw("FIELD(type,'shop','warehouse')")
                    ->orderBy('warehouse_name')
                    ->select('id', 'warehouse_name', 'type')
                    ->get(),
                'ledger_data'    => $ledgerData,
            ];

        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        }
    }

    public function fetchItemStock(Request $request)
    {
        $data = $this->buildItemStockData($request);
        if (isset($data['error'])) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportItemStockExcel(Request $request)
    {
        $d = $this->buildItemStockData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $data = [['#', 'Code', 'Product (Brand - Name / UOM)', 'Category', 'Opening', 'In (Pur)', 'Pur.Ret', 'Out (Sale)', 'Sale Ret', 'Balance', 'Warehouse', 'Pur Amt', 'Sale Amt', 'Stock Value']];
        
        $totInit = 0; $totIn = 0; $totOut = 0; $totBal = 0; $totPurAmt = 0; $totSaleAmt = 0; $totVal = 0;

        foreach ($d['data'] as $i => $r) {
            $ppb = $r['pieces_per_box'] ?? 1;
            $bal = (float)($r['balance'] ?? 0);
            $boxes = floor($bal / $ppb);
            $loose = round(fmod($bal, $ppb));

            $label = (!empty($r['brand']) && $r['brand'] !== '-' ? $r['brand'] . ' - ' : '') . $r['item_name'];
            $uomLabel = '';
            if (!empty($r['packings']) && is_array($r['packings']) && count($r['packings']) > 0) {
                $uomLabel = implode(' / ', array_map(fn($p) => $p['name'] . '(' . $p['pieces_per_box'] . ')', $r['packings']));
            } elseif ($ppb > 1) {
                $uomLabel = $ppb . ' pcs/box';
            }
            if ($uomLabel) $label .= "\n" . $uomLabel;

            $inQty = (float)($r['purchased'] ?? 0);
            $outQty = (float)($r['sold'] ?? 0) + (float)($r['purchase_return_qty'] ?? 0);
            $retIn = (float)($r['sale_return_qty'] ?? 0);

            $totInit += (float)($r['initial_stock'] ?? 0);
            $totIn += $inQty;
            $totOut += $outQty;
            $totBal += $bal;
            $totPurAmt += (float)($r['purchase_amount'] ?? 0);
            $totSaleAmt += (float)($r['sale_amount'] ?? 0);
            $totVal += (float)($r['stock_value'] ?? 0);

            $whText = '';
            if (!empty($r['warehouses'])) {
                $whText = implode("\n", array_map(fn($w) => $w['warehouse_name'] . ':' . $w['display'], $r['warehouses']));
            }

            $catStr = $r['category'] . (!empty($r['sub_category']) && $r['sub_category'] !== '-' ? '/' . $r['sub_category'] : '');

            $data[] = [
                $i + 1,
                $r['item_code'] ?? '-',
                $label,
                $catStr,
                $r['initial_stock'] ?? 0,
                abs($inQty),
                abs((float)($r['purchase_return_qty'] ?? 0)),
                abs($outQty - (float)($r['purchase_return_qty'] ?? 0)),
                abs($retIn),
                $boxes . '.' . $loose . "\n(" . round($bal) . ' pcs)',
                $whText ?: '-',
                $r['purchase_amount'] ?? 0,
                $r['sale_amount'] ?? 0,
                $r['stock_value'] ?? 0,
            ];
        }

        $data[] = [
            '', '', 'GRAND TOTAL', '', 
            $totInit, $totIn, '', $totOut, '', $totBal, '', 
            $totPurAmt, $totSaleAmt, $totVal
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Item_Stock_Summary_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportItemStockPdf(Request $request)
    {
        $request->merge(['report_type' => 'ledger']); // Force detail ledger data load
        $d = $this->buildItemStockData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $filename = 'Item_Stock_Detail_Ledger_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .group-hdr { background-color:#e2e8f0; font-weight:bold; }
            .closing-row { background-color:#f1f5f9; font-weight:bold; color:#b91c1c; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>ITEM STOCK DETAIL REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>';
        
        foreach ($d['data'] as $pi => $product) {
            $ledger = $d['ledger_data'][$product['id']] ?? null;
            
            $uomLabel = '';
            if (!empty($product['packings'])) {
                $uomLabel = implode(' / ', array_column($product['packings'], 'name'));
            } elseif (($product['pieces_per_box'] ?? 1) > 1) {
                $uomLabel = $product['pieces_per_box'] . 'PCS';
            }
            
            $prodLabel = strtoupper($product['item_name']) . ($uomLabel ? ' (' . strtoupper($uomLabel) . ')' : '');
            
            $html .= '<tr class="group-hdr"><td colspan="8">'.($pi+1).'. ' . $prodLabel . '</td></tr>';
            
            $html .= '<tr>
                        <th width="4%">#</th>
                        <th width="12%">Date</th>
                        <th width="28%">Description</th>
                        <th width="14%">Ref</th>
                        <th class="num" width="10%">Rate</th>
                        <th class="num" width="10%">Debit (IN)</th>
                        <th class="num" width="10%">Credit (OUT)</th>
                        <th class="num" width="12%">Balance</th>
                      </tr>';
                      
            $openBal = $ledger ? (float)$ledger['opening_balance'] : 0;
            $html .= '<tr>
                        <td>1</td>
                        <td></td>
                        <td>OPENING STOCK</td>
                        <td></td>
                        <td class="num">0.00</td>
                        <td class="num">'.($openBal > 0 ? number_format($openBal,3) : '0.000').'</td>
                        <td class="num">'.($openBal < 0 ? number_format(abs($openBal),3) : '0.000').'</td>
                        <td class="num">'.($openBal != 0 ? number_format($openBal,3) : '').'</td>
                      </tr>';
                      
            if ($ledger && !empty($ledger['transactions'])) {
                foreach ($ledger['transactions'] as $idx => $tx) {
                    $html .= '<tr>
                                <td>'.($idx+2).'</td>
                                <td>'.($tx['date'] ?? '').'</td>
                                <td>'.($tx['desc'] ?? '').'</td>
                                <td>'.($tx['ref'] ?? '').'</td>
                                <td class="num">'.($tx['rate'] ? number_format($tx['rate'],2) : '0.00').'</td>
                                <td class="num">'.($tx['debit'] > 0 ? number_format($tx['debit'],3) : '0.000').'</td>
                                <td class="num">'.($tx['credit'] > 0 ? number_format($tx['credit'],3) : '0.000').'</td>
                                <td class="num">'.(isset($tx['balance']) ? number_format($tx['balance'],3) : '').'</td>
                              </tr>';
                }
            }
            
            $closeBal = $ledger ? (float)$ledger['closing_balance'] : 0;
            $html .= '<tr class="closing-row">
                        <td colspan="7" class="num">Closing Balance :</td>
                        <td class="num">'.($closeBal != 0 ? number_format($closeBal,3) : '').'</td>
                      </tr>';
                      
            $html .= '<tr><td colspan="8" style="border:none; height:10px;"></td></tr>';
        }
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
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

    // ─── Helper: build purchase report data ───────
    private function buildPurchaseReportData(Request $request): array
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
                'purchases.id', 'purchases.invoice_no', 'purchases.purchase_date',
                'purchases.status_purchase', 'vendors.name as vendor_name',
                'vendors.phone as vendor_phone', 'warehouses.warehouse_name',
                'purchases.subtotal', 'purchases.discount', 'purchases.extra_cost',
                'purchases.net_amount', 'purchases.paid_amount', 'purchases.due_amount',
                'purchases.note', 'purchases.po_ref'
            );

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
        $purchaseIds = $purchases->pluck('id')->toArray();

        $itemsQuery = DB::table('purchase_items')
            ->join('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('product_uoms', 'purchase_items.uom_id', '=', 'product_uoms.id')
            ->whereIn('purchase_items.purchase_id', $purchaseIds)
            ->select(
                'purchase_items.purchase_id', 'products.item_code', 'products.item_name',
                'brands.name as brand_name', 'purchase_items.qty', 'purchase_items.loose_qty',
                'purchase_items.free_qty_pieces', 'purchase_items.unit', 'purchase_items.price',
                'purchase_items.item_discount', 'purchase_items.line_total', 'purchase_items.size_mode',
                'purchase_items.pieces_per_box', 'purchase_items.gst_percent', 'purchase_items.gst_amount',
                'purchase_items.it_percent', 'purchase_items.adv_tax_percent', 'products.hs_code',
                'product_uoms.name as table_uom_name', 'purchase_items.uom_factor'
            );

        if ($catId && $catId !== 'all')      $itemsQuery->where('products.category_id', $catId);
        if ($subId && $subId !== 'all')      $itemsQuery->where('products.sub_category_id', $subId);
        if ($brandId && $brandId !== 'all')  $itemsQuery->where('products.brand_id', $brandId);
        if ($productId && $productId !== 'all') $itemsQuery->where('products.id', $productId);

        $itemsMap = $itemsQuery->get()->groupBy('purchase_id');

        $returnsMap = DB::table('purchase_returns')
            ->whereIn('purchase_id', $purchaseIds)
            ->select('purchase_id', DB::raw('SUM(net_amount) as total_returned'), DB::raw('COUNT(*) as return_count'))
            ->groupBy('purchase_id')
            ->get()
            ->keyBy('purchase_id');

        $rows = [];
        $grandSubtotal = 0; $grandNet = 0; $grandPaid = 0; $grandDue = 0; $grandReturned = 0;

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

        return ['rows' => $rows, 'grandSubtotal' => $grandSubtotal, 'grandNet' => $grandNet, 
                'grandPaid' => $grandPaid, 'grandDue' => $grandDue, 'grandReturned' => $grandReturned,
                'start' => $startDate, 'end' => $endDate];
    }

    public function fetchPurchaseReport(Request $request)
    {
        $d = $this->buildPurchaseReportData($request);
        $branchId = $this->getBranchId();

        $vendors = DB::table('vendors')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'name')->orderBy('name')->get();
            
        $warehouses = DB::table('warehouses')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'warehouse_name')->orderBy('warehouse_name')->get();

        return response()->json([
            'data' => $d['rows'],
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'grand_subtotal' => $d['grandSubtotal'],
            'grand_net' => $d['grandNet'],
            'grand_paid' => $d['grandPaid'],
            'grand_due' => $d['grandDue'],
            'grand_returned' => $d['grandReturned'],
        ]);
    }

    public function exportPurchaseReportExcel(Request $request)
    {
        $d = $this->buildPurchaseReportData($request);
        
        $data = [['Invoice No', 'Date', 'Vendor', 'Warehouse', 'Subtotal', 'Discount', 'Extra Cost', 'Net Amount', 'Paid', 'Due', 'Returned', 'Status', 'Item Code', 'Item Name', 'Packing', 'Qty', 'Free', 'Price', 'Item Discount', 'Line Total']];
        
        foreach ($d['rows'] as $r) {
            if (empty($r['items'])) {
                $data[] = [$r['invoice_no'], $r['purchase_date'], $r['vendor_name'], $r['warehouse_name'], $r['subtotal'], $r['discount'], $r['extra_cost'], $r['net_amount'], $r['paid_amount'], $r['due_amount'], $r['total_returned'], $r['status'], '', '', '', '', '', '', '', ''];
            } else {
                foreach ($r['items'] as $ii => $it) {
                    if ($ii == 0) {
                        $data[] = [$r['invoice_no'], $r['purchase_date'], $r['vendor_name'], $r['warehouse_name'], $r['subtotal'], $r['discount'], $r['extra_cost'], $r['net_amount'], $r['paid_amount'], $r['due_amount'], $r['total_returned'], $r['status'], $it['item_code'], $it['item_name'], $it['uom_name'], $it['qty'], $it['free_qty'], $it['price'], $it['item_discount'], $it['line_total']];
                    } else {
                        $data[] = [$r['invoice_no'], $r['purchase_date'], $r['vendor_name'], $r['warehouse_name'], '', '', '', '', '', '', '', '', $it['item_code'], $it['item_name'], $it['uom_name'], $it['qty'], $it['free_qty'], $it['price'], $it['item_discount'], $it['line_total']];
                    }
                }
            }
        }
        
        $data[] = [];
        $data[] = ['','','','GRAND TOTAL:', $d['grandSubtotal'], '', '', $d['grandNet'], $d['grandPaid'], $d['grandDue'], $d['grandReturned']];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Purchase_Report_' . now()->format('Y-m-d') . '.xlsx';

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    public function exportPurchaseReportPdf(Request $request)
    {
        $d = $this->buildPurchaseReportData($request);
        $filename = 'Purchase_Report_' . now()->format('Y-m-d') . '.pdf';

        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 8px; margin: 10px; }
            h2 { text-align:center; font-size:12px; margin-bottom:4px; }
            p.sub { text-align:center; color:#555; margin:0 0 8px; font-size:8px; }
            table { width:100%; border-collapse:collapse; }
            th { background:#1e40af; color:#fff; padding:4px 2px; text-align:left; font-size:7px; }
            td { padding:3px 2px; border-bottom:1px solid #e5e7eb; font-size:7px; }
            tr:nth-child(even) td { background:#f8fafc; }
            .num { text-align:right; }
            .total-row td { font-weight:bold; background:#dbeafe !important; }
        </style></head><body>
        <h2>Purchase Report — Detailed</h2>
        <p class="sub">Period: ' . ($d['start'] ?? '-') . ' to ' . ($d['end'] ?? '-') . ' &nbsp;|&nbsp; Generated: ' . now()->format('d M Y H:i') . '</p>
        <table>
        <tr><th>Invoice</th><th>Date</th><th>Vendor</th><th>Warehouse</th><th>Status</th><th>Item</th><th>Code</th><th>Packing</th><th class="num">Rate</th><th class="num">Qty</th><th class="num">Free</th><th class="num">Total</th></tr>';

        foreach ($d['rows'] as $r) {
            foreach ($r['items'] as $it) {
                $html .= '<tr>
                    <td>' . e($r['invoice_no'])  . '</td>
                    <td>' . e($r['purchase_date'])     . '</td>
                    <td>' . e($r['vendor_name']) . '</td>
                    <td>' . e($r['warehouse_name'])     . '</td>
                    <td>' . e($r['status'])     . '</td>
                    <td>' . e($it['item_name'])  . '</td>
                    <td>' . e($it['item_code'])  . '</td>
                    <td>' . e($it['uom_name'])  . '</td>
                    <td class="num">' . number_format($it['price'],     2) . '</td>
                    <td class="num">' . number_format($it['qty'],      2) . '</td>
                    <td class="num">' . number_format($it['free_qty'],     2) . '</td>
                    <td class="num">' . number_format($it['line_total'],    2) . '</td>
                </tr>';
            }
        }

        $html .= '<tr class="total-row">
            <td colspan="11">GRAND NET TOTAL</td>
            <td class="num">' . number_format($d['grandNet'],  2) . '</td>
        </tr></table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);

        return $pdf->download($filename);
    }

    public function sale_report()
    {
        $vendors = \App\Models\Vendor::orderBy('name')->get();
        return view('admin_panel.reporting.sale_report', compact('vendors'));
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
        $vendorId    = $request->vendor_id;
        $branchId    = $this->getBranchId();

        $vendorProductIds = [];
        if ($vendorId && $vendorId !== 'all') {
            $vendorProductIds = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->where('purchases.vendor_id', $vendorId)
                ->pluck('purchase_items.product_id')
                ->unique()
                ->toArray();

            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'vendor_id')) {
                $directIds = DB::table('products')->where('vendor_id', $vendorId)->pluck('id')->toArray();
                $vendorProductIds = array_unique(array_merge($vendorProductIds, $directIds));
            }
        }

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
        if (($catId && $catId !== 'all') || ($subId && $subId !== 'all') || ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all') || ($vendorId && $vendorId !== 'all')) {
            $query->whereIn('sales.id', function($sub) use ($catId, $subId, $brandId, $productId, $vendorId, $vendorProductIds) {
                $sub->select('sale_id')->from('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->when($catId && $catId !== 'all', fn($q) => $q->where('products.category_id', $catId))
                    ->when($subId && $subId !== 'all', fn($q) => $q->where('products.sub_category_id', $subId))
                    ->when($brandId && $brandId !== 'all', fn($q) => $q->where('products.brand_id', $brandId))
                    ->when($productId && $productId !== 'all', fn($q) => $q->where('products.id', $productId))
                    ->when($vendorId && $vendorId !== 'all', fn($q) => $q->whereIn('products.id', !empty($vendorProductIds) ? $vendorProductIds : [0]));
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
        $itemsQuery = DB::table('sale_items')
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
            );

        if ($catId && $catId !== 'all') {
            $itemsQuery->where('products.category_id', $catId);
        }
        if ($subId && $subId !== 'all') {
            $itemsQuery->where('products.sub_category_id', $subId);
        }
        if ($brandId && $brandId !== 'all') {
            $itemsQuery->where('products.brand_id', $brandId);
        }
        if ($productId && $productId !== 'all') {
            $itemsQuery->where('products.id', $productId);
        }
        if ($vendorId && $vendorId !== 'all') {
            $itemsQuery->whereIn('products.id', !empty($vendorProductIds) ? $vendorProductIds : [0]);
        }

        $itemsMap = $itemsQuery->get()->groupBy('sale_id');

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
        $grandSubtotal = 0;
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

            $grandSubtotal += (float) ($s->total_bill_amount ?? 0);
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
            'grand_subtotal' => $grandSubtotal,
            'grand_net' => $grandNet,
            'grand_paid' => $grandPaid,
            'grand_due' => $grandNet - $grandPaid,
            'grand_returned' => $grandReturned,
        ]);
    }

    // ─── Helper: build sale report data (shared by Excel & PDF exports) ───────
    private function buildSaleReportData(Request $request): array
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
        $vendorId    = $request->vendor_id;
        $branchId    = $this->getBranchId();

        $vendorProductIds = [];
        if ($vendorId && $vendorId !== 'all') {
            $vendorProductIds = DB::table('purchase_items')
                ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                ->where('purchases.vendor_id', $vendorId)
                ->pluck('purchase_items.product_id')->unique()->toArray();
            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'vendor_id')) {
                $directIds = DB::table('products')->where('vendor_id', $vendorId)->pluck('id')->toArray();
                $vendorProductIds = array_unique(array_merge($vendorProductIds, $directIds));
            }
        }

        $query = DB::table('sales')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->select('sales.id','sales.invoice_no','sales.sale_status','sales.total_bill_amount',
                     'sales.total_extradiscount','sales.total_net','sales.cash','sales.change',
                     'sales.created_at','customers.customer_name');

        if (($catId && $catId !== 'all') || ($subId && $subId !== 'all') ||
            ($brandId && $brandId !== 'all') || ($productId && $productId !== 'all') ||
            ($vendorId && $vendorId !== 'all')) {
            $query->whereIn('sales.id', function($sub) use ($catId, $subId, $brandId, $productId, $vendorId, $vendorProductIds) {
                $sub->select('sale_id')->from('sale_items')
                    ->join('products', 'products.id', '=', 'sale_items.product_id')
                    ->when($catId    && $catId    !== 'all', fn($q) => $q->where('products.category_id',     $catId))
                    ->when($subId    && $subId    !== 'all', fn($q) => $q->where('products.sub_category_id', $subId))
                    ->when($brandId  && $brandId  !== 'all', fn($q) => $q->where('products.brand_id',        $brandId))
                    ->when($productId && $productId !== 'all', fn($q) => $q->where('products.id',            $productId))
                    ->when($vendorId && $vendorId !== 'all',
                        fn($q) => $q->whereIn('products.id', !empty($vendorProductIds) ? $vendorProductIds : [0]));
            });
        }

        if ($branchId)                             $query->where('sales.branch_id', $branchId);
        if ($start && $end)                        $query->whereBetween(DB::raw('DATE(sales.created_at)'), [$start, $end]);
        if ($customerId && $customerId !== 'all')  $query->where('sales.customer_id', $customerId);
        if ($status     && $status     !== 'all')  $query->where('sales.sale_status', $status);
        if ($warehouseId && $warehouseId !== 'all') {
            $query->whereIn('sales.id', function($sub) use ($warehouseId) {
                $sub->select('sale_id')->from('sale_items')->where('warehouse_id', $warehouseId);
            });
        }

        $sales   = $query->orderBy('sales.created_at', 'desc')->get();
        $saleIds = $sales->pluck('id')->toArray();

        $itemsMap = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('product_uoms', function($join) {
                $join->on('products.id', '=', 'product_uoms.product_id')
                     ->on('sale_items.uom_factor', '=', 'product_uoms.pieces_per_box');
            })
            ->whereIn('sale_items.sale_id', $saleIds)
            ->select('sale_items.sale_id','products.item_code','products.item_name',
                     'brands.name as brand_name','product_uoms.name as uom_name',
                     'units.name as master_uom','products.hs_code',
                     'sale_items.qty','sale_items.free_qty','sale_items.price','sale_items.total',
                     'sale_items.gst_amount','sale_items.inc_tax','sale_items.adv_tax','sale_items.discount_amount')
            ->get()->groupBy('sale_id');

        $rows = []; $grandQty = 0; $grandFree = 0; $grandNet = 0;

        foreach ($sales as $s) {
            $dp = explode('-', explode(' ', $s->created_at)[0]);
            $date = count($dp) === 3 ? "{$dp[2]}/{$dp[1]}/{$dp[0]}" : $s->created_at;

            foreach ($itemsMap->get($s->id, collect()) as $it) {
                $qty   = (float)($it->qty       ?? 0);
                $free  = (float)($it->free_qty  ?? 0);
                $total = (float)($it->total      ?? 0);
                $grandQty += $qty; $grandFree += $free; $grandNet += $total;

                $rows[] = [
                    'invoice'   => $s->invoice_no ?? 'SLE-'.$s->id,
                    'date'      => $date,
                    'customer'  => $s->customer_name ?? 'Walk-in',
                    'status'    => $s->sale_status ?? '-',
                    'item'      => trim(($it->item_name ?? '') . ' ' . ($it->brand_name ?? '')),
                    'code'      => $it->item_code ?? '',
                    'hs_code'   => $it->hs_code ?? '',
                    'packing'   => $it->uom_name ?? $it->master_uom ?? '',
                    'rate'      => (float)($it->price ?? 0),
                    'qty'       => $qty,
                    'free'      => $free,
                    'discount'  => (float)($it->discount_amount ?? 0),
                    'gst'       => (float)($it->gst_amount ?? 0),
                    'total'     => $total,
                ];
            }
        }

        return ['rows' => $rows, 'grandQty' => $grandQty, 'grandFree' => $grandFree, 'grandNet' => $grandNet,
                'start' => $start, 'end' => $end];
    }

    /** Server-side Excel export — proper filename via Content-Disposition */
    public function exportSaleReportExcel(Request $request)
    {
        $d = $this->buildSaleReportData($request);

        $data   = [['Invoice No','Date','Customer','Status','Item Description','Code','HS Code','Packing','Rate','Qty','Free','Discount','GST','Net Total']];
        foreach ($d['rows'] as $r) {
            $data[] = [$r['invoice'],$r['date'],$r['customer'],$r['status'],$r['item'],$r['code'],$r['hs_code'],$r['packing'],$r['rate'],$r['qty'],$r['free'],$r['discount'],$r['gst'],$r['total']];
        }
        $data[] = [];
        $data[] = ['','','','','','','','','GRAND TOTAL:',$d['grandQty'],$d['grandFree'],'','',$d['grandNet']];

        $xlsx     = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Sale_Report_' . now()->format('Y-m-d') . '.xlsx';

        return response((string) $xlsx, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }

    /** Server-side PDF export — proper filename via Content-Disposition */
    public function exportSaleReportPdf(Request $request)
    {
        $d        = $this->buildSaleReportData($request);
        $filename = 'Sale_Report_' . now()->format('Y-m-d') . '.pdf';

        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 10px; }
            h2 { text-align:center; font-size:13px; margin-bottom:4px; }
            p.sub { text-align:center; color:#555; margin:0 0 8px; font-size:8px; }
            table { width:100%; border-collapse:collapse; }
            th { background:#1e40af; color:#fff; padding:4px 3px; text-align:left; font-size:8px; }
            td { padding:3px 3px; border-bottom:1px solid #e5e7eb; font-size:8px; }
            tr:nth-child(even) td { background:#f8fafc; }
            .num { text-align:right; }
            .total-row td { font-weight:bold; background:#dbeafe !important; }
        </style></head><body>
        <h2>Sale Report — Detailed</h2>
        <p class="sub">Period: ' . ($d['start'] ?? '-') . ' to ' . ($d['end'] ?? '-') . ' &nbsp;|&nbsp; Generated: ' . now()->format('d M Y H:i') . '</p>
        <table>
        <tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Item</th><th>Code</th><th>Packing</th><th class="num">Rate</th><th class="num">Qty</th><th class="num">Free</th><th class="num">Discount</th><th class="num">GST</th><th class="num">Total</th></tr>';

        foreach ($d['rows'] as $r) {
            $html .= '<tr>
                <td>' . e($r['invoice'])  . '</td>
                <td>' . e($r['date'])     . '</td>
                <td>' . e($r['customer']) . '</td>
                <td>' . e($r['item'])     . '</td>
                <td>' . e($r['code'])     . '</td>
                <td>' . e($r['packing'])  . '</td>
                <td class="num">' . number_format($r['rate'],     2) . '</td>
                <td class="num">' . number_format($r['qty'],      2) . '</td>
                <td class="num">' . number_format($r['free'],     2) . '</td>
                <td class="num">' . number_format($r['discount'], 2) . '</td>
                <td class="num">' . number_format($r['gst'],      2) . '</td>
                <td class="num">' . number_format($r['total'],    2) . '</td>
            </tr>';
        }

        $html .= '<tr class="total-row">
            <td colspan="7">GRAND TOTAL</td>
            <td class="num">' . number_format($d['grandQty'],  2) . '</td>
            <td class="num">' . number_format($d['grandFree'], 2) . '</td>
            <td class="num"></td><td class="num"></td>
            <td class="num">' . number_format($d['grandNet'],  2) . '</td>
        </tr></table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->setOptions(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false]);

        return $pdf->download($filename);
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

    private function buildCustomerLedgerData(Request $request)
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

        return [
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
        ];
    }

    public function fetch_customer_ledger(Request $request)
    {
        $data = $this->buildCustomerLedgerData($request);
        if (isset($data->headers)) return $data; // in case of error response
        return response()->json($data);
    }

    public function exportCustomerLedgerExcel(Request $request)
    {
        $d = $this->buildCustomerLedgerData($request);
        if (isset($d->headers)) return $d; // return error response if any
        
        $data = [['Date', 'Invoice', 'Description', 'Type', 'Debit', 'Credit', 'Balance']];
        $data[] = ['','','Opening Balance','','','',$d['opening_balance']];
        foreach ($d['transactions'] as $r) {
            $data[] = [$r['date'], $r['invoice'], $r['description'], $r['type'], $r['debit'], $r['credit'], $r['balance']];
        }
        $data[] = ['','','Closing Balance','',$d['total_debit'],$d['total_credit'],$d['closing_balance']];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Customer_Ledger_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportCustomerLedgerPdf(Request $request)
    {
        $d = $this->buildCustomerLedgerData($request);
        if (isset($d->headers)) return $d;

        $filename = 'Customer_Ledger_' . now()->format('Y-m-d') . '.pdf';
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h2 { text-align:center; } table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:4px; text-align:left; }
            th { background-color:#f2f2f2; }
            .num { text-align:right; }
        </style></head><body>';
        $html .= '<h2>Customer Ledger: ' . $d['customer']['customer_name'] . '</h2>';
        $html .= '<p>Period: ' . $d['report_period'] . '</p>';
        $html .= '<table><tr><th>Date</th><th>Invoice</th><th>Description</th><th>Type</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr>';
        $html .= '<tr><td colspan="6">Opening Balance</td><td class="num">'.number_format($d['opening_balance'],2).'</td></tr>';
        foreach ($d['transactions'] as $t) {
            $html .= '<tr><td>'.$t['date'].'</td><td>'.$t['invoice'].'</td><td>'.$t['description'].'</td><td>'.$t['type'].'</td><td class="num">'.number_format($t['debit'],2).'</td><td class="num">'.number_format($t['credit'],2).'</td><td class="num">'.number_format($t['balance'],2).'</td></tr>';
        }
        $html .= '<tr><td colspan="4"><b>Totals</b></td><td class="num"><b>'.number_format($d['total_debit'],2).'</b></td><td class="num"><b>'.number_format($d['total_credit'],2).'</b></td><td class="num"><b>'.number_format($d['closing_balance'],2).'</b></td></tr>';
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
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
                0.00 as price_per_m2,
                0.00 as total_m2,
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
                'products.pieces_per_box'
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
        \App\Models\Warehouse::ensureShopWarehousesExists();
        $branchId = $this->getBranchId();

        $shops = Warehouse::where('type', 'shop')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        $warehouses = Warehouse::where('type', 'warehouse')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        return view('admin_panel.reporting.warehouse_report', compact('shops', 'warehouses'));
    }

    private function buildWarehouseData(Request $request)
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
            return ['error' => $e->getMessage()];
        }
    }

    public function fetchWarehouseReport(Request $request)
    {
        $data = $this->buildWarehouseData($request);
        if (isset($data['error'])) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportWarehouseExcel(Request $request)
    {
        $d = $this->buildWarehouseData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $data = [['#', 'Code', 'Product (Brand - Name / UOM)', 'Category', 'Initial', 'In (Pur)', 'Out (Sale)', 'Ret/Adj', 'Balance', 'Pur Amt', 'Sale Amt', 'Stock Value']];
        
        $totInit = 0; $totIn = 0; $totOut = 0; $totRetAdj = 0; $totBal = 0; $totPurAmt = 0; $totSaleAmt = 0; $totVal = 0;

        foreach ($d['data'] as $i => $r) {
            $ppb = max(1, (int)($r['pieces_per_box'] ?? 1));
            $bal = (float)($r['balance'] ?? 0);
            $boxes = floor($bal / $ppb);
            $loose = round(fmod($bal, $ppb));

            $label = (!empty($r['brand']) && $r['brand'] !== '-' ? $r['brand'] . ' - ' : '') . $r['item_name'];
            $uomLabel = '';
            if (!empty($r['packings']) && is_array($r['packings']) && count($r['packings']) > 0) {
                $uomLabel = implode(' / ', array_map(fn($p) => $p['name'] . '(' . $p['pieces_per_box'] . ')', $r['packings']));
            } elseif ($ppb > 1) {
                $uomLabel = $ppb . ' pcs/box';
            }
            if ($uomLabel) $label .= "\n" . $uomLabel;

            $totInit += (float)($r['initial'] ?? 0);
            $totIn += (float)($r['purchased'] ?? 0);
            $totOut += (float)($r['sold'] ?? 0);
            $totRetAdj += (float)($r['ret_adj'] ?? 0);
            $totBal += $bal;
            $totPurAmt += (float)($r['purchase_amount'] ?? 0);
            $totSaleAmt += (float)($r['sale_amount'] ?? 0);
            $totVal += (float)($r['stock_value'] ?? 0);

            $data[] = [
                $i + 1,
                $r['item_code'] ?? '-',
                $label,
                $r['category'] . (!empty($r['sub_category']) && $r['sub_category'] !== '-' ? '/' . $r['sub_category'] : ''),
                $r['initial'] ?? 0,
                $r['purchased'] ?? 0,
                $r['sold'] ?? 0,
                $r['ret_adj'] ?? 0,
                $boxes . '.' . $loose . "\n(" . round($bal) . ' pcs)',
                $r['purchase_amount'] ?? 0,
                $r['sale_amount'] ?? 0,
                $r['stock_value'] ?? 0,
            ];
        }

        $data[] = [
            '', '', 'GRAND TOTAL', '', 
            $totInit, $totIn, $totOut, $totRetAdj, $totBal,
            $totPurAmt, $totSaleAmt, $totVal
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Warehouse_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportWarehousePdf(Request $request)
    {
        $d = $this->buildWarehouseData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $filename = 'Warehouse_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .total-row { background-color:#1e3a8a; color:white; font-weight:bold; }
            .total-row td { border-color:#1e3a8a; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>WAREHOUSE STOCK SUMMARY | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="3%">#</th>
                        <th width="8%">Code</th>
                        <th width="22%">Product (Brand - Name / UOM)</th>
                        <th width="10%">Category</th>
                        <th width="7%" class="num">Initial</th>
                        <th width="7%" class="num">In (Pur)</th>
                        <th width="7%" class="num">Out (Sale)</th>
                        <th width="7%" class="num">Ret/Adj</th>
                        <th width="7%" class="num">Balance</th>
                        <th width="7%" class="num">Pur Amt</th>
                        <th width="7%" class="num">Sale Amt</th>
                        <th width="8%" class="num">Stock Value</th>
                    </tr>';
                    
        $totInit = 0; $totIn = 0; $totOut = 0; $totRetAdj = 0; $totBal = 0; $totPurAmt = 0; $totSaleAmt = 0; $totVal = 0;

        foreach ($d['data'] as $i => $r) {
            $ppb = max(1, (int)($r['pieces_per_box'] ?? 1));
            $bal = (float)($r['balance'] ?? 0);
            $boxes = floor($bal / $ppb);
            $loose = round(fmod($bal, $ppb));

            $label = (!empty($r['brand']) && $r['brand'] !== '-' ? $r['brand'] . ' - ' : '') . $r['item_name'];
            $uomLabel = '';
            if (!empty($r['packings']) && is_array($r['packings']) && count($r['packings']) > 0) {
                $uomLabel = implode(' / ', array_map(fn($p) => $p['name'] . '(' . $p['pieces_per_box'] . ')', $r['packings']));
            } elseif ($ppb > 1) {
                $uomLabel = $ppb . ' pcs/box';
            }
            if ($uomLabel) $label .= '<br><span style="color:#666;">' . $uomLabel . '</span>';
            
            $catStr = $r['category'] . (!empty($r['sub_category']) && $r['sub_category'] !== '-' ? '/' . $r['sub_category'] : '');

            $totInit += (float)($r['initial'] ?? 0);
            $totIn += (float)($r['purchased'] ?? 0);
            $totOut += (float)($r['sold'] ?? 0);
            $totRetAdj += (float)($r['ret_adj'] ?? 0);
            $totBal += $bal;
            $totPurAmt += (float)($r['purchase_amount'] ?? 0);
            $totSaleAmt += (float)($r['sale_amount'] ?? 0);
            $totVal += (float)($r['stock_value'] ?? 0);

            $html .= '<tr>
                        <td>'.($i+1).'</td>
                        <td>'.($r['item_code'] ?? '-').'</td>
                        <td>'.$label.'</td>
                        <td>'.$catStr.'</td>
                        <td class="num">'.number_format($r['initial'] ?? 0).'</td>
                        <td class="num">'.number_format($r['purchased'] ?? 0).'</td>
                        <td class="num">'.number_format($r['sold'] ?? 0).'</td>
                        <td class="num">'.number_format($r['ret_adj'] ?? 0).'</td>
                        <td class="num" style="text-align:center;">'.$boxes.'.'.$loose.'<br>('.round($bal).' pcs)</td>
                        <td class="num">'.number_format($r['purchase_amount'] ?? 0, 2).'</td>
                        <td class="num">'.number_format($r['sale_amount'] ?? 0, 2).'</td>
                        <td class="num">'.number_format($r['stock_value'] ?? 0, 2).'</td>
                      </tr>';
        }
        
        $html .= '<tr class="total-row">
                    <td colspan="4" class="num">GRAND TOTAL</td>
                    <td class="num">'.number_format($totInit).'</td>
                    <td class="num">'.number_format($totIn).'</td>
                    <td class="num">'.number_format($totOut).'</td>
                    <td class="num">'.number_format($totRetAdj).'</td>
                    <td class="num">'.number_format($totBal).'</td>
                    <td class="num">'.number_format($totPurAmt, 2).'</td>
                    <td class="num">'.number_format($totSaleAmt, 2).'</td>
                    <td class="num">'.number_format($totVal, 2).'</td>
                  </tr>';
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'landscape');
        return $pdf->download($filename);
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

    private function buildVendorLedgerData(Request $request)
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

        return [
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
        ];
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $data = $this->buildVendorLedgerData($request);
        if (isset($data->headers)) return $data; // in case of error response
        return response()->json($data);
    }

    public function exportVendorLedgerExcel(Request $request)
    {
        $d = $this->buildVendorLedgerData($request);
        if (isset($d->headers)) return $d; // return error response if any
        
        $data = [['Date', 'Invoice', 'Description', 'Type', 'Debit', 'Credit', 'Balance']];
        $data[] = ['','','Opening Balance','','','',$d['opening_balance']];
        foreach ($d['transactions'] as $r) {
            $data[] = [$r['date'], $r['invoice'], $r['description'], $r['type'], $r['debit'], $r['credit'], $r['balance']];
        }
        $data[] = ['','','Closing Balance','',$d['total_debit'],$d['total_credit'],$d['closing_balance']];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Vendor_Ledger_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportVendorLedgerPdf(Request $request)
    {
        $d = $this->buildVendorLedgerData($request);
        if (isset($d->headers)) return $d;

        $filename = 'Vendor_Ledger_' . now()->format('Y-m-d') . '.pdf';
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h2 { text-align:center; } table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:4px; text-align:left; }
            th { background-color:#f2f2f2; }
            .num { text-align:right; }
        </style></head><body>';
        $html .= '<h2>Vendor Ledger: ' . $d['vendor']['name'] . '</h2>';
        $html .= '<p>Period: ' . $d['report_period'] . '</p>';
        $html .= '<table><tr><th>Date</th><th>Invoice</th><th>Description</th><th>Type</th><th class="num">Debit</th><th class="num">Credit</th><th class="num">Balance</th></tr>';
        $html .= '<tr><td colspan="6">Opening Balance</td><td class="num">'.number_format($d['opening_balance'],2).'</td></tr>';
        foreach ($d['transactions'] as $t) {
            $html .= '<tr><td>'.$t['date'].'</td><td>'.$t['invoice'].'</td><td>'.$t['description'].'</td><td>'.$t['type'].'</td><td class="num">'.number_format($t['debit'],2).'</td><td class="num">'.number_format($t['credit'],2).'</td><td class="num">'.number_format($t['balance'],2).'</td></tr>';
        }
        $html .= '<tr><td colspan="4"><b>Totals</b></td><td class="num"><b>'.number_format($d['total_debit'],2).'</b></td><td class="num"><b>'.number_format($d['total_credit'],2).'</b></td><td class="num"><b>'.number_format($d['closing_balance'],2).'</b></td></tr>';
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
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

    private function buildGlobalSummaryData(Request $request)
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

            return [
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
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchGlobalSummary(Request $request)
    {
        $data = $this->buildGlobalSummaryData($request);
        if (isset($data['success']) && !$data['success']) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportGlobalSummaryExcel(Request $request)
    {
        $d = $this->buildGlobalSummaryData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $data = [['#', 'Item Code', 'Item Name', 'Opening', 'Purchased', 'Sold', 'Adjusted', 'Closing', 'Purchase Value', 'Sale Value', 'Stock Value']];

        foreach ($d['data'] as $i => $r) {
            $data[] = [
                $i + 1,
                $r['item_code'] ?? '-',
                $r['item_name'] ?? '-',
                $r['opening'] ?? 0,
                $r['purchased'] ?? 0,
                $r['sold'] ?? 0,
                $r['adjusted'] ?? 0,
                $r['closing'] ?? 0,
                $r['purchase_value'] ?? 0,
                $r['sale_value'] ?? 0,
                $r['stock_value'] ?? 0,
            ];
        }

        $summ = $d['summary'] ?? [];
        $data[] = [
            '', '', 'GRAND TOTAL', 
            $summ['opening'] ?? 0, 
            $summ['purchased'] ?? 0, 
            $summ['sold'] ?? 0, 
            '', 
            $summ['closing'] ?? 0, 
            $summ['purch_value'] ?? 0, 
            $summ['sale_value'] ?? 0, 
            $summ['total_stock_value'] ?? 0
        ];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Global_Summary_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportGlobalSummaryPdf(Request $request)
    {
        $d = $this->buildGlobalSummaryData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $filename = 'Global_Summary_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .total-row { background-color:#1e3a8a; color:white; font-weight:bold; }
            .total-row td { border-color:#1e3a8a; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>GLOBAL SUMMARY REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="3%">#</th>
                        <th width="10%">Item Code</th>
                        <th width="20%">Item Name</th>
                        <th width="7%" class="num">Opening</th>
                        <th width="7%" class="num">Purchased</th>
                        <th width="7%" class="num">Sold</th>
                        <th width="7%" class="num">Adjusted</th>
                        <th width="7%" class="num">Closing</th>
                        <th width="10%" class="num">Purchase Value</th>
                        <th width="10%" class="num">Sale Value</th>
                        <th width="10%" class="num">Stock Value</th>
                    </tr>';
                    
        foreach ($d['data'] as $i => $r) {
            $html .= '<tr>
                        <td>'.($i+1).'</td>
                        <td>'.($r['item_code'] ?? '-').'</td>
                        <td>'.($r['item_name'] ?? '-').'</td>
                        <td class="num">'.number_format($r['opening'] ?? 0).'</td>
                        <td class="num">'.number_format($r['purchased'] ?? 0).'</td>
                        <td class="num">'.number_format($r['sold'] ?? 0).'</td>
                        <td class="num">'.number_format($r['adjusted'] ?? 0).'</td>
                        <td class="num" style="font-weight:bold;">'.number_format($r['closing'] ?? 0).'</td>
                        <td class="num">'.number_format($r['purchase_value'] ?? 0, 2).'</td>
                        <td class="num">'.number_format($r['sale_value'] ?? 0, 2).'</td>
                        <td class="num" style="font-weight:bold;">'.number_format($r['stock_value'] ?? 0, 2).'</td>
                      </tr>';
        }
        
        $summ = $d['summary'] ?? [];
        $html .= '<tr class="total-row">
                    <td colspan="3" class="num">GRAND TOTAL</td>
                    <td class="num">'.number_format($summ['opening'] ?? 0).'</td>
                    <td class="num">'.number_format($summ['purchased'] ?? 0).'</td>
                    <td class="num">'.number_format($summ['sold'] ?? 0).'</td>
                    <td class="num"></td>
                    <td class="num">'.number_format($summ['closing'] ?? 0).'</td>
                    <td class="num">'.number_format($summ['purch_value'] ?? 0, 2).'</td>
                    <td class="num">'.number_format($summ['sale_value'] ?? 0, 2).'</td>
                    <td class="num">'.number_format($summ['total_stock_value'] ?? 0, 2).'</td>
                  </tr>';
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'landscape');
        return $pdf->download($filename);
    }

    public function cdr_report()
    {
        $customers = Customer::orderBy('customer_name')->get(['id', 'customer_name', 'title']);
        $accounts  = Account::orderBy('title')->get(['id', 'title', 'account_code']);
        $isSuperAdmin = $this->isSuperAdmin();
        $branches = $isSuperAdmin ? DB::table('branches')->get(['id', 'name']) : collect();

        return view('admin_panel.reporting.cdr_report', compact('customers', 'accounts', 'isSuperAdmin', 'branches'));
    }

    private function buildCdrReportData(Request $request)
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

            return [
                'success' => true,
                'data' => $rows,
                'summary' => [
                    'total_amount' => $rows->sum('amount'),
                    'count' => $rows->count()
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchCdrReport(Request $request)
    {
        $data = $this->buildCdrReportData($request);
        if (isset($data['success']) && !$data['success']) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportCdrExcel(Request $request)
    {
        $d = $this->buildCdrReportData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $data = [['#', 'CDR Date', 'Status', 'Cheque Number', 'Bank/Account', 'Customer', 'Reference No', 'Amount']];

        foreach ($d['data'] as $i => $r) {
            $data[] = [
                $i + 1,
                \Carbon\Carbon::parse($r->cdr_date)->format('Y-m-d'),
                strtoupper($r->status),
                $r->cheque_number ?? '-',
                $r->bankAccount ? $r->bankAccount->title . ' (' . $r->bankAccount->account_code . ')' : '-',
                $r->customer ? $r->customer->customer_name : '-',
                $r->reference_no ?? '-',
                $r->amount
            ];
        }
        
        $data[] = ['', '', '', '', '', '', 'Total:', $d['summary']['total_amount']];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'CDR_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportCdrPdf(Request $request)
    {
        $d = $this->buildCdrReportData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $filename = 'CDR_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .total-row { font-weight:bold; background-color:#e2e8f0; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>CDR REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="5%">#</th>
                        <th width="10%">CDR Date</th>
                        <th width="10%">Status</th>
                        <th width="15%">Cheque Number</th>
                        <th width="20%">Bank/Account</th>
                        <th width="20%">Customer</th>
                        <th width="10%">Ref No</th>
                        <th width="10%" class="num">Amount</th>
                    </tr>';
                    
        foreach ($d['data'] as $i => $r) {
            $html .= '<tr>
                        <td>'.($i+1).'</td>
                        <td>'.\Carbon\Carbon::parse($r->cdr_date)->format('Y-m-d').'</td>
                        <td>'.strtoupper($r->status).'</td>
                        <td>'.($r->cheque_number ?? '-').'</td>
                        <td>'.($r->bankAccount ? $r->bankAccount->title . ' (' . $r->bankAccount->account_code . ')' : '-').'</td>
                        <td>'.($r->customer ? $r->customer->customer_name : '-').'</td>
                        <td>'.($r->reference_no ?? '-').'</td>
                        <td class="num">'.number_format($r->amount, 2).'</td>
                      </tr>';
        }
        
        $html .= '<tr class="total-row">
                    <td colspan="7" class="num">Total Amount</td>
                    <td class="num">'.number_format($d['summary']['total_amount'], 2).'</td>
                  </tr>';
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
    }
    public function price_adjustment_report()
    {
		
        $products = Product::orderBy('item_name')->get(['id', 'item_name', 'item_code']);
        $categories = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();

        return view('admin_panel.reporting.price_adjustment_report', compact('products', 'categories', 'subCategories', 'brands'));
    }

    private function buildPriceAdjustmentData(Request $request)
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

            return [
                'success' => true,
                'data' => $mappedRows
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchPriceAdjustmentReport(Request $request)
    {
        $data = $this->buildPriceAdjustmentData($request);
        if (isset($data['success']) && !$data['success']) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportPriceAdjustmentExcel(Request $request)
    {
        $d = $this->buildPriceAdjustmentData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $data = [['#', 'Date & Time', 'Product', 'Adjustment Type', 'Old Price', 'New Price', 'Difference', 'Adjusted By']];

        foreach ($d['data'] as $i => $r) {
            $data[] = [
                $i + 1,
                $r->created_at->format('Y-m-d H:i:s'),
                ($r->product->item_code ?? '-') . ' - ' . ($r->product->item_name ?? '-'),
                ucfirst($r->type),
                $r->old_price,
                $r->new_price,
                $r->new_price - $r->old_price,
                $r->user->name ?? 'System',
            ];
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Price_Adjustment_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportPriceAdjustmentPdf(Request $request)
    {
        $d = $this->buildPriceAdjustmentData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $filename = 'Price_Adjustment_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>PRICE ADJUSTMENT REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Date & Time</th>
                        <th width="25%">Product</th>
                        <th width="15%">Adjustment Type</th>
                        <th width="10%" class="num">Old Price</th>
                        <th width="10%" class="num">New Price</th>
                        <th width="10%" class="num">Difference</th>
                        <th width="10%">Adjusted By</th>
                    </tr>';
                    
        foreach ($d['data'] as $i => $r) {
            $diff = $r->new_price - $r->old_price;
            $html .= '<tr>
                        <td>'.($i+1).'</td>
                        <td>'.$r->created_at->format('Y-m-d H:i:s').'</td>
                        <td>'.($r->product->item_code ?? '-').' - '.($r->product->item_name ?? '-').'</td>
                        <td>'.ucfirst($r->type).'</td>
                        <td class="num">'.number_format($r->old_price, 2).'</td>
                        <td class="num">'.number_format($r->new_price, 2).'</td>
                        <td class="num">'.number_format($diff, 2).'</td>
                        <td>'.($r->user->name ?? 'System').'</td>
                      </tr>';
        }
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
    }

    public function dc_report()
    {
        $customers     = Customer::orderBy('customer_name')->get(['id', 'customer_name']);
        $categories    = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands        = \App\Models\Brand::orderBy('name')->get();
        $vendors       = \App\Models\Vendor::orderBy('name')->get();
        $products      = Product::orderBy('item_name')->get(['id', 'item_name', 'item_code', 'brand_id', 'category_id', 'sub_category_id']);

        return view('admin_panel.reporting.dc_report', compact('customers', 'categories', 'subCategories', 'brands', 'vendors', 'products'));
    }

    private function buildDcReportData(Request $request)
    {
        try {
            $start      = $request->start_date;
            $end        = $request->end_date;
            $customerId = $request->customer_id;
            $brandId    = $request->brand_id;
            $productId  = $request->product_id;
            $vendorId   = $request->vendor_id;
            $branchId   = $this->getBranchId();

            $vendorProductIds = [];
            if ($vendorId && $vendorId !== 'all') {
                $vendorProductIds = DB::table('purchase_items')
                    ->join('purchases', 'purchase_items.purchase_id', '=', 'purchases.id')
                    ->where('purchases.vendor_id', $vendorId)
                    ->pluck('purchase_items.product_id')
                    ->unique()
                    ->toArray();

                if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'vendor_id')) {
                    $directIds = DB::table('products')->where('vendor_id', $vendorId)->pluck('id')->toArray();
                    $vendorProductIds = array_unique(array_merge($vendorProductIds, $directIds));
                }
            }

            $query = DeliveryNote::with([
                'customer',
                'sale',
                'items.warehouse',
                'items.product.brand',
                'items.uom',
                'items.saleItem.uom',
            ]);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($start && $end) {
                $query->whereBetween(DB::raw('COALESCE(delivery_date, DATE(created_at))'), [$start, $end]);
            }
            if ($customerId && $customerId !== 'all') {
                $query->where('customer_id', $customerId);
            }

            // Product / catalogue filters
            if (($request->category_id    && $request->category_id    !== 'all') ||
                ($request->sub_category_id && $request->sub_category_id !== 'all') ||
                ($brandId                  && $brandId                  !== 'all') ||
                ($productId                && $productId                !== 'all') ||
                ($vendorId                 && $vendorId                 !== 'all')) {

                $query->whereHas('items.product', function ($q) use ($request, $brandId, $productId, $vendorId, $vendorProductIds) {
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
                    if ($vendorId && $vendorId !== 'all') {
                        $q->whereIn('id', !empty($vendorProductIds) ? $vendorProductIds : [0]);
                    }
                });
            }

            $rows = $query->orderBy(DB::raw('COALESCE(delivery_date, DATE(created_at))'), 'desc')
                          ->orderBy('id', 'desc')
                          ->get();

            $data = $rows->map(function ($r) use ($request, $brandId, $productId, $vendorId, $vendorProductIds) {
                $totalPieces = 0;
                $whBreakdown = [];
                $itemsDetail = [];

                $filteredItems = $r->items;
                if ($vendorId && $vendorId !== 'all') {
                    $filteredItems = $filteredItems->filter(fn($item) => in_array($item->product_id, !empty($vendorProductIds) ? $vendorProductIds : [0]));
                }
                if ($productId && $productId !== 'all') {
                    $filteredItems = $filteredItems->filter(fn($item) => $item->product_id == $productId);
                } else {
                    if ($request->category_id && $request->category_id !== 'all') {
                        $filteredItems = $filteredItems->filter(fn($item) => $item->product?->category_id == $request->category_id);
                    }
                    if ($request->sub_category_id && $request->sub_category_id !== 'all') {
                        $filteredItems = $filteredItems->filter(fn($item) => $item->product?->sub_category_id == $request->sub_category_id);
                    }
                    if ($brandId && $brandId !== 'all') {
                        $filteredItems = $filteredItems->filter(fn($item) => $item->product?->brand_id == $brandId);
                    }
                }

                foreach ($filteredItems as $item) {
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

                $dcDateFormatted = $r->delivery_date 
                    ? \Carbon\Carbon::parse($r->delivery_date)->format('d/m/Y') 
                    : ($r->created_at ? $r->created_at->format('d/m/Y') : '-');

                return [
                    'id'            => $r->id,
                    'created_at'    => $r->created_at,
                    'delivery_date' => $dcDateFormatted,
                    'dc_no'         => $r->dc_no,
                    'sale'          => $r->sale,
                    'customer'      => $r->customer,
                    'customer_name' => $r->customer->customer_name ?? 'Walk-in',
                    'customer_phone'=> $r->customer->mobile ?? '-',
                    'total_pieces'  => $totalPieces,
                    'warehouses'    => implode(', ', $whSummary),
                    'items_count'   => count($filteredItems),
                    'items_detail'  => $itemsDetail,
                ];
            });

            return ['success' => true, 'data' => $data];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchDcReport(Request $request)
    {
        $data = $this->buildDcReportData($request);
        if (isset($data['success']) && !$data['success']) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportDcExcel(Request $request)
    {
        $d = $this->buildDcReportData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $data = [['#', 'DC Date', 'DC No.', 'Invoice No.', 'Customer', 'Items Count', 'Total Pieces', 'Warehouses']];

        foreach ($d['data'] as $i => $r) {
            $data[] = [
                $i + 1,
                $r['delivery_date'] ?? '-',
                $r['dc_no'] ?? '-',
                $r['sale']->invoice_no ?? '-',
                $r['customer_name'] . ' (' . $r['customer_phone'] . ')',
                $r['items_count'],
                $r['total_pieces'],
                $r['warehouses']
            ];
            
            if (!empty($r['items_detail'])) {
                $data[] = ['', '--- Product ---', '--- Code ---', '--- UOM ---', '--- Boxes ---', '--- Pcs ---', '--- WH ---', ''];
                foreach ($r['items_detail'] as $det) {
                    $data[] = [
                        '', 
                        $det['product_name'], 
                        $det['item_code'], 
                        $det['uom_name'], 
                        $det['qty_boxes'], 
                        $det['qty_pieces'], 
                        $det['warehouse'], 
                        ''
                    ];
                }
                $data[] = ['', '', '', '', '', '', '', ''];
            }
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'DC_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportDcPdf(Request $request)
    {
        $d = $this->buildDcReportData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $filename = 'DC_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .detail-row td { background-color:#f9fafb; font-style:italic; border:none; border-bottom:1px solid #ddd; }
            .master-row { background-color:#e2e8f0; font-weight:bold; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>DELIVERY CHALLAN REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="3%">#</th>
                        <th width="12%">DC Date</th>
                        <th width="10%">DC No.</th>
                        <th width="10%">Invoice No.</th>
                        <th width="25%">Customer</th>
                        <th width="8%" class="num">Items</th>
                        <th width="12%" class="num">Total Pcs</th>
                        <th width="20%">Warehouses</th>
                    </tr>';
                    
        foreach ($d['data'] as $i => $r) {
            $html .= '<tr class="master-row">
                        <td>'.($i+1).'</td>
                        <td>'.($r['delivery_date'] ?? '-').'</td>
                        <td>'.($r['dc_no'] ?? '-').'</td>
                        <td>'.($r['sale']->invoice_no ?? '-').'</td>
                        <td>'.$r['customer_name'].' ('.$r['customer_phone'].')</td>
                        <td class="num">'.$r['items_count'].'</td>
                        <td class="num">'.$r['total_pieces'].'</td>
                        <td>'.$r['warehouses'].'</td>
                      </tr>';
                      
            if (!empty($r['items_detail'])) {
                $html .= '<tr class="detail-row"><td></td><td colspan="3" style="font-weight:bold;">Product (Code)</td><td style="font-weight:bold;">UOM</td><td class="num" style="font-weight:bold;">Boxes</td><td class="num" style="font-weight:bold;">Pcs</td><td style="font-weight:bold;">Warehouse</td></tr>';
                foreach ($r['items_detail'] as $det) {
                    $html .= '<tr class="detail-row">
                                <td></td>
                                <td colspan="3">'.$det['product_name'].' ('.$det['item_code'].')</td>
                                <td>'.$det['uom_name'].'</td>
                                <td class="num">'.number_format($det['qty_boxes']).'</td>
                                <td class="num">'.number_format($det['qty_pieces']).'</td>
                                <td>'.$det['warehouse'].'</td>
                              </tr>';
                }
            }
        }
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
    }


    // ══════════════════════════════════════════════════════════════════════════
    //  PRODUCT LEDGER REPORT
    // ══════════════════════════════════════════════════════════════════════════

    public function product_ledger_report()
    {
        \App\Models\Warehouse::ensureShopWarehousesExists();
        $products    = \App\Models\Product::orderBy('item_name')->get();
        $branchId = $this->getBranchId();

        $shops = Warehouse::where('type', 'shop')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        $warehouses = Warehouse::where('type', 'warehouse')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('warehouse_name')
            ->get();

        $categories  = \App\Models\Category::orderBy('name')->get();
        $subCategories = \App\Models\Subcategory::orderBy('name')->get();
        $brands      = \App\Models\Brand::orderBy('name')->get();
        
        $isSuperAdmin = $this->isSuperAdmin();
        $branches    = $isSuperAdmin
            ? DB::table('branches')->select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('admin_panel.reporting.product_ledger_report', 
            compact('products', 'shops', 'warehouses', 'categories', 'subCategories', 'brands', 'isSuperAdmin', 'branches'));
    }

    private function buildProductLedgerData(\Illuminate\Http\Request $request)
    {
        try {
            $productId     = $request->input('product_id');
            $categoryId    = $request->input('category_id');
            $subCategoryId = $request->input('sub_category_id');
            $brandId       = $request->input('brand_id');
            $statusFilter  = $request->input('status');
            $startDate     = $request->input('start_date');
            $endDate       = $request->input('end_date');
            $warehouseId   = $request->input('warehouse_id');
            $branchId      = $request->input('branch_id');
            
            if (!$branchId || $branchId === 'all') {
                $branchId = $this->getBranchId();
            } else {
                $branchId = (int)$branchId;
            }

            // Build Product Filter Query
            $prodQuery = DB::table('products')
                ->leftJoin('units',      'units.id',      '=', 'products.unit_id')
                ->leftJoin('brands',     'brands.id',     '=', 'products.brand_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->select(
                    'products.id', 'products.item_code', 'products.item_name',
                    'products.size_mode', 'products.pieces_per_box',
                    'products.sale_price_per_piece', 'products.sale_price_per_box',
                    'products.purchase_price_per_piece', 'products.purchase_price_per_box',
                    DB::raw('COALESCE(units.name, "pcs") as unit_name'),
                    DB::raw('COALESCE(brands.name, "-") as brand_name'),
                    DB::raw('COALESCE(categories.name, "-") as category_name')
                );

            if ($productId && $productId !== 'all') {
                if (is_array($productId)) {
                    $prodQuery->whereIn('products.id', $productId);
                } elseif (is_string($productId) && strpos($productId, ',') !== false) {
                    $ids = array_filter(array_map('intval', explode(',', $productId)));
                    $prodQuery->whereIn('products.id', $ids);
                } else {
                    $prodQuery->where('products.id', $productId);
                }
            }
            if ($categoryId && $categoryId !== 'all') {
                $prodQuery->where('products.category_id', $categoryId);
            }
            if ($subCategoryId && $subCategoryId !== 'all') {
                $prodQuery->where('products.sub_category_id', $subCategoryId);
            }
            if ($brandId && $brandId !== 'all') {
                $prodQuery->where('products.brand_id', $brandId);
            }

            $matchingProducts = $prodQuery->get();

            if ($matchingProducts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No products match the selected filters.'
                ], 404);
            }

            $productIds = $matchingProducts->pluck('id')->toArray();
            $isConsolidated = count($productIds) > 1 || (!$productId || $productId === 'all');
            
            $firstProduct = $matchingProducts->first();

            $productMeta = [
                'id'            => $isConsolidated ? null : $firstProduct->id,
                'item_code'     => $isConsolidated ? 'MULTI' : $firstProduct->item_code,
                'item_name'     => $isConsolidated ? 'Consolidated Ledger (' . count($productIds) . ' Products)' : $firstProduct->item_name,
                'brand_name'    => ($brandId && $brandId !== 'all') ? ($firstProduct->brand_name ?? '-') : ($isConsolidated ? 'Multiple Companies' : $firstProduct->brand_name),
                'category_name' => ($categoryId && $categoryId !== 'all') ? ($firstProduct->category_name ?? '-') : ($isConsolidated ? 'Multiple Categories' : $firstProduct->category_name),
                'unit_name'     => 'pcs',
                'pieces_per_box'=> 1,
            ];

            $rows = [];
            $productsData = [];

            $grandOpeningBalance = 0;
            $grandTotalQtyIn     = 0;
            $grandTotalQtyOut    = 0;
            $grandTotalSaleValue = 0;
            $grandClosingBalance = 0;

            // Iterate over each matching product to generate per-product ledger blocks
            foreach ($matchingProducts as $p) {
                $pId = $p->id;

                // ── Product Opening Balance ──────────────────────────────────────
                $purchBeforeQ = DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->leftJoin('product_batches', 'product_batches.purchase_item_id', '=', 'purchase_items.id')
                    ->where('purchase_items.product_id', $pId)
                    ->where('purchases.status_purchase', '!=', 'draft');
                if ($branchId)    $purchBeforeQ->where('purchases.branch_id', $branchId);
                if ($warehouseId) $purchBeforeQ->where('purchase_items.warehouse_id', $warehouseId);
                if ($startDate)   $purchBeforeQ->where('purchases.purchase_date', '<', $startDate);
                $purchasedBefore = (float)$purchBeforeQ->sum(
                    DB::raw("COALESCE(
                        NULLIF(product_batches.qty_received, 0),
                        NULLIF(purchase_items.boxes_qty * COALESCE(purchase_items.pieces_per_box, 1), 0),
                        NULLIF(purchase_items.qty * COALESCE(purchase_items.uom_factor, 1), 0),
                        purchase_items.qty,
                        0
                    )")
                );

                $saleBeforeQ = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $pId)
                    ->whereIn('sales.sale_status', ['posted', 'post', 'in_delivery']);
                if ($branchId)    $saleBeforeQ->where('sales.branch_id', $branchId);
                if ($warehouseId) $saleBeforeQ->where('sale_items.warehouse_id', $warehouseId);
                if ($startDate)   $saleBeforeQ->where('sales.sale_date', '<', $startDate);
                $soldBefore = (float)$saleBeforeQ->sum(DB::raw("COALESCE(NULLIF(sale_items.total_pieces, 0), sale_items.qty * " . (int)$p->pieces_per_box . ")"));

                $dcBeforeQ = DB::table('delivery_note_items')
                    ->join('delivery_notes', 'delivery_notes.id', '=', 'delivery_note_items.dc_note_id')
                    ->where('delivery_note_items.product_id', $pId);
                if ($branchId)    $dcBeforeQ->where('delivery_notes.branch_id', $branchId);
                if ($warehouseId) $dcBeforeQ->where('delivery_note_items.warehouse_id', $warehouseId);
                if ($startDate)   $dcBeforeQ->where('delivery_notes.delivery_date', '<', $startDate);
                $dcBefore = (float)$dcBeforeQ->sum(DB::raw("COALESCE(NULLIF(delivery_note_items.total_pieces, 0), delivery_note_items.qty * " . (int)$p->pieces_per_box . ")"));

                $srBeforeQ = DB::table('sale_return_items')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->where('sale_return_items.product_id', $pId)
                    ->where('sale_returns.status', 'posted');
                if ($branchId)    $srBeforeQ->where('sale_returns.branch_id', $branchId);
                if ($warehouseId) $srBeforeQ->where('sale_return_items.warehouse_id', $warehouseId);
                if ($startDate)   $srBeforeQ->where('sale_returns.return_date', '<', $startDate);
                $saleRetBefore = (float)$srBeforeQ->sum('sale_return_items.qty');

                $prBeforeQ = DB::table('purchase_return_items')
                    ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->where('purchase_return_items.product_id', $pId);
                if ($branchId)    $prBeforeQ->where('purchase_returns.branch_id', $branchId);
                if ($warehouseId) $prBeforeQ->where('purchase_return_items.warehouse_id', $warehouseId);
                if ($startDate)   $prBeforeQ->where('purchase_returns.return_date', '<', $startDate);
                $purchRetBefore = (float)$prBeforeQ->sum('purchase_return_items.qty');

                $obQ = DB::table('product_batches')
                    ->where('product_id', $pId)
                    ->where(function ($q) {
                        $q->where('source_type', 'opening_stock')
                          ->orWhereRaw("batch_number REGEXP '^[0]+$'");
                    });
                if ($branchId)    $obQ->where('branch_id', $branchId);
                if ($warehouseId) $obQ->where('warehouse_id', $warehouseId);
                if ($startDate)   $obQ->where('created_at', '<', $startDate . ' 00:00:00');
                $openingStockQty = (float)$obQ->sum('qty_remaining');

                $pOpeningBalance = $openingStockQty + $purchasedBefore - $soldBefore - $dcBefore + $saleRetBefore - $purchRetBefore;

                $pRows = [];
                $pRows[] = [
                    'sort_key'    => '0000-00-00_000',
                    'type'        => 'opening',
                    'date'        => $startDate ?? now()->format('Y-m-d'),
                    'description' => 'Opening Balance',
                    'ref'         => '-',
                    'qty_in'      => null,
                    'qty_out'     => null,
                    'sale_price'  => null,
                    'cost_price'  => null,
                    'balance'     => round($pOpeningBalance, 4),
                    'product_id'  => $pId,
                    'item_code'   => $p->item_code,
                    'item_name'   => $p->item_name,
                ];

                // ── Purchases ──
                $purchQ = DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->leftJoin('vendors', 'vendors.id', '=', 'purchases.vendor_id')
                    ->leftJoin('product_batches', 'product_batches.purchase_item_id', '=', 'purchase_items.id')
                    ->where('purchase_items.product_id', $pId)
                    ->where('purchases.status_purchase', '!=', 'draft')
                    ->select(
                        'purchases.purchase_date as date',
                        DB::raw('COALESCE(NULLIF(purchases.invoice_no, ""), purchases.po_ref, CONCAT("GRN#", purchases.id)) as ref'),
                        DB::raw('COALESCE(vendors.name, "Unknown Vendor") as party'),
                        DB::raw('COALESCE(
                            NULLIF(product_batches.qty_received, 0),
                            NULLIF(purchase_items.boxes_qty * COALESCE(purchase_items.pieces_per_box, 1), 0),
                            NULLIF(purchase_items.qty * COALESCE(purchase_items.uom_factor, 1), 0),
                            purchase_items.qty,
                            0
                        ) as qty'),
                        'purchase_items.price',
                        'purchase_items.line_total',
                        'purchase_items.batch_no',
                        'purchase_items.exp_date'
                    );
                if ($branchId)    $purchQ->where('purchases.branch_id', $branchId);
                if ($warehouseId) $purchQ->where('purchase_items.warehouse_id', $warehouseId);
                if ($startDate)   $purchQ->where('purchases.purchase_date', '>=', $startDate);
                if ($endDate)     $purchQ->where('purchases.purchase_date', '<=', $endDate);
                foreach ($purchQ->get() as $r) {
                    $batchLabel = !empty($r->batch_no) ? ' [Batch: ' . $r->batch_no . (!empty($r->exp_date) ? ', Exp: ' . $r->exp_date : '') . ']' : '';
                    $pRows[] = [
                        'sort_key'    => $r->date . '_1',
                        'type'        => 'purchase',
                        'date'        => $r->date,
                        'description' => 'Purchase GRN (' . $r->party . ')' . $batchLabel,
                        'ref'         => $r->ref,
                        'qty_in'      => (float)$r->qty,
                        'qty_out'     => null,
                        'sale_price'  => null,
                        'cost_price'  => (float)$r->price,
                        'balance'     => null,
                        'product_id'  => $pId,
                        'item_code'   => $p->item_code,
                        'item_name'   => $p->item_name,
                    ];
                }

                // ── Sales ──
                $saleQ = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
                    ->where('sale_items.product_id', $pId)
                    ->whereIn('sales.sale_status', ['posted', 'post', 'in_delivery'])
                    ->select(
                        'sales.sale_date as date',
                        DB::raw('COALESCE(NULLIF(sales.invoice_no, ""), CONCAT("SIN#", sales.id)) as ref'),
                        DB::raw('COALESCE(customers.customer_name, "Walk-in") as party'),
                        DB::raw("COALESCE(NULLIF(sale_items.total_pieces, 0), sale_items.qty * " . (int)$p->pieces_per_box . ") as qty"),
                        'sale_items.price',
                        'sale_items.total'
                    );
                if ($branchId)    $saleQ->where('sales.branch_id', $branchId);
                if ($warehouseId) $saleQ->where('sale_items.warehouse_id', $warehouseId);
                if ($startDate)   $saleQ->where('sales.sale_date', '>=', $startDate);
                if ($endDate)     $saleQ->where('sales.sale_date', '<=', $endDate);
                foreach ($saleQ->get() as $r) {
                    $pRows[] = [
                        'sort_key'    => $r->date . '_2',
                        'type'        => 'sale',
                        'date'        => $r->date,
                        'description' => 'Sale Invoice (' . $r->party . ')',
                        'ref'         => $r->ref,
                        'qty_in'      => null,
                        'qty_out'     => (float)$r->qty,
                        'sale_price'  => (float)$r->price,
                        'cost_price'  => null,
                        'balance'     => null,
                        'product_id'  => $pId,
                        'item_code'   => $p->item_code,
                        'item_name'   => $p->item_name,
                    ];
                }

                // ── Delivery Challans ──
                $dcQ = DB::table('delivery_note_items')
                    ->join('delivery_notes', 'delivery_notes.id', '=', 'delivery_note_items.dc_note_id')
                    ->leftJoin('customers', 'customers.id', '=', 'delivery_notes.customer_id')
                    ->where('delivery_note_items.product_id', $pId)
                    ->select(
                        'delivery_notes.delivery_date as date',
                        DB::raw('COALESCE(delivery_notes.dc_no, "-") as ref'),
                        DB::raw('COALESCE(customers.customer_name, "Walk-in") as party'),
                        DB::raw("COALESCE(NULLIF(delivery_note_items.total_pieces, 0), delivery_note_items.qty * " . (int)$p->pieces_per_box . ") as qty"),
                        'delivery_note_items.price',
                        'delivery_note_items.line_total'
                    );
                if ($branchId)    $dcQ->where('delivery_notes.branch_id', $branchId);
                if ($warehouseId) $dcQ->where('delivery_note_items.warehouse_id', $warehouseId);
                if ($startDate)   $dcQ->where('delivery_notes.delivery_date', '>=', $startDate);
                if ($endDate)     $dcQ->where('delivery_notes.delivery_date', '<=', $endDate);
                foreach ($dcQ->get() as $r) {
                    $pRows[] = [
                        'sort_key'    => $r->date . '_3',
                        'type'        => 'delivery_challan',
                        'date'        => $r->date,
                        'description' => 'Delivery Challan (' . $r->party . ')',
                        'ref'         => $r->ref,
                        'qty_in'      => null,
                        'qty_out'     => (float)$r->qty,
                        'sale_price'  => (float)$r->price,
                        'cost_price'  => null,
                        'balance'     => null,
                        'product_id'  => $pId,
                        'item_code'   => $p->item_code,
                        'item_name'   => $p->item_name,
                    ];
                }

                // ── Sale Returns ──
                $srQ = DB::table('sale_return_items')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->leftJoin('customers', 'customers.id', '=', 'sale_returns.customer_id')
                    ->where('sale_return_items.product_id', $pId)
                    ->where('sale_returns.status', 'posted')
                    ->select(
                        'sale_returns.return_date as date',
                        DB::raw('COALESCE(sale_returns.return_invoice, "-") as ref'),
                        DB::raw('COALESCE(customers.customer_name, "Walk-in") as party'),
                        'sale_return_items.qty',
                        'sale_return_items.price'
                    );
                if ($branchId)    $srQ->where('sale_returns.branch_id', $branchId);
                if ($warehouseId) $srQ->where('sale_return_items.warehouse_id', $warehouseId);
                if ($startDate)   $srQ->where('sale_returns.return_date', '>=', $startDate);
                if ($endDate)     $srQ->where('sale_returns.return_date', '<=', $endDate);
                foreach ($srQ->get() as $r) {
                    $pRows[] = [
                        'sort_key'    => $r->date . '_4',
                        'type'        => 'sale_return',
                        'date'        => $r->date,
                        'description' => 'Sale Return (' . $r->party . ')',
                        'ref'         => $r->ref,
                        'qty_in'      => (float)$r->qty,
                        'qty_out'     => null,
                        'sale_price'  => (float)$r->price,
                        'cost_price'  => null,
                        'balance'     => null,
                        'product_id'  => $pId,
                        'item_code'   => $p->item_code,
                        'item_name'   => $p->item_name,
                    ];
                }

                // ── Purchase Returns ──
                $prQ = DB::table('purchase_return_items')
                    ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->leftJoin('vendors', 'vendors.id', '=', 'purchase_returns.vendor_id')
                    ->where('purchase_return_items.product_id', $pId)
                    ->select(
                        'purchase_returns.return_date as date',
                        DB::raw('COALESCE(purchase_returns.return_invoice, "-") as ref'),
                        DB::raw('COALESCE(vendors.name, "Unknown Vendor") as party'),
                        'purchase_return_items.qty',
                        'purchase_return_items.price'
                    );
                if ($branchId)    $prQ->where('purchase_returns.branch_id', $branchId);
                if ($warehouseId) $prQ->where('purchase_return_items.warehouse_id', $warehouseId);
                if ($startDate)   $prQ->where('purchase_returns.return_date', '>=', $startDate);
                if ($endDate)     $prQ->where('purchase_returns.return_date', '<=', $endDate);
                foreach ($prQ->get() as $r) {
                    $pRows[] = [
                        'sort_key'    => $r->date . '_5',
                        'type'        => 'purchase_return',
                        'date'        => $r->date,
                        'description' => 'Purchase Return (' . $r->party . ')',
                        'ref'         => $r->ref,
                        'qty_in'      => null,
                        'qty_out'     => (float)$r->qty,
                        'sale_price'  => null,
                        'cost_price'  => (float)$r->price,
                        'balance'     => null,
                        'product_id'  => $pId,
                        'item_code'   => $p->item_code,
                        'item_name'   => $p->item_name,
                    ];
                }

                // Skip products that have NO opening balance AND NO transactions
                $txCount = count($pRows) - 1; // subtract opening balance row
                if ($pOpeningBalance == 0 && $txCount == 0) {
                    continue;
                }

                // Sort product rows chronologically
                usort($pRows, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

                // Calculate product running balance
                $pBalance        = 0;
                $pQtyIn         = 0;
                $pQtyOut        = 0;
                $pSaleValue     = 0;

                foreach ($pRows as &$row) {
                    if ($row['type'] === 'opening') {
                        $pBalance = $row['balance'];
                    } else {
                        $in  = (float)($row['qty_in']  ?? 0);
                        $out = (float)($row['qty_out'] ?? 0);
                        $pBalance        += $in - $out;
                        $row['balance']   = round($pBalance, 4);
                        $pQtyIn          += $in;
                        $pQtyOut         += $out;
                        if (!empty($row['sale_price']) && $out > 0) {
                            $pSaleValue  += $row['sale_price'] * $out;
                        }
                    }
                    $rows[] = $row; // Accumulate into flat rows array as well
                }
                unset($row);

                $productsData[] = [
                    'product' => [
                        'id'            => $p->id,
                        'item_code'     => $p->item_code,
                        'item_name'     => $p->item_name,
                        'brand_name'    => $p->brand_name ?? '-',
                        'category_name' => $p->category_name ?? '-',
                        'unit_name'     => $p->unit_name ?? 'pcs',
                        'pieces_per_box'=> $p->pieces_per_box ?? 1,
                    ],
                    'opening_balance'  => round($pOpeningBalance, 4),
                    'total_qty_in'     => $pQtyIn,
                    'total_qty_out'    => $pQtyOut,
                    'closing_balance'  => round($pBalance, 4),
                    'total_sale_value' => round($pSaleValue, 2),
                    'rows'             => $pRows,
                ];

                $grandOpeningBalance += $pOpeningBalance;
                $grandTotalQtyIn     += $pQtyIn;
                $grandTotalQtyOut    += $pQtyOut;
                $grandTotalSaleValue += $pSaleValue;
                $grandClosingBalance += $pBalance;
            }

            // Sort overall flat rows chronologically
            usort($rows, fn($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

            return [
                'success'         => true,
                'is_consolidated' => $isConsolidated,
                'product_count'   => count($productsData),
                'products_data'   => $productsData,
                'rows'            => $rows,
                'summary'         => [
                    'product'          => $productMeta,
                    'opening_balance'  => round($grandOpeningBalance, 4),
                    'total_qty_in'     => $grandTotalQtyIn,
                    'total_qty_out'    => $grandTotalQtyOut,
                    'closing_balance'  => round($grandClosingBalance, 4),
                    'total_sale_value' => round($grandTotalSaleValue, 2),
                    'period_start'     => $startDate,
                    'period_end'       => $endDate,
                ],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function fetchProductLedger(\Illuminate\Http\Request $request)
    {
        $data = $this->buildProductLedgerData($request);
        if (isset($data['success']) && !$data['success']) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportProductLedgerExcel(\Illuminate\Http\Request $request)
    {
        $d = $this->buildProductLedgerData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);
        
        $data = [['Date', 'Ref', 'Description', 'Qty In', 'Qty Out', 'Balance']];
        $data[] = ['','','Opening Balance','','',$d['summary']['opening_balance']];
        foreach ($d['rows'] as $r) {
            if ($r['type'] === 'opening') continue;
            $data[] = [$r['date'], $r['ref'], $r['description'], $r['qty_in'] ?? '-', $r['qty_out'] ?? '-', $r['balance']];
        }
        $data[] = ['','','Closing Balance',$d['summary']['total_qty_in'],$d['summary']['total_qty_out'],$d['summary']['closing_balance']];

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Product_Ledger_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportProductLedgerPdf(\Illuminate\Http\Request $request)
    {
        $d = $this->buildProductLedgerData($request);
        if (isset($d['success']) && !$d['success']) return response()->json($d, 500);

        $filename = 'Product_Ledger_' . now()->format('Y-m-d') . '.pdf';
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
            h2 { text-align:center; } table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:4px; text-align:left; }
            th { background-color:#f2f2f2; }
            .num { text-align:right; }
        </style></head><body>';
        $pName = $d['summary']['product'] ? $d['summary']['product']->item_name : 'All Products';
        $html .= '<h2>Product Ledger: ' . $pName . '</h2>';
        $html .= '<p>Period: ' . \Carbon\Carbon::parse($d['summary']['period_start'])->format('d/m/Y') . ' to ' . \Carbon\Carbon::parse($d['summary']['period_end'])->format('d/m/Y') . '</p>';
        $html .= '<table><tr><th>Date</th><th>Ref</th><th>Description</th><th class="num">Qty In</th><th class="num">Qty Out</th><th class="num">Balance</th></tr>';
        $html .= '<tr><td colspan="5">Opening Balance</td><td class="num">'.number_format($d['summary']['opening_balance'],4).'</td></tr>';
        
        foreach ($d['rows'] as $t) {
            if ($t['type'] === 'opening') continue;
            $html .= '<tr><td>'.$t['date'].'</td><td>'.$t['ref'].'</td><td>'.$t['description'].'</td><td class="num">'.($t['qty_in'] ? number_format($t['qty_in'],2) : '-').'</td><td class="num">'.($t['qty_out'] ? number_format($t['qty_out'],2) : '-').'</td><td class="num">'.number_format($t['balance'],4).'</td></tr>';
        }
        $html .= '<tr><td colspan="3"><b>Totals</b></td><td class="num"><b>'.number_format($d['summary']['total_qty_in'],2).'</b></td><td class="num"><b>'.number_format($d['summary']['total_qty_out'],2).'</b></td><td class="num"><b>'.number_format($d['summary']['closing_balance'],4).'</b></td></tr>';
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
    }

    public function voucher_report()
    {
        $branchId  = $this->getBranchId();

        $customers = DB::table('customers')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'customer_name')
            ->orderBy('customer_name')
            ->get();

        $vendors = DB::table('vendors')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $products = DB::table('products')
            ->select('id', 'item_code', 'item_name')
            ->orderBy('item_name')
            ->get();

        $isSuperAdmin = $this->isSuperAdmin();
        $branches    = $isSuperAdmin
            ? DB::table('branches')->select('id', 'name')->orderBy('name')->get()
            : collect();

        return view('admin_panel.reporting.voucher_report',
            compact('customers', 'vendors', 'products', 'isSuperAdmin', 'branches'));
    }

    private function buildVoucherData(Request $request)
    {
        $this->repairMissingExpenseVouchersDetails();
        try {
            $startDate   = $request->get('start_date');
            $endDate     = $request->get('end_date');
            $month       = $request->get('month');
            $year        = $request->get('year');
            $partyType   = $request->get('party_type');
            $customerId  = $request->get('customer_id');
            $vendorId    = $request->get('vendor_id');
            $productId    = $request->get('product_id');
            $voucherType = $request->get('voucher_type');
            $status      = $request->get('status');
            $branchId    = $request->get('branch_id');
            $headId      = $request->get('head_id');

            // Apply Branch Scoping
            $sessionBranchId = $this->getBranchId();
            if ($sessionBranchId) {
                $branchId = $sessionBranchId;
            }

            \Log::info('VoucherReport Debug', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'branch_id_final' => $branchId,
                'session_branch' => $sessionBranchId,
                'party_type' => $partyType,
                'customer_id' => $customerId,
                'vendor_id' => $vendorId,
                'voucher_type' => $voucherType,
                'status' => $status,
                'head_id' => $headId,
                'total_in_table' => DB::table('voucher_masters')->count(),
            ]);

            $query = DB::table('voucher_masters');

            // 1. Branch scoping
            if ($branchId && $branchId !== 'all') {
                if ($branchId == 1) {
                    $query->where(function($q) use ($branchId) {
                        $q->where('branch_id', $branchId)
                          ->orWhereNull('branch_id')
                          ->orWhere('branch_id', 0)
                          ->orWhere('branch_id', '');
                    });
                } else {
                    $query->where('branch_id', $branchId);
                }
            }

            // 2. Date/Month/Year filters
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            } elseif ($month && $year && $month !== 'all' && $year !== 'all') {
                $query->whereMonth('date', $month)->whereYear('date', $year);
            } elseif ($year && $year !== 'all') {
                $query->whereYear('date', $year);
            }

            // 3. Voucher Type filter
            if ($voucherType && $voucherType !== 'all') {
                $query->where('voucher_type', $voucherType);
            }

            // 4. Status filter
            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            // 5. Party / Customer / Vendor filter
            if ($partyType === 'customer') {
                $query->where('party_type', 'App\Models\Customer');
                if ($customerId && $customerId !== 'all') {
                    $query->where('party_id', $customerId);
                }
            } elseif ($partyType === 'vendor') {
                $query->where('party_type', 'App\Models\Vendor');
                if ($vendorId && $vendorId !== 'all') {
                    $query->where('party_id', $vendorId);
                }
            }

            // 5b. Account Head filter
            if ($headId && $headId !== 'all') {
                $query->whereExists(function ($q) use ($headId) {
                    $q->select(DB::raw(1))
                      ->from('voucher_details')
                      ->join('accounts', 'accounts.id', '=', 'voucher_details.account_id')
                      ->whereColumn('voucher_details.voucher_master_id', 'voucher_masters.id')
                      ->where('accounts.head_id', $headId);
                });
            }

            // 7. Product filter
            if ($productId && $productId !== 'all') {
                // Get all sale invoices containing the product
                $sales = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $productId)
                    ->select('sales.invoice_no', 'sales.customer_id')
                    ->get();
                $saleInvoices = $sales->pluck('invoice_no')->filter()->unique()->toArray();
                $customerIds = $sales->pluck('customer_id')->filter()->unique()->toArray();

                // Get all purchase invoices containing the product
                $purchases = DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchase_items.product_id', $productId)
                    ->select('purchases.invoice_no', 'purchases.vendor_id')
                    ->get();
                $purchaseInvoices = $purchases->pluck('invoice_no')->filter()->unique()->toArray();
                $vendorIds = $purchases->pluck('vendor_id')->filter()->unique()->toArray();

                $allInvoices = array_merge($saleInvoices, $purchaseInvoices);

                $query->where(function($q) use ($allInvoices, $customerIds, $vendorIds) {
                    if (!empty($allInvoices)) {
                        foreach ($allInvoices as $inv) {
                            $q->orWhere('remarks', 'like', "%{$inv}%");
                        }
                    }
                    if (!empty($customerIds)) {
                        $q->orWhere(function($sub) use ($customerIds) {
                            $sub->where('party_type', 'App\Models\Customer')
                                ->whereIn('party_id', $customerIds);
                        });
                    }
                    if (!empty($vendorIds)) {
                        $q->orWhere(function($sub) use ($vendorIds) {
                            $sub->where('party_type', 'App\Models\Vendor')
                                ->whereIn('party_id', $vendorIds);
                        });
                    }
                });
            }

            $vouchers = $query->orderBy('date', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            $voucherIds = $vouchers->pluck('id')->toArray();

            // Fetch Voucher Details & Accounts
            $detailsMap = DB::table('voucher_details')
                ->join('accounts', 'accounts.id', '=', 'voucher_details.account_id')
                ->whereIn('voucher_details.voucher_master_id', $voucherIds)
                ->select('voucher_details.*', 'accounts.title as account_title')
                ->get()
                ->groupBy('voucher_master_id');

            // Fetch users for creator info
            $usersMap = DB::table('users')->pluck('name', 'id');

            // Fetch customer and vendor names for resolving parties
            $customersMap = DB::table('customers')->pluck('customer_name', 'id');
            $vendorsMap = DB::table('vendors')->pluck('name', 'id');
            $accountsMap = DB::table('accounts')->pluck('title', 'id');

            $rows = [];
            $totalAmount = 0;
            $totalReceipts = 0;
            $totalPayments = 0;
            $totalExpenses = 0;
            $totalJournals = 0;
            $totalContras = 0;

            foreach ($vouchers as $v) {
                $partyName = '-';
                if ($v->party_type && $v->party_id) {
                    if ($v->party_type === 'App\Models\Customer') {
                        $partyName = $customersMap->get($v->party_id) ?? '-';
                    } elseif ($v->party_type === 'App\Models\Vendor') {
                        $partyName = $vendorsMap->get($v->party_id) ?? '-';
                    } elseif ($v->party_type === 'App\Models\Account') {
                        $partyName = $accountsMap->get($v->party_id) ?? '-';
                    } else {
                        $partyName = class_basename($v->party_type) . ' #' . $v->party_id;
                    }
                }

                $amt = (float)$v->total_amount;
                $totalAmount += $amt;

                if ($v->voucher_type === 'receipt') {
                    $totalReceipts += $amt;
                } elseif ($v->voucher_type === 'payment') {
                    $totalPayments += $amt;
                } elseif ($v->voucher_type === 'expense') {
                    $totalExpenses += $amt;
                } elseif ($v->voucher_type === 'journal') {
                    $totalJournals += $amt;
                } elseif ($v->voucher_type === 'contra') {
                    $totalContras += $amt;
                }

                $vDetails = $detailsMap->get($v->id, collect());

                $rows[] = [
                    'id' => $v->id,
                    'voucher_no' => $v->voucher_no,
                    'voucher_type' => $v->voucher_type,
                    'date' => \Carbon\Carbon::parse($v->date)->format('d/m/Y'),
                    'status' => $v->status,
                    'remarks' => $v->remarks ?? '-',
                    'total_amount' => $amt,
                    'party_name' => $partyName,
                    'party_type' => $v->party_type ? class_basename($v->party_type) : '-',
                    'created_by' => $usersMap->get($v->created_by) ?? '-',
                    'details' => $vDetails->map(fn($d) => [
                        'account_title' => $d->account_title,
                        'debit' => (float)$d->debit,
                        'credit' => (float)$d->credit,
                        'narration' => $d->narration ?? '-',
                    ])->values()->toArray(),
                ];
            }

            return [
                'data' => $rows,
                'summary' => [
                    'total_count' => count($rows),
                    'total_amount' => round($totalAmount, 2),
                    'total_receipts' => round($totalReceipts, 2),
                    'total_payments' => round($totalPayments, 2),
                    'total_expenses' => round($totalExpenses, 2),
                    'total_journals' => round($totalJournals, 2),
                    'total_contras' => round($totalContras, 2),
                    'draft_count' => collect($rows)->where('status', 'draft')->count(),
                    'posted_count' => collect($rows)->where('status', 'posted')->count(),
                    'cancelled_count' => collect($rows)->where('status', 'cancelled')->count(),
                ]
            ];

        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        }
    }

    public function fetchVoucherReport(Request $request)
    {
        $data = $this->buildVoucherData($request);
        if (isset($data['error'])) return response()->json($data, 500);
        return response()->json($data);
    }

    public function exportVoucherExcel(Request $request)
    {
        $d = $this->buildVoucherData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $data = [['#', 'Date', 'Voucher No', 'Type', 'Party', 'Status', 'Total Amount', 'Remarks']];

        foreach ($d['data'] as $i => $v) {
            $data[] = [
                $i + 1,
                $v['date'] ?? '-',
                $v['voucher_no'] ?? '-',
                strtoupper(str_replace('_', ' ', $v['voucher_type'] ?? '-')),
                ($v['party_name'] ?? '-') . ($v['party_type'] && $v['party_type'] !== '-' ? ' (' . $v['party_type'] . ')' : ''),
                strtoupper($v['status'] ?? '-'),
                $v['total_amount'] ?? 0,
                $v['remarks'] ?? '-',
            ];
            
            // Add details rows if needed, but for simple export we can keep it master level or include details below
            // Let's include details under the master row
            if (!empty($v['details'])) {
                $data[] = ['', '--- Account ---', '--- Narration ---', '--- Debit ---', '--- Credit ---', '', '', ''];
                foreach ($v['details'] as $det) {
                    $data[] = [
                        '', 
                        $det['account_title'], 
                        $det['narration'], 
                        $det['debit'], 
                        $det['credit'], 
                        '', '', ''
                    ];
                }
                $data[] = ['', '', '', '', '', '', '', ''];
            }
        }

        $xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);
        $filename = 'Voucher_Report_' . now()->format('Y-m-d') . '.xlsx';
        return response((string) $xlsx, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportVoucherPdf(Request $request)
    {
        $d = $this->buildVoucherData($request);
        if (isset($d['error'])) return response()->json($d, 500);

        $filename = 'Voucher_Report_' . now()->format('Y-m-d') . '.pdf';
        
        $html = '<html><head><style>
            body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
            h2 { text-align:center; font-size:14px; margin-bottom:5px; } 
            h3 { text-align:center; font-size:10px; margin-top:0; font-weight:normal; }
            table { width:100%; border-collapse:collapse; margin-bottom:10px; }
            th, td { border:1px solid #ccc; padding:3px 4px; text-align:left; }
            th { background-color:#d9e2f3; color:#1f3864; font-weight:bold; }
            .num { text-align:right; }
            .detail-row td { background-color:#f9fafb; font-style:italic; border:none; border-bottom:1px solid #ddd; }
            .master-row { background-color:#e2e8f0; font-weight:bold; }
        </style></head><body>';
        
        $html .= '<h2>THREE STARS MEDICAL SUPPLIES</h2>';
        $html .= '<h3>VOUCHER REPORT | Period: ' . ($request->start_date ?: 'All') . ' to ' . ($request->end_date ?: 'All') . '</h3>';
        
        $html .= '<table>
                    <tr>
                        <th width="5%">#</th>
                        <th width="10%">Date</th>
                        <th width="12%">Voucher No</th>
                        <th width="10%">Type</th>
                        <th width="20%">Party</th>
                        <th width="10%">Status</th>
                        <th width="15%" class="num">Total Amt</th>
                        <th width="18%">Remarks</th>
                    </tr>';
                    
        foreach ($d['data'] as $i => $v) {
            $html .= '<tr class="master-row">
                        <td>'.($i+1).'</td>
                        <td>'.($v['date'] ?? '-').'</td>
                        <td>'.($v['voucher_no'] ?? '-').'</td>
                        <td>'.strtoupper(str_replace('_', ' ', $v['voucher_type'] ?? '-')).'</td>
                        <td>'.($v['party_name'] ?? '-').'</td>
                        <td>'.strtoupper($v['status'] ?? '-').'</td>
                        <td class="num">'.number_format($v['total_amount'] ?? 0, 2).'</td>
                        <td>'.($v['remarks'] ?? '-').'</td>
                      </tr>';
                      
            if (!empty($v['details'])) {
                $html .= '<tr class="detail-row"><td colspan="2"></td><td colspan="2" style="font-weight:bold;">Account</td><td colspan="2" style="font-weight:bold;">Narration</td><td class="num" style="font-weight:bold;">Debit</td><td class="num" style="font-weight:bold;">Credit</td></tr>';
                foreach ($v['details'] as $det) {
                    $html .= '<tr class="detail-row">
                                <td colspan="2"></td>
                                <td colspan="2">'.$det['account_title'].'</td>
                                <td colspan="2">'.$det['narration'].'</td>
                                <td class="num">'.number_format($det['debit'], 2).'</td>
                                <td class="num">'.number_format($det['credit'], 2).'</td>
                              </tr>';
                }
            }
        }
        
        $html .= '</table></body></html>';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        return $pdf->download($filename);
    }
    public function getVoucherHeads(Request $request)
    {
        $this->repairMissingExpenseVouchersDetails();
        try {
            $voucherType = $request->get('voucher_type');
            $branchId    = $this->getBranchId();

            $query = DB::table('account_heads')
                ->select('account_heads.id', 'account_heads.name')
                ->join('accounts', 'accounts.head_id', '=', 'account_heads.id')
                ->join('voucher_details', 'voucher_details.account_id', '=', 'accounts.id')
                ->join('voucher_masters', 'voucher_masters.id', '=', 'voucher_details.voucher_master_id')
                ->distinct();

            if ($branchId && $branchId !== 'all') {
                if ($branchId == 1) {
                    $query->where(function($q) use ($branchId) {
                        $q->where('voucher_masters.branch_id', $branchId)
                          ->orWhereNull('voucher_masters.branch_id')
                          ->orWhere('voucher_masters.branch_id', 0)
                          ->orWhere('voucher_masters.branch_id', '');
                    });
                } else {
                    $query->where('voucher_masters.branch_id', $branchId);
                }
            }

            if ($voucherType && $voucherType !== 'all') {
                $query->where('voucher_masters.voucher_type', $voucherType);
            }

            $heads = $query->orderBy('account_heads.name')->get();

            // Fallback: If no vouchers recorded yet, return all account heads of the branch/system
            if ($heads->isEmpty()) {
                $fallbackQuery = DB::table('account_heads')->select('id', 'name');

                if ($branchId && $branchId !== 'all') {
                    if ($branchId == 1) {
                        $fallbackQuery->where(function($q) use ($branchId) {
                            $q->where('branch_id', $branchId)
                              ->orWhereNull('branch_id')
                              ->orWhere('branch_id', 0)
                              ->orWhere('branch_id', '');
                        });
                    } else {
                        $fallbackQuery->where('branch_id', $branchId);
                    }
                }

                $heads = $fallbackQuery->orderBy('name')->get();
            }

            return response()->json([
                'success' => true,
                'heads'   => $heads
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function repairMissingExpenseVouchersDetails()
    {
        try {
            $masters = DB::table('voucher_masters')
                ->where('voucher_type', 'expense')
                ->get();

            foreach ($masters as $master) {
                // Check if details exist
                $hasDetails = DB::table('voucher_details')
                    ->where('voucher_master_id', $master->id)
                    ->exists();

                if (!$hasDetails) {
                    // Find matching legacy expense voucher
                    $legacy = DB::table('expense_vouchers')
                        ->where('evid', $master->voucher_no)
                        ->first();

                    if ($legacy) {
                        // Reconstruct creditAccountId and partyType
                        $creditAccountId = null;
                        $partyType = null;
                        $partyId = $legacy->party_id;

                        if ($legacy->type === 'vendor') {
                            $balanceService = app(\App\Services\BalanceService::class);
                            $creditAccountId = $balanceService->getAccountsPayableId();
                            $partyType = \App\Models\Vendor::class;
                        } elseif ($legacy->type === 'customer') {
                            $balanceService = app(\App\Services\BalanceService::class);
                            $creditAccountId = $balanceService->getAccountsReceivableId();
                            $partyType = \App\Models\Customer::class;
                        } else {
                            $creditAccountId = (int) $legacy->party_id;
                            $partyType = \App\Models\Account::class;
                        }

                        // Update voucher_masters with party details if empty
                        if (empty($master->party_type) || empty($master->party_id)) {
                            DB::table('voucher_masters')
                                ->where('id', $master->id)
                                ->update([
                                    'party_type' => $partyType,
                                    'party_id'   => $partyId,
                                ]);
                        }

                        // Write Credit Detail
                        if ($creditAccountId) {
                            DB::table('voucher_details')->insert([
                                'voucher_master_id' => $master->id,
                                'account_id'        => $creditAccountId,
                                'debit'             => 0,
                                'credit'            => $legacy->total_amount ?? 0,
                                'narration'         => $master->remarks ?? 'Expense Credit Source',
                                'created_at'        => $master->created_at ?? now(),
                                'updated_at'        => $master->updated_at ?? now(),
                            ]);
                        }

                        // Write Debit Details
                        $rowAccountIds = json_decode($legacy->row_account_id, true);
                        $amounts = json_decode($legacy->amount, true);

                        if (is_array($rowAccountIds) && is_array($amounts)) {
                            foreach ($rowAccountIds as $index => $accId) {
                                $rowAmount = isset($amounts[$index]) ? (float) $amounts[$index] : 0;
                                if ($rowAmount > 0 && $accId) {
                                    DB::table('voucher_details')->insert([
                                        'voucher_master_id' => $master->id,
                                        'account_id'        => $accId,
                                        'debit'             => $rowAmount,
                                        'credit'            => 0,
                                        'narration'         => $master->remarks ?? 'Expense Row Detail',
                                        'created_at'        => $master->created_at ?? now(),
                                        'updated_at'        => $master->updated_at ?? now(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Repair Expense Vouchers Details failed: ' . $e->getMessage());
        }
    }

}
