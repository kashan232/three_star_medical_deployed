@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #059669;
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

        .main-content { padding: 1.5rem; }

        /* Hero Header */
        .hero-header {
            background: linear-gradient(135deg, #065f46 0%, #064e3b 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
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
            top: -20%; right: -10%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(16,185,129,0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-text h4 { font-weight: 800; font-size: 2rem; margin-bottom: 0.5rem; letter-spacing: -0.02em; color: white; }
        .hero-text p  { font-size: 1rem; opacity: 0.8; margin: 0; font-weight: 400; }

        /* Stat Cards */
        .stat-card {
            background: var(--white);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px) scale(1.02); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1); border-color: var(--primary); }

        .stat-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .stat-icon.emerald { background: #d1fae5; color: #059669; }
        .stat-icon.amber   { background: #fef3c7; color: #d97706; }
        .stat-icon.rose    { background: #ffe4e6; color: #e11d48; }

        .stat-value { font-size: 1.75rem; font-weight: 800; color: var(--slate-900); line-height: 1; }
        .stat-label { font-size: 0.875rem; color: var(--secondary); font-weight: 600; margin-top: 0.25rem; }

        /* Table */
        .premium-table-container {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        #grn-table { width: 100% !important; }

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

        #grn-table tbody tr { border-bottom: 1px solid var(--slate-100); transition: background 0.2s; }
        #grn-table tbody tr:hover { background: var(--slate-50); }
        #grn-table td { padding: 1.25rem 1rem; vertical-align: middle; font-size: 0.9rem; }

        .grn-pill {
            display: inline-flex; align-items: center;
            padding: 0.4rem 0.8rem;
            background: var(--primary-light); color: var(--primary-dark);
            border-radius: 100px; font-weight: 700; font-size: 0.75rem;
            border: 1px solid #a7f3d0;
        }

        .vendor-box { display: flex; flex-direction: column; }
        .vendor-name { font-weight: 700; color: var(--slate-900); }
        .business-tag { font-size: 0.75rem; color: var(--secondary); font-weight: 500; margin-top: 2px; }

        .batch-grid { display: grid; grid-template-columns: 1fr; gap: 4px; max-width: 200px; }
        .batch-badge { font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; }
        .lot-badge { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .mfg-badge { background: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }
        .exp-badge { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }

        .btn-dropdown { background: var(--slate-100); border: 1px solid var(--slate-200); border-radius: 10px; padding: 0.5rem 1rem; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; }
        .btn-dropdown:hover { background: var(--slate-200); }

        .dropdown-menu { border-radius: 16px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); border: 1px solid var(--slate-200); padding: 0.5rem; }
        .dropdown-item { border-radius: 8px; padding: 0.6rem 1rem; font-weight: 500; display: flex; align-items: center; gap: 0.75rem; }
        .dropdown-item i { width: 16px; text-align: center; }
        .dropdown-item:hover { background: var(--primary-light); color: var(--primary-dark); }

        .amt-verified { font-family: 'JetBrains Mono', monospace; font-weight: 800; color: var(--primary-dark); text-align: right; }
        .amt-status { font-size: 0.7rem; text-align: right; padding-top: 2px; }

        .dataTables_wrapper .dataTables_filter input {
            border-radius: 14px; padding: 0.75rem 1.25rem;
            background-color: var(--slate-50); border: 1px solid var(--slate-200);
            width: 300px; transition: all 0.2s;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none; border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16,185,129,0.1); background-color: white;
        }

        /* ═══════════════════════════════════════
           WORD-LIKE PRINT OVERLAY
        ═══════════════════════════════════════ */
        #print-overlay {
            display: none; position: fixed; inset: 0;
            background: #fff; z-index: 9999; flex-direction: column;
        }
        #print-overlay.active { display: flex; }

        .po-topbar {
            height: 36px; background: #f3f3f3; border-bottom: 1px solid #ddd;
            display: flex; align-items: center; padding: 0 16px;
            font-size: 12px; color: #555; flex-shrink: 0;
        }

        .po-body { display: flex; flex: 1; overflow: hidden; }

        .po-sidebar {
            width: 320px; flex-shrink: 0; border-right: 1px solid #e0e0e0;
            overflow-y: auto; padding: 24px 20px; background: #fff;
        }
        .po-sidebar::-webkit-scrollbar { width: 6px; }
        .po-sidebar::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }

        .po-heading { font-size: 28px; font-weight: 300; color: #111; margin-bottom: 20px; }

        .po-print-btn-row { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; }

        .po-print-big {
            display: flex; flex-direction: column; align-items: center;
            background: #dce6f1; border: none; padding: 10px 14px;
            border-radius: 4px; cursor: pointer; width: 80px; gap: 4px;
            transition: background 0.15s;
        }
        .po-print-big:hover { background: #c8d8ed; }
        .po-print-big span { font-size: 12px; color: #1f4e79; font-weight: 600; }

        .po-copies { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #333; }
        .po-copies input { width: 60px; padding: 4px 6px; border: 1px solid #ccc; font-size: 13px; text-align: center; }

        .po-section-title { font-size: 14px; font-weight: 600; color: #2e74b5; margin-bottom: 10px; margin-top: 4px; }

        .po-printer-box {
            border: 1px solid #ccc; border-radius: 3px; padding: 8px 10px;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; background: #fff; margin-bottom: 6px; position: relative;
        }
        .po-printer-left { display: flex; align-items: center; gap: 10px; }
        .po-printer-icon { position: relative; }
        .po-ready-dot {
            width: 10px; height: 10px; background: #2e7d32; border-radius: 50%;
            border: 2px solid #fff; position: absolute; bottom: -1px; right: -2px;
        }
        .po-printer-info p  { font-size: 13px; color: #222; font-weight: 500; margin: 0; }
        .po-printer-info span { font-size: 11px; color: #666; }

        .po-printer-props {
            font-size: 12px; color: #2e74b5; cursor: pointer;
            display: block; margin-bottom: 18px; text-decoration: none;
        }
        .po-printer-props:hover { text-decoration: underline; }

        .po-setting {
            border: 1px solid #ccc; border-radius: 3px; padding: 7px 10px;
            display: flex; align-items: center; justify-content: space-between;
            cursor: pointer; background: #fff; margin-bottom: 4px;
            user-select: none; position: relative;
        }
        .po-setting:hover { background: #f5f5f5; }
        .po-setting-left { display: flex; align-items: center; gap: 10px; }
        .po-setting-left svg { color: #555; flex-shrink: 0; }
        .po-setting-text p    { font-size: 13px; color: #222; font-weight: 500; margin: 0; }
        .po-setting-text span { font-size: 11px; color: #666; }
        .po-chevron { color: #666; }

        .po-pages-row { display: flex; align-items: center; gap: 10px; margin-bottom: 4px; padding: 4px 0; }
        .po-pages-row label { font-size: 13px; color: #444; width: 46px; }
        .po-pages-row input { flex: 1; border: 1px solid #ccc; padding: 5px 8px; font-size: 13px; border-radius: 2px; }

        .po-dropdown-menu {
            display: none; position: absolute; top: 100%; left: 0; right: 0;
            background: #fff; border: 1px solid #bbb; z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .po-dropdown-menu.open { display: block; }
        .po-dropdown-item { padding: 8px 12px; font-size: 13px; cursor: pointer; color: #222; }
        .po-dropdown-item:hover { background: #e8f0fb; color: #1a56a0; }

        .po-preview {
            flex: 1; background: #b3b3b3;
            display: flex; flex-direction: column; align-items: center;
            justify-content: flex-start; overflow-y: auto; padding: 32px 24px 16px;
        }

        .po-page {
            background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.3);
            width: 595px; min-height: 842px; padding: 56px 48px;
            position: relative; flex-shrink: 0;
            transition: width 0.3s, min-height 0.3s;
        }
        .po-page.landscape { width: 842px; min-height: 595px; }

        .po-page-header { border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        .po-page-header h2 { font-size: 14px; font-weight: 600; color: #2d2d5e; margin-bottom: 2px; }
        .po-page-header p  { font-size: 10px; color: #888; margin: 0; }

        .po-page table   { width: 100%; border-collapse: collapse; font-size: 9px; }
        .po-page thead   { background: #f0f0f7; }
        .po-page th      { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: 600; color: #444; border: 0.5px solid #ddd; }
        .po-page td      { padding: 5px 8px; border: 0.5px solid #ddd; color: #555; font-size: 9px; }

        .po-page-footer {
            position: absolute; bottom: 28px; left: 48px; right: 48px;
            display: flex; justify-content: space-between; align-items: center;
            border-top: 0.5px solid #ccc; padding-top: 6px;
        }
        .po-page-footer span { font-size: 8px; color: #999; }

        .po-nav {
            background: #f3f3f3; border-top: 1px solid #ddd; height: 36px;
            display: flex; align-items: center; justify-content: center;
            gap: 8px; flex-shrink: 0;
        }
        .po-nav button { background: none; border: none; cursor: pointer; color: #333; padding: 4px 6px; border-radius: 3px; display: flex; align-items: center; }
        .po-nav button:hover { background: #e0e0e0; }
        .po-nav button:disabled { opacity: 0.3; cursor: default; }
        .po-nav input { width: 36px; text-align: center; border: 1px solid #ccc; padding: 2px 4px; font-size: 12px; }
        .po-nav span { font-size: 12px; color: #555; }
        .po-zoom { font-size: 12px; color: #555; margin-left: 20px; }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-text">
                    <h4>Goods Receipt Notes</h4>
                    <p>Verified invoices and finalized inventory entries for Three Star Medical.</p>
                </div>
                <div>
                    @can('purchases.create')
                        <a class="btn btn-light btn-lg px-5 shadow-lg fw-800 rounded-pill text-emerald"
                           style="color: #065f46"
                           href="{{ route('add_purchase', ['mode' => 'grn']) }}">
                            <i class="fas fa-file-signature me-2"></i> CREATE NEW GRN
                        </a>
                    @endcan
                </div>
            </div>

            <!-- ═══════════════════════════════════════
                 PRINT OVERLAY (Word-like)
            ═══════════════════════════════════════ -->
            <div id="print-overlay">

                <div class="po-topbar">
                    <span>Three-Star Medical · Verified Registry – Print Preview</span>
                </div>

                <div class="po-body">

                    <!-- Sidebar -->
                    <div class="po-sidebar">

                        <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                            <button onclick="closePrint()" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;font-size:13px;color:#555;padding:0;">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                                Back
                            </button>
                        </div>

                        <div class="po-heading">Print</div>

                        <div class="po-print-btn-row">
                            <button class="po-print-big" onclick="triggerPrint()">
                                <svg width="28" height="28" fill="none" stroke="#1f4e79" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                                <span>Print</span>
                            </button>
                            <div class="po-copies">
                                <label for="copies">Copies:</label>
                                <input type="number" id="copies" value="1" min="1" max="99">
                            </div>
                        </div>

                        <!-- Printer -->
                        <div class="po-section-title">Printer</div>
                        <div class="po-printer-box">
                            <div class="po-printer-left">
                                <div class="po-printer-icon">
                                    <svg width="28" height="28" fill="none" stroke="#1f4e79" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                                    <div class="po-ready-dot"></div>
                                </div>
                                <div class="po-printer-info">
                                    <p>Microsoft Print to PDF</p>
                                    <span>Ready</span>
                                </div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <a href="#" class="po-printer-props">Printer Properties</a>

                        <!-- Settings -->
                        <div class="po-section-title">Settings</div>

                        <!-- Print All Pages -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M3 9h18M9 21V9"/></svg>
                                <div class="po-setting-text"><p>Print All Pages</p><span>The whole thing</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print All Pages','The whole thing')">Print All Pages</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print Selection','Selected content only')">Print Selection</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print Current Page','Current page only')">Print Current Page</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Custom Print','Custom range')">Custom Print</div>
                            </div>
                        </div>

                        <!-- Pages input -->
                        <div class="po-pages-row">
                            <label>Pages:</label>
                            <input type="text" placeholder="e.g. 1-5, 8">
                            <svg style="color:#999;cursor:pointer;flex-shrink:0;" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                        </div>

                        <!-- Print One Sided -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>
                                <div class="po-setting-text"><p>Print One Sided</p><span>Only print on one side of the...</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print One Sided','Only print on one side of the...')">Print One Sided</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print Two Sided (Long Edge)','Flip on long edge')">Print Two Sided (Long Edge)</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Print Two Sided (Short Edge)','Flip on short edge')">Print Two Sided (Short Edge)</div>
                            </div>
                        </div>

                        <!-- Collated -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="5" width="9" height="12" rx="1"/><rect x="8" y="5" width="9" height="12" rx="1"/><rect x="14" y="5" width="9" height="12" rx="1"/></svg>
                                <div class="po-setting-text"><p>Collated</p><span>1,2,3 &nbsp; 1,2,3 &nbsp; 1,2,3</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'Collated','1,2,3  1,2,3  1,2,3')">Collated</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Uncollated','1,1,1  2,2,2  3,3,3')">Uncollated</div>
                            </div>
                        </div>

                        <!-- Orientation -->
                        <div class="po-setting" id="orientation-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="1"/></svg>
                                <div class="po-setting-text"><p>Portrait Orientation</p><span id="orientation-sub">Tall page layout</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="setOrientation('portrait')">Portrait Orientation</div>
                                <div class="po-dropdown-item" onclick="setOrientation('landscape')">Landscape Orientation</div>
                            </div>
                        </div>

                        <!-- Paper Size -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="6" y="2" width="12" height="16" rx="1"/><rect x="3" y="6" width="12" height="16" rx="1"/></svg>
                                <div class="po-setting-text"><p>Letter</p><span>21.59 cm × 27.94 cm</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'Letter','21.59 cm × 27.94 cm')">Letter</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'A4','21.00 cm × 29.70 cm')">A4</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Legal','21.59 cm × 35.56 cm')">Legal</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Executive','18.41 cm × 26.67 cm')">Executive</div>
                            </div>
                        </div>

                        <!-- Margins -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="1"/><rect x="6" y="6" width="12" height="12"/></svg>
                                <div class="po-setting-text"><p>Normal Margins</p><span>Left: 2.54 cm &nbsp; Right: 2.54 cm</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'Normal Margins','Left: 2.54 cm   Right: 2.54 cm')">Normal</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Narrow Margins','Left: 1.27 cm   Right: 1.27 cm')">Narrow</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Wide Margins','Left: 5.08 cm   Right: 5.08 cm')">Wide</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'Mirrored Margins','Inside: 2.54 cm  Outside: 2.54 cm')">Mirrored</div>
                            </div>
                        </div>

                        <!-- Pages Per Sheet -->
                        <div class="po-setting" onclick="toggleDropdown(this)">
                            <div class="po-setting-left">
                                <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M12 3v18M3 12h18"/></svg>
                                <div class="po-setting-text"><p>1 Page Per Sheet</p><span>Standard single page</span></div>
                            </div>
                            <svg class="po-chevron" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                            <div class="po-dropdown-menu">
                                <div class="po-dropdown-item" onclick="selectOption(this,'1 Page Per Sheet','Standard single page')">1 Page Per Sheet</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'2 Pages Per Sheet','Two pages side by side')">2 Pages Per Sheet</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'4 Pages Per Sheet','Four pages per sheet')">4 Pages Per Sheet</div>
                                <div class="po-dropdown-item" onclick="selectOption(this,'6 Pages Per Sheet','Six pages per sheet')">6 Pages Per Sheet</div>
                            </div>
                        </div>

                    </div><!-- /po-sidebar -->

                    <!-- Preview -->
                    <div class="po-preview" id="po-preview">
                        <div class="po-page" id="po-page">
                            <div class="po-page-header">
                                <h2>Three Star Company – Verified Registry</h2>
                                <p>Printed on: <span id="print-date"></span> &nbsp;|&nbsp; Microsoft Print to PDF</p>
                            </div>
                            <table id="preview-table">
                                <thead>
                                    <tr>
                                        <th>#ID</th>
                                        <th>Verification Date</th>
                                        <th>GRN Number</th>
                                        <th>Vendor / Company Entity</th>
                                        <th>Total Qty</th>
                                        <th>Batch Info</th>
                                        <th>Billing Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" style="text-align:center;padding:24px;color:#bbb;font-size:10px;">No records found</td></tr>
                                </tbody>
                            </table>
                            <div class="po-page-footer">
                                <span>Three Star Company – Confidential</span>
                                <span>Page <span id="preview-page-num">1</span> of <span id="preview-total-pages">1</span></span>
                                <span id="footer-date"></span>
                            </div>
                        </div>
                    </div>

                </div><!-- /po-body -->

                <!-- Bottom nav -->
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

            </div><!-- /print-overlay -->

            <!-- Dashboard Stats -->
            <div class="row mb-5">
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-check-double"></i></div>
                        <div class="stat-value">{{ number_format($Purchase->count()) }}</div>
                        <div class="stat-label">Verified GRNs</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon amber"><i class="fas fa-money-check-alt"></i></div>
                        <div class="stat-value">Rs. {{ number_format($Purchase->sum('net_amount'), 0) }}</div>
                        <div class="stat-label">Total Inventory Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon rose"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-value">Rs. {{ number_format($Purchase->sum('due_amount'), 0) }}</div>
                        <div class="stat-label">Pending Liabilities</div>
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
                    <h5 class="fw-800 m-0 text-slate-800">
                        <i class="fas fa-stream me-2 text-primary"></i>Verified Registry
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" id="export-registry" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-download me-2"></i>Excel
                        </button>
                        <button type="button" id="print-registry" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-print me-2"></i>Print Registry
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="grn-table" class="table align-middle datanew">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Verification Date</th>
                                <th>GRN Number</th>
                                <th>Vendor / Company Entity</th>
                                <th class="text-center">Total Qty</th>
                                <th>Batch Information</th>
                                <th class="text-end">Billing Detail</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Purchase as $purchase)
                                <tr>
                                    <td class="fw-bold text-slate-400">#{{ $purchase->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-emerald" style="font-size:1.1rem;"><i class="fas fa-calendar-check"></i></div>
                                            <span class="fw-600">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d-m-Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="grn-pill" style="width:fit-content;">{{ $purchase->invoice_no }}</span>
                                            @if($purchase->po_ref)
                                                <div class="small text-muted mt-1" style="font-size:0.7rem;">PO: {{ $purchase->po_ref }}</div>
                                            @endif
                                            @if($purchase->status_purchase == 'un-post')
                                                <span class="badge badge-danger" style="font-size:0.6rem;width:fit-content;">UNPOSTED</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vendor-box">
                                            <span class="vendor-name">{{ $purchase->vendor->name ?? 'System Vendor' }}</span>
                                            <span class="business-tag"><i class="fas fa-building me-1"></i>{{ $purchase->vendor->business_name ?? 'Health Institution' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-800 text-slate-800 fs-6">{{ number_format($purchase->total_original_pieces, 0) }}</div>
                                        <div class="text-muted fw-600" style="font-size:0.65rem;">Total Pcs Received</div>
                                    </td>
                                    <td>
                                        <div class="batch-grid">
                                            @if ($purchase->batch_summary != '-')
                                                <div class="batch-badge lot-badge">LOT: {{ $purchase->batch_summary }}</div>
                                            @endif
                                            @if ($purchase->mfg_summary != '-')
                                                <div class="batch-badge mfg-badge">MFG: {{ $purchase->mfg_summary }}</div>
                                            @endif
                                            @if ($purchase->exp_summary != '-')
                                                <div class="batch-badge exp-badge">EXP: {{ $purchase->exp_summary }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="small text-muted fw-bold" style="font-size:0.7rem;">
                                                GROSS: <span class="text-dark">{{ number_format($purchase->gross_total, 2) }}</span>
                                            </div>
                                            <div class="small text-danger fw-bold" style="font-size:0.7rem;">
                                                DISC: -{{ number_format($purchase->discount_amount, 2) }}
                                            </div>
                                            <div class="small text-success fw-bold mb-1" style="font-size:0.7rem;">
                                                TAX: +{{ number_format($purchase->total_gst, 2) }}
                                            </div>
                                            <div class="amt-verified fs-6 mb-0">
                                                {{ number_format($purchase->net_amount, 2) }}
                                            </div>
                                            <div class="amt-status">
                                                @if ($purchase->due_amount > 0)
                                                    <span class="text-danger fw-700">Due: {{ number_format($purchase->due_amount, 2) }}</span>
                                                @else
                                                    <span class="text-success fw-800"><i class="fas fa-check-circle me-1"></i>Fully Paid</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-dropdown" type="button" data-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-h me-1"></i> Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right shadow-lg">
                                                @can('purchases.view')
                                                    <li><a class="dropdown-item" href="{{ route('purchase.invoice', $purchase->id) }}">
                                                        <i class="fas fa-file-invoice text-emerald"></i> View Invoice</a></li>
                                                    <li><a class="dropdown-item" href="{{ route('purchase.grn_report', $purchase->id) }}" target="_blank">
                                                        <i class="fas fa-receipt text-amber"></i> Stock Receipt</a></li>
                                                @endcan
                                                @if (!$purchase->is_fully_returned && $purchase->status_purchase == 'post')
                                                    @can('purchases.create')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-danger" href="{{ route('purchase.return.show', $purchase->id) }}">
                                                            <i class="fas fa-undo-alt"></i> Process Return</a></li>
                                                    @endcan
                                                @endif
                                                @if($purchase->status_purchase == 'post')
                                                    @can('purchases.unpost')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-warning btn-unpost" href="javascript:void(0);"
                                                               data-id="{{ $purchase->id }}" data-invoice="{{ $purchase->invoice_no }}">
                                                            <i class="fas fa-history"></i> Un-post GRN</a></li>
                                                    @endcan
                                                @else
                                                    @can('purchases.edit')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-info" href="{{ route('purchase.edit', $purchase->id) }}">
                                                            <i class="fas fa-edit"></i> Edit Draft GRN</a></li>
                                                    @endcan
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
$(document).ready(function () {

    // ── DataTable ──────────────────────────────────────────────
    $('.datanew').DataTable({
        pageLength: 10,
        aaSorting: [],
        language: {
            search: '',
            searchPlaceholder: 'Search GRNs, Vendors or Batches...'
        },
        dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
    });

    // ── Excel Export ───────────────────────────────────────────
    function exportTableToExcel(tableID, filename) {
        var dataType  = 'application/vnd.ms-excel';
        var tableEl   = document.getElementById(tableID);
        if (!tableEl) return;
        var tableHTML = tableEl.outerHTML.replace(/ /g, '%20');
        filename = (filename || 'Verified_GRN_Registry') + '.xls';
        var link = document.createElement('a');
        document.body.appendChild(link);
        if (navigator.msSaveOrOpenBlob) {
            var blob = new Blob(['\ufeff', tableHTML], { type: dataType });
            navigator.msSaveOrOpenBlob(blob, filename);
        } else {
            link.href     = 'data:' + dataType + ', ' + tableHTML;
            link.download = filename;
            link.click();
        }
        document.body.removeChild(link);
    }

    $('#export-registry').on('click', function () {
        exportTableToExcel('grn-table', 'Verified_GRN_Registry');
    });

    // ── Print Registry button ──────────────────────────────────
    $('#print-registry').on('click', function () {
        openPrint();
    });

    // ── Un-post GRN ────────────────────────────────────────────
    $(document).on('click', '.btn-unpost', function () {
        var id      = $(this).data('id');
        var invoice = $(this).data('invoice');

        Swal.fire({
            title: 'Un-post GRN?',
            text: 'You are about to revert GRN ' + invoice + ' to UNPOSTED status. Accounting entries and batches will be PERMANENTLY DELETED. Continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Un-post Now',
            showLoaderOnConfirm: true,
            preConfirm: function () {
                return $.ajax({
                    url: '/purchase/' + id + '/unpost',
                    type: 'GET',
                    dataType: 'json'
                }).catch(function (error) {
                    Swal.showValidationMessage('Request failed: ' + (error.responseJSON.message || 'Unknown error'));
                });
            },
            allowOutsideClick: function () { return !Swal.isLoading(); }
        }).then(function (result) {
            if (result.isConfirmed && result.value && result.value.success) {
                Swal.fire('Reverted!', result.value.message, 'success').then(function () {
                    location.reload();
                });
            }
        });
    });

}); // end document.ready


// ═══════════════════════════════════════════════════════════════
// PRINT OVERLAY — Word-like UI
// ═══════════════════════════════════════════════════════════════

// Set dates on load
(function () {
    var now     = new Date();
    var dateStr = now.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    document.getElementById('print-date').textContent  = dateStr;
    document.getElementById('footer-date').textContent = dateStr;
})();

// Open overlay — pulls ALL DataTable rows (across all pages)
function openPrint() {
    var dtInstance  = $('#grn-table').DataTable();
    var previewBody = document.querySelector('#preview-table tbody');
    previewBody.innerHTML = '';

    var allNodes = dtInstance.rows().nodes().toArray();

    if (allNodes.length === 0) {
        previewBody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:24px;color:#bbb;font-size:10px;">No records found</td></tr>';
    } else {
        allNodes.forEach(function (row) {
            var cells = row.querySelectorAll('td');
            if (cells.length === 0) return;
            var newRow = document.createElement('tr');
            [0, 1, 2, 3, 4, 5, 6].forEach(function (i) {
                var td = document.createElement('td');
                td.textContent = cells[i] ? cells[i].textContent.trim() : '';
                newRow.appendChild(td);
            });
            previewBody.appendChild(newRow);
        });
    }

    document.getElementById('print-overlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    closeAllDropdowns();
}

// Close overlay
function closePrint() {
    document.getElementById('print-overlay').classList.remove('active');
    document.body.style.overflow = '';
}

// Send preview content to browser print dialog
function triggerPrint() {
    var page = document.getElementById('po-page').outerHTML;
    var w    = window.open('', '_blank');
    w.document.write('<!DOCTYPE html><html><head><title>Print Registry</title><style>' +
        'body{font-family:Segoe UI,sans-serif;margin:0;padding:40px;}' +
        'table{width:100%;border-collapse:collapse;font-size:9px;}' +
        'th,td{border:0.5px solid #ddd;padding:5px 8px;text-align:left;}' +
        'thead{background:#f0f0f7;}' +
        'h2{font-size:13px;margin-bottom:4px;}' +
        'p{font-size:9px;color:#888;margin:0;}' +
        '.po-page-footer{display:flex;justify-content:space-between;border-top:0.5px solid #ccc;padding-top:6px;margin-top:40px;font-size:8px;color:#999;}' +
        '</style></head><body>' + page + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function () { w.print(); w.close(); }, 400);
}

// Toggle a sidebar dropdown open/closed
function toggleDropdown(el) {
    var menu   = el.querySelector('.po-dropdown-menu');
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

// Update a setting row's label after selection
function selectOption(item, title, sub) {
    event.stopPropagation();
    var setting = item.closest('.po-setting');
    setting.querySelector('.po-setting-text p').textContent    = title;
    setting.querySelector('.po-setting-text span').textContent = sub;
    closeAllDropdowns();
}

// Toggle portrait / landscape on the preview page
function setOrientation(mode) {
    event.stopPropagation();
    var page    = document.getElementById('po-page');
    var setting = document.getElementById('orientation-setting');
    if (mode === 'landscape') {
        page.classList.add('landscape');
        setting.querySelector('.po-setting-text p').textContent    = 'Landscape Orientation';
        setting.querySelector('.po-setting-text span').textContent = 'Wide page layout';
    } else {
        page.classList.remove('landscape');
        setting.querySelector('.po-setting-text p').textContent    = 'Portrait Orientation';
        setting.querySelector('.po-setting-text span').textContent = 'Tall page layout';
    }
    closeAllDropdowns();
}

// Page navigation
var currentPage = 1;
var totalPages  = 1;

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