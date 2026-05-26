@extends('admin_panel.layout.app')

@section('content')
    <style>
        /* ── Purchase Report Styles ────────────────────────── */
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
            border-color: #6366f1;
            background: #fff;
        }

        .btn-search {
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 8px 20px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-search:hover {
            background: #4f46e5;
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

        .btn-csv {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 8px 16px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-csv:hover {
            background: #059669;
        }

        .btn-print {
            background: #0ea5e9;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 8px 16px;
            font-size: .88rem;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-print:hover {
            background: #0284c7;
        }

        /* KPI Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .kpi-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .kpi-card .kpi-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .kpi-card .kpi-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
        }

        .kpi-card.kpi-net .kpi-value {
            color: #6366f1;
        }

        .kpi-card.kpi-paid .kpi-value {
            color: #10b981;
        }

        .kpi-card.kpi-due .kpi-value {
            color: #ef4444;
        }

        .kpi-card.kpi-ret .kpi-value {
            color: #f59e0b;
        }

        /* ── Detailed Report Styles (Matching Sale Report) ──────────────── */
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

        table.rpt-detailed {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        table.rpt-detailed thead th {
            background: #f1f5f9;
            color: #000;
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Group Header */
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
                <h4 style="margin:0; font-size:1.2rem; color:#1e293b;">📦 Purchase Report - Detailed</h4>
                <p style="margin:4px 0 0; font-size:.85rem; color:#64748b;">Invoice-wise detailed purchase analysis</p>
            </div>
            <div style="display:flex; gap:10px;">
                <button class="btn-pdf" id="btnExportPdf" style="background:#ef4444; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">Export PDF</button>
                <button class="btn-csv" id="btnExportCsv" style="background:#10b981; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">⬇ Export CSV</button>
                <button class="btn-print" onclick="window.print()" style="background:#6366f1; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;">🖨 Print</button>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-card">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="start_date">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="end_date">
                </div>
                <div class="filter-group">
                    <label>Vendor</label>
                    <select id="filterVendor">
                        <option value="all">All Vendors</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Warehouse</label>
                    <select id="filterWarehouse">
                        <option value="all">All Warehouses</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus">
                        <option value="all">All Status</option>
                        <option value="approved">Approved</option>
                        <option value="draft">Draft</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
            </div>
            <div class="filter-row mt-3">
                <div class="filter-group" style="min-width: 200px;">
                    <label>Category</label>
                    <select id="filterCategory">
                        <option value="all">All Category</option>
                        @foreach(App\Models\Category::orderBy('name')->get() as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="min-width: 200px;">
                    <label>Sub-Category</label>
                    <select id="filterSubCategory">
                        <option value="all">All Sub-category</option>
                        @foreach(App\Models\Subcategory::orderBy('name')->get() as $sc)
                            <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="min-width: 200px;">
                    <label>Company (Brand)</label>
                    <select id="filterBrand">
                        <option value="all">All Companies</option>
                        @foreach(App\Models\Brand::orderBy('name')->get() as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 2; min-width: 300px;">
                    <label>Product</label>
                    <select id="filterProduct" class="select2-product">
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
                <div class="filter-group" style="flex-direction:row;gap:8px;align-items:flex-end;">
                    <button class="btn-search" id="btnSearch">🔍 Search</button>
                    <button class="btn-reset" id="btnReset">↺ Reset</button>
                </div>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="kpi-row" id="kpiRow" style="display:none;">
            <div class="kpi-card">
                <div class="kpi-label">Total Invoices</div>
                <div class="kpi-value" id="kpiCount">0</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Subtotal</div>
                <div class="kpi-value" id="kpiSubtotal">0</div>
            </div>
            <div class="kpi-card kpi-net">
                <div class="kpi-label">Net Amount</div>
                <div class="kpi-value" id="kpiNet">0</div>
            </div>
            <div class="kpi-card kpi-paid">
                <div class="kpi-label">Paid</div>
                <div class="kpi-value" id="kpiPaid">0</div>
            </div>
            <div class="kpi-card kpi-due">
                <div class="kpi-label">Due / Outstanding</div>
                <div class="kpi-value" id="kpiDue">0</div>
            </div>
            <div class="kpi-card kpi-ret">
                <div class="kpi-label">Total Returned</div>
                <div class="kpi-value" id="kpiReturned">0</div>
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
                <span>Purchase Report - Invoice-Wise</span>
            </div>

            <div class="meta-strip">
                <span class="ws-title">PURCHASE DETAILED REPORT</span>
                <span id="rangeLabel" class="date-range">...</span>
            </div>

            <div class="loader-wrap" id="loader" style="display:none; text-align:center; padding:40px;">
                <div class="spinner" style="width:36px; height:36px; border:4px solid #e0e7ff; border-top-color:#6366f1; border-radius:50%; animation:spin .7s linear infinite; display:inline-block;"></div>
                <p style="margin-top:10px;color:#94a3b8;font-size:.88rem;">Generating dynamic report…</p>
            </div>

            <div class="empty-state" id="emptyState" style="text-align:center; padding:60px; color:#94a3b8;">
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
                    approved: '<span class="badge badge-approved">Approved</span>',
                    draft: '<span class="badge badge-draft">Draft</span>',
                    returned: '<span class="badge badge-returned">Returned</span>',
                };
                return map[s] || `<span class="badge">${s}</span>`;
            }

            function renderTable(data, totals) {
                // KPI
                document.getElementById('kpiCount').textContent = data.length;
                document.getElementById('kpiSubtotal').textContent = 'Rs ' + fmt(totals.subtotal);
                document.getElementById('kpiNet').textContent = 'Rs ' + fmt(totals.net);
                document.getElementById('kpiPaid').textContent = 'Rs ' + fmt(totals.paid);
                document.getElementById('kpiDue').textContent = 'Rs ' + fmt(totals.due);
                document.getElementById('kpiReturned').textContent = 'Rs ' + fmt(totals.returned);
                document.getElementById('kpiRow').style.display = '';

                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                document.getElementById('rangeLabel').textContent = `${start} -TO- ${end}`;

                let html = `
                    `;

                let grandTotalQty = 0;
                let grandTotalFree = 0;
                let grandTotalNet = 0;

                data.forEach((r) => {
                    const dateArr = (r.purchase_date || '').split('T')[0].split('-');
                    const formattedDate = dateArr.length === 3 ? `${dateArr[2]}/${dateArr[1]}/${dateArr[0]}` : r.purchase_date;

                    // Invoice Header row
                    let displayInvoice = r.invoice_no;
                    if (r.status === 'draft' && r.po_ref && r.po_ref !== '000000') {
                        displayInvoice = `${r.po_ref} (Draft)`;
                    }

                    html += `
                        <tr class="inv-bar-row">
                            <td colspan="5">
                                <strong>${r.status === 'draft' ? 'PURCHASE ORDER' : 'PURCHASE'}</strong> &nbsp;&nbsp;
                                <strong>Ref # :</strong> ${displayInvoice} &nbsp;&nbsp;
                                ${r.status !== 'draft' ? `<strong>PO Ref :</strong> ${r.po_ref} &nbsp;&nbsp;` : ''}
                                <strong>Date :</strong> ${formattedDate}
                            </td>
                            <td colspan="7">
                                <strong>Vendor :</strong> ${r.vendor_name}
                            </td>
                        </tr>`;

                    let invQty = 0;
                    let invFree = 0;
                    let invNet = 0;

                        let itemIdx = 0;
                        (r.items || []).forEach(it => {
                            const qty = parseFloat(it.qty || 0);
                            const free = parseFloat(it.free_qty || 0);
                            const total = parseFloat(it.line_total || 0);
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
                                    <td class="text-right">${fmt(it.item_discount)}</td>
                                    <td class="text-right">
                                        <div>${fmt(it.gst_amount)}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.gst_percent}%)</div>
                                    </td>
                                    <td class="text-right">
                                        <div>${fmt(total * (it.it_percent / 100))}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.it_percent}%)</div>
                                    </td>
                                    <td class="text-right">
                                        <div>${fmt(total * (it.adv_tax_percent / 100))}</div>
                                        <div style="font-size:9px; color:#64748b;">(${it.adv_tax_percent}%)</div>
                                    </td>
                                    <td class="text-right" style="font-weight:600;">${fmt(total)}</td>
                                </tr>`;
                        });

                    // Subtotal for this invoice
                    html += `
                        <tr class="inv-sum-row">
                            <td colspan="5" class="text-right">
                                <span class="text-red">TOTAL FOR :</span> PURCHASE ${r.invoice_no}
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

                // 1. Filter Sub-Categories based on Category
                var validSubs = new Set();
                $('#filterSubCategory option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    var matchCat = (catId === 'all' || $opt.attr('data-cat') == catId);
                    if (matchCat) {
                        $opt.show().prop('disabled', false);
                        validSubs.add($opt.val());
                    } else {
                        $opt.hide().prop('disabled', true);
                    }
                });
                if (subId !== 'all' && !validSubs.has(subId)) {
                    $('#filterSubCategory').val('all').trigger('change.select2');
                    subId = 'all';
                }

                // 2. Build map of valid Brands and Products based on Cat/Sub selection
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

                // 3. Filter Brands based on validBrands set
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

                // 4. Filter Products based on validProds set
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

                // Event bindings for dynamic filters
                $('#filterCategory').on('change', function() {
                    updateFilters();
                });
                $('#filterSubCategory, #filterBrand').on('change', function() {
                    updateFilters();
                });

                updateFilters(); // Initial sync
            });

            function fetchReport() {
                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                const vendor = document.getElementById('filterVendor').value;
                const wh = document.getElementById('filterWarehouse').value;
                const status = document.getElementById('filterStatus').value;
                const cat = document.getElementById('filterCategory').value;
                const sub = document.getElementById('filterSubCategory').value;
                const brand = document.getElementById('filterBrand').value;
                const product = document.getElementById('filterProduct').value;

                document.getElementById('loader').style.display = '';
                document.getElementById('tableWrap').style.display = 'none';
                document.getElementById('emptyState').style.display = 'none';
                document.getElementById('kpiRow').style.display = 'none';

                fetch("{{ route('report.purchase.fetch') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            start_date: start,
                            end_date: end,
                            vendor_id: vendor,
                            warehouse_id: wh,
                            status: status,
                            category_id: cat,
                            sub_category_id: sub,
                            brand_id: brand,
                            product_id: product
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        document.getElementById('loader').style.display = 'none';

                        if (res.vendors) {
                            const vSel = document.getElementById('filterVendor');
                            const vCurrent = vSel.value;
                            while (vSel.options.length > 1) vSel.remove(1);
                            res.vendors.forEach(v => {
                                const o = new Option(v.name, v.id);
                                vSel.add(o);
                            });
                            vSel.value = vCurrent;
                        }
                        if (res.warehouses) {
                            const wSel = document.getElementById('filterWarehouse');
                            const wCurrent = wSel.value;
                            while (wSel.options.length > 1) wSel.remove(1);
                            res.warehouses.forEach(w => {
                                const o = new Option(w.warehouse_name, w.id);
                                wSel.add(o);
                            });
                            wSel.value = wCurrent;
                        }

                        lastData = res.data || [];
                        if (!lastData.length) {
                            document.getElementById('emptyState').style.display = '';
                            document.getElementById('emptyState').querySelector('p').textContent =
                                'No purchase records found for the selected filters.';
                            return;
                        }

                        document.getElementById('tableWrap').style.display = '';
                        renderTable(lastData, {
                            subtotal: res.grand_subtotal,
                            net: res.grand_net,
                            paid: res.grand_paid,
                            due: res.grand_due,
                            returned: res.grand_returned,
                        });
                    })
                    .catch(() => {
                        document.getElementById('loader').style.display = 'none';
                        alert('Error fetching purchase report. Please try again.');
                    });
            }

            document.getElementById('btnSearch').addEventListener('click', fetchReport);

            document.getElementById('btnReset').addEventListener('click', function() {
                document.getElementById('start_date').value = '';
                document.getElementById('end_date').value = '';
                document.getElementById('filterVendor').value = 'all';
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
                    'Select a date range and click <strong>Search</strong> to view the purchase report.';
                lastData = [];
            });

            // PDF Export logic matching Sale Report
            document.getElementById('btnExportPdf').addEventListener('click', async function() {
                if (!lastData.length) {
                    alert('No data to export. Run a search first.');
                    return;
                }

                const { jsPDF } = window.jspdf;
                const el = document.getElementById('reportResult');
                
                this.textContent = 'Generating...';
                this.disabled = true;

                try {
                    const canvas = await html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
                    
                    const w = pdf.internal.pageSize.getWidth();
                    const h = (canvas.height * w) / canvas.width;
                    
                    pdf.addImage(imgData, 'PNG', 0, 0, w, h);
                    pdf.save(`Detailed_Purchase_Report_${new Date().getTime()}.pdf`);
                } catch (e) {
                    console.error(e);
                    alert('PDF generation failed.');
                } finally {
                    this.textContent = 'Export PDF';
                    this.disabled = false;
                }
            });

            // CSV Export
            document.getElementById('btnExportCsv').addEventListener('click', function() {
                if (!lastData.length) {
                    alert('No data to export. Run a search first.');
                    return;
                }
                let csv = 'Invoice No,Date,Vendor,Warehouse,Subtotal,Discount,Extra Cost,Net Amount,Paid,Due,Returned,Status,Item Code,Item Name,Packing,Qty (Pcs),Free (Pcs),Price,Item Discount,Line Total\n';
                lastData.forEach(r => {
                    if (r.items.length === 0) {
                        csv += `"${r.invoice_no}","${r.purchase_date}","${r.vendor_name}","${r.warehouse_name}",${r.subtotal},${r.discount},${r.extra_cost},${r.net_amount},${r.paid_amount},${r.due_amount},${r.total_returned},"${r.status}","","","","","","","",""\n`;
                    } else {
                        r.items.forEach((it, ii) => {
                            csv += `"${r.invoice_no}","${r.purchase_date}","${r.vendor_name}","${r.warehouse_name}",${ii > 0 ? ',,,,,,,' : r.subtotal+','+r.discount+','+r.extra_cost+','+r.net_amount+','+r.paid_amount+','+r.due_amount+','+r.total_returned},"${ii > 0 ? '' : r.status}","${it.item_code}","${it.item_name}","${it.uom_name}",${it.qty},${it.free_qty},${it.price},${it.item_discount},${it.line_total}\n`;
                        });
                    }
                });
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = `purchase_report_${new Date().toISOString().slice(0,10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });

            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            const firstOfMonth = today.slice(0, 7) + '-01';
            document.getElementById('start_date').value = firstOfMonth;
            document.getElementById('end_date').value = today;
            fetchReport();
        })();

        function printReport() {
            // Expand all item detail rows so they show in print
            document.querySelectorAll('.items-row').forEach(r => r.classList.add('open'));

            // Set print subtitle
            const start = document.getElementById('start_date').value;
            const end = document.getElementById('end_date').value;
            const vendor = document.getElementById('filterVendor');
            const vName = vendor.options[vendor.selectedIndex]?.text || 'All Vendors';
            const sub = document.getElementById('printSubtitle');
            if (sub) sub.textContent =
                `Period: ${start} to ${end}  |  Vendor: ${vName}  |  Printed: {{ now()->format('d M Y H:i') }}`;

            window.print();

            // Collapse rows again after print dialog closes
            setTimeout(() => {
                document.querySelectorAll('.items-row').forEach(r => r.classList.remove('open'));
            }, 1000);
        }
    </script>
@endsection
