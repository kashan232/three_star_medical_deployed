@extends('admin_panel.layout.app')
@section('title', 'Edit Delivery Note #' . $dc->dc_no)

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #10b981;
            --danger: #ef4444;
        }

        body { background: #f1f5f9; font-family: 'Inter', sans-serif; color: #1e293b; }
        .main-content { padding: 1.5rem; }
        .page-glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px; padding: 1.75rem 2rem; margin-bottom: 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .15); position: relative; overflow: hidden;
        }
        .header-title h4 { color: #fff; font-weight: 800; font-size: 1.5rem; margin: 0; }
        .header-title p { color: #94a3b8; font-size: .875rem; margin: 0; }

        .card-section {
            background: #fff; border-radius: 18px; border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04); padding: 1.5rem; margin-bottom: 1.5rem;
        }
        .section-title {
            font-size: .75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .08em; color: #64748b; padding-bottom: .75rem;
            border-bottom: 2px solid #f1f5f9; margin-bottom: 1.25rem;
        }
        .form-label { font-size: .8rem; font-weight: 600; color: #475569; margin-bottom: .35rem; }
        .form-control, .form-select { border-radius: 10px; border: 1px solid #e2e8f0; padding: .55rem .9rem; font-size: .875rem; }
        
        .floating-action-bar {
            position: fixed; bottom: 25px; left: 50%; transform: translateX(-50%);
            background: #1e293b; padding: 8px 12px; border-radius: 50px;
            display: flex; align-items: center; gap: 10px; box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            z-index: 1000; border: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px);
        }
        .btn-floating-save {
            background: #4f46e5; color: white; border: none; border-radius: 40px;
            padding: 10px 24px; font-weight: 700; font-size: 0.9rem;
            display: flex; align-items: center; gap: 8px; transition: all 0.2s; cursor: pointer;
        }
        .btn-floating-save:hover { background: #4338ca; transform: scale(1.02); }
        .btn-floating-cancel {
            background: rgba(255,255,255,0.1); color: #cbd5e1; border: none; border-radius: 40px;
            padding: 10px 20px; font-weight: 600; font-size: 0.9rem; text-decoration: none;
            transition: all 0.2s; display: flex; align-items: center;
        }

        .items-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .items-table thead th {
            background: #f8fafc; padding: .85rem .75rem; font-size: .7rem; font-weight: 700;
            color: #475569; text-transform: uppercase; border-bottom: 2px solid #f1f5f9;
        }
        .items-table td { padding: .65rem .75rem; border-bottom: 1px solid #f1f5f9; font-size: .875rem; }

        .product-tag { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; border-radius: 8px; padding: .25rem .65rem; font-size: .75rem; font-weight: 600; }
        
        /* Product Select Button Style */
        .product-select-btn {
            background: #fff !important; color: #333 !important; border: 1px solid #000 !important;
            padding: 4px 10px !important; border-radius: 1px !important; font-weight: 500 !important;
            width: 100% !important; text-align: left !important; position: relative !important; cursor: pointer !important; font-size: 13px !important;
        }
        .product-select-btn.has-value { font-weight: 700 !important; }
        .psm-btn-arrow { float: right; font-size: 0.8em; margin-top: 3px; }

        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; }
        .summary-row { display: flex; justify-content: space-between; align-items: center; padding: .35rem 0; font-size: .875rem; }
        .summary-row.total { font-weight: 800; font-size: 1rem; border-top: 2px solid #e2e8f0; padding-top: .75rem; margin-top: .5rem; }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="page-glass-header">
                <div class="header-title">
                    <h4><i class="fas fa-edit me-3"></i>Edit Delivery Note #{{ $dc->dc_no }}</h4>
                    <p>Modify shipment details and adjust quantities.</p>
                </div>
                <div class="d-flex gap-2">
                    <form action="{{ route('delivery.note.cancel', $dc->id) }}" method="POST" id="cancel-form-{{ $dc->id }}">
                        @csrf
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4" onclick="confirmCancel({{ $dc->id }})">
                            <i class="fas fa-times-circle me-2"></i>Cancel DC
                        </button>
                    </form>
                    <a href="{{ route('delivery.note.index') }}" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </div>

            <form action="{{ route('delivery.note.update', $dc->id) }}" method="POST" id="dcForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="sale_id" id="saleId" value="{{ $dc->sale_id }}">
                <input type="hidden" name="branch_id" value="{{ $dc->branch_id }}">

                @if ($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm mb-4" style="border-radius:15px;">
                        <ul class="mb-0 py-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-lg-7">
                        <div class="card-section h-100">
                            <p class="section-title">Delivery Note Info</p>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label">DC No</label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $dc->dc_no }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="delivery_date" class="form-control form-control-sm" value="{{ $dc->delivery_date }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Customer</label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $dc->customer->customer_name ?? 'N/A' }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Branch</label>
                                    <input type="text" class="form-control form-control-sm" value="{{ $branchName }}" readonly>
                                </div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">Remarks</label>
                                    <input type="text" name="note" class="form-control form-control-sm" value="{{ $dc->note }}" placeholder="Delivery notes...">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" id="enable_hs_code" name="enable_hs_code" value="1" {{ $dc->enable_hs_code ? 'checked' : '' }}>
                                        <label class="form-check-label fw-bold small" for="enable_hs_code">HS CODE</label>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" name="is_sample" id="isSample" value="1" {{ $dc->is_sample ? 'checked' : '' }} onclick="return false;">
                                        <label class="form-check-label fw-bold text-primary small" for="isSample">SAMPLE</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card-section h-100">
                            <p class="section-title">Summary</p>
                            <div class="summary-box p-2">
                                <div class="summary-row small"><span>Total Qty</span><span id="summaryQty" class="fw-bold text-primary">0</span></div>
                                <div class="summary-row total text-primary"><span>Net Amount</span><span id="summaryNet">0.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12">
                        <div class="card-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="section-title mb-0">Products</p>
                                @if($dc->is_sample)
                                    <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm btn-sm" id="addManualRow">
                                        <i class="fas fa-plus me-1"></i>Add Product Row
                                    </button>
                                @endif
                            </div>

                            <div class="table-responsive">
                                <table class="items-table" id="dcItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">#</th>
                                            <th>Product</th>
                                            <th class="hs-code-col" style="width:90px;">HS Code</th>
                                            <th style="width:100px;">UOM</th>
                                            <th>Warehouse</th>
                                            <th style="width:150px;">Lot / Batch</th>
                                            @if(!$dc->is_sample) <th style="width:90px;">Order</th> @endif
                                            <th style="width:140px;">Deliver Qty</th>
                                            <th class="text-end" style="width:110px;">Amount</th>
                                            @if($dc->is_sample) <th style="width:40px;"></th> @endif
                                        </tr>
                                    </thead>
                                    <tbody id="dcItemsBody">
                                        @foreach($dc->items as $index => $item)
                                            @php
                                                $ppb = (int)($item->product->pieces_per_box ?? 1);
                                                $totalPieces = (int)$item->total_pieces;
                                                // Max allowed = CurrentDCQty + (SO_Total - SO_Delivered)
                                                if (!$dc->is_sample && $item->sale_item) {
                                                    $remPieces = ($item->sale_item->total_pieces ?? 0) - ($item->sale_item->delivered_qty ?? 0);
                                                    $maxPieces = $totalPieces + $remPieces;
                                                } else {
                                                    $maxPieces = 999999;
                                                }
                                            @endphp
                                            <tr class="item-row" id="item-row-{{ $index }}">
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <input type="hidden" name="product_id[]" value="{{ $item->product_id }}" class="item-id" id="pid-{{ $index }}">
                                                    <input type="hidden" name="sale_item_id[]" value="{{ $item->sale_item_id }}" id="siid-{{ $index }}">
                                                    <input type="hidden" id="ppb-{{ $index }}" value="{{ $ppb }}">
                                                    <div class="fw-600 text-dark">{{ $item->product->item_name }}</div>
                                                    <small class="text-muted font-monospace">{{ $item->product->item_code }}</small>
                                                </td>
                                                <td class="hs-code-col">
                                                    <input type="text" name="hs_code[]" class="form-control text-center hs-code" value="{{ $item->product->hs_code }}" readonly style="background:#f8fafc; font-size: 0.8rem;">
                                                </td>
                                                <td>
                                                    <select name="uom_id[]" id="uom-{{ $index }}" class="form-select form-select-sm uom-select" onchange="updateUomInfo('{{ $index }}', this.value)">
                                                        @if($item->product->packings->isEmpty())
                                                            <option value="" data-ppb="{{ $ppb }}">{{ $item->product->unit->name ?? 'Piece' }}</option>
                                                        @else
                                                            @foreach($item->product->packings as $pkg)
                                                                <option value="{{ $pkg->id }}" data-ppb="{{ $pkg->pieces_per_box }}" {{ $item->uom_id == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="warehouse_id[]" id="wh-{{ $index }}" class="form-select form-select-sm warehouse-select" onchange="loadBatches('{{ $index }}', {{ $item->product_id }}, this.value); recalcAll();">
                                                        @foreach($warehouses as $w)
                                                            <option value="{{ $w->id }}" {{ $item->warehouse_id == $w->id ? 'selected' : '' }}>{{ $w->warehouse_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="batch_id[]" id="batch-{{ $index }}" class="form-select form-select-sm batch-select">
                                                        <option value="">FEFO (Auto)</option>
                                                        @if($item->batch_id)
                                                            <option value="{{ $item->batch_id }}" selected>{{ $item->lot_number }} (Current)</option>
                                                        @endif
                                                    </select>
                                                </td>
                                                @if(!$dc->is_sample)
                                                <td>
                                                    @if($item->sale_item)
                                                        <div class="d-flex flex-column gap-1">
                                                            <span class="badge bg-light text-primary border" style="font-size: 0.65rem;">ORDERED: {{ $item->sale_item->total_pieces ?? 0 }}</span>
                                                            <span class="badge bg-light text-warning border" style="font-size: 0.65rem;">REMAINING: {{ ($item->sale_item->total_pieces ?? 0) - ($item->sale_item->delivered_qty ?? 0) }}</span>
                                                        </div>
                                                    @else
                                                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">MANUAL ENTRY</span>
                                                    @endif
                                                </td>
                                                @endif
                                                <td>
                                                    <input type="hidden" id="max-qty-{{ $index }}" value="{{ $maxPieces }}">
                                                    <div class="d-flex gap-1">
                                                        <input type="number" id="qty-box-{{ $index }}" class="form-control form-control-sm text-center" value="{{ floor($totalPieces / $ppb) }}" oninput="recalcRow('{{ $index }}')">
                                                        <input type="number" id="qty-loose-{{ $index }}" class="form-control form-control-sm text-center" value="{{ $totalPieces % $ppb }}" oninput="recalcRow('{{ $index }}')">
                                                        <input type="hidden" name="qty[]" id="qty-{{ $index }}" value="{{ $totalPieces }}">
                                                        <input type="hidden" name="free_qty[]" value="{{ $item->free_qty }}">
                                                        <input type="hidden" name="price[]" id="price-{{ $index }}" value="{{ $item->price }}">
                                                    </div>
                                                </td>
                                                <td class="text-end fw-700 text-primary" id="amt-{{ $index }}">{{ number_format($item->line_total, 2) }}</td>
                                                @if($dc->is_sample)
                                                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow('{{ $index }}')"><i class="fas fa-times"></i></button></td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-action-bar">
                    <a href="{{ route('delivery.note.index') }}" class="btn-floating-cancel">Discard</a>
                    <div style="width:1px; height:24px; background:rgba(255,255,255,0.1); margin:0 5px;"></div>
                    <button type="submit" class="btn-floating-save">
                        <i class="fas fa-save me-2"></i>Update Delivery Note
                    </button>
                </div>
            </form>
        </div>
    </div>

    @include('admin_panel.components.product_select_modal')
@endsection

@section('js')
    <script>
        const allWarehouses = @json($warehouses);
        let rowCount = {{ $dc->items->count() }};

        $(document).ready(function() {
            $('#enable_hs_code').on('change', function() {
                $('.hs-code-col').toggle($(this).is(':checked'));
            }).trigger('change');

            $('.item-row').each(function() {
                const idx = $(this).attr('id').replace('item-row-', '');
                const pid = $(`#pid-${idx}`).val();
                const whId = $(`#wh-${idx}`).val();
                loadBatches(idx, pid, whId);
            });

            recalcAll();
        });

        function loadBatches(idx, pid, whId) {
            const $sel = $(`#batch-${idx}`);
            const currentVal = $sel.val();
            $sel.html('<option value="">FEFO (Auto)</option>');
            $.get(`{{ url('batches/product') }}/${pid}`, { warehouse_id: whId }).done(function(batches) {
                batches.forEach(b => {
                    $sel.append(`<option value="${b.id}" ${b.id == currentVal ? 'selected' : ''}>${b.label}</option>`);
                });
            });
        }

        function recalcRow(i) {
            const ppb = parseInt($(`#ppb-${i}`).val()) || 1;
            const boxes = parseInt($(`#qty-box-${i}`).val()) || 0;
            const loose = parseInt($(`#qty-loose-${i}`).val()) || 0;
            let total = (boxes * ppb) + loose;

            const max = parseInt($(`#max-qty-${i}`).val()) || 999999;
            if (total > max) {
                total = max;
                $(`#qty-box-${i}`).val(Math.floor(max / ppb));
                $(`#qty-loose-${i}`).val(max % ppb);
            }

            $(`#qty-${i}`).val(total);
            const price = parseFloat($(`#price-${i}`).val()) || 0;
            $(`#amt-${i}`).text((total * price).toFixed(2));
            recalcAll();
        }

        function recalcAll() {
            let totalQty = 0;
            let totalAmt = 0;
            $('.item-row').each(function() {
                const idx = $(this).attr('id').replace('item-row-', '');
                totalQty += parseInt($(`#qty-${idx}`).val()) || 0;
                totalAmt += parseFloat($(`#amt-${idx}`).text()) || 0;
            });
            $('#summaryQty').text(totalQty);
            $('#summaryNet').text(totalAmt.toFixed(2));
        }

        function removeRow(i) {
            $(`#item-row-${i}`).remove();
            recalcAll();
        }

        @if($dc->is_sample)
        $('#addManualRow').click(function() {
            ERPProductModal.open({
                priceField: 'sale',
                onSelect: function(products) {
                    products.forEach(p => addProductRow(p));
                }
            });
        });

        function addProductRow(p) {
            const idx = rowCount++;
            const ppb = p.pieces_per_box || 1;
            let uomHtml = '';
            if (p.packings && p.packings.length > 0) {
                p.packings.forEach(pkg => {
                    uomHtml += `<option value="${pkg.id}" data-ppb="${pkg.pieces_per_box}">${pkg.name}</option>`;
                });
            } else {
                uomHtml = `<option value="" data-ppb="${ppb}">${p.unit_name || 'Piece'}</option>`;
            }

            const row = `
                <tr class="item-row" id="item-row-${idx}">
                    <td>${idx + 1}</td>
                    <td>
                        <input type="hidden" name="product_id[]" value="${p.id}" class="item-id" id="pid-${idx}">
                        <input type="hidden" name="sale_item_id[]" value="">
                        <input type="hidden" id="ppb-${idx}" value="${ppb}">
                        <div class="fw-600 text-dark">${p.item_name}</div>
                    </td>
                    <td class="hs-code-col"><input type="text" name="hs_code[]" value="${p.hs_code || ''}" class="form-control form-control-sm" readonly></td>
                    <td><select name="uom_id[]" id="uom-${idx}" class="form-select form-select-sm" onchange="updateUomInfo(${idx}, this.value)">${uomHtml}</select></td>
                    <td><select name="warehouse_id[]" id="wh-${idx}" class="form-select form-select-sm" onchange="loadBatches(${idx}, ${p.id}, this.value)">${allWarehouses.map(w => `<option value="${w.id}">${w.warehouse_name}</option>`).join('')}</select></td>
                    <td><select name="batch_id[]" id="batch-${idx}" class="form-select form-select-sm"><option value="">FEFO (Auto)</option></select></td>
                    <td>
                        <input type="hidden" id="max-qty-${idx}" value="999999">
                        <div class="d-flex gap-1">
                            <input type="number" id="qty-box-${idx}" class="form-control form-control-sm text-center" value="0" oninput="recalcRow(${idx})">
                            <input type="number" id="qty-loose-${idx}" class="form-control form-control-sm text-center" value="0" oninput="recalcRow(${idx})">
                            <input type="hidden" name="qty[]" id="qty-${idx}" value="0">
                            <input type="hidden" name="free_qty[]" value="0">
                            <input type="hidden" name="price[]" id="price-${idx}" value="0">
                        </div>
                    </td>
                    <td class="text-end fw-700 text-primary" id="amt-${idx}">0.00</td>
                    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${idx})"><i class="fas fa-times"></i></button></td>
                </tr>
            `;
            $('#dcItemsBody').append(row);
            loadBatches(idx, p.id, $(`#wh-${idx}`).val());
        }
        @endif

        function updateUomInfo(i, uomId) {
            const $uomSel = document.getElementById(`uom-${i}`);
            const option = $uomSel.options[$uomSel.selectedIndex];
            const ppb = option ? (option.getAttribute('data-ppb') || 1) : 1;
            document.getElementById(`ppb-${i}`).value = ppb;
            recalcRow(i);
        }

        function confirmCancel(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This DC will be cancelled and stock will be returned to the warehouse/batches!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel-form-' + id).submit();
                }
            });
        }
    </script>
@endsection
