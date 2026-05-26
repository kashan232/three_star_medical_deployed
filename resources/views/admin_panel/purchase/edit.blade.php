@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ================= RESPONSIVE PURCHASE UI (Modernized) ================= */
        body {
            background-color: #f4f6f9;
            /* Light gray background for contrast */
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .sales-table {
            min-width: 1000px;
            /* Base width */
            border-collapse: separate;
            border-spacing: 0;
        }

        .sales-table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 12px 8px;
            border-bottom: 2px solid #e9ecef !important;
        }

        .sales-table tbody td {
            vertical-align: middle;
            padding: 8px;
            border-color: #f1f3f5;
        }

        .sales-table tfoot td {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
        }

        /* Premium Table Look */
        .table-bordered>:not(caption)>*>* {
            border-width: 1px;
            border-color: #e9ecef;
        }

        /* Column widths */
        .col-product {
            width: 300px;
            min-width: 250px;
        }

        .col-warehouse {
            width: 140px;
        }

        .col-stock {
            width: 90px;
        }

        .col-qty {
            width: 100px;
        }

        .col-pieces {
            width: 100px;
        }

        .col-price {
            width: 120px;
        }

        .col-disc {
            width: 80px;
        }

        .col-disc-amt {
            width: 95px;
        }

        .col-price-p {
            width: 100px;
        }

        .col-amount {
            width: 120px;
            text-align: right;
        }

        .col-action {
            width: 50px;
            text-align: center;
        }

        .input-readonly {
            background: #f8f9fa;
            color: #495057;
            font-weight: 500;
            border: 1px solid #dee2e6;
        }

        .form-control,
        .form-select {
            border-radius: 6px;
            border: 1px solid #ced4da;
            padding: 0.4rem 0.6rem;
            font-size: 0.85rem;
            transition: all 0.2s ease-in-out;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .main-container {
            font-size: .85rem;
            max-width: 99%;
            border-radius: 12px !important;
            border: none !important;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.08) !important;
        }

        .btn {
            font-size: .82rem;
            padding: .35rem .8rem;
            border-radius: 5px;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .btn-success {
            background-color: #198754;
            border-color: #198754;
        }

        .section-title {
            font-weight: 700;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
            border-left: 3px solid #0d6efd;
            padding-left: 8px;
        }

        /* Product Search Dropdown */
        .search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            z-index: 1000;
            max-height: 250px;
            overflow-y: auto;
            width: 100%;
            list-style: none;
            padding: 0;
            margin: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
        }

        .search-result-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.1s;
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background-color: #e7f1ff;
            color: #0b5ed7;
        }

        /* Layout Helpers */
        .card-panel {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
        }

        .summary-card {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .select2-container .select2-selection--single {
            height: 36px !important;
            padding: 3px 12px;
            border-color: #ced4da;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 5px !important;
        }
    </style>

    <div class="container-fluid py-2">
        <div class="main-container bg-white border shadow-sm mx-auto p-2 rounded-3">

            <form id="purchaseForm" action="{{ route('purchase.update', $purchase->id) }}" method="POST" autocomplete="off">
                @csrf
                @method('PUT')

                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                    <div>
                        <a href="{{ route('Purchase.home') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                    <h2 class="header-text text-secondary fw-bold mb-0">Edit Purchase #{{ $purchase->invoice_no }}</h2>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-secondary" id="entryDate">Date: {{ date('d-M-Y') }}</small>
                    </div>
                </div>

                <div class="row g-3 border-bottom pb-4 mb-3 mt-2">
                    {{-- LEFT: Invoice & Vendor --}}
                    <div class="col-lg-3 col-md-4">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Invoice & Vendor</div>

                            <div class="mb-2 d-flex align-items-center gap-2">
                                <label class="form-label fw-bold mb-0 text-muted small" style="min-width: 80px;">GRN
                                    #</label>
                                <input type="text" class="form-control input-readonly" name="invoice_no"
                                    value="{{ $purchase->invoice_no }}" readonly>
                            </div>

                            <!-- VENDOR SELECT -->
                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Select Vendor</label>
                                <select class="form-select select2" id="vendorSelect" name="vendor_id">
                                    <option value="" disabled>Select Vendor</option>
                                    @foreach ($Vendor as $v)
                                        <option value="{{ $v->id }}"
                                            {{ $v->id == $purchase->vendor_id ? 'selected' : '' }}>
                                            {{ $v->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold mb-1 text-muted small">Date</label>
                                <input type="date" name="purchase_date" class="form-control"
                                    value="{{ $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : date('Y-m-d') }}">
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-bold text-muted small">Remarks</label>
                                <textarea class="form-control" name="note" rows="2">{{ $purchase->note }}</textarea>
                            </div>

                            <input type="hidden" name="warehouse_id" value="{{ $purchase->warehouse_id }}">
                        </div>
                    </div>

                    {{-- RIGHT: Items --}}
                    <div class="col-lg-9 col-md-8">
                        <div class="card-panel shadow-sm p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="section-title mb-0">Purchase Items</div>
                                <button type="button" class="btn btn-sm btn-primary px-3 shadow-sm"
                                    onclick="addBlankRow()">
                                    <i class="bi bi-plus-lg"></i> Add Row
                                </button>
                            </div>

                            <div class="table-responsive border rounded-3 bg-white">
                                <table class="table table-bordered sales-table mb-0" id="purchaseTable">
                                    <thead>
                                        <tr>
                                            <th class="col-product">Product</th>
                                            <th class="hs-code-col">HS Code</th>
                                            <th class="col-qty">Boxes</th>
                                            <th class="col-stock">Pack Size</th>
                                            <th class="col-pieces">Pieces</th>
                                            <th class="col-price">Price</th>
                                            <th class="col-disc">Disc %</th>
                                            <th class="col-disc-amt">Disc Amt</th>
                                            <th style="width:100px">GST %</th>
                                            <th style="width:100px">IT %</th>
                                            <th style="width:100px">Adv %</th>
                                            <th class="col-amount">Amount</th>
                                            <th style="width:120px">Mfg Date</th>
                                            <th style="width:120px">Expiry</th>
                                            <th style="width:100px">Lot#</th>
                                            <th class="col-action">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseTableBody">
                                        @foreach ($purchase->items as $item)
                                            @php
                                                $sizeMode = $item->size_mode ?? 'by_pieces';
                                                $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : 1);
                                                $boxes = (float) ($item->boxes_qty ?? 0);
                                                $loose = (float) ($item->loose_qty ?? 0);

                                                $displayBoxes = (float) $item->qty;

                                                // Unit Label
                                                $unitLabel = '';
                                                if ($sizeMode == 'by_size') {
                                                    $unitLabel = '(m²)';
                                                } elseif ($sizeMode == 'by_cartons') {
                                                    $unitLabel = '(carton)';
                                                } else {
                                                    $unitLabel = '(piece)';
                                                }
                                            @endphp
                                            <tr data-sizemode="{{ $sizeMode }}"
                                                data-pieces_per_m2="{{ $item->pieces_per_m2 }}">
                                                <td>
                                                    {{-- Hidden product ID for form submission --}}
                                                    <input type="hidden" name="product_id[]" class="hidden-product-id" value="{{ $item->product_id }}">
                                                    {{-- Product select button --}}
                                                    <button type="button" class="product-select-btn has-value"
                                                        title="{{ $item->product->item_name }} ({{ $item->product->item_code }})">
                                                        {{ $item->product->item_name }}<br>
                                                        <small class="text-muted" style="font-size:0.7rem;font-weight:400;">{{ $item->product->item_code }}</small>
                                                        <span class="psm-btn-arrow">&#9660;</span>
                                                    </button>
                                                    {{-- Snapshots --}}
                                                    <input type="hidden" name="size_mode[]" class="hidden-size-mode"
                                                        value="{{ $sizeMode }}">
                                                    <input type="hidden" name="pieces_per_box[]"
                                                        class="hidden-pieces-per-box" value="{{ $ppb }}">
                                                    <input type="hidden" name="pieces_per_m2[]"
                                                        class="hidden-pieces-per-m2" value="{{ $item->pieces_per_m2 }}">
                                                    <input type="hidden" name="length[]" class="hidden-length"
                                                        value="{{ $item->length }}">
                                                    <input type="hidden" name="width[]" class="hidden-width"
                                                        value="{{ $item->width }}">
                                                    {{-- Hidden Box/Loose Calc fields --}}
                                                    <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty"
                                                        value="{{ $boxes }}">
                                                    <input type="hidden" name="loose_qty[]" class="hidden-loose-qty"
                                                        value="{{ $loose }}">
                                                </td>
                                                <td class="hs-code-col">
                                                    <input type="text" name="hs_code[]" class="form-control hs-code-input p-1" value="{{ $item->hs_code }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="text" name="qty[]" class="form-control box-qty"
                                                        value="{{ $displayBoxes }}" placeholder="Boxes">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control input-readonly pack-size"
                                                        value="{{ $ppb }}" readonly>
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control input-readonly qty-pcs"
                                                        value="{{ (float) ($item->qty * $ppb) }}" readonly>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="price[]" class="form-control price"
                                                            step="0.01" value="{{ (float) $item->price }}">
                                                    </div>
                                                    <small class="text-muted price-unit-label"
                                                        style="font-size:0.7rem;">{{ $unitLabel }}</small>
                                                </td>
                                                <td>
                                                    {{-- Calc Disc % from Amt --}}
                                                    @php
                                                        $gross = $item->line_total + $item->item_discount;
                                                        $dPct = $gross > 0 ? ($item->item_discount / $gross) * 100 : 0;
                                                    @endphp
                                                    <input type="number" class="form-control item-disc-percent"
                                                        value="{{ round($dPct, 2) }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="item_discount[]"
                                                        class="form-control item-disc-amt"
                                                        value="{{ (float) $item->item_discount }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="gst_percent[]" class="form-control gst text-end p-1" value="{{ (float) $item->gst_percent }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="it_percent[]" class="form-control inc-tax text-end p-1" value="{{ (float) ($item->it_percent ?? 0) }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="adv_tax_percent[]" class="form-control adv-tax text-end p-1" value="{{ (float) ($item->adv_tax_percent ?? 0) }}">
                                                </td>
                                                <td>
                                                    <input type="number" class="form-control input-readonly row-total text-end"
                                                        value="{{ (float) $item->line_total }}" readonly>
                                                    <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="{{ (float) $item->gst_amount }}">
                                                    <input type="hidden" class="row-sub-total" value="{{ (float) ($item->line_total - ($item->gst_amount ?? 0) - ($item->it_amount ?? 0) - ($item->adv_tax_amount ?? 0)) }}">
                                                </td>
                                                <td><input type="date" name="mfg_date[]" class="form-control p-1" value="{{ $item->mfg_date ? $item->mfg_date->format('Y-m-d') : ($item->batch?->mfg_date ? $item->batch->mfg_date->format('Y-m-d') : '') }}"></td>
                                                <td><input type="date" name="expiry[]" class="form-control p-1" value="{{ $item->exp_date ? $item->exp_date->format('Y-m-d') : ($item->batch?->exp_date ? $item->batch->exp_date->format('Y-m-d') : '') }}"></td>
                                                <td><input type="text" name="lot_no[]" class="form-control p-1" value="{{ $item->batch_no ?? ($item->batch?->batch_number ?? '') }}"></td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-danger remove-row border-0"><i
                                                            class="bi bi-x-lg"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="10" class="text-end fw-bold text-muted">Total Amount:</td>
                                            <td class="text-end fw-bold fs-6 text-dark"><span id="totalAmount">0.00</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY --}}
                <div class="row g-3 mt-1">
                    {{-- LEFT: Payment / Receipt Voucher --}}
                    <div class="col-lg-7">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Payment / Receipt Voucher</div>
                            <div id="paymentWrapper" class="border rounded p-3 bg-light mb-3">
                                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                    <select class="form-select rv-account" name="payment_account_id[]"
                                        style="max-width: 300px; flex-grow: 1;">
                                        <option value="" selected disabled>Select Account</option>
                                        @foreach ($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" class="form-control text-end payment-amount"
                                        name="payment_amount[]" placeholder="Amount" style="width:140px">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddPayment">
                                        <i class="bi bi-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="me-2 fw-bold text-muted">Total Paid:</span>
                                <span class="fw-bold fs-6 text-success" id="totalPaid">0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Summary --}}
                    <div class="col-lg-5">
                        <div class="card-panel shadow-sm">
                            <div class="section-title mb-3">Summary</div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Total Qty (Pieces)</div>
                                <div class="col-5 text-end"><span id="tQty" class="fw-bold">0</span></div>
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Sub-Total</div>
                                <div class="col-5 text-end fw-bold"><span id="tSub">0.00</span></div>
                                <input type="hidden" name="subtotal" id="subtotalInput">
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Bill Discount</div>
                                <div class="col-5 text-end">
                                    <input type="number" class="form-control text-end form-control-sm" name="discount"
                                        id="billDiscount" value="{{ (float) $purchase->discount }}">
                                </div>
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Extra Cost</div>
                                <div class="col-5 text-end">
                                    <input type="number" class="form-control text-end form-control-sm" name="extra_cost"
                                        id="extraCost" value="{{ (float) $purchase->extra_cost }}">
                                </div>
                            </div>
                            <div class="row py-1 align-items-center">
                                <div class="col-7 text-muted fw-medium">Freight Charges</div>
                                <div class="col-5 text-end">
                                    <input type="number" class="form-control text-end form-control-sm"
                                        name="freight_charges" id="freightCharges"
                                        value="{{ (float) $purchase->freight_charges ?? 0 }}">
                                </div>
                            </div>
                            <hr class="my-2 border-secondary">
                            <div class="row py-2">
                                <div class="col-6 fw-bold fs-5 text-primary">Net Payable</div>
                                <div class="col-6 text-end fw-bold fs-5 text-primary"><span id="tPayable">0.00</span>
                                </div>
                                <input type="hidden" name="net_amount" id="netAmountInput">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i> Update Purchase
                    </button>
                    <div class="mt-3 bg-light p-3 rounded border shadow-sm">
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input mt-0" type="checkbox" id="gst_invoice" name="is_gst_invoice" {{ $purchase->is_gst_invoice ? 'checked' : '' }} style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                <label class="form-check-label fw-bold text-dark" for="gst_invoice" style="cursor:pointer;">GST INVOICE</label>
                            </div>
                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                <input class="form-check-input mt-0" type="checkbox" id="enable_hs_code" name="enable_hs_code" checked style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                <label class="form-check-label fw-bold text-dark" for="enable_hs_code" style="cursor:pointer;">ENABLE HS CODE</label>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>

    {{-- ERP Product Modal (shared component) --}}
    @include('admin_panel.components.product_select_modal')

@endsection

@section('js')
    <script>
        $(document).ready(function() {
            function num(n) {
                return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
            }

            // Init Global Select2
            $('.select2').select2({
                width: '100%'
            });

            // Recalc existing rows
            recalcAll();

            // Add Row
            window.addBlankRow = function() {
                const html = `
                <tr>
                    <td>
                        <input type="hidden" name="product_id[]" class="hidden-product-id" value="">
                        <button type="button" class="product-select-btn">Select Product <span class="psm-btn-arrow">&#9660;</span></button>
                        <input type="hidden" name="size_mode[]" class="hidden-size-mode" value="">
                        <input type="hidden" name="pieces_per_box[]" class="hidden-pieces-per-box" value="1">
                        <input type="hidden" name="pieces_per_m2[]" class="hidden-pieces-per-m2" value="0">
                        <input type="hidden" name="length[]" class="hidden-length" value="">
                        <input type="hidden" name="width[]" class="hidden-width" value="">
                        <input type="hidden" name="boxes_qty[]" class="hidden-boxes-qty" value="0">
                        <input type="hidden" name="loose_qty[]" class="hidden-loose-qty" value="0">
                    </td>
                    <td class="hs-code-col"><input type="text" name="hs_code[]" class="form-control hs-code-input p-1" readonly></td>
                    <td><input type="text" name="qty[]" class="form-control box-qty" placeholder="Boxes"></td>
                    <td><input type="number" class="form-control input-readonly pack-size" value="1" readonly></td>
                    <td><input type="number" class="form-control input-readonly qty-pcs" value="0" readonly></td>
                    <td><div class="input-group input-group-sm"><input type="number" name="price[]" class="form-control price" step="0.01" value="0"></div></td>
                    <td><input type="number" class="form-control item-disc-percent" value="0"></td>
                    <td><input type="number" name="item_discount[]" class="form-control item-disc-amt" value="0"></td>
                    <td><input type="number" name="gst_percent[]" class="form-control gst text-end p-1" value="0"></td>
                    <td><input type="number" name="it_percent[]" class="form-control inc-tax text-end p-1" value="0"></td>
                    <td><input type="number" name="adv_tax_percent[]" class="form-control adv-tax text-end p-1" value="0"></td>
                    <td><input type="number" class="form-control input-readonly row-total text-end" value="0" readonly>
                        <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="0">
                        <input type="hidden" class="row-sub-total" value="0">
                    </td>
                    <td><input type="date" name="mfg_date[]" class="form-control p-1"></td>
                    <td><input type="date" name="expiry[]" class="form-control p-1"></td>
                    <td><input type="text" name="lot_no[]" class="form-control p-1"></td>
                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row border-0"><i class="bi bi-x-lg"></i></button></td>
                </tr>`;
                const $row = $(html);
                $('#purchaseTableBody').append($row);
                recalcAll();
                return $row;
            };

            // Remove Row
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
                recalcAll();
            });

            // Inputs -> Calc
            $('#purchaseTableBody').on('input', '.box-qty, .price, .item-disc-percent, .item-disc-amt, .gst, .inc-tax, .adv-tax', function() {
                if ($(this).hasClass('box-qty')) {
                    normalizeQtyInput($(this), $(this).closest('tr'));
                }
                recalcRow($(this).closest('tr'));
                recalcAll();
            });

            $('#billDiscount, #extraCost, #freightCharges').on('input', function() {
                recalcAll();
            });

            // --- Payment Section Logic ---
            $('#btnAddPayment').on('click', function() {
                const row = `
                <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                    <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 300px; flex-grow: 1;">
                        <option value="" selected disabled>Select Account</option>
                        @foreach ($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                        @endforeach
                    </select>
                    <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="Amount" style="width:140px">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-payment">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>`;
                $('#paymentWrapper').append(row);
            });

            $(document).on('click', '.remove-payment', function() {
                $(this).closest('.payment-row').remove();
                recalcPayments();
            });

            $(document).on('input', '.payment-amount', function() {
                recalcPayments();
            });

            function recalcPayments() {
                let total = 0;
                $('.payment-amount').each(function() {
                    total += parseFloat($(this).val()) || 0;
                });
                $('#totalPaid').text(total.toFixed(2));
            }

            @if (isset($purchase->payments) && $purchase->payments->count() > 0)
                @php $firstPay = true; @endphp
                @foreach($purchase->payments as $pay)
                    @if($firstPay)
                        // Update existing first row if possible
                        $('.payment-row:first').find('.rv-account').val('{{ $pay->account_id }}');
                        $('.payment-row:first').find('.payment-amount').val('{{ (float)$pay->amount }}');
                        @php $firstPay = false; @endphp
                    @else
                        // Append new rows for subsequent payments
                        const htmlPay = `
                            <div class="d-flex gap-2 align-items-center mb-2 payment-row flex-wrap">
                                <select class="form-select rv-account" name="payment_account_id[]" style="max-width: 300px; flex-grow: 1;">
                                    <option value="" disabled>Select Account</option>
                                    @foreach ($accounts as $acc)
                                        <option value="{{ $acc->id }}" {{ $acc->id == $pay->account_id ? 'selected' : '' }}>{{ $acc->title }}</option>
                                    @endforeach
                                </select>
                                <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" value="{{ (float)$pay->amount }}" placeholder="Amount" style="width:140px">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-payment">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>`;
                        $('#paymentWrapper').append(htmlPay);
                    @endif
                @endforeach
                recalcPayments();
            @endif

            function normalizeQtyInput($input, $row) {
                // Same logic as add_purchase_v2
                const val = $input.val();
                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();

                if (sizeMode === 'by_pieces') {
                    if (val.includes('.')) {
                        $input.val(val.split('.')[0]);
                    }
                    return;
                }

                if (ppb > 1 && val.includes('.')) {
                    const parts = val.split('.');
                    const boxes = parseInt(parts[0]) || 0;
                    const loose = parts[1] ? parseInt(parts[1]) : 0;

                    if (loose >= ppb) {
                        const extraBoxes = Math.floor(loose / ppb);
                        const newLoose = loose % ppb;
                        const newBoxes = boxes + extraBoxes;
                        let newVal = newBoxes.toString();
                        if (newLoose > 0) newVal += '.' + newLoose;
                        $input.val(newVal);
                    }
                }
            }

            function recalcRow($row) {
                let boxesStr = $row.find('.box-qty').val();
                if (!boxesStr) boxesStr = "0";
                boxesStr = boxesStr.toString();

                const ppb = parseFloat($row.find('.pack-size').val()) || 1;
                const sizeMode = $row.data('sizemode') || $row.find('.hidden-size-mode').val();
                const pieces_per_m2 = parseFloat($row.data('pieces_per_m2')) || parseFloat($row.find(
                    '.hidden-pieces-per-m2').val()) || 0;

                let boxes = 0;
                let loose = 0;
                let totalPieces = 0;

                if (ppb > 1 && boxesStr.includes('.')) {
                    const parts = boxesStr.split('.');
                    boxes = parseInt(parts[0]) || 0;
                    loose = parts[1] ? parseInt(parts[1]) : 0;
                    totalPieces = (boxes * ppb) + loose;
                } else {
                    boxes = parseFloat(boxesStr) || 0;
                    totalPieces = boxes * ppb;
                }

                // Update qty field value with fractional boxes if loose pieces present
                if (loose > 0 && ppb > 0) {
                    $row.find('.box-qty').val((boxes + loose / ppb).toFixed(3));
                }

                // Update hidden separate fields
                $row.find('.hidden-boxes-qty').val(boxes);
                $row.find('.hidden-loose-qty').val(loose);

                $row.find('.qty-pcs').val(totalPieces);

                const price = parseFloat($row.find('.price').val()) || 0;

                // --- TOTAL CALCULATION ---
                let grossTotal = 0;

                if (sizeMode == 'by_size') {
                    // Price is per M2. Total M2 = totalPieces * pieces_per_m2 (m2/piece)
                    grossTotal = (totalPieces * pieces_per_m2) * price;
                } else if (sizeMode == 'by_cartons' || sizeMode == 'by_carton') {
                    // Price is per Carton/Box
                    grossTotal = (totalPieces / ppb) * price;
                } else {
                    // Price is per Piece (Standardized)
                    grossTotal = totalPieces * price;
                }

                // Discount
                let discAmt = parseFloat($row.find('.item-disc-amt').val()) || 0;
                // If focus on %, calc amt
                if ($(document.activeElement).hasClass('item-disc-percent')) {
                    const pct = parseFloat($row.find('.item-disc-percent').val()) || 0;
                    discAmt = grossTotal > 0 ? grossTotal * (pct / 100) : 0;
                    $row.find('.item-disc-amt').val(discAmt.toFixed(2));
                } else {
                    // Else calc % from amt (default or if amt edited)
                    const pct = grossTotal > 0 ? (discAmt / grossTotal) * 100 : 0;
                    $row.find('.item-disc-percent').val(pct.toFixed(2));
                }

                const net = grossTotal - discAmt;
                const gstPercent = num($row.find('.gst').val());
                const itPercent = num($row.find('.inc-tax').val());
                const advPercent = num($row.find('.adv-tax').val());

                const gstAmount = net * (gstPercent / 100); 
                const lineNet = net + gstAmount + (net * (itPercent + advPercent) / 100);

                $row.find('.row-sub-total').val(net.toFixed(2));
                $row.find('.gst-amount-row').val(gstAmount.toFixed(2));
                $row.find('.row-total').val(lineNet.toFixed(2));
            }

            function recalcAll() {
                let totalQty = 0;
                let subtotal = 0;
                let totalTaxAmt = 0;

                $('#purchaseTableBody tr').each(function() {
                    const qty = num($(this).find('.qty-pcs').val());
                    const rowSub = num($(this).find('.row-sub-total').val());
                    const rowGstPer = num($(this).find('.gst').val());
                    const rowItPer = num($(this).find('.inc-tax').val());
                    const rowAdvPer = num($(this).find('.adv-tax').val());
                    
                    totalQty += qty;
                    subtotal += rowSub;
                    
                    let rowTaxes = rowSub * (rowGstPer + rowItPer + rowAdvPer) / 100;
                    totalTaxAmt += rowTaxes;
                });

                $('#tQty').text(totalQty.toFixed(2));
                $('#tSub').text(subtotal.toFixed(2));
                $('#subtotalInput').val(subtotal.toFixed(2));
                $('#totalAmount').text(subtotal.toFixed(2));

                const billDisc = num($('#billDiscount').val());
                const extraCost = num($('#extraCost').val());
                const freightCharges = num($('#freightCharges').val());

                const netPayable = subtotal - billDisc + extraCost + freightCharges + totalTaxAmt;

                $('#tPayable').text(netPayable.toFixed(2));
                $('#netAmountInput').val(netPayable.toFixed(2));
            }

            function toggleGstFields() {
                let isGst = $('#gst_invoice').is(':checked');
                if (isGst) {
                    $('.gst, .inc-tax, .adv-tax').prop('readonly', false).css('background', '');
                } else {
                    $('.gst, .inc-tax, .adv-tax').val(0).prop('readonly', true).css('background', '#f8fafc');
                }
                $('#purchaseTableBody tr').each(function() {
                    recalcRow($(this));
                });
                recalcAll();
            }

            $('#gst_invoice').on('change', toggleGstFields);
            setTimeout(toggleGstFields, 500);

            function toggleHsCodeFields() {
                const isEnabled = $('#enable_hs_code').is(':checked');
                if (isEnabled) {
                    $('.hs-code-col').show();
                } else {
                    $('.hs-code-col').hide();
                }
            }
            $('#enable_hs_code').on('change', toggleHsCodeFields);
            toggleHsCodeFields();

            /* ── Product Select Button → ERP Modal ── */
            $(document).on('click', '.product-select-btn', function() {
                var $btn = $(this);
                var $row = $btn.closest('tr');

                var currentId = $row.find('.hidden-product-id').val();
                var allIds = [];
                $('#purchaseTableBody tr').each(function() {
                    var id = $(this).find('.hidden-product-id').val();
                    if (id) allIds.push(parseInt(id));
                });

                ERPProductModal.open({
                    priceField: 'purchase',
                    targetRow: $row,
                    selectedIds: currentId ? [parseInt(currentId)] : [],
                    existingIds: allIds,
                    onSelect: function(products) {
                        products.forEach(function(p, idx) {
                            var $targetRow = (idx === 0) ? $row : null;
                            if (idx > 0) {
                                // Add new blank row then populate
                                addBlankRow();
                                $targetRow = $('#purchaseTableBody tr:last');
                            }
                            populateEditRow($targetRow, p);
                        });
                    }
                });
            });

            function populateEditRow($row, p) {
                const sizeMode = p.size_mode || 'by_pieces';
                const ppb = parseFloat(p.pieces_per_box) || 1;
                const pM2 = parseFloat(p.purchase_price_per_m2) || 0;
                const pPiece = parseFloat(p.purchase_price_per_piece) || 0;

                // Hidden product ID
                $row.find('.hidden-product-id').val(p.id);

                // Update button label
                $row.find('.product-select-btn')
                    .addClass('has-value')
                    .html(
                        (p.item_name || p.name || '') +
                        '<br><small class="text-muted" style="font-size:0.7rem;font-weight:400;">' +
                        (p.item_code || p.sku || '') +
                        '</small><span class="psm-btn-arrow">&#9660;</span>'
                    );

                // Snapshots
                $row.find('.hidden-size-mode').val(sizeMode);
                $row.find('.hidden-pieces-per-box').val(ppb);
                $row.find('.hidden-pieces-per-m2').val(p.pieces_per_m2 || 0);
                $row.find('.hidden-length').val(p.length || '');
                $row.find('.hidden-width').val(p.width || '');

                // Pack size
                $row.find('.pack-size').val(ppb);
                $row.find('.hs-code-input').val(p.hs_code || '');

                // Price & label
                let price = 0;
                let unitLabel = '';
                if (sizeMode === 'by_size') {
                    price = pM2;
                    unitLabel = '(m²)';
                } else if (sizeMode === 'by_cartons') {
                    price = pPiece * ppb;
                    unitLabel = '(carton)';
                } else {
                    price = pPiece;
                    unitLabel = '(piece)';
                }
                $row.find('.price').val(price);
                $row.find('.price-unit-label').remove();
                $row.find('.price').after('<small class="text-muted price-unit-label" style="font-size:0.7rem;">' + unitLabel + '</small>');

                // Row data attrs
                $row.data('sizemode', sizeMode);
                $row.data('pieces_per_m2', parseFloat(p.pieces_per_m2) || 0);

                // Auto-fill last purchase price
                if (p.id) {
                    $.getJSON("{{ url('purchase/product') }}/" + p.id + "/last-price", function(res) {
                        if (res && res.price && res.price > 0) {
                            $row.find('.price').val(res.price);
                            recalcRow($row);
                            recalcAll();
                        }
                    }).fail(function(err) {
                        console.error('Failed to fetch last purchase price:', err);
                    });
                }

                // Ensure batch info stays empty for manual entry on new selections
                $row.find('input[name="mfg_date[]"]').val('');
                $row.find('input[name="expiry[]"]').val('');
                $row.find('input[name="lot_no[]"]').val('');

                $row.find('.box-qty').focus();
                recalcRow($row);
                recalcAll();
            }
        });
    </script>
@endsection
