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
            --green-lt: #d1fae5;
            --red: #ef4444;
            --red-lt: #fee2e2;
            --amber: #f59e0b;
            --amber-lt: #fef3c7;
            --sky: #0ea5e9;
            --sky-lt: #e0f2fe;
        }

        .led-page {
            padding: 20px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* ── Top bar ─────────────────────────────────────────────── */
        .led-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .led-topbar h4 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .led-topbar p {
            margin: 0;
            color: var(--muted);
            font-size: .85rem;
        }

        .topbar-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn-led {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .83rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: filter .15s;
        }

        .btn-led:hover {
            filter: brightness(.92);
        }

        .btn-gen {
            background: var(--brand);
            color: #fff;
        }

        .btn-print {
            background: var(--sky);
            color: #fff;
        }

        .btn-csv {
            background: var(--green);
            color: #fff;
        }

        .btn-reset-form {
            background: var(--bg);
            color: var(--ink);
            border: 1px solid var(--border);
        }

        /* Filters */
        .filter-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 18px;
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
            margin-top: 14px;
        }

        .btn-filter-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 12px;
            min-width: 100px;
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

        /* ── Loader ──────────────────────────────────────────────── */
        .led-loader {
            display: none;
            text-align: center;
            padding: 50px;
        }

        .spinner {
            width: 38px;
            height: 38px;
            border: 4px solid var(--brand-light);
            border-top-color: var(--brand);
            border-radius: 50%;
            animation: spin .7s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Result area ─────────────────────────────────────────── */
        #ledgerResult {
            display: none;
        }

        /* ── Vendor profile card ───────────────────────────────── */
        .cust-profile {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            border-radius: 12px;
            padding: 22px 26px;
            margin-bottom: 18px;
            color: white;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 16px;
            align-items: center;
        }

        .cust-profile h5 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0 0 4px;
        }

        .cust-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 8px;
        }

        .cust-meta span {
            font-size: .8rem;
            opacity: .85;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .cust-badge {
            background: rgba(255, 255, 255, .18);
            border-radius: 8px;
            padding: 4px 12px;
            font-size: .76rem;
            font-weight: 700;
            letter-spacing: .4px;
            border: 1px solid rgba(255, 255, 255, .25);
        }

        .period-badge {
            background: rgba(255, 255, 255, .15);
            border-radius: 8px;
            padding: 6px 14px;
            font-size: .8rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, .2);
            white-space: nowrap;
            margin-top: 4px;
        }

        /* ── KPI row ─────────────────────────────────────────────── */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 13px;
            margin-bottom: 20px;
        }

        .kpi-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 11px;
            padding: 15px 17px;
            position: relative;
            overflow: hidden;
        }

        .kpi-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .kpi-box.k-blue::before {
            background: var(--brand);
        }

        .kpi-box.k-green::before {
            background: var(--green);
        }

        .kpi-box.k-red::before {
            background: var(--red);
        }

        .kpi-box.k-amber::before {
            background: var(--amber);
        }

        .kpi-box.k-sky::before {
            background: var(--sky);
        }

        .kpi-lbl {
            font-size: .71rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .55px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .kpi-val {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--ink);
        }

        .kpi-sub {
            font-size: .73rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Table card ──────────────────────────────────────────── */
        .tbl-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .tbl-card table {
            width: 100%;
            border-collapse: collapse;
            font-size: .84rem;
        }

        .tbl-card thead tr {
            background: #f1f5f9;
        }

        .tbl-card thead th {
            padding: 11px 14px;
            text-align: left;
            font-size: .72rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .45px;
            white-space: nowrap;
            border-bottom: 2px solid var(--border);
        }

        .tbl-card thead th.tr {
            text-align: right;
        }

        .tbl-card tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: background .1s;
        }

        .tbl-card tbody tr:hover {
            background: #fafaff;
        }

        .tbl-card tbody td {
            padding: 10px 14px;
            vertical-align: middle;
            color: #334155;
        }

        .tbl-card tbody td.tr {
            text-align: right;
        }

        /* Opening / closing special rows */
        .row-opening {
            background: #f0fdf4 !important;
        }

        .row-total {
            background: #f8fafc !important;
            border-top: 2px solid var(--border) !important;
        }

        .row-closing {
            background: #eff6ff !important;
            border-top: 2px solid var(--border) !important;
        }

        .row-opening td,
        .row-total td,
        .row-closing td {
            font-weight: 700 !important;
        }

        /* Transaction type icons */
        .tx-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 9px;
            border-radius: 18px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .tx-sale {
            background: var(--brand-light);
            color: var(--brand);
        }

        .tx-receipt {
            background: var(--green-lt);
            color: #065f46;
        }

        .tx-return {
            background: var(--amber-lt);
            color: #92400e;
        }

        .tx-journal {
            background: var(--sky-lt);
            color: #0369a1;
        }

        /* Balance cell */
        .bal-dr {
            color: #dc2626;
            font-weight: 700;
        }

        .bal-cr {
            color: #16a34a;
            font-weight: 700;
        }

        .bal-zero {
            color: var(--muted);
        }

        /* Amount cols */
        .amt-dr {
            color: #dc2626;
            font-weight: 600;
        }

        .amt-cr {
            color: #16a34a;
            font-weight: 600;
        }

        .amt-nil {
            color: #cbd5e1;
        }

        /* Invoice badge */
        .inv-badge {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 2px 8px;
            font-size: .73rem;
            color: var(--ink);
            font-weight: 600;
            font-family: monospace;
        }

        /* Empty state */
        .empty-ledger {
            text-align: center;
            padding: 60px;
            color: var(--muted);
        }

        .empty-ledger svg {
            width: 52px;
            opacity: .3;
            margin-bottom: 12px;
        }

        /* Bottom bar */
        .bottom-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 14px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .bottom-bar small {
            font-size: .78rem;
            color: var(--muted);
        }

        /* Print: hide all led-page children, show only ledgerResult */
        @media print {
            .led-page>* {
                display: none !important;
            }

            #ledgerResult {
                display: block !important;
            }

            /* Inside ledgerResult: hide all, show only print-header + tbl-card */
            #ledgerResult>* {
                display: none !important;
            }

            #ledgerResult>.print-header,
            #ledgerResult>.tbl-card {
                display: block !important;
            }

            .tbl-card {
                border: none !important;
            }

            .print-header {
                display: block !important;
            }
        }
    </style>

    <div class="led-page">

        {{-- Top Bar --}}
        <div class="led-topbar">
            <div>
                <h4>
                    <svg style="width:22px;height:22px;color:#4f46e5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Vendor Ledger Report
                </h4>
                <p>Detailed statement of account — purchases, payments & running balance</p>
            </div>
            <div class="topbar-actions" id="exportBtns" style="display:none;">
                <button class="btn-filter-action btn-excel-action" id="btnExportExcel">📊 Export Excel</button>
                <button class="btn-filter-action btn-pdf-action" id="btnExportPdf">📄 Export PDF</button>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="filter-card">
            <div class="filter-inputs-container">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 150px; max-width: 350px;">
                        <label>Vendor</label>
                        <select id="sel_vendor" class="select2-product">
                            <option value="">— Select Vendor —</option>
                            @foreach ($vendors as $v)
                                <option value="{{ $v->id }}">{{ $v->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Start Date</label>
                        <input type="date" id="sel_start">
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>End Date</label>
                        <input type="date" id="sel_end">
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Category</label>
                        <select id="sel_category" class="select2-product">
                            <option value="all">All Category</option>
                            @foreach(App\Models\Category::orderBy('name')->get() as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Sub-Category</label>
                        <select id="sel_subcategory" class="select2-product">
                            <option value="all">All Sub-category</option>
                            @foreach(App\Models\Subcategory::orderBy('name')->get() as $sc)
                                <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Company (Brand)</label>
                        <select id="sel_brand" class="select2-product">
                            <option value="all">All Companies</option>
                            @foreach(App\Models\Brand::orderBy('name')->get() as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px; max-width: 350px;">
                        <label>Product</label>
                        <select id="sel_product" class="select2-product">
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
                    <div class="filter-group" style="flex-direction: row; gap: 6px; align-items: flex-end; min-width: 200px; margin-left: 10px;">
                        <button class="btn-filter-action btn-filter-search" id="btnGenerate" style="flex: 1;">🔍 Search</button>
                        <button class="btn-filter-action btn-filter-reset" id="btnResetFilters" style="flex: 1;">↺ Reset</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Loader --}}
        <div class="led-loader" id="ledLoader">
            <div class="spinner"></div>
            <p style="margin-top:10px;color:var(--muted);font-size:.88rem;">Building ledger report…</p>
        </div>

        {{-- Result --}}
        <div id="ledgerResult" style="display:none;">

            {{-- Print Header (only visible when printing) --}}
            <div class="print-header" style="display:none; margin-bottom:16px;">
                <h2 style="margin:0;font-size:18px;font-weight:700;">📄 Vendor Ledger Report</h2>
                <p id="printLedgerSubtitle" style="margin:4px 0 0;font-size:12px;color:#555;">Printed:
                    {{ now()->format('d M Y H:i') }}</p>
            </div>

            {{-- Vendor Profile --}}
            <div class="cust-profile" id="custProfile"></div>

            {{-- KPI Row --}}
            <div class="kpi-row" id="kpiRow"></div>

            {{-- Ledger Table --}}
            <div class="tbl-card">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:110px;">Date</th>
                                <th style="width:120px;">Ref / Document #</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="tr">Debit (Out)</th>
                                <th class="tr">Credit (In)</th>
                                <th class="tr" style="width:150px;">Running Balance</th>
                            </tr>
                        </thead>
                        <tbody id="ledgerBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-bar">
                <small id="genTime"></small>
                <small id="txCount"></small>
            </div>
        </div>

    {{-- Hidden Template Area for "Export All" --}}
    <div id="allReportTemplate" style="display:none;"></div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        (function() {
            const fmt = n => parseFloat(n || 0).toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Auto set dates to current month
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            const firstOfMonth = today.slice(0, 7) + '-01';
            document.getElementById('sel_start').value = firstOfMonth;
            document.getElementById('sel_end').value = today;

            function txBadge(type) {
                const map = {
                    purchase: '<span class="tx-type tx-sale">📦 Purchase</span>',
                    sale: '<span class="tx-type tx-sale">💰 Sale</span>',
                    payment: '<span class="tx-type tx-receipt">💸 Payment</span>',
                    receipt: '<span class="tx-type tx-receipt">✔ Receipt</span>',
                    return: '<span class="tx-type tx-return">↩ Return</span>',
                    journal: '<span class="tx-type tx-journal">📖 Journal</span>',
                };
                return map[type] || map.journal;
            }

            function balClass(b) {
                if (Math.abs(b) < 0.01) return 'bal-zero';
                return b > 0 ? 'bal-cr' : 'bal-dr';
            }

            let lastRes = null;

            // ── Dynamic Dropdown Logic ──────────────────────────────────────────
            function updateFilters() {
                var catId   = $('#sel_category').val();
                var subId   = $('#sel_subcategory').val();
                var brandId = $('#sel_brand').val();

                // 1. Filter Sub-Categories
                var validSubs = new Set();
                $('#sel_subcategory option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    var matchCat = (catId === 'all' || $opt.attr('data-cat') == catId);
                    if (matchCat) { $opt.show().prop('disabled', false); validSubs.add($opt.val()); }
                    else { $opt.hide().prop('disabled', true); }
                });
                if (subId !== 'all' && !validSubs.has(subId)) {
                    $('#sel_subcategory').val('all').trigger('change');
                    subId = 'all';
                }

                // 2. Build valid maps
                var validBrands = new Set();
                var validProds  = new Set();
                $('#sel_product option').each(function() {
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

                // 3. Filter Brands
                $('#sel_brand option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validBrands.has($opt.val())) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });
                if (brandId !== 'all' && !validBrands.has(brandId)) {
                    $('#sel_brand').val('all').trigger('change');
                    brandId = 'all';
                }

                // 4. Filter Products
                $('#sel_product option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validProds.has($opt.val())) $opt.show().prop('disabled', false);
                    else $opt.hide().prop('disabled', true);
                });

                $('.select2-product').trigger('change.select2');
            }

            $(document).ready(function() {
                $('.select2-product').select2({ width: '100%' });

                $('#sel_category').on('change', updateFilters);
                $('#sel_subcategory, #sel_brand').on('change', updateFilters);
                updateFilters(); // Initial sync
            });

            document.getElementById('btnResetFilters').addEventListener('click', function() {
                $('#sel_vendor').val('').trigger('change.select2');
                $('#sel_category').val('all').trigger('change.select2');
                $('#sel_subcategory').val('all').trigger('change.select2');
                $('#sel_brand').val('all').trigger('change.select2');
                $('#sel_product').val('all').trigger('change.select2');
                updateFilters();
                document.getElementById('ledgerResult').style.display = 'none';
                document.getElementById('exportBtns').style.display = 'none';
            });

            document.getElementById('btnGenerate').addEventListener('click', function() {
                const vid   = document.getElementById('sel_vendor').value;
                const start = document.getElementById('sel_start').value;
                const end   = document.getElementById('sel_end').value;
                const cat   = document.getElementById('sel_category').value;
                const sub   = document.getElementById('sel_subcategory').value;
                const brand = document.getElementById('sel_brand').value;
                const prod  = document.getElementById('sel_product').value;

                if (!vid || !start || !end) {
                    alert('Please select a vendor and date range.');
                    return;
                }

                document.getElementById('ledLoader').style.display = 'block';
                document.getElementById('ledgerResult').style.display = 'none';
                document.getElementById('exportBtns').style.display = 'none';

                const params = new URLSearchParams({
                    vendor_id: vid,
                    start_date: start,
                    end_date: end,
                    category_id: cat,
                    sub_category_id: sub,
                    brand_id: brand,
                    product_id: prod
                });

                fetch(`{{ route('report.vendor.ledger.fetch') }}?${params}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        document.getElementById('ledLoader').style.display = 'none';
                        if (res.error) {
                            alert(res.error);
                            return;
                        }

                        lastRes = res;
                        lastRes.startDate = start;
                        lastRes.endDate = end;
                        renderLedger(res, start, end);

                        document.getElementById('ledgerResult').style.display = 'block';
                        document.getElementById('exportBtns').style.display = 'flex';
                    })
                    .catch(err => {
                        document.getElementById('ledLoader').style.display = 'none';
                        console.error(err);
                        alert('Failed to fetch ledger. Please try again.');
                    });
            });

            function renderLedger(res, start, end) {
                const v = res.vendor;
                const ob = parseFloat(res.opening_balance || 0);
                const cb = parseFloat(res.closing_balance || 0);
                const txList = res.transactions || [];

                // ── Vendor Profile ──────────────────────────────────────────
                const twinBadge = v.has_twin_customer
                    ? `<span class="cust-badge" style="background:rgba(79,70,229,.18);border-color:rgba(79,70,229,.3);" title="This vendor is also a customer — sale transactions are included">🔗 Also a Customer: ${v.twin_customer_name || ''}</span>`
                    : '';
                const formatDate = (dateStr) => {
                    if (!dateStr) return '';
                    if (dateStr.includes('-')) {
                        const parts = dateStr.split('-');
                        if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
                    }
                    return dateStr;
                };
                document.getElementById('custProfile').innerHTML = `
            <div>
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                    <span style="font-size:1.4rem;font-weight:900;">${v.name}</span>
                    <span class="cust-badge">VENDOR</span>
                    ${twinBadge}
                </div>
                <div class="cust-meta">
                    <span>📱 ${v.mobile}</span>
                    <span>📍 ${v.address}</span>
                    <span>💳 Opening Bal: Rs ${fmt(v.opening_balance)}</span>
                    ${v.has_twin_customer ? '<span style="opacity:.8;font-size:.75rem;">⚠️ Net balance = Purchase payable − Sales receivable</span>' : ''}
                </div>
            </div>
            <div style="text-align:right;">
                <div class="period-badge">📅 ${formatDate(start)} → ${formatDate(end)}</div>
            </div>
        `;

                // ── KPI Cards ─────────────────────────────────────────────────
                const netBal = cb;
                const balLabel = netBal > 0.01 ? 'Net Payable (Cr)' : (netBal < -0.01 ? 'Advance Paid (Dr)' : 'Settled ✔');
                const balKlass = netBal > 0.01 ? 'k-red' : 'k-green';
                const crLabel = v.has_twin_customer ? 'Total Cr (Purchases + Sale Ret.)' : 'Purchases (Cr)';
                const drLabel = v.has_twin_customer ? 'Total Dr (Payments + Sales)' : 'Payments (Dr)';

                document.getElementById('kpiRow').innerHTML = `
            <div class="kpi-box k-blue">
                <div class="kpi-lbl">Opening Balance</div>
                <div class="kpi-val">Rs ${fmt(Math.abs(ob))}</div>
                <div class="kpi-sub">${ob >= 0 ? 'Payable' : 'Advance'}</div>
            </div>
            <div class="kpi-box k-sky">
                <div class="kpi-lbl">${crLabel}</div>
                <div class="kpi-val" style="color:var(--red)">Rs ${fmt(res.total_credit)}</div>
                <div class="kpi-sub">Credit entries</div>
            </div>
            <div class="kpi-box k-green">
                <div class="kpi-lbl">${drLabel}</div>
                <div class="kpi-val" style="color:var(--green)">Rs ${fmt(res.total_debit)}</div>
                <div class="kpi-sub">Debit entries</div>
            </div>
            <div class="kpi-box k-amber">
                <div class="kpi-lbl">Transactions</div>
                <div class="kpi-val">${txList.length}</div>
                <div class="kpi-sub">In period</div>
            </div>
            <div class="kpi-box ${balKlass}">
                <div class="kpi-lbl">Closing Balance</div>
                <div class="kpi-val" style="color:${netBal > 0.01 ? 'var(--red)' : 'var(--green)'}">Rs ${fmt(Math.abs(cb))}</div>
                <div class="kpi-sub">${balLabel}</div>
            </div>
        `;

                // ── Ledger Table ──────────────────────────────────────────────
                let html = '';

                // Opening row
                const obClass = ob >= 0 ? 'bal-cr' : (ob < 0 ? 'bal-dr' : 'bal-zero');
                const obLabel = ob >= 0 ? 'Cr' : 'Dr';
                html += `
            <tr class="row-opening">
                <td>—</td>
                <td>—</td>
                <td><span class="tx-type tx-journal">B/F</span></td>
                <td>Opening Balance Brought Forward</td>
                <td class="tr amt-nil">—</td>
                <td class="tr amt-nil">—</td>
                <td class="tr ${obClass}">
                    Rs ${fmt(Math.abs(ob))}
                    <small style="font-size:.7em;opacity:.7">${obLabel}</small>
                </td>
            </tr>`;

                if (txList.length === 0) {
                    html += `<tr><td colspan="7">
                <div class="empty-ledger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p>No transactions found for this period.</p>
                </div>
            </td></tr>`;
                } else {
                    txList.forEach((t, i) => {
                        const dr = parseFloat(t.debit || 0);
                        const cr = parseFloat(t.credit || 0);
                        const bal = parseFloat(t.balance || 0);
                        const bc = balClass(bal);
                        const bl = bal > 0.01 ? 'Cr' : (bal < -0.01 ? 'Dr' : '—');

                        html += `
                    <tr>
                        <td style="color:var(--muted);font-size:.82rem;">${formatDate((t.date||'').split(' ')[0])}</td>
                        <td>
                            ${t.invoice && t.invoice !== '-'
                                ? `<span class="inv-badge">${t.invoice}</span>`
                                : `<span style="color:var(--muted)">—</span>`}
                        </td>
                        <td>${txBadge(t.type)}</td>
                        <td style="max-width:280px;font-size:.82rem;">${t.description}</td>
                        <td class="tr ${dr > 0 ? 'amt-dr' : 'amt-nil'}">${dr > 0 ? 'Rs ' + fmt(dr) : '—'}</td>
                        <td class="tr ${cr > 0 ? 'amt-cr' : 'amt-nil'}">${cr > 0 ? 'Rs ' + fmt(cr) : '—'}</td>
                        <td class="tr ${bc}">
                            Rs ${fmt(Math.abs(bal))}
                            <small style="font-size:.7em;opacity:.7">${bl}</small>
                        </td>
                    </tr>`;
                    });
                }

                // Period Total row
                html += `
            <tr class="row-total">
                <td colspan="3" style="text-align:right;color:var(--muted);">Period Totals:</td>
                <td style="color:var(--muted);font-size:.8rem;">${txList.length} transaction${txList.length !== 1 ? 's' : ''}</td>
                <td class="tr amt-dr">Rs ${fmt(res.total_debit)}</td>
                <td class="tr amt-cr">Rs ${fmt(res.total_credit)}</td>
                <td class="tr">—</td>
            </tr>`;

                // Closing Balance row
                const cbClass = cb >= 0 ? 'bal-cr' : 'bal-dr';
                const cbLabel = cb > 0.01 ? 'Payable (Cr)' : (cb < -0.01 ? 'Advance (Dr)' : 'Settled');
                html += `
            <tr class="row-closing">
                <td colspan="4" style="text-align:right;">
                    Closing Balance as of <strong>${formatDate(end)}</strong>:
                </td>
                <td colspan="2"></td>
                <td class="tr ${cbClass}" style="font-size:1rem;">
                    Rs ${fmt(Math.abs(cb))}
                    <div style="font-size:.72rem;font-weight:500;opacity:.75;">${cbLabel}</div>
                </td>
            </tr>`;

                document.getElementById('ledgerBody').innerHTML = html;
                document.getElementById('genTime').textContent = '🕐 Generated: ' + new Date().toLocaleString('en-PK');
                document.getElementById('txCount').textContent = `${txList.length} transaction(s) in period`;
            }

            // Server-Side Excel Export
            document.getElementById('btnExportExcel')?.addEventListener('click', function() {
                const vendor_id = document.getElementById('sel_vendor').value;
                const start_date = document.getElementById('sel_start').value;
                const end_date = document.getElementById('sel_end').value;

                if (!vendor_id || !start_date || !end_date) {
                    Swal.fire('Warning', 'Please generate the report first before exporting.', 'warning');
                    return;
                }

                const params = new URLSearchParams({ vendor_id, start_date, end_date });
                window.location.href = `{{ route('report.vendor.ledger.export.excel') }}?${params}`;
            });

            // Server-Side PDF Export
            document.getElementById('btnExportPdf')?.addEventListener('click', function() {
                const vendor_id = document.getElementById('sel_vendor').value;
                const start_date = document.getElementById('sel_start').value;
                const end_date = document.getElementById('sel_end').value;

                if (!vendor_id || !start_date || !end_date) {
                    Swal.fire('Warning', 'Please generate the report first before exporting.', 'warning');
                    return;
                }

                const params = new URLSearchParams({ vendor_id, start_date, end_date });
                window.location.href = `{{ route('report.vendor.ledger.export.pdf') }}?${params}`;
            });

            // PDF Export ALL
            document.getElementById('btnExportAll')?.addEventListener('click', function() {
                Swal.fire({
                    title: 'Export All Vendor Ledgers',
                    html: `
                        <div style="text-align:left; padding:0 10px;">
                            <label style="display:block; font-size:13px; margin-bottom:5px; font-weight:bold; color:#475569;">Transaction Period From:</label>
                            <input type="date" id="swal_start" class="form-control mb-3" value="${document.getElementById('sel_start').value}">
                            <label style="display:block; font-size:13px; margin-bottom:5px; font-weight:bold; color:#475569;">To Date:</label>
                            <input type="date" id="swal_end" class="form-control" value="${document.getElementById('sel_end').value}">
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '🚀 Start Batch Export',
                    confirmButtonColor: '#4f46e5',
                    preConfirm: () => {
                        const s = document.getElementById('swal_start').value;
                        const e = document.getElementById('swal_end').value;
                        if (!s || !e) { Swal.showValidationMessage('Please select both dates'); return false; }
                        return { start: s, end: e };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        doExportAll(result.value.start, result.value.end);
                    }
                });
            });

            function doExportAll(start, end) {
                document.getElementById('ledLoader').style.display = 'block';
                const params = new URLSearchParams({ start_date: start, end_date: end });

                fetch(`{{ route('report.vendor.ledger.fetch_all') }}?${params}`)
                    .then(r => r.json())
                    .then(res => {
                        document.getElementById('ledLoader').style.display = 'none';
                        if (res.error) { alert(res.error); return; }

                        renderAllInTemplate(res);
                        const element = document.getElementById('allReportTemplate');
                        element.style.display = 'block';
                        
                        html2pdf().set({
                            margin: 0,
                            filename: `All_Vendor_Ledgers_${start}_to_${end}.pdf`,
                            html2canvas: { scale: 3, useCORS: true, letterRendering: true },
                            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                            pagebreak: { mode: 'after', selector: '.print-container' }
                        }).from(element).save().then(() => {
                            element.style.display = 'none';
                        });
                    })
                    .catch(e => {
                        document.getElementById('ledLoader').style.display = 'none';
                        console.error(e);
                        alert('Export failed.');
                    });
            }

            function renderAllInTemplate(data) {
                const container = document.getElementById('allReportTemplate');
                let html = `
                    <style>
                        .print-container { width: 210mm; min-height: 297mm; background: #fff; padding: 10mm; box-sizing: border-box; position: relative; font-family: Arial, sans-serif; color: #000; }
                        .p-header { font-size: 11px; margin-bottom: 5px; border-bottom: 1.5px solid #000; padding-bottom: 5px; }
                        .p-title { font-weight: bold; font-size: 14px; margin-top: 5px; }
                        .p-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-top: 3px; }
                        .p-table th, .p-table td { border: 1px solid #000; padding: 2px 4px; }
                        .p-table th { background: #eee; text-align: left; }
                        .p-right { text-align: right; }
                        .p-summary-box { width: 300px; border: 1.5px solid #000; padding: 5px; margin-top: 10px; float: right; font-size: 10px; margin-bottom: 25px; }
                        .p-summary-title { font-weight: bold; margin-bottom: 3px; font-size: 11px; }
                        .p-footer { font-size: 8.5px; clear: both; padding-top: 10px; border-top: 1px solid #eee; display: flex; justify-content: space-between; }
                        .cf { clear: both; }
                    </style>
                `;

                data.all_data.forEach(item => {
                    const v = item.vendor;
                    const ob = parseFloat(item.opening_balance);
                    const cb = parseFloat(item.closing_balance);
                    
                    html += `
                        <div class="print-container">
                            <div class="p-header">
                                <strong>{{ $activeBranch->name ?? "Head Office" }}</strong><br>
                                {{ $activeBranch->address ?? "M17-18 Mezzanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore.." }}<br>
                                Phone : {{ $activeBranch->number ?? "0092-42-37353433" }}
                            </div>

                            <div class="p-title">Vendor Ledger Statement</div>
                            <div style="font-size:12px; margin:5px 0;">
                                ${data.period}
                            </div>

                            <table class="p-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Voucher #</th>
                                        <th>Description</th>
                                        <th>Ref #</th>
                                        <th>Debit (Out)</th>
                                        <th>Credit (In)</th>
                                        <th>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7"><strong>GL A/C : ${v.id} - ${v.name}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>${data.period.split(' to ')[0]}</td>
                                        <td></td>
                                        <td>B/F BALANCE AS ON ${data.period.split(' to ')[0]}</td>
                                        <td></td>
                                        <td class="p-right">${ob < 0 ? fmt(Math.abs(ob)) : '0.00'}</td>
                                        <td class="p-right">${ob >= 0 ? fmt(Math.abs(ob)) : '0.00'}</td>
                                        <td class="p-right">${fmt(Math.abs(ob))} ${ob >= 0 ? 'CR' : 'DR'}</td>
                                    </tr>
                                    ${item.transactions.map(t => `
                                        <tr>
                                            <td>${formatDate((t.date || '').split(' ')[0])}</td>
                                            <td>${t.invoice}</td>
                                            <td>${t.description}</td>
                                            <td>-</td>
                                            <td class="p-right">${t.debit > 0 ? fmt(t.debit) : '0.00'}</td>
                                            <td class="p-right">${t.credit > 0 ? fmt(t.credit) : '0.00'}</td>
                                            <td class="p-right">${fmt(Math.abs(t.balance))} ${t.balance >= 0 ? 'CR' : 'DR'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>

                            <div class="p-summary-box">
                                <div class="p-summary-title">SUMMARY : ${v.id} - ${v.name}</div>
                                <table style="width:100%; font-size:10px; border-collapse: collapse; line-height: 1.1;">
                                    <tr>
                                        <td style="padding: 2px 0;">B/F BALANCE AS ON</td>
                                        <td class="p-right" style="padding: 2px 0;">${data.period.split(' to ')[0]}</td>
                                        <td class="p-right" style="padding: 2px 0;">${fmt(Math.abs(ob))} ${ob >= 0 ? 'CR' : 'DR'}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 2px 0;">TOTAL DEBIT (PAID)</td>
                                        <td></td>
                                        <td class="p-right" style="padding: 2px 0;">${fmt(item.total_debit)}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 2px 0;">TOTAL CREDIT (BILLED)</td>
                                        <td></td>
                                        <td class="p-right" style="padding: 2px 0;">${fmt(item.total_credit)}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 2px 0;"><strong>CLOSING BALANCE AS ON</strong></td>
                                        <td class="p-right" style="padding: 2px 0;">${data.period.split(' to ')[1]}</td>
                                        <td class="p-right" style="padding: 2px 0;"><strong>${fmt(Math.abs(cb))} ${cb >= 0 ? 'CR' : 'DR'}</strong></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="cf"></div>

                            <div class="p-footer">
                                <div>Three Stars Medical Supplies Ledger Report</div>
                                <div>Report Printed On : ${data.date}</div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            }

        })();

        function printReport() {
            document.getElementById('ledgerResult').style.display = 'block';
            window.print();
        }
    </script>
@endsection
