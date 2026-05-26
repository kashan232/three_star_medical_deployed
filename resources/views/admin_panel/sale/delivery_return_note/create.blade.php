@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #e11d48;
            --primary-dark: #be123c;
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
            background: linear-gradient(135deg, #be123c 0%, #4c0519 100%);
            border-radius: 20px;
            padding: 1.75rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px -5px rgba(225, 29, 72, .15);
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
            background: rgba(255, 255, 255, .1);
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
            color: rgba(255, 255, 255, 0.7);
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
            box-shadow: 0 0 0 3px rgba(225, 29, 72, .12);
        }

        .btn-import-dc {
            background: linear-gradient(135deg, #e11d48, #9f1239);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: .6rem 1.4rem;
            font-weight: 700;
            font-size: .875rem;
            cursor: pointer;
            transition: all .25s;
            box-shadow: 0 4px 12px rgba(225, 29, 72, .3);
        }

        .btn-import-dc:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(225, 29, 72, .4);
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

        .items-table td {
            padding: .65rem .75rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: .875rem;
        }

        .row-check {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        .empty-row td {
            text-align: center;
            padding: 2.5rem;
            color: #94a3b8;
            font-size: .875rem;
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
            color: var(--primary);
        }

        /* Modal */
        .modal-content {
            border-radius: 18px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #4c0519, #be123c);
            border-radius: 18px 18px 0 0;
            padding: 1.25rem 1.5rem;
        }

        .modal-title {
            color: #fff;
            font-weight: 700;
        }

        .dc-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            cursor: pointer;
            transition: all .2s;
            margin-bottom: .75rem;
        }

        .dc-card:hover {
            border-color: var(--primary);
            background: #fff1f2;
            transform: translateX(3px);
        }

        .dc-card .dc-no {
            font-weight: 800;
            color: #1e293b;
            font-size: .95rem;
        }

        .dc-card .dc-meta {
            font-size: .78rem;
            color: #64748b;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            {{-- Header --}}
            <div class="page-glass-header">
                <div class="header-title">
                    <h4><i class="fas fa-undo-alt me-3"></i>Create Delivery Return Note</h4>
                    <p>Select a customer, choose a delivery, and record returned items.</p>
                </div>
                <a href="{{ route('delivery.return.index') }}" class="btn btn-outline-light rounded-pill px-4">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>

            <form action="{{ route('delivery.return.store') }}" method="POST" id="drnForm">
                @csrf
                <input type="hidden" name="delivery_note_id" id="deliveryNoteId">
                <input type="hidden" name="sale_id" id="saleId">

                <div class="row g-3">
                    {{-- Left side --}}
                    <div class="col-lg-9">

                        {{-- Header Info --}}
                        <div class="card-section">
                            <p class="section-title">Return Info</p>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Return Number</label>
                                    <input type="text" class="form-control" value="{{ $nextReturnNo }}" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Return Date</label>
                                    <input type="date" name="return_date" class="form-control"
                                        value="{{ date('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Select Customer</label>
                                    <select name="customer_id" id="customerId" class="form-select" required onchange="onCustomerChange(this.value)">
                                        <option value="">-- Choose Customer --</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->customer_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">DC / SO Ref</label>
                                    <input type="text" id="dcSoRef" class="form-control" readonly
                                        placeholder="Auto-filled from DC">
                                </div>
                            </div>
                            <div class="row g-3 mt-1">
                                <div class="col-md-12">
                                    <label class="form-label">Note (Optional)</label>
                                    <textarea name="remarks" class="form-control" rows="2" placeholder="Return reasons..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Product Items --}}
                        <div class="card-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p class="section-title mb-0">Products to Return</p>
                                <button type="button" class="btn-import-dc" id="openDcModal" disabled>
                                    <i class="fas fa-truck-loading me-2"></i>Select Delivery
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="items-table" id="drnItemsTable">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;"><i class="fas fa-check-square"></i></th>
                                            <th>Product</th>
                                            <th>Warehouse</th>
                                            <th style="width:130px;">Delivered Qty (Pcs)</th>
                                            <th style="width:90px;">Box</th>
                                            <th style="width:90px;">Loose</th>
                                            <th style="width:100px;">Total Pcs</th>
                                            <th style="width:110px;" class="d-none">Price/Pc</th>
                                            <th style="width:120px;" class="text-end d-none">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="drnItemsBody">
                                        <tr class="empty-row" id="emptyRow">
                                            <td colspan="7">
                                                <i class="fas fa-search mb-2"
                                                    style="font-size:2rem; color:#cbd5e1;"></i><br>
                                                Select a customer then click <strong>"Select Delivery"</strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /col-lg-9 -->

                    {{-- Right side --}}
                    <div class="col-lg-3">
                        <div class="card-section">
                            <p class="section-title">Summary</p>
                            <div class="summary-box">
                                <div class="summary-row"><span>Total Pieces</span><span id="summaryQty" class="fw-bold">0</span></div>
                                <div class="summary-row total d-none"><span>Net Amount</span><span id="summaryNet" class="fw-800">0.00</span></div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg fw-bold rounded-3 shadow" id="submitBtn" disabled>
                                <i class="fas fa-save me-2"></i>Save Return Note
                            </button>
                            <a href="{{ route('delivery.return.index') }}" class="btn btn-outline-secondary rounded-3">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="dcModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-truck-loading me-2"></i>Select Delivery Note (DCN)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="dcList">
                        <div class="text-center py-4 text-muted">
                            <p>Select a customer first to load deliveries.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#customerId').on('change', function() {
                onCustomerChange($(this).val());
            });
        });

        function onCustomerChange(id) {
            const btn = document.getElementById('openDcModal');
            if (id) {
                btn.disabled = false;
                loadDeliveries(id);
            } else {
                btn.disabled = true;
                document.getElementById('dcList').innerHTML = '<div class="text-center py-4 text-muted"><p>Select a customer first.</p></div>';
            }
        }

        function loadDeliveries(customerId) {
            const container = document.getElementById('dcList');
            container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div></div>';

            fetch(`{{ url('delivery-returns/deliveries') }}/${customerId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        container.innerHTML = '<div class="text-center py-4 text-muted"><p>No non-invoiced deliveries found for this customer.</p></div>';
                        return;
                    }
                    container.innerHTML = data.map(dc => `
                        <div class="dc-card" onclick="importDC(${dc.id})">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="dc-no">${dc.dc_no}</div>
                                    <div class="dc-meta">SO: ${dc.so_no} &nbsp;&bull;&nbsp; Date: ${dc.date}</div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-light text-dark">${dc.items_count} Items</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                });
        }

        document.getElementById('openDcModal').addEventListener('click', function() {
            $('#dcModal').modal('show');
        });

        function importDC(id) {
            $('#dcModal').modal('hide');
            
            fetch(`{{ url('delivery-returns/items') }}/${id}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('deliveryNoteId').value = data.delivery.id;
                    document.getElementById('saleId').value = data.delivery.sale_id;
                    document.getElementById('dcSoRef').value = data.delivery.dc_no + ' / ' + (data.delivery.sale ? data.delivery.sale.invoice_no : 'N/A');
                    
                    renderItems(data.items);
                    document.getElementById('submitBtn').disabled = false;
                });
        }

        function renderItems(items) {
            const tbody = document.getElementById('drnItemsBody');
            if (!items.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="7">No items found.</td></tr>';
                return;
            }

            tbody.innerHTML = items.map((item, i) => `
                <tr class="item-row" id="row-${i}">
                    <td><input type="checkbox" class="row-check" checked onchange="toggleRow(${i})"></td>
                    <td>
                        <input type="hidden" name="product_id[]" value="${item.product_id}">
                        <input type="hidden" name="dc_item_id[]" value="${item.id}">
                        <input type="hidden" name="batch_id[]" value="${item.batch_id || ''}">
                        <input type="hidden" id="ppb-${i}" value="${item.ppb}">
                        <div class="fw-600">${item.product_name}</div>
                        <small class="text-muted font-monospace">${item.product_code}</small>
                        <div class="small text-info mt-1">PPB: ${item.ppb}</div>
                        ${item.lot_number ? `<div class="small text-success">Batch: ${item.lot_number}</div>` : ''}
                    </td>
                    <td>
                        <input type="hidden" name="warehouse_id[]" value="${item.warehouse_id}">
                        <span class="text-muted small">Wh ID: ${item.warehouse_id}</span>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark">${item.delivered_pieces} Pcs</span>
                        <div class="small text-danger mt-1">Rem: ${item.remaining_pieces} Pcs</div>
                        <input type="hidden" id="max-pieces-${i}" value="${item.remaining_pieces}">
                    </td>
                    <td>
                        <input type="number" id="qty-box-${i}" class="form-control text-center" value="0" oninput="calculateTotalPcs(${i})">
                    </td>
                    <td>
                        <input type="number" id="qty-loose-${i}" class="form-control text-center" value="0" oninput="calculateTotalPcs(${i})">
                    </td>
                    <td>
                        <input type="number" step="any" id="total-pcs-${i}" class="form-control text-end fw-bold" value="0" oninput="calculateBoxLoose(${i})">
                        <input type="hidden" name="qty[]" id="qty-${i}" value="0">
                    </td>
                    <td class="d-none">
                        <input type="text" name="price[]" id="price-${i}" class="form-control text-end" value="${item.price}" readonly>
                    </td>
                    <td class="text-end fw-bold text-danger d-none" id="amt-${i}">
                        0.00
                    </td>
                </tr>
            `).join('');
            
            recalcAll();
        }

        function calculateTotalPcs(i) {
            const ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
            let boxes = parseInt(document.getElementById('qty-box-' + i).value) || 0;
            let loose = parseInt(document.getElementById('qty-loose-' + i).value) || 0;
            
            // Normalize loose pieces if they exceed PPB
            if (ppb > 1 && loose >= ppb) {
                boxes += Math.floor(loose / ppb);
                loose = loose % ppb;
                document.getElementById('qty-box-' + i).value = boxes;
                document.getElementById('qty-loose-' + i).value = loose;
            }

            const total = (boxes * ppb) + loose;
            document.getElementById('total-pcs-' + i).value = total;
            
            // Update hidden qty (boxes.loose)
            document.getElementById('qty-' + i).value = boxes + '.' + loose;
            
            validateTotalPcs(i);
            recalcRow(i);
        }

        function calculateBoxLoose(i) {
            const ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
            const totalInput = document.getElementById('total-pcs-' + i);
            let total = parseFloat(totalInput.value) || 0;
            
            const maxPieces = parseFloat(document.getElementById('max-pieces-' + i).value) || 0;
            if (total > maxPieces) {
                total = maxPieces;
                totalInput.value = total;
            }

            const boxes = Math.floor(total / ppb);
            const loose = Math.round((total % ppb) * 100) / 100; // handle float precision
            
            document.getElementById('qty-box-' + i).value = boxes;
            document.getElementById('qty-loose-' + i).value = loose;
            
            // Update hidden qty
            document.getElementById('qty-' + i).value = boxes + '.' + loose;
            
            recalcRow(i);
        }
        
        function validateTotalPcs(i) {
            const totalInput = document.getElementById('total-pcs-' + i);
            const maxPieces = parseFloat(document.getElementById('max-pieces-' + i).value) || 0;
            let total = parseFloat(totalInput.value) || 0;
            
            if (total > maxPieces) {
                total = maxPieces;
                totalInput.value = total;
                // re-calculate box/loose
                const ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
                const boxes = Math.floor(total / ppb);
                const loose = Math.round((total % ppb) * 100) / 100;
                document.getElementById('qty-box-' + i).value = boxes;
                document.getElementById('qty-loose-' + i).value = loose;
                document.getElementById('qty-' + i).value = boxes + '.' + loose;
            }
        }

        function toggleRow(i) {
            const checked = document.querySelector(`#row-${i} .row-check`).checked;
            const tr = document.getElementById('row-' + i);
            tr.querySelectorAll('input:not(.row-check)').forEach(inp => inp.disabled = !checked);
            recalcAll();
        }

        function recalcRow(i) {
            const ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
            const qtyStr = document.getElementById('qty-' + i).value || '0';
            const price = parseFloat(document.getElementById('price-' + i).value) || 0;
            
            let parts = qtyStr.toString().split('.');
            let boxes = parseInt(parts[0]) || 0;
            let loose = parts[1] ? parseInt(parts[1]) : 0;
            let total = (boxes * ppb) + loose;
            
            document.getElementById('amt-' + i).textContent = (total * price).toFixed(2);
            recalcAll();
        }

        function recalcAll() {
            let totalNet = 0;
            let totalPcs = 0;
            
            document.querySelectorAll('.item-row').forEach((tr, i) => {
                const chk = tr.querySelector('.row-check');
                if (chk && chk.checked) {
                    const amt = parseFloat(document.getElementById('amt-' + i).textContent) || 0;
                    const ppb = parseInt(document.getElementById('ppb-' + i).value) || 1;
                    const qtyStr = document.getElementById('qty-' + i).value || '0';
                    let parts = qtyStr.toString().split('.');
                    let boxes = parseInt(parts[0]) || 0;
                    let loose = parts[1] ? parseInt(parts[1]) : 0;
                    
                    totalNet += amt;
                    totalPcs += (boxes * ppb) + loose;
                }
            });
            
            document.getElementById('summaryQty').textContent = totalPcs;
            document.getElementById('summaryNet').textContent = totalNet.toFixed(2);
        }
    </script>
@endsection
