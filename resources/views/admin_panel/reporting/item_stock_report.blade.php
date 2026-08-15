@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --c-primary: #2563eb;
            --c-success: #16a34a;
            --c-warning: #d97706;
            --c-danger: #dc2626;
            --c-purple: #7c3aed;
            --c-cyan: #0891b2;
            --card-shadow: 0 1px 4px rgba(0, 0, 0, .07), 0 6px 20px rgba(0, 0, 0, .06);
        }

        .rpt-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #38bdf8 100%);
            border-radius: 14px;
            padding: 22px 28px;
            margin-bottom: 22px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 6px 24px rgba(37, 99, 235, .32);
        }

        .rpt-header h3 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .rpt-header p {
            margin: 3px 0 0;
            font-size: .84rem;
            opacity: .82;
        }

        .rpt-header-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .16);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid transparent;
            display: flex;
            flex-direction: column;
            gap: 5px;
            transition: transform .15s;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
        }

        .kpi-card.blue {
            border-color: var(--c-primary);
        }

        .kpi-card.green {
            border-color: var(--c-success);
        }

        .kpi-card.amber {
            border-color: var(--c-warning);
        }

        .kpi-card.red {
            border-color: var(--c-danger);
        }

        .kpi-card.purple {
            border-color: var(--c-purple);
        }

        .kpi-label {
            font-size: .73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #64748b;
        }

        .kpi-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.1;
        }

        .kpi-sub {
            font-size: .73rem;
            color: #94a3b8;
        }

        .kpi-icon {
            font-size: 1.2rem;
        }

        .kpi-card.blue .kpi-icon {
            color: var(--c-primary);
        }

        .kpi-card.green .kpi-icon {
            color: var(--c-success);
        }

        .kpi-card.amber .kpi-icon {
            color: var(--c-warning);
        }

        .kpi-card.red .kpi-icon {
            color: var(--c-danger);
        }

        .kpi-card.purple .kpi-icon {
            color: var(--c-purple);
        }

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

        .btn-pdf-summary {
            background: #10b981;
            color: #fff;
        }

        .btn-pdf-summary:hover {
            background: #059669;
        }

        .btn-pdf-detail {
            background: #7c3aed;
            color: #fff;
        }

        .btn-pdf-detail:hover {
            background: #6d28d9;
        }

        .btn-print-action {
            background: #6366f1;
            color: #fff;
        }

        .btn-print-action:hover {
            background: #4f46e5;
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

        .table-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px 20px 14px;
            box-shadow: var(--card-shadow);
        }

        #stockTable thead th {
            background: #1e3a8a;
            color: #fff;
            font-size: .73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            white-space: nowrap;
            padding: 10px 11px;
            border: none;
        }

        #stockTable thead th:first-child {
            border-radius: 8px 0 0 0;
        }

        #stockTable thead th:last-child {
            border-radius: 0 8px 0 0;
        }

        #stockTable tbody tr {
            font-size: .82rem;
            transition: background .1s;
        }

        #stockTable tbody tr:hover {
            background: #eff6ff !important;
        }

        #stockTable tbody td {
            padding: 8px 11px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        #stockTable tfoot th {
            background: #f8fafc;
            font-size: .8rem;
            font-weight: 700;
            color: #1e293b;
            padding: 9px 11px;
            border-top: 2px solid #e2e8f0;
        }

        .mode-badge {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-size: .68rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .mode-badge.by_size {
            background: #ede9fe;
            color: #5b21b6;
        }

        .mode-badge.by_carton {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .mode-badge.by_piece {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: .71rem;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .status-badge.normal {
            background: #dcfce7;
            color: #15803d;
        }

        .status-badge.low_stock {
            background: #fef9c3;
            color: #a16207;
        }

        .status-badge.out_of_stock {
            background: #fee2e2;
            color: #b91c1c;
        }

        .balance-main {
            font-weight: 700;
            font-size: .9rem;
        }

        .balance-sub {
            font-size: .71rem;
            color: #94a3b8;
            line-height: 1.3;
        }

        .wh-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            min-width: 170px;
        }

        .wh-pill {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: .72rem;
            padding: 3px 8px;
            color: #334155;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .wh-pill .wh-name {
            font-weight: 600;
            color: #1e3a8a;
        }

        .wh-pill .wh-qty {
            font-weight: 700;
            color: #6d28d9;
        }

        .wh-no-data {
            font-size: .75rem;
            color: #94a3b8;
            font-style: italic;
        }

        .amt-chip {
            font-weight: 600;
            font-size: .82rem;
        }

        .amt-chip.pur {
            color: #1d4ed8;
        }

        .amt-chip.sal {
            color: #15803d;
        }

        .amt-chip.val {
            color: #6d28d9;
            font-size: .88rem;
        }

        .total-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }

        .total-tile {
            border-radius: 10px;
            padding: 11px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .total-tile.pur {
            background: #eff6ff;
        }

        .total-tile.sal {
            background: #f0fdf4;
        }

        .total-tile.val {
            background: #faf5ff;
        }

        .total-tile .tt-label {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .total-tile.pur .tt-label {
            color: #3b82f6;
        }

        .total-tile.sal .tt-label {
            color: #16a34a;
        }

        .total-tile.val .tt-label {
            color: #7c3aed;
        }

        .total-tile .tt-val {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .total-tile.pur .tt-val {
            color: #1e3a8a;
        }

        .total-tile.sal .tt-val {
            color: #14532d;
        }

        .total-tile.val .tt-val {
            color: #4c1d95;
        }

        .total-tile .tt-icon {
            font-size: 1.2rem;
        }

        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .65);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .loader-box {
            background: #fff;
            border-radius: 14px;
            padding: 30px 38px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .12);
        }

        .loader-box .spinner-border {
            width: 2.5rem;
            height: 2.5rem;
            color: var(--c-primary);
            border-width: 3px;
        }

        .loader-box p {
            margin: 10px 0 0;
            font-size: .88rem;
            color: #64748b;
            font-weight: 600;
        }

        @media(max-width:1000px) {
            .kpi-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media(max-width:640px) {
            .kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .total-strip {
                grid-template-columns: 1fr;
            }
        }

        @media print {

            .filter-card,
            .btn-srp,
            .total-strip,
            .rpt-header {
                display: none !important;
            }

            .main-content,
            .main-content-inner,
            .container-fluid,
            #stockTableWrap {
                display: block !important;
                width: 100% !important;
            }

            .print-header {
                display: block !important;
            }

            #stockTable thead th {
                background: #f1f5f9 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                border: 1px solid #000 !important;
            }
        }
    </style>
    <style>
        /* ── Product Ledger A4 Template CSS (User Provided) ── */
        #pdfLedgerTemplate { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; line-height: 1.2; display: none; }
        .pdf-a4 { width: 794px; padding: 22px 26px 28px 26px; background: #fff; margin: 0 auto; min-height: 1123px; }
        .pdf-co-name { font-size: 11px; font-weight: bold; margin-bottom: 1px; }
        .pdf-co-addr { font-size: 10px; line-height: 1.65; margin-bottom: 16px; }
        .pdf-report-title { font-size: 20px; font-weight: bold; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 0; }
        .pdf-date-right { text-align: right; font-size: 10.5px; font-weight: bold; color: #1a56a0; margin-top: 5px; margin-bottom: 2px; }
        .pdf-loc-page-row { display: flex; justify-content: space-between; font-size: 10.5px; margin-bottom: 3px; }
        .pdf-loc-val { font-weight: bold; }
        table.pdf-main { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 20px; }
        table.pdf-main thead th { background: #c6d9f1; border: 1px solid #8eb3d9; padding: 4px 5px; text-align: left; font-weight: bold; }
        table.pdf-main thead th.r { text-align: right; }
        tr.pdf-prod-hdr td { background: #dce6f1; border: 1px solid #aac2dc; padding: 3px 5px; font-weight: bold; font-size: 10.5px; }
        tr.pdf-prod-hdr td span.pcode { margin-right: 12px; }
        tr.pdf-drow td { border: 1px solid #d0d8e4; padding: 2.5px 5px; font-size: 10.5px; vertical-align: top; }
        tr.pdf-drow td.r { text-align: right; }
        tr.pdf-cbal td { border: 1px solid #d0d8e4; padding: 2.5px 5px; font-size: 10.5px; text-align: right; font-weight: bold; color: #1a56a0; }
        tr.pdf-cbal td.lbl { font-weight: bold; color: #1a56a0; }
        .pdf-footer { display: flex; justify-content: space-between; font-size: 9px; color: #444; border-top: 0.5px solid #aaa; margin-top: 30px; padding-top: 4px; }
    </style>

    <!-- Loader -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader-box">
            <div class="spinner-border" role="status"></div>
            <p>Loading stock data…</p>
        </div>
    </div>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid">

                <!-- Print Header (only visible when printing) -->
                <div class="print-header" style="display:none; margin-bottom:16px;">
                    <h2 style="margin:0;font-size:18px;font-weight:700;">📊 Item Stock Report</h2>
                    <p style="margin:4px 0 0;font-size:12px;color:#555;">Printed: {{ now()->format('d M Y H:i') }}</p>
                </div>

                <!-- Page Header -->
                <div class="rpt-header" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin:0; font-size:1.35rem; font-weight:700; color:#1e293b;"><i class="fas fa-layer-group me-2"></i> Item Stock Report</h3>
                        <p style="margin:4px 0 0; font-size:.85rem; color:#64748b;">Full inventory — stock by size mode, per-warehouse breakdown, movements &amp; valuations</p>
                        <p style="opacity:.62;font-size:.76rem;margin-top:4px; margin-bottom:0;">Generated: <span id="reportDate"></span></p>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card blue">
                        <span class="kpi-icon"><i class="fas fa-boxes"></i></span>
                        <span class="kpi-label">Total Products</span>
                        <span class="kpi-value" id="kpiTotal">—</span>
                        <span class="kpi-sub">In this view</span>
                    </div>
                    <div class="kpi-card purple">
                        <span class="kpi-icon"><i class="fas fa-gem"></i></span>
                        <span class="kpi-label">Stock Value</span>
                        <span class="kpi-value" id="kpiValue">—</span>
                        <span class="kpi-sub">PKR</span>
                    </div>
                    <div class="kpi-card green">
                        <span class="kpi-icon"><i class="fas fa-warehouse"></i></span>
                        <span class="kpi-label">Warehouses</span>
                        <span class="kpi-value" id="kpiWarehouses">—</span>
                        <span class="kpi-sub">Distinct locations</span>
                    </div>
                    <div class="kpi-card amber">
                        <span class="kpi-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <span class="kpi-label">Low Stock</span>
                        <span class="kpi-value" id="kpiLow">—</span>
                        <span class="kpi-sub">At/below alert qty</span>
                    </div>
                    <div class="kpi-card red">
                        <span class="kpi-icon"><i class="fas fa-times-circle"></i></span>
                        <span class="kpi-label">Out of Stock</span>
                        <span class="kpi-value" id="kpiOut">—</span>
                        <span class="kpi-sub">Zero balance</span>
                    </div>
                </div>

                <!-- Filters -->
                <div class="filter-card">
                    <div class="filter-inputs-container">
                        <div class="filter-row">
                            <div class="filter-group" style="flex: 1; min-width: 150px;">
                                <label>Category</label>
                                <select id="filterCategory" class="form-control select2-global">
                                    <option value="all">All Category</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 150px;">
                                <label>Sub-Category</label>
                                <select id="filterSubCategory" class="form-control select2-global">
                                    <option value="all">All Sub-category</option>
                                    @foreach($subCategories as $sc)
                                        <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 150px;">
                                <label>Brand / Company</label>
                                <select id="filterBrand" class="form-control select2-global">
                                    <option value="all">All Company</option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 150px; max-width: 350px;">
                                <label>Product</label>
                                <select id="product_id" class="form-control select2-product">
                                    <option value="all">— All Products —</option>
                                    @foreach ($products as $prod)
                                        <option value="{{ $prod->id }}" 
                                            data-cat="{{ $prod->category_id }}" 
                                            data-sub="{{ $prod->sub_category_id }}" 
                                            data-brand="{{ $prod->brand_id }}">
                                            {{ $prod->item_code }} — {{ $prod->item_name }} {{ $prod->brand->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 150px;">
                                <label>Location (Shop / Warehouse)</label>
                                <select id="filterWarehouse" class="form-control select2-global">
                                    <option value="all">All Locations</option>
                                    @php
                                        $shopLocations = $allLocations->where('type','shop');
                                        $whLocations   = $allLocations->where('type','warehouse');
                                    @endphp
                                    @if($shopLocations->isNotEmpty())
                                    <optgroup label="🏪 Shops (Retail)">
                                        @foreach($shopLocations as $loc)
                                            <option value="{{ $loc->id }}" data-type="shop">🏪 {{ $loc->warehouse_name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endif
                                    @if($whLocations->isNotEmpty())
                                    <optgroup label="🏭 Warehouses (Storage)">
                                        @foreach($whLocations as $loc)
                                            <option value="{{ $loc->id }}" data-type="warehouse">🏭 {{ $loc->warehouse_name }}</option>
                                        @endforeach
                                    </optgroup>
                                    @endif
                                </select>
                            </div>
                            @if($isSuperAdmin)
                            <div class="filter-group" style="flex: 1; min-width: 150px;">
                                <label>Branch</label>
                                <select id="filterBranch" class="form-control select2-global">
                                    <option value="all">— All Branches —</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>

                        <div class="filter-row">
                            <div class="filter-group" style="flex: 1; min-width: 110px; max-width: 150px;">
                                <label>Start Date</label>
                                <input type="date" id="start_date" class="form-control">
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 110px; max-width: 150px;">
                                <label>End Date</label>
                                <input type="date" id="end_date" class="form-control">
                            </div>
                            <div class="filter-group" style="flex: 1; min-width: 110px; max-width: 150px;">
                                <label>Status</label>
                                <select id="filterStatus" class="form-control">
                                    <option value="all">All Status</option>
                                    <option value="normal">✅ Normal</option>
                                    <option value="low_stock">⚠️ Low Stock</option>
                                    <option value="out_of_stock">❌ Out of Stock</option>
                                </select>
                            </div>
                            <div class="filter-group" style="flex-direction: row; gap: 6px; align-items: flex-end; min-width: 400px; margin-left: 10px; flex: 1.5;">
                                <button type="button" class="btn-filter-action btn-filter-search" id="btnSearch" style="flex: 1;">🔍 Search</button>
                                <button type="button" class="btn-filter-action btn-filter-reset" id="btnReset" style="flex: 1;">↺ Reset</button>
                                <button type="button" id="btnExportExcel" class="btn-filter-action btn-excel-action" style="flex: 1.2;" title="Export Excel Spreadsheet">📊 Export Excel</button>
                                <button type="button" id="btnExportPdf" class="btn-filter-action btn-pdf-action" style="flex: 1.2;" title="Export Detail PDF">📄 Export PDF</button>
                                <button type="button" onclick="printReport()" class="btn-filter-action btn-print-action" style="flex: 1;" title="Print View">🖨 Print</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals Strip -->
                <div class="total-strip" id="totalsStrip" style="display:none;">
                    <div class="total-tile pur">
                        <i class="fas fa-shopping-cart tt-icon" style="color:#3b82f6;"></i>
                        <div>
                            <div class="tt-label">Total Purchase Amount</div>
                            <div class="tt-val" id="stripPurchase">PKR 0.00</div>
                        </div>
                    </div>
                    <div class="total-tile sal">
                        <i class="fas fa-receipt tt-icon" style="color:#16a34a;"></i>
                        <div>
                            <div class="tt-label">Total Sale Amount</div>
                            <div class="tt-val" id="stripSale">PKR 0.00</div>
                        </div>
                    </div>
                    <div class="total-tile val">
                        <i class="fas fa-gem tt-icon" style="color:#7c3aed;"></i>
                        <div>
                            <div class="tt-label">Grand Stock Value</div>
                            <div class="tt-val" id="stripValue">PKR 0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Main Table -->
                <div class="table-card">
                    <div class="table-responsive">
                        <table id="stockTable" class="table table-bordered" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Company / Cat.</th>
                                    <th>Stock Detail</th>
                                    <th>Warehouse Stock</th>
                                    <th>Batch / Lot</th>
                                    @if($isSuperAdmin)
                                    <th>Branch</th>
                                    @endif
                                    <th>Status</th>
                                    <th>Init. (Pcs)</th>
                                    <th>Purchsed</th>
                                    <th>Pur.Ret</th>
                                    <th>Sold</th>
                                    <th>Sale Ret</th>
                                    <th>Adj.</th>
                                    <th>Balance (Pcs)</th>
                                    <th>Pur.Amt</th>
                                    <th>Sale Amt</th>
                                    <th>Price</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody id="reportBody"></tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="{{ $isSuperAdmin ? 9 : 8 }}" class="text-end">Grand Totals:</th>
                                    <th id="ftInit">0</th>
                                    <th id="ftPurchased">0</th>
                                    <th id="ftPurRet">0</th>
                                    <th id="ftSold">0</th>
                                    <th id="ftSaleRet">0</th>
                                    <th id="ftAdjusted">0</th>
                                    <th id="ftBalance">0</th>
                                    <th id="ftPurAmt">0.00</th>
                                    <th id="ftSaleAmt">0.00</th>
                                    <th></th>
                                    <th id="ftStockVal">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Hidden Detail PDF Template (portrait A4, matches classic ledger design) -->
    <div id="pdfLedgerTemplate" style="display:none;position:fixed;left:-9999px;top:0;z-index:-1;">
        <div id="pdfA4Content" style="
            width:794px;
            background:#fff;
            padding:20px 24px 28px 24px;
            font-family:Arial,sans-serif;
            font-size:11px;
            color:#000;
            box-sizing:border-box;
        ">
            <!-- Header -->
            <div style="font-size:12px;font-weight:bold;margin-bottom:1px;">THREE STARS MEDICAL SUPPLIES</div>
            <div style="font-size:10px;line-height:1.65;margin-bottom:16px;">
                <strong>{{ $activeBranch->name ?? 'Head Office' }} :</strong> {{ $activeBranch->address ?? 'M17-18 Mezanine Floor Seth Centre 10 Syed Mouj Darya Road (Edward Road) Lahore.' }}<br>
                <strong>Phone :</strong> {{ $activeBranch->number ?? '0092-42-37353433' }}
            </div>
            <div style="font-size:19px;font-weight:bold;border-bottom:2px solid #000;padding-bottom:4px;margin-bottom:0;">Product Ledger</div>
            <div id="pdfReportPeriod" style="text-align:right;font-size:10.5px;font-weight:bold;color:#1a56a0;margin-top:4px;margin-bottom:1px;">— — —</div>
            <div style="display:flex;justify-content:space-between;font-size:10.5px;margin-bottom:6px;">
                <span>Location : &nbsp;<strong>{{ strtoupper($activeBranch->name ?? 'Head Office') }}</strong></span>
                <span id="pdfPageLabel"></span>
            </div>

            <!-- Main Table -->
            <table id="pdfLedgerTable" style="width:100%;border-collapse:collapse;font-size:10.5px;">
                <thead>
                    <tr>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:left;font-weight:bold;width:30px;">SR #</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:left;font-weight:bold;width:92px;">Date</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:left;font-weight:bold;">Description</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:left;font-weight:bold;width:54px;">REF #</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:right;font-weight:bold;width:70px;">Rate</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:right;font-weight:bold;width:60px;">Debit</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:right;font-weight:bold;width:60px;">Credit</th>
                        <th style="background:#4472c4;color:#fff;border:1px solid #2f569e;padding:3px 5px;text-align:right;font-weight:bold;width:60px;">Balance</th>
                    </tr>
                </thead>
                <tbody id="pdfLedgerBody">
                    <!-- rows injected by JS -->
                </tbody>
            </table>

            <!-- Footer -->
            <div id="pdfPrintFooter" style="display:flex;justify-content:space-between;font-size:9px;color:#444;border-top:0.5px solid #aaa;margin-top:28px;padding-top:4px;">
                <span>ProWaves ver.8.0.1.4592 Copyrights &copy; 2026 Cybernetic Technologies. All rights reserved. &nbsp;&nbsp; rptItemLedger</span>
                <span><strong>Print Date :</strong> &nbsp;<span id="pdfPrintDate"></span></span>
            </div>
        </div>
    </div>

    </div>
@endsection

@section('js')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script>
        $(document).ready(function() {
            // Set default dates
            const now = new Date();
            const todayStr = now.toISOString().split('T')[0];
            const firstOfM = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-01';
            $('#start_date').val(firstOfM);
            $('#end_date').val(todayStr);

            document.getElementById('reportDate').textContent = new Date().toLocaleString();

            const IS_SUPER_ADMIN = {{ $isSuperAdmin ? 'true' : 'false' }};

            // ── Select2 ─────────────────────────────────────────────────────────
            $('.select2-global').select2({ width: '100%', dropdownCssClass: 'select2-custom-dropdown' });
            $('.select2-product').select2({
                placeholder: '— All Products —',
                allowClear: true,
                width: '100%',
                dropdownCssClass: 'select2-custom-dropdown'
            });

            // ── DataTable ────────────────────────────────────────────────────────
            var dt = $('#stockTable').DataTable({
                paging: true,
                searching: true,
                info: true,
                ordering: true,
                pageLength: 25,
                order: [
                    [IS_SUPER_ADMIN ? 8 : 7, 'asc']
                ],
                language: {
                    search: '',
                    searchPlaceholder: '🔍 Quick search…',
                    lengthMenu: 'Show _MENU_ rows',
                    info: 'Showing _START_–_END_ of _TOTAL_ items',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                },
                columnDefs: [{
                        targets: [0],
                        className: 'text-center',
                        width: '38px'
                    },
                    {
                        targets: IS_SUPER_ADMIN ? [8] : [7],
                        className: 'text-center'
                    },
                    {
                        targets: IS_SUPER_ADMIN ? [9, 10, 11, 12, 13, 14, 15, 16, 17, 19] : [8, 9, 10, 11, 12, 13, 14, 15, 16, 18],
                        className: 'text-right'
                    },
                ],
                drawCallback: updateFooter
            });

            // ── Formatters ───────────────────────────────────────────────────────
            function fmt(v, dec) {
                dec = (dec === undefined) ? 2 : dec;
                return parseFloat(v || 0).toLocaleString('en-PK', {
                    minimumFractionDigits: dec,
                    maximumFractionDigits: dec
                });
            }

            function fmtPKR(v) {
                return 'PKR ' + fmt(v, 2);
            }

            // Alias used in Detail PDF HTML builder
            function fmtN(v, dec) { return fmt(v, dec); }

            // ── Batch/Lot cell ────────────────────────────────────────────────────────
            function batchCell(r) {
                var batches = r.batches || [];
                if (!batches.length) {
                    return '<span style="font-size:.72rem;color:#94a3b8;font-style:italic;">No batches</span>';
                }
                var today = new Date();
                today.setHours(0, 0, 0, 0);
                var html = '<div style="display:flex;flex-direction:column;gap:4px;min-width:175px;max-height:160px;overflow-y:auto;padding-right:2px;">';
                batches.forEach(function(b) {
                    var expDate = b.exp_date ? new Date(b.exp_date) : null;
                    var isExpired = expDate && expDate < today;
                    var daysLeft = expDate ? Math.ceil((expDate - today) / (1000*60*60*24)) : null;
                    var expColor = isExpired ? '#dc2626' : (daysLeft !== null && daysLeft <= 90 ? '#d97706' : '#15803d');
                    var expIcon  = isExpired ? '⚠️' : (daysLeft !== null && daysLeft <= 90 ? '⏳' : '✅');
                    
                    var isOpening = b.source_type === 'opening_stock' || (b.batch_number && /^[0]+$/.test(b.batch_number.trim()));
                    var isUnbatched = b.source_type === 'unbatched_stock';
                    var statusBg = isOpening ? '#eff6ff' : (isUnbatched ? '#f1f5f9' : (b.status === 'expired' ? '#fee2e2' : (b.status === 'held' ? '#fef9c3' : '#f0fdf4')));
                    var titleColor = isOpening ? '#1e40af' : (isUnbatched ? '#475569' : '#1e3a8a');
                    var label = isOpening ? '📦 Opening Stock' : (isUnbatched ? '📦 Unbatched Stock' : '🏷️ ' + (b.batch_number || '-'));
                    
                    html += '<div style="background:' + statusBg + ';border:1px solid #e2e8f0;border-radius:6px;padding:4px 7px;">' +
                        '<div style="font-weight:700;font-size:.78rem;color:' + titleColor + ';display:flex;align-items:center;gap:4px;">' +
                            '<span>' + label + '</span>' +
                            (b.exp_date ? '<span style="margin-left:auto;display:inline-flex;align-items:center;">' + expIcon + '</span>' : '') +
                        '</div>' +
                        '<div style="font-size:.7rem;color:#475569;margin-top:2px;">' +
                            '<span style="color:#7c3aed;font-weight:600;">' + fmt(b.qty_remaining, 0) + ' pcs</span>' +
                            (b.exp_date ? ' &nbsp;|&nbsp; <span style="color:' + expColor + ';">Exp: ' + b.exp_date + '</span>' : '') +
                        '</div>' +
                    '</div>';
                });
                html += '</div>';
                return html;
            }

            // ── Size mode badge ──────────────────────────────────────────────────
            var modeLabels = {
                by_size: '📐 By Size',
                by_carton: '📦 By Carton',
                by_piece: '🔢 By Piece'
            };

            function modeBadge(m) {
                return '<span class="mode-badge ' + m + '">' + (modeLabels[m] || m) + '</span>';
            }

            // ── Status badge ─────────────────────────────────────────────────────
            var statusMap = {
                normal: ['<i class="fas fa-check-circle"></i> Normal', 'normal'],
                low_stock: ['<i class="fas fa-exclamation-triangle"></i> Low', 'low_stock'],
                out_of_stock: ['<i class="fas fa-times-circle"></i> Out of Stock', 'out_of_stock'],
            };

            function statusBadge(s) {
                var info = statusMap[s] || statusMap.normal;
                return '<span class="status-badge ' + info[1] + '">' + info[0] + '</span>';
            }

            // ── Stock detail cell — per size_mode ────────────────────────────────
            function stockDetailCell(r) {
                var db = r.display_balance || {};
                var mode = db.mode || r.size_mode;

                if (mode === 'by_size') {
                    var m2 = parseFloat(db.total_m2 || 0).toFixed(4);
                    var col = r.stock_status === 'out_of_stock' ? '#dc2626' : r.stock_status === 'low_stock' ?
                        '#d97706' : '#6d28d9';
                    return '<div class="balance-main" style="color:' + col + ';">' + m2 + ' m²</div>' +
                        '<div class="balance-sub">' + fmt(db.boxes, 0) + ' box + ' + fmt(db.loose, 0) + ' pcs<br>' +
                        '<span style="color:#94a3b8;">' + fmt(r.height, 0) + '×' + fmt(r.width, 0) + ' cm · ' +
                        parseFloat(r.total_m2_box || 0).toFixed(4) + ' m²/box</span></div>';

                } else if (mode === 'by_carton') {
                    var col2 = r.stock_status === 'out_of_stock' ? '#dc2626' : r.stock_status === 'low_stock' ?
                        '#d97706' : '#15803d';
                    
                    var dotDisplay = db.dot_notation || (fmt(db.boxes, 0) + '.' + fmt(db.loose, 0));
                    
                    var uomLine = db.uom_breakdown ? '<div class="small text-muted mt-1">' + db.uom_breakdown + '</div>' : '';

                    var packingsHtml = '';
                    if (r.packings && r.packings.length > 0) {
                        packingsHtml = '<div style="margin-top:3px;display:flex;flex-wrap:wrap;gap:2px;">' +
                            r.packings.map(p => '<span class="badge bg-light text-dark border" style="font-size:.65rem;">' + p.name + ' (' + p.pieces_per_box + ' pcs)</span>').join('') +
                            '</div>';
                    }

                    return '<div class="balance-main" style="color:' + col2 + ';">' + dotDisplay + ' <small class="fw-normal text-muted">(B.P)</small></div>' +
                        '<div class="balance-sub">' + fmt(db.pieces, 0) + ' total pcs<br>' +
                        '<span style="color:#94a3b8;">' + r.pieces_per_box + ' pcs/box</span>' +
                        uomLine +
                        packingsHtml + '</div>';

                } else {
                    var col3 = r.stock_status === 'out_of_stock' ? '#dc2626' : r.stock_status === 'low_stock' ?
                        '#d97706' : '#15803d';
                    
                    var dotDisplay = db.dot_notation || (Math.floor(r.balance / (r.pieces_per_box || 1)) + '.' + (r.balance % (r.pieces_per_box || 1)));

                    var packingsHtml = '';
                    if (r.packings && r.packings.length > 0) {
                        packingsHtml = '<div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:2px;">' +
                            r.packings.map(p => '<span class="badge bg-light text-dark border" style="font-size:.65rem;">' + p.name + ' (' + p.pieces_per_box + ')</span>').join('') +
                            '</div>';
                    }

                    return '<div class="balance-main" style="color:' + col3 + ';">' + dotDisplay + ' <small class="fw-normal text-muted">(B.P)</small></div>' +
                        '<div class="balance-sub">' + fmt(db.pieces || r.balance, 0) + ' total pcs<br>' +
                        '<span style="color:#94a3b8;">Unit: ' + r.unit + '</span>' + 
                        packingsHtml + '</div>';
                }
            }

            // ── Balance ledger cell ──────────────────────────────────────────────
            function balanceCell(r) {
                var b = parseFloat(r.balance || 0);
                var col = r.stock_status === 'out_of_stock' ? '#dc2626' : r.stock_status === 'low_stock' ?
                    '#d97706' : '#15803d';
                
                var ppb = r.pieces_per_box || 1;
                var boxes = Math.floor(b / ppb);
                var loose = b % ppb;

                return '<span style="font-weight:700;color:' + col + ';">' + boxes + '.' + loose + '</span><br>' +
                    '<span class="balance-sub">' + fmt(b, 0) + ' total pcs</span>';
            }

            // ── Warehouse pills ──────────────────────────────────────────────────
            function whCell(r) {
                var whs = r.warehouses || [];
                if (!whs.length) {
                    return '<span class="wh-no-data"><i class="fas fa-times-circle" style="color:#fca5a5;"></i> No stock</span>';
                }
                var pills = whs.map(function(w) {
                    return '<div class="wh-pill">' +
                        '<i class="fas fa-warehouse" style="color:#64748b;font-size:.7rem;"></i>' +
                        '<span class="wh-name">' + w.warehouse_name + '</span>: ' +
                        '<span class="wh-qty">' + w.display + '</span>' +
                        '</div>';
                }).join('');
                return '<div class="wh-pills">' + pills + '</div>';
            }

            // ── Price cell ───────────────────────────────────────────────────────
            function priceCell(r) {
                var mode = (r.display_balance || {}).mode || r.size_mode;
                if (mode === 'by_size' && r.price_per_m2 > 0)
                    return '<span style="font-size:.8rem;">' + fmtPKR(r.price_per_m2) +
                        '<br><span style="color:#64748b;">/m²</span></span>';
                if (r.sale_price_per_piece > 0)
                    return '<span style="font-size:.8rem;">' + fmtPKR(r.sale_price_per_piece) +
                        '<br><span style="color:#64748b;">/pc</span></span>';
                return '<span style="font-size:.8rem;">' + fmtPKR(r.sale_price_per_box) +
                    '<br><span style="color:#64748b;">/box</span></span>';
            }

            // ── State ────────────────────────────────────────────────────────────
            var _allRows = [];
            var _warehousesLoaded = false;

            // ── Render rows ──────────────────────────────────────────────────────
            function renderRows(rows) {
                _allRows = rows;
                dt.clear();

                var kTotal = rows.length,
                    kVal = 0,
                    kLow = 0,
                    kOut = 0;
                var whSet = new Set();
                var gInit = 0,
                    gPur = 0,
                    gPurRet = 0,
                    gSold = 0,
                    gDonated = 0,
                    gSaleRet = 0,
                    gAdj = 0,
                    gBal = 0,
                    gPurAmt = 0,
                    gSaleAmt = 0,
                    gVal = 0;

                rows.forEach(function(r, i) {
                    kVal += parseFloat(r.stock_value || 0);
                    if (r.stock_status === 'low_stock') kLow++;
                    if (r.stock_status === 'out_of_stock') kOut++;
                    (r.warehouses || []).forEach(function(w) {
                        whSet.add(w.warehouse_name);
                    });

                    gInit += parseFloat(r.initial_stock || 0);
                    gPur += parseFloat(r.purchased || 0);
                    gPurRet += parseFloat(r.purchase_return_qty || 0);
                    gSold += parseFloat(r.sold || 0);
                    gDonated += parseFloat(r.donated || 0);
                    gSaleRet += parseFloat(r.sale_return_qty || 0);
                    gAdj += parseFloat(r.adjusted_qty || 0);
                    gBal += parseFloat(r.balance || 0);
                    gPurAmt += parseFloat(r.purchase_amount || 0);
                    gSaleAmt += parseFloat(r.sale_amount || 0);
                    gVal += parseFloat(r.stock_value || 0);

                    var rowData = [
                        i + 1,
                        '<strong style="color:#1e3a8a;">' + r.item_code + '</strong>',
                        '<div style="font-weight:600;color:#0f172a;max-width:180px;">' + r
                        .item_name + '</div>' +
                        (r.color && r.color !== '-' ?
                            '<span style="font-size:.72rem;color:#64748b;">🎨 ' + r.color +
                            '</span>' : ''),
                        '<span style="font-weight:600;font-size:.82rem;">' + r.brand +
                        '</span><br>' +
                        '<span style="font-size:.72rem;color:#64748b;">' + r.category +
                        (r.sub_category && r.sub_category !== '-' ? ' › ' + r.sub_category : '') +
                        '</span>',
                        stockDetailCell(r),
                        whCell(r),
                        batchCell(r),
                    ];

                    if (IS_SUPER_ADMIN) {
                        rowData.push('<span class="badge bg-soft-primary text-primary">' + (r.branch_names || '-') + '</span>');
                    }

                    rowData.push(
                        statusBadge(r.stock_status),
                        '<span style="font-size:.82rem;">' + fmt(r.initial_stock, 0) + '</span>',
                        '<span style="color:#1d4ed8;font-weight:600;">' + fmt(Math.abs(r.purchased), 0) + '</span>',
                        '<span style="color:#dc2626;font-weight:600;">-' + fmt(Math.abs(r.purchase_return_qty), 0) + '</span>',
                        '<span style="color:#b45309;font-weight:600;">-' + fmt(Math.abs(r.sold), 0) + '</span>',
                        '<span style="color:#0284c7;font-weight:600;">-' + fmt(Math.abs(r.donated || 0), 0) + '</span>',
                        '<span style="color:#7c3aed;font-weight:600;">+' + fmt(Math.abs(r.sale_return_qty), 0) + '</span>',
                        '<span style="color:#64748b;">' + (r.adjusted_qty >= 0 ? '+' : '-') + fmt(Math.abs(r.adjusted_qty), 0) + '</span>',
                        balanceCell(r),
                        '<span class="amt-chip pur">' + fmtPKR(r.purchase_amount) + '</span>',
                        '<span class="amt-chip sal">' + fmtPKR(r.sale_amount) + '</span>',
                        priceCell(r),
                        '<strong class="amt-chip val">' + fmtPKR(r.stock_value) + '</strong>'
                    );

                    dt.row.add(rowData);
                });

                dt.draw();

                // KPIs
                $('#kpiTotal').text(kTotal.toLocaleString());
                $('#kpiValue').text('PKR ' + fmt(kVal, 0));
                $('#kpiWarehouses').text(whSet.size);
                $('#kpiLow').text(kLow);
                $('#kpiOut').text(kOut);
                // Totals strip
                $('#stripPurchase').text(fmtPKR(gPurAmt));
                $('#stripSale').text(fmtPKR(gSaleAmt));
                $('#stripValue').text(fmtPKR(gVal));
                $('#totalsStrip').show();
                // Footer
                window._gTotals = {
                    gInit,
                    gPur,
                    gPurRet,
                    gSold,
                    gDonated,
                    gSaleRet,
                    gAdj,
                    gBal,
                    gPurAmt,
                    gSaleAmt,
                    gVal
                };
                updateFooter();
            }

            function updateFooter() {
                var g = window._gTotals;
                if (!g) return;
                $('#ftInit').text(fmt(g.gInit, 0));
                $('#ftPurchased').text(fmt(Math.abs(g.gPur), 0));
                $('#ftPurRet').text('-' + fmt(Math.abs(g.gPurRet), 0));
                $('#ftSold').text('-' + fmt(Math.abs(g.gSold), 0));
                $('#ftDonated').text('-' + fmt(Math.abs(g.gDonated), 0));
                $('#ftSaleRet').text('+' + fmt(Math.abs(g.gSaleRet), 0));
                $('#ftAdjusted').text((g.gAdj >= 0 ? '+' : '-') + fmt(Math.abs(g.gAdj), 0));
                $('#ftBalance').text(fmt(g.gBal, 0));
                $('#ftPurAmt').text(fmtPKR(g.gPurAmt));
                $('#ftSaleAmt').text(fmtPKR(g.gSaleAmt));
                $('#ftStockVal').text(fmtPKR(g.gVal));
            }

            // ── Populate warehouse dropdown ──────────────────────────────────────
            // Pre-populated from Blade (allLocations) on page load.
            // AJAX response may return updated locations – only apply if dropdown is currently empty (no optgroups).
            function populateWarehouseFilter(warehouses) {
                var $sel = $('#filterWarehouse');
                var hasGroups = $sel.find('optgroup').length > 0;
                if (hasGroups) {
                    // Already pre-populated from Blade – just ensure select2 is refreshed
                    $sel.trigger('change.select2');
                    return;
                }
                // Fallback: populate from AJAX response (first search before page has options)
                _warehousesLoaded = true;
                $sel.find('option:not(:first)').remove();

                var shops = warehouses.filter(function(w){ return w.type === 'shop'; });
                var whs   = warehouses.filter(function(w){ return w.type !== 'shop'; });

                if (shops.length > 0) {
                    var $gShop = $('<optgroup label="🏪 Shops (Retail)"></optgroup>');
                    shops.forEach(function(w) {
                        $gShop.append('<option value="' + w.id + '" data-type="shop">🏪 ' + w.warehouse_name + '</option>');
                    });
                    $sel.append($gShop);
                }
                if (whs.length > 0) {
                    var $gWh = $('<optgroup label="🏭 Warehouses (Storage)"></optgroup>');
                    whs.forEach(function(w) {
                        $gWh.append('<option value="' + w.id + '" data-type="warehouse">🏭 ' + w.warehouse_name + '</option>');
                    });
                    $sel.append($gWh);
                }
                $sel.trigger('change.select2');
            }

            // ── Fetch data ───────────────────────────────────────────────────────
            function fetchReport() {
                var productId = $('#product_id').val() || 'all';
                var categoryId = $('#filterCategory').val() || 'all';
                var subCategoryId = $('#filterSubCategory').val() || 'all';
                var brandId = $('#filterBrand').val() || 'all';
                var warehouseId = $('#filterWarehouse').val() || 'all';
                var statusValue = $('#filterStatus').val() || 'all';
                
                console.log("Fetching Report with Filters:", {
                    productId, categoryId, subCategoryId, brandId, warehouseId, statusValue
                });
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $('#loaderOverlay').css('display', 'flex');

                $.ajax({
                    url: "{{ route('report.item_stock.fetch') }}",
                    type: 'POST',
                   data: {
    _token: "{{ csrf_token() }}",
    product_id: productId,
    category_id: categoryId,
    sub_category_id: subCategoryId,
    brand_id: brandId,
    warehouse_id: warehouseId,
    status: statusValue,
    start_date: startDate,
    end_date: endDate,
    branch_id: IS_SUPER_ADMIN ? ($('#filterBranch').val() || 'all') : 'all', // ← ADD THIS
},
                    success: function(res) {
                        console.log("Report Data Received:", res);
                        $('#loaderOverlay').hide();
                        
                        renderRows(res.data || []);
                        populateWarehouseFilter(res.warehouses || []);
                        
                        // Update Top Cards with Backend Totals
                        $('#stripPurchase').text(fmtPKR(res.grand_purchase || 0));
                        $('#stripSale').text(fmtPKR(res.grand_sale || 0));
                        $('#stripValue').text(fmtPKR(res.grand_total || 0));
                        $('#kpiValue').text('PKR ' + fmt(res.grand_total || 0, 0));
                        $('#totalsStrip').show();
                    },
                    error: function(xhr) {
                        $('#loaderOverlay').hide();
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to load report data.' });
                    }
                });
            }
            function resetKpi() {
                ['kpiTotal', 'kpiValue', 'kpiWarehouses', 'kpiLow', 'kpiOut'].forEach(function(id) {
                    document.getElementById(id).textContent = '0';
                });
                $('#stripPurchase,#stripSale,#stripValue').text('PKR 0.00');
                $('#totalsStrip').hide();
                window._gTotals = null;
            }

            // ── Dynamic Dropdown Logic: Cat -> Sub -> Brand -> Product ──────────
            function updateFilters(changedElement) {
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
                // Reset Sub-Cat if no longer valid
                if (subId !== 'all' && !validSubs.has(subId)) {
                    $('#filterSubCategory').val('all').trigger('change.select2');
                    subId = 'all';
                }

                // 2. Build map of valid Brands and Products based on Cat/Sub selection
                var validBrands = new Set();
                var validProds  = new Set();

                $('#product_id option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;

                    var pCat   = $opt.attr('data-cat');
                    var pSub   = $opt.attr('data-sub');
                    var pBrand = $opt.attr('data-brand');

                    // A product is valid for the current Cat/Sub selection if it matches both
                    var matchCat = (catId === 'all' || pCat == catId);
                    var matchSub = (subId === 'all' || pSub == subId);

                    if (matchCat && matchSub) {
                        if (pBrand) validBrands.add(pBrand);
                        
                        // Additionally, for the product itself to be visible, it must also match the Brand selection
                        if (brandId === 'all' || pBrand == brandId) {
                            validProds.add($opt.val());
                        }
                    }
                });

                // 3. Filter Brands based on validBrands set
                $('#filterBrand option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validBrands.has($opt.val())) {
                        $opt.show().prop('disabled', false);
                    } else {
                        $opt.hide().prop('disabled', true);
                    }
                });
                // Reset Brand if no longer valid
                if (brandId !== 'all' && !validBrands.has(brandId)) {
                    $('#filterBrand').val('all').trigger('change.select2');
                    brandId = 'all';
                }

                // 4. Filter Products based on validProds set
                $('#product_id option').each(function() {
                    var $opt = $(this);
                    if ($opt.val() === 'all') return;
                    if (validProds.has($opt.val())) {
                        $opt.show().prop('disabled', false);
                    } else {
                        $opt.hide().prop('disabled', true);
                    }
                });
                // Product ID doesn't usually need a reset-to-all unless we want to be strict

                // 5. Refresh Select2 state (required to hide/show options properly in UI)
                $('.select2-global, .select2-product').trigger('change.select2');
            }

            // ── Event bindings ───────────────────────────────────────────────────
            $('#btnSearch').on('click', function() {
                fetchReport();
            });

            // Handle changes with cascading logic
            $('#filterCategory').on('change', function() {
                updateFilters('cat');
            });

            $('#filterSubCategory').on('change', function() {
                updateFilters('sub');
            });

            $('#filterBrand').on('change', function() {
                updateFilters('brand');
            });

            if (IS_SUPER_ADMIN) {
                $('#filterBranch').on('change', function() {
                    // Just update warehouse list if needed, or wait for search
                });
            }

            $('#btnReset').on('click', function() {
                // Clear inputs
                $('#start_date').val(firstOfM);
                $('#end_date').val(todayStr);
                $('#filterStatus').val('all');

                // Reset Select2s
                $('#product_id').val('all').trigger('change.select2');
                $('#filterWarehouse').val('all').trigger('change.select2');
                $('#filterCategory').val('all').trigger('change.select2');
                $('#filterSubCategory').val('all').trigger('change.select2');
                $('#filterBrand').val('all').trigger('change.select2');
                
                if (IS_SUPER_ADMIN) $('#filterBranch').val('all').trigger('change.select2');
                
                _warehousesLoaded = false;
                updateFilters(); // Refresh product list visibility
                fetchReport();
            });

            // ── Server-Side Excel Export ─────────────────────────────────────────────────────────
            $('#btnExportExcel').on('click', function() {
                var startDate   = $('#start_date').val();
                var endDate     = $('#end_date').val();

                if (!startDate || !endDate) {
                    Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a date range first.' });
                    return;
                }

                const params = new URLSearchParams({
                    product_id: $('#filterProduct').val() || 'all',
                    category_id: $('#filterCategory').val() || 'all',
                    sub_category_id: $('#filterSubCategory').val() || 'all',
                    brand_id: $('#filterBrand').val() || 'all',
                    warehouse_id: $('#filterWarehouse').val() || 'all',
                    start_date: startDate,
                    end_date: endDate,
                    branch_id: IS_SUPER_ADMIN ? ($('#filterBranch').val() || 'all') : 'all'
                });

                window.location.href = `{{ route('report.item_stock.export.excel') }}?${params}`;
            });

            // ── Server-Side PDF Export ─────────────────────────────────────────────────────────
            $('#btnExportPdf').on('click', function() {
                var startDate   = $('#start_date').val();
                var endDate     = $('#end_date').val();

                if (!startDate || !endDate) {
                    Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a date range first.' });
                    return;
                }

                const params = new URLSearchParams({
                    product_id: $('#filterProduct').val() || 'all',
                    category_id: $('#filterCategory').val() || 'all',
                    sub_category_id: $('#filterSubCategory').val() || 'all',
                    brand_id: $('#filterBrand').val() || 'all',
                    warehouse_id: $('#filterWarehouse').val() || 'all',
                    start_date: startDate,
                    end_date: endDate,
                    branch_id: IS_SUPER_ADMIN ? ($('#filterBranch').val() || 'all') : 'all'
                });

                window.location.href = `{{ route('report.item_stock.export.pdf') }}?${params}`;
            });

            // ── Auto-load on page open ─────────────────────────────────────────
            updateFilters(); // Initialize dropdown states
            // fetchReport();
        });


        function printReport() {
            const wrap = document.getElementById('stockTableWrap');
            if (wrap) wrap.style.display = 'block';
            const strip = document.getElementById('totalsStrip');
            if (strip) strip.style.display = 'flex';
            window.print();
        }
    </script>
@endsection
