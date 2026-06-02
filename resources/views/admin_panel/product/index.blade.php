@extends('admin_panel.layout.app')
@section('content')
    <style>
        div.dataTables_wrapper div.dataTables_length select {
            width: 75px !important
        }
    </style>



    <div class="card shadow-sm border-0">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0 fw-bold">📦 Product List</h5>
                <small class="text-muted">Manage all products here</small>
            </div>
            <div class="d-flex justify-content-between align-items-end gap-1">
                @if (auth()->user()->can('discount.products.view') || auth()->user()->email === 'admin@admin.com')
                    <a href="{{ route('discount.index') }}" class="btn btn-success btn-sm">
                        View Discount
                    </a>
                @endif
                @if (auth()->user()->can('products.create') || auth()->user()->email === 'admin@admin.com')
                    <a href="create_prodcut" class="btn btn-primary"> Add product</a>
                @endif

                @if (auth()->user()->can('discount.products.create') || auth()->user()->email === 'admin@admin.com')
                    <button id="createDiscountBtn" class="btn btn-success btn-sm">
                        ➡ Create Discount
                    </button>
                @endif
            </div>

        </div>

        <div class="card-body">
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    ✅ {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <div class="table-responsive">
                <div class="mb-3" style="max-width: 420px;">
                    <input type="text" id="search_all" class="form-control" placeholder="Search Item Name, Code, Category, Brand">
                </div>
                <table id="productTable" class="table table-striped table-bordered align-middle nowrap" style="width:100%">

                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>#</th>
                            <th>Code</th>
                            <th>Image</th>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th>Packing</th>
                            <th>Pcs / Box</th>
                            <th>Sale Price</th>
                            <th>Sale Total</th>
                            <th>Company</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $key => $product)
                            <tr>
                                <td><input type="checkbox" class="selectProduct" value="{{ $product->id }}"></td>
                                <td>{{ $key + 1 }}</td>
                                <td class="fw-bold">{{ $product->item_code }}</td>
                                <td>
                                    @if ($product->image)
                                        <img src="{{ asset('uploads/products/' . $product->image) }}" alt="Product"
                                            width="50" height="50" class="rounded border">
                                    @else
                                        <span class="badge bg-secondary">No Img</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $product->category_relation->name ?? '-' }}</strong><br>
                                    <small class="text-muted">{{ $product->sub_category_relation->name ?? '-' }}</small>
                                </td>
                                <td>{{ $product->item_name }}</td>
                                <td>
                                    @if ($product->packings->count() > 0)
                                        @foreach ($product->packings as $packing)
                                            <span class="badge bg-light text-primary border border-primary">{{ $packing->name }}</span>
                                        @endforeach
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $product->pieces_per_box ?? '-' }}</td>
                                <td class="fw-bold">Rs.
                                    @php
                                        $displayPrice = 0;
                                        if ($product->size_mode === 'by_size') {
                                            $displayPrice = $product->price_per_m2 ?? 0;
                                        } else {
                                            // Prefer piece price if > 0, else fallback to box price (which is pc price in by_pieces mode)
                                            $displayPrice = ($product->sale_price_per_piece > 0) 
                                                ? $product->sale_price_per_piece 
                                                : ($product->sale_price_per_box ?? 0);
                                        }
                                    @endphp
                                    {{ number_format($displayPrice, 2) }}
                                </td>
                                <td class="text-success fw-bold">Rs. {{ number_format($product->total_price, 2) }}</td>
                                <td>{{ $product->brand->name ?? '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning viewProductBtn"
                                        data-id="{{ $product->id }}">
                                        View
                                    </button>


                                    @if (auth()->user()->can('products.edit') || auth()->user()->email === 'admin@admin.com')
                                        <a href="{{ route('products.edit', $product->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            ✏ Edit
                                        </a>
                                    @endif

                                    <a href="{{ route('product.batches', $product->id) }}"
                                        class="btn btn-sm btn-outline-info">
                                        📦 View Batch
                                    </a>

                                    {{-- 
                                    <a href="{{ route('generate-barcode-image', $product->id) }}"
                                        class="btn btn-sm btn-outline-success">
                                        🏷 Barcode
                                    </a> --}}



                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                {{ $products->links() }}
            </div>
        </div>
    </div>

    {{-- add product modal --}}

    <div class="modal fade bd-example-modal-lg" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Product</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">Please use the main "Add Product" page for the new per-m² flow.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Detail View Modal (Modern Bootstrap 5) -->
    <div class="modal fade" id="productViewModal" tabindex="-1" aria-labelledby="productViewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">

                <!-- Header -->
                <div class="modal-header bg-light pb-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white p-2 rounded-circle"
                            style="width:40px;height:40px;display:grid;place-items:center;">
                            <i class="las la-box fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="view_item_name">Product Name</h5>
                            <div class="d-flex gap-2 align-items-center mt-1">
                                <span class="badge bg-secondary" id="view_item_code">CODE</span>
                                <span class="badge bg-dark" id="view_barcode_path">BARCODE</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body bg-light p-4">

                    <!-- Loading Spinner -->
                    <div id="modalLoadingSpinner" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="row g-4" id="modalContentRow">

                        <!-- Panel 1: Identity -->
                        <div class="col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body p-4">
                                    <h6 class="text-uppercase text-primary fw-bold small mb-4 border-bottom pb-2">
                                        <i class="las la-info-circle fs-5 align-text-bottom"></i> 1. Identity & Details
                                    </h6>

                                    <div class="text-center mb-4">
                                        <div class="bg-light rounded-4 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                                            style="width: 140px; height: 140px; overflow: hidden; border: 1px solid #e2e8f0;">
                                            <img id="view_image_preview" src="" class="img-fluid d-none"
                                                style="object-fit: contain; width:100%; height:100%;">
                                            <div id="view_image_placeholder" class="text-center">
                                                <i class="las la-image text-muted" style="font-size: 3rem;"></i>
                                                <small class="d-block text-muted mt-2">No Image</small>
                                            </div>
                                        </div>
                                    </div>

                                    <ul class="list-group list-group-flush small">
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Category</span>
                                            <strong class="text-dark text-end" id="view_cat_sub">-</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Company</span>
                                            <strong class="text-dark" id="view_brand_name">-</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Model</span>
                                            <strong class="text-dark" id="view_model_name">-</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Colors</span>
                                            <strong class="text-dark text-end" id="view_color">-</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">HS Code</span>
                                            <strong class="text-dark" id="view_hs_code">-</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between px-0">
                                            <span class="text-muted">Registered</span>
                                            <strong class="text-dark" id="view_created_at">-</strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 2: Measurement & Stock -->
                        <div class="col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
                                        <h6 class="text-uppercase text-info fw-bold small mb-0">
                                            <i class="las la-ruler-combined fs-5 align-text-bottom"></i> 2. Specs & Stock
                                        </h6>
                                        <span class="badge" id="view_size_mode_badge">Mode</span>
                                    </div>

                                    <div class="h-100 d-flex flex-column justify-content-between">
                                        <!-- By Size -->
                                        <div id="sec_by_size" class="d-none h-100 flex-column justify-content-between">
                                            <div>
                                                <div class="row text-center mb-3">
                                                    <div class="col-12">
                                                        <small class="text-muted d-block">Dimensions (cm)</small>
                                                        <strong class="text-dark fs-6" id="view_dimensions">-</strong>
                                                    </div>
                                                </div>
                                                <div class="bg-light p-3 rounded-3 border mb-3">
                                                    <div class="row text-center">
                                                        <div class="col-6 border-end">
                                                            <strong class="text-dark fs-5"
                                                                id="view_boxes_qty_size">-</strong>
                                                            <small class="text-muted d-block text-uppercase"
                                                                style="font-size: 0.7rem">Boxes</small>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong class="text-dark fs-5"
                                                                id="view_pcs_box_size">-</strong>
                                                            <small class="text-muted d-block text-uppercase"
                                                                style="font-size: 0.7rem">Pcs/Box</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="text-center bg-info bg-opacity-10 p-2 rounded-3">
                                                <small class="text-info fw-bold d-block text-uppercase">Total Area
                                                    (m²)</small>
                                                <div class="fs-4 fw-bold text-info" id="view_total_m2">-</div>
                                            </div>
                                        </div>

                                        <!-- By Box/Carton -->
                                        <div id="sec_packing" class="d-none">
                                            <div class="row text-center g-2 mb-3">
                                                <div class="col-12">
                                                    <div class="bg-light p-3 rounded-3 border">
                                                        <small class="text-muted d-block text-uppercase fw-bold"
                                                            style="font-size: 0.7rem;">Pieces per Carton</small>
                                                        <strong class="text-dark fs-4" id="view_pcs_box">-</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row text-center g-2 mt-3">
                                                <div class="col-6">
                                                    <div role="group" aria-label="Full Cartons" class="bg-primary text-white p-3 rounded-3 border border-primary text-center shadow-sm">
                                                        <small class="text-white fw-bold text-uppercase" style="font-size:0.7rem;">Full Cartons</small>
                                                        <strong class="text-white fs-4 d-block mt-2" id="view_boxes_qty">-</strong>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div role="group" aria-label="Loose Pieces" class="bg-warning bg-opacity-25 text-dark p-3 rounded-3 border border-warning shadow-sm text-center">
                                                        <small class="text-dark fw-bold text-uppercase" style="font-size:0.7rem;">Loose Pieces</small>
                                                        <strong class="text-dark fs-4 d-block mt-2" id="view_loose_pcs">-</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- By Piece -->
                                        <div id="sec_by_piece"
                                            class="d-none text-center d-flex flex-column justify-content-center h-100">
                                            <div class="p-4 bg-light rounded-4 border">
                                                <i class="las la-layer-group text-primary mb-2"
                                                    style="font-size: 3rem;"></i>
                                                <h5 class="fw-bold text-dark">Unit Tracking</h5>
                                                <p class="text-muted small mb-0">Item is tracked and sold as individual
                                                    units.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Stock Footer -->
                                    <div class="mt-4 pt-3 border-top">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted fw-bold text-uppercase"
                                                style="letter-spacing: 1px;">Total Inventory</span>
                                            <div>
                                                <span class="fs-3 fw-bold text-success" id="view_total_stock_qty">0</span>
                                                <span class="text-success small fw-bold ms-1">PCS</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <!-- Panel 3: Financial -->
                        <div class="col-lg-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-body p-4 position-relative">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="text-uppercase text-dark fw-bold small mb-0">
                                            <i class="las la-wallet fs-5 align-text-bottom text-success"></i> 3. Financials
                                        </h6>
                                        <small class="text-muted">Summary</small>
                                    </div>

                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-end mb-2">
                                            <span class="text-muted small fw-bold" id="lbl_price_unit">Sale Price</span>
                                            <span class="fw-bold fs-5 text-success" id="view_price_unit">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-end mb-2">
                                            <span class="text-muted small fw-bold" id="lbl_purch_unit">Purch Price</span>
                                            <span class="text-dark fs-6" id="view_purch_unit">-</span>
                                        </div>
                                    </div>

                                    <div class="p-3 mb-3 mt-3 rounded-3 border border-success bg-success bg-opacity-10">
                                        <span class="d-block text-white opacity-75 fw-bold text-uppercase mb-1"
                                            style="font-size: 0.75rem; letter-spacing: 1px;">Est. Sales Value</span>
                                        <div class="fw-bold text-white fs-2 lh-1" id="view_sale_total">-</div>
                                    </div>

                                    <div class="p-3 rounded-3 border border-secondary bg-light">
                                        <span class="d-block text-muted fw-bold text-uppercase mb-1"
                                            style="font-size: 0.75rem; letter-spacing: 1px;">Est. Cost Value</span>
                                        <div class="fw-bold text-dark fs-4 lh-1" id="view_purch_total">-</div>
                                        <div class="mt-2 text-end">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-white border border-success px-2 py-1"
                                                id="view_profit_margin">Margin: -</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer border-top-0 bg-light rounded-bottom pb-3 pe-4">
                    <button type="button" class="btn btn-secondary px-4 fw-bold shadow-sm"
                        data-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>




    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    {{-- product model --}}
    @section('js')
    <script>
        $(document).on('click', '.viewProductBtn', function() {
            let productId = $(this).data('id');

            // 1. Reset & Loading State
            $('#modalContentRow').addClass('d-none');
            $('#modalLoadingSpinner').removeClass('d-none');

            // Bootstrap modal show
            $('#productViewModal').modal('show');

            $.ajax({
                url: "/productview/" + productId,
                type: "GET",
                success: function(product) {

                    // 2. Hide Spinner, Show Content
                    $('#modalLoadingSpinner').addClass('d-none');
                    $('#modalContentRow').removeClass('d-none');

                    // --- Basic ---
                    $('#view_item_name').text(product.item_name ?? 'Unknown Product');
                    $('#view_item_code').text('Code: ' + (product.item_code ?? 'N/A'));
                    $('#view_barcode_path').text('Barcode: ' + (product.barcode_path ?? 'N/A'));

                    $('#view_cat_sub').text((product.category_relation?.name ?? '') + (product
                        .sub_category_relation ? ' • ' + product.sub_category_relation.name : ''
                    ));

                    $('#view_brand_name').text(product.brand?.name ?? '-');
                    $('#view_model_name').text(product.model ?? '-');

                    $('#view_hs_code').text(product.hs_code ?? '-');
                    $('#view_created_at').text(product.created_at ? new Date(product.created_at)
                        .toLocaleDateString() : '-');

                    // --- Image ---
                    if (product.image) {
                        $('#view_image_preview').attr('src', '/uploads/products/' + product.image)
                            .removeClass('d-none');
                        $('#view_image_placeholder').addClass('d-none');
                    } else {
                        $('#view_image_preview').addClass('d-none');
                        $('#view_image_placeholder').removeClass('d-none');
                    }

                    // --- Colors ---
                    if (product.color) {
                        try {
                            let colors = JSON.parse(product.color);
                            $('#view_color').text(Array.isArray(colors) ? colors.join(', ') : colors);
                        } catch (e) {
                            $('#view_color').text(product.color);
                        }
                    } else {
                        $('#view_color').text('-');
                    }

                    // --- Mode & Layout Switching ---
                    let mode = product.size_mode ?? 'by_size';

                    // Defaults
                    $('#sec_by_size, #sec_packing, #sec_by_piece').addClass('d-none').removeClass(
                        'd-flex');

                    let calcBoxes = product.calculated_boxes_quantity ?? 0;
                    let calcLoose = product.calculated_loose_pieces ?? 0;
                    let calcTotal = product.calculated_total_stock_qty ?? 0;

                    let salePrice = 0;
                    let purchPrice = 0;
                    let estSaleVal = 0;
                    let estPurchVal = 0;

                    if (mode === 'by_size') {
                        $('#view_size_mode_badge').text('By Size').removeClass(
                                'bg-info bg-warning border-info border-warning text-dark text-white')
                            .addClass('bg-primary bg-opacity-10 text-primary border border-primary');
                        $('#sec_by_size').removeClass('d-none').addClass('d-flex');

                        // Fill Size Data
                        let h = parseFloat(product.height ?? 0);
                        let w = parseFloat(product.width ?? 0);
                        $('#view_dimensions').text(h + ' x ' + w);

                        let m2Piece = (h * w) / 10000;

                        $('#view_boxes_qty_size').text(calcBoxes); // Box count for Size mode
                        $('#view_pcs_box_size').text(product.pieces_per_box ?? 0);

                        let m2Box = m2Piece * (product.pieces_per_box > 0 ? product.pieces_per_box : 1);
                        let totalArea = m2Box * calcBoxes;
                        $('#view_total_m2').text(totalArea.toFixed(4));

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per m²');
                        $('#lbl_purch_unit').text('Cost per m²');
                        salePrice = parseFloat(product.price_per_m2 ?? 0);
                        purchPrice = parseFloat(product.purchase_price_per_m2 ?? 0);

                        estSaleVal = totalArea * salePrice;
                        estPurchVal = totalArea * purchPrice;

                    } else if (mode === 'by_cartons') {
                        $('#view_size_mode_badge').text('By Carton').removeClass(
                                'bg-primary bg-warning border-primary border-warning text-primary text-dark bg-opacity-10 border'
                            )
                            .addClass('bg-info text-dark');
                        $('#sec_packing').removeClass('d-none');

                        $('#view_boxes_qty').text(calcBoxes);
                        $('#view_loose_pcs').text(calcLoose);
                        $('#view_pcs_box').text(product.pieces_per_box ?? '-');

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per Unit (Pc)');
                        $('#lbl_purch_unit').text('Cost per Unit (Pc)');
                        salePrice = parseFloat(product.sale_price_per_box ?? 0);
                        purchPrice = parseFloat(product.purchase_price_per_piece ?? 0);

                        // In cartons mode, calculation is purely based on piece qty directly
                        estSaleVal = calcTotal * salePrice;
                        estPurchVal = calcTotal * purchPrice;

                    } else { // by_pieces
                        $('#view_size_mode_badge').text('By Piece').removeClass(
                                'bg-primary bg-info border-primary border-info text-primary text-white bg-opacity-10 border'
                            )
                            .addClass('bg-warning text-dark');
                        $('#sec_by_piece').removeClass('d-none');

                        // Stock
                        $('#view_total_stock_qty').text(calcTotal);

                        // Price Labels
                        $('#lbl_price_unit').text('Price per Piece');
                        $('#lbl_purch_unit').text('Cost per Piece');
                        salePrice = parseFloat(product.sale_price_per_box ?? 0);
                        purchPrice = parseFloat(product.purchase_price_per_piece ?? 0);

                        estSaleVal = calcTotal * salePrice;
                        estPurchVal = calcTotal * purchPrice;
                    }

                    // Format Financials
                    const formatCurrency = (val) => 'Rs. ' + val.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                    $('#view_price_unit').text(formatCurrency(salePrice));
                    $('#view_purch_unit').text(formatCurrency(purchPrice));
                    $('#view_sale_total').text(formatCurrency(estSaleVal));
                    $('#view_purch_total').text(formatCurrency(estPurchVal));

                    // Margin calculation
                    if (estPurchVal > 0) {
                        let margin = ((estSaleVal - estPurchVal) / estPurchVal) * 100;
                        $('#view_profit_margin').text('Margin: ' + margin.toFixed(2) + '%').show();
                    } else if (estSaleVal > 0) {
                        $('#view_profit_margin').text('Margin: 100%').show();
                    } else {
                        $('#view_profit_margin').hide();
                    }

                },
                error: function() {
                    $('#modalLoadingSpinner').addClass('d-none');
                    $('#productViewModal').modal('hide');
                    Swal.fire('Error', 'Could not fetch product details', 'error');
                }
            });
        });
    </script>


    <script>
        $(document).ready(function() {

            // Select/Deselect all checkboxes
            $('#selectAll').click(function() {
                $('.selectProduct').prop('checked', this.checked);
            });

            // On "Create Discount" click
            $('#createDiscountBtn').click(function() {
                var selected = [];
                $('.selectProduct:checked').each(function() {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    Swal.fire({
                        icon: "error",
                        title: "Oops...",
                        text: "Please select at least one product!",

                    });
                    return;
                }

                // Redirect with product IDs as query param
                window.location.href = "{{ route('discount.create') }}" + "?products=" + selected.join(
                    ',');
            });
        });
    </script>

    <script>
        $(document).ready(function() {

            function debounce(func, delay) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => func.apply(this, args), delay);
                }
            }

            let table = $('#productTable').DataTable({
                responsive: true,
                paging: false,
                ordering: true,
                info: false,
                order: [
                    [1, 'asc']
                ],
                dom: 'rt<"bottom"><"clear">',
                columnDefs: [{
                    targets: [0, 11],
                    searchable: false
                }, ]
            });

            // Use the single custom search box instead of the built-in DataTables filter.
            $('#search_all').on('input', debounce(function() {
                table.search(this.value).draw();
            }, 200));

            // ===== Initialize Products DataTable =====

        });
    </script>

    <!-- DataTables CSS -->
@endsection

    <script>
        document.addEventListener("DOMContentLoaded", function() {
        let cartonQuantityInput = document.getElementById("carton_quantity");
        let piecesPerCartonInput = document.getElementById("pieces_per_carton");
        let initialStockInput = document.getElementById("initial_stock");

        if (cartonQuantityInput && piecesPerCartonInput && initialStockInput) {
            function updateInitialStock() {
                let cartonQuantity = parseInt(cartonQuantityInput.value) || 0;
                let piecesPerCarton = parseInt(piecesPerCartonInput.value) || 0;
                initialStockInput.value = cartonQuantity * piecesPerCarton;
            }

            cartonQuantityInput.addEventListener("input", updateInitialStock);
            piecesPerCartonInput.addEventListener("input", updateInitialStock);
        }
    });


    $(document).ready(function() {
        // Add Product Modal: Fetch Subcategories on Category Change
        $('#categorySelect').change(function() {
            var categoryId = $(this).val();

            $('#subCategorySelect').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#subCategorySelect').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#subCategorySelect').append('<option value="' +
                                subCategory.id + '">' +
                                subCategory.name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#subCategorySelect').html('<option value="">Select Sub-Category</option>');
            }
        });

        // Edit Product Modal: Fetch Subcategories when Category is Changed
        $('#edit_category').change(function() {
            var categoryId = $(this).val();
            $('#edit_sub_category').html('<option value="">Loading...</option>');

            if (categoryId) {
                $.ajax({
                    url: "/get-subcategories/" + categoryId,

                    type: "GET",
                    data: {
                        category_id: categoryId
                    },
                    success: function(data) {
                        $('#edit_sub_category').html(
                            '<option value="">Select Sub-Category</option>');
                        $.each(data, function(key, subCategory) {
                            $('#edit_sub_category').append('<option value="' +
                                subCategory.sub_category_name + '">' +
                                subCategory.sub_category_name + '</option>');
                        });
                    },
                    error: function() {
                        alert('Error fetching subcategories.');
                    }
                });
            } else {
                $('#edit_sub_category').html('<option value="">Select Sub-Category</option>');
            }
        });
    });
    </script>
@endsection
