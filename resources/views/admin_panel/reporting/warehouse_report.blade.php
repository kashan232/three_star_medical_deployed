@extends('admin_panel.layout.app')
@section('content')
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
            --brand: #4f46e5;
            --brand-light: #ede9fe;
            --green: #10b981;
            --green-light: #d1fae5;
            --red: #ef4444;
            --red-light: #fee2e2;
            --blue: #3b82f6;
            --amber: #f59e0b;
        }

        .rpt-page {
            padding: 20px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* Topbar */
        .rpt-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .rpt-title h4 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rpt-title p {
            margin: 2px 0 0;
            color: var(--muted);
            font-size: .85rem;
        }

        .rpt-actions {
            display: flex;
            gap: 8px;
        }

        .btn-rpt {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: .2s;
        }

        .btn-rpt:hover {
            filter: brightness(.92);
        }

        .btn-print {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-csv {
            background: var(--green);
            color: #fff;
        }

        /* Filter Row Styles */
        .filter-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filter-inputs-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: .62rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .75px;
        }

        .filter-group select,
        .filter-group input {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: .8rem;
            color: #1e293b;
            outline: none;
            background: #f8fafc;
            height: 32px;
            min-height: 32px;
            box-sizing: border-box;
            transition: border-color 0.2s, background-color 0.2s;
            width: 100%;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #0ea5e9;
            background: #fff;
        }

        .filter-buttons-col {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-left: 20px;
            justify-content: center;
            align-self: flex-end;
        }

        .btn-filter-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 6px;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            min-width: 80px;
            height: 32px;
            border: none;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-filter-action:active {
            transform: scale(0.98);
        }

        .btn-filter-search {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-filter-search:hover {
            background: #0284c7;
        }

        .btn-filter-reset {
            background: #94a3b8;
            color: #fff;
        }

        .btn-filter-reset:hover {
            background: #64748b;
        }

        .btn-csv-action {
            background: var(--green);
            color: #fff;
        }

        .btn-csv-action:hover {
            background: #059669;
        }

        .btn-print-action {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-print-action:hover {
            background: #0284c7;
        }

        /* Select2 Theme Overrides */
        .select2-container--default .select2-selection--single {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            height: 32px !important;
            background-color: #f8fafc !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: .8rem !important;
            padding-left: 8px !important;
            padding-right: 20px !important;
            line-height: 30px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px !important;
            right: 6px !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--open .select2-selection--single {
            border-color: #0ea5e9 !important;
            background-color: #fff !important;
        }


        /* KPI Strip */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .kpi-box {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            position: relative;
            overflow: hidden;
        }

        .kpi-box::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            border-radius: 4px 0 0 4px;
        }

        .kpi-blue::before {
            background: var(--brand);
        }

        .kpi-green::before {
            background: var(--green);
        }

        .kpi-red::before {
            background: var(--amber);
        }

        .kpi-label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .kpi-val {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ink);
        }

        /* Table */
        .table-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: .85rem;
        }

        thead th {
            background: #f1f5f9;
            padding: 12px 16px;
            text-align: left;
            font-size: .75rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        thead th.tr {
            text-align: right;
        }

        tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        tbody td.tr {
            text-align: right;
        }

        .c-green {
            color: var(--green);
            font-weight: 600;
        }

        .c-red {
            color: var(--red);
            font-weight: 600;
        }

        .c-blue {
            color: var(--blue);
            font-weight: 600;
        }

        .c-brand {
            color: var(--brand);
            font-weight: 600;
        }

        .c-amber {
            color: var(--amber);
            font-weight: 600;
        }

        .item-code {
            font-size: .75rem;
            color: var(--muted);
        }

        .item-name {
            font-weight: 600;
            color: var(--ink);
        }

        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            display: none;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--brand);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Print */
        @media print {

            .rpt-topbar,
            .filter-card {
                display: none !important;
            }

            .rpt-page,
            .table-card {
                border: none !important;
                margin: 0 !important;
                width: 100% !important;
            }
        }
    </style>

    <div class="rpt-page">
        <div class="print-header" style="display:none; margin-bottom:16px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">🏠 Warehouse Activity Report</h2>
            <p style="margin:4px 0 0;font-size:12px;color:#555;">Printed: {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <div class="rpt-topbar">
            <div class="rpt-title">
                <h4>
                    <svg style="width:24px;height:24px;color:#4f46e5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Warehouse Report
                </h4>
                <p>Track stock levels, purchases, and sales for a specific warehouse.</p>
            </div>
        </div>

        <div class="filter-card">
            <div class="filter-inputs-container">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 150px; max-width: 220px;">
                        <label>Location (Shop / Warehouse)</label>
                        <select id="warehouseId" class="select2">
                            <option value="all">All Locations</option>
                            <optgroup label="🏪 Shops (Retail)">
                                @foreach ($shops as $sh)
                                    <option value="{{ $sh->id }}">{{ $sh->warehouse_name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="🏭 Warehouses (Storage)">
                                @foreach ($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 110px; max-width: 130px;">
                        <label>Date From</label>
                        <input type="date" id="dateFrom">
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 110px; max-width: 130px;">
                        <label>Date To</label>
                        <input type="date" id="dateTo">
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 120px; max-width: 180px;">
                        <label>Category</label>
                        <select id="filterCategory" class="select2">
                            <option value="all">All Category</option>
                            @foreach(App\Models\Category::orderBy('name')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 120px; max-width: 180px;">
                        <label>Sub-Category</label>
                        <select id="filterSubCategory" class="select2">
                            <option value="all">All Sub-category</option>
                            @foreach(App\Models\Subcategory::orderBy('name')->get() as $sc)
                                <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 120px; max-width: 180px;">
                        <label>Brand</label>
                        <select id="filterBrand" class="select2">
                            <option value="all">All Brands</option>
                            @foreach(App\Models\Brand::orderBy('name')->get() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px; max-width: 250px;">
                        <label>Product</label>
                        <select id="filterProduct" class="select2">
                            <option value="all">All Products</option>
                            @foreach(App\Models\Product::orderBy('item_name')->get() as $p)
                                <option value="{{ $p->id }}" 
                                    data-cat="{{ $p->category_id }}" 
                                    data-sub="{{ $p->sub_category_id }}" 
                                    data-brand="{{ $p->brand_id }}">
                                    {{ $p->item_code }} — {{ $p->item_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex-direction: row; gap: 6px; align-items: flex-end; min-width: 360px; margin-left: 10px; flex: 1.5;">
                        <button class="btn-filter-action btn-filter-search" onclick="loadReport()" style="flex: 1;">🔍 Search</button>
                        <button class="btn-filter-action btn-filter-reset" onclick="resetFilters()" style="flex: 1;">↺ Reset</button>
                        <button class="btn-filter-action btn-csv-action" onclick="exportData()" style="flex: 1;">⬇ CSV</button>
                        <button class="btn-filter-action btn-print-action" onclick="window.print()" style="flex: 1;">🖨 Print</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- KPI Strip -->
        <div class="kpi-row" style="display: none;" id="kpiStrip">
            <div class="kpi-box kpi-blue">
                <div class="kpi-label">Current Stock Value</div>
                <div class="kpi-val">Rs <span id="kpiStockVal">0</span></div>
            </div>
            <div class="kpi-box kpi-green">
                <div class="kpi-label">Sales Given Period</div>
                <div class="kpi-val">Rs <span id="kpiSaleVal">0</span></div>
            </div>
            <div class="kpi-box kpi-red">
                <div class="kpi-label">Purchases Given Period</div>
                <div class="kpi-val">Rs <span id="kpiPurchVal">0</span></div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card" style="position:relative;">
            <div class="loader-overlay" id="tblLoader">
                <div class="spinner"></div>
            </div>
            <div style="overflow-x:auto;">
                <table id="rptTable">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="tr">Current Stock<br><small style="font-weight:400;opacity:.7">(UOM Breakdown)</small></th>
                            <th>Packing / UOM</th>
                            <th class="tr">Stock<br>Value Rs</th>
                            <th class="tr">Purchased<br>In Period</th>
                            <th class="tr">Sold<br>In Period</th>
                            <th class="tr">Transferred<br>In / Out</th>
                        </tr>
                    </thead>
                    <tbody id="rptBody">
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">Loading report data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <link  href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let reportData = [];

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

            $('#filterSubCategory, #filterBrand, #filterProduct').trigger('change.select2');
        }

        document.addEventListener('DOMContentLoaded', function() {
            $('.select2').select2({ width: '100%' });
            
            $('#filterCategory').on('change', updateFilters);
            $('#filterSubCategory, #filterBrand').on('change', updateFilters);
            updateFilters(); // Initial sync

            loadReport();
        });

        function resetFilters() {
            $('#warehouseId').val('all').trigger('change.select2');
            document.getElementById('dateFrom').value = '';
            document.getElementById('dateTo').value = '';
            $('#filterCategory').val('all').trigger('change.select2');
            $('#filterSubCategory').val('all').trigger('change.select2');
            $('#filterBrand').val('all').trigger('change.select2');
            $('#filterProduct').val('all').trigger('change.select2');
            updateFilters();
            loadReport();
        }

        function formatCurrency(num) {
            return Number(num || 0).toLocaleString('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function loadReport() {
            const wh    = document.getElementById('warehouseId').value;
            const from  = document.getElementById('dateFrom').value;
            const to    = document.getElementById('dateTo').value;
            const cat   = $('#filterCategory').val();
            const sub   = $('#filterSubCategory').val();
            const brand = $('#filterBrand').val();
            const prod  = $('#filterProduct').val();

            document.getElementById('tblLoader').style.display = 'flex';
            document.getElementById('rptBody').innerHTML = '';

            fetch("{{ route('report.warehouse.fetch') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    body: JSON.stringify({
                        warehouse_id: wh,
                        start_date: from,
                        end_date: to,
                        category_id: cat,
                        sub_category_id: sub,
                        brand_id: brand,
                        product_id: prod
                    })
                })
                .then(res => res.json())
                .then(res => {
                    document.getElementById('tblLoader').style.display = 'none';

                    if (res.error) {
                        Swal.fire('Error', res.error, 'error');
                        return;
                    }

                    reportData = res.data;
                    const summary = res.summary;

                    // Update KPIs
                    document.getElementById('kpiStrip').style.display = 'grid';
                    document.getElementById('kpiStockVal').textContent = formatCurrency(summary.total_stock_value);
                    document.getElementById('kpiSaleVal').textContent = formatCurrency(summary.period_sales_value);
                    document.getElementById('kpiPurchVal').textContent = formatCurrency(summary.period_purchases_value);

                    // Populate Table
                    let html = '';
                    if (reportData.length === 0) {
                        html =
                            `<tr><td colspan="7" class="text-center text-muted py-5">No activity or stock found for this warehouse in the selected period.</td></tr>`;
                    } else {
                        reportData.forEach(r => {
                            // Use UOM-aware display if available
                            let stockDisplay;
                            if (r.current_stock_display) {
                                const col = r.current_stock > 0 ? 'var(--brand)' : 'var(--muted)';
                                stockDisplay = `<span style="color:${col};font-weight:600;">${r.current_stock_display}</span>`;
                            } else if (r.current_stock > 0) {
                                stockDisplay = `<span class="c-brand">${r.current_stock} pcs</span>`;
                            } else {
                                stockDisplay = `<span class="text-muted">0 pcs</span>`;
                            }

                            // Packing / UOM pills
                            let packingHtml = '-';
                            if (r.packings && r.packings.length > 0) {
                                packingHtml = r.packings.map(p =>
                                    `<span style="display:inline-block;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:5px;padding:2px 7px;font-size:.73rem;font-weight:600;margin:1px;">${p.name} <span style="color:#7c3aed;">(${p.pieces_per_box}pc)</span></span>`
                                ).join('');
                            }

                            let purchDisplay = r.purchased_qty > 0 ?
                                `<span class="c-amber">${r.purchased_qty} pcs<br><small>Rs ${formatCurrency(r.purchased_amount)}</small></span>` :
                                '-';
                            let saleDisplay = r.sold_qty > 0 ?
                                `<span class="c-green">${r.sold_qty} pcs<br><small>Rs ${formatCurrency(r.sold_amount)}</small></span>` :
                                '-';

                            let tIn = r.transferred_in > 0 ?
                                `<span class="c-blue">In: ${r.transferred_in}</span><br>` : '';
                            let tOut = r.transferred_out > 0 ?
                                `<span class="c-red">Out: ${r.transferred_out}</span>` : '';
                            let transferDisplay = (tIn + tOut) || '-';

                            html += `
                            <tr>
                                <td>
                                    <div class="item-code">${r.item_code}</div>
                                    <div class="item-name">${r.item_name}</div>
                                </td>
                                <td class="tr" style="vertical-align:top;">${stockDisplay}</td>
                                <td style="vertical-align:top;">${packingHtml}</td>
                                <td class="tr" style="vertical-align:top; font-weight:700;">Rs ${formatCurrency(r.stock_value)}</td>
                                <td class="tr" style="vertical-align:top;">${purchDisplay}</td>
                                <td class="tr" style="vertical-align:top;">${saleDisplay}</td>
                                <td class="tr" style="vertical-align:top;">${transferDisplay}</td>
                            </tr>
                        `;
                        });
                    }
                    document.getElementById('rptBody').innerHTML = html;
                })
                .catch(err => {
                    document.getElementById('tblLoader').style.display = 'none';
                    console.error(err);
                    Swal.fire('Error', 'Failed to load report.', 'error');
                });
        }

        function exportData() {
            if (reportData.length === 0) {
                Swal.fire('Empty', 'No data to export.', 'info');
                return;
            }

            let csv =
                "Item Code,Item Name,Current Stock (Pcs),Stock Value (Rs),Purchased Qty,Purchased Amount (Rs),Sold Qty,Sold Amount (Rs),Transferred In,Transferred Out\n";

            reportData.forEach(r => {
                csv +=
                    `"${r.item_code}","${r.item_name}","${r.current_stock}","${r.stock_value}","${r.purchased_qty}","${r.purchased_amount}","${r.sold_qty}","${r.sold_amount}","${r.transferred_in}","${r.transferred_out}"\n`;
            });

            const blob = new Blob([csv], {
                type: 'text/csv;charset=utf-8;'
            });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", "warehouse_report.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
@endsection
