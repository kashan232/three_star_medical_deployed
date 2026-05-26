@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* ── Delivery Report Styles (Same as Sale Report) ────────────────────────── */
        .rpt-page {
            padding: 20px;
        }

        .rpt-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .rpt-header h4 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }

        .rpt-header p {
            margin: 0;
            color: #64748b;
            font-size: .85rem;
        }

        /* Filters */
        .filter-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 18px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .filter-group label {
            font-size: .78rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .filter-group select,
        .filter-group input {
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 7px 10px;
            font-size: .88rem;
            color: #1e293b;
            outline: none;
            background: #f8fafc;
            min-width: 160px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #0ea5e9;
            background: #fff;
        }

        .btn-search {
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 8px 20px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-search:hover {
            background: #0284c7;
        }

        .btn-reset {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 8px 16px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset:hover {
            background: #e2e8f0;
        }

        /* ── ProWaves Detail Report Styles ──────────────── */
        .a4-container {
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-height: 800px;
        }

        .co-header { margin-bottom: 12px; }
        .co-name { font-size: 14px; font-weight: bold; color: #1e293b; }
        .co-addr { font-size: 11px; color: #64748b; line-height: 1.4; }

        .report-title-bar {
            font-size: 18px;
            font-weight: 800;
            border-bottom: 3px solid #000;
            padding-bottom: 6px;
            margin-bottom: 12px;
            color: #000;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .meta-strip {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
            padding: 8px 12px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .ws-title { font-weight: bold; color: #000; }
        .date-range { color: #000; font-weight: 700; }

        /* Main Detailed Table */
        table.rpt-detailed {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.rpt-detailed thead th {
            background: #e2e8f0;
            color: #000;
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Group Header (DELIVERY) */
        .inv-bar-row td {
            background: #f1f5f9;
            padding: 6px 10px;
            border: 1px solid #e2e8f0;
            font-weight: 700;
            color: #334155;
        }

        /* Item Row */
        .it-row td {
            padding: 5px 10px;
            border-bottom: 1px solid #f1f5f9;
        }
        .it-row:hover { background: #f0f9ff; }

        /* Summary Row */
        .inv-sum-row td {
            background: #fff;
            padding: 6px 10px;
            border-top: 1px solid #e2e8f0;
            border-bottom: 2px solid #cbd5e1;
            font-weight: 700;
            text-align: right;
        }

        .text-red { color: #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .loader-wrap {
            padding: 40px;
            text-align: center;
        }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0ea5e9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media print {
            .filter-card, .rpt-actions, .kpi-row, .action-bar { display: none !important; }
            .a4-container { box-shadow: none; padding: 0; }
            body { background: #fff; }
        }
    </style>

    <div class="rpt-page">
        {{-- Header & Actions --}}
        <div class="rpt-actions" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h4 style="margin:0; font-size:1.2rem; color:#1e293b;">🚚 Delivery Report - Detailed</h4>
                <p style="margin:4px 0 0; font-size:.85rem; color:#64748b;">DC-wise detailed shipment analysis</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-pdf" id="btnExportPdf" style="background:#ef4444; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Export PDF</button>
                <button class="btn-print" onclick="window.print()" style="background:#6366f1; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">🖨 Print</button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-card">
            <div class="filter-row" style="display:flex; flex-wrap:wrap; gap:15px;">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="start_date">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="end_date">
                </div>
                <div class="filter-group">
                    <label>Customer</label>
                    <select id="filterCustomer" style="width:200px;">
                        <option value="all">All Customers</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Company (Brand)</label>
                    <select id="filterBrand">
                        <option value="all">All Companies</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select id="filterCategory">
                        <option value="all">All Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-row mt-3" style="display:flex; flex-wrap:wrap; gap:15px;">
                <div class="filter-group">
                    <label>Sub-Category</label>
                    <select id="filterSubCategory">
                        <option value="all">All Sub-category</option>
                        @foreach($subCategories as $sub)
                            <option value="{{ $sub->id }}" data-cat="{{ $sub->category_id }}">{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 2; min-width:300px;">
                    <label>Product</label>
                    <select id="filterProduct" class="select2-product" style="width:100%;">
                        <option value="all">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" 
                                data-cat="{{ $p->category_id }}" 
                                data-sub="{{ $p->sub_category_id }}"
                                data-brand="{{ $p->brand_id }}">
                                {{ $p->item_code }} — {{ $p->item_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="display:flex; align-items:flex-end; gap:8px;">
                    <button id="btnSearch" class="btn-search">🔍 Search</button>
                    <button id="btnReset" class="btn-reset">↺ Reset</button>
                </div>
            </div>
        </div>

        <div class="a4-container" id="reportResult">
            <!-- Company Header -->
            <div class="co-header">
                <div class="co-name">THREE STARS MEDICAL SUPPLIES</div>
                <div class="co-addr">
                    <strong>{{ $activeBranch->name ?? 'Head Office' }} :</strong> {{ $activeBranch->address ?? 'Lahore, Pakistan.' }}<br>
                    <strong>Phone :</strong> {{ $activeBranch->number ?? '+92 42 37353433' }}
                </div>
            </div>

            <div class="report-title-bar">
                <span>Delivery Challan Report - Detailed</span>
            </div>

            <div class="meta-strip">
                <span class="ws-title">DETAILED SHIPMENT REPORT</span>
                <span id="rangeLabel" class="date-range">...</span>
            </div>

            <div class="loader-wrap" id="loader" style="display:none;">
                <div class="spinner"></div>
                <p style="margin-top:10px;color:#94a3b8;font-size:.88rem;">Generating report…</p>
            </div>

            <div class="empty-state" id="emptyState" style="padding:40px; text-align:center;">
                <i class="fas fa-truck mb-3" style="font-size:40px; opacity:0.3; display:block;"></i>
                <p>Select filters and click <strong>Search</strong> to generate the delivery report.</p>
            </div>

            <div id="tableWrap" style="display:none;">
                <table class="rpt-detailed">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th style="width:25%;">Item Description</th>
                            <th style="width:10%;">HS Code</th>
                            <th style="width:12%;">Packing</th>
                            <th style="width:12%;">Warehouse</th>
                            <th class="text-right" style="width:8%;">Rate</th>
                            <th class="text-right" style="width:8%;">Qty</th>
                            <th class="text-right" style="width:8%;">Free</th>
                            <th class="text-right" style="width:12%;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody id="rptBody"></tbody>
                    <tfoot id="rptFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        (function() {
            const fmt = (n) => parseFloat(n || 0).toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            function renderTable(data) {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                document.getElementById('rangeLabel').textContent = `${start} -TO- ${end}`;

                // Group data by Customer
                const groupedByCustomer = {};
                data.forEach(r => {
                    const cName = r.customer_name || 'Walk-in / Unknown';
                    if (!groupedByCustomer[cName]) groupedByCustomer[cName] = [];
                    groupedByCustomer[cName].push(r);
                });

                let html = '';
                let grandTotalPieces = 0;
                let grandTotalFree = 0;
                let grandTotalAmount = 0;

                Object.keys(groupedByCustomer).sort().forEach(customerName => {
                    const customerRows = groupedByCustomer[customerName];
                    
                    // Customer Header Row
                    html += `
                        <tr class="group-header-row" style="background:#e0f2fe; border:1px solid #bae6fd;">
                            <td colspan="9" style="padding:10px; font-size:14px;">
                                <i class="fas fa-user-tie mr-2"></i> <strong>CUSTOMER : ${customerName.toUpperCase()}</strong>
                            </td>
                        </tr>`;

                    let customerPieces = 0;
                    let customerAmount = 0;

                    customerRows.forEach((r) => {
                        const formattedDate = new Date(r.created_at).toLocaleDateString('en-GB');
                        let dcAmount = 0;
                        let dcPieces = 0;

                        // DC Sub-header row
                        html += `
                            <tr class="inv-bar-row">
                                <td colspan="5">
                                    <span style="color:#0369a1;">●</span> <strong>DC No :</strong> ${r.dc_no} &nbsp;&nbsp;
                                    <strong>Date :</strong> ${formattedDate}
                                    ${r.sale ? `&nbsp;&nbsp; <strong>Sale :</strong> ${r.sale.invoice_no}` : ''}
                                </td>
                                <td colspan="4" class="text-right">
                                    <strong>Items :</strong> ${r.items_count}
                                </td>
                            </tr>`;

                        let itemIdx = 1;
                        (r.items_detail || []).forEach(it => {
                            const pcs = parseFloat(it.qty_pieces || 0);
                            const free = parseFloat(it.free_qty || 0);
                            const amount = parseFloat(it.line_total || 0);
                            
                            dcPieces += pcs;
                            dcAmount += amount;

                            html += `
                                <tr class="it-row">
                                    <td>${itemIdx++}</td>
                                    <td>
                                        <div style="font-weight:700; font-size:12px;">${it.product_name || '-'}</div>
                                        <div style="font-size:10px; color:#64748b;">Code: ${it.item_code || '-'}</div>
                                    </td>
                                    <td>${it.hs_code || '-'}</td>
                                    <td>${it.uom_name || '-'}</td>
                                    <td>${it.warehouse || '-'}</td>
                                    <td class="text-right">${fmt(it.price)}</td>
                                    <td class="text-right">${pcs.toFixed(0)} <small>pcs</small></td>
                                    <td class="text-right">${free.toFixed(0)}</td>
                                    <td class="text-right" style="font-weight:600;">${fmt(amount)}</td>
                                </tr>`;
                        });

                        // DC Subtotal row
                        html += `
                            <tr class="inv-sum-row" style="background:#f1f5f9;">
                                <td colspan="6" class="text-right">DC Total:</td>
                                <td class="text-right">${dcPieces.toFixed(0)}</td>
                                <td class="text-right"></td>
                                <td class="text-right">${fmt(dcAmount)}</td>
                            </tr>`;

                        customerPieces += dcPieces;
                        customerAmount += dcAmount;
                        
                        grandTotalPieces += dcPieces;
                        grandTotalFree += (r.items_detail || []).reduce((acc, i) => acc + (parseFloat(i.free_qty) || 0), 0);
                        grandTotalAmount += dcAmount;
                    });

                    // Customer Total Row
                    html += `
                        <tr class="inv-sum-row" style="background:#fefce8; border-bottom:3px double #eab308;">
                            <td colspan="6" class="text-right" style="font-size:13px; color:#854d0e;">
                                <strong>TOTAL FOR ${customerName.toUpperCase()} :</strong>
                            </td>
                            <td class="text-right" style="font-size:13px; color:#854d0e;"><strong>${customerPieces.toFixed(0)}</strong></td>
                            <td class="text-right"></td>
                            <td class="text-right" style="font-size:13px; color:#854d0e;"><strong>${fmt(customerAmount)}</strong></td>
                        </tr>`;
                });

                // Grand Footer
                const footHtml = `
                    <tr class="inv-sum-row" style="background:#1e293b; color:#fff;">
                        <td colspan="6" class="text-right" style="color:#fff;">GRAND TOTAL :</td>
                        <td class="text-right" style="font-size:15px; font-weight:800; color:#fff;">${grandTotalPieces.toFixed(0)}</td>
                        <td class="text-right" style="color:#fff;">${grandTotalFree.toFixed(0)}</td>
                        <td class="text-right" style="font-size:15px; font-weight:800; color:#fff;">${fmt(grandTotalAmount)}</td>
                    </tr>`;

                document.getElementById('rptBody').innerHTML = html;
                document.getElementById('rptFoot').innerHTML = footHtml;
            }

            function updateFilters() {
                var catId   = $('#filterCategory').val();
                var subId   = $('#filterSubCategory').val();
                var brandId = $('#filterBrand').val();

                $('#filterSubCategory option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (catId === 'all' || $opt.attr('data-cat') == catId) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });

                $('#filterProduct option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    var matchCat = (catId === 'all' || $opt.attr('data-cat') == catId);
                    var matchSub = (subId === 'all' || $opt.attr('data-sub') == subId);
                    var matchBrand = (brandId === 'all' || $opt.attr('data-brand') == brandId);
                    if (matchCat && matchSub && matchBrand) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });
                $('.select2-product').trigger('change.select2');
            }

            $(document).ready(function() {
                $('.select2-product').select2();
                $('#filterCategory, #filterSubCategory, #filterBrand').on('change', updateFilters);
                
                // Set default dates
                const now = new Date();
                const pad = n => String(n).padStart(2, '0');
                const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
                const firstOfMonth = today.slice(0, 7) + '-01';
                document.getElementById('start_date').value = firstOfMonth;
                document.getElementById('end_date').value = today;

                fetchReport();
            });

            function fetchReport() {
                const formData = new URLSearchParams({
                    start_date: document.getElementById('start_date').value,
                    end_date: document.getElementById('end_date').value,
                    customer_id: document.getElementById('filterCustomer').value,
                    brand_id: document.getElementById('filterBrand').value,
                    category_id: document.getElementById('filterCategory').value,
                    sub_category_id: document.getElementById('filterSubCategory').value,
                    product_id: document.getElementById('filterProduct').value,
                });

                document.getElementById('loader').style.display = '';
                document.getElementById('tableWrap').style.display = 'none';
                document.getElementById('emptyState').style.display = 'none';

                fetch(`{{ route('report.dc.fetch') }}`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' 
                    },
                    body: formData.toString()
                })
                .then(r => r.json())
                .then(res => {
                    document.getElementById('loader').style.display = 'none';
                    if (res.data && res.data.length) {
                        document.getElementById('tableWrap').style.display = '';
                        renderTable(res.data);
                    } else {
                        document.getElementById('emptyState').style.display = '';
                        document.getElementById('emptyState').querySelector('p').textContent = 'No delivery records found.';
                    }
                })
                .catch(err => {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('emptyState').style.display = '';
                    document.getElementById('emptyState').querySelector('p').textContent = 'Error loading report. Please check console.';
                    console.error('Fetch error:', err);
                });
            }

            document.getElementById('btnSearch').addEventListener('click', fetchReport);
            document.getElementById('btnReset').addEventListener('click', () => {
                location.reload();
            });

            document.getElementById('btnExportPdf').addEventListener('click', async function() {
                const el = document.getElementById('reportResult');
                const { jsPDF } = window.jspdf;
                const canvas = await html2canvas(el, { scale: 2 });
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const w = pdf.internal.pageSize.getWidth();
                const h = (canvas.height * w) / canvas.width;
                pdf.addImage(imgData, 'PNG', 0, 0, w, h);
                pdf.save('Delivery_Report.pdf');
            });
        })();
    </script>
@endsection
