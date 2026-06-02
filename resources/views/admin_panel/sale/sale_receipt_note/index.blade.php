@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #059669;
            /* Emerald 600 */
            --primary-dark: #047857;
            --primary-light: #ecfdf5;
            --secondary: #64748b;
            --accent: #10b981;
            --white: #ffffff;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        body {
            background-color: #f4f7fa;
            font-family: 'Outfit', sans-serif;
            color: var(--slate-800);
        }

        .main-content {
            padding: 1.5rem;
        }

        /* Hero Header Section */
        .hero-header {
            background: linear-gradient(135deg, #065f46 0%, #064e3b 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-text h4 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            color: white;
        }

        .hero-text p {
            font-size: 1rem;
            opacity: 0.8;
            margin: 0;
            font-weight: 400;
        }

        /* KPI Dashboard */
        .stat-card {
            background: var(--white);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.emerald {
            background: #d1fae5;
            color: #059669;
        }

        .stat-icon.amber {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.rose {
            background: #ffe4e6;
            color: #e11d48;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary);
            font-weight: 600;
            margin-top: 0.25rem;
        }

        /* Verified Table Styling */
        .premium-table-container {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        #grn-table {
            width: 100% !important;
        }

        #grn-table thead th {
            padding: 1.25rem 1rem;
            font-weight: 700;
            color: var(--slate-800);
            background: var(--slate-50);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--slate-200);
        }

        #grn-table tbody tr {
            border-bottom: 1px solid var(--slate-100);
            transition: background 0.2s;
        }

        #grn-table tbody tr:hover {
            background: var(--slate-50);
        }

        #grn-table td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Branding Elements */
        .grn-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
            border: 1px solid #a7f3d0;
        }

        .vendor-box {
            display: flex;
            flex-direction: column;
        }

        .vendor-name {
            font-weight: 700;
            color: var(--slate-900);
        }

        .business-tag {
            font-size: 0.75rem;
            color: var(--secondary);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Batch Information Grid */
        .batch-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
            max-width: 200px;
        }

        .batch-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lot-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .mfg-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
        }

        .exp-badge {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        /* Action Menus */
        .btn-dropdown {
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-dropdown:hover {
            background: var(--slate-200);
        }

        .dropdown-menu {
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--slate-200);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Financials Column */
        .amt-verified {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: var(--primary-dark);
            text-align: right;
        }

        .amt-status {
            font-size: 0.7rem;
            text-align: right;
            padding-top: 2px;
        }

        /* DataTables Premium Look */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 14px;
            padding: 0.75rem 1.25rem;
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            width: 300px;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            background-color: white;
        }

        /* Word-like print modal */
        #print-overlay { display:none; position:fixed; inset:0; background:#fff; z-index:9999; flex-direction:column; }
        #print-overlay.active { display:flex; }
        .po-topbar { height:36px; background:#f3f3f3; border-bottom:1px solid #ddd; display:flex; align-items:center; padding:0 16px; font-size:12px; color:#555; flex-shrink:0; }
        .po-body { display:flex; flex:1; overflow:hidden; }
        .po-sidebar { width:320px; flex-shrink:0; border-right:1px solid #e0e0e0; overflow-y:auto; padding:24px 20px; background:#fff; }
        .po-heading { font-size:28px; font-weight:300; color:#111; margin-bottom:20px; }
        .po-print-btn-row { display:flex; align-items:center; gap:16px; margin-bottom:24px; }
        .po-print-big { display:flex; flex-direction:column; align-items:center; background:#dce6f1; border:none; padding:10px 14px; border-radius:4px; cursor:pointer; width:80px; gap:4px; transition:background 0.15s; }
        .po-print-big:hover { background:#c8d8ed; }
        .po-section-title { font-size:14px; font-weight:600; color:#2e74b5; margin-bottom:10px; margin-top:4px; }
        .po-printer-box { border:1px solid #ccc; border-radius:3px; padding:8px 10px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; background:#fff; margin-bottom:6px; position:relative; }
        .po-printer-left { display:flex; align-items:center; gap:10px; }
        .po-ready-dot { width:10px; height:10px; background:#2e7d32; border-radius:50%; border:2px solid #fff; position:absolute; bottom:-1px; right:-2px; }
        .po-printer-props { font-size:12px; color:#2e74b5; cursor:pointer; display:block; margin-bottom:18px; text-decoration:none; }
        .po-printer-props:hover { text-decoration:underline; }
        .po-setting { border:1px solid #ccc; border-radius:3px; padding:7px 10px; display:flex; align-items:center; justify-content:space-between; cursor:pointer; background:#fff; margin-bottom:4px; user-select:none; position:relative; }
        .po-setting:hover { background:#f5f5f5; }
        .po-setting-left { display:flex; align-items:center; gap:10px; }
        .po-setting-text p { font-size:13px; color:#222; font-weight:500; }
        .po-setting-text span { font-size:11px; color:#666; }
        .po-dropdown-menu { display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #bbb; z-index:100; box-shadow:0 4px 12px rgba(0,0,0,0.12); }
        .po-dropdown-menu.open { display:block; }
        .po-dropdown-item { padding:8px 12px; font-size:13px; cursor:pointer; color:#222; }
        .po-dropdown-item:hover { background:#e8f0fb; color:#1a56a0; }
        .po-copies { display:flex; align-items:center; gap:8px; font-size:13px; color:#333; }
        .po-copies input { width:60px; padding:4px 6px; border:1px solid #ccc; font-size:13px; text-align:center; }
        .po-preview { flex:1; background:#b3b3b3; display:flex; flex-direction:column; align-items:center; justify-content:flex-start; overflow-y:auto; padding:32px 24px 16px; }
        .po-page { background:#fff; box-shadow:0 2px 12px rgba(0,0,0,0.3); width:595px; min-height:842px; padding:56px 48px; position:relative; flex-shrink:0; }
        .po-page.landscape { width:842px; min-height:595px; }
        .po-page-header { border-bottom:1px solid #ccc; padding-bottom:10px; margin-bottom:20px; }
        .po-page-header h2 { font-size:14px; font-weight:600; color:#2d2d5e; margin-bottom:2px; }
        .po-page-header p { font-size:10px; color:#888; }
        .po-page table { width:100%; border-collapse:collapse; font-size:9px; }
        .po-page thead { background:#f0f0f7; }
        .po-page th { padding:6px 8px; text-align:left; font-size:9px; font-weight:600; color:#444; border:0.5px solid #ddd; }
        .po-page td { padding:5px 8px; border:0.5px solid #ddd; color:#555; font-size:9px; }
        .po-page-footer { position:absolute; bottom:28px; left:48px; right:48px; display:flex; justify-content:space-between; border-top:0.5px solid #ccc; padding-top:6px; }
        .po-page-footer span { font-size:8px; color:#999; }
        .po-nav { background:#f3f3f3; border-top:1px solid #ddd; height:36px; display:flex; align-items:center; justify-content:center; gap:8px; flex-shrink:0; }
        .po-nav button { background:none; border:none; cursor:pointer; color:#333; padding:4px 6px; border-radius:3px; display:flex; align-items:center; }
        .po-nav button:hover { background:#e0e0e0; }
        .po-nav button:disabled { opacity:0.3; cursor:default; }
        .po-nav input { width:36px; text-align:center; border:1px solid #ccc; padding:2px 4px; font-size:12px; }
        .po-nav span { font-size:12px; color:#555; }
        .po-zoom { font-size:12px; color:#555; margin-left:20px; }

        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
            }

            .hero-header,
            .stat-card,
            .dataTables_wrapper .dataTables_filter,
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_paginate,
            .dataTables_wrapper .dataTables_info,
            .d-flex.gap-2,
            .btn,
            .page-link,
            .dropdown,
            .dropdown-menu,
            .dropdown-toggle,
            .form-control,
            .input-group,
            .search-box,
            .select2-container,
            nav,
            footer,
            .breadcrumb,
            .btn.btn-light,
            .btn.btn-outline-secondary {
                display: none !important;
            }

            .premium-table-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .premium-table-container .d-flex.justify-content-between {
                display: none !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            #grn-table,
            #grn-table th,
            #grn-table td {
                color: #000 !important;
                border: 1px solid #000 !important;
            }

            #grn-table th {
                background: #f8f8f8 !important;
            }

            #grn-table td {
                background: transparent !important;
            }

            .main-content {
                padding: 0 !important;
            }
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-text">
                    <h4>Sale Invoice Notes</h4>
                    <p>Verified sales and finalized outward inventory entries for Three Star Medical.</p>
                </div>
                <div>
                    @can('sales.create')
                        <a class="btn btn-light btn-lg px-5 shadow-lg fw-800 rounded-pill text-emerald" style="color: #065f46"
                            href="{{ route('sale.add', ['mode' => 'sin']) }}">
                            <i class="fas fa-file-signature me-2"></i> CREATE NEW INVOICE NOTE
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Print Overlay (Word-like) -->
            <div id="print-overlay">
              <div class="po-topbar">
                <span>Three-Star Medical · Verified Registry – Print Preview</span>
              </div>
              <div class="po-body">
                <div class="po-sidebar">
                  <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                    <button onclick="closePrint()" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;color:#555;padding:0;">
                      <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Back
                    </button>
                  </div>
                  <div class="po-heading">Print</div>
                  <div class="po-print-btn-row">
                    <button class="po-print-big" onclick="triggerPrint()">
                      <svg width="28" height="28" fill="none" stroke="#1f4e79" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                      <span>Print</span>
                    </button>
                    <div class="po-copies">
                      <label for="copies" style="font-size:13px;">Copies:</label>
                      <input type="number" id="copies" value="1" min="1" max="99">
                    </div>
                  </div>
                  <div class="po-section-title">Printer</div>
                  <div class="po-printer-box">
                    <div class="po-printer-left">
                      <svg width="28" height="28" fill="none" stroke="#1f4e79" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                      <div class="po-printer-info">
                        <p>Microsoft Print to PDF</p>
                        <span>Ready</span>
                      </div>
                    </div>
                  </div>
                  <a href="#" class="po-printer-props">Printer Properties</a>
                  <div class="po-section-title">Settings</div>
                  <div class="po-setting" onclick="toggleDropdown(this)">
                    <div class="po-setting-left">
                      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M3 9h18M9 21V9"/></svg>
                      <div class="po-setting-text"><p>Print All Pages</p><span>The whole thing</span></div>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                    <div class="po-dropdown-menu"><div class="po-dropdown-item" onclick="selectOption(this, 'Print All Pages', 'The whole thing')">Print All Pages</div></div>
                  </div>
                  <div class="po-setting" id="orientation-setting" onclick="toggleDropdown(this)">
                    <div class="po-setting-left">
                      <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="1"/></svg>
                      <div class="po-setting-text"><p>Portrait Orientation</p><span id="orientation-sub">Tall page layout</span></div>
                    </div>
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                    <div class="po-dropdown-menu"><div class="po-dropdown-item" onclick="setOrientation('portrait')">Portrait Orientation</div><div class="po-dropdown-item" onclick="setOrientation('landscape')">Landscape Orientation</div></div>
                  </div>
                </div>
                <div class="po-preview" id="po-preview">
                  <div class="po-page" id="po-page">
                    <div class="po-page-header">
                      <h2>Three Star Company – Verified Registry</h2>
                      <p>Printed on: <span id="print-date"></span> | Microsoft Print to PDF</p>
                    </div>
                    <table id="preview-table"><thead><tr><th>#ID</th><th>Verification Date</th><th>Sale Ref #</th><th>Customer / Institution</th><th>Product Details</th><th>Billing Detail</th></tr></thead><tbody><tr><td colspan="6" style="text-align:center;padding:24px;color:#bbb;font-size:10px;">No records found</td></tr></tbody></table>
                    <div class="po-page-footer">
                      <span>Three Star Company – Confidential</span>
                      <span>Page <span id="preview-page-num">1</span> of <span id="preview-total-pages">1</span></span>
                      <span id="footer-date"></span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="po-nav">
                <button id="nav-prev" onclick="changePage(-1)" disabled>
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <input type="number" id="nav-page" value="1" min="1" max="1" onchange="goToPage(this.value)">
                <span>of <span id="nav-total">1</span></span>
                <button id="nav-next" onclick="changePage(1)" disabled>
                  <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
                </button>
                <span class="po-zoom">49%</span>
              </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-5">
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-check-double"></i></div>
                        <div class="stat-value">{{ number_format($sales->count()) }}</div>
                        <div class="stat-label">Verified Sales</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon amber"><i class="fas fa-money-check-alt"></i></div>
                        <div class="stat-value">Rs. {{ number_format($sales->sum('total_net'), 0) }}</div>
                        <div class="stat-label">Total Sale Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon rose"><i class="fas fa-hand-holding-usd"></i></div>
                        <div class="stat-value">Rs. {{ number_format($sales->sum('total_net'), 0) }}</div>
                        <div class="stat-label">Total Dispatched Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-box-open"></i></div>
                        <div class="stat-value">Stocked</div>
                        <div class="stat-label">System Updated</div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="premium-table-container">
                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <h5 class="fw-800 m-0 text-slate-800"><i class="fas fa-stream me-2 text-primary"></i>Verified Registry
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" id="export-registry" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                            class="fas fa-download me-2"></i>Excel</button>
                        <button type="button" id="print-registry" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                            class="fas fa-print me-2"></i>Print Registry</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="grn-table" class="table align-middle datanew">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Verification Date</th>
                                <th>Sale Ref #</th>
                                <th>Customer / Institution</th>
                                <th>Product Details</th>
                                <th class="text-end">Billing Detail</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sales as $sale)
                                <tr>
                                    <td class="fw-bold text-slate-400">#{{ $sale->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-emerald" style="font-size: 1.1rem;"><i
                                                    class="fas fa-calendar-check"></i></div>
                                            <span
                                                class="fw-bold">{{ \Carbon\Carbon::parse($sale->sale_date)->format('d-m-Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="grn-pill text-nowrap">{{ $sale->invoice_no }}</span>
                                            @if($sale->sale_status === 'un-post')
                                                <span class="badge badge-warning text-dark uppercase fw-800 animate__animated animate__pulse animate__infinite" style="font-size: 0.65rem; border: 1px solid #d97706; padding: 4px 8px; border-radius: 6px;">UN-POSTED</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vendor-box">
                                            <span
                                                class="vendor-name">{{ $sale->customer_relation->customer_name ?? 'System Customer' }}</span>
                                            @if($sale->sale_status === 'un-post')
                                                <span class="text-warning small fw-bold d-block mb-1" style="font-size: 0.65rem;"><i class="fas fa-clock"></i> PENDING VERIFICATION</span>
                                            @endif
                                            <span class="business-tag"><i
                                                    class="fas fa-user me-1"></i>{{ $sale->customer_relation->business_name ?? 'Individual' }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="batch-grid">
                                            @foreach ($sale->items as $item)
                                                @php
                                                    $prod = $item->product;
                                                    $qtyString = $item->total_pieces . " Pcs";
                                                @endphp
                                                <span class="detail-tag">{{ ($prod->brand->name ?? '') . ' ' . ($prod->item_name ?? 'Item') }}
                                                    ({{ $qtyString }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="text-muted mb-1 font-monospace" style="font-size: 0.65rem; text-transform: uppercase; line-height: 1.2;">
                                            Gross: <span class="fw-bold text-dark">{{ number_format($sale->total_bill_amount, 2) }}</span><br>
                                            GST: <span class="fw-bold text-dark">{{ number_format($sale->total_gst, 2) }}</span><br>
                                            @if(($sale->total_inc_tax ?? 0) > 0) Inc. Tax: <span class="fw-bold text-dark">{{ number_format($sale->total_inc_tax, 2) }}</span><br> @endif
                                            @if(($sale->total_adv_tax ?? 0) > 0) Adv. Tax: <span class="fw-bold text-dark">{{ number_format($sale->total_adv_tax, 2) }}</span><br> @endif
                                            Disc: <span class="fw-bold text-danger">{{ number_format($sale->total_extradiscount, 2) }}</span>
                                        </div>
                                        <div class="amt-verified">
                                            @if (($sale->total_returned ?? 0) > 0)
                                                <div class="text-decoration-line-through text-muted small"
                                                    style="font-size: 0.7rem;">
                                                    {{ number_format($sale->total_net, 2) }}</div>
                                                <span>{{ number_format($sale->updated_net_amount, 2) }}</span>
                                            @else
                                                {{ number_format($sale->total_net, 2) }}
                                            @endif
                                        </div>
                                        <div class="amt-status">
                                            @php $displayPrice = ($sale->total_returned ?? 0) > 0 ? $sale->updated_net_amount : $sale->total_net; @endphp
                                            @if($sale->sale_status === 'post')
                                                <span class="text-success fw-800"><i
                                                        class="fas fa-check-circle me-1"></i>Posted</span>
                                            @else
                                                <span class="text-warning fw-800"><i
                                                        class="fas fa-file-signature me-1"></i>Un-posted</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-dropdown" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fas fa-ellipsis-h me-1"></i> Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right shadow-lg">
                                                @can('sales.view')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('sales.invoice', $sale->id) }}">
                                                            <i class="fas fa-file-invoice text-emerald"></i> View Invoice</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="{{ route('sales.dc', $sale->id) }}">
                                                            <i class="fas fa-shipping-fast text-amber"></i> Dispatch Note</a>
                                                    </li>
                                                @endcan
                                                @if (!$sale->is_fully_returned)
                                                    @if($sale->sale_status === 'post')
                                                        @can('sales.create')
                                                            <div class="dropdown-divider"></div>
                                                            <li><a class="dropdown-item text-danger"
                                                                    href="{{ route('sale.return.show', $sale->id) }}">
                                                                    <i class="fas fa-undo-alt"></i> Process Return</a></li>
                                                        @endcan
                                                        @can('sales.unpost')
                                                            <li><a class="dropdown-item text-warning btn-unpost-srn"
                                                                   href="javascript:void(0);" 
                                                                   data-id="{{ $sale->id }}" 
                                                                   data-invoice="{{ $sale->invoice_no }}">
                                                                   <i class="fas fa-history"></i> Un-post SIN</a></li>
                                                        @endcan
                                                    @elseif($sale->sale_status === 'un-post')
                                                        @can('sales.edit')
                                                            <div class="dropdown-divider"></div>
                                                            <li><a class="dropdown-item text-primary"
                                                                   href="{{ route('sales.edit', $sale->id) }}">
                                                                   <i class="fas fa-edit"></i> Edit Draft SIN</a></li>
                                                        @endcan
                                                    @endif
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search and filter records..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            function exportTableToExcel(tableID, filename = '') {
                var downloadLink;
                var dataType = 'application/vnd.ms-excel';
                var tableSelect = document.getElementById(tableID);
                if (!tableSelect) return;
                var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');

                filename = filename ? filename + '.xls' : 'Verified_Sales_Registry.xls';
                downloadLink = document.createElement('a');
                document.body.appendChild(downloadLink);

                if (navigator.msSaveOrOpenBlob) {
                    var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
                    navigator.msSaveOrOpenBlob(blob, filename);
                } else {
                    downloadLink.style.display = 'none';
                    var encodedData = encodeURIComponent('\uFEFF' + tableHTML);
                    downloadLink.href = 'data:' + dataType + ';charset=utf-8,' + encodedData;
                    downloadLink.download = filename;
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                }
            }

            function printRegistry() {
                window.print();
            }

            $('#export-registry').on('click', function(e) {
                e.preventDefault();
                exportTableToExcel('grn-table', 'Verified_Sales_Registry');
            });

            $('#print-registry').on('click', function(e) {
                e.preventDefault();
                openPrint();
            });

            $(document).on('click', '.btn-unpost-srn', function() {
                let id = $(this).data('id');
                let invoice = $(this).data('invoice');
                
                Swal.fire({
                    title: 'Un-post SIN?',
                    text: "Reverting SIN " + invoice + " to DRAFT will PERMANENTLY DELETE accounting/payment records. Continue?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Un-post Now',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "{{ url('sales') }}/" + id + "/unpost";
                    }
                });
            });
        });

        (function () {
            var now = new Date();
            var dateStr = now.toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' });
            document.getElementById('print-date').textContent = dateStr;
            document.getElementById('footer-date').textContent = dateStr;
        })();

        function openPrint() {
            var dtInstance = $('#grn-table').DataTable();
            var previewBody = document.querySelector('#preview-table tbody');
            if (!previewBody) return;

            previewBody.innerHTML = '';
            var allRows = dtInstance.rows().nodes().toArray();

            if (allRows.length === 0) {
                previewBody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#bbb;font-size:10px;">No records found</td></tr>';
            } else {
                allRows.forEach(function (row) {
                    var cells = row.querySelectorAll('td');
                    if (cells.length === 0) return;
                    var newRow = document.createElement('tr');
                    [0, 1, 2, 3, 4, 5].forEach(function (index) {
                        var td = document.createElement('td');
                        td.textContent = cells[index] ? cells[index].textContent.trim() : '';
                        newRow.appendChild(td);
                    });
                    previewBody.appendChild(newRow);
                });
            }

            document.getElementById('print-overlay').classList.add('active');
            document.body.style.overflow = 'hidden';
            closeAllDropdowns();
        }

        function closePrint() {
            document.getElementById('print-overlay').classList.remove('active');
            document.body.style.overflow = '';
        }

        function triggerPrint() {
            var page = document.getElementById('po-page').outerHTML;
            var w = window.open('', '_blank');
            w.document.write('<!DOCTYPE html><html><head><title>Print Registry</title><style>' +
                'body { font-family: Segoe UI, sans-serif; margin: 0; padding: 40px; }' +
                'table { width:100%; border-collapse:collapse; font-size:9px; }' +
                'th,td { border:0.5px solid #ddd; padding:5px 8px; text-align:left; }' +
                'thead { background:#f0f0f7; }' +
                'h2 { font-size:13px; margin-bottom:4px; }' +
                'p { font-size:9px; color:#888; margin:0; }' +
                '.po-page-footer { display:flex; justify-content:space-between; border-top:0.5px solid #ccc; padding-top:6px; margin-top:40px; font-size:8px; color:#999; }' +
                '</style></head><body>' + page + '</body></html>');
            w.document.close();
            w.focus();
            setTimeout(function () { w.print(); w.close(); }, 400);
        }

        function toggleDropdown(el) {
            var menu = el.querySelector('.po-dropdown-menu');
            if (!menu) return;
            var wasOpen = menu.classList.contains('open');
            closeAllDropdowns();
            if (!wasOpen) menu.classList.add('open');
            event.stopPropagation();
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.po-dropdown-menu.open').forEach(function (m) {
                m.classList.remove('open');
            });
        }

        document.addEventListener('click', closeAllDropdowns);

        function selectOption(item, title, sub) {
            event.stopPropagation();
            var setting = item.closest('.po-setting');
            setting.querySelector('.po-setting-text p').textContent = title;
            setting.querySelector('.po-setting-text span').textContent = sub;
            closeAllDropdowns();
        }

        function setOrientation(mode) {
            event.stopPropagation();
            var page = document.getElementById('po-page');
            var setting = document.getElementById('orientation-setting');
            if (mode === 'landscape') {
                page.classList.add('landscape');
                setting.querySelector('.po-setting-text p').textContent = 'Landscape Orientation';
                setting.querySelector('.po-setting-text span').textContent = 'Wide page layout';
            } else {
                page.classList.remove('landscape');
                setting.querySelector('.po-setting-text p').textContent = 'Portrait Orientation';
                setting.querySelector('.po-setting-text span').textContent = 'Tall page layout';
            }
            closeAllDropdowns();
        }

        var currentPage = 1;
        var totalPages = 1;

        function changePage(dir) {
            currentPage = Math.min(Math.max(1, currentPage + dir), totalPages);
            document.getElementById('nav-page').value = currentPage;
            updateNavButtons();
        }

        function goToPage(val) {
            currentPage = Math.min(Math.max(1, parseInt(val) || 1), totalPages);
            document.getElementById('nav-page').value = currentPage;
            updateNavButtons();
        }

        function updateNavButtons() {
            document.getElementById('nav-prev').disabled = currentPage <= 1;
            document.getElementById('nav-next').disabled = currentPage >= totalPages;
        }

        updateNavButtons();
    </script>
@endsection