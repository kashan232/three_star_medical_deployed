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
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 18px;
        }

        .filter-title {
            font-size: .73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            font-size: .86rem;
            padding: 7px 11px;
            height: auto;
            transition: border-color .2s, box-shadow .2s;
        }

        .filter-card .form-control:focus {
            border-color: var(--c-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
            outline: none;
        }

        label.form-label {
            font-size: .78rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .btn-srp {
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            padding: 8px 18px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: box-shadow .2s, transform .1s;
        }

        .btn-srp:hover {
            transform: translateY(-1px);
        }

        .btn-srp.blue {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
        }

        .btn-srp.blue:hover {
            box-shadow: 0 4px 14px rgba(37, 99, 235, .38);
        }

        .btn-srp.ghost {
            background: #f1f5f9;
            color: #475569;
            border: 1.5px solid #e2e8f0;
        }

        .btn-srp.ghost:hover {
            background: #e2e8f0;
        }

        .btn-srp.green {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
        }

        .btn-srp.green:hover {
            box-shadow: 0 4px 14px rgba(22, 163, 74, .38);
        }

        .btn-srp.purple {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: #fff;
        }

        .btn-srp.purple:hover {
            box-shadow: 0 4px 14px rgba(124, 58, 237, .38);
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
                <div class="rpt-header">
                    <div>
                        <h3><i class="fas fa-layer-group me-2"></i> Item Stock Report</h3>
                        <p>Full inventory — stock by size mode, per-warehouse breakdown, movements &amp; valuations</p>
                        <p style="opacity:.62;font-size:.76rem;margin-top:4px;">Generated: <span id="reportDate"></span></p>
                    </div>
                    <div class="rpt-header-icon"><i class="fas fa-chart-bar"></i></div>
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
                    <div class="filter-title"><i class="fas fa-filter"></i> Advanced Filters & Actions</div>
                    <div class="row g-3">
                        <!-- Unified Row 1: Products Attributes -->
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select id="filterCategory" class="form-control select2-global">
                                <option value="all">All Category</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sub-Category</label>
                            <select id="filterSubCategory" class="form-control select2-global">
                                <option value="all">All Sub-category</option>
                                @foreach($subCategories as $sc)
                                    <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Brand / Company</label>
                            <select id="filterBrand" class="form-control select2-global">
                                <option value="all">All Company</option>
                                @foreach($brands as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
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

                        <!-- Unified Row 2: Parameters -->
                        <div class="col-md-2">
                            <label class="form-label">Warehouse</label>
                            <select id="filterWarehouse" class="form-control select2-global">
                                <option value="all">All Warehouses</option>
                            </select>
                        </div>
                        @if($isSuperAdmin)
                        <div class="col-md-2">
                            <label class="form-label">Branch</label>
                            <select id="filterBranch" class="form-control select2-global">
                                <option value="all">— All Branches —</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="start_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" id="end_date" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select id="filterStatus" class="form-control">
                                <option value="all">All Status</option>
                                <option value="normal">✅ Normal</option>
                                <option value="low_stock">⚠️ Low Stock</option>
                                <option value="out_of_stock">❌ Out of Stock</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="col-md-2 d-flex align-items-end gap-1 flex-wrap">
                            <button type="button" id="btnSearch" class="btn-srp blue flex-fill" title="Generate Report">
                                <i class="fas fa-sync-alt"></i> Search
                            </button>
                            <button type="button" id="btnReset" class="btn-srp ghost" title="Reset Filters">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button type="button" id="btnSummaryPdf" class="btn-srp green" title="Export Summary PDF (totals per product)">
                                <i class="fas fa-file-pdf"></i> Summary
                            </button>
                            <button type="button" id="btnExportPdf" class="btn-srp" style="background:#7c3aed;color:#fff;" title="Export Detail PDF (full stock ledger per product)">
                                <i class="fas fa-list-alt"></i> Detail
                            </button>
                            <button type="button" onclick="printReport()" class="btn-srp purple" title="Print View">
                                <i class="fas fa-print"></i>
                            </button>
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
                                    <th colspan="{{ $isSuperAdmin ? 8 : 7 }}" class="text-end">Grand Totals:</th>
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
                    [IS_SUPER_ADMIN ? 7 : 6, 'asc']
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
                        targets: IS_SUPER_ADMIN ? [7] : [6],
                        className: 'text-center'
                    },
                    {
                        targets: IS_SUPER_ADMIN ? [8, 9, 10, 11, 12, 13, 14, 15, 16, 18] : [7, 8, 9, 10, 11, 12, 13, 14, 15, 17],
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
                        '<span class="wh-name">' + w.warehouse_name + '</span>:' +
                        '<span class="wh-qty">' + w.display + '</span>' +
                        (IS_SUPER_ADMIN && w.branch_name ? '<br><small class="text-muted">Branch: ' + w.branch_name + '</small>' : '') +
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
                $('#ftSaleRet').text('+' + fmt(Math.abs(g.gSaleRet), 0));
                $('#ftAdjusted').text((g.gAdj >= 0 ? '+' : '-') + fmt(Math.abs(g.gAdj), 0));
                $('#ftBalance').text(fmt(g.gBal, 0));
                $('#ftPurAmt').text(fmtPKR(g.gPurAmt));
                $('#ftSaleAmt').text(fmtPKR(g.gSaleAmt));
                $('#ftStockVal').text(fmtPKR(g.gVal));
            }

            // ── Populate warehouse dropdown ──────────────────────────────────────
            function populateWarehouseFilter(warehouses) {
                if (_warehousesLoaded) return;
                _warehousesLoaded = true;
                var $sel = $('#filterWarehouse');
                $sel.find('option:not(:first)').remove();
                warehouses.forEach(function(w) {
                    $sel.append('<option value="' + w.id + '">' + w.warehouse_name + '</option>');
                });
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

            // ── SUMMARY PDF ──────────────────────────────────────────────────────
            $('#btnSummaryPdf').on('click', function() {
                if (!_allRows || _allRows.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'No Data', text: 'Please run a search first.' });
                    return;
                }

                var startDate = $('#start_date').val();
                var endDate   = $('#end_date').val();

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

                // Header
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(14);
                doc.text('THREE STARS MEDICAL SUPPLIES', 148, 12, { align: 'center' });
                doc.setFontSize(10);
                doc.setFont('helvetica', 'normal');
                doc.text('{{ $activeBranch->name ?? "Head Office" }}: {{ $activeBranch->address ?? "M17-18 Mezanine Floor Seth Centre, 10 Syed Mouj Darya Road (Edward Road) Lahore" }}', 148, 18, { align: 'center' });
                doc.text('Phone: {{ $activeBranch->number ?? "0092-42-37353433" }}', 148, 22, { align: 'center' });
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(12);
                doc.text('ITEM STOCK SUMMARY REPORT', 148, 28, { align: 'center' });
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                doc.text('Period: ' + (startDate || '—') + '  to  ' + (endDate || '—'), 148, 34, { align: 'center' });
                doc.text('Print Date: ' + new Date().toLocaleDateString('en-GB'), 270, 34, { align: 'right' });

                // Build table rows
                var tableBody = [];
                var totInit = 0, totIn = 0, totOut = 0, totBal = 0, totPurAmt = 0, totSaleAmt = 0, totVal = 0;

                _allRows.forEach(function(r, i) {
                    var ppb  = r.pieces_per_box || 1;
                    var bal  = parseFloat(r.balance || 0);
                    var boxes = Math.floor(bal / ppb);
                    var loose = Math.round(bal % ppb);

                    // Product full label: Brand - Name (UOM)
                    var label = (r.brand && r.brand !== '-' ? r.brand + ' - ' : '') + r.item_name;
                    var uomLabel = '';
                    if (r.packings && r.packings.length > 0) {
                        uomLabel = r.packings.map(function(p){ return p.name + '(' + p.pieces_per_box + ')'; }).join(' / ');
                    } else if (ppb > 1) {
                        uomLabel = ppb + ' pcs/box';
                    }

                    var inQty  = parseFloat(r.purchased     || 0);
                    var outQty = parseFloat(r.sold          || 0) + parseFloat(r.purchase_return_qty || 0);
                    var retIn  = parseFloat(r.sale_return_qty || 0);

                    totInit    += parseFloat(r.initial_stock || 0);
                    totIn      += inQty;
                    totOut     += outQty;
                    totBal     += bal;
                    totPurAmt  += parseFloat(r.purchase_amount || 0);
                    totSaleAmt += parseFloat(r.sale_amount     || 0);
                    totVal     += parseFloat(r.stock_value     || 0);

                    // Warehouse summary
                    var whText = (r.warehouses || []).map(function(w){ return w.warehouse_name + ':' + w.display; }).join('\n');

                    tableBody.push([
                        i + 1,
                        r.item_code || '-',
                        label + (uomLabel ? '\n' + uomLabel : ''),
                        r.category + (r.sub_category && r.sub_category !== '-' ? '/' + r.sub_category : ''),
                        fmt(r.initial_stock, 0),
                        fmt(Math.abs(inQty), 0),
                        fmt(Math.abs(parseFloat(r.purchase_return_qty || 0)), 0),
                        fmt(Math.abs(outQty - parseFloat(r.purchase_return_qty||0)), 0),
                        fmt(Math.abs(retIn), 0),
                        boxes + '.' + loose + '\n(' + fmt(bal, 0) + ' pcs)',
                        whText || '-',
                        fmtPKR(r.purchase_amount),
                        fmtPKR(r.sale_amount),
                        fmtPKR(r.stock_value),
                    ]);
                });

                // Footer row
                tableBody.push([
                    { content: 'GRAND TOTAL', colSpan: 4, styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: fmt(totInit, 0),    styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: fmt(totIn, 0),      styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: '',                 styles: { fillColor: [30, 58, 138] } },
                    { content: fmt(totOut, 0),     styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: '',                 styles: { fillColor: [30, 58, 138] } },
                    { content: fmt(totBal, 0),     styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: '',                 styles: { fillColor: [30, 58, 138] } },
                    { content: fmtPKR(totPurAmt),  styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: fmtPKR(totSaleAmt), styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                    { content: fmtPKR(totVal),     styles: { fontStyle: 'bold', fillColor: [30, 58, 138], textColor: 255 } },
                ]);

                doc.autoTable({
                    startY: 36,
                    head: [['#', 'Code', 'Product (Brand - Name / UOM)', 'Category', 'Opening', 'In (Pur)', 'Pur.Ret', 'Out (Sale)', 'Sale Ret', 'Balance', 'Warehouse', 'Pur Amt', 'Sale Amt', 'Stock Value']],
                    body: tableBody,
                    styles: { fontSize: 7, cellPadding: 1.5, valign: 'middle' },
                    headStyles: { fillColor: [30, 58, 138], textColor: 255, fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [240, 245, 255] },
                    columnStyles: {
                        0:  { cellWidth: 8,  halign: 'center' },
                        1:  { cellWidth: 18 },
                        2:  { cellWidth: 45 },
                        3:  { cellWidth: 22 },
                        4:  { cellWidth: 14, halign: 'right' },
                        5:  { cellWidth: 14, halign: 'right' },
                        6:  { cellWidth: 13, halign: 'right' },
                        7:  { cellWidth: 15, halign: 'right' },
                        8:  { cellWidth: 13, halign: 'right' },
                        9:  { cellWidth: 16, halign: 'center' },
                        10: { cellWidth: 28 },
                        11: { cellWidth: 22, halign: 'right' },
                        12: { cellWidth: 22, halign: 'right' },
                        13: { cellWidth: 22, halign: 'right' },
                    },
                    didDrawPage: function(data) {
                        var pageCount = doc.internal.getNumberOfPages();
                        doc.setFontSize(7);
                        doc.text('Page ' + data.pageNumber + ' of ' + pageCount, 148, doc.internal.pageSize.height - 5, { align: 'center' });
                    }
                });

                doc.save('item_summary_' + (startDate || 'all') + '_to_' + (endDate || 'all') + '.pdf');
            });

            // ── DETAIL PDF (Portrait A4 – Classic Ledger via html2canvas) ───────
            $('#btnExportPdf').on('click', async function() {
                var startDate   = $('#start_date').val();
                var endDate     = $('#end_date').val();
                var productId   = $('#product_id').val() || 'all';
                var warehouseId = $('#filterWarehouse').val() || 'all';
                var branchId    = IS_SUPER_ADMIN ? ($('#filterBranch').val() || 'all') : 'all';

                if (!startDate || !endDate) {
                    Swal.fire({ icon: 'warning', title: 'Required', text: 'Please select a date range first.' });
                    return;
                }
                if (!_allRows || _allRows.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'No Data', text: 'Please run a search first.' });
                    return;
                }

                $('#loaderOverlay').css('display', 'flex');
                $('#loaderOverlay p').text('Fetching ledger data from server...');

                try {
                    const res = await $.ajax({
                        url: "{{ route('report.item_stock.fetch') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            product_id: productId,
                            category_id: $('#filterCategory').val() || 'all',
                            sub_category_id: $('#filterSubCategory').val() || 'all',
                            brand_id: $('#filterBrand').val() || 'all',
                            warehouse_id: warehouseId,
                            start_date: startDate,
                            end_date: endDate,
                            branch_id: branchId,
                            report_type: 'ledger'
                        }
                    });

                    if (!res.data || res.data.length === 0) {
                        $('#loaderOverlay').hide();
                        Swal.fire({ icon: 'info', title: 'No Data', text: 'No products found.' });
                        return;
                    }

                    $('#loaderOverlay p').text('Building PDF layout...');

                    // ── Populate the hidden HTML template ───────────────────────
                    document.getElementById('pdfReportPeriod').textContent = startDate + ' -TO- ' + endDate;
                    document.getElementById('pdfPrintDate').textContent    = new Date().toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });

                    var $body = $('#pdfLedgerBody');
                    $body.empty();

                    var srBase = 1;

                    for (var pi = 0; pi < res.data.length; pi++) {
                        var product = res.data[pi];
                        var ledger  = (res.ledger_data || {})[product.id];

                        // Product display label: Brand  Name  UOM (Company)
                        var uomLabel = '';
                        if (product.packings && product.packings.length > 0) {
                            uomLabel = product.packings.map(function(p){ return p.name; }).join(' / ');
                        } else if ((product.pieces_per_box || 1) > 1) {
                            uomLabel = product.pieces_per_box + 'PCS';
                        }
                        var brandPart = (product.brand && product.brand !== '-') ? product.brand + ' &nbsp; ' : '';
                        var prodLabel = (product.item_name || '').toUpperCase()
                            + (uomLabel ? ' &nbsp; ' + uomLabel.toUpperCase() : '');

                        // — Product header row —
                        $body.append(
                            '<tr style="background:#d9e2f3;border:1px solid #aab8d8;">' +
                                '<td style="border:1px solid #aab8d8;padding:3px 5px;font-weight:bold;color:#1f3864;font-size:10.5px;">' +
                                    '<span style="margin-right:14px;">' + (pi + 1) + '</span>' +
                                '</td>' +
                                '<td colspan="7" style="border:1px solid #aab8d8;padding:3px 5px;font-weight:bold;color:#1f3864;font-size:10.5px;">' +
                                    prodLabel +
                                '</td>' +
                            '</tr>'
                        );

                        // Opening balance row
                        var openBal = ledger ? parseFloat(ledger.opening_balance) : 0;
                        $body.append(
                            '<tr>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">1</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;"></td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">OPENING STOCK</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;"></td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">0.00</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (openBal > 0 ? fmtN(openBal, 3) : '0.000') + '</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (openBal < 0 ? fmtN(Math.abs(openBal), 3) : '0.000') + '</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (openBal !== 0 ? fmtN(openBal, 3) : '') + '</td>' +
                            '</tr>'
                        );

                        // Transaction rows
                        if (ledger && ledger.transactions && ledger.transactions.length > 0) {
                            ledger.transactions.forEach(function(tx, idx) {
                                var runBal = (tx.balance !== undefined && tx.balance !== null) ? fmtN(parseFloat(tx.balance), 3) : '';
                                $body.append(
                                    '<tr>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">' + (idx + 2) + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">' + (tx.date || '') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">' + (tx.desc || '') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;">' + (tx.ref || '') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (tx.rate ? fmtPKR(tx.rate) : '0.00') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (tx.debit > 0 ? fmtN(tx.debit, 3) : '0.000') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + (tx.credit > 0 ? fmtN(tx.credit, 3) : '0.000') + '</td>' +
                                        '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;text-align:right;">' + runBal + '</td>' +
                                    '</tr>'
                                );
                            });
                        }

                        // Closing balance row
                        var closeBal = ledger ? parseFloat(ledger.closing_balance) : 0;
                        $body.append(
                            '<tr>' +
                                '<td colspan="7" style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;font-weight:bold;color:#c00;text-align:right;">Closing Balance :</td>' +
                                '<td style="border:1px solid #d0d8e8;padding:2.5px 5px;font-size:10.5px;font-weight:bold;color:#c00;text-align:right;">' + (closeBal !== 0 ? fmtN(closeBal, 3) : '') + '</td>' +
                            '</tr>'
                        );
                    }

                    // ── Render and export as PDF pages ───────────────────────────
                    var el  = document.getElementById('pdfA4Content');
                    var tmpl = document.getElementById('pdfLedgerTemplate');
                    tmpl.style.display = 'block';

                    // Let browser layout settle
                    await new Promise(function(r){ setTimeout(r, 300); });

                    $('#loaderOverlay p').text('Rendering PDF pages...');

                    var canvas = await html2canvas(el, {
                        scale: 2,
                        useCORS: true,
                        backgroundColor: '#ffffff',
                        logging: false
                    });

                    tmpl.style.display = 'none';
                    $('#loaderOverlay').hide();

                    // A4 at scale=2: each page = 794px * 2 = 1588px wide, 1123px * 2 = 2246px tall
                    var pageHeightPx = Math.round((canvas.width / 794) * 1123);
                    var totalPages   = Math.ceil(canvas.height / pageHeightPx);

                    const { jsPDF } = window.jspdf;
                    var pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

                    for (var page = 0; page < totalPages; page++) {
                        if (page > 0) pdf.addPage();

                        var srcY      = page * pageHeightPx;
                        var srcH      = Math.min(pageHeightPx, canvas.height - srcY);
                        var pageCanvas = document.createElement('canvas');
                        pageCanvas.width  = canvas.width;
                        pageCanvas.height = pageHeightPx;

                        var ctx = pageCanvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, pageCanvas.width, pageCanvas.height);
                        ctx.drawImage(canvas, 0, srcY, canvas.width, srcH, 0, 0, canvas.width, srcH);

                        var imgData = pageCanvas.toDataURL('image/jpeg', 0.95);
                        pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);
                    }

                    pdf.save('product_ledger_' + startDate + '_to_' + endDate + '.pdf');

                } catch (err) {
                    $('#loaderOverlay').hide();
                    document.getElementById('pdfLedgerTemplate').style.display = 'none';
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'PDF Error', text: (err.responseJSON && err.responseJSON.error) ? err.responseJSON.error : err.message || 'Failed to generate PDF.' });
                }
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
