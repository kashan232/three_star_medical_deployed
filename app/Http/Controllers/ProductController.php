<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\PriceLog;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;

class ProductController extends Controller
{
    public function getPrice(Request $request)
    {
        $product = Product::find($request->product_id);

        if (! $product) {
            return response()->json(['retail_price' => 0]);
        }

        // Standardizing to per-piece pricing
        $price = $product->sale_price_per_piece ?? 0;

        return response()->json([
            'retail_price' => $price,
            'size_mode' => $product->size_mode,
            'pieces_per_box' => $product->pieces_per_box,
            'price_per_m2' => $product->price_per_m2,
            'sale_price_per_box' => $product->sale_price_per_box,
            'sale_price_per_piece' => $product->sale_price_per_piece,
            'purchase_price_per_piece' => $product->purchase_price_per_piece,
            'height' => $product->height,
            'width' => $product->width,
            'item_code' => $product->item_code,
        ]);
    }

    public function productget()
    {
        $products = Product::all();

        return response()->json($products);
    }

    // NOTE: Stock adjustments via the product form have been removed.
    // All stock changes must go through Purchase (GRN) or Sale transactions.
    // Use StockService::credit() / StockService::debit() only.


    public function getDetails($id)
    {
        $user = auth()->user();
        $branchId = $user->getBranchId() ?: 1;

        $product = Product::with(['packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($user) {
                if (!$user->hasRole('Super Admin')) {
                    $q->whereHas('warehouse', function ($wh) use ($user) {
                        $wh->where('branch_id', $user->branch_id);
                    });
                }
            }], 'total_pieces')
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1)
            ])
            ->findOrFail($id);

        return response()->json($product);
    }

    // ===== High Performance Select2 Search (Ajax) =====
    public function ajaxSearch(Request $request)
    {
        $term = $request->get('term') ?? $request->get('q') ?? '';

        $user = auth()->user();
        $branchId = $user->getBranchId() ?: ($request->branch_id ?: 1);
        $query = Product::query()
            ->select('id', 'item_name', 'item_code', 'barcode_path', 'size_mode', 'height', 'width', 'pieces_per_box', 'purchase_price_per_m2', 'purchase_price_per_piece', 'pieces_per_m2', 'hs_code')
            ->select('products.*')
            ->with(['packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($user) {
                if (!$user->hasRole('Super Admin')) {
                    $q->whereHas('warehouse', function ($wh) use ($user) {
                        $wh->where('branch_id', $user->branch_id);
                    });
                }
            }], 'total_pieces') /* Sum PIECES, not boxes */
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1),
                'last_purchased_date' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->whereNull('purchases.deleted_at')
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchases.purchase_date')
                    ->limit(1)
            ])
            ->where(function ($q) use ($term) {
                $q->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhere('barcode_path', 'like', "%{$term}%");
            });

        $products = $query->paginate(10); // Lazy loading (10 per request)

        $results = $products->map(function ($p) use ($branchId) {
            // Get total pieces from warehouse stocks
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            // Global Stock Display: Pieces only (as requested)
            $stockDisplay = $stockPieces . " Pcs";

            return [
                'id' => $p->id,
                'text' => $p->item_name." (SKU: {$p->item_code})", // Enhanced text for selection
                // Custom attributes for template
                'sku' => $p->item_code ?? '',
                'hs_code' => $p->hs_code ?? '',
                'stock' => $stockDisplay,
                'stock_pieces' => $stockPieces, // Raw pieces for validation
                'name' => $p->item_name,
                'size_mode' => $p->size_mode,
                'pieces_per_box' => $ppb,
                'ppb' => $ppb, // Legacy
                'trade_price' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_piece ?? 0),
                'purchase_price_per_m2' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_m2 ?? 0),
                'purchase_price_per_piece' => ($p->purchase_price_per_piece ?? 0) ?: floatval($p->last_purchased_price),
                'purchase_price_per_box' => ($p->purchase_price_per_piece ?? 0) * $ppb,
                'sale_price_per_piece' => $p->sale_price_per_piece ?? 0,
                'sale_price_per_box' => ($p->sale_price_per_piece ?? 0) * $ppb,
                'retail_price' => $p->sale_price_per_piece ?? 0,
                'uom_name' => $p->unit->name ?? 'Piece',
                'debug_last_price' => $p->last_purchased_price,
                'debug_last_date' => $p->last_purchased_date,
                'debug_branch_used' => $branchId,
                'height' => $p->height ?? 0,
                'length' => $p->height ?? 0, // Alias for purchase snapshot
                'width' => $p->width ?? 0,
                'pieces_per_m2' => $p->pieces_per_m2 ?? 0,
                'packings' => $p->packings->map(function($pkg) {
                    return [
                        'id' => $pkg->id,
                        'name' => $pkg->name,
                        'pieces_per_box' => $pkg->pieces_per_box,
                        'purchase_price' => $pkg->purchase_price, // Per piece
                        'sale_price' => $pkg->sale_price,       // Per piece
                    ];
                }),
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $products->hasMorePages()],
        ]);
    }

    public function getProductFilters()
    {
        return response()->json([
            'categories' => \App\Models\Category::orderBy('name')->get(['id', 'name']),
            'brands'     => \App\Models\Brand::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');

        $user = auth()->user();
        $branchId = $user->getBranchId() ?: ($request->branch_id ?: 1);

        $products = Product::query()
            ->with(['category_relation', 'sub_category_relation', 'brand', 'packings', 'unit'])
            ->withSum(['warehouseStocks' => function ($q) use ($user) {
                if (!$user->hasRole('Super Admin')) {
                    $q->whereHas('warehouse', function ($wh) use ($user) {
                        $wh->where('branch_id', $user->branch_id);
                    });
                }
            }], 'total_pieces')
            ->addSelect([
                'last_purchased_price' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchase_items.price')
                    ->limit(1),
                'last_purchased_date' => \DB::table('purchase_items')
                    ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
                    ->whereRaw('purchase_items.product_id = products.id')
                    ->where('purchases.branch_id', $branchId)
                    ->where('purchase_items.price', '>', 0)
                    ->orderByDesc('purchases.purchase_date')
                    ->orderByDesc('purchase_items.id')
                    ->select('purchases.purchase_date')
                    ->limit(1)
            ])
            ->when($term, function ($query) use ($term) {
                $query->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhereHas('category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('sub_category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->sub_category_id, fn ($q) => $q->where('sub_category_id', $request->sub_category_id))
            ->when($request->brand_id, fn ($q) => $q->where('brand_id', $request->brand_id))
            ->paginate($request->get('per_page', 25));

        return response()->json([
            'results' => $products->map(function ($p, $key) use ($branchId) {
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);

            // Global Stock Display: Pieces only (as requested)
            $stockDisplay = $stockPieces . " Pcs";
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            return [
                'id' => $p->id,
                'item_code' => $p->item_code,
                'hs_code' => $p->hs_code ?? '',
                'item_name' => $p->item_name,
                'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                'category_name' => $p->category_relation->name ?? '-',
                'sub_category_name' => $p->sub_category_relation->name ?? '-',
                'height' => $p->height ?? null,
                'width' => $p->width ?? null,
                'pieces_per_box' => $ppb,
                'size_mode' => $p->size_mode,
                'stock' => $stockDisplay,
                'trade_price' => floatval($p->last_purchased_price) ?: ($p->purchase_price_per_piece ?? 0),
                'purchase_price_per_piece' => ($p->purchase_price_per_piece ?? 0) ?: floatval($p->last_purchased_price),
                'purchase_price_per_box' => ($p->purchase_price_per_piece ?? 0) * $ppb,
                'sale_price_per_piece' => $p->sale_price_per_piece ?? 0,
                'sale_price_per_box' => ($p->sale_price_per_piece ?? 0) * $ppb,
                'retail_price' => $p->sale_price_per_piece ?? 0,
                'debug_last_price' => $p->last_purchased_price,
                'debug_last_date' => $p->last_purchased_date,
                'debug_branch_used' => $branchId,
                'total_m2' => number_format($p->total_m2 ?? 0, 2),
                'price_per_m2' => number_format($p->price_per_m2 ?? 0, 2),
                'total_price' => number_format($p->total_price ?? 0, 2),
                'brand_name' => $p->brand->name ?? '-',
                'uom_name' => $p->unit->name ?? 'Piece',
                'packings' => $p->packings->map(function($pkg) {
                    return [
                        'id' => $pkg->id,
                        'name' => $pkg->name,
                        'pieces_per_box' => $pkg->pieces_per_box,
                        'purchase_price' => $pkg->purchase_price, // Per piece
                        'sale_price' => $pkg->sale_price,       // Per piece
                    ];
                }),
            ];
        }),
            'pagination' => [
                'more'         => $products->hasMorePages(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
            ],
        ]);
    }

    // ===== Get warehouses that have stock for a product =====
    public function getProductWarehouses($id)
    {
        $user = auth()->user();
        $product = \App\Models\Product::find($id);
        
        if (!$product) {
            return response()->json([]);
        }

        $ppb = (float) ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1);
        $sizeMode = $product->size_mode ?? 'by_pieces';

        $stocks = \App\Models\WarehouseStock::with('warehouse')
            ->where('product_id', $id)
            ->when(!$request->has('include_empty'), fn($q) => $q->where('total_pieces', '>', 0))
            ->when(!$user->hasRole('Super Admin'), function ($q) use ($user) {
                $q->whereHas('warehouse', fn($w) => $w->where('branch_id', $user->branch_id));
            })
            ->get();

        $warehouses = $stocks->map(function ($s) use ($ppb, $sizeMode) {
            $totalPieces = (float) $s->total_pieces;
            
            // Format stock display as total pieces (User requested pieces only)
            $disp = $totalPieces . " Pcs";

            return [
                'id'             => $s->warehouse_id,
                'name'           => $s->warehouse->warehouse_name ?? 'Warehouse #' . $s->warehouse_id,
                'total_pieces'   => $totalPieces,
                'stock_display'  => $disp,
                'ppb'            => $ppb,
                'size_mode'      => $sizeMode,
            ];
        })->unique('id')->values();

        return response()->json($warehouses);
    }

    // ===== List page =====
    public function product()
    {
        $products = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
            'packings',
        ])
            ->latest()
            ->paginate(10);

        $categories = Category::get();

        return view('admin_panel.product.index', compact('products', 'categories'));
    }

    public function productview($id)
    {
        $user = auth()->user();
        $product = Product::with([
            'category_relation',
            'sub_category_relation',
            'brand',
            'unit',
            'packings',
            'warehouseStocks' => function ($q) use ($user) {
                if (!$user->hasRole('Super Admin')) {
                    $q->whereHas('warehouse', function ($wh) use ($user) {
                        $wh->where('branch_id', $user->branch_id);
                    });
                }
            },
        ])->find($id);

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate derived fields
        $totalPieces = $product->warehouseStocks->sum('total_pieces');
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        $boxes = 0;
        $loose = 0;

        if ($product->size_mode === 'by_cartons' || $product->size_mode === 'by_size') {
            $boxes = floor($totalPieces / $ppb);
            $loose = $totalPieces % $ppb;
        } else {
            // For by_pieces, boxes is essentially the piece count if we treat it largely
            // But strict interpretation:
            $boxes = $totalPieces;
            $loose = 0;
        }

        // Append these purely for the view (not saved in DB)
        $product->setAttribute('calculated_total_stock_qty', $totalPieces);
        $product->setAttribute('calculated_boxes_quantity', $boxes);
        $product->setAttribute('calculated_loose_pieces', $loose);

        return response()->json($product);
    }

    // //////////////////////

    // /////////////////////////

    // ===== Create page =====
    public function view_store()
    {
        $categories = Category::select('id', 'name')->get();
        $units = Unit::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();
        
        $user = auth()->user();
        if ($user->hasRole('Super Admin')) {
            $warehouses = \App\Models\Warehouse::with('branch')->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        } else {
            $warehouses = \App\Models\Warehouse::where('branch_id', $user->branch_id)->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        }

        return view('admin_panel.product.create', compact('categories', 'units', 'brands', 'warehouses'));
    }

    // ===== Dependent subcategories =====
    public function getSubcategories($category_id)
    {
        $subcategories = Subcategory::where('category_id', $category_id)->get();

        return response()->json($subcategories);
    }

    // ===== Barcode =====
    public function generateBarcode(Request $request)
    {
        $barcodeNumber = $request->filled('code') ? $request->code : rand(100000000000, 999999999999);
        $barcodePNG = (new DNS1D)->getBarcodePNG($barcodeNumber, 'C39', 3, 50);
        $barcodeImage = 'data:image/png;base64,'.$barcodePNG;

        return response()->json([
            'barcode_number' => $barcodeNumber,
            'barcode_image' => $barcodeImage,
        ]);
    }

    // ===== Store product =====
    // ===== Store product =====
    public function store_product(Request $request)
    {
        if (! Auth::id()) {
            return $request->wantsJson()
                ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401)
                : redirect()->route('login');
        }

        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }
            return redirect()->back()->withErrors($validation)->withInput();
        }

        $packings = $request->input('packings', []);
        $mode = 'by_pieces';
        $piecesPerBox = 1;
        
        foreach ($packings as $p) {
            if (intval($p['pieces_per_box'] ?? 1) > 1) {
                $mode = 'by_cartons';
                break;
            }
        }
        
        $first = reset($packings);
        $piecesPerBox = intval($first['pieces_per_box'] ?? 1);
        $buy = floatval($first['purchase_price'] ?? 0);
        $sell = floatval($first['sale_price'] ?? 0);

        if ($mode === 'by_cartons') {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell * $piecesPerBox;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy * $piecesPerBox;
        } else {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy;
        }

        $height = (float) $request->height;
        $width = (float) $request->width;
        $totalM2 = ($height * $width) / 10000;

        $userId = Auth::id();
        $itemCode = $request->item_code;
        if (empty($itemCode)) {
            $last = Product::orderBy('id', 'desc')->first();
            $itemCode = $last ? ('ITEM-'.str_pad($last->id + 1, 4, '0', STR_PAD_LEFT)) : 'ITEM-0001';
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = 'uploads/products/'.$filename;
        }

        DB::transaction(function () use ($request, $userId, $itemCode, $imagePath, $mode, $height, $width, $piecesPerBox, 
            $totalM2, $salePricePerPiece, $salePricePerBox, $purchasePricePerPiece, $purchasePricePerBox, $packings) {

            $product = Product::create([
                'creater_id' => $userId,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $itemCode,
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path ?? rand(100000000000, 999999999999),
                'brand_id' => $request->brand_id,
                'model' => $request->model,
                'mdr' => $request->mdr,
                'hs_code' => $request->hs_code,
                'image' => $imagePath,
                'color' => $request->color ? json_encode(array_values(array_filter($request->color))) : null,
                'size_mode' => $mode,
                'height' => $height,
                'width' => $width,
                'pieces_per_box' => $piecesPerBox,
                'total_m2' => $totalM2,
                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,
                'is_fridge' => $request->is_fridge ? 1 : 0,
                'is_non_fridge' => $request->is_non_fridge ? 1 : 0,
                'is_fast_moving' => $request->is_fast_moving ? 1 : 0,
                'is_slow_moving' => $request->is_slow_moving ? 1 : 0,
                'is_part' => 0,
                'is_assembled' => 0,
            ]);

            $warehouseId = $request->warehouse_id ?: \App\Models\Warehouse::min('id');
            if ($warehouseId) {
                WarehouseStock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'total_pieces' => 0,
                    'remarks' => 'Initial Entry',
                ]);
            }

            foreach ($packings as $pData) {
                if (!empty($pData['name'])) {
                    \App\Models\ProductUom::create([
                        'product_id' => $product->id,
                        'name' => $pData['name'],
                        'pieces_per_box'  => intval($pData['pieces_per_box'] ?? 1),
                        'purchase_price' => floatval($pData['purchase_price'] ?? 0),
                        'sale_price' => floatval($pData['sale_price'] ?? 0),
                    ]);
                }
            }
        });

        return $request->wantsJson() 
            ? response()->json(['status' => 'success', 'message' => 'Product created successfully'])
            : redirect()->back()->with('success', 'Product created successfully');
    }

    /*
    // ===== Parts search (for BOM modal) with real available qty =====
        public function searchPartName(Request $request)
    {
        $q = $request->get('q', '');

        $parts = Product::where('is_part', 1)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->where(function ($x) use ($q) {
                $x->where('products.item_name', 'like', "%{$q}%")
                  ->orWhere('products.item_code', 'like', "%{$q}%");
            })
            ->groupBy('products.id', 'products.item_name', 'products.item_code', 'products.unit_id')
            ->selectRaw('products.id, products.item_name, products.item_code, products.unit_id, COALESCE(SUM(stocks.qty),0) as available_qty')
            ->limit(20)
            ->get();

        return response()->json($parts->map(function ($p) {
            return [
                'id'            => $p->id,
                'item_name'     => $p->item_name,
                'item_code'     => $p->item_code,
                'unit'          => optional(Unit::find($p->unit_id))->name ?? '',
                'available_qty' => (float)$p->available_qty,
            ];
        }));
    }
    */

    public function update(Request $request, $id)
    {
        $userId = auth()->id();

        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            return $request->wantsJson() 
                ? response()->json(['status' => 'error', 'errors' => $validation->errors()], 422)
                : redirect()->back()->withErrors($validation)->withInput();
        }

        $packings = $request->input('packings', []);
        $mode = 'by_pieces';
        $piecesPerBox = 1;

        foreach ($packings as $p) {
            if (intval($p['pieces_per_box'] ?? 1) > 1) {
                $mode = 'by_cartons';
                break;
            }
        }
        
        $first = reset($packings);
        $piecesPerBox = intval($first['pieces_per_box'] ?? 1);
        $buy = floatval($first['purchase_price'] ?? 0);
        $sell = floatval($first['sale_price'] ?? 0);

        if ($mode === 'by_cartons') {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell * $piecesPerBox;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy * $piecesPerBox;
        } else {
            $salePricePerPiece = $sell;
            $salePricePerBox = $sell;
            $purchasePricePerPiece = $buy;
            $purchasePricePerBox = $buy;
        }

        $imagePath = Product::where('id', $id)->value('image');
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = 'uploads/products/'.$filename;
        }

        DB::transaction(function () use ($request, $id, $userId, $imagePath, $mode, $piecesPerBox,
            $salePricePerPiece, $salePricePerBox, $purchasePricePerPiece, $purchasePricePerBox, $packings) {

            $product = Product::findOrFail($id);
            $oldPurchasePrice = (float) $product->purchase_price_per_piece;
            $oldSalePrice = (float) $product->sale_price_per_piece;

            $product->update([
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $request->item_code,
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path,
                'brand_id' => $request->brand_id,
                'model' => $request->model,
                'mdr' => $request->mdr,
                'hs_code' => $request->hs_code,
                'image' => $imagePath,
                'color' => $request->color ? json_encode(array_values(array_filter($request->color))) : null,
                'size_mode' => $mode,
                'pieces_per_box' => $piecesPerBox,
                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,
                'is_fridge' => $request->is_fridge ? 1 : 0,
                'is_non_fridge' => $request->is_non_fridge ? 1 : 0,
                'is_fast_moving' => $request->is_fast_moving ? 1 : 0,
                'is_slow_moving' => $request->is_slow_moving ? 1 : 0,
                'updated_at' => now(),
            ]);

            // Log manual price changes if any
            if ($oldPurchasePrice != $purchasePricePerPiece) {
                PriceLog::log($id, 'purchase', $oldPurchasePrice, $purchasePricePerPiece, 'MANUAL', null, "Manual purchase price update");
            }
            if ($oldSalePrice != $salePricePerPiece) {
                PriceLog::log($id, 'sale', $oldSalePrice, $salePricePerPiece, 'MANUAL', null, "Manual sale price update");
            }

            $existingUoms = \App\Models\ProductUom::where('product_id', $id)->get()->keyBy('name');
            $submittedNames = collect($packings)->pluck('name')->filter()->toArray();

            foreach ($existingUoms as $name => $uom) {
                if (!in_array($name, $submittedNames) && $uom->warehouseStocks()->sum('total_pieces') == 0) {
                    $uom->delete();
                }
            }

            foreach ($packings as $pData) {
                if (!empty($pData['name'])) {
                    \App\Models\ProductUom::updateOrCreate(
                        ['product_id' => $id, 'name' => $pData['name']],
                        [
                            'pieces_per_box'  => intval($pData['pieces_per_box'] ?? 1),
                            'purchase_price'  => floatval($pData['purchase_price'] ?? 0),
                            'sale_price'      => floatval($pData['sale_price'] ?? 0),
                        ]
                    );
                }
            }
        });

        return $request->wantsJson() 
            ? response()->json(['status' => 'success', 'message' => 'Product updated successfully'])
            : redirect()->back()->with('success', 'Product updated successfully');
    }

    // ===== Edit view =====
    public function edit($id)
    {
        $product = Product::with('packings', 'category_relation', 'sub_category_relation', 'unit', 'brand')->findOrFail($id);
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $brands = Brand::all();
        
        $user = auth()->user();
        if ($user->hasRole('Super Admin')) {
            $warehouses = \App\Models\Warehouse::with('branch')->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        } else {
            $warehouses = \App\Models\Warehouse::where('branch_id', $user->branch_id)->select('id', 'warehouse_name', 'location', 'branch_id')->get();
        }

        return view('admin_panel.product.edit', compact('product', 'categories', 'subcategories', 'brands', 'warehouses'));
    }

    // ===== Barcode view =====
    public function barcode($id)
    {
        $product = Product::findOrFail($id);

        return view('admin_panel.product.barcode', compact('product'));
    }

    // Shared validation rules
    private function validateProductRequest(Request $request)
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required',
            'item_code' => 'required|string|max:255',
            'sub_category_id' => 'nullable',
            'brand_id' => 'required',
            'unit' => 'nullable',
            'model' => 'nullable',
            'mdr' => 'nullable',
            'color' => 'nullable|array',
            'packings' => 'required|array|min:1',
            'packings.*.name' => 'required|string',
            'packings.*.pieces_per_box' => 'required|integer|min:1',
            'packings.*.purchase_price' => 'nullable|numeric|min:0',
            'packings.*.sale_price' => 'nullable|numeric|min:0',
        ];

        return \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    }

    // AJAX Validation Endpoint
    public function validateForm(Request $request)
    {
        $validator = $this->validateProductRequest($request);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Valid']);
    }
}
