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
            --gray: #6b7280;
            --gray-lt: #f3f4f6;
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
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 55%, #38bdf8 100%);
            border-radius: 14px;
            padding: 22px 28px;
            color: #fff;
            box-shadow: 0 6px 24px rgba(37, 99, 235, .32);
        }

        .led-topbar h4 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .led-topbar p {
            margin: 3px 0 0;
            color: rgba(255, 255, 255, .82);
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
        #reportResult {
            display: none;
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
            margin-bottom: 20px;
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

        .tbl-card tbody tr.main-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background .1s;
            cursor: pointer;
        }

        .tbl-card tbody tr.main-row:hover {
            background: #fafaff;
        }

        .tbl-card tbody td {
            padding: 12px 14px;
            vertical-align: middle;
            color: #334155;
        }

        .tbl-card tbody td.tr {
            text-align: right;
        }

        /* Badges */
        .badge-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 18px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .b-receipt {
            background: var(--green-lt);
            color: #065f46;
        }

        .b-payment {
            background: var(--red-lt);
            color: #991b1b;
        }

        .b-expense {
            background: var(--amber-lt);
            color: #92400e;
        }

        .b-journal {
            background: var(--sky-lt);
            color: #0369a1;
        }

        .b-contra {
            background: var(--gray-lt);
            color: #374151;
        }

        .status-badge {
            font-size: .72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .status-posted {
            background: #d1fae5;
            color: #065f46;
        }

        .status-draft {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

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

        /* Detail/Accordion Row */
        .detail-row {
            background: #f8fafc;
            display: none;
        }

        .detail-container {
            padding: 12px 24px;
            border-bottom: 1px solid var(--border);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .detail-table th {
            background: #f1f5f9;
            color: var(--muted);
            font-weight: 700;
            padding: 6px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            font-size: .68rem;
            text-transform: uppercase;
        }

        .detail-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--muted);
        }

        .empty-state svg {
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

        /* Expand Button Rotation */
        .exp-icon {
            transition: transform 0.2s ease;
        }
        .expanded .exp-icon {
            transform: rotate(180deg);
        }

        @media print {
            .led-page>* {
                display: none !important;
            }

            #reportResult {
                display: block !important;
            }

            #reportResult>* {
                display: none !important;
            }

            #reportResult>.print-header,
            #reportResult>.tbl-card {
                display: block !important;
            }

            .tbl-card {
                border: none !important;
            }

            .print-header {
                display: block !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <div class="led-page">

        {{-- Top Bar --}}
        <div class="led-topbar">
            <div>
                <h4>
                    <svg style="width:22px;height:22px;color:#fff" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Voucher Summary Report
                </h4>
                <p>ERP double-entry ledger statement of cash, bank, journal, and contra transactions</p>
            </div>
            <div class="topbar-actions" id="exportBtns" style="display:none;">
                <button class="btn-filter-action btn-excel-action" id="btnExportExcel">📊 Export Excel</button>
                <button class="btn-filter-action btn-pdf-action" id="btnExportPdf">📄 Export PDF</button>
                <button class="btn-led btn-print" onclick="window.print()">🖨 Print Report</button>
            </div>
        </div>

        {{-- Filter Card --}}
        <div class="filter-card">
            <div class="filter-inputs-container">
                {{-- Row 1: Dates & Month/Year selectors --}}
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Start Date</label>
                        <input type="date" id="sel_start">
                    </div>
                    <div class="filter-group">
                        <label>End Date</label>
                        <input type="date" id="sel_end">
                    </div>
                    <div class="filter-group">
                        <label>Month</label>
                        <select id="sel_month">
                            <option value="all">Select Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Year</label>
                        <select id="sel_year">
                            <option value="all">Select Year</option>
                            @for ($y = 2024; $y <= 2030; $y++)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                {{-- Row 2: Party Type / Party Name / Product --}}
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Party Type</label>
                        <select id="sel_party_type">
                            <option value="all">All Parties</option>
                            <option value="customer">Customer</option>
                            <option value="vendor">Vendor</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 200px;">
                        <label id="lbl_party_name">Party Name</label>
                        <select id="sel_party_name" class="select2-filter">
                            <option value="all">-- Select Party Type First --</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 200px;">
                        <label>Product</label>
                        <select id="sel_product" class="select2-filter">
                            <option value="all">All Products</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}">{{ $p->item_code }} — {{ $p->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Hidden data for customers & vendors (JSON injected from server) --}}
                <script id="customers-data" type="application/json">
                    {!! json_encode($customers->map(fn($c) => ['id' => $c->id, 'name' => $c->customer_name])) !!}
                </script>
                <script id="vendors-data" type="application/json">
                    {!! json_encode($vendors->map(fn($v) => ['id' => $v->id, 'name' => $v->name])) !!}
                </script>

                {{-- Row 3: Type, Head, Status, Branch & Generate --}}
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Voucher Type</label>
                        <select id="sel_type">
                            <option value="all">All Types</option>
                            <option value="receipt">Receipt Voucher (RV)</option>
                            <option value="payment">Payment Voucher (PV)</option>
                            <option value="expense">Expense Voucher (EV)</option>
                            <option value="journal">Journal Voucher (JV)</option>
                            <option value="contra">Contra Voucher (CV)</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 0 1 180px;">
                        <label>Account Head</label>
                        <select id="sel_head" class="select2-filter">
                            <option value="all">-- Select Voucher Type First --</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select id="sel_status">
                            <option value="all">All Status</option>
                            <option value="posted">Posted</option>
                            <option value="draft">Draft</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Branch</label>
                        <select id="sel_branch" {{ !$isSuperAdmin ? 'disabled' : '' }}>
                            @if(!$isSuperAdmin)
                                <option value="{{ $activeBranch->id ?? 'all' }}">{{ $activeBranch->name ?? 'Current Office' }}</option>
                            @else
                                <option value="all">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ isset($activeBranch) && $activeBranch->id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div style="flex: 1;"></div>
                    <button type="button" class="btn-filter-action btn-filter-search" id="btnGenerate">🔍 Search</button>
                    <button type="button" class="btn-filter-action btn-filter-reset" id="btnResetFilters">↺ Reset</button>
                </div>
            </div>
        </div>

        {{-- Loader --}}
        <div class="led-loader" id="ledLoader">
            <div class="spinner"></div>
            <p style="margin-top:10px;color:var(--muted);font-size:.88rem;">Generating vouchers statement…</p>
        </div>

        {{-- Result --}}
        <div id="reportResult" style="display:none;">

            {{-- Print Header (only visible when printing) --}}
            <div class="print-header" style="display:none; margin-bottom:16px;">
                <h2 style="margin:0;font-size:18px;font-weight:700;">📄 Voucher Summary Statement</h2>
                <p id="printSubtitle" style="margin:4px 0 0;font-size:12px;color:#555;">
                    Printed: {{ now()->format('d M Y H:i') }}
                </p>
            </div>

            {{-- KPI Row --}}
            <div class="kpi-row" id="kpiRow"></div>

            {{-- Voucher Table --}}
            <div class="tbl-card">
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="no-print"></th>
                                <th style="width:110px;">Date</th>
                                <th style="width:120px;">Voucher #</th>
                                <th>Type</th>
                                <th>Party Name</th>
                                <th>Remarks/Remarks Description</th>
                                <th class="tr">Total Amount (Rs)</th>
                                <th>Status</th>
                                <th class="no-print">Created By</th>
                                <th class="no-print" style="width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reportBody"></tbody>
                    </table>
                </div>
            </div>

            <div class="bottom-bar">
                <small id="genTime"></small>
                <small id="txCount"></small>
            </div>
        </div>

    </div>
@endsection

@section('js')
    <script>
        (function() {
            const fmt = n => parseFloat(n || 0).toLocaleString('en-PK', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Handle date reset
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            const today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate());
            const firstOfMonth = today.slice(0, 7) + '-01';
            
            document.getElementById('sel_start').value = firstOfMonth;
            document.getElementById('sel_end').value = today;

            // ── Party Type / Party Name dynamic logic ──────────────────
            const customersData = JSON.parse(document.getElementById('customers-data').textContent);
            const vendorsData   = JSON.parse(document.getElementById('vendors-data').textContent);

            function populatePartyName(type) {
                const sel   = document.getElementById('sel_party_name');
                const label = document.getElementById('lbl_party_name');

                // Destroy existing Select2 before rebuilding options
                if ($(sel).hasClass('select2-hidden-accessible')) {
                    $(sel).select2('destroy');
                }

                sel.innerHTML = '';

                if (type === 'customer') {
                    label.textContent = 'Customer Name';
                    sel.innerHTML = '<option value="all">All Customers</option>';
                    customersData.forEach(c => {
                        sel.innerHTML += `<option value="${c.id}">${c.name}</option>`;
                    });
                } else if (type === 'vendor') {
                    label.textContent = 'Vendor Name';
                    sel.innerHTML = '<option value="all">All Vendors</option>';
                    vendorsData.forEach(v => {
                        sel.innerHTML += `<option value="${v.id}">${v.name}</option>`;
                    });
                } else {
                    label.textContent = 'Party Name';
                    sel.innerHTML = '<option value="all">-- Select Party Type First --</option>';
                }

                // Re-init Select2
                $(sel).select2({ width: '100%' });
            }

            document.getElementById('sel_party_type').addEventListener('change', function() {
                populatePartyName(this.value);
            });

            // ── Voucher Type / Account Head dynamic logic ──────────────────
            function populateAccountHeads(voucherType) {
                const sel = document.getElementById('sel_head');

                // Destroy Select2 before rebuilding options
                if ($(sel).hasClass('select2-hidden-accessible')) {
                    $(sel).select2('destroy');
                }

                if (voucherType === 'all') {
                    sel.innerHTML = '<option value="all">-- Select Voucher Type First --</option>';
                    $(sel).select2({ width: '100%' });
                    return;
                }

                sel.innerHTML = '<option value="all">Loading Account Heads...</option>';

                fetch(`{{ route('report.voucher.heads') }}?voucher_type=${voucherType}`)
                    .then(r => r.json())
                    .then(res => {
                        sel.innerHTML = '<option value="all">All Account Heads</option>';
                        if (res.success && res.heads) {
                            res.heads.forEach(h => {
                                sel.innerHTML += `<option value="${h.id}">${h.name}</option>`;
                            });
                        }
                        $(sel).select2({ width: '100%' });
                    })
                    .catch(err => {
                        console.error(err);
                        sel.innerHTML = '<option value="all">Error loading heads</option>';
                        $(sel).select2({ width: '100%' });
                    });
            }

            document.getElementById('sel_type').addEventListener('change', function() {
                populateAccountHeads(this.value);
            });

            // Initialize Select2 dropdowns
            $(document).ready(function() {
                $('.select2-filter').select2({ width: '100%' });
                // Init party name and account heads as empty/all
                populatePartyName('all');
                populateAccountHeads('all');
            });

            // Date / Month / Year selection logic to keep inputs synchronized
            document.getElementById('sel_start').addEventListener('change', function() {
                if (this.value) {
                    document.getElementById('sel_month').value = 'all';
                    document.getElementById('sel_year').value = 'all';
                }
            });
            document.getElementById('sel_end').addEventListener('change', function() {
                if (this.value) {
                    document.getElementById('sel_month').value = 'all';
                    document.getElementById('sel_year').value = 'all';
                }
            });
            document.getElementById('sel_month').addEventListener('change', function() {
                if (this.value !== 'all') {
                    document.getElementById('sel_start').value = '';
                    document.getElementById('sel_end').value = '';
                    // Ensure a year is selected
                    if (document.getElementById('sel_year').value === 'all') {
                        document.getElementById('sel_year').value = new Date().getFullYear();
                    }
                }
            });
            document.getElementById('sel_year').addEventListener('change', function() {
                if (this.value !== 'all' && document.getElementById('sel_month').value === 'all') {
                    document.getElementById('sel_start').value = '';
                    document.getElementById('sel_end').value = '';
                }
            });

            // Reset filters logic
            document.getElementById('btnResetFilters').addEventListener('click', function() {
                document.getElementById('sel_start').value = firstOfMonth;
                document.getElementById('sel_end').value = today;
                document.getElementById('sel_month').value = 'all';
                document.getElementById('sel_year').value = 'all';
                document.getElementById('sel_type').value = 'all';
                document.getElementById('sel_status').value = 'all';

                document.getElementById('sel_party_type').value = 'all';
                populatePartyName('all');
                populateAccountHeads('all');

                $('#sel_product').val('all').trigger('change');

                document.getElementById('reportResult').style.display = 'none';
                document.getElementById('exportBtns').style.display = 'none';
            });

            // Generate report
            let reportData = [];
            document.getElementById('btnGenerate').addEventListener('click', function() {
                const start     = document.getElementById('sel_start').value;
                const end       = document.getElementById('sel_end').value;
                const month     = document.getElementById('sel_month').value;
                const year      = document.getElementById('sel_year').value;
                const product   = document.getElementById('sel_product').value;
                const type      = document.getElementById('sel_type').value;
                const status    = document.getElementById('sel_status').value;
                const branch    = document.getElementById('sel_branch').value;
                const head      = document.getElementById('sel_head').value;

                // Resolve customer_id / vendor_id from the combined party selector
                const partyType = document.getElementById('sel_party_type').value;
                const partyId   = document.getElementById('sel_party_name').value;
                const customer  = (partyType === 'customer') ? partyId : 'all';
                const vendor    = (partyType === 'vendor')   ? partyId : 'all';

                document.getElementById('ledLoader').style.display = 'block';
                document.getElementById('reportResult').style.display = 'none';
                document.getElementById('exportBtns').style.display = 'none';

                const params = new URLSearchParams({
                    start_date:   start,
                    end_date:     end,
                    month:        month,
                    year:         year,
                    party_type:   partyType,
                    customer_id:  customer,
                    vendor_id:    vendor,
                    product_id:   product,
                    voucher_type: type,
                    status:       status,
                    branch_id:    branch,
                    head_id:      head
                });

                console.log('Voucher Report Params:', Object.fromEntries(params));
                fetch(`{{ route('report.voucher.fetch') }}?${params}`)
                    .then(r => r.json())
                    .then(res => {
                        document.getElementById('ledLoader').style.display = 'none';
                        if (res.error) {
                            Swal.fire('Error', res.error, 'error');
                            return;
                        }

                        reportData = res.data;
                        renderReport(res);
                        document.getElementById('reportResult').style.display = 'block';
                        document.getElementById('exportBtns').style.display = 'flex';
                    })
                    .catch(err => {
                        document.getElementById('ledLoader').style.display = 'none';
                        console.error(err);
                        Swal.fire('Error', 'Failed to fetch voucher report.', 'error');
                    });
            });

            function getBadgeClass(type) {
                switch(type) {
                    case 'receipt': return 'b-receipt';
                    case 'payment': return 'b-payment';
                    case 'expense': return 'b-expense';
                    case 'journal': return 'b-journal';
                    case 'contra': return 'b-contra';
                    default: return 'b-journal';
                }
            }

            function getStatusClass(status) {
                switch(status) {
                    case 'posted': return 'status-posted';
                    case 'draft': return 'status-draft';
                    case 'cancelled': return 'status-cancelled';
                    default: return 'status-draft';
                }
            }

            function renderReport(res) {
                const list = res.data || [];
                const sum = res.summary || {};

                // ── Populate KPI boxes ─────────────────────────────────────
                document.getElementById('kpiRow').innerHTML = `
                    <div class="kpi-box k-blue">
                        <div class="kpi-lbl">Total Vouchers</div>
                        <div class="kpi-val">${sum.total_count}</div>
                        <div class="kpi-sub">${sum.posted_count} Posted — ${sum.draft_count} Draft</div>
                    </div>
                    <div class="kpi-box k-sky">
                        <div class="kpi-lbl">Total Amount Value</div>
                        <div class="kpi-val">Rs ${fmt(sum.total_amount)}</div>
                        <div class="kpi-sub">Combined value of all</div>
                    </div>
                    <div class="kpi-box k-green">
                        <div class="kpi-lbl">Total Receipts (RV)</div>
                        <div class="kpi-val" style="color:var(--green)">Rs ${fmt(sum.total_receipts)}</div>
                        <div class="kpi-sub">Cash/Bank additions</div>
                    </div>
                    <div class="kpi-box k-red">
                        <div class="kpi-lbl">Total Payments (PV)</div>
                        <div class="kpi-val" style="color:var(--red)">Rs ${fmt(sum.total_payments)}</div>
                        <div class="kpi-sub">Cash/Bank reductions</div>
                    </div>
                    <div class="kpi-box k-amber">
                        <div class="kpi-lbl">Total Expenses (EV)</div>
                        <div class="kpi-val" style="color:var(--amber)">Rs ${fmt(sum.total_expenses)}</div>
                        <div class="kpi-sub">Expense transactions</div>
                    </div>
                `;

                // ── Populate Table body ────────────────────────────────────
                let html = '';
                if (list.length === 0) {
                    html = `
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p>No vouchers found matching your filter criteria.</p>
                                </div>
                            </td>
                        </tr>
                    `;
                } else {
                    list.forEach((v, index) => {
                        const typeBadge = `<span class="badge-type ${getBadgeClass(v.voucher_type)}">${v.voucher_type}</span>`;
                        const statusBadge = `<span class="status-badge ${getStatusClass(v.status)}">${v.status}</span>`;

                        // Print URL selector
                        let printUrl = '';
                        if (v.voucher_type === 'receipt') {
                            printUrl = `{{ url('print') }}/${v.id}`;
                        } else if (v.voucher_type === 'payment') {
                            printUrl = `{{ url('Paymentprint') }}/${v.id}`;
                        } else if (v.voucher_type === 'expense') {
                            printUrl = `{{ url('expenseprint') }}/${v.id}`;
                        }

                        const printBtn = printUrl 
                            ? `<a href="${printUrl}" target="_blank" class="btn-led bg-light border text-dark py-1 px-2" style="font-size: 11px;">🖨 Print</a>`
                            : `<span class="text-muted" style="font-size: 10px;">—</span>`;

                        html += `
                            <tr class="main-row" onclick="toggleDetails(${v.id}, this)">
                                <td class="no-print" style="text-align: center;">
                                    <i class="fas fa-chevron-down exp-icon text-secondary" style="font-size: 11px;"></i>
                                </td>
                                <td>${v.date}</td>
                                <td><span class="inv-badge">${v.voucher_no}</span></td>
                                <td>${typeBadge}</td>
                                <td><strong>${v.party_name}</strong> <small class="text-muted d-block">${v.party_type !== '-' ? v.party_type : ''}</small></td>
                                <td style="max-width: 250px; font-size: 12px; white-space: normal;">${v.remarks}</td>
                                <td class="tr font-weight-bold">Rs ${fmt(v.total_amount)}</td>
                                <td>${statusBadge}</td>
                                <td class="no-print" style="font-size: 12px;">${v.created_by}</td>
                                <td class="no-print" onclick="event.stopPropagation()">${printBtn}</td>
                            </tr>
                            <tr class="detail-row" id="details-${v.id}">
                                <td colspan="10">
                                    <div class="detail-container">
                                        <div class="mb-2 font-weight-bold" style="font-size: 11px; text-transform: uppercase; color: var(--muted);">Double Entry Lines</div>
                                        <table class="detail-table">
                                            <thead>
                                                <tr>
                                                    <th>Account Name</th>
                                                    <th style="width: 150px; text-align: right;">Debit (Dr)</th>
                                                    <th style="width: 150px; text-align: right;">Credit (Cr)</th>
                                                    <th>Line Narration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${v.details.map(d => `
                                                    <tr>
                                                        <td><strong>${d.account_title}</strong></td>
                                                        <td class="text-right text-danger">${d.debit > 0 ? 'Rs ' + fmt(d.debit) : '—'}</td>
                                                        <td class="text-right text-success">${d.credit > 0 ? 'Rs ' + fmt(d.credit) : '—'}</td>
                                                        <td>${d.narration}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        `;
                    });
                }

                document.getElementById('reportBody').innerHTML = html;
                document.getElementById('genTime').textContent = '🕐 Generated: ' + new Date().toLocaleString('en-PK');
                document.getElementById('txCount').textContent = `${list.length} voucher(s) found`;
            }

            // Global toggle function
            window.toggleDetails = function(id, row) {
                const detailRow = document.getElementById(`details-${id}`);
                if (detailRow.style.display === 'table-row') {
                    detailRow.style.display = 'none';
                    row.classList.remove('expanded');
                } else {
                    detailRow.style.display = 'table-row';
                    row.classList.add('expanded');
                }
            };

            // Export Excel logic
            document.getElementById('btnExportExcel').addEventListener('click', function() {
                if (reportData.length === 0) return;

                const params = new URLSearchParams({
                    start_date: document.getElementById('sel_start').value,
                    end_date: document.getElementById('sel_end').value,
                    month: document.getElementById('sel_month').value,
                    year: document.getElementById('sel_year').value,
                    party_type: document.getElementById('sel_party_type').value,
                    customer_id: document.getElementById('sel_customer').value,
                    vendor_id: document.getElementById('sel_vendor').value,
                    product_id: document.getElementById('sel_product').value,
                    voucher_type: document.getElementById('sel_voucher_type').value,
                    status: document.getElementById('sel_status').value,
                    head_id: document.getElementById('sel_head_id').value,
                    branch_id: IS_SUPER_ADMIN ? document.getElementById('sel_branch').value : 'all'
                });

                window.location.href = `{{ route('report.voucher.export.excel') }}?${params}`;
            });

            // Export PDF logic
            document.getElementById('btnExportPdf').addEventListener('click', function() {
                if (reportData.length === 0) return;

                const params = new URLSearchParams({
                    start_date: document.getElementById('sel_start').value,
                    end_date: document.getElementById('sel_end').value,
                    month: document.getElementById('sel_month').value,
                    year: document.getElementById('sel_year').value,
                    party_type: document.getElementById('sel_party_type').value,
                    customer_id: document.getElementById('sel_customer').value,
                    vendor_id: document.getElementById('sel_vendor').value,
                    product_id: document.getElementById('sel_product').value,
                    voucher_type: document.getElementById('sel_voucher_type').value,
                    status: document.getElementById('sel_status').value,
                    head_id: document.getElementById('sel_head_id').value,
                    branch_id: IS_SUPER_ADMIN ? document.getElementById('sel_branch').value : 'all'
                });

                window.location.href = `{{ route('report.voucher.export.pdf') }}?${params}`;
            });

        })();
    </script>
@endsection
