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
            --amber: #f59e0b;
            --amber-light: #fef3c7;
        }

        .inv-page {
            padding: 20px;
            font-family: 'Inter', 'Segoe UI', sans-serif;
        }

        /* ── Top bar ──────────────────────────────────────────────────── */
        .inv-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .inv-topbar h4 {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--ink);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .inv-topbar p {
            margin: 0;
            color: var(--muted);
            font-size: .85rem;
        }

        .topbar-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* ── Buttons ──────────────────────────────────────────────────── */
        .btn-inv {
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

        .btn-inv:hover {
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

        .btn-back {
            background: var(--bg);
            color: var(--ink);
            border: 1px solid var(--border);
        }

        /* ── KPI Strip ────────────────────────────────────────────────── */
        .kpi-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .kpi-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 18px;
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
            border-radius: 2px 0 0 2px;
        }

        .kpi-box.blue::before {
            background: var(--brand);
        }

        .kpi-box.green::before {
            background: var(--green);
        }

        .kpi-box.red::before {
            background: var(--red);
        }

        .kpi-box.amber::before {
            background: var(--amber);
        }

        .kpi-box.indigo::before {
            background: #6366f1;
        }

        .kpi-label {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: var(--muted);
            margin-bottom: 4px;
        }

        .kpi-val {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.2;
        }

        .kpi-sub {
            font-size: .76rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Filter row ────────────────────────────────────────────────── */
        .filter-bar {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
            margin-bottom: 18px;
        }

        .filter-bar input,
        .filter-bar select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 10px;
            font-size: .85rem;
            color: var(--ink);
            background: var(--bg);
            outline: none;
            min-width: 170px;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: var(--brand);
            background: var(--white);
        }

        .filter-bar label {
            font-size: .75rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            display: block;
            margin-bottom: 4px;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 32px;
            min-width: 220px;
        }

        .search-box svg {
            position: absolute;
            left: 9px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            width: 15px;
            height: 15px;
            pointer-events: none;
        }

        /* ── Table card ────────────────────────────────────────────────── */
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
            padding: 11px 13px;
            text-align: left;
            font-size: .73rem;
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
            transition: background .12s;
        }

        .tbl-card tbody tr:hover {
            background: #fafaff;
        }

        .tbl-card tbody td {
            padding: 10px 13px;
            vertical-align: middle;
            color: #334155;
        }

        .tbl-card tbody td.tr {
            text-align: right;
        }

        .tbl-card tfoot td {
            padding: 11px 13px;
            font-weight: 700;
            color: var(--ink);
            background: #f8fafc;
            border-top: 2px solid var(--border);
        }

        .tbl-card tfoot td.tr {
            text-align: right;
        }

        /* Item cell */
        .item-code {
            font-size: .75rem;
            color: var(--muted);
        }

        .item-name {
            font-weight: 600;
            color: var(--ink);
        }

        /* Stock status badges */
        .stk {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .stk-ok {
            background: var(--green-light);
            color: #065f46;
        }

        .stk-low {
            background: var(--amber-light);
            color: #92400e;
        }

        .stk-out {
            background: var(--red-light);
            color: #991b1b;
        }

        /* Mode badge */
        .mode-badge {
            font-size: .68rem;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 700;
            background: var(--brand-light);
            color: var(--brand);
        }

        /* Qty display */
        .qty-num {
            font-size: 1rem;
            font-weight: 700;
        }

        /* Warehouse chips */
        .wh-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 4px;
        }

        .wh-chip {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: .72rem;
            color: #475569;
            white-space: nowrap;
        }

        /* Expand btn */
        .btn-exp {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--brand);
            font-size: .78rem;
            font-weight: 600;
            padding: 0;
        }

        .detail-row {
            display: none;
        }

        .detail-row.open {
            display: table-row;
        }

        .detail-inner {
            padding: 10px 16px 12px 40px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }

        .det-item {
            background: var(--bg);
            border-radius: 8px;
            padding: 10px 12px;
        }

        .det-label {
            font-size: .7rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 3px;
        }

        .det-val {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        /* Amount colors */
        .c-green {
            color: var(--green);
        }

        .c-red {
            color: var(--red);
        }

        .c-brand {
            color: var(--brand);
        }

        /* Empty */
        .empty-state {
            text-align: center;
            padding: 60px;
            color: var(--muted);
        }

        .empty-state svg {
            width: 52px;
            height: 52px;
            opacity: .35;
            margin-bottom: 12px;
        }

        /* Print */
        @media print {

            .inv-topbar,
            .filter-bar,
            .kpi-strip,
            .btn-inv,
            .btn-exp {
                display: none !important;
            }

            .inv-page,
            .tbl-card {
                display: block !important;
                width: 100% !important;
                border: none !important;
            }

            .print-header {
                display: block !important;
            }

            .detail-row {
                display: table-row !important;
            }
        }
    </style>

    <div class="inv-page">

        <!-- Print Header (only visible when printing) -->
        <div class="print-header" style="display:none; margin-bottom:16px;">
            <h2 style="margin:0;font-size:18px;font-weight:700;">📦 Inventory On-Hand Report</h2>
            <p style="margin:4px 0 0;font-size:12px;color:#555;">Printed: {{ now()->format('d M Y H:i') }}</p>
        </div>

        {{-- Top Bar --}}
        <div class="inv-topbar">
            <div>
                <h4>
                    <svg style="width:22px;height:22px;color:#4f46e5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11" />
                    </svg>
                    Inventory On-Hand
                </h4>
                <p>Live stock position across all warehouses • {{ now()->format('d M Y, h:i A') }}</p>
            </div>
            <div class="topbar-actions">
                <a href="{{ route('product') }}" class="btn-inv btn-back">← Products</a>
                <button class="btn-inv btn-csv" id="btnCsv">⬇ Export CSV</button>
                <button class="btn-inv btn-print" onclick="printReport()">🖨 Print</button>
            </div>
        </div>

        {{-- KPI Strip --}}
        <div class="kpi-strip">
            <div class="kpi-box blue">
                <div class="kpi-label">Total SKUs</div>
                <div class="kpi-val">{{ $summary->total_products }}</div>
                <div class="kpi-sub">Tracked products</div>
            </div>
            <div class="kpi-box green">
                <div class="kpi-label">Total On-Hand</div>
                <div class="kpi-val">{{ number_format($summary->grand_on_hand) }}</div>
                <div class="kpi-sub">Total pieces in stock</div>
            </div>
            <div class="kpi-box indigo">
                <div class="kpi-label">Cost Value</div>
                <div class="kpi-val">Rs {{ number_format($summary->cost_value, 0) }}</div>
                <div class="kpi-sub">At purchase price</div>
            </div>
            <div class="kpi-box green">
                <div class="kpi-label">Sale Value</div>
                <div class="kpi-val">Rs {{ number_format($summary->sale_value, 0) }}</div>
                <div class="kpi-sub">At sale price</div>
            </div>
            <div class="kpi-box amber">
                <div class="kpi-label">Low Stock</div>
                <div class="kpi-val">{{ $summary->low_stock }}</div>
                <div class="kpi-sub">Below 20 units</div>
            </div>
            <div class="kpi-box red">
                <div class="kpi-label">Out of Stock</div>
                <div class="kpi-val">{{ $summary->out_of_stock }}</div>
                <div class="kpi-sub">Zero balance</div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="filter-bar">
            <div>
                <label>Search</label>
                <div class="search-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                    </svg>
                    <input type="text" id="searchInput" placeholder="Code or name…">
                </div>
            </div>
            <div>
                <label>Status</label>
                <select id="filterStatus">
                    <option value="all">All Status</option>
                    <option value="ok">In Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>
            <div>
                <label>Mode</label>
                <select id="filterMode">
                    <option value="all">All Modes</option>
                    <option value="by_pieces">By Pieces</option>
                    <option value="by_cartons">By Cartons</option>
                    <option value="by_size">By Size (m²)</option>
                </select>
            </div>
            <div style="margin-left:auto;display:flex;align-items:flex-end;">
                <span id="rowCount" style="font-size:.82rem;color:var(--muted);padding:8px 0;">
                    Showing <strong id="shownCount">{{ count($rows) }}</strong> of <strong>{{ count($rows) }}</strong>
                    products
                </span>
            </div>
        </div>

        {{-- Table --}}
        <div class="tbl-card">
            @if (count($rows) === 0)
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 10V11" />
                    </svg>
                    <p>No product stock data found.</p>
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table id="invTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Brand</th>
                                <th>Mode</th>
                                <th class="tr">On-Hand&nbsp;Qty</th>
                                <th class="tr">Cost&nbsp;Value</th>
                                <th class="tr">Sale&nbsp;Value</th>
                                <th>Status</th>
                                <th>Warehouses</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="invBody">
                            @foreach ($rows as $i => $r)
                                <tr class="inv-row" data-status="{{ $r->stock_status }}"
                                    data-mode="{{ strtolower($r->size_mode) }}"
                                    data-name="{{ strtolower($r->item_name . ' ' . $r->item_code) }}">
                                    <td style="color:var(--muted);font-size:.8rem;">{{ $i + 1 }}</td>
                                    <td>
                                        <div class="item-code">{{ $r->item_code }}</div>
                                        <div class="item-name">{{ $r->item_name }}</div>
                                        <div style="font-size:.72rem;color:var(--muted);">{{ $r->unit_name }}</div>
                                    </td>
                                    <td style="color:var(--muted);">{{ $r->brand_name }}</td>
                                    <td>
                                        <span class="mode-badge">
                                            @if ($r->size_mode === 'by_size')
                                                m²
                                            @elseif(str_contains($r->size_mode, 'carton'))
                                                Box
                                            @else
                                                Pcs
                                            @endif
                                        </span>
                                    </td>
                                    <td class="tr">
                                        <div class="qty-num {{ $r->total_pieces <= 0 ? 'c-red' : ($r->total_pieces < 20 ? 'c-amber' : 'c-green') }}">
                                            {{ $r->dot_notation }} <small class="fw-normal text-muted" style="font-size: 0.7rem;">(B.P)</small>
                                        </div>
                                        <div style="font-size: 0.72rem; color: var(--muted);">
                                            {{ number_format($r->total_pieces) }} total pcs
                                        </div>
                                        @if($r->size_mode === 'by_size')
                                            <div style="font-size: 0.72rem; color: var(--brand);">
                                                {{ $r->display_qty }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="tr">
                                        <span style="color:var(--brand);font-weight:700;">
                                            Rs {{ number_format($r->cost_value, 0) }}
                                        </span>
                                    </td>
                                    <td class="tr">
                                        <span style="color:var(--green);font-weight:700;">
                                            Rs {{ number_format($r->sale_value, 0) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($r->stock_status === 'out')
                                            <span class="stk stk-out">● Out of Stock</span>
                                        @elseif($r->stock_status === 'low')
                                            <span class="stk stk-low">⚠ Low Stock</span>
                                        @else
                                            <span class="stk stk-ok">✔ In Stock</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($r->warehouses->count() > 0)
                                            <div class="wh-chips">
                                                @foreach ($r->warehouses as $w)
                                                    <span class="wh-chip">{{ $w['name'] }}: {{ $w['display'] }}</span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color:var(--muted);font-size:.78rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn-exp" data-idx="{{ $i }}">▶ More</button>
                                    </td>
                                </tr>
                                <tr class="detail-row" id="det-{{ $i }}">
                                    <td colspan="10"
                                        style="padding:0; background:#fafbff; border-bottom:2px solid var(--border);">
                                        <div class="detail-inner">
                                            <div class="detail-grid">
                                                <div class="det-item">
                                                    <div class="det-label">Total Purchased</div>
                                                    <div class="det-val">Rs {{ number_format($r->purchase_amount, 0) }}
                                                    </div>
                                                </div>
                                                <div class="det-item">
                                                    <div class="det-label">Total Sold</div>
                                                    <div class="det-val">Rs {{ number_format($r->sale_amount, 0) }}</div>
                                                </div>
                                                <div class="det-item">
                                                    <div class="det-label">Gross Profit</div>
                                                    <div
                                                        class="det-val {{ $r->sale_amount - $r->purchase_amount >= 0 ? 'c-green' : 'c-red' }}">
                                                        Rs {{ number_format($r->sale_amount - $r->purchase_amount, 0) }}
                                                    </div>
                                                </div>
                                                @foreach ($r->warehouses as $w)
                                                    <div class="det-item">
                                                        <div class="det-label">{{ $w['name'] }}</div>
                                                        <div class="det-val">{{ $w['display'] }} <small class="text-muted fw-normal">(B.P)</small> / {{ number_format($w['pieces']) }} pcs</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" style="text-align:right;">
                                    Grand Total ({{ count($rows) }} SKUs):
                                </td>
                                <td class="tr">{{ number_format($summary->grand_on_hand) }} pcs</td>
                                <td class="tr" style="color:var(--brand);">Rs
                                    {{ number_format($summary->cost_value, 0) }}</td>
                                <td class="tr" style="color:var(--green);">Rs
                                    {{ number_format($summary->sale_value, 0) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        (function() {
            // Expand/collapse detail rows
            document.querySelectorAll('.btn-exp').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = this.dataset.idx;
                    const row = document.getElementById('det-' + idx);
                    if (!row) return;
                    const open = row.classList.toggle('open');
                    this.textContent = (open ? '▼ ' : '▶ ') + 'More';
                });
            });

            // Live filter
            const allRows = Array.from(document.querySelectorAll('.inv-row'));
            const shownEl = document.getElementById('shownCount');

            function applyFilters() {
                const search = (document.getElementById('searchInput')?.value || '').toLowerCase();
                const status = document.getElementById('filterStatus')?.value || 'all';
                const mode = document.getElementById('filterMode')?.value || 'all';
                let shown = 0;
                allRows.forEach(row => {
                    const name = row.dataset.name || '';
                    const st = row.dataset.status || '';
                    const md = row.dataset.mode || '';
                    const detRow = document.getElementById('det-' + row.querySelector('.btn-exp')?.dataset.idx);

                    const matchSearch = !search || name.includes(search);
                    const matchStatus = status === 'all' || st === status;
                    const matchMode = mode === 'all' || md.includes(mode.replace('by_', ''));

                    const visible = matchSearch && matchStatus && matchMode;
                    row.style.display = visible ? '' : 'none';
                    if (detRow) detRow.style.display = 'none'; // collapse on filter
                    if (visible) shown++;
                });
                if (shownEl) shownEl.textContent = shown;
            }

            document.getElementById('searchInput')?.addEventListener('input', applyFilters);
            document.getElementById('filterStatus')?.addEventListener('change', applyFilters);
            document.getElementById('filterMode')?.addEventListener('change', applyFilters);

            // CSV Export
            document.getElementById('btnCsv')?.addEventListener('click', function() {
                const visibleRows = allRows.filter(r => r.style.display !== 'none');
                let csv = 'Code,Name,Brand,Mode,On-Hand Pcs,Cost Value (Rs),Sale Value (Rs),Status\n';
                visibleRows.forEach(r => {
                    const cells = r.querySelectorAll('td');
                    const code = cells[1]?.querySelector('.item-code')?.textContent.trim() || '';
                    const name = cells[1]?.querySelector('.item-name')?.textContent.trim() || '';
                    const brand = cells[2]?.textContent.trim() || '';
                    const mode = cells[3]?.textContent.trim() || '';
                    const qty = cells[4]?.textContent.trim() || '';
                    const cost = cells[5]?.textContent.trim() || '';
                    const sale = cells[6]?.textContent.trim() || '';
                    const status = cells[7]?.textContent.trim() || '';
                    csv +=
                        `"${code}","${name}","${brand}","${mode}","${qty}","${cost}","${sale}","${status}"\n`;
                });
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([csv], {
                    type: 'text/csv;charset=utf-8;'
                }));
                a.download = 'inventory_onhand_{{ now()->format('Ymd_Hi') }}.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            });
        })();

        function printReport() {
            // Expand all warehouse detail rows
            document.querySelectorAll('.detail-row').forEach(r => r.style.display = 'table-row');
            window.print();
            setTimeout(() => document.querySelectorAll('.detail-row').forEach(r => r.style.display = ''), 1000);
        }
    </script>
@endsection
