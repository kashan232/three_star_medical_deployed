@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* ── Sale Report Styles ────────────────────────── */
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

        /* Group Header (SALE) */
        .group-header-row td {
            background: #f8fafc;
            color: #000;
            font-weight: 800;
            font-size: 12px;
            text-align: center;
            padding: 6px;
            border: 1px solid #cbd5e1;
        }

        /* Invoice bar */
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

        /* Invoice Summary Row */
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
                <h4 style="margin:0; font-size:1.2rem; color:#1e293b;">🧾 Sale Report - Detailed</h4>
                <p style="margin:4px 0 0; font-size:.85rem; color:#64748b;">Invoice-wise detailed sales analysis</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-pdf" id="btnExportPdf" style="background:#ef4444; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Export PDF</button>
                <button class="btn-print" onclick="window.print()" style="background:#6366f1; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">🖨 Print</button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-card">
            <div class="filter-row" style="display:flex; flex-wrap:wrap; gap:15px;">
                <div class="filter-group" style="display:flex; flex-direction:column; gap:5px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Start Date</label>
                    <input type="date" id="start_date" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                </div>
                <div class="filter-group" style="display:flex; flex-direction:column; gap:5px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">End Date</label>
                    <input type="date" id="end_date" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                </div>
                <div class="filter-group" style="display:flex; flex-direction:column; gap:5px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Customer</label>
                    <select id="filterCustomer" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem; width:180px;">
                        <option value="all">All Customers</option>
                    </select>
                </div>
                <div class="filter-group" style="display:flex; flex-direction:column; gap:5px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Warehouse</label>
                    <select id="filterWarehouse" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem; width:180px;">
                        <option value="all">All Warehouses</option>
                    </select>
                </div>
                <div class="filter-group" style="display:flex; flex-direction:column; gap:5px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Status</label>
                    <select id="filterStatus" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                        <option value="all">All Status</option>
                        <option value="posted">Posted</option>
                        <option value="booked">Booked</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
            </div>
            <div class="filter-row mt-3" style="display:flex; flex-wrap:wrap; gap:15px;">
                <div class="filter-group">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Category</label>
                    <select id="filterCategory" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                        <option value="all">All Category</option>
                        @foreach(App\Models\Category::orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Sub-Category</label>
                    <select id="filterSubCategory" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                        <option value="all">All Sub-category</option>
                        @foreach(App\Models\Subcategory::orderBy('name')->get() as $sc)
                            <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Company (Brand)</label>
                    <select id="filterBrand" style="padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:.88rem;">
                        <option value="all">All Companies</option>
                        @foreach(App\Models\Brand::orderBy('name')->get() as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 2; min-width:300px;">
                    <label style="font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase;">Product</label>
                    <select id="filterProduct" class="select2-product" style="width:100%;">
                        <option value="all">All Products</option>
                        @foreach(App\Models\Product::orderBy('item_name')->get() as $p)
                            <option value="{{ $p->id }}" 
                                data-cat="{{ $p->category_id }}" 
                                data-sub="{{ $p->sub_category_id }}"
                                data-brand="{{ $p->brand_id }}">
                                {{ $p->item_code }} — {{ $p->item_name }} {{ $p->brand->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="display:flex; align-items:flex-end; gap:8px;">
                    <button id="btnSearch" style="background:#0ea5e9; color:#fff; border:none; padding:8px 20px; border-radius:6px; font-weight:600; cursor:pointer;">🔍 Search</button>
                    <button id="btnReset" style="background:#94a3b8; color:#fff; border:none; padding:8px 20px; border-radius:6px; font-weight:600; cursor:pointer;">↺ Reset</button>
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
                <span>Sale Report - Invoice-Wise</span>
            </div>

            <div class="meta-strip">
                <span class="ws-title">WHOLESALE DETAILED REPORT</span>
                <span id="rangeLabel" class="date-range">...</span>
            </div>

            <div class="loader-wrap" id="loader">
                <div class="spinner"></div>
                <p style="margin-top:10px;color:#94a3b8;font-size:.88rem;">Generating dynamic report…</p>
            </div>

            <div class="empty-state" id="emptyState">
                <i class="fas fa-file-invoice mb-3" style="font-size:40px; opacity:0.3; display:block; text-align:center;"></i>
                <p style="text-align:center;">Select filters and click <strong>Search</strong> to generate the detailed invoice-wise report.</p>
            </div>

            <div id="tableWrap" style="display:none;">
                <table class="rpt-detailed">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th style="width:22%;">Item Description</th>
                            <th style="width:8%;">HS Code</th>
                            <th style="width:10%;">Packing</th>
                            <th class="text-right" style="width:8%;">Rate</th>
                            <th class="text-right" style="width:7%;">Qty</th>
                            <th class="text-right" style="width:7%;">Free</th>
                            <th class="text-right" style="width:8%;">Discount</th>
                            <th class="text-right" style="width:8%;">GST</th>
                            <th class="text-right" style="width:8%;">Income Tax</th>
                            <th class="text-right" style="width:8%;">Adv</th>
                            <th class="text-right" style="width:12%;">Net Total</th>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>

    <script>
        (function() {
            const fmt = (n) => parseFloat(n || 0).toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            function statusBadge(s) {
                const map = {
                    posted: '<span class="badge badge-posted">Posted</span>',
                    booked: '<span class="badge badge-booked">Booked</span>',
                    cancelled: '<span class="badge badge-cancelled">Cancelled</span>',
                    returned: '<span class="badge badge-returned">Returned</span>',
                };
                return map[s] || `<span class="badge">${s || '-'}</span>`;
            }

            function itemDisplay(it) {
                // Show quantity in a human-readable format based on size_mode
                if (it.size_mode === 'by_carton' || it.size_mode === 'by_cartons') {
                    return `${parseFloat(it.qty).toFixed(0)} boxes (${it.total_pieces} pcs)`;
                } else if (it.size_mode === 'by_size') {
                    return `${it.total_pieces} pcs`;
                }
                return parseFloat(it.qty).toFixed(2) + ' pcs';
            }

            function renderTable(data, totals) {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                document.getElementById('rangeLabel').textContent = `${start} -TO- ${end}`;

                let html = `
                    `;

                let grandTotalQty = 0;
                let grandTotalFree = 0;
                let grandTotalNet = 0;

                data.forEach((s) => {
                    const dateArr = (s.created_at || '').split('T')[0].split('-');
                    const formattedDate = dateArr.length === 3 ? `${dateArr[2]}/${dateArr[1]}/${dateArr[0]}` : s.created_at;

                    let invQty = 0;
                    let invFree = 0;
                    let invNet = 0;

                    // Invoice Header row
                    html += `
                        <tr class="inv-bar-row">
                            <td colspan="5">
                                <strong>SALE</strong> &nbsp;&nbsp;
                                <strong>Invoice :</strong> ${s.invoice_no} &nbsp;&nbsp;
                                <strong>Date :</strong> ${formattedDate}
                            </td>
                            <td colspan="7">
                                <strong>Customer :</strong> ${s.customer_name}
                            </td>
                        </tr>`;

                    let itemIdx = 1;
                    (s.items || []).forEach(it => {
                        const qty = parseFloat(it.qty || 0);
                        const free = parseFloat(it.free_qty || 0);
                        const total = parseFloat(it.total || 0);
                        invQty += qty;
                        invFree += free;
                        invNet += total;

                        html += `
                                <tr class="it-row">
                                    <td>${itemIdx++}</td>
                                    <td>
                                        <div style="font-weight:700; font-size:12px;">${it.item_name || '-'}</div>
                                        <div style="font-size:10px; color:#64748b;">Code: ${it.item_code || '-'}</div>
                                    </td>
                                    <td>${it.hs_code || '-'}</td>
                                    <td>${it.uom_name}</td>
                                    <td class="text-right">${fmt(it.price)}</td>
                                    <td class="text-right">${qty.toFixed(0)}</td>
                                    <td class="text-right">${free.toFixed(0)}</td>
                                    <td class="text-right">${fmt(it.discount_amount)}</td>
                                    <td class="text-right">
                                        <div>${fmt(it.gst_amount)}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.gst_percent}%)</div>
                                    </td>
                                    <td class="text-right">
                                        <div>${fmt(it.wht_amount)}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.inc_tax_percent}%)</div>
                                    </td>
                                    <td class="text-right">
                                        <div>${fmt(it.adv_amount)}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.adv_tax_percent}%)</div>
                                    </td>
                                    <td class="text-right" style="font-weight:600;">${fmt(total)}</td>
                                </tr>`;
                    });

                    // Subtotal for this invoice
                    html += `
                        <tr class="inv-sum-row">
                            <td colspan="5" class="text-right">
                                <span class="text-red">TOTAL FOR :</span> SALE ${s.invoice_no}
                            </td>
                            <td class="text-right"><span class="text-red">${invQty.toFixed(0)}</span></td>
                            <td class="text-right"><span class="text-red">${invFree.toFixed(0)}</span></td>
                            <td colspan="4"></td>
                            <td class="text-right">${fmt(invNet)}</td>
                        </tr>`;

                    grandTotalQty += invQty;
                    grandTotalFree += invFree;
                    grandTotalNet += invNet;
                });

                // Grand Footer
                const footHtml = `
                    <tr class="inv-sum-row" style="background:#f8fafc;">
                        <td colspan="5" class="text-right">Grand Total :</td>
                        <td class="text-right">${grandTotalQty.toFixed(0)}</td>
                        <td class="text-right">${grandTotalFree.toFixed(0)}</td>
                        <td colspan="4"></td>
                        <td class="text-right" style="font-size:14px; font-weight:800;">${fmt(grandTotalNet)}</td>
                    </tr>`;

                document.getElementById('rptBody').innerHTML = html;
                document.getElementById('rptFoot').innerHTML = footHtml;
            }

            let lastData = [];

            // ── Dynamic Dropdown Logic ──────────────────────────────────────────
            function updateFilters() {
                var catId   = $('#filterCategory').val();
                var subId   = $('#filterSubCategory').val();
                var brandId = $('#filterBrand').val();

                var validSubs = new Set();
                $('#filterSubCategory option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    var matchCat = (catId === 'all' || $opt.attr('data-cat') == catId);
                    if (matchCat) { $opt.show().prop('disabled', false); validSubs.add($opt.val()); }
                    else { $opt.hide().prop('disabled', true); }
                });
                if (subId !== 'all' && !validSubs.has(subId)) {
                    $('#filterSubCategory').val('all').trigger('change.select2');
                    subId = 'all';
                }

                var validBrands = new Set();
                var validProds  = new Set();
                $('#filterProduct option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    var pCat = $opt.attr('data-cat');
                    var pSub = $opt.attr('data-sub');
                    var pBrand = $opt.attr('data-brand');
                    var matchCat = (catId === 'all' || pCat == catId);
                    var matchSub = (subId === 'all' || pSub == subId);
                    if (matchCat && matchSub) {
                        if (pBrand) validBrands.add(pBrand);
                        if (brandId === 'all' || pBrand == brandId) validProds.add($opt.val());
                    }
                });

                $('#filterBrand option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validBrands.has($opt.val())) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });
                if (brandId !== 'all' && !validBrands.has(brandId)) {
                    $('#filterBrand').val('all').trigger('change.select2');
                    brandId = 'all';
                }

                $('#filterProduct option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validProds.has($opt.val())) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });

                $('.select2-product').trigger('change.select2');
            }

            $(document).ready(function() {
                $('.select2-product').select2({ width: '100%' });
                
                $('#filterCategory').on('change', updateFilters);
                $('#filterSubCategory, #filterBrand').on('change', updateFilters);
                updateFilters(); // Initial sync
            });

            function fetchReport() {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                const customer = document.getElementById('filterCustomer').value;
                const wh = document.getElementById('filterWarehouse').value;
                const status = document.getElementById('filterStatus').value;
                const cat = document.getElementById('filterCategory').value;
                const sub = document.getElementById('filterSubCategory').value;
                const brand = document.getElementById('filterBrand').value;
                const product = document.getElementById('filterProduct').value;

                document.getElementById('loader').style.display = '';
                document.getElementById('tableWrap').style.display = 'none';
                document.getElementById('emptyState').style.display = 'none';

                const params = new URLSearchParams({
                    start_date: start,
                    end_date: end,
                    customer_id: customer,
                    warehouse_id: wh,
                    status: status,
                    category_id: cat,
                    sub_category_id: sub,
                    brand_id: brand,
                    product_id: product
                });

                fetch(`{{ route('report.sale.fetch') }}?${params}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        document.getElementById('loader').style.display = 'none';

                        // Populate dropdowns once
                        if (res.customers && document.getElementById('filterCustomer').options.length === 1) {
                            res.customers.forEach(c => {
                                const o = new Option(c.customer_name, c.id);
                                document.getElementById('filterCustomer').add(o);
                            });
                        }
                        if (res.warehouses && document.getElementById('filterWarehouse').options.length === 1) {
                            res.warehouses.forEach(w => {
                                const o = new Option(w.warehouse_name, w.id);
                                document.getElementById('filterWarehouse').add(o);
                            });
                        }

                        lastData = res.data || [];
                        if (!lastData.length) {
                            document.getElementById('emptyState').style.display = '';
                            document.getElementById('emptyState').querySelector('p').textContent =
                                'No sale records found for the selected filters.';
                            return;
                        }

                        document.getElementById('tableWrap').style.display = '';
                        renderTable(lastData, {
                            net: res.grand_net,
                            paid: res.grand_paid,
                            due: res.grand_due,
                            returned: res.grand_returned,
                        });
                    })
                    .catch(err => {
                        document.getElementById('loader').style.display = 'none';
                        console.error(err);
                        alert('Error fetching sale report. Please try again.');
                    });
            }

            document.getElementById('btnSearch').addEventListener('click', fetchReport);

            document.getElementById('btnReset').addEventListener('click', function() {
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                document.getElementById('filterCustomer').value = 'all';
                document.getElementById('filterWarehouse').value = 'all';
                document.getElementById('filterStatus').value = 'all';
                document.getElementById('filterCategory').value = 'all';
                document.getElementById('filterSubCategory').value = 'all';
                $('#filterBrand').val('all');
                updateFilters();
                $('#filterProduct').val('all').trigger('change');

                document.getElementById('tableWrap').style.display = 'none';
                document.getElementById('emptyState').style.display = '';
                document.getElementById('kpiRow').style.display = 'none';
                document.getElementById('emptyState').querySelector('p').innerHTML =
                    'Select a date range and click <strong>Search</strong> to view the sale report.';
                lastData = [];
            });

            // PDF Export using html2canvas as per user template
            document.getElementById('btnExportPdf').addEventListener('click', async function() {
                if (!lastData.length) {
                    alert('No data to export. Run a search first.');
                    return;
                }

                const { jsPDF } = window.jspdf;
                const el = document.getElementById('reportResult');
                
                // Show standard processing feedback
                this.textContent = 'Generating...';
                this.disabled = true;

                try {
                    const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                    
                    const w = pdf.internal.pageSize.getWidth();
                    const h = (canvas.height * w) / canvas.width;
                    
                    pdf.addImage(imgData, 'PNG', 0, 0, w, h);
                    pdf.save(`Detailed_Sale_Report_${new Date().getTime()}.pdf`);
                } catch (e) {
                    console.error(e);
                    alert('PDF generation failed.');
                } finally {
                    this.textContent = 'Export PDF';
                    this.disabled = false;
                }
            });

            // Use LOCAL date (not UTC) to avoid timezone cutoff at night
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            const firstOfMonth = today.slice(0, 7) + '-01';
            document.getElementById('start_date').value = firstOfMonth;
            document.getElementById('end_date').value = today;
            fetchReport();
        })();

        function printReport() {
            document.querySelectorAll('.items-row').forEach(r => r.classList.add('open'));
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const cust = document.getElementById('filterCustomer');
            const cName = cust ? cust.options[cust.selectedIndex]?.text : 'All Customers';
            const sub = document.getElementById('printSubtitle');
            if (sub) sub.textContent =
                `Period: ${start} to ${end}  |  Customer: ${cName}  |  Printed: {{ now()->format('d M Y H:i') }}`;
            window.print();
            setTimeout(() => document.querySelectorAll('.items-row').forEach(r => r.classList.remove('open')), 1000);
        }
    </script>
@endsection
