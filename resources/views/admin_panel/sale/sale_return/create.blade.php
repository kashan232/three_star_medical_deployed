@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* Professional ERP Styling */
        :root {
            --erp-primary: #4a69bd;
            /* Professional Blue */
            --erp-bg: #f5f6fa;
            --erp-border: #dcdde1;
            --erp-text: #2f3640;
            --erp-muted: #7f8fa6;
        }

        body {
            background-color: var(--erp-bg);
            color: var(--erp-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .erp-card {
            background: white;
            border: 1px solid var(--erp-border);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .erp-header {
            background: white;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--erp-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 8px 8px 0 0;
        }

        .erp-header h5 {
            margin: 0;
            font-weight: 600;
            color: var(--erp-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--erp-muted);
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .form-control,
        .form-select {
            border-radius: 4px;
            border: 1px solid var(--erp-border);
            padding: 0.4rem 0.75rem;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: var(--erp-primary);
            box-shadow: 0 0 0 2px rgba(74, 105, 189, 0.2);
        }

        .form-control[readonly] {
            background-color: #f9fafb;
            /* Minimalist light gray */
            color: var(--erp-muted);
            font-weight: 500;
            cursor: not-allowed;
            pointer-events: none;
            border-color: var(--erp-border);
            /* Standard border, no pattern */
            opacity: 1;
            /* Full opacity for clarity */
        }

        .form-control[readonly]:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }

        /* Table Styles */
        .erp-table-container {
            border: 1px solid var(--erp-border);
            border-radius: 6px;
            overflow: hidden;
        }

        .table-erp {
            width: 100%;
            margin-bottom: 0;
        }

        .table-erp thead th {
            background-color: #f1f2f6;
            color: var(--erp-text);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            padding: 0.75rem;
            border-bottom: 2px solid var(--erp-border);
            white-space: nowrap;
        }

        .table-erp tbody td {
            vertical-align: middle;
            padding: 0.5rem;
            border-bottom: 1px solid #f1f2f6;
        }

        .table-erp input.form-control {
            border: 1px solid transparent;
            background: transparent;
            padding: 0.25rem 0.5rem;
            height: auto;
        }

        .table-erp input.form-control:focus,
        .table-erp input.form-control:hover {
            border-color: var(--erp-border);
            background: white;
        }

        /* Read-only inputs in table */
        .table-erp input.form-control[readonly] {
            cursor: not-allowed;
            pointer-events: none;
            background-color: transparent;
            /* Seamless integration */
            color: #adb5bd;
            /* Muted text */
            font-weight: 400;
            border-color: transparent;
        }

        .table-erp input.form-control[readonly]:hover,
        .table-erp input.form-control[readonly]:focus {
            background-color: #f8f9fa;
            border-color: transparent;
            box-shadow: none;
            cursor: not-allowed;
        }

        .summary-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            border: 1px solid var(--erp-border);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .summary-row.total {
            border-top: 1px solid var(--erp-border);
            padding-top: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--erp-primary);
        }

        .btn-erp-primary {
            background-color: var(--erp-primary);
            color: white;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border: none;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .btn-erp-primary:hover {
            background-color: #3c5aa6;
            color: white;
            transform: translateY(-1px);
        }

        /* Select2 Tweaks */
        .select2-container .select2-selection--single {
            height: 36px;
            border-color: var(--erp-border);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 36px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }

        .select2-container--default .select2-selection--multiple {
            border-color: var(--erp-border);
        }

        /* Editable Field Highlighting */
        /* Editable Field Highlighting - Minimal */
        .quantity-box:not([readonly]),
        #extraDiscount {
            background-color: #fff;
            font-weight: 600;
            color: var(--erp-text);
            transition: all 0.2s;
        }

        /* Only show focus ring when active */
        .quantity-box:not([readonly]):focus,
        #extraDiscount:focus {
            border-color: var(--erp-primary) !important;
            box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.1) !important;
        }

        .quantity-box:not([readonly]):hover {
            border-color: #b0bdd1;
        }

        /* Visual indicator for editable vs read-only */
        .form-label .fa-lock {
            color: #dee2e6;
            /* Very subtle lock icon */
        }
    </style>


    <!-- Structure Wrapper -->
    <div class="container-fluid py-4">
        <div class="erp-card">
            <div class="erp-header">
                <h5><i class="fas fa-undo-alt me-2"></i> Sale Return</h5>
                <div class="d-flex align-items-center gap-3">
                    <button type="button" class="btn btn-sm btn-info text-white shadow-sm" id="btnImportSIN">
                        <i class="fas fa-file-import me-1"></i> Import Sale Invoice Notes
                    </button>
                    @if ($sale)
                        <span class="badge bg-light text-dark border"><i class="fas fa-file-invoice me-1"></i> Original
                            Invoice #
                            {{ $sale->invoice_no }}</span>
                    @endif
                    <a href="{{ route('sale.return.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
                </div>
            </div>

            <div class="card-body p-4">
                <form action="{{ route('sale.return.store') }}" method="POST" id="SaleReturnForm">
                    @csrf
                    <input type="hidden" name="sale_id" value="{{ optional($sale)->id }}">

                    <!-- Alert Section -->
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> <strong>Validation Errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Top Section: Customer, Warehouse & Reference -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" id="customer_id_select" class="form-select form-select-sm select2" {{ $sale ? 'disabled' : 'required' }}>
                                <option value="">Select Customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}" {{ (optional($sale)->customer_id == $customer->id) ? 'selected' : '' }}>
                                        {{ $customer->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($sale)
                                <input type="hidden" name="customer_id" value="{{ $sale->customer_id }}">
                            @endif
                        </div>
                        <div class="col-md-2">
                            <label class="form-label"><i class="fas fa-lock text-muted me-1"
                                    style="font-size: 0.7rem;"></i>Reference / PO #</label>
                            <input type="text" name="reference" class="form-control form-control-sm"
                                value="{{ optional($sale)->invoice_no ?? '' }}" readonly>
                        </div>
                        <div class="col-md-5 text-end align-self-end">
                            <div class="p-2 bg-light rounded d-inline-block border">
                                <small class="text-muted d-block text-start" style="font-size: 0.7rem;">ORIGINAL Sale
                                    DATE</small>
                                <strong class="text-dark"><i class="far fa-calendar-alt me-1"></i>
                                    <span
                                        id="originalDateDisplay">{{ $sale ? $sale->created_at->format('d M, Y h:i A') : '-' }}</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Return Info -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Return Date <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Return Reason / Notes</label>
                            <input type="text" name="return_reason" class="form-control"
                                placeholder="e.g., Damaged goods, Wrong item sent">
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnReturnAll">
                            <i class="fas fa-check-double me-1"></i> Return All
                        </button>
                    </div>
                    <div class="table-responsive erp-table-container mb-4">
                        <table class="table table-erp table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 12%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Product</th>
                                    <th style="width: 10%;">Warehouse</th>
                                    <th style="width: 8%;">Lot #</th>
                                    <th style="width: 8%;">Mfg/Exp</th>
                                    <th style="width: 5%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>P/B</th>
                                    <th style="width: 8%; text-align:right;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Price</th>
                                    <th style="width: 8%;"><i class="fas fa-lock me-1"
                                            style="font-size: 0.65rem; opacity: 0.6;"></i>Rem.</th>
                                    <th style="width: 8%;">Return Pcs</th>
                                    <th style="width: 15%;">Discount</th>
                                    <th style="width: 8%;">GST</th>
                                    <th style="width: 10%;">Total</th>
                                    <th style="width: 5%;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="returnItems">
                                @foreach ($saleItems as $index => $item)
                                    @php
                                        $original  = (float)($item['original_qty'] ?? $item['qty'] ?? 0);
                                        $returned  = (float)($item['returned_qty'] ?? 0);
                                        $remaining = (float)($item['max_returnable'] ?? max(0, $original - $returned));
                                    @endphp
                                    <tr>
                                        <input type="hidden" name="product_id[]"        value="{{ $item['product_id'] }}">
                                        <input type="hidden" name="sale_item_id[]"  value="{{ $item['sale_item_id'] ?? '' }}">
                                        <input type="hidden" name="unit[]"              value="{{ $item['unit'] ?? 'pc' }}">
                                        <input type="hidden" name="size_mode[]"  class="size-mode"       value="{{ $item['size_mode'] ?? 'by_pieces' }}">
                                        <input type="hidden" name="pieces_per_m2[]" class="pieces-per-m2" value="{{ $item['pieces_per_m2'] ?? 0 }}">

                                        <td>
                                            <input type="text" class="form-control fw-bold" value="{{ ($item['brand'] ?? '') . ' ' . $item['item_name'] }}" readonly>
                                            <small class="text-muted d-block ms-1" style="font-size:0.7rem;">{{ $item['item_code'] ?? '' }}</small>
                                        </td>

                                        <td>
                                            <select class="form-select form-select-sm" disabled>
                                                @foreach($warehouses as $wh)
                                                    <option value="{{ $wh->id }}" {{ ($item['warehouse_id'] ?? 1) == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" name="warehouse_id[]" value="{{ $item['warehouse_id'] ?? (optional($sale)->warehouse_id ?? 1) }}">
                                        </td>

                                        <td>
                                            <input type="text" name="batch_no[]" class="form-control text-center" value="{{ $item['batch_no'] ?? '' }}">
                                        </td>

                                        {{-- Mfg / Exp combined in one column --}}
                                        <td>
                                            <input type="date" name="mfg_date[]" class="form-control form-control-sm" value="{{ $item['mfg_date'] ?? '' }}">
                                            <input type="date" name="exp_date[]" class="form-control form-control-sm mt-1" value="{{ $item['exp_date'] ?? '' }}">
                                        </td>

                                        {{-- P/B --}}
                                        <td>
                                            <input type="number" class="form-control text-center pieces-per-box" value="{{ $item['pieces_per_box'] ?? 1 }}" readonly>
                                            <small class="text-muted d-block text-center" style="font-size:0.65rem;">{{ $item['uom_name'] ?? '' }}</small>
                                        </td>

                                        {{-- Price per Pc --}}
                                        <td>
                                            <input type="number" name="price[]" step="0.01" class="form-control text-end price" value="{{ (float) $item['price'] }}" readonly>
                                            <small class="text-muted d-block text-end" style="font-size:0.65rem;">Per Pc</small>
                                        </td>

                                        {{-- Remaining (read-only display) --}}
                                        <td>
                                            <input type="text" class="form-control text-center text-muted" value="{{ $remaining }}" readonly
                                                   title="Original: {{ $original }} | Returned: {{ $returned }} | Remaining: {{ $remaining }}">
                                            @if ($returned > 0)
                                                <small class="d-block text-center text-danger" style="font-size:0.65rem;">Rtnd: {{ $returned }}</small>
                                            @endif
                                        </td>

                                        {{-- Return Qty (editable) --}}
                                        <td>
                                            <input type="number" name="qty[]" step="any"
                                                   class="form-control text-center fw-bold text-primary quantity-pieces"
                                                   value="0" min="0" placeholder="Pcs"
                                                   data-max="{{ $remaining }}"
                                                   data-ppb="{{ $item['pieces_per_box'] ?? 1 }}"
                                                   data-sizemode="{{ $item['size_mode'] ?? 'by_pieces' }}">
                                        </td>

                                        {{-- Discount --}}
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" step="any" name="item_disc_val[]" class="form-control item-disc-val" value="{{ (float)($item['discount'] ?? 0) }}">
                                                <select name="item_disc_type[]" class="form-select item-disc-type" style="max-width:60px;">
                                                    <option value="percent" {{ ($item['discount_type'] ?? '') === 'percent' ? 'selected' : '' }}>%</option>
                                                    <option value="amount"  {{ ($item['discount_type'] ?? 'amount') === 'amount'  ? 'selected' : '' }}>Amt</option>
                                                </select>
                                            </div>
                                            <input type="hidden" class="row-disc" value="0.00">
                                        </td>

                                        {{-- GST --}}
                                        <td>
                                            <input type="hidden" name="gst_percent[]"  class="gst-percent"  value="{{ (float)($item['gst_percent'] ?? 0) }}">
                                            <input type="hidden" name="original_qty[]" class="original-qty" value="{{ $original }}">
                                            <div class="row-gst-amount fw-500">0.00</div>
                                            <small class="text-muted" style="font-size:0.65rem;">{{ (float)($item['gst_percent'] ?? 0) }}%</small>
                                        </td>

                                        {{-- Row Total --}}
                                        <td>
                                            <input type="text" name="row_total[]" class="form-control text-end fw-bold row-total" value="0.00" readonly>
                                        </td>

                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger border-0 remove-row rounded-circle" title="Remove"><i class="fas fa-times"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Footer Summary -->
                    <div class="row mt-4">
                        <div class="col-md-7">
                            <div class="p-4 bg-light rounded border h-100">
                                <label class="form-label text-muted small">AMOUNT IN WORDS</label>
                                <input type="text" name="total_amount_Words"
                                    class="form-control border-0 bg-transparent fw-bold text-primary fs-5 fst-italic"
                                    id="amountInWords" readonly placeholder="...">

                                <div class="mt-4 pt-4 border-top">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-cubes text-muted"></i>
                                        <span class="text-muted small">Total Return Qty:</span>
                                        <strong id="totalReturnQty" class="text-dark fs-5">0</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Partial Return Status Indicator -->
                        <div class="col-md-12 mb-3">
                            <div class="partial-return-indicator shadow-sm border-0 p-3 rounded"
                                style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0 text-white">
                                        <i class="fas fa-chart-pie me-2"></i>Return Status
                                    </h6>
                                    <span id="returnTypeBadge" class="badge bg-light text-dark">
                                        <i class="fas fa-spinner fa-spin me-1"></i>Calculating...
                                    </span>
                                </div>
                                <div class="progress" style="height: 25px; background: rgba(255,255,255,0.2);">
                                    <div id="returnProgressBar"
                                        class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                        style="width: 0%; background: #10ac84;" aria-valuenow="0" aria-valuemin="0"
                                        aria-valuemax="100">
                                        <strong id="returnPercentage">0%</strong>
                                    </div>
                                </div>
                                <div class="mt-2 text-white small" id="returnStatusText">
                                    <i class="fas fa-info-circle me-1"></i>Select items to return
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="summary-card shadow-sm border-0">
                                <h6 class="mb-3 text-uppercase fw-bold text-muted"
                                    style="font-size: 0.8rem; letter-spacing: 1px;">Refund Summary</h6>
                                <div class="summary-row">
                                    <span class="text-muted">Gross Amount</span>
                                    <input type="text" id="billAmount"
                                        class="form-control form-control-sm w-50 text-end border-0 bg-transparent p-0"
                                        readonly value="0.00">
                                </div>
                                <div class="summary-row">
                                    <span class="text-muted">Total GST</span>
                                    <input type="text" id="totalGst"
                                        class="form-control form-control-sm w-50 text-end border-0 bg-transparent p-0 text-info"
                                        readonly value="0.00">
                                </div>
                                <div class="summary-row">
                                    <span class="text-muted">Total Discount</span>
                                    <input type="text" name="total_discount" id="itemDiscount"
                                        class="form-control form-control-sm w-50 text-end border-0 bg-transparent p-0 text-danger"
                                        readonly value="0.00">
                                </div>
                                <div class="summary-row align-items-center mt-2">
                                    <span class="text-dark fw-medium">Extra Deductions</span>
                                    <div class="input-group input-group-sm w-50">
                                        <input type="number" name="extra_discount" id="extraDiscount"
                                            class="form-control text-end bg-white" value="0">
                                        <select name="extra_discount_type" id="extraDiscountType" class="form-select" style="max-width: 65px;">
                                            <option value="amount">Amt</option>
                                            <option value="percent">%</option>
                                        </select>
                                    </div>
                                </div>
                                <hr class="my-3">
                                <div class="summary-row total">
                                    <span>NET REFUND AMOUNT</span>
                                    <input type="text" name="net_amount" id="netAmount"
                                        class="form-control form-control-lg w-50 text-end border-0 bg-transparent p-0 fw-bold text-primary"
                                        readonly value="0.00">
                                </div>

                                <!-- Payment Voucher Section -->
                                <div class="mt-4 pt-4 border-top">
                                    <h6 class="mb-3 text-uppercase fw-bold text-muted"
                                        style="font-size: 0.8rem; letter-spacing: 1px;">
                                        <i class="fas fa-money-bill-wave me-2"></i>Refund Received (Optional)
                                    </h6>

                                    <div class="alert alert-light border small text-muted">
                                        <i class="fas fa-info-circle me-1"></i> If you received cash/bank refund, enter
                                        details. Otherwise, amount will be credited to Customer Ledger.
                                    </div>

                                    <div class="payment-voucher-rows">
                                        <div class="payment-row mb-2">
                                            <div class="row g-2">
                                                <div class="col-7">
                                                    <select name="payment_account_id[]"
                                                        class="form-select form-select-sm payment-account">
                                                        <option value="">Select Account (Cash/Bank)</option>
                                                        @foreach ($accounts as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}
                                                                ({{ $acc->account_code }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-5">
                                                    <input type="number" name="payment_amount[]" step="0.01"
                                                        class="form-control form-control-sm text-end payment-amount"
                                                        placeholder="Amount">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                                <div class="mt-4 d-grid gap-2">
                                    <button type="submit" id="btnSubmitReturn"
                                        class="btn btn-erp-primary btn-lg shadow-sm">
                                        <i class="fas fa-check-circle me-2"></i> Process Sale Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- SIN Import Modal -->
    <div class="modal fade" id="sinModal" tabindex="-1" aria-labelledby="sinModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="sinModalLabel"><i class="fas fa-file-invoice me-2"></i> Select Goods
                        Receipt
                        Note (SIN)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 bg-light border-bottom">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i
                                    class="fas fa-search text-muted"></i></span>
                            <input type="text" id="sinSearch" class="form-control border-start-0"
                                placeholder="Search by Invoice # or Customer...">
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0" id="SINTable">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th class="ps-3">Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="sinList">
                                <!-- Loaded via AJAX -->
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="spinner-border text-info spinner-border-sm me-2"></div> Loading
                                        Sales...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const allWarehouses = @json($warehouses);
        $(document).ready(function() {

            // Initialize Select2
            // Initialize Select2
            if ($.fn.select2) {
                $('.select2').select2();
                $('.payment-account').select2();
            } else {
                console.warn('Select2 not loaded');
            }

            function num(n) {
                return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
            }

            function numberToWords(number) {
                const a = ["", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine", "Ten",
                    "Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen",
                    "Eighteen", "Nineteen"
                ];
                const b = ["", "", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety"];

                function convert_hundreds(num) {
                    let str = "";
                    if (num > 99) {
                        str += a[Math.floor(num / 100)] + " Hundred ";
                        num %= 100;
                    }
                    if (num > 0) {
                        if (str !== "") str += "and ";
                        if (num < 20) {
                            str += a[num];
                        } else {
                            str += b[Math.floor(num / 10)];
                            if (num % 10 > 0) str += " " + a[num % 10];
                        }
                    }
                    return str;
                }

                if (number === 0) return "Zero Rupees Only";
                if (number < 0) return "Minus " + numberToWords(Math.abs(number));

                let str = "";
                let crores = Math.floor(number / 10000000);
                number %= 10000000;
                let lakhs = Math.floor(number / 100000);
                number %= 100000;
                let thousands = Math.floor(number / 1000);
                number %= 1000;
                let hundreds = number;

                if (crores > 0) str += convert_hundreds(crores) + " Crore ";
                if (lakhs > 0) str += convert_hundreds(lakhs) + " Lakh ";
                if (thousands > 0) str += convert_hundreds(thousands) + " Thousand ";
                if (hundreds > 0) str += convert_hundreds(hundreds);

                return str.trim() + " Rupees Only";
            }

             function recalcRow($row) {
                const pieces = num($row.find('.quantity-pieces').val());
                const price = num($row.find('.price').val());
                
                const gstPercent = num($row.find('.gst-percent').val());
                const discVal = num($row.find('.item-disc-val').val());
                const discType = $row.find('.item-disc-type').val() || 'amount';
                const originalQty = num($row.find('.original-qty').val()) || 1;

                // Per user request: ALWAYS treat as Pieces * Price
                const gross = pieces * price;

                let rowDiscount = 0;
                if (discType === 'percent') {
                    rowDiscount = gross * (discVal / 100);
                } else {
                    rowDiscount = (discVal / originalQty) * pieces;
                }

                const netBeforeTax = gross - rowDiscount;
                const tax = netBeforeTax * (gstPercent / 100);
                
                $row.find('.row-disc').val(rowDiscount.toFixed(2));
                $row.find('.row-gst-amount').text(tax.toFixed(2));

                const rowNetTotal = netBeforeTax + tax;
                $row.find('.row-total').val(rowNetTotal.toFixed(2));

                $row.find('.row-calc-hint').text(`${pieces} Pcs × ${price}`);
                
                $row.data('row-gross', gross);
                $row.data('row-discount', rowDiscount);
                $row.data('row-tax', tax);
                $row.data('pieces-count', pieces);
            }

             function recalcSummary() {
                let totalGross = 0;
                let totalRowDiscount = 0;
                let totalGst = 0;
                let totalQtyPieces = 0;

                $('#returnItems tr').each(function() {
                    recalcRow($(this));
                    totalGross += num($(this).data('row-gross'));
                    totalRowDiscount += num($(this).data('row-discount'));
                    totalGst += num($(this).data('row-tax'));
                    totalQtyPieces += num($(this).data('pieces-count'));
                });

                const extraDiscountVal = num($('#extraDiscount').val());
                const extraDiscountType = $('#extraDiscountType').val() || 'amount';
                
                let extraDiscountAmount = 0;
                if (extraDiscountType === 'percent') {
                    extraDiscountAmount = (totalGross - totalRowDiscount) * (extraDiscountVal / 100);
                } else {
                    extraDiscountAmount = extraDiscountVal;
                }

                const combinedDiscount = totalRowDiscount + extraDiscountAmount;
                
                // Final Net = Gross + GST - Discount
                const netRefund = totalGross + totalGst - combinedDiscount;

                $('#billAmount').val(totalGross.toFixed(2));
                $('#totalGst').val(totalGst.toFixed(2));
                $('#itemDiscount').val(combinedDiscount.toFixed(2));
                $('#netAmount').val(netRefund.toFixed(2));

                if (netRefund > 0) {
                    $('#amountInWords').val(numberToWords(Math.round(netRefund)));
                } else {
                    $('#amountInWords').val('Zero Rupees');
                }

                $('#totalReturnQty').text(totalQtyPieces + ' Pcs');
                updatePartialReturnIndicator();
            }



            // SIN Import Modal Logic
            $('#btnImportSIN').click(function() {
                $('#sinModal').modal('show');
                loadSINs();
            });

            // Automatically open SIN modal when Customer is selected
            $('#customer_id_select').on('change', function() {
                const CustomerId = $(this).val();
                if (CustomerId && !$('input[name="sale_id"]').val()) {
                    $('#sinModal').modal('show');
                    loadSINs();
                }
            });

            function loadSINs() {
                $('#sinList').html(`
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <div class="spinner-border text-info spinner-border-sm me-2"></div> Loading Sales...
                        </td>
                    </tr>
                `);

                $.get("{{ route('sale.return.api_srns') }}", { customer_id: $('#customer_id_select').val() }, function(data) {
                    let html = '';
                    if (data.length === 0) {
                        html =
                            '<tr><td colspan="5" class="text-center py-4 text-muted">No approved SINs found.</td></tr>';
                    } else {
                        data.forEach(p => {
                            let statusBadge = p.is_fully_returned ?
                                '<span class="badge bg-danger ms-1" style="font-size:10px">Fully Returned</span>' :
                                '';
                            let btnClass = p.is_fully_returned ? 'btn-outline-secondary disabled' :
                                'btn-primary';
                            let icon = p.is_fully_returned ? 'fa-ban' : 'fa-undo';
                            let actionText = p.is_fully_returned ? 'Returned' : 'Select';

                            html += `
                                <tr>
                                    <td class="ps-3"><strong class="text-primary">${p.invoice_no}</strong>${statusBadge}</td>
                                    <td>${p.customer_name}</td>
                                    <td>${p.sale_date}</td>
                                    <td class="text-end fw-bold">${p.net_amount}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm ${btnClass} shadow-sm px-3 select-sin" data-id="${p.id}">
                                            <i class="fas ${icon} me-1 small"></i> ${actionText}
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    $('#sinList').html(html);
                });
            }

            // Select SIN from Modal (AJAX)
            $(document).on('click', '.select-sin', function() {
                const id = $(this).data('id');
                const btn = $(this);

                if (btn.hasClass('disabled')) return;

                btn.html('<span class="spinner-border spinner-border-sm"></span>').prop('disabled', true);
                
                $('#sinModal').modal('hide');
                if (window.ERPImportLoader) window.ERPImportLoader.start();

                $.get(`/sale/details/${id}`, function(res) {
                    if (res.success) {
                        // 1. Update Header Info
                        $('input[name="sale_id"]').val(res.sale.id);
                        $('#customer_id_select').val(res.sale.customer_id).trigger('change').prop('disabled', true);
                        
                        // If we disabled it, we need a hidden field to ensure value is sent
                        if ($('input[name="customer_id"]').length === 0) {
                            $('<input type="hidden" name="customer_id" value="' + res.sale.customer_id + '">').insertAfter('#customer_id_select');
                        } else {
                            $('input[name="customer_id"]').val(res.sale.customer_id);
                        }

                        $('input[name="reference"]').val(res.sale.invoice_no);
                        $('#originalDateDisplay').text(res.sale.sale_date);

                        // 2. Clear & Render Items
                        $('#returnItems').empty();
                        res.items.forEach(item => {
                            $('#returnItems').append(createReturnRow(item));
                        });
                        // Initialize Select2 for new rows
                        if ($.fn.select2) {
                            $('.select2-in-table').select2({
                                width: '100%'
                            });
                        }

                        // 3. Update UI
                        recalcSummary();

                        // Add original invoice badge if missing
                        if ($('.erp-header .badge').length === 0) {
                            $('<span class="badge bg-light text-dark border"><i class="fas fa-file-invoice me-1"></i> Original Invoice # ' +
                                res.sale.invoice_no + '</span>').insertBefore(
                                '.erp-header .btn-outline-secondary');
                        } else {
                            $('.erp-header .badge').html(
                                '<i class="fas fa-file-invoice me-1"></i> Original Invoice # ' +
                                res.sale.invoice_no);
                        }

                        // Automatically "Return All" to bring all information
                        setTimeout(function() {
                            $('#btnReturnAll').click();
                        }, 100);

                        if (window.ERPImportLoader) window.ERPImportLoader.success();
                    } else {
                        if (window.ERPImportLoader) {
                            window.ERPImportLoader.error('Failed to load Sale details.');
                        } else {
                            Swal.fire('Error', 'Failed to load Sale details.', 'error');
                        }
                    }
                }).fail(function(xhr) {
                    if (window.ERPImportLoader) {
                        window.ERPImportLoader.error(xhr.responseJSON?.message || 'Failed to load Sale details.');
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to load Sale details.', 'error');
                    }
                }).always(function() {
                    btn.html('<i class="fas fa-undo me-1 small"></i> Select').prop('disabled', false);
                });
            });

            function createReturnRow(item) {
                const remaining = item.max_returnable ?? item.qty;
                const sizeMode = item.size_mode || 'by_pieces';
                const ppb = item.pieces_per_box || 1;
                const displayRemaining = remaining;

                return `
                    <tr>
                        <input type="hidden" name="product_id[]" value="${item.product_id}">
                        <input type="hidden" name="original_qty[]" class="original-qty" value="${item.original_qty || 1}">
                        <input type="hidden" name="gst_percent[]" class="gst-percent" value="${item.gst_percent || 0}">
                        <input type="hidden" name="unit[]" value="${item.unit || 'pc'}">
                        <input type="hidden" name="size_mode[]" class="size-mode" value="${sizeMode}">
                        <input type="hidden" name="pieces_per_m2[]" class="pieces-per-m2" value="${item.pieces_per_m2 || 0}">

                        <td>
                            <input type="text" class="form-control fw-bold border-0 bg-transparent p-0" value="${item.item_name}" readonly>
                            <small class="text-muted d-block" style="font-size: 0.7rem;">${item.item_code}</small>
                        </td>

                        <td>
                            <select class="form-select form-select-sm select2-in-table" disabled>
                                ${allWarehouses.map(wh => `
                                    <option value="${wh.id}" ${item.warehouse_id == wh.id ? 'selected' : ''}>
                                        ${wh.warehouse_name}
                                    </option>
                                `).join('')}
                            </select>
                            <input type="hidden" name="warehouse_id[]" value="${item.warehouse_id}">
                        </td>

                        <td><input type="text" name="batch_no[]" class="form-control text-center form-control-sm" value="${item.batch_no || ''}"></td>
                        
                        <td class="text-center">
                            <input type="date" name="mfg_date[]" class="form-control form-control-sm" value="${item.mfg_date || ''}">
                            <input type="date" name="exp_date[]" class="form-control form-control-sm mt-1" value="${item.exp_date || ''}">
                        </td>

                        <td>
                            <input type="number" class="form-control text-center pieces-per-box form-control-sm" value="${ppb}" readonly>
                        </td>

                        <td>
                            <input type="number" name="price[]" step="any" class="form-control text-end price form-control-sm fw-bold" value="${item.price}" readonly>
                                <small class="text-muted d-block" style="font-size: 0.6rem;">Price / PC</small>
                        </td>

                        <td class="text-center">
                            <span class="badge bg-light text-dark border">${displayRemaining}</span>
                        </td>

                        <td>
                            <input type="number" name="qty[]" step="any"
                                class="form-control text-center fw-bold text-primary quantity-pieces form-control-sm"
                                value="0" min="0" 
                                data-max="${remaining}"
                                data-ppb="${ppb}"
                                data-sizemode="${sizeMode}">
                        </td>

                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" step="any" name="item_disc_val[]" class="form-control item-disc-val" value="${item.discount || 0}">
                                <select name="item_disc_type[]" class="form-select item-disc-type" style="max-width: 60px;">
                                    <option value="percent" ${item.discount_type === 'percent' ? 'selected' : ''}>%</option>
                                    <option value="amount" ${item.discount_type === 'amount' ? 'selected' : ''}>Amt</option>
                                </select>
                            </div>
                            <input type="hidden" class="row-disc" value="0.00">
                        </td>
                        <td>
                            <div class="row-gst-amount fw-500">0.00</div>
                            <small class="text-muted" style="font-size: 0.65rem;">${item.gst_percent || 0}%</small>
                        </td>
                        <td>
                            <input type="text" name="row_total[]" class="form-control text-end row-total form-control-sm fw-bold border-primary bg-light" value="0.00" readonly>
                            <small class="text-muted d-block text-end row-calc-hint" style="font-size: 0.6rem;"></small>
                        </td>

                        <td class="text-center">
                            <button type="button" class="btn btn-link text-danger p-0 ms-2 remove-row" title="Exclude from Return">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            // Client-side search for modal
            $('#sinSearch').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $("#sinList tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Return All — fill each row to its maximum returnable qty
            $('#btnReturnAll').click(function() {
                $('#returnItems tr').each(function() {
                    const $row = $(this);
                    const $qty = $row.find('.quantity-pieces');
                    const maxPieces = num($qty.data('max'));
                    $qty.val(maxPieces);

                    recalcRow($row);
                });
                recalcSummary();
            });


            // Events
            $(document).on('input change', '.quantity-pieces, .price, #extraDiscount, #extraDiscountType, .item-disc-val, .item-disc-type', function() {
                const $row = $(this).closest('tr');
                const $qtyInput = $row.find('.quantity-pieces');

                if ($qtyInput.length) {
                    const pieces = num($qtyInput.val());
                    const maxReturnablePieces = num($qtyInput.data('max'));

                    if (pieces > maxReturnablePieces) {
                        $qtyInput.val(maxReturnablePieces);
                        $(this).addClass('border-danger');

                        // Show warning
                        if (!$(this).next('.text-danger').length) {
                            $(this).after('<small class="text-danger d-block">Max allowed</small>');
                        }

                        setTimeout(() => {
                            $(this).removeClass('border-danger');
                            $(this).next('.text-danger').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 2000);
                    }
                }

                if ($row.length) {
                    recalcRow($row);
                }
                recalcSummary();
            });

            // Initialize
            $('#returnItems tr').each(function() {
                recalcRow($(this));
            });
            recalcSummary();

            // Remove row
            $(document).on('click', '.remove-row', function() {
                if (confirm('Are you sure you want to remove this item from return?')) {
                    $(this).closest('tr').remove();
                    recalcSummary();
                }
            });

            // Update Partial Return Visual Indicator
            function updatePartialReturnIndicator() {
                let totalOriginalPieces = 0;
                let totalReturningPieces = 0;

                $('#returnItems tr').each(function() {
                    const $qtyInput = $(this).find('.quantity-pieces');
                    const originalPieces = num($qtyInput.data('max')); // Pieces
                    const returningPieces = num($qtyInput.val());

                    totalOriginalPieces += originalPieces;
                    totalReturningPieces += returningPieces;
                });

                const returnPercentage = totalOriginalPieces > 0 ? (totalReturningPieces / totalOriginalPieces *
                    100) : 0;

                // Update progress bar
                $('#returnProgressBar').css('width', returnPercentage + '%');
                $('#returnProgressBar').attr('aria-valuenow', returnPercentage);
                $('#returnPercentage').text(returnPercentage.toFixed(1) + '%');

                // Update badge and status text - labels use Qty instead of Pieces
                if (totalReturningPieces === 0) {
                    $('#returnTypeBadge').html('<i class="fas fa-info-circle me-1"></i>No Items Selected');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-secondary');
                    $('#returnStatusText').html('<i class="fas fa-info-circle me-1"></i>Select items to return');
                    $('#returnProgressBar').css('background', '#6c757d');
                } else if (returnPercentage >= 100) {
                    $('#returnTypeBadge').html('<i class="fas fa-check-circle me-1"></i>Full Return');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-success');
                    $('#returnStatusText').html(
                        '<i class="fas fa-check-circle me-1"></i>Returning all items (100% of Sale)');
                    $('#returnProgressBar').css('background', '#10ac84');
                } else {
                    $('#returnTypeBadge').html('<i class="fas fa-chart-pie me-1"></i>Partial Return');
                    $('#returnTypeBadge').removeClass().addClass('badge bg-warning text-dark');
                    $('#returnStatusText').html('<i class="fas fa-chart-pie me-1"></i>Partial Return: ' +
                        returnPercentage.toFixed(1) + '% of Sale');
                    $('#returnProgressBar').css('background', '#f79f1f');
                }
            }


        });

        // Prevent Duplicate Form Submission
        $('#SaleReturnForm').on('submit', function(e) {
            const btn = $('#btnSubmitReturn');
            
            // Validate at least one item is being returned
            let hasItems = false;
            $('.quantity-pieces').each(function() {
                if (num($(this).val()) > 0) {
                    hasItems = true;
                }
            });

            if (!hasItems) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'No Items Selected',
                    text: 'Please enter a return quantity for at least one item.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            if (btn.hasClass('disabled')) {
                e.preventDefault();
                return false;
            }
            
            btn.addClass('disabled').html('<i class="fas fa-spinner fa-spin me-2"></i> Processing...');
        });
    </script>
@endsection
