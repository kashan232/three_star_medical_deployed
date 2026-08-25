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

    /* Product Filter Button & Tag Tray */
    .product-filter-btn {
        border-radius: 6px !important;
        background-color: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        transition: all 0.2s ease;
    }
    .product-filter-btn:hover, .product-filter-btn:focus {
        border-color: #0ea5e9 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.15);
    }
    .btn-browse-badge {
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        font-size: 0.72rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 6px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
    }
    .selected-tags-tray {
        max-height: 120px;
        overflow-y: auto;
        background: #f8fafc;
        padding: 6px 8px;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
    }
    .prod-chip {
        background: #eff6ff;
        color: #1e40af;
        border: 1px solid #bfdbfe;
        border-radius: 14px;
        padding: 2px 8px;
        font-size: 0.73rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        max-width: 100%;
    }
    .prod-chip .chip-remove {
        cursor: pointer;
        color: #93c5fd;
        font-size: 0.8rem;
        line-height: 1;
        transition: color 0.15s;
    }
    .prod-chip .chip-remove:hover {
        color: #ef4444;
    }

    /* ── Classic Ledger Sheet (Matching ERP & PDF Print Layout) ──────── */
    .ledger-report-sheet {
        background: #ffffff;
        border-radius: 8px;
        padding: 24px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        margin-bottom: 25px;
    }
    .sheet-header {
        border-bottom: 2px solid #000000;
        padding-bottom: 8px;
        margin-bottom: 12px;
    }
    .classic-ledger-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #94a3b8;
        font-size: 0.84rem;
    }
    .classic-ledger-table thead th {
        background-color: #2e62a6 !important;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.82rem;
        padding: 7px 6px;
        border: 1px solid #234d85 !important;
        white-space: nowrap;
    }
    .classic-ledger-table tbody td {
        border: 1px solid #cbd5e1;
        padding: 6px 8px;
        font-size: 0.82rem;
        vertical-align: middle;
    }
    .product-banner-row td {
        background-color: #dce6f7 !important;
        border: 1px solid #b8cce4 !important;
        font-weight: 700;
        color: #0f172a;
        padding: 7px 10px;
    }
    .ledger-row-opening td {
        background-color: #ffffff;
    }
    .ledger-row-tx:hover td {
        background-color: #f1f5f9;
    }
    .ledger-row-closing td {
        background-color: #ffffff;
        border-bottom: 2px solid #94a3b8 !important;
    }

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
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        body * {
            visibility: hidden;
        }
        #ledgerReportSheet, #ledgerReportSheet * {
            visibility: visible;
        }
        #ledgerReportSheet {
            position: absolute;
            left: 0;
            top: 0;
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }
        .filter-card,
        .rpt-header,
        .btn-srp,
        .sidebar,
        .header-area,
        .footer-area {
            display: none !important;
        }
        .classic-ledger-table thead th {
            background-color: #2e62a6 !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border: 1px solid #000 !important;
        }
        .product-banner-row td {
            background-color: #dce6f7 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            border: 1px solid #b8cce4 !important;
        }
        .classic-ledger-table td {
            border: 1px solid #cbd5e1 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
                    <button type="button" id="btnExportExcel" class="btn-filter-action btn-excel-action" title="Export Excel Spreadsheet">📊 Export Excel</button>
                    <button type="button" id="btnExportPdf" class="btn-filter-action btn-pdf-action" title="Export Detail PDF">📄 Export PDF</button>
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
                <div class="filter-group" style="flex: 2; min-width: 280px;">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="m-0">Product</label>
                        <span id="productSelectCountBadge" class="badge bg-primary text-white" style="display:none; font-size:0.72rem; padding: 2px 7px; border-radius: 12px;">0 Selected</span>
                    </div>
                    <div class="product-selector-box">
                        <button type="button" id="btnOpenProductModal" class="product-filter-btn form-control d-flex align-items-center justify-content-between text-start" style="height: auto; min-height: 32px; padding: 4px 10px; cursor: pointer;">
                            <span id="productFilterText" class="text-truncate text-secondary" style="font-size: 0.8rem;">
                                <i class="bi bi-boxes text-primary me-1"></i> — Select Product(s) / All —
                            </span>
                            <span class="btn-browse-badge">
                                <i class="bi bi-search me-1"></i>Select Products
                            </span>
                        </button>
                        <input type="hidden" id="sel_product_ids" value="">
                        
                        <!-- Selected Product Tags Tray -->
                        <div id="selectedProductsTagTray" class="selected-tags-tray mt-1" style="display: none;">
                            <div id="selectedProductsTags" class="d-flex flex-wrap gap-1"></div>
                            <div class="d-flex justify-content-end mt-1">
                                <button type="button" id="btnClearSelectedProducts" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" style="font-size: 0.72rem;">
                                    <i class="bi bi-trash3"></i> Clear Selected
                                </button>
                            </div>
                        </div>
                    </div>
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
        <div class="ledger-report-sheet" id="ledgerReportSheet">
            <!-- Classic Header identical to PDF -->
            <div class="sheet-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div style="font-size:1.15rem; font-weight:800; color:#000; text-transform:uppercase; letter-spacing:0.3px;">THREE STARS MEDICAL SUPPLIES</div>
                        <div style="font-size:0.82rem; color:#333; margin-top:2px;">Three Stars Medical Supplies : <span id="hdrBranchName">Lahore</span></div>
                        <div style="font-size:0.82rem; color:#333;">Phone : 0321-4208158</div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-size:0.85rem; font-weight:700; color:#1e3a8a;" id="hdrDateRange">2026-04-01 -TO- 2026-04-30</div>
                        <div style="font-size:0.75rem; color:#666; margin-top:3px;">Print Date : {{ now()->format('d M Y') }}</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-end" style="margin-top:14px;">
                    <div style="font-size:1.4rem; font-weight:800; color:#000;">Product Ledger</div>
                    <div style="font-size:0.85rem; font-weight:700; color:#000;">
                        Location : <span id="hdrLocation" style="font-weight:600;">THREE STARS MEDICAL SUPPLIES</span>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table id="ledgerTable" class="classic-ledger-table">
                    <thead>
                        <tr>
                            <th style="width:45px; text-align:center;">SR #</th>
                            <th style="width:115px; text-align:left;">Date</th>
                            <th style="text-align:left;">Description</th>
                            <th style="width:90px; text-align:left;">REF #</th>
                            <th style="width:105px; text-align:right;">Rate</th>
                            <th style="width:95px; text-align:right;">Debit</th>
                            <th style="width:95px; text-align:right;">Credit</th>
                            <th style="width:105px; text-align:right;">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="ledgerBody"></tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="d-flex justify-content-between align-items-center mt-3 pt-2" style="border-top:1px solid #cbd5e1; font-size:0.75rem; color:#64748b;">
                <span>ProWaves ver.8.0.1.4592 Copyrights &copy; {{ date('Y') }} Cybernetic Technologies. All rights reserved. &nbsp;&nbsp; rptItemLedger</span>
                <span><strong>Print Date :</strong> {{ now()->format('d M Y') }}</span>
            </div>
        </div>
    </div>

        </div>
    </div>
</div>

@include('admin_panel.components.product_select_modal')
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
    const fmt3 = n => {
        if (n == null || isNaN(n)) return '0.000';
        return parseFloat(n).toLocaleString('en-PK', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
    };
    const fmt2 = n => {
        if (n == null || isNaN(n)) return '0.00';
        return parseFloat(n).toLocaleString('en-PK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    const fmtDateWithTime = dt => {
        if (!dt) return '';
        const parts = dt.split(' ');
        const dateParts = parts[0].split('-');
        if (dateParts.length === 3) {
            const dStr = dateParts[2] + '/' + dateParts[1] + '/' + dateParts[0];
            const tStr = parts[1] ? parts[1].substring(0, 5) : '';
            return tStr ? (dStr + '<br>' + tStr) : dStr;
        }
        return dt;
    };

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
    });

    // ── Product Selection Modal Logic ──────────────────────────────────────────
    let selectedProductsMap = new Map();

    function updateSelectedProductsUI() {
        const ids = Array.from(selectedProductsMap.keys());
        const count = ids.length;
        
        $('#sel_product_ids').val(ids.join(','));
        
        if (count === 0) {
            $('#productFilterText').html('<i class="bi bi-boxes me-1 text-primary"></i> <span class="text-muted">— Select Product(s) / All —</span>');
            $('#productSelectCountBadge').hide();
            $('#selectedProductsTagTray').hide();
            $('#selectedProductsTags').empty();
        } else {
            $('#productFilterText').html('<i class="bi bi-check2-circle me-1 text-success fw-bold"></i> <strong class="text-dark">' + count + ' Product' + (count > 1 ? 's' : '') + ' Selected</strong>');
            $('#productSelectCountBadge').text(count + ' Selected').show();
            
            let tagsHtml = '';
            selectedProductsMap.forEach((p, id) => {
                const code = p.item_code || '';
                const name = p.item_name || '';
                const label = (code ? code + ' — ' : '') + name;
                tagsHtml += `<span class="prod-chip" title="${escHtml(name)}">
                    <span class="text-truncate" style="max-width: 220px;">${escHtml(label)}</span>
                    <i class="bi bi-x-circle-fill chip-remove ms-1" data-id="${id}" title="Remove"></i>
                </span>`;
            });
            
            $('#selectedProductsTags').html(tagsHtml);
            $('#selectedProductsTagTray').show();
        }
    }

    $('#btnOpenProductModal').on('click', function(e) {
        e.preventDefault();
        const catId = $('#filterCategory').val();
        const brandId = $('#filterBrand').val();

        if (window.ERPProductModal) {
            window.ERPProductModal.open({
                singleSelect: false,
                selectedIds: Array.from(selectedProductsMap.keys()),
                categoryId: (catId && catId !== 'all') ? catId : '',
                brandId: (brandId && brandId !== 'all') ? brandId : '',
                onSelect: function(products) {
                    selectedProductsMap.clear();
                    if (Array.isArray(products)) {
                        products.forEach(p => {
                            if (p && p.id) {
                                selectedProductsMap.set(parseInt(p.id), p);
                            }
                        });
                    }
                    updateSelectedProductsUI();
                }
            });
        }
    });

    $(document).on('click', '.chip-remove', function(e) {
        e.stopPropagation();
        const id = parseInt($(this).data('id'));
        selectedProductsMap.delete(id);
        updateSelectedProductsUI();
    });

    $('#btnClearSelectedProducts').on('click', function(e) {
        e.preventDefault();
        selectedProductsMap.clear();
        updateSelectedProductsUI();
    });

    // ── Filter reset ────────────────────────────────────────────────────────
    $('#btnReset').on('click', function() {
        $('#sel_start').val(firstOfYear);
        $('#sel_end').val(today);
        $('#filterStatus').val('all');

        selectedProductsMap.clear();
        updateSelectedProductsUI();

        $('#filterCategory').val('all').trigger('change.select2');
        $('#filterSubCategory').val('all').trigger('change.select2');
        $('#filterBrand').val('all').trigger('change.select2');
        $('#sel_warehouse').val('all').trigger('change.select2');
        if (document.getElementById('filterBranch')) {
            $('#filterBranch').val('all').trigger('change.select2');
        }

        $('#pledResult').hide();
    });

    // ── Cascading SubCategory by Category ────────────────────────────────────
    $('#filterCategory').on('change', function() {
        const catId = $(this).val();
        const $sub  = $('#filterSubCategory');
        $sub.find('option').each(function() {
            if ($(this).val() === 'all') return;
            const c = $(this).data('cat');
            if (catId === 'all' || !c || c == catId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $sub.val('all').trigger('change.select2');
    });

    // ── Generate Ledger (AJAX) ───────────────────────────────────────────────
    let lastData = null;

    document.getElementById('btnGenerate').addEventListener('click', function () {
        const product_id = document.getElementById('sel_product_ids').value;
        const start_date = document.getElementById('sel_start').value;
        const end_date   = document.getElementById('sel_end').value;

        if (!start_date || !end_date) {
            Swal.fire({ icon: 'warning', title: 'Dates Required', text: 'Please select both start and end dates.' });
            return;
        }

        const params = new URLSearchParams({ start_date, end_date });
        if (product_id) params.append('product_id', product_id);
        const cat_id = document.getElementById('filterCategory')?.value;
        const sub_id = document.getElementById('filterSubCategory')?.value;
        const brand_id = document.getElementById('filterBrand')?.value;
        const branch_id = document.getElementById('filterBranch')?.value;
        const warehouse_id = document.getElementById('sel_warehouse')?.value;
        if (cat_id && cat_id !== 'all') params.append('category_id', cat_id);
        if (sub_id && sub_id !== 'all') params.append('sub_category_id', sub_id);
        if (brand_id && brand_id !== 'all') params.append('brand_id', brand_id);
        if (branch_id && branch_id !== 'all') params.append('branch_id', branch_id);
        if (warehouse_id) params.append('warehouse_id', warehouse_id);

        document.getElementById('pledLoader').style.display = 'flex';

        fetch(`{{ route('report.product.ledger.fetch') }}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            document.getElementById('pledLoader').style.display = 'none';
            if (!res.success) { 
                Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'Failed to load ledger data.' }); 
                return; 
            }
            lastData = res;
            renderLedger(res);
        })
        .catch(err => {
            document.getElementById('pledLoader').style.display = 'none';
            Swal.fire({ icon: 'error', title: 'Network Error', text: err.message });
        });
    });

    function renderLedger(res) {
        const { rows, summary, is_consolidated, product_count, products_data } = res;

        // Meta headers
        const sd = summary.period_start || document.getElementById('sel_start').value || '2026-04-01';
        const ed = summary.period_end   || document.getElementById('sel_end').value || '2026-04-30';
        document.getElementById('hdrDateRange').textContent = `${sd} -TO- ${ed}`;

        const branchText = $('#filterBranch option:selected').text();
        if (branchText && branchText !== 'All Branches') {
            document.getElementById('hdrBranchName').textContent = branchText.replace(/^[^\w\s]+/, '').trim();
        }

        const whText = $('#sel_warehouse option:selected').text();
        if (whText && whText !== 'All Locations') {
            document.getElementById('hdrLocation').textContent = whText.replace(/^[^\w\s]+/, '').trim();
        } else {
            document.getElementById('hdrLocation').textContent = 'THREE STARS MEDICAL SUPPLIES';
        }

        const tbody = document.getElementById('ledgerBody');
        tbody.innerHTML = '';

        let listToRender = (products_data && products_data.length > 0) ? products_data : [];
        if (listToRender.length === 0 && summary.product) {
            listToRender = [{
                product: summary.product,
                opening_balance: summary.opening_balance,
                closing_balance: summary.closing_balance,
                rows: rows || []
            }];
        }

        if (listToRender.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <b>No records found for the selected filters.</b>
            </td></tr>`;
            document.getElementById('pledResult').style.display = 'block';
            return;
        }

        listToRender.forEach((pData, pIdx) => {
            const prod = pData.product || {};
            const pNum = pIdx + 1;
            const brandStr = (prod.brand_name && prod.brand_name !== '-' && prod.brand_name !== 'None') ? prod.brand_name : '';
            const packStr = (prod.pieces_per_box && prod.pieces_per_box > 1) ? ` ${prod.pieces_per_box}PCS` : '';
            const brandTag = brandStr ? ` (${brandStr.toUpperCase()})` : '';

            let titleName = prod.item_name || 'Product';
            if (brandStr && !titleName.toUpperCase().includes(brandStr.toUpperCase())) {
                titleName = brandStr + ' ' + titleName;
            }
            titleName = titleName + packStr + brandTag;

            // 1. Product Title Banner Row
            const bannerTr = document.createElement('tr');
            bannerTr.className = 'product-banner-row';
            bannerTr.innerHTML = `
                <td style="text-align:center; font-weight:700; vertical-align:middle; background:#e4edfa; border:1px solid #b8cce4; font-size:0.86rem; color:#1e293b;">
                    ${pNum}
                </td>
                <td colspan="7" style="padding:6px 10px; font-weight:700; font-size:0.86rem; color:#0f172a; text-transform:uppercase; border:1px solid #b8cce4;">
                    ${escHtml(titleName)}
                </td>
            `;
            tbody.appendChild(bannerTr);

            // 2. Opening Stock Row (Row 1)
            const opBal = parseFloat(pData.opening_balance ?? 0);
            const opTr = document.createElement('tr');
            opTr.className = 'ledger-row-opening';
            opTr.innerHTML = `
                <td style="text-align:center; color:#64748b; border:1px solid #cbd5e1;">1</td>
                <td style="border:1px solid #cbd5e1;"></td>
                <td style="font-weight:600; text-transform:uppercase; border:1px solid #cbd5e1; color:#1e293b;">OPENING STOCK</td>
                <td style="border:1px solid #cbd5e1;"></td>
                <td style="text-align:right; border:1px solid #cbd5e1;">0.00</td>
                <td style="text-align:right; border:1px solid #cbd5e1;">${fmt3(opBal > 0 ? opBal : 0)}</td>
                <td style="text-align:right; border:1px solid #cbd5e1;">0.000</td>
                <td style="text-align:right; font-weight:700; border:1px solid #cbd5e1; color:#0f172a;">${fmt3(opBal)}</td>
            `;
            tbody.appendChild(opTr);

            // 3. Transactions Rows
            let subIdx = 2;
            if (pData.rows && pData.rows.length > 0) {
                pData.rows.forEach(r => {
                    if (r.type === 'opening') return;

                    const inQty  = parseFloat(r.qty_in  ?? 0);
                    const outQty = parseFloat(r.qty_out ?? 0);
                    const bal    = parseFloat(r.balance ?? 0);
                    const rateVal = parseFloat(r.rate ?? r.cost_price ?? r.sale_price ?? 0);
                    const rateText = rateVal > 0 ? ('PKR ' + fmt2(rateVal)) : '0.00';

                    const tr = document.createElement('tr');
                    tr.className = 'ledger-row-tx';
                    tr.innerHTML = `
                        <td style="text-align:center; color:#64748b; border:1px solid #cbd5e1;">${subIdx}</td>
                        <td style="white-space:nowrap; border:1px solid #cbd5e1; font-size:0.8rem; color:#334155; line-height:1.2;">${fmtDateWithTime(r.date)}</td>
                        <td style="border:1px solid #cbd5e1; color:#0f172a; font-weight:500;">${escHtml(r.description || '')}</td>
                        <td style="border:1px solid #cbd5e1; font-weight:600; color:#334155; font-size:0.8rem;">${escHtml(r.ref || '-')}</td>
                        <td style="text-align:right; border:1px solid #cbd5e1; white-space:nowrap; color:#334155;">${rateText}</td>
                        <td style="text-align:right; border:1px solid #cbd5e1; ${inQty > 0 ? 'font-weight:600;' : ''}">${fmt3(inQty)}</td>
                        <td style="text-align:right; border:1px solid #cbd5e1; ${outQty > 0 ? 'font-weight:600;' : ''}">${fmt3(outQty)}</td>
                        <td style="text-align:right; font-weight:700; border:1px solid #cbd5e1; color:#0f172a;">${fmt3(bal)}</td>
                    `;
                    tbody.appendChild(tr);
                    subIdx++;
                });
            }

            // 4. Closing Balance Row
            const closingBal = parseFloat(pData.closing_balance ?? 0);
            const closingTr = document.createElement('tr');
            closingTr.className = 'ledger-row-closing';
            closingTr.innerHTML = `
                <td colspan="7" style="text-align:right; font-weight:700; color:#991b1b; padding:6px 12px; border:1px solid #cbd5e1; font-size:0.85rem;">
                    Closing Balance :
                </td>
                <td style="text-align:right; font-weight:700; color:#991b1b; padding:6px 8px; border:1px solid #cbd5e1; font-size:0.88rem;">
                    ${fmt3(closingBal)}
                </td>
            `;
            tbody.appendChild(closingTr);
        });

        document.getElementById('pledResult').style.display = 'block';
    }

    function escHtml(str) {
        const d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    // ── Print ────────────────────────────────────────────────────────────────
    document.getElementById('btnPrint').addEventListener('click', () => window.print());

    // ── Server-Side Excel Export ─────────────────────────────────────────────────────────
    document.getElementById('btnExportExcel')?.addEventListener('click', function() {
        const product_id = document.getElementById('sel_product_ids')?.value || '';
        const start_date = document.getElementById('sel_start')?.value || '';
        const end_date = document.getElementById('sel_end')?.value || '';

        const params = new URLSearchParams({ start_date, end_date });
        if (product_id) params.append('product_id', product_id);
        const cat_id = document.getElementById('filterCategory')?.value;
        const sub_id = document.getElementById('filterSubCategory')?.value;
        const brand_id = document.getElementById('filterBrand')?.value;
        const branch_id = document.getElementById('filterBranch')?.value;
        const warehouse_id = document.getElementById('sel_warehouse')?.value;
        if (cat_id && cat_id !== 'all') params.append('category_id', cat_id);
        if (sub_id && sub_id !== 'all') params.append('sub_category_id', sub_id);
        if (brand_id && brand_id !== 'all') params.append('brand_id', brand_id);
        if (branch_id && branch_id !== 'all') params.append('branch_id', branch_id);
        if (warehouse_id) params.append('warehouse_id', warehouse_id);

        window.location.href = `{{ route('report.product.ledger.export.excel') }}?${params}`;
    });

    // ── Server-Side PDF Export ─────────────────────────────────────────────────────────
    document.getElementById('btnExportPdf')?.addEventListener('click', function() {
        const product_id = document.getElementById('sel_product_ids')?.value || '';
        const start_date = document.getElementById('sel_start')?.value || '';
        const end_date = document.getElementById('sel_end')?.value || '';

        const params = new URLSearchParams({ start_date, end_date });
        if (product_id) params.append('product_id', product_id);
        const cat_id = document.getElementById('filterCategory')?.value;
        const sub_id = document.getElementById('filterSubCategory')?.value;
        const brand_id = document.getElementById('filterBrand')?.value;
        const branch_id = document.getElementById('filterBranch')?.value;
        const warehouse_id = document.getElementById('sel_warehouse')?.value;
        if (cat_id && cat_id !== 'all') params.append('category_id', cat_id);
        if (sub_id && sub_id !== 'all') params.append('sub_category_id', sub_id);
        if (brand_id && brand_id !== 'all') params.append('brand_id', brand_id);
        if (branch_id && branch_id !== 'all') params.append('branch_id', branch_id);
        if (warehouse_id) params.append('warehouse_id', warehouse_id);

        window.location.href = `{{ route('report.product.ledger.export.pdf') }}?${params}`;
    });

})();
</script>
@endsection
