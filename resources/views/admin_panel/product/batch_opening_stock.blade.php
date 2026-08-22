@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
        }

        .page-container {
            max-width: 1240px;
            margin: 0 auto;
            padding: 24px 20px 60px;
        }

        .section-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 16px -4px rgba(15, 23, 42, .05), 0 1px 3px rgba(0,0,0,.03);
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header-pro {
            padding: 16px 24px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-body-pro {
            padding: 24px;
        }

        .form-label-pro {
            font-size: 0.73rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 6px;
            letter-spacing: .04em;
        }

        .form-control-pro {
            width: 100%;
            padding: 9px 13px;
            font-size: .92rem;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #fff;
            color: #1e293b;
            transition: all .15s ease-in-out;
        }

        .form-control-pro:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .12);
        }

        .form-control-pro.is-invalid {
            border-color: #ef4444 !important;
            background-color: #fffaf0;
        }

        .batch-row {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 18px;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,.02);
            transition: border-color .2s, box-shadow .2s;
        }

        .batch-row:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 16px rgba(0,0,0,.04);
        }

        .batch-row.row-invalid {
            border-color: #fca5a5 !important;
            background: #fffafa;
        }

        .btn-row-action {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #fff;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all .15s;
        }

        .btn-remove-row {
            color: #ef4444;
            background: #fef2f2;
            border-color: #fecaca;
        }
        .btn-remove-row:hover {
            background: #ef4444;
            color: #fff;
        }

        .btn-duplicate-row {
            color: #4f46e5;
            background: #eef2ff;
            border-color: #c7d2fe;
        }
        .btn-duplicate-row:hover {
            background: #4f46e5;
            color: #fff;
        }

        .badge-exp {
            font-size: .75rem;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: .02em;
        }

        .btn-add-row {
            background: #f8fafc;
            color: #4f46e5;
            border: 2px dashed #c7d2fe;
            border-radius: 12px;
            padding: 14px 20px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            width: 100%;
            transition: all .2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-add-row:hover {
            background: #eef2ff;
            border-color: #818cf8;
            color: #3730a3;
        }

        .summary-bar {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #fff;
            border-radius: 14px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-stat-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #94a3b8;
            font-weight: 600;
        }

        .summary-stat-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .tag-toggle-check {
            cursor: pointer;
            user-select: none;
            padding: 2px 8px;
            border-radius: 6px;
            transition: background-color .15s;
        }
        .tag-toggle-check:hover {
            background: #f1f5f9;
        }

        .readonly-tag-field {
            background-color: #f8fafc !important;
            color: #64748b !important;
            cursor: not-allowed;
        }

        /* Select2 custom adjustments */
        .select2-container--default .select2-selection--single {
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            height: 42px !important;
            background-color: #fff !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: .92rem !important;
            padding-left: 13px !important;
            padding-right: 25px !important;
            line-height: 40px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            right: 10px !important;
        }
        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, .12) !important;
            outline: none !important;
        }
        .select2-dropdown {
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
            z-index: 9999;
        }
        .select2-results__option {
            padding: 9px 14px !important;
            font-size: .92rem !important;
        }
        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
        }
    </style>

    <div class="page-container">
        <!-- Page Header -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url()->previous() }}" class="btn btn-white border shadow-sm rounded-circle p-0"
                    style="width:40px;height:40px;display:grid;place-items:center;">
                    <i class="las la-arrow-left fs-5"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-0">Consolidated Opening Stock Entry</h4>
                    <small class="text-muted">Directly register opening stock with or without Batch numbers and Expiry dates</small>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="bulkSetNoBatch()">
                    <i class="las la-boxes me-1"></i> Set All: No Batch
                </button>
                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="bulkSetNoExpiry()">
                    <i class="las la-infinity me-1"></i> Set All: No Expiry
                </button>
            </div>
        </div>

        <!-- Notification Alert -->
        <div id="alertBox" class="d-none"></div>

        <!-- Grand Summary Bar -->
        <div class="summary-bar shadow-sm">
            <div>
                <div class="summary-stat-label">Total Batch Rows</div>
                <div class="summary-stat-value" id="summaryTotalRows">0</div>
            </div>
            <div class="vr bg-secondary opacity-50 d-none d-md-block" style="height: 36px;"></div>
            <div>
                <div class="summary-stat-label">Total Cartons / Boxes</div>
                <div class="summary-stat-value text-info" id="summaryTotalCartons">0</div>
            </div>
            <div class="vr bg-secondary opacity-50 d-none d-md-block" style="height: 36px;"></div>
            <div>
                <div class="summary-stat-label">Total Loose Pieces</div>
                <div class="summary-stat-value text-warning" id="summaryTotalLoose">0</div>
            </div>
            <div class="vr bg-secondary opacity-50 d-none d-md-block" style="height: 36px;"></div>
            <div>
                <div class="summary-stat-label">Grand Total Stock Entry</div>
                <div class="summary-stat-value text-success" id="summaryTotalPcs">0 Total Pcs</div>
            </div>
        </div>

        <!-- Main Card Form -->
        <div class="section-card">
            <div class="card-header-pro">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="las la-layer-group text-primary me-2 fs-5"></i>Opening Stock Batches</h6>
                    <span class="text-muted small">Add multiple products or multiple batches for the same product.</span>
                </div>
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                    <i class="las la-info-circle me-1"></i> No Batch & No Expiry items are automatically supported
                </span>
            </div>
            <div class="card-body-pro">
                <div id="batchRows"></div>
                <button type="button" class="btn-add-row mt-3" onclick="addRow()">
                    <i class="las la-plus-circle fs-5"></i> Add Another Product / Batch Row
                </button>
            </div>
        </div>

        <!-- Bottom Action Bar -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="text-muted small">
                <i class="las la-shield-alt text-success me-1"></i> Stock will be automatically credited to selected warehouse and ledger.
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary px-4 fw-semibold rounded-pill" id="btnCancel"
                    onclick="history.back()">Cancel</button>
                <button type="button" class="btn btn-primary px-5 fw-bold rounded-pill shadow-sm" id="btnSave" onclick="saveAll()">
                    <i class="las la-save me-2" id="saveIcon"></i>
                    <span id="saveSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status"
                        aria-hidden="true"></span>
                    <span id="saveText">Save Opening Stock</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Row Template -->
    <template id="rowTemplate">
        <div class="batch-row" data-row-index="__IDX__">
            <!-- Row Actions (Top Right) -->
            <div class="position-absolute top-0 end-0 m-3 d-flex gap-1">
                <button type="button" class="btn-row-action btn-duplicate-row" onclick="duplicateRow(this)" title="Duplicate Row">
                    <i class="las la-copy"></i>
                </button>
                <button type="button" class="btn-row-action btn-remove-row" onclick="removeRow(this)" title="Remove Row">
                    <i class="las la-trash-alt"></i>
                </button>
            </div>

            <!-- Row Header Indicator -->
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="badge bg-primary-subtle text-primary fw-bold rounded-pill px-3 py-1" style="font-size:0.8rem;">
                    Item / Batch #<span class="row-num-display">__ROW_NUM__</span>
                </span>
                <span class="text-muted small product-pack-info" style="font-size: 0.8rem;">Select product to calculate packing units</span>
            </div>
            
            <div class="row g-3">
                <!-- Column 1: Medicine / Product -->
                <div class="col-lg-5 col-md-6">
                    <label class="form-label-pro">Medicine / Product <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-control-pro product-select" required>
                        <option value="">— Select Product / Item —</option>
                        @foreach ($products as $p)
                            @php
                                $uom = $p->packings->first();
                                $packName = $uom && !empty($uom->name) ? $uom->name : null;
                                $ppbVal   = $uom && $uom->pieces_per_box > 0 ? (int)$uom->pieces_per_box : ($p->pieces_per_box > 0 ? (int)$p->pieces_per_box : 1);
                                if ($ppbVal <= 1) {
                                    if ($packName && preg_match('/1[xX](\d+)/', $packName, $m)) {
                                        $ppbVal = (int)$m[1];
                                    } elseif (preg_match('/1[xX](\d+)/', $p->item_name, $m)) {
                                        $ppbVal = (int)$m[1];
                                    }
                                }
                                if (!$packName || $packName === '1X1') {
                                    $packName = "1X{$ppbVal}";
                                }
                            @endphp
                            <option value="{{ $p->id }}" data-ppb="{{ $ppbVal }}" data-packname="{{ $packName }}" data-code="{{ $p->item_code }}">{{ $p->item_name }} ({{ $p->item_code }}) — [{{ $packName }}]</option>
                        @endforeach
                    </select>
                </div>

                <!-- Column 2: Location -->
                <div class="col-lg-3 col-md-6">
                    <label class="form-label-pro">Stock Location <span class="text-danger">*</span></label>
                    <select class="form-control-pro location-select" required>
                        <option value="">— Select Location —</option>
                        <optgroup label="🏪 Shops">
                            @foreach ($shops as $s)
                                <option value="shop__{{ $s->id }}" data-type="shop" data-wid="{{ $s->id }}">{{ $s->warehouse_name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🏭 Warehouses">
                            <option value="warehouse_group" data-type="warehouse">🏭 Warehouse (Select Sub-warehouse)</option>
                        </optgroup>
                    </select>
                    <!-- Resolved warehouse_id sent to server -->
                    <input type="hidden" name="warehouse_id" class="resolved-warehouse-id">
                    
                    <!-- Sub-dropdown: shown only when warehouse is chosen -->
                    <div class="warehouse-sub-field mt-2 d-none">
                        <select class="form-control-pro warehouse-sub-select">
                            <option value="">— Pick Warehouse —</option>
                            @foreach ($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Column 3: Carton Boxes -->
                <div class="col-lg-2 col-md-6 col-6">
                    <label class="form-label-pro">Cartons / Boxes</label>
                    <input type="number" class="form-control-pro box-qty-input text-end fw-bold" placeholder="0" min="0" step="1">
                </div>

                <!-- Column 4: Loose Pcs -->
                <div class="col-lg-2 col-md-6 col-6">
                    <label class="form-label-pro">Loose Pcs</label>
                    <input type="number" class="form-control-pro loose-qty-input text-end fw-bold" placeholder="0" min="0" step="1">
                </div>

                <!-- Row 2: Batch Number, MFG Date, EXP Date, Expiry Status, Total Stock Summary -->
                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label-pro mb-0">Batch / Lot No.</label>
                        <div class="form-check form-check-inline mb-0 me-0 tag-toggle-check">
                            <input class="form-check-input no-batch-check" type="checkbox" id="no_batch___IDX__" onchange="toggleNoBatch(this)">
                            <label class="form-check-label small fw-bold text-primary" for="no_batch___IDX__" style="font-size:0.75rem; cursor:pointer;">📦 No Batch</label>
                        </div>
                    </div>
                    <input type="text" name="batch_number" class="form-control-pro batch-number-input" placeholder="e.g. BT-2024-001 (or leave blank for No Batch)">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label-pro">MFG Date</label>
                    <input type="text" name="mfg_date" class="form-control-pro mfg-datepicker" placeholder="dd/mm/yyyy (Optional)">
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <label class="form-label-pro mb-0">EXP Date</label>
                        <div class="form-check form-check-inline mb-0 me-0 tag-toggle-check">
                            <input class="form-check-input no-expiry-check" type="checkbox" id="no_exp___IDX__" onchange="toggleNoExpiry(this)">
                            <label class="form-check-label small fw-bold text-success" for="no_exp___IDX__" style="font-size:0.75rem; cursor:pointer;">♾️ No Expiry</label>
                        </div>
                    </div>
                    <input type="text" name="exp_date" class="form-control-pro exp-datepicker" placeholder="dd/mm/yyyy (or check No Expiry)">
                </div>

                <div class="col-lg-2 col-md-3 col-6 d-flex flex-column justify-content-end">
                    <label class="form-label-pro">Status</label>
                    <div id="exp_badge___IDX__" class="badge-exp bg-secondary text-white w-100 text-center fw-bold">
                        —
                    </div>
                </div>

                <div class="col-lg-2 col-md-3 col-6 d-flex flex-column justify-content-end">
                    <label class="form-label-pro text-primary fw-bold">Stock Entry</label>
                    <div class="p-2 bg-primary-subtle border border-primary-subtle rounded-3 text-center">
                        <span class="fw-bold text-primary total-pcs-badge" style="font-size: 0.88rem;">0 Total Pcs</span>
                    </div>
                </div>

            </div>
        </div>
    </template>
@endsection

@section('js')
    <!-- Flatpickr Datepicker JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        let rowCount = 0;

        function recalcRowQty(row) {
            const $pSel = $(row).find('.product-select');
            const optVal = $pSel.val();
            const $opt = optVal ? $pSel.find('option[value="' + optVal + '"]') : $();

            let packName = 'Select product';
            let ppb = 1;

            if (optVal && $opt.length && optVal !== '') {
                packName = $opt.attr('data-packname') || $opt.data('packname') || '';
                ppb = parseInt($opt.attr('data-ppb') || $opt.data('ppb')) || 1;

                if (ppb <= 1) {
                    if (packName && packName.match(/1[xX](\d+)/)) {
                        ppb = parseInt(packName.match(/1[xX](\d+)/)[1]);
                    } else {
                        const optText = $opt.text();
                        if (optText && optText.match(/1[xX](\d+)/)) {
                            ppb = parseInt(optText.match(/1[xX](\d+)/)[1]);
                        }
                    }
                }
                if (!packName || packName === '1X1') {
                    packName = `1X${ppb}`;
                }

                const packInfoEl = row.querySelector('.product-pack-info');
                if (packInfoEl) {
                    packInfoEl.innerHTML = `<span class="badge bg-light text-dark border">Packing: ${packName} (1 Box = ${ppb} Pcs)</span>`;
                }
            } else {
                const packInfoEl = row.querySelector('.product-pack-info');
                if (packInfoEl) {
                    packInfoEl.textContent = 'Select product to calculate packing units';
                }
            }

            const boxInput = row.querySelector('.box-qty-input');
            const looseInput = row.querySelector('.loose-qty-input');
            const boxes = parseFloat(boxInput ? boxInput.value : 0) || 0;
            const loose = parseFloat(looseInput ? looseInput.value : 0) || 0;

            const totalPcs = (boxes * ppb) + loose;
            const badge = row.querySelector('.total-pcs-badge');
            if (badge) {
                badge.textContent = `${totalPcs} Total Pcs`;
            }

            recalcGrandSummary();
            return totalPcs;
        }

        function recalcGrandSummary() {
            let totalRows = 0;
            let totalCartons = 0;
            let totalLoose = 0;
            let grandTotalPcs = 0;

            document.querySelectorAll('.batch-row').forEach(row => {
                totalRows++;
                const boxes = parseFloat(row.querySelector('.box-qty-input')?.value || 0) || 0;
                const loose = parseFloat(row.querySelector('.loose-qty-input')?.value || 0) || 0;
                
                const $pSel = $(row).find('.product-select');
                const optVal = $pSel.val();
                const $opt = optVal ? $pSel.find('option[value="' + optVal + '"]') : $();
                let ppb = parseInt($opt.attr('data-ppb') || $opt.data('ppb')) || 1;
                if (ppb <= 1) {
                    const packName = $opt.attr('data-packname') || '';
                    if (packName && packName.match(/1[xX](\d+)/)) {
                        ppb = parseInt(packName.match(/1[xX](\d+)/)[1]);
                    }
                }

                totalCartons += boxes;
                totalLoose += loose;
                grandTotalPcs += (boxes * ppb) + loose;
            });

            document.getElementById('summaryTotalRows').textContent = totalRows;
            document.getElementById('summaryTotalCartons').textContent = totalCartons.toLocaleString();
            document.getElementById('summaryTotalLoose').textContent = totalLoose.toLocaleString();
            document.getElementById('summaryTotalPcs').textContent = `${grandTotalPcs.toLocaleString()} Total Pcs`;
        }

        function addRow(initialData = null) {
            const tpl = document.getElementById('rowTemplate').innerHTML
                .replace(/__IDX__/g, rowCount)
                .replace(/__ROW_NUM__/g, rowCount + 1);
            const div = document.createElement('div');
            div.innerHTML = tpl;
            const rowElem = div.firstElementChild;
            document.getElementById('batchRows').appendChild(rowElem);

            const row = document.querySelector(`[data-row-index="${rowCount}"]`);

            // Initialize flatpickr on date inputs
            flatpickr(row.querySelector('.mfg-datepicker'), {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                allowInput: true
            });
            flatpickr(row.querySelector('.exp-datepicker'), {
                altInput: true,
                altFormat: "d/m/Y",
                dateFormat: "Y-m-d",
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    updateExpBadge(row, dateStr);
                }
            });

            // Initialize Select2 on product dropdown
            $(row).find('.product-select').select2({ width: '100%' });

            // Initialize Select2 on location dropdown
            $(row).find('.location-select').select2({ width: '100%' });

            // Initialize Select2 on warehouse sub-dropdown
            $(row).find('.warehouse-sub-select').select2({ width: '100%' });

            // Quantity Calculation Event Handlers
            $(row).find('.product-select').on('change select2:select select2:clear', function() {
                $(this).removeClass('is-invalid');
                row.classList.remove('row-invalid');
                recalcRowQty(row);
            });
            $(row).find('.box-qty-input, .loose-qty-input').on('input change', function() {
                $(this).removeClass('is-invalid');
                row.classList.remove('row-invalid');
                recalcRowQty(row);
            });

            // Location change: show/hide warehouse sub-dropdown & resolve warehouse_id
            $(row).find('.location-select').on('change', function() {
                $(this).removeClass('is-invalid');
                row.classList.remove('row-invalid');
                const sel = this.options[this.selectedIndex];
                const type = sel.getAttribute('data-type');
                const wid  = sel.getAttribute('data-wid');
                const subField  = row.querySelector('.warehouse-sub-field');
                const hiddenWid = row.querySelector('.resolved-warehouse-id');

                if (type === 'shop') {
                    hiddenWid.value = wid;
                    subField.classList.add('d-none');
                    $(row).find('.warehouse-sub-select').val('').trigger('change.select2');
                } else if (type === 'warehouse') {
                    hiddenWid.value = '';
                    subField.classList.remove('d-none');
                } else {
                    hiddenWid.value = '';
                    subField.classList.add('d-none');
                }
            });

            // Warehouse sub-select change → update hidden warehouse_id
            $(row).find('.warehouse-sub-select').on('change', function() {
                $(this).removeClass('is-invalid');
                row.classList.remove('row-invalid');
                const hiddenWid = row.querySelector('.resolved-warehouse-id');
                hiddenWid.value = this.value;
            });

            // If initial data is provided (e.g. duplicating row)
            if (initialData) {
                if (initialData.product_id) {
                    $(row).find('.product-select').val(initialData.product_id).trigger('change');
                }
                if (initialData.location_value) {
                    $(row).find('.location-select').val(initialData.location_value).trigger('change');
                    if (initialData.warehouse_sub_id) {
                        setTimeout(() => {
                            $(row).find('.warehouse-sub-select').val(initialData.warehouse_sub_id).trigger('change');
                        }, 50);
                    }
                }
                if (initialData.box_qty) row.querySelector('.box-qty-input').value = initialData.box_qty;
                if (initialData.loose_qty) row.querySelector('.loose-qty-input').value = initialData.loose_qty;
                if (initialData.no_batch) {
                    const chk = row.querySelector('.no-batch-check');
                    chk.checked = true;
                    toggleNoBatch(chk);
                } else if (initialData.batch_number) {
                    row.querySelector('.batch-number-input').value = initialData.batch_number;
                }
                if (initialData.no_exp) {
                    const chk = row.querySelector('.no-expiry-check');
                    chk.checked = true;
                    toggleNoExpiry(chk);
                }
                recalcRowQty(row);
            }

            rowCount++;
            updateRowNumbers();
            recalcGrandSummary();
        }

        function duplicateRow(btn) {
            const row = btn.closest('.batch-row');
            const pId = $(row).find('.product-select').val();
            const locVal = $(row).find('.location-select').val();
            const subWid = $(row).find('.warehouse-sub-select').val();
            const boxQty = row.querySelector('.box-qty-input').value;
            const looseQty = row.querySelector('.loose-qty-input').value;
            const isNoBatch = row.querySelector('.no-batch-check').checked;
            const batchNo = row.querySelector('.batch-number-input').value;
            const isNoExp = row.querySelector('.no-expiry-check').checked;

            addRow({
                product_id: pId,
                location_value: locVal,
                warehouse_sub_id: subWid,
                box_qty: boxQty,
                loose_qty: looseQty,
                no_batch: isNoBatch,
                batch_number: batchNo,
                no_exp: isNoExp
            });
        }

        function removeRow(btn) {
            const row = btn.closest('.batch-row');
            if (document.querySelectorAll('.batch-row').length > 1) {
                row.remove();
                updateRowNumbers();
                recalcGrandSummary();
            } else {
                showAlert('warning', 'At least one row is required in Opening Stock.');
            }
        }

        function updateRowNumbers() {
            document.querySelectorAll('.batch-row').forEach((r, idx) => {
                const display = r.querySelector('.row-num-display');
                if (display) display.textContent = idx + 1;
            });
        }

        function toggleNoBatch(chk) {
            const row = chk.closest('.batch-row');
            const batchInput = row.querySelector('.batch-number-input');

            if (chk.checked) {
                batchInput.value = 'NO-BATCH';
                batchInput.readOnly = true;
                batchInput.classList.add('readonly-tag-field');
                batchInput.placeholder = 'No Batch (Default)';
            } else {
                batchInput.readOnly = false;
                batchInput.classList.remove('readonly-tag-field');
                if (batchInput.value === 'NO-BATCH') {
                    batchInput.value = '';
                }
                batchInput.placeholder = 'e.g. BT-2024-001 (or leave blank for No Batch)';
            }
        }

        function toggleNoExpiry(chk) {
            const row = chk.closest('.batch-row');
            const expInput = row.querySelector('.exp-datepicker');
            const badge = row.querySelector('[id^="exp_badge_"]');

            if (chk.checked) {
                if (expInput._flatpickr) {
                    expInput._flatpickr.setDate('2099-12-31', true);
                    if (expInput._flatpickr.altInput) {
                        expInput._flatpickr.altInput.value = 'No Expiry';
                        expInput._flatpickr.altInput.readOnly = true;
                        expInput._flatpickr.altInput.classList.add('readonly-tag-field');
                    }
                } else {
                    expInput.value = '2099-12-31';
                    expInput.readOnly = true;
                    expInput.classList.add('readonly-tag-field');
                }
                badge.textContent = '♾️ NO EXPIRY';
                badge.className = 'badge-exp bg-success text-white w-100 text-center fw-bold';
            } else {
                if (expInput._flatpickr) {
                    if (expInput._flatpickr.altInput) {
                        expInput._flatpickr.altInput.readOnly = false;
                        expInput._flatpickr.altInput.classList.remove('readonly-tag-field');
                    }
                    expInput._flatpickr.clear();
                } else {
                    expInput.readOnly = false;
                    expInput.classList.remove('readonly-tag-field');
                    expInput.value = '';
                }
                updateExpBadge(row, '');
            }
        }

        function updateExpBadge(row, expDate) {
            const chk = row.querySelector('.no-expiry-check');
            const badge = row.querySelector('[id^="exp_badge_"]');
            if (chk && chk.checked) {
                badge.textContent = '♾️ NO EXPIRY';
                badge.className = 'badge-exp bg-success text-white w-100 text-center fw-bold';
                return;
            }
            if (!expDate) {
                badge.textContent = '—';
                badge.className = 'badge-exp bg-secondary text-white w-100 text-center fw-bold';
                return;
            }
            const expYear = new Date(expDate).getFullYear();
            if (expYear >= 2090) {
                badge.textContent = '♾️ NO EXPIRY';
                badge.className = 'badge-exp bg-success text-white w-100 text-center fw-bold';
                return;
            }
            const days = Math.round((new Date(expDate) - new Date()) / (1000 * 60 * 60 * 24));
            if (days < 0) {
                badge.textContent = 'EXPIRED';
                badge.className = 'badge-exp bg-danger text-white w-100 text-center fw-bold';
            } else if (days <= 30) {
                badge.textContent = days + 'd left';
                badge.className = 'badge-exp bg-warning text-dark w-100 text-center fw-bold';
            } else if (days <= 90) {
                badge.textContent = days + 'd left';
                badge.className = 'badge-exp bg-info text-dark w-100 text-center fw-bold';
            } else {
                badge.textContent = 'OK';
                badge.className = 'badge-exp bg-success text-white w-100 text-center fw-bold';
            }
        }

        function bulkSetNoBatch() {
            document.querySelectorAll('.batch-row').forEach(row => {
                const chk = row.querySelector('.no-batch-check');
                if (chk) {
                    chk.checked = true;
                    toggleNoBatch(chk);
                }
            });
            showAlert('info', 'All rows marked as No Batch.');
        }

        function bulkSetNoExpiry() {
            document.querySelectorAll('.batch-row').forEach(row => {
                const chk = row.querySelector('.no-expiry-check');
                if (chk) {
                    chk.checked = true;
                    toggleNoExpiry(chk);
                }
            });
            showAlert('info', 'All rows marked as No Expiry.');
        }

        function saveAll() {
            const btnSave = document.getElementById('btnSave');
            const btnCancel = document.getElementById('btnCancel');
            const saveSpinner = document.getElementById('saveSpinner');
            const saveIcon = document.getElementById('saveIcon');
            const saveText = document.getElementById('saveText');
            const btnAddRow = document.querySelector('.btn-add-row');
            const removeBtns = document.querySelectorAll('.btn-remove-row');
            const duplicateBtns = document.querySelectorAll('.btn-duplicate-row');

            // Reset visual errors
            document.querySelectorAll('.batch-row').forEach(r => r.classList.remove('row-invalid'));
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            const rows = document.querySelectorAll('.batch-row');
            const data = {
                rows: []
            };
            let validationErrors = [];

            rows.forEach((row, index) => {
                const rowNum = index + 1;
                const pId = row.querySelector('[name="product_id"]')?.value || '';
                const warehouseId = row.querySelector('.resolved-warehouse-id')?.value || '';
                const locSel = row.querySelector('.location-select');
                const locOpt = locSel.options[locSel.selectedIndex];
                const locType = locOpt ? (locOpt.getAttribute('data-type') || '') : '';
                const totalPcs = recalcRowQty(row);

                let batchNo = (row.querySelector('[name="batch_number"]')?.value || '').trim();
                const isNoBatchChecked = row.querySelector('.no-batch-check')?.checked;
                if (isNoBatchChecked || batchNo === '') {
                    batchNo = 'NO-BATCH';
                }

                let expDate = (row.querySelector('[name="exp_date"]')?.value || '').trim();
                const isNoExpChecked = row.querySelector('.no-expiry-check')?.checked;
                if (isNoExpChecked || expDate === '' || expDate === 'No Expiry') {
                    expDate = '2099-12-31';
                }

                const mfgDate = (row.querySelector('[name="mfg_date"]')?.value || '').trim() || null;

                // Validate Essential Requirements
                let rowHasError = false;
                if (!pId) {
                    $(row).find('.product-select').addClass('is-invalid');
                    validationErrors.push(`Row #${rowNum}: Please select a Medicine / Product.`);
                    rowHasError = true;
                }
                if (!warehouseId) {
                    $(row).find('.location-select').addClass('is-invalid');
                    $(row).find('.warehouse-sub-select').addClass('is-invalid');
                    validationErrors.push(`Row #${rowNum}: Please pick a valid Location.`);
                    rowHasError = true;
                }
                if (totalPcs <= 0) {
                    row.querySelector('.box-qty-input')?.classList.add('is-invalid');
                    row.querySelector('.loose-qty-input')?.classList.add('is-invalid');
                    validationErrors.push(`Row #${rowNum}: Total quantity must be greater than 0 (enter Cartons or Loose Pcs).`);
                    rowHasError = true;
                }

                if (rowHasError) {
                    row.classList.add('row-invalid');
                }

                data.rows.push({
                    product_id: pId,
                    warehouse_id: warehouseId,
                    location_type: locType,
                    batch_number: batchNo,
                    is_no_batch: isNoBatchChecked || (batchNo === 'NO-BATCH'),
                    mfg_date: mfgDate,
                    exp_date: expDate,
                    is_no_expiry: isNoExpChecked || (expDate === '2099-12-31'),
                    qty: totalPcs,
                });
            });

            if (validationErrors.length > 0) {
                showAlert('danger', validationErrors.join('<br>'));
                // Scroll to first invalid row
                const firstInvalid = document.querySelector('.batch-row.row-invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            const setProcessingState = (isProcessing) => {
                btnSave.disabled = isProcessing;
                if (btnCancel) btnCancel.disabled = isProcessing;
                if (btnAddRow) btnAddRow.disabled = isProcessing;
                removeBtns.forEach(b => b.disabled = isProcessing);
                duplicateBtns.forEach(b => b.disabled = isProcessing);

                if (isProcessing) {
                    saveIcon.classList.add('d-none');
                    saveSpinner.classList.remove('d-none');
                    saveText.textContent = 'Saving Batches...';
                } else {
                    saveIcon.classList.remove('d-none');
                    saveSpinner.classList.add('d-none');
                    saveText.textContent = 'Save Opening Stock';
                }
            };

            setProcessingState(true);

            fetch('{{ route('batches.opening.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showAlert('success', '<strong>' + res.message + '</strong> Stock has been credited successfully. Resetting form...');
                        setTimeout(() => {
                            document.getElementById('batchRows').innerHTML = '';
                            rowCount = 0;
                            addRow();
                            setProcessingState(false);
                            document.getElementById('alertBox').classList.add('d-none');
                        }, 1800);
                    } else {
                        const errMsg = res.errors ? Object.values(res.errors).flat().join('<br>') : (res.message || 'Validation error');
                        showAlert('danger', errMsg);
                        setProcessingState(false);
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('danger', 'A network or server error occurred. Please try again.');
                    setProcessingState(false);
                });
        }

        function showAlert(type, htmlMsg) {
            const box = document.getElementById('alertBox');
            box.className = `alert alert-${type} alert-dismissible fade show shadow-sm mb-4 border-0`;
            box.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="las ${type === 'success' ? 'la-check-circle fs-4' : (type === 'danger' ? 'la-exclamation-circle fs-4' : 'la-info-circle fs-4')} me-2"></i>
                    <div>${htmlMsg}</div>
                </div>
            `;
            box.classList.remove('d-none');
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Start with one row
        document.addEventListener('DOMContentLoaded', () => addRow());
    </script>
@endsection
