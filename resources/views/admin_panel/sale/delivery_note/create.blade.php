@extends('admin_panel.layout.app')
@section('title', 'Create Delivery Note')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .main-content {
            padding: 1.5rem;
        }

        .page-glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .15);
            position: relative;
            overflow: hidden;
        }

        .page-glass-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 280px;
            height: 280px;
            background: rgba(79, 70, 229, .12);
            border-radius: 50%;
            filter: blur(50px);
            pointer-events: none;
        }

        .header-title h4 {
            color: #fff;
            font-weight: 800;
            font-size: 1.5rem;
            margin: 0;
        }

        .header-title p {
            color: #94a3b8;
            font-size: .875rem;
            margin: 0;
        }

        .card-section {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            padding-bottom: .75rem;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 1.25rem;
        }

        .form-label {
            font-size: .8rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: .35rem;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: .55rem .9rem;
            font-size: .875rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, .1);
            outline: none;
        }

        /* Floating Action Bar */
        .floating-action-bar {
            position: fixed;
            bottom: 25px;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            padding: 8px 12px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            z-index: 1000;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        @keyframes slideUp {
            from { transform: translate(-50%, 100px); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        .btn-floating-save {
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 10px 24px;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-floating-save:hover { background: #4338ca; transform: scale(1.02); }
        .btn-floating-save:disabled { opacity: 0.6; cursor: not-allowed; filter: grayscale(0.5); }
        .btn-floating-cancel {
            background: rgba(255,255,255,0.1);
            color: #cbd5e1;
            border: none;
            border-radius: 40px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }
        .btn-floating-cancel:hover { background: rgba(255,255,255,0.2); color: white; }
        
        .floating-divider {
            width: 1px;
            height: 24px;
            background: rgba(255,255,255,0.1);
            margin: 0 5px;
        }
        
        .form-control[readonly] {
            background: #f8fafc;
        }

        /* Import SO button */
        .btn-import-so {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .6rem 1.4rem;
            font-weight: 700;
            font-size: .875rem;
            cursor: pointer;
            transition: all .25s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, .3);
        }

        .btn-import-so:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, .4);
            color: #fff;
        }

        /* Product table */
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .items-table thead th {
            background: #f8fafc;
            padding: .85rem .75rem;
            font-size: .7rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 2px solid #f1f5f9;
        }

        .items-table tbody tr {
            transition: background .15s;
        }

        .items-table tbody tr:hover {
            background: #fafafa;
        }

        .items-table td {
            padding: .65rem .75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: .875rem;
        }

         /* Disabled row: only lock the data cells, NOT the checkbox column */
        .items-table .item-row.disabled-row td:not(:first-child) {
            opacity: .45;
            pointer-events: none;
        }

        .items-table .item-row.disabled-row td:not(:first-child) input,
        .items-table .item-row.disabled-row td:not(:first-child) select {
            background: #f1f5f9 !important;
        }

        .stock-badge {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: .2rem .55rem;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .stock-badge.empty { background:#fef2f2; color:#dc2626; border-color:#fecaca; }

        .row-check {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        input[readonly].price-cell {
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            cursor: not-allowed;
        }

        .product-tag {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: .25rem .65rem;
            font-size: .75rem;
            font-weight: 600;
        }

        .empty-row td {
            text-align: center;
            padding: 2.5rem;
            color: #94a3b8;
            font-size: .875rem;
        }

        /* Voucher section */
        .voucher-card {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
            border: 1px solid #d1fae5;
            border-radius: 14px;
            padding: 1.25rem;
            margin-bottom: .75rem;
        }

        .voucher-card .form-label {
            color: #065f46;
        }

        /* Summary */
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .35rem 0;
            font-size: .875rem;
        }

        .summary-row.total {
            font-weight: 800;
            font-size: 1rem;
            border-top: 2px solid #e2e8f0;
            padding-top: .75rem;
            margin-top: .5rem;
        }

        /* Modal */
        .modal-content {
            border-radius: 18px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-radius: 18px 18px 0 0;
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            color: #fff;
            font-weight: 700;
        }

        .so-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all .2s;
            margin-bottom: .75rem;
        }

        .so-card:hover {
            border-color: var(--primary);
            background: #eff6ff;
            transform: translateX(3px);
        }

        .so-card .so-no {
            font-weight: 800;
            color: #1e293b;
            font-size: .95rem;
        }

        .so-card .so-meta {
            font-size: .78rem;
            color: #64748b;
        }

        /* Product Select Button Style */
        .product-select-btn {
            background: #fff !important;
            color: #333 !important;
            border: 1px solid #000 !important;
            padding: 4px 10px !important;
            border-radius: 1px !important;
            font-weight: 500 !important;
            width: 100% !important;
            text-align: left !important;
            position: relative !important;
            cursor: pointer !important;
            font-size: 13px !important;
        }

        .product-select-btn:hover {
            background: #f8f9fa !important;
        }

        .product-select-btn.has-value {
            background: #fff !important;
            color: #000 !important;
            border-color: #000 !important;
            font-weight: 700 !important;
        }

        .psm-btn-arrow {
            float: right;
            font-size: 0.8em;
            margin-top: 3px;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="page-glass-header">
                <div class="header-title">
                    <h4><i class="fas fa-truck me-3"></i>Create Delivery Note</h4>
                    <p>Select a Sale Order, tick products to deliver, and confirm dispatch.</p>
                </div>
                <a href="{{ route('delivery.note.index') }}" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>

            <form action="{{ route('delivery.note.store') }}" method="POST" id="dcForm">
                @csrf\
                <input type="hidden" name="sale_id" id="saleId">
                <input type="hidden" name="branch_id" value="{{ $branchId }}">

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius:15px;">
                        <ul class="mb-0 pt-1 pb-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-3">
                    {{-- 2nd Section: DC Info (Left) & Summary (Right) --}}
                    <div class="col-lg-7">
                        <div class="card-section h-100">
                            <p class="section-title">Delivery Note Info</p>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">DC No</label>
                                    <input type="text" name="dc_no_display" id="dcNoDisplay" class="form-control form-control-sm" value="{{ $nextDcNo }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="delivery_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Customer</label>
                                    <div id="customerSoWrapper">
                                        <input type="text" id="customerName" class="form-control form-control-sm" readonly placeholder="Auto-filled">
                                        <input type="hidden" name="customer_id" id="customerId">
                                    </div>
                                    <div id="customerManualWrapper" class="d-none">
                                        <select name="customer_id_manual" id="customerSelect" class="form-select form-select-sm select2">
                                            <option value="">Select Customer</option>
                                            @foreach($customers as $c)
                                                <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Branch</label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $branchName }}" readonly>
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="note" class="form-control form-control-sm" placeholder="Delivery notes...">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" id="enable_hs_code" name="enable_hs_code" value="1" checked>
                                        <label class="form-check-label fw-bold small" for="enable_hs_code">HS CODE</label>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="is_sample" id="isSample" value="1">
                                        <label class="form-check-label fw-bold text-primary small" for="isSample">SAMPLE</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card-section h-100">
                            <p class="section-title">Summary & Actions</p>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="summary-box p-2">
                                        <div class="summary-row small"><span>Total Qty</span><span id="summaryQty" class="fw-bold text-primary">—</span></div>
                                        <div class="summary-row small"><span>Free Pcs</span><span id="summaryFreeStock" class="fw-bold text-success">—</span></div>
                                        <div class="summary-row d-none"><span>Subtotal</span><span id="summarySubtotal">0.00</span></div>
                                        <div class="summary-row total text-primary d-none"><span>Net Amount</span><span id="summaryNet">0.00</span></div>
                                    </div>
                                </div>
                                <div class="col-md-12 d-flex flex-column gap-2 justify-content-center">
                                    <div class="alert alert-info py-2 px-3 small border-0 shadow-sm" style="background: #eff6ff; color: #1e40af; border-radius: 12px;">
                                        <i class="fas fa-info-circle me-1"></i> Review the totals and use the floating bar at the bottom to save.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Block 3: Products full width --}}
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="section-title mb-0">Products to Deliver</p>
                                <div id="soActionWrapper">
                                    <button type="button" class="btn-import-so py-1 px-3" id="openSoModal">
                                        <i class="fas fa-file-import me-2"></i>Import Sale Order
                                    </button>
                                </div>
                                <div id="manualActionWrapper" class="d-none">
                                    <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm btn-sm" id="addManualRow">
                                        <i class="fas fa-plus me-1"></i>Add Product Row
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="items-table" id="dcItemsTable">
                                    <thead>
                                        <tr>
                                            <th class="so-col" style="width:40px;"><i class="fas fa-check-square"></i></th>
                                            <th>Product</th>
                                            <th class="hs-code-col" style="width:90px;">HS Code</th>
                                            <th style="width:100px;">UOM</th>
                                            <th>Warehouse</th>
                                            <th style="width:150px;">Lot / Batch</th>
                                            <th class="so-col" style="width:100px;">Free Pcs</th>
                                            <th class="so-col" style="width:90px;">Order</th>
                                            <th style="width:140px;" id="qty-header-label">Deliver Qty</th>
                                            <th class="so-col d-none" style="width:100px;">Price/Pc</th>
                                            <th class="so-col d-none text-end" style="width:110px;">Amount</th>
                                            <th class="manual-col d-none" style="width:40px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="dcItemsBody">
                                        <tr class="empty-row" id="emptyRow">
                                            <td colspan="10">
                                                <i class="fas fa-file-import mb-2" style="font-size:1.5rem; color:#cbd5e1;"></i><br>
                                                Click <strong>"Import Sale Order"</strong> to load products
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Floating Action Bar --}}
                <div class="floating-action-bar">
                    <a href="{{ route('delivery.note.index') }}" class="btn-floating-cancel">
                        Discard Changes
                    </a>
                    <div class="floating-divider"></div>
                    <button type="submit" class="btn-floating-save" id="submitBtn" disabled>
                        <i class="fas fa-check-circle"></i> Save Delivery Note
                    </button>
                </div>
            </form>

        </div>
    </div>

    {{-- ══════════════ IMPORT SALE ORDER MODAL ══════════════ --}}
    <div class="modal fade" id="soModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Import Sale Order</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-3">
                        <input type="text" id="soSearch" class="form-control"
                            placeholder="Search by SO number or customer...">
                    </div>
                    <div id="soList">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted">Loading sale orders...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('admin_panel.components.product_select_modal')
@endsection

@section('js')
    <script>
        // HS Code Toggle Logic
        $('#enable_hs_code').on('change', function() {
            if($(this).is(':checked')) {
                $('.hs-code-col').show();
            } else {
                $('.hs-code-col').hide();
            }
        });
        $('#enable_hs_code').trigger('change');

        $(document).ready(function() {
            // Initialize Select2 for manual customer selection
            $('#customerSelect').select2({
                placeholder: 'Search and select customer...',
                width: '100%',
                dropdownParent: $('#customerManualWrapper')
            });
        });

        let saleId      = null;
        let warehouses = {}; // product_id → warehouse_id (from SO)
        let allSoItems = [];
        
        // Pass PHP data to JS safely!
        const allWarehouses = @json($warehouses);
        const allProducts = @json($products);

        function updateDcNo(saleId = null) {
            fetch(`{{ route('delivery.note.next-no') }}?sale_id=${saleId || ''}`)
                .then(r => r.json())
                .then(data => {
                    if (data.dc_no) {
                        document.getElementById('dcNoDisplay').value = data.dc_no;
                    }
                });
        }

        // ── Open modal & load SOs ──
        document.getElementById('openSoModal').addEventListener('click', function() {
            $('#soModal').modal('show');
            fetch("{{ route('delivery.note.sale-orders') }}", { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => renderSoList(data));

            document.getElementById('soSearch').addEventListener('input', function() {
                const q = this.value.toLowerCase();
                document.querySelectorAll('.so-card').forEach(card => {
                    card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        });

        // Standardized Product Row Population
        function populateProductRow($row, p) {
            const $btn = $row.find('.product-select-btn');
            var btnText = (p.item_name || 'Select Product') + (p.item_code ? ' (' + p.item_code + ')' : '');
            $btn.html(btnText + ' <span class="psm-btn-arrow">&#9660;</span>');
            $btn.addClass('has-value');

            $row.find('.item-id').val(p.id);
            $row.find('.hs-code').val(p.hs_code || '');
            $row.find('.ppb-input').val(p.pieces_per_box || 1);
            
            // HS Code visibility sync
            if ($('#enable_hs_code').is(':checked')) {
                $row.find('.hs-code-col').show();
            } else {
                $row.find('.hs-code-col').hide();
            }
            
            // Standardize UOM/Packing dropdown
            const $uomSel = $row.find('.uom-select');
            if ($uomSel.length) {
                $uomSel.empty();
                let seenFactors = {};

                // 1. Packings from database (ProductUom table) - Prioritize these
                if (p.packings && p.packings.length > 0) {
                    p.packings.forEach(pkg => {
                        let f = parseInt(pkg.pieces_per_box) || 1;
                        if (f === 1) f = parseInt(p.uom || p.pieces_per_box) || 1;
                        if (seenFactors[f]) return;
                        seenFactors[f] = true;
                        $uomSel.append(`<option value="${pkg.id}" data-ppb="${f}">${pkg.name}</option>`);
                    });
                } else {
                    // 2. Base Unit (Add only if NO packings exist)
                    let bPpb = parseInt(p.uom || p.pieces_per_box) || 1;
                    let bName = p.uom_name || (p.unit ? p.unit.name : 'Pcs');
                    if (!bName || bName.toLowerCase() === 'piece' || bName.toLowerCase() === 'pcs') {
                        bName = '1x' + bPpb;
                    }
                    $uomSel.append(`<option value="" data-ppb="${bPpb}">${bName} (Base)</option>`);
                }
                $uomSel.trigger('change');
            }

            // Load warehouses and then batches
            updateWarehousesForProduct($row.attr('id').replace('item-row-', ''), p.id);
            recalcRow($row.attr('id').replace('item-row-', ''));
        }

        // ── Sample Mode Toggle ──
        document.getElementById('isSample').addEventListener('change', function() {
            const isSample = this.checked;
            updateDcNo(isSample ? null : document.getElementById('saleId').value);
            
            document.getElementById('customerSoWrapper').classList.toggle('d-none', isSample);
            document.getElementById('customerManualWrapper').classList.toggle('d-none', !isSample);
            document.getElementById('soActionWrapper').classList.toggle('d-none', isSample);
            document.getElementById('manualActionWrapper').classList.toggle('d-none', !isSample);
            
            document.getElementById('dcItemsBody').innerHTML = `
                <tr class="empty-row" id="emptyRow">
                    <td colspan="10">
                        <i class="fas fa-file-import mb-2" style="font-size:2rem; color:#cbd5e1;"></i><br>
                        ${isSample ? 'Click <strong>"Add Product Row"</strong> to begin manual entry' : 'Click <strong>"Import Sale Order"</strong> to load products'}
                    </td>
                </tr>
            `;
            allSoItems = [];
            document.getElementById('saleId').value = "";
            document.getElementById('customerId').value = "";
            document.getElementById('submitBtn').disabled = !isSample;

            document.querySelectorAll('.so-col').forEach(el => el.classList.toggle('d-none', isSample));
            document.querySelectorAll('.manual-col').forEach(el => el.classList.toggle('d-none', !isSample));
            document.getElementById('qty-header-label').textContent = isSample ? 'Qty (Box / Loose)' : 'Deliver Qty (Pcs)';
            recalcAll();
        });

        function loadBatches(rowId, productId, warehouseId) {
            if (!productId || !warehouseId) return;
            const $sel = $(`#batch-${rowId}`);
            $sel.html('<option value="">FEFO (Auto)</option>');
            
            fetch(`{{ route('batches.for.product', '') }}/${productId}?warehouse_id=${warehouseId}`)
                .then(r => r.json())
                .then(batches => {
                    batches.forEach(b => {
                        $sel.append(`<option value="${b.id}">${b.batch_number} (Exp: ${b.exp_date}) - ${b.total_pieces} Pcs</option>`);
                    });
                });
        }

        function updateManualProductInfo(i, productId) {
            if (!productId) return;
            const $option = $(`#pid-${i} option:selected`);
            const ppb = parseInt($option.data('ppb')) || 1;
            document.getElementById(`ppb-${i}`).value = ppb;
            
            // Populate UOMs
            const $uomSel = $(`#uom-${i}`);
            $uomSel.empty().append(`<option value="">${$option.data('uom')}</option>`);
            
            const product = allProducts.find(p => p.id == productId);
            if (product && product.packings) {
                product.packings.forEach(pkg => {
                    $uomSel.append(`<option value="${pkg.id}" data-ppb="${pkg.pieces_per_box}">${pkg.name}</option>`);
                });
            }
            
            // Load batches for first warehouse
            loadBatches(i, productId, $(`#wh-${i}`).val());
            recalcAll();
        }

        let manualRowCount = 0;
        document.getElementById('addManualRow').addEventListener('click', function() {
            const tbody = document.getElementById('dcItemsBody');
            const emptyRow = document.getElementById('emptyRow');
            if (emptyRow) emptyRow.remove();

            const i = 'm' + manualRowCount++;
            const row = document.createElement('tr');
            row.className = 'item-row';
            row.id = `item-row-${i}`;
            row.innerHTML = `
                <td class="so-col d-none"></td>
                <td>
                    <input type="hidden" name="product_id[]" id="pid-${i}" class="item-id">
                    <button type="button" class="product-select-btn manual-product-select">Select Product <span class="psm-btn-arrow">&#9660;</span></button>
                </td>
                <td class="hs-code-col">
                    <input type="text" name="hs_code[]" class="form-control text-center hs-code" readonly tabindex="-1" style="background:#f8fafc; font-size: 0.8rem;">
                </td>
                <td>
                    <input type="hidden" id="ppb-${i}" class="ppb-input" value="1">
                    <select name="uom_id[]" id="uom-${i}" class="form-select form-select-sm uom-select" onchange="updateUomInfo('${i}', this.value)">
                        <option value="">—</option>
                    </select>
                </td>
                <td>
                    <select name="warehouse_id[]" id="wh-${i}" class="form-select form-select-sm warehouse-select" onchange="loadBatches('${i}', $('#pid-${i}').val(), this.value); recalcAll();">
                        ${allWarehouses.map(w => `<option value="${w.id}">${w.warehouse_name}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select name="batch_id[]" id="batch-${i}" class="form-select form-select-sm batch-select">
                        <option value="">FEFO (Auto)</option>
                    </select>
                </td>
                <td class="so-col d-none"></td>
                <td class="so-col d-none"></td>
                <td>
                    <div class="d-flex gap-1" style="min-width: 130px;">
                        <input type="number" id="qty-box-${i}" class="form-control form-control-sm text-center box-input" placeholder="Box" oninput="recalcRow('${i}')" style="flex: 1; padding: 4px 2px;">
                        <input type="number" id="qty-loose-${i}" class="form-control form-control-sm text-center loose-input" placeholder="Loose" oninput="recalcRow('${i}')" style="flex: 1; padding: 4px 2px;">
                        <input type="hidden" name="qty[]" id="qty-${i}" class="qty-input">
                    </div>
                </td>
                <td class="so-col d-none"></td>
                <td class="so-col d-none"></td>
                <td class="manual-col">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeManualRow('${i}')"><i class="fas fa-times"></i></button>
                </td>
            `;
            tbody.appendChild(row);
        });

        // ── Manual Product Select Click ──
        $(document).on('click', '.manual-product-select', function() {
            var $triggerBtn = $(this);
            var $triggerRow = $triggerBtn.closest('tr');
            
            // Collect currently selected IDs
            const selectedIds = [];
            $('.item-row').each(function() {
                const pid = $(this).find('.item-id').val();
                if (pid) selectedIds.push(parseInt(pid));
            });

            ERPProductModal.open({
                priceField: 'sale',
                selectedIds: selectedIds,
                onSelect: function(products) {
                    products.forEach(function(p, idx) {
                        var $row = (idx === 0) ? $triggerRow : null;
                        if (!$row) {
                            document.getElementById('addManualRow').click();
                            $row = $('.item-row').last();
                        }
                        populateProductRow($row, p);
                    });
                }
            });
        });

        function updateManualProductInfo(i, productId) {
            const product = allProducts.find(p => p.id == productId);
            const $uomSel = $(`#uom-${i}`);
            $uomSel.empty();

            if (product) {
                if (product.packings && product.packings.length > 0) {
                    product.packings.forEach(pkg => {
                        $uomSel.append(`<option value="${pkg.id}" data-ppb="${pkg.pieces_per_box}">${pkg.name}</option>`);
                    });
                } else {
                    $uomSel.append(`<option value="" data-ppb="${product.pieces_per_box}">${product.unit_name}</option>`);
                }
                // Trigger UOM info update for first packing
                updateUomInfo(i, $uomSel.val());
            }

            updateWarehousesForProduct(i, productId);
            recalcRow(i);
        }

        function updateUomInfo(i, uomId) {
            const $uomSel = document.getElementById(`uom-${i}`);
            const option = $uomSel.options[$uomSel.selectedIndex];
            const ppb = option ? (option.getAttribute('data-ppb') || 1) : 1;
            document.getElementById(`ppb-${i}`).value = ppb;
            recalcRow(i);
        }

        function removeManualRow(i) {
            document.getElementById(`item-row-${i}`).remove();
            if (document.querySelectorAll('.item-row').length === 0) {
                 document.getElementById('dcItemsBody').innerHTML = `
                    <tr class="empty-row" id="emptyRow">
                        <td colspan="10">Click <strong>"Add Product Row"</strong> to begin manual entry</td>
                    </tr>
                `;
            }
            recalcAll();
        }

        function renderSoList(sales) {
            const container = document.getElementById('soList');
            if (!sales.length) {
                container.innerHTML =
                    `<div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-3"></i><p>No draft Sale Orders available.</p></div>`;
                return;
            }
            container.innerHTML = sales.map(s => `
        <div class="so-card" onclick="importSO(${s.id})">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="so-no">${s.invoice_no}</div>
                    <div class="so-meta"><i class="fas fa-user me-1"></i>${s.customer_name} &nbsp;&bull;&nbsp; <i class="far fa-calendar me-1"></i>${s.sale_date}</div>
                </div>
                <div class="text-end">
                    <div class="fw-700 text-primary mb-1">PKR ${Number(s.total_net).toLocaleString()}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); importSO(${s.id})">
                        <i class="fas fa-arrow-right"></i> Select
                    </button>
                </div>
            </div>
        </div>
    `).join('');
        }

        function importSO(id) {
            $('#soModal').modal('hide');

            fetch(`{{ url('delivery-notes/so') }}/${id}/items`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('saleId').value = data.sale.id;
                    document.getElementById('customerName').value = data.sale.customer_name;
                    updateDcNo(data.sale.id);
                    renderItems(data.items);
                    document.getElementById('submitBtn').disabled = false;
                });
        }

        // ── Helpers ──────────────────────────────────────────────────────
        // Format total pieces as 'X Box Y Pc' using ppb
        function fmtBoxPc(totalPieces, ppb) {
            ppb = parseInt(ppb) || 1;
            totalPieces = Math.max(0, Math.round(totalPieces));
            if (ppb <= 1) return totalPieces + ' Pc';
            var boxes = Math.floor(totalPieces / ppb);
            var loose = totalPieces % ppb;
            if (boxes > 0 && loose > 0) return boxes + ' Box ' + loose + ' Pc';
            if (boxes > 0)              return boxes + ' Box';
            return loose + ' Pc';
        }

        // Convert piece-count → string
        function piecesToDotQty(totalPieces, ppb) {
            return String(Math.round(totalPieces));
        }

        // ── renderItems ───────────────────────────────────────────────────
        function renderItems(items) {
            allSoItems = items;
            const tbody = document.getElementById('dcItemsBody');
            if (!items.length) {
                tbody.innerHTML = `<tr class="empty-row"><td colspan="8">No items found in this Sale Order.</td></tr>`;
                return;
            }

            tbody.innerHTML = items.map((item, i) => {
                const ppb         = parseInt(item.ppb) || 1;
                // remaining is in pieces (from backend)
                const remPieces   = Math.max(0, item.remaining_pieces);
                const stockPieces = Math.max(0, item.warehouse_stock || 0);
                const soQtyPieces = item.so_total_pieces || 0;

                const defaultQty  = Math.max(0, remPieces);
                const initAmt     = (remPieces * item.price).toFixed(2);

            return `
        <tr class="item-row" id="item-row-${i}">
            <td class="so-col"><input type="checkbox" class="row-check" id="chk-${i}" onchange="toggleRow(${i})" checked></td>
            <td>
                <input type="hidden" name="product_id[]" value="${item.product_id}" id="pid-${i}">
                <input type="hidden" name="sale_item_id[]" value="${item.sale_item_id}" id="siid-${i}">
                <input type="hidden" id="ppb-${i}" value="${ppb}">
                <input type="hidden" id="stock-${i}" value="${stockPieces}">
                <div class="fw-600 text-dark">${item.product_name}</div>
                <small class="text-muted font-monospace">${item.product_code}</small>
            </td>
            <td class="hs-code-col">
                <input type="text" name="hs_code[]" class="form-control text-center hs-code" value="${item.hs_code || ''}" readonly tabindex="-1" style="background:#f8fafc; font-size: 0.8rem;">
            </td>
            <td>
                <span class="badge bg-light text-dark border">${item.uom || 'Piece'}</span>
            </td>
            <td>
                <select name="warehouse_id[]" id="wh-${i}" class="form-select form-select-sm warehouse-select" onchange="loadBatches(${i}, $('#pid-${i}').val(), this.value); recalcAll();">
                    ${allWarehouses.map(w => 
                        `<option value="${w.id}" ${w.id == item.warehouse_id ? 'selected' : ''}>${w.warehouse_name}</option>`
                    ).join('')}
                </select>
            </td>
            <td>
                <select name="batch_id[]" id="batch-${i}" class="form-select form-select-sm batch-select">
                    <option value="">FEFO (Auto)</option>
                </select>
            </td>
            <td class="so-col">
                <input type="number" name="free_qty[]" id="free-qty-${i}"
                       class="form-control text-end free-qty-cell"
                       value="0" placeholder="Free Pcs"
                       oninput="recalcAll()">
            </td>
            <td class="so-col">
                <div class="d-flex flex-column gap-1">
                    <span class="badge bg-primary-light text-primary border-primary-light" style="font-size: 0.65rem;">ORDERED: ${soQtyPieces}</span>
                    <span class="badge bg-warning-light text-warning border-warning-light" style="font-size: 0.65rem;">REMAINING: ${remPieces}</span>
                </div>
            </td>
            <td>
                <input type="hidden" id="max-qty-${i}" value="${remPieces}">
                <div class="d-flex gap-1" style="min-width: 130px;">
                    <input type="number" id="qty-box-${i}" class="form-control form-control-sm text-center box-input" 
                           value="${Math.floor(defaultQty / ppb)}" placeholder="Box" oninput="recalcRow(${i})" style="flex: 1; padding: 4px 2px;">
                    <input type="number" id="qty-loose-${i}" class="form-control form-control-sm text-center loose-input" 
                           value="${defaultQty % ppb}" placeholder="Loose" oninput="recalcRow(${i})" style="flex: 1; padding: 4px 2px;">
                    <input type="hidden" name="qty[]" id="qty-${i}" value="${defaultQty}" class="qty-input">
                </div>
            </td>
            <td class="so-col d-none">
                <input type="number" name="price[]" id="price-${i}"
                       class="form-control text-end price-cell"
                       value="${item.price}" readonly>
            </td>
            <td class="so-col d-none text-end fw-700 text-primary" id="amt-${i}">
                ${initAmt}
            </td>
            <td class="manual-col d-none"></td>
        </tr>`;
            }).join('');

            // Load warehouses and then batches for each generated row
            items.forEach((item, i) => {
                updateWarehousesForProduct(i, item.product_id);
            });

            $('#enable_hs_code').trigger('change');
            recalcAll();
        }

        function updateWarehousesForProduct(i, productId) {
            if (!productId) return;
            const $whSel = $(`#wh-${i}`);
            const currentWhId = $whSel.val();

            // Fetch batches to see which warehouses have stock
            $.get(`{{ url('batches/product') }}/${productId}`)
                .done(function(batches) {
                    // Extract unique warehouse IDs that have qty_remaining > 0
                    const validWhIds = [...new Set(batches.filter(b => b.qty_remaining > 0).map(b => b.warehouse_id))];
                    
                    $whSel.empty();
                    
                    if (validWhIds.length === 0) {
                        $whSel.append('<option value="">(No Stock)</option>');
                    } else {
                        allWarehouses.forEach(w => {
                            if (validWhIds.includes(w.id)) {
                                const selected = w.id == currentWhId ? 'selected' : '';
                                $whSel.append(`<option value="${w.id}" ${selected}>${w.warehouse_name}</option>`);
                            }
                        });
                    }

                    // If current warehouse is no longer valid, select the first valid one
                    const firstValidId = validWhIds[0];
                    if (!validWhIds.includes(parseInt(currentWhId)) && firstValidId) {
                        $whSel.val(firstValidId);
                    }
                    
                    // Now load batches for the selected warehouse
                    loadBatches(i, productId, $whSel.val());
                });
        }

        function loadBatches(rowIndex, productId, warehouseId) {
            const $batchSelect = $('#batch-' + rowIndex);
            if (!productId) return;

            $batchSelect.empty().append('<option value="">FEFO (Auto)</option>');
            
            if (!warehouseId) return;

            $.get(`{{ url('batches/product') }}/${productId}`, { warehouse_id: warehouseId })
                .done(function(batches) {
                    batches.forEach(function(batch) {
                        const style = batch.expiry_status === 'expired' ? 'color: red;'
                                    : batch.expiry_status === 'critical' ? 'color: orange;' : '';
                        $batchSelect.append(
                            `<option value="${batch.id}" style="${style}">${batch.label}</option>`
                        );
                    });
                });
        }

        function toggleRow(i) {
            const checked   = document.getElementById('chk-' + i).checked;
            const row       = document.getElementById('item-row-' + i);
            const qtyInput  = document.getElementById('qty-'  + i);
            const pidInput  = document.getElementById('pid-'  + i);
            const siidInput = document.getElementById('siid-' + i);
            const whInput   = document.getElementById('wh-'   + i);
            const batchSel  = document.getElementById('batch-'+ i);

            if (checked) {
                row.classList.remove('disabled-row');
                [qtyInput, pidInput, siidInput, whInput, batchSel].forEach(el => el && (el.disabled = false));
                recalcRow(i);
            } else {
                row.classList.add('disabled-row');
                [qtyInput, pidInput, siidInput, whInput, batchSel].forEach(el => el && (el.disabled = true));
                document.getElementById('amt-' + i).textContent = '—';
            }
            recalcAll();
        }

        function parseQtyToPieces(val, ppb) {
            return parseInt(val) || 0;
        }

        // ── recalcRow ─────────────────────────────────────────────────────
        function recalcRow(i) {
            const isSample = document.getElementById('isSample').checked;
            var ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
            var totalPieces = 0;

            // Box + Loose calculation
            var boxes = parseInt(document.getElementById('qty-box-' + i).value) || 0;
            var loose = parseInt(document.getElementById('qty-loose-' + i).value) || 0;
            totalPieces = (boxes * ppb) + loose;
            
            var qtyInput = document.getElementById('qty-' + i);
            if (qtyInput) qtyInput.value = totalPieces;

            if (!isSample) {
                // SO Mode: Limit check against remaining
                var maxEl = document.getElementById('max-qty-' + i);
                if (maxEl) {
                    var maxPieces = parseInt(maxEl.value) || 0;
                    if (totalPieces > maxPieces) {
                        totalPieces = maxPieces;
                        // Reset inputs to max allowed
                        document.getElementById('qty-box-' + i).value = Math.floor(maxPieces / ppb);
                        document.getElementById('qty-loose-' + i).value = maxPieces % ppb;
                        if (qtyInput) qtyInput.value = maxPieces;
                    }
                }
            }
            
            var price = 0;
            if (!isSample) {
                var priceEl = document.getElementById('price-' + i);
                price = priceEl ? parseFloat(priceEl.value) : 0;
            }
            
            var amt = totalPieces * price;
            var amtEl = document.getElementById('amt-' + i);
            if (amtEl) amtEl.textContent = amt.toFixed(2);
            recalcAll();
        }

        // ── recalcAll ─────────────────────────────────────────────────────
        function recalcAll() {
            let sub           = 0;
            let totalPieces   = 0;
            let totalFree     = 0;
            let totalStock    = 0;
            let hasPpb        = false;
            let lastPpb       = 1;
            const isSample = document.getElementById('isSample').checked;

            // Iterate through all rows (SO rows use indices 0,1,2... manual use m0, m1...)
            $('.item-row').each(function() {
                var idAttr = $(this).attr('id');
                var i = idAttr.replace('item-row-', '');
                
                const chk  = document.getElementById('chk-' + i);
                // If it's SO mode, check if row is ticked. If manual mode, all rows are active.
                if (isSample || (chk && chk.checked)) {
                    const ppb    = parseInt(document.getElementById('ppb-' + i)?.value) || 1;
                    const rawQty = document.getElementById('qty-'   + i)?.value || '0';
                    const pcs    = parseQtyToPieces(rawQty, ppb);
                    
                    let price = 0;
                    if (!isSample) {
                        const priceEl = document.getElementById('price-' + i);
                        price = priceEl ? parseFloat(priceEl.value) : 0;
                    }
                    
                    const free   = parseInt(document.getElementById('free-qty-' + i)?.value || '0');
                    const stock  = parseInt(document.getElementById('stock-' + i)?.value) || 0;

                    sub         += pcs * price;
                    totalPieces += pcs;
                    totalFree   += free;
                    totalStock  += stock;
                }
            });

            // Format qty & stock labels
            document.getElementById('summaryQty').textContent       = totalPieces;
            document.getElementById('summaryFreeStock').textContent  = totalFree;
            document.getElementById('summarySubtotal').textContent   = sub.toFixed(2);
            document.getElementById('summaryNet').textContent        = sub.toFixed(2);
        }

        function updatePaymentSummary() {
            let total = 0;
            document.querySelectorAll('.payment-input').forEach(inp => total += parseFloat(inp.value) || 0);
            const el = document.getElementById('totalPayment');
            if (el) el.textContent = 'PKR ' + total.toFixed(2);
        }

        // ── Form validation before submit ──
        document.getElementById('dcForm').addEventListener('submit', function(e) {
            const isSample = document.getElementById('isSample').checked;
            const saleId = document.getElementById('saleId').value;
            console.log('Submitting DC:', { isSample, saleId });
            
            if (!isSample && !saleId) {
                e.preventDefault();
                alert('Please import a Sale Order first.');
                return;
            }

            if (isSample) {
                const customerId = document.getElementById('customerSelect').value;
                if (!customerId) {
                    e.preventDefault();
                    alert('Please select a customer.');
                    return;
                }
                
                const itemRows = document.querySelectorAll('.item-row');
                if (!itemRows.length) {
                    e.preventDefault();
                    alert('Please add at least one product.');
                    return;
                }
            } else {
                // SO Mode: ensure at least one item is checked
                const checked = document.querySelectorAll('.row-check:checked');
                if (!checked.length) {
                    e.preventDefault();
                    alert('Please select at least one product from the Sale Order to deliver.');
                    return;
                }
            }
        });

        // ── Auto Load if passed via query param ──
        @if (request('sale_id'))
            importSO({{ request('sale_id') }});
        @endif
    </script>
@endsection
