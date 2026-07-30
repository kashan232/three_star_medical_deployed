@extends('admin_panel.layout.app')
@section('content')
<style>
    /* ── Design tokens ──────────────────────────────────────────────────── */
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
        color: #fff;
    }

    .rpt-header p {
        margin: 3px 0 0;
        font-size: .84rem;
        opacity: .82;
        color: #fff;
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
        color: #fff;
    }

    /* ── KPI tiles ───────────────────────────────────────────────────────── */
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

    .kpi-card.blue { border-color: var(--c-primary); }
    .kpi-card.green { border-color: var(--c-success); }
    .kpi-card.amber { border-color: var(--c-warning); }
    .kpi-card.red { border-color: var(--c-danger); }
    .kpi-card.purple { border-color: var(--c-purple); }

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
        margin-bottom: 4px;
    }

    .kpi-card.blue .kpi-icon { color: var(--c-primary); }
    .kpi-card.green .kpi-icon { color: var(--c-success); }
    .kpi-card.amber .kpi-icon { color: var(--c-warning); }
    .kpi-card.red .kpi-icon { color: var(--c-danger); }
    .kpi-card.purple .kpi-icon { color: var(--c-purple); }

    @media(max-width:1000px) {
        .kpi-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media(max-width:640px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* ── Filter card ─────────────────────────────────────────────────────── */
    .filter-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 18px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .filter-inputs-container {
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

    .filter-row:last-of-type {
        justify-content: flex-start;
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
        flex-direction: row;
        gap: 8px;
        margin-left: auto;
        align-items: center;
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
        padding: 6px 14px;
        min-width: 95px;
        height: 32px;
        border: none;
        transition: background-color 0.2s, transform 0.1s;
        white-space: nowrap;
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

    /* ── Product profile card ────────────────────────────────────────────── */
    .prod-profile {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #38bdf8 100%);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 20px;
        color: white;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px;
        align-items: center;
        box-shadow: 0 6px 24px rgba(37, 99, 235, .2);
    }

    .prod-profile h5 {
        font-size: 1.2rem;
        font-weight: 800;
        margin: 0 0 4px;
        color: #fff;
    }

    .prod-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 8px;
    }

    .prod-meta span {
        font-size: .82rem;
        opacity: .9;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .period-badge {
        background: rgba(255, 255, 255, .18);
        border-radius: 8px;
        padding: 6px 14px;
        font-size: .8rem;
        font-weight: 600;
        border: 1px solid rgba(255, 255, 255, .25);
        white-space: nowrap;
        color: #fff;
    }

    /* ── Table card ──────────────────────────────────────────────────────── */
    .table-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px 20px 14px;
        box-shadow: var(--card-shadow);
    }

    #ledgerTable {
        width: 100%;
        border-collapse: collapse;
        font-size: .82rem;
    }

    #ledgerTable thead th {
        background: #1e3a8a;
        color: #fff;
        font-size: .73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        white-space: nowrap;
        padding: 10px 11px;
        border: 1px solid #1e3a8a;
    }

    #ledgerTable thead th:first-child { border-radius: 8px 0 0 0; }
    #ledgerTable thead th:last-child  { border-radius: 0 8px 0 0; }

    #ledgerTable tbody tr {
        font-size: .82rem;
        transition: background .1s;
    }

    #ledgerTable tbody tr:hover td {
        background: #eff6ff !important;
    }

    #ledgerTable tbody td {
        padding: 8px 11px;
        vertical-align: middle;
        border: 1px solid #f1f5f9;
    }

    /* Striped alternating rows like DataTables */
    #ledgerTable tbody tr:nth-child(odd) td  { background: #fff; }
    #ledgerTable tbody tr:nth-child(even) td { background: #f8fafc; }

    /* Left-border accent per row type (subtle, like item_stock status coloring) */
    .row-opening td:first-child  { border-left: 3px solid #2563eb !important; }
    .row-purchase td:first-child { border-left: 3px solid #16a34a !important; }
    .row-sale td:first-child     { border-left: 3px solid #dc2626 !important; }
    .row-dc td:first-child       { border-left: 3px solid #d97706 !important; }
    .row-sr td:first-child       { border-left: 3px solid #0891b2 !important; }
    .row-pr td:first-child       { border-left: 3px solid #7c3aed !important; }
    .row-closing td              { background: #f8fafc !important; font-weight: 700 !important; border-top: 2px solid #e2e8f0 !important; }
    .row-closing td:first-child  { border-left: 3px solid #1e3a8a !important; }
    .row-opening td              { font-weight: 700 !important; }

    /* tfoot totals row — same as item_stock_report #stockTable tfoot th */
    #ledgerTable tfoot th {
        background: #f8fafc;
        font-size: .8rem;
        font-weight: 700;
        color: #1e293b;
        padding: 9px 11px;
        border-top: 2px solid #e2e8f0;
        border: 1px solid #e2e8f0;
    }
    #ledgerTable tfoot th.tr { text-align: right; }

    #ledgerTable tbody td.tr,
    #ledgerTable thead th.tr {
        text-align: right;
    }

    /* ── Transaction type badges ─────────────────────────────────────────── */
    .tx-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 18px;
        font-size: .7rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .tx-opening  { background: #dbeafe; color: #1e40af; }
    .tx-purchase { background: #d1fae5; color: #065f46; }
    .tx-sale     { background: #fee2e2; color: #991b1b; }
    .tx-dc       { background: #fef3c7; color: #92400e; }
    .tx-sr       { background: #ccfbf1; color: #0f766e; }
    .tx-pr       { background: #ede9fe; color: #5b21b6; }

    /* ── Amounts ─────────────────────────────────────────────────────────── */
    .qty-in    { color: #16a34a; font-weight: 700; }
    .qty-out   { color: #dc2626; font-weight: 700; }
    .qty-nil   { color: #cbd5e1; }
    .bal-pos   { color: #16a34a; font-weight: 700; }
    .bal-neg   { color: #dc2626; font-weight: 700; }
    .bal-zero  { color: #64748b; }

    /* ── Ref badge ───────────────────────────────────────────────────────── */
    .ref-badge {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 2px 8px;
        font-size: .73rem;
        color: #0f172a;
        font-weight: 600;
        font-family: monospace;
    }

    /* ── Total strip (below table, like item_stock grand totals bar) ─────── */
    .total-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 12px;
        padding: 10px 14px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: .8rem;
    }
    .total-strip .ts-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .total-strip .ts-label { color: #64748b; font-weight: 600; }
    .total-strip .ts-val   { color: #1e293b; font-weight: 700; }
    .total-strip .ts-val.green { color: #16a34a; }
    .total-strip .ts-val.red   { color: #dc2626; }
    .total-strip .ts-val.blue  { color: #1e3a8a; }
    .total-strip .ts-sep { color: #e2e8f0; }

    /* ── Empty state ─────────────────────────────────────────────────────── */
    .empty-state { text-align: center; padding: 60px; color: #64748b; }
    .empty-state svg { width: 52px; opacity: .3; margin-bottom: 12px; }

    /* ── Bottom bar ──────────────────────────────────────────────────────── */
    .bottom-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 14px;
        flex-wrap: wrap;
        gap: 8px;
    }

    .bottom-bar small { font-size: .78rem; color: #64748b; }


    /* ── Loader overlay (full screen matching item stock report) ────────── */
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

    /* ── Print ───────────────────────────────────────────────────────────── */
    @media print {
        .filter-card,
        .btn-srp,
        .rpt-header {
            display: none !important;
        }

        .main-content,
        .main-content-inner,
        .container-fluid {
            display: block !important;
            width: 100% !important;
        }

        .print-header {
            display: block !important;
        }

        #ledgerTable thead th {
            background: #f1f5f9 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border: 1px solid #000 !important;
        }
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid">

            <!-- Print Header (only visible when printing) -->
            <div class="print-header" style="display:none; margin-bottom:16px;">
                <h2 style="margin:0;font-size:18px;font-weight:700;">📦 Product Ledger Report</h2>
                <p id="printSubtitle" style="margin:4px 0 0;font-size:12px;color:#555;">
                    Printed: {{ now()->format('d M Y H:i') }}
                </p>
            </div>

            <!-- Page Header -->
            <div class="rpt-header" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h3 style="margin:0; font-size:1.35rem; font-weight:700; color:#1e293b;"><i class="fas fa-history me-2"></i> Product Ledger Report</h3>
                    <p style="margin:4px 0 0; font-size:.85rem; color:#64748b;">Chronological stock movement ledger — purchases, sales, delivery challans &amp; returns</p>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="button" id="btnExcel" style="background:#059669; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;" title="Export Excel Spreadsheet"><i class="fas fa-file-excel me-1"></i> Excel Export</button>
                    <button type="button" id="btnSummaryPdf" class="btn-pdf" style="background:#10b981; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;" title="Export Summary PDF"><i class="fas fa-file-pdf me-1"></i> Summary PDF</button>
                    <button type="button" id="btnPdf" style="background:#7c3aed; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;" title="Export Detail PDF"><i class="fas fa-list-alt me-1"></i> Detail PDF</button>
                    <button type="button" id="btnPrint" class="btn-print" style="background:#6366f1; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; cursor:pointer;" title="Print View">🖨 Print</button>
                </div>
            </div>

    {{-- Filter Card --}}
    <div class="filter-card">
        <div class="filter-inputs-container">
            <!-- Row 1: Product Attributes -->
            <div class="filter-row">
                <div class="filter-group">
                    <label>Category</label>
                    <select id="filterCategory" class="form-control select2-global">
                        <option value="all">All Category</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sub-Category</label>
                    <select id="filterSubCategory" class="form-control select2-global">
                        <option value="all">All Sub-category</option>
                        @foreach($subCategories as $sc)
                            <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group">
                    <label>Brand / Company</label>
                    <select id="filterBrand" class="form-control select2-global">
                        <option value="all">All Company</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-group" style="flex: 2; min-width: 250px;">
                    <label>Product</label>
                    <select id="sel_product" class="form-control select2-product" multiple="multiple">
                        <option value=""></option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}"
                                data-cat="{{ $p->category_id }}"
                                data-sub="{{ $p->sub_category_id }}"
                                data-brand="{{ $p->brand_id }}"
                                data-code="{{ $p->item_code }}"
                                data-name="{{ $p->item_name }}">
                                {{ $p->item_code }} — {{ $p->item_name }} {{ $p->brand->name ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Row 2: Parameters & Action Buttons inline -->
            <div class="filter-row">
                <!-- Warehouse -->
                <div class="filter-group">
                    <label>Location (Shop / Warehouse)</label>
                    <select id="sel_warehouse" class="form-control select2-global">
                        <option value="">All Locations</option>
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
                <!-- Branch -->
                @if($isSuperAdmin)
                <div class="filter-group">
                    <label>Branch</label>
                    <select id="filterBranch" class="form-control select2-global">
                        <option value="all">— All Branches —</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <!-- Start Date -->
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" id="sel_start" class="form-control">
                </div>
                <!-- End Date -->
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" id="sel_end" class="form-control">
                </div>
                <!-- Status -->
                <div class="filter-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="all">All Status</option>
                        <option value="normal">✅ Normal</option>
                        <option value="low_stock">⚠️ Low Stock</option>
                        <option value="out_of_stock">❌ Out of Stock</option>
                    </select>
                </div>
                <!-- Spacer -->
                <div style="flex: 1;"></div>
                <!-- Action Buttons -->
                <button type="button" class="btn-filter-action btn-filter-search" id="btnGenerate">🔍 Search</button>
                <button type="button" class="btn-filter-action btn-filter-reset" id="btnReset">↺ Reset</button>
            </div>
        </div>
    </div>

    <!-- Loader Overlay -->
    <div class="loader-overlay" id="pledLoader">
        <div class="loader-box">
            <div class="spinner-border" role="status"></div>
            <p>Building product ledger…</p>
        </div>
    </div>

    {{-- Result --}}
    <div id="pledResult" style="display:none;">

        {{-- Product Profile --}}
        <div class="prod-profile" id="prodProfile"></div>

        {{-- KPI Grid --}}
        <div class="kpi-grid" id="kpiRow"></div>

        {{-- Ledger Table --}}
        <div class="table-card">
            <div class="table-responsive">
                <table id="ledgerTable" class="table table-bordered" style="width:100%;">
                    <thead>
                        <tr>
                            <th style="width:100px;">Date</th>
                            <th>Description</th>
                            <th style="width:130px;">Ref / Doc #</th>
                            <th class="tr" style="width:90px;">Qty IN</th>
                            <th class="tr" style="width:90px;">Qty OUT</th>
                            <th class="tr" style="width:110px;">Sale Price</th>
                            <th class="tr" style="width:110px;">Cost Price</th>
                            <th class="tr" style="width:120px;">Balance (Pcs)</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerBody"></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Grand Totals:</th>
                            <th class="tr" id="ftQtyIn">0</th>
                            <th class="tr" id="ftQtyOut">0</th>
                            <th class="tr" id="ftSaleVal">—</th>
                            <th class="tr" id="ftCostVal">—</th>
                            <th class="tr" id="ftBalance">0</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            {{-- Total strip below table --}}
            <div class="total-strip" id="totalStrip" style="display:none;">
                <div class="ts-item">
                    <span class="ts-label">Opening Stock:</span>
                    <span class="ts-val blue" id="tsOpening">—</span>
                </div>
                <span class="ts-sep">|</span>
                <div class="ts-item">
                    <span class="ts-label">Total IN:</span>
                    <span class="ts-val green" id="tsIn">—</span>
                </div>
                <span class="ts-sep">|</span>
                <div class="ts-item">
                    <span class="ts-label">Total OUT:</span>
                    <span class="ts-val red" id="tsOut">—</span>
                </div>
                <span class="ts-sep">|</span>
                <div class="ts-item">
                    <span class="ts-label">Closing Stock:</span>
                    <span class="ts-val blue" id="tsClosing">—</span>
                </div>
                <span class="ts-sep">|</span>
                <div class="ts-item">
                    <span class="ts-label">Sale Revenue:</span>
                    <span class="ts-val" id="tsSaleVal">—</span>
                </div>
                <span class="ts-sep">|</span>
                <div class="ts-item">
                    <span class="ts-label">Transactions:</span>
                    <span class="ts-val" id="tsTxCount">—</span>
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="bottom-bar">
            <small id="genTime"></small>
            <small id="txCount"></small>
        </div>
    </div>

        </div>
    </div>
</div>
@endsection

@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
(function () {
    // ── Helpers ────────────────────────────────────────────────────────────
    const fmtQty = n => n == null ? '' : parseFloat(n).toLocaleString('en-PK', {minimumFractionDigits:0, maximumFractionDigits:2});
    const fmtAmt = n => n == null ? '' : 'Rs ' + parseFloat(n).toLocaleString('en-PK', {minimumFractionDigits:2, maximumFractionDigits:2});
    const fmtDate = d => { if (!d) return '-'; const p = d.split('-'); return p[2]+'/'+p[1]+'/'+p[0]; };

    // ── Date defaults ───────────────────────────────────────────────────────
    const now = new Date();
    const pad = n => String(n).padStart(2,'0');
    const today       = now.getFullYear()+'-'+pad(now.getMonth()+1)+'-'+pad(now.getDate());
    const firstOfYear = now.getFullYear()+'-01-01';
    document.getElementById('sel_start').value = firstOfYear;
    document.getElementById('sel_end').value   = today;

    // ── Select2 ─────────────────────────────────────────────────────────────
    $(document).ready(function(){
        $('.select2-global').select2({ width: '100%' });
        $('.select2-product').select2({
            placeholder: '— Select Product —',
            allowClear: true,
            width: '100%'
        });
    });

    // ── Dynamic Dropdown Logic: Cat -> Sub -> Brand -> Product ──────────
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
        // Reset Sub-Cat if no longer valid
        if (subId !== 'all' && !validSubs.has(subId)) {
            $('#filterSubCategory').val('all').trigger('change.select2');
            subId = 'all';
        }

        // 2. Build map of valid Brands and Products based on Cat/Sub selection
        var validBrands = new Set();
        var validProds  = new Set();

        $('#sel_product option').each(function() {
            var $opt = $(this);
            if (!$opt.val()) return; // Skip placeholder

            var pCat   = $opt.attr('data-cat');
            var pSub   = $opt.attr('data-sub');
            var pBrand = $opt.attr('data-brand');

            // A product is valid if it matches Cat/Sub selection
            var matchCat = (catId === 'all' || pCat == catId);
            var matchSub = (subId === 'all' || pSub == subId);

            if (matchCat && matchSub) {
                if (pBrand) validBrands.add(pBrand);
                
                // Visible if matches brand selection
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
        $('#sel_product option').each(function() {
            var $opt = $(this);
            if (!$opt.val()) return;
            if (validProds.has($opt.val())) {
                $opt.show().prop('disabled', false);
            } else {
                $opt.hide().prop('disabled', true);
            }
        });

        // 5. Refresh Select2 state
        $('.select2-global, .select2-product').trigger('change.select2');
    }

    // Bind change events
    $('#filterCategory').on('change', updateFilters);
    $('#filterSubCategory').on('change', updateFilters);
    $('#filterBrand').on('change', updateFilters);

    // ── Transaction type map ────────────────────────────────────────────────
    const txInfo = {
        opening:          { label:'📋 Opening Balance', cls:'tx-opening',  rowCls:'row-opening'  },
        purchase:         { label:'📦 Purchase GRN',    cls:'tx-purchase', rowCls:'row-purchase' },
        sale:             { label:'🧾 Sale Invoice',    cls:'tx-sale',     rowCls:'row-sale'     },
        delivery_challan: { label:'🚚 Delivery Challan',cls:'tx-dc',       rowCls:'row-dc'       },
        sale_return:      { label:'↩ Sale Return',      cls:'tx-sr',       rowCls:'row-sr'       },
        purchase_return:  { label:'↩ Purchase Return',  cls:'tx-pr',       rowCls:'row-pr'       },
        closing:          { label:'🏁 Closing Balance',  cls:'tx-opening',  rowCls:'row-closing'  },
    };

    function balClass(b) {
        if (Math.abs(b) < 0.001) return 'bal-zero';
        return b > 0 ? 'bal-pos' : 'bal-neg';
    }

    let lastData = null;

    // ── Reset ────────────────────────────────────────────────────────────────
    document.getElementById('btnReset').addEventListener('click', function(){
        $('#sel_product').val('').trigger('change.select2');
        $('#filterCategory').val('all').trigger('change.select2');
        $('#filterSubCategory').val('all').trigger('change.select2');
        $('#filterBrand').val('all').trigger('change.select2');
        $('#filterStatus').val('all');
        if (document.getElementById('filterBranch')) {
            $('#filterBranch').val('all').trigger('change.select2');
        }
        document.getElementById('sel_start').value = firstOfYear;
        document.getElementById('sel_end').value   = today;
        document.getElementById('sel_warehouse').value = '';
        document.getElementById('pledResult').style.display = 'none';
        lastData = null;
        updateFilters();
    });

        // ── Generate ────────────────────────────────────────────────────────────
    document.getElementById('btnGenerate').addEventListener('click', function(){
        const pids  = $('#sel_product').val();
        const catId = document.getElementById('filterCategory').value;
        const subId = document.getElementById('filterSubCategory').value;
        const bndId = document.getElementById('filterBrand').value;
        const stId  = document.getElementById('filterStatus').value;
        const sd    = document.getElementById('sel_start').value;
        const ed    = document.getElementById('sel_end').value;
        const wid   = document.getElementById('sel_warehouse').value;
        const bid   = document.getElementById('filterBranch') ? document.getElementById('filterBranch').value : 'all';

        document.getElementById('pledLoader').style.display  = 'flex';
        document.getElementById('pledResult').style.display  = 'none';

        const qs = new URLSearchParams({ start_date: sd, end_date: ed });
        if (pids && pids.length > 0) {
            if (Array.isArray(pids)) {
                qs.set('product_id', pids.join(','));
            } else {
                qs.set('product_id', pids);
            }
        }
        if (catId && catId !== 'all') qs.set('category_id', catId);
        if (subId && subId !== 'all') qs.set('sub_category_id', subId);
        if (bndId && bndId !== 'all') qs.set('brand_id', bndId);
        if (stId  && stId  !== 'all') qs.set('status', stId);
        if (wid)                      qs.set('warehouse_id', wid);
        if (bid   && bid   !== 'all') qs.set('branch_id', bid);

        fetch('{{ route("report.product.ledger.fetch") }}?' + qs.toString(), {
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            document.getElementById('pledLoader').style.display = 'none';
            if (!res.success) { alert(res.message || 'Error'); return; }
            lastData = res;
            renderLedger(res);
        })
        .catch(err => {
            document.getElementById('pledLoader').style.display = 'none';
            alert('Network error: ' + err.message);
        });
    });

    function renderLedger(res) {
        const { rows, summary, is_consolidated, product_count, products_data } = res;
        const p = summary.product;

        // ── Product profile ─────────────────────────────────────────────────
        const headerTitle = is_consolidated ? `📦 Consolidated Product Ledger (${product_count} Products)` : `📦 ${p.item_name}`;
        document.getElementById('prodProfile').innerHTML = `
            <div>
                <h5>${headerTitle}</h5>
                <div class="prod-meta">
                    <span>🏷 Code: <b>${p.item_code || '-'}</b></span>
                    <span>🏷 Company: <b>${p.brand_name || '-'}</b></span>
                    <span>📂 Category: <b>${p.category_name || '-'}</b></span>
                    <span>📏 Unit: <b>${p.unit_name || 'pcs'}</b></span>
                </div>
            </div>
            <div>
                <div class="period-badge">
                    ${summary.period_start ? fmtDate(summary.period_start) : 'All time'}
                    ${summary.period_start ? ' → ' + fmtDate(summary.period_end) : ''}
                </div>
            </div>`;

        // ── KPI tiles ────────────────────────────────────────────────────────
        document.getElementById('kpiRow').innerHTML = `
            <div class="kpi-card blue">
                <span class="kpi-icon"><i class="fas fa-boxes"></i></span>
                <span class="kpi-label">Opening Stock</span>
                <span class="kpi-value">${fmtQty(summary.opening_balance)} <span style="font-size:.75rem;font-weight:400;color:#64748b">pcs</span></span>
                <span class="kpi-sub">Before period start</span>
            </div>
            <div class="kpi-card green">
                <span class="kpi-icon"><i class="fas fa-arrow-circle-down"></i></span>
                <span class="kpi-label">Total IN</span>
                <span class="kpi-value">${fmtQty(summary.total_qty_in)} <span style="font-size:.75rem;font-weight:400;color:#64748b">pcs</span></span>
                <span class="kpi-sub">Purchases + Returns</span>
            </div>
            <div class="kpi-card red">
                <span class="kpi-icon"><i class="fas fa-arrow-circle-up"></i></span>
                <span class="kpi-label">Total OUT</span>
                <span class="kpi-value">${fmtQty(summary.total_qty_out)} <span style="font-size:.75rem;font-weight:400;color:#64748b">pcs</span></span>
                <span class="kpi-sub">Sales + DCs + Returns</span>
            </div>
            <div class="kpi-card purple">
                <span class="kpi-icon"><i class="fas fa-flag-checkered"></i></span>
                <span class="kpi-label">Closing Stock</span>
                <span class="kpi-value ${summary.closing_balance < 0 ? 'text-danger' : ''}">${fmtQty(summary.closing_balance)} <span style="font-size:.75rem;font-weight:400;color:#64748b">pcs</span></span>
                <span class="kpi-sub">End of period</span>
            </div>
            <div class="kpi-card amber">
                <span class="kpi-icon"><i class="fas fa-wallet"></i></span>
                <span class="kpi-label">Total Sale Value</span>
                <span class="kpi-value" style="font-size:1.3rem;">${fmtAmt(summary.total_sale_value)}</span>
                <span class="kpi-sub">Revenue in period</span>
            </div>`;

        // ── Ledger rows ──────────────────────────────────────────────────────
        const tbody = document.getElementById('ledgerBody');
        tbody.innerHTML = '';

        if ((!rows || rows.length === 0) && (!products_data || products_data.length === 0)) {
            tbody.innerHTML = `<tr><td colspan="8" class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <div>No transactions found for the selected filters.</div>
            </td></tr>`;
            document.getElementById('totalStrip').style.display = 'none';
        } else if (products_data && products_data.length > 0 && is_consolidated) {
            // ── GROUPED PRODUCT VIEW (MULTI-PRODUCT) ──────────────────────────
            let grandIn = 0, grandOut = 0, grandSaleVal = 0;

            products_data.forEach((pData, idx) => {
                const prod = pData.product;

                // Section Header Row
                const headTr = document.createElement('tr');
                headTr.className = 'product-group-header';
                headTr.innerHTML = `
                    <td colspan="8" style="background:#1e3a8a; color:#ffffff; padding:10px 16px; font-size:0.95rem; font-weight:700; border-top: 3px solid #0f172a;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span>📦 Product #${idx+1}: [${escHtml(prod.item_code)}] ${escHtml(prod.item_name)}
                                <span style="font-weight:400; font-size:0.82rem; margin-left:12px; color:#cbd5e1;">
                                    Company: <b>${escHtml(prod.brand_name)}</b> | Category: <b>${escHtml(prod.category_name)}</b> | Unit: <b>${escHtml(prod.unit_name)}</b>
                                </span>
                            </span>
                            <span style="background:rgba(255,255,255,0.2); padding:3px 12px; border-radius:12px; font-size:0.82rem;">
                                Closing Stock: <b>${fmtQty(pData.closing_balance)} pcs</b>
                            </span>
                        </div>
                    </td>`;
                tbody.appendChild(headTr);

                // Transaction Rows for this Product
                pData.rows.forEach(r => {
                    const info       = txInfo[r.type] || txInfo.purchase;
                    const qtyInHtml  = r.qty_in  ? `<span class="qty-in">+${fmtQty(r.qty_in)}</span>`  : `<span class="qty-nil">—</span>`;
                    const qtyOutHtml = r.qty_out ? `<span class="qty-out">-${fmtQty(r.qty_out)}</span>` : `<span class="qty-nil">—</span>`;
                    const salePriceHtml = r.sale_price ? `<span style="color:#0f172a;">${fmtAmt(r.sale_price)}</span>` : `<span class="qty-nil">—</span>`;
                    const costPriceHtml = r.cost_price ? `<span style="color:#64748b;">${fmtAmt(r.cost_price)}</span>` : `<span class="qty-nil">—</span>`;
                    const bal = parseFloat(r.balance ?? 0);
                    const balHtml = `<span class="${balClass(bal)}">${fmtQty(bal)}</span>`;
                    const descHtml = r.type === 'opening'
                        ? `<span class="tx-badge ${info.cls}">${info.label}</span>`
                        : `<span class="tx-badge ${info.cls}">${info.label}</span>
                           <span style="margin-left:6px;color:#334155;font-weight:500;font-size:.82rem;">${escHtml(r.description)}</span>`;

                    const tr = document.createElement('tr');
                    tr.className = info.rowCls;
                    tr.innerHTML = `
                        <td style="white-space:nowrap;">${fmtDate(r.date)}</td>
                        <td>${descHtml}</td>
                        <td><span class="ref-badge">${escHtml(r.ref || '—')}</span></td>
                        <td class="tr">${qtyInHtml}</td>
                        <td class="tr">${qtyOutHtml}</td>
                        <td class="tr">${salePriceHtml}</td>
                        <td class="tr">${costPriceHtml}</td>
                        <td class="tr">${balHtml}</td>`;
                    tbody.appendChild(tr);

                    if (r.type !== 'opening') {
                        grandIn  += parseFloat(r.qty_in  ?? 0);
                        grandOut += parseFloat(r.qty_out ?? 0);
                        if (r.sale_price && r.qty_out) grandSaleVal += r.sale_price * r.qty_out;
                    }
                });

                // Product Subtotal Row
                const subTr = document.createElement('tr');
                subTr.style.background = '#f1f5f9';
                subTr.style.fontWeight = '700';
                subTr.style.borderBottom = '2px solid #cbd5e1';
                subTr.innerHTML = `
                    <td colspan="3" style="color:#334155; font-size:0.85rem; text-align:right;">Subtotal for [${escHtml(prod.item_code)}] ${escHtml(prod.item_name)}:</td>
                    <td class="tr" style="color:#16a34a;">+${fmtQty(pData.total_qty_in)}</td>
                    <td class="tr" style="color:#dc2626;">-${fmtQty(pData.total_qty_out)}</td>
                    <td class="tr" colspan="2"></td>
                    <td class="tr" style="color:#1e3a8a; font-weight:700;">${fmtQty(pData.closing_balance)} pcs</td>`;
                tbody.appendChild(subTr);
            });

            // Update footer totals
            const closingBal = parseFloat(summary.closing_balance ?? 0);
            document.getElementById('ftQtyIn').innerHTML   = `<span class="qty-in">${fmtQty(grandIn)}</span>`;
            document.getElementById('ftQtyOut').innerHTML  = `<span class="qty-out">${fmtQty(grandOut)}</span>`;
            document.getElementById('ftSaleVal').innerHTML = grandSaleVal ? fmtAmt(grandSaleVal) : '—';
            document.getElementById('ftCostVal').innerHTML = '—';
            document.getElementById('ftBalance').innerHTML = `<span class="${balClass(closingBal)}">${fmtQty(closingBal)}</span>`;
        } else {
            // ── SINGLE PRODUCT VIEW ──────────────────────────────────────────
            let grandIn = 0, grandOut = 0, grandSaleVal = 0;
            rows.forEach(r => {
                const info     = txInfo[r.type] || txInfo.purchase;
                const qtyInHtml  = r.qty_in  ? `<span class="qty-in">+${fmtQty(r.qty_in)}</span>`  : `<span class="qty-nil">—</span>`;
                const qtyOutHtml = r.qty_out ? `<span class="qty-out">-${fmtQty(r.qty_out)}</span>` : `<span class="qty-nil">—</span>`;
                const salePriceHtml = r.sale_price ? `<span style="color:#0f172a;">${fmtAmt(r.sale_price)}</span>` : `<span class="qty-nil">—</span>`;
                const costPriceHtml = r.cost_price ? `<span style="color:#64748b;">${fmtAmt(r.cost_price)}</span>` : `<span class="qty-nil">—</span>`;
                const bal = parseFloat(r.balance ?? 0);
                const balHtml = `<span class="${balClass(bal)}">${fmtQty(bal)}</span>`;
                const descHtml = r.type === 'opening'
                    ? `<span class="tx-badge ${info.cls}">${info.label}</span>`
                    : `<span class="tx-badge ${info.cls}">${info.label}</span>
                       <span style="margin-left:6px;color:#334155;font-weight:500;font-size:.82rem;">${escHtml(r.description)}</span>`;

                const tr = document.createElement('tr');
                tr.className = info.rowCls;
                tr.innerHTML = `
                    <td style="white-space:nowrap;">${fmtDate(r.date)}</td>
                    <td>${descHtml}</td>
                    <td><span class="ref-badge">${escHtml(r.ref || '—')}</span></td>
                    <td class="tr">${qtyInHtml}</td>
                    <td class="tr">${qtyOutHtml}</td>
                    <td class="tr">${salePriceHtml}</td>
                    <td class="tr">${costPriceHtml}</td>
                    <td class="tr">${balHtml}</td>`;
                tbody.appendChild(tr);

                if (r.type !== 'opening') {
                    grandIn  += parseFloat(r.qty_in  ?? 0);
                    grandOut += parseFloat(r.qty_out ?? 0);
                    if (r.sale_price && r.qty_out) grandSaleVal += r.sale_price * r.qty_out;
                }
            });

            // Closing balance row
            const closingBal = parseFloat(summary.closing_balance ?? 0);
            const closingTr = document.createElement('tr');
            closingTr.className = 'row-closing';
            closingTr.innerHTML = `
                <td style="white-space:nowrap;">${fmtDate(summary.period_end || today)}</td>
                <td><span class="tx-badge tx-opening">🏁 Closing Balance</span></td>
                <td>—</td>
                <td class="tr"><span class="qty-nil">—</span></td>
                <td class="tr"><span class="qty-nil">—</span></td>
                <td class="tr"><span class="qty-nil">—</span></td>
                <td class="tr"><span class="qty-nil">—</span></td>
                <td class="tr"><span class="${balClass(closingBal)}" style="font-size:1rem;">${fmtQty(closingBal)}</span></td>`;
            tbody.appendChild(closingTr);

            document.getElementById('ftQtyIn').innerHTML   = `<span class="qty-in">${fmtQty(grandIn)}</span>`;
            document.getElementById('ftQtyOut').innerHTML  = `<span class="qty-out">${fmtQty(grandOut)}</span>`;
            document.getElementById('ftSaleVal').innerHTML = grandSaleVal ? fmtAmt(grandSaleVal) : '—';
            document.getElementById('ftCostVal').innerHTML = '—';
            document.getElementById('ftBalance').innerHTML = `<span class="${balClass(closingBal)}">${fmtQty(closingBal)}</span>`;
        }

                // ── Update total strip
        document.getElementById('tsOpening').textContent = fmtQty(summary.opening_balance) + ' pcs';
        document.getElementById('tsIn').textContent      = fmtQty(summary.total_qty_in)  + ' pcs';
        document.getElementById('tsOut').textContent     = fmtQty(summary.total_qty_out) + ' pcs';
        document.getElementById('tsClosing').textContent = fmtQty(summary.closing_balance) + ' pcs';
        document.getElementById('tsSaleVal').textContent = fmtAmt(summary.total_sale_value);
        document.getElementById('tsTxCount').textContent = (rows ? rows.length : 0) + ' transactions';
        document.getElementById('totalStrip').style.display = 'flex';
        document.getElementById('pledResult').style.display = 'block';
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    // ── Print ────────────────────────────────────────────────────────────────
    document.getElementById('btnPrint').addEventListener('click', () => window.print());

    // ── Summary PDF Export ───────────────────────────────────────────────────
    document.getElementById('btnSummaryPdf').addEventListener('click', function(){
        if (!lastData) { alert('Please select a product and generate the ledger first.'); return; }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });

        const p   = lastData.summary.product;
        const sum = lastData.summary;

        // Company Header
        doc.setFontSize(16); doc.setFont('helvetica','bold');
        doc.text('THREE STARS MEDICAL SUPPLIES', 40, 50);
        doc.setFontSize(9); doc.setFont('helvetica','normal');
        doc.text('Product Ledger Summary Report', 40, 65);
        doc.text('Printed: ' + new Date().toLocaleString('en-PK'), 40, 78);
        doc.line(40, 85, 555, 85);

        // Product Details Box
        doc.setFillColor(248, 250, 252);
        doc.rect(40, 100, 515, 90, 'F');
        doc.setDrawColor(226, 232, 240);
        doc.rect(40, 100, 515, 90, 'D');

        doc.setFont('helvetica','bold'); doc.setFontSize(11); doc.setTextColor(15, 23, 42);
        doc.text('PRODUCT PROFILE', 55, 120);

        doc.setFont('helvetica','normal'); doc.setFontSize(9.5); doc.setTextColor(71, 85, 105);
        doc.text('Product Name:', 55, 140);
        doc.setFont('helvetica','bold'); doc.setTextColor(15, 23, 42);
        doc.text(p.item_name || '-', 140, 140);

        doc.setFont('helvetica','normal'); doc.setTextColor(71, 85, 105);
        doc.text('Item Code:', 55, 155);
        doc.setFont('helvetica','bold'); doc.setTextColor(15, 23, 42);
        doc.text(p.item_code || '-', 140, 155);

        doc.setFont('helvetica','normal'); doc.setTextColor(71, 85, 105);
        doc.text('Brand:', 55, 170);
        doc.setFont('helvetica','bold'); doc.setTextColor(15, 23, 42);
        doc.text(p.brand_name || '-', 140, 170);

        doc.setFont('helvetica','normal'); doc.setTextColor(71, 85, 105);
        doc.text('Category:', 300, 155);
        doc.setFont('helvetica','bold'); doc.setTextColor(15, 23, 42);
        doc.text(p.category_name || '-', 380, 155);

        doc.setFont('helvetica','normal'); doc.setTextColor(71, 85, 105);
        doc.text('UOM/Unit:', 300, 170);
        doc.setFont('helvetica','bold'); doc.setTextColor(15, 23, 42);
        doc.text(p.unit_name || 'pcs', 380, 170);

        // Period Box
        doc.setFont('helvetica','normal'); doc.setTextColor(71, 85, 105);
        doc.text('Report Period:', 300, 120);
        doc.setFont('helvetica','bold'); doc.setTextColor(37, 99, 235);
        const periodStr = (sum.period_start ? fmtDate(sum.period_start) : 'All') + ' to ' + (sum.period_end ? fmtDate(sum.period_end) : 'All');
        doc.text(periodStr, 380, 120);

        // Financial & Stock Summary Table
        doc.autoTable({
            startY: 210,
            head: [['Metric / Stock KPI', 'Quantity (pcs / boxes)', 'Note']],
            body: [
                ['Opening Balance', fmtQty(sum.opening_balance), 'Stock level before period start'],
                ['Total Stock IN', '+' + fmtQty(sum.total_qty_in), 'Purchases and sales returns in period'],
                ['Total Stock OUT', '-' + fmtQty(sum.total_qty_out), 'Sales, delivery challans, and returns in period'],
                ['Closing Stock', fmtQty(sum.closing_balance), 'Current inventory level at period end'],
                ['Total Sale Value', fmtAmt(sum.total_sale_value), 'Total revenue generated in this period'],
            ],
            styles: { fontSize: 10, cellPadding: 8 },
            headStyles: { fillColor: [30, 58, 138], textColor: 255, fontStyle: 'bold' },
            columnStyles: {
                0: { cellWidth: 150, fontStyle: 'bold' },
                1: { cellWidth: 180, halign: 'right', fontStyle: 'bold' },
                2: { cellWidth: 185 },
            },
            didParseCell: function(data) {
                if (data.section === 'body') {
                    if (data.row.index === 3) {
                        data.cell.styles.fillColor = [243, 244, 246]; // Highlight closing stock
                    }
                    if (data.row.index === 4) {
                        data.cell.styles.fillColor = [254, 243, 199]; // Highlight revenue
                        data.cell.styles.textColor = [146, 64, 14];
                    }
                }
            }
        });

        doc.save(`Product_Summary_${p.item_code}_${sum.period_start || 'all'}_to_${sum.period_end || 'all'}.pdf`);
    });

        // ── Detail PDF Export ───────────────────────────────────────────────────
    document.getElementById('btnPdf').addEventListener('click', function(){
        if (!lastData) { alert('Please select a product and generate the ledger first.'); return; }
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

        const p   = lastData.summary.product;
        const sum = lastData.summary;

        doc.setFontSize(16); doc.setFont('helvetica','bold');
        doc.text('Product Ledger Report', 40, 40);
        doc.setFontSize(10); doc.setFont('helvetica','normal');
        doc.text(`Product: ${p.item_name} (${p.item_code})  |  Brand: ${p.brand_name}  |  Period: ${sum.period_start || 'All'} to ${sum.period_end || 'All'}`, 40, 58);
        doc.text(`Opening: ${fmtQty(sum.opening_balance)} pcs  |  Total IN: ${fmtQty(sum.total_qty_in)} pcs  |  Total OUT: ${fmtQty(sum.total_qty_out)} pcs  |  Closing: ${fmtQty(sum.closing_balance)} pcs`, 40, 72);

        const tableRows = [];
        if (lastData.is_consolidated && lastData.products_data) {
            lastData.products_data.forEach((pData, idx) => {
                const prod = pData.product;
                
                // Group Header Row
                const headerRow = [
                    { content: `📦 Product #${idx+1}: [${prod.item_code}] ${prod.item_name} (Company: ${prod.brand_name} | Closing: ${fmtQty(pData.closing_balance)} pcs)`, colSpan: 8, styles: { fillColor: [30, 58, 138], textColor: 255, fontStyle: 'bold' } }
                ];
                headerRow.isGroupHeader = true;
                tableRows.push(headerRow);
                
                pData.rows.forEach(r => {
                    const rowData = [
                        fmtDate(r.date),
                        r.description,
                        r.ref || '—',
                        r.qty_in  ? '+' + fmtQty(r.qty_in)  : '—',
                        r.qty_out ? '-' + fmtQty(r.qty_out) : '—',
                        r.sale_price ? fmtAmt(r.sale_price) : '—',
                        r.cost_price ? fmtAmt(r.cost_price) : '—',
                        fmtQty(r.balance),
                    ];
                    rowData.txType = r.type;
                    tableRows.push(rowData);
                });
                
                // Subtotal Row
                const subtotalRow = [
                    { content: `Subtotal for [${prod.item_code}] ${prod.item_name}:`, colSpan: 3, styles: { halign: 'right', fontStyle: 'bold', fillColor: [241, 245, 249] } },
                    { content: `+${fmtQty(pData.total_qty_in)}`, styles: { halign: 'right', fontStyle: 'bold', textColor: [21, 128, 61], fillColor: [241, 245, 249] } },
                    { content: `-${fmtQty(pData.total_qty_out)}`, styles: { halign: 'right', fontStyle: 'bold', textColor: [185, 28, 28], fillColor: [241, 245, 249] } },
                    { content: '', colSpan: 2, styles: { fillColor: [241, 245, 249] } },
                    { content: `${fmtQty(pData.closing_balance)}`, styles: { halign: 'right', fontStyle: 'bold', textColor: [30, 58, 138], fillColor: [241, 245, 249] } }
                ];
                subtotalRow.isSubtotal = true;
                tableRows.push(subtotalRow);
            });
        } else {
            lastData.rows.forEach(r => {
                const rowData = [
                    fmtDate(r.date),
                    r.description,
                    r.ref || '—',
                    r.qty_in  ? '+' + fmtQty(r.qty_in)  : '—',
                    r.qty_out ? '-' + fmtQty(r.qty_out) : '—',
                    r.sale_price ? fmtAmt(r.sale_price) : '—',
                    r.cost_price ? fmtAmt(r.cost_price) : '—',
                    fmtQty(r.balance),
                ];
                rowData.txType = r.type;
                tableRows.push(rowData);
            });
            
            // Add closing row
            const closingRow = [
                fmtDate(sum.period_end || today),
                'Closing Balance',
                '—', '—', '—', '—', '—',
                fmtQty(sum.closing_balance),
            ];
            closingRow.isClosing = true;
            tableRows.push(closingRow);
        }

        doc.autoTable({
            startY: 85,
            head: [['Date','Description','Ref / Doc #','Qty IN','Qty OUT','Sale Price','Cost Price','Balance']],
            body: tableRows,
            styles: { fontSize: 8, cellPadding: 4 },
            headStyles: { fillColor: [30, 58, 138], textColor: 255, fontStyle: 'bold' },
            columnStyles: {
                0: {cellWidth:60},
                1: {cellWidth:180},
                2: {cellWidth:80},
                3: {halign:'right',cellWidth:60},
                4: {halign:'right',cellWidth:60},
                5: {halign:'right',cellWidth:80},
                6: {halign:'right',cellWidth:80},
                7: {halign:'right',cellWidth:75, fontStyle:'bold'},
            },
            didParseCell: function(data) {
                if (data.section === 'body') {
                    const rowData = tableRows[data.row.index];
                    if (rowData) {
                        if (rowData.isGroupHeader) {
                            // Style already defined in cell
                        } else if (rowData.isSubtotal) {
                            // Style already defined in cell
                        } else if (rowData.isClosing) {
                            data.cell.styles.fillColor = [245,243,255];
                            data.cell.styles.fontStyle = 'bold';
                        } else if (rowData.txType === 'opening') {
                            data.cell.styles.fillColor = [219,234,254];
                        } else if (rowData.txType === 'purchase') {
                            data.cell.styles.fillColor = [240,253,244];
                        } else if (rowData.txType === 'sale') {
                            data.cell.styles.fillColor = [255,241,242];
                        } else if (rowData.txType === 'delivery_challan') {
                            data.cell.styles.fillColor = [255,247,237];
                        }
                    }
                }
            },
            foot: [[
                '', 'TOTALS', '',
                '+' + fmtQty(sum.total_qty_in),
                '-' + fmtQty(sum.total_qty_out),
                '', '', fmtQty(sum.closing_balance)
            ]],
            footStyles: { fillColor: [241,245,249], textColor: [15,23,42], fontStyle: 'bold' },
        });

        doc.save(`Product_Ledger_${p.item_code}_${sum.period_start || 'all'}_to_${sum.period_end || 'all'}.pdf`);
    });

        // ── Official Styled Excel Export ─────────────────────────────────────────────────────────
    document.getElementById('btnExcel').addEventListener('click', function(){
        if (!lastData) { alert('Please select a product and generate the ledger first.'); return; }

        const p   = lastData.summary.product;
        const sum = lastData.summary;
        const rows = lastData.rows || [];

        const esc = s => String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

        const xTag = 'x:';
        let tableHtml = `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="utf-8">
            <!--[if gte mso 9]>
            <xml>
             <${xTag}ExcelWorkbook>
              <${xTag}ExcelWorksheets>
               <${xTag}ExcelWorksheet>
                <${xTag}Name>Product Ledger</${xTag}Name>
                <${xTag}WorksheetOptions>
                 <${xTag}DisplayGridlines/>
                </${xTag}WorksheetOptions>
               </${xTag}ExcelWorksheet>
              </${xTag}ExcelWorksheets>
             </${xTag}ExcelWorkbook>
            </xml>
            <![endif]-->` + `
            <style>
                body { font-family: 'Segoe UI', Calibri, Arial, sans-serif; }
                table { border-collapse: collapse; width: 100%; font-size: 10pt; }
                th, td { border: 1px solid #cbd5e1; padding: 7px 10px; vertical-align: middle; }
                
                /* Title Header */
                .title-header { background-color: #1e3a8a; color: #ffffff; font-size: 15pt; font-weight: bold; text-align: center; height: 42px; border: 1px solid #1e3a8a; }
                
                /* Meta info block */
                .meta-lbl { background-color: #f1f5f9; color: #1e293b; font-weight: bold; border: 1px solid #cbd5e1; }
                .meta-val { background-color: #ffffff; color: #0f172a; border: 1px solid #cbd5e1; }
                
                /* KPI Summary */
                .kpi-hdr { background-color: #0f172a; color: #ffffff; font-weight: bold; text-align: center; font-size: 10pt; }
                .kpi-val { background-color: #f8fafc; font-weight: bold; text-align: center; font-size: 11pt; border: 1px solid #cbd5e1; }
                
                /* Main Table Headings */
                .tbl-hdr { background-color: #1e3a8a; color: #ffffff; font-weight: bold; font-size: 11pt; text-align: center; height: 32px; border: 1px solid #1e293b; }
                
                /* Data rows */
                .row-even { background-color: #ffffff; }
                .row-odd { background-color: #f8fafc; }
                .row-closing { background-color: #eff6ff; font-weight: bold; }
                
                /* Cell utility classes */
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .text-left { text-align: left; }
                
                .qty-in { color: #15803d; font-weight: bold; text-align: right; }
                .qty-out { color: #b91c1c; font-weight: bold; text-align: right; }
                .bal-val { color: #0f172a; font-weight: bold; text-align: right; }
                
                /* Grand Totals Footer */
                .tbl-foot { background-color: #0f172a; color: #ffffff; font-weight: bold; font-size: 11pt; height: 34px; border: 1px solid #0f172a; }
            </style>
        </head>
        <body>
            <table>
                <!-- Main Header Banner -->
                <tr>
                    <th colspan="8" class="title-header">PRODUCT LEDGER REPORT</th>
                </tr>
                <tr><td colspan="8" style="border:none; height:10px;"></td></tr>

                <!-- Product Profile Metadata -->
                <tr>
                    <td class="meta-lbl">Product Name:</td>
                    <td class="meta-val" colspan="3"><b>${esc(p.item_name)}</b></td>
                    <td class="meta-lbl">Product Code:</td>
                    <td class="meta-val" colspan="3"><b>${esc(p.item_code)}</b></td>
                </tr>
                <tr>
                    <td class="meta-lbl">Category:</td>
                    <td class="meta-val" colspan="3">${esc(p.category_name)}</td>
                    <td class="meta-lbl">Brand / Company:</td>
                    <td class="meta-val" colspan="3">${esc(p.brand_name)}</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Unit:</td>
                    <td class="meta-val" colspan="3">${esc(p.unit_name || 'pcs')}</td>
                    <td class="meta-lbl">Period Range:</td>
                    <td class="meta-val" colspan="3">${fmtDate(sum.period_start)} to ${fmtDate(sum.period_end)}</td>
                </tr>
                <tr><td colspan="8" style="border:none; height:10px;"></td></tr>

                <!-- KPI Executive Summary -->
                <tr>
                    <th class="kpi-hdr" colspan="2">Opening Stock</th>
                    <th class="kpi-hdr" colspan="2">Total Qty IN</th>
                    <th class="kpi-hdr" colspan="2">Total Qty OUT</th>
                    <th class="kpi-hdr">Closing Stock</th>
                    <th class="kpi-hdr">Total Sale Revenue</th>
                </tr>
                <tr>
                    <td class="kpi-val" colspan="2">${fmtQty(sum.opening_balance)} pcs</td>
                    <td class="kpi-val" style="color:#15803d;" colspan="2">+${fmtQty(sum.total_qty_in)} pcs</td>
                    <td class="kpi-val" style="color:#b91c1c;" colspan="2">-${fmtQty(sum.total_qty_out)} pcs</td>
                    <td class="kpi-val" style="color:#1e3a8a;">${fmtQty(sum.closing_balance)} pcs</td>
                    <td class="kpi-val" style="color:#b45309;">${fmtAmt(sum.total_sale_value)}</td>
                </tr>
                <tr><td colspan="8" style="border:none; height:12px;"></td></tr>

                <!-- Highlighted Column Headings -->
                <thead>
                    <tr class="tbl-hdr">
                        <th style="width:110px;">Date</th>
                        <th style="width:300px;">Description</th>
                        <th style="width:130px;">Ref / Doc #</th>
                        <th style="width:110px;">Qty IN</th>
                        <th style="width:110px;">Qty OUT</th>
                        <th style="width:120px;">Sale Price</th>
                        <th style="width:120px;">Cost Price</th>
                        <th style="width:130px;">Balance (Pcs)</th>
                    </tr>
                </thead>
                <tbody>
        `;

        // Data Rows
        if (lastData.is_consolidated && lastData.products_data) {
            lastData.products_data.forEach((pData, idx) => {
                const prod = pData.product;
                tableHtml += `
                    <tr style="background-color:#1e3a8a; color:#ffffff; font-weight:bold;">
                        <td colspan="8">📦 Product #${idx+1}: [${esc(prod.item_code)}] ${esc(prod.item_name)} (Company: ${esc(prod.brand_name)} | Closing: ${fmtQty(pData.closing_balance)} pcs)</td>
                    </tr>
                `;
                pData.rows.forEach((r, idx2) => {
                    const descClean = esc(r.description ? r.description.replace(/<[^>]+>/g, '') : '');
                    const rowClass  = idx2 % 2 === 0 ? 'row-even' : 'row-odd';
                    tableHtml += `
                        <tr class="${rowClass}">
                            <td class="text-center">${fmtDate(r.date)}</td>
                            <td class="text-left">${descClean}</td>
                            <td class="text-center">${esc(r.ref || '—')}</td>
                            <td class="qty-in">${r.qty_in ? '+' + fmtQty(r.qty_in) : '—'}</td>
                            <td class="qty-out">${r.qty_out ? '-' + fmtQty(r.qty_out) : '—'}</td>
                            <td class="text-right">${r.sale_price ? fmtAmt(r.sale_price) : '—'}</td>
                            <td class="text-right">${r.cost_price ? fmtAmt(r.cost_price) : '—'}</td>
                            <td class="bal-val">${fmtQty(r.balance)}</td>
                        </tr>
                    `;
                });
                tableHtml += `
                    <tr style="background-color:#f1f5f9; font-weight:bold;">
                        <td colspan="3" class="text-right">Subtotal for [${esc(prod.item_code)}] ${esc(prod.item_name)}:</td>
                        <td class="qty-in" style="color:#15803d;">+${fmtQty(pData.total_qty_in)}</td>
                        <td class="qty-out" style="color:#b91c1c;">-${fmtQty(pData.total_qty_out)}</td>
                        <td colspan="2"></td>
                        <td class="bal-val" style="color:#1e3a8a;">${fmtQty(pData.closing_balance)}</td>
                    </tr>
                `;
            });
        } else {
            rows.forEach((r, idx) => {
                const descClean = esc(r.description ? r.description.replace(/<[^>]+>/g, '') : '');
                const rowClass  = idx % 2 === 0 ? 'row-even' : 'row-odd';
                tableHtml += `
                    <tr class="${rowClass}">
                        <td class="text-center">${fmtDate(r.date)}</td>
                        <td class="text-left">${descClean}</td>
                        <td class="text-center">${esc(r.ref || '—')}</td>
                        <td class="qty-in">${r.qty_in ? '+' + fmtQty(r.qty_in) : '—'}</td>
                        <td class="qty-out">${r.qty_out ? '-' + fmtQty(r.qty_out) : '—'}</td>
                        <td class="text-right">${r.sale_price ? fmtAmt(r.sale_price) : '—'}</td>
                        <td class="text-right">${r.cost_price ? fmtAmt(r.cost_price) : '—'}</td>
                        <td class="bal-val">${fmtQty(r.balance)}</td>
                    </tr>
                `;
            });
            // Closing Balance Row
            tableHtml += `
                <tr class="row-closing">
                    <td class="text-center">${fmtDate(sum.period_end || today)}</td>
                    <td class="text-left"><b>🏁 Closing Balance</b></td>
                    <td class="text-center">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="text-right">—</td>
                    <td class="bal-val" style="color:#1e3a8a;">${fmtQty(sum.closing_balance)}</td>
                </tr>
            `;
        }

        // Grand Totals Footer Row
        tableHtml += `
                </tbody>
                <tfoot>
                    <tr class="tbl-foot">
                        <td colspan="3" class="text-right">GRAND TOTALS:</td>
                        <td class="text-right" style="color:#4ade80;">+${fmtQty(sum.total_qty_in)}</td>
                        <td class="text-right" style="color:#fca5a5;">-${fmtQty(sum.total_qty_out)}</td>
                        <td class="text-right">—</td>
                        <td class="text-right">—</td>
                        <td class="text-right" style="color:#93c5fd;">${fmtQty(sum.closing_balance)}</td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
        `;

        // Create Blob and Trigger Download
        const blob = new Blob(['﻿' + tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const safeCode = (p.item_code || 'Ledger').replace(/[^a-zA-Z0-9_-]/g, '_');
        a.href = url;
        a.download = `Product_Ledger_${safeCode}_${sum.period_start || 'all'}_to_${sum.period_end || 'all'}.xls`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

})();
</script>
@endsection
