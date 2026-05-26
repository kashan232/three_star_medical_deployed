@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --pnl-green: #16a34a;
            --pnl-red: #dc2626;
            --pnl-blue: #2563eb;
            --pnl-orange: #d97706;
            --pnl-purple: #7c3aed;
        }

        .pnl-wrap {
            padding: 24px;
            background: #f8fafc;
            min-height: 100vh;
        }

        /* Filter */
        .filter-bar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 22px;
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .filter-bar .fg {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 155px;
        }

        .filter-bar label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #64748b;
        }

        .filter-bar input {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 7px 11px;
            font-size: 13px;
            color: #1e293b;
        }

        .btn-apply {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-print-rpt {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            color: #475569;
            cursor: pointer;
        }

        .btn-print-rpt:hover,
        .btn-apply:hover {
            opacity: .88;
        }

        /* KPI */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            padding: 18px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            transition: transform .2s, box-shadow .2s;
            position: relative;
            overflow: hidden;
        }

        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 14px 0 0 14px;
        }

        .kpi-card.revenue::before {
            background: var(--pnl-blue);
        }

        .kpi-card.cogs::before {
            background: var(--pnl-orange);
        }

        .kpi-card.gross::before {
            background: var(--pnl-green);
        }

        .kpi-card.expense::before {
            background: var(--pnl-red);
        }

        .kpi-card.net::before {
            background: var(--pnl-purple);
        }

        .kpi-card.inv::before {
            background: #0891b2;
        }

        .kpi-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
            margin-bottom: 5px;
        }

        .kpi-value {
            font-size: 23px;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.1;
        }

        .kpi-value.loss {
            color: var(--pnl-red);
        }

        .kpi-value.profit {
            color: var(--pnl-green);
        }

        .kpi-sub {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }

        .kpi-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-top: 3px;
        }

        .kpi-badge.up {
            background: #dcfce7;
            color: #16a34a;
        }

        .kpi-badge.down {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Sections */
        .pnl-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
            margin-bottom: 22px;
            overflow: hidden;
        }

        .pnl-section-header {
            padding: 13px 18px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafbfc;
        }

        .pnl-section-header h5 {
            margin: 0;
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
        }

        .pnl-section-body {
            padding: 18px;
        }

        /* Income statement */
        .stmt-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stmt-table tr td {
            padding: 9px 14px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        .stmt-table tr.rh td {
            font-weight: 700;
            color: #1e293b;
            background: #f8fafc;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .stmt-table tr.subtotal td {
            font-weight: 700;
            border-top: 2px solid #e2e8f0;
            background: #f0f9ff;
        }

        .stmt-table tr.grand td {
            font-weight: 800;
            border-top: 3px double #cbd5e1;
            font-size: 15px;
        }

        .stmt-table tr.grand.profit td:last-child {
            color: var(--pnl-green);
        }

        .stmt-table tr.grand.loss td:last-child {
            color: var(--pnl-red);
        }

        .stmt-table td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .stmt-table td.ded {
            color: #dc2626;
        }

        .ind {
            padding-left: 28px !important;
        }

        .ind2 {
            padding-left: 44px !important;
        }

        .div-row td {
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 2px 0 !important;
        }

        /* Data tables */
        .rpt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .rpt-table th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 9px 12px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .rpt-table td {
            padding: 9px 12px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .rpt-table tbody tr:hover {
            background: #fafbfc;
        }

        .rpt-table td.amt {
            text-align: right;
            font-variant-numeric: tabular-nums;
            font-weight: 600;
        }

        .rpt-table th.thr {
            text-align: right;
        }

        .mg-positive {
            color: var(--pnl-green);
            font-weight: 700;
        }

        .mg-negative {
            color: var(--pnl-red);
            font-weight: 700;
        }

        /* Mini bar */
        .mini-bar {
            height: 5px;
            border-radius: 3px;
            background: #e2e8f0;
            overflow: hidden;
            margin-top: 3px;
        }

        .mini-fill {
            height: 100%;
            border-radius: 3px;
        }

        /* Tooltip note */
        .note-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #78350f;
        }

        .note-box strong {
            color: #92400e;
        }

        @media print {

            .filter-bar,
            .btn-print-rpt,
            .btn-apply,
            nav,
            footer,
            .top_nav,
            .nav-bottom,
            .no-print {
                display: none !important;
            }

            .pnl-wrap {
                padding: 0 !important;
                background: #fff !important;
            }

            .pnl-section {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }

            .kpi-card {
                box-shadow: none !important;
            }
        }
    </style>

    <div class="main-content">
        <div class="pnl-wrap">

            {{-- HEADER --}}
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-0" style="color:#1e293b;"><i class="fas fa-chart-line text-primary me-2"></i>Profit
                        &amp; Loss Statement</h2>
                    <p class="text-muted small mb-0">Period:
                        <strong>{{ \Carbon\Carbon::parse($start)->format('d M Y') }}</strong> —
                        <strong>{{ \Carbon\Carbon::parse($end)->format('d M Y') }}</strong>
                    </p>
                </div>
                <button class="btn-print-rpt no-print" onclick="window.print()"><i class="fas fa-print me-1"></i>
                    Print</button>
            </div>

            {{-- FILTER --}}
            <form method="GET" action="{{ route('reports.profit_loss') }}" class="filter-bar no-print">
                <div class="fg"><label>From Date</label><input type="date" name="start_date"
                        value="{{ $start }}"></div>
                <div class="fg"><label>To Date</label><input type="date" name="end_date" value="{{ $end }}">
                </div>
                <button type="submit" class="btn-apply"><i class="fas fa-search me-1"></i> Generate</button>
            </form>

            {{-- KEY EXPLANATION NOTE --}}
            <div class="note-box no-print">
                <strong><i class="fas fa-info-circle me-1"></i> How COGS is calculated:</strong>
                COGS = <em>Quantity Sold × Purchase Price Per Piece</em> (per product, using the size mode).
                <strong>Unsold stock is NOT counted</strong> — if you bought 10 items at Rs 1,000 each but only sold 3,
                the COGS is Rs 3,000 and the remaining Rs 7,000 stays as <em>Inventory on Hand</em> below.
                Purchase Expensive (extra costs on purchases) are listed separately as an Operating Expense.
            </div>

            {{-- ── KPI CARDS ───────────────────────────────────────────── --}}
            <div class="kpi-grid">
                <div class="kpi-card revenue">
                    <div class="kpi-label">Net Revenue</div>
                    <div class="kpi-value">{{ number_format($netRevenue, 0) }}</div>
                    <div class="kpi-sub">Sales: {{ number_format($salesRevenue, 0) }} &nbsp;|&nbsp; Returns: <span
                            style="color:#dc2626">-{{ number_format($saleReturns, 0) }}</span></div>
                </div>
                <div class="kpi-card cogs">
                    <div class="kpi-label">COGS (Items Sold)</div>
                    <div class="kpi-value" style="color:#d97706;">{{ number_format($totalCOGS, 0) }}</div>
                    <div class="kpi-sub">Cost of {{ count($cogsPerProduct) }} product(s) actually sold</div>
                </div>
                <div class="kpi-card gross">
                    <div class="kpi-label">Gross Profit</div>
                    <div class="kpi-value {{ $grossProfit >= 0 ? 'profit' : 'loss' }}">
                        {{ number_format($grossProfit, 0) }}</div>
                    <div class="kpi-sub"><span
                            class="kpi-badge {{ $grossProfitMargin >= 0 ? 'up' : 'down' }}">{{ $grossProfitMargin }}%</span>
                        margin</div>
                </div>
                <div class="kpi-card expense">
                    <div class="kpi-label">Operating Expenses</div>
                    <div class="kpi-value" style="color:#dc2626;">{{ number_format($totalOperatingExpenses, 0) }}</div>
                    <div class="kpi-sub">Extra Cost: {{ number_format($purchaseExpenses, 0) }} &nbsp;|&nbsp; Other:
                        {{ number_format($otherExpenses, 0) }}</div>
                </div>
                <div class="kpi-card {{ $netProfit >= 0 ? 'net' : '' }}"
                    style="{{ $netProfit < 0 ? 'border-color:#fecaca;' : '' }}">
                    <div class="kpi-label">Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</div>
                    <div class="kpi-value {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                        {{ number_format(abs($netProfit), 0) }}</div>
                    <div class="kpi-sub"><span
                            class="kpi-badge {{ $netProfitMargin >= 0 ? 'up' : 'down' }}">{{ $netProfitMargin }}%</span>
                        net margin</div>
                </div>
                <div class="kpi-card inv">
                    <div class="kpi-label">Inventory On-Hand Value</div>
                    <div class="kpi-value" style="color:#0891b2;">{{ number_format($inventoryOnHand, 0) }}</div>
                    <div class="kpi-sub">{{ $purchasesThisPeriodCount }} purchase(s) —
                        {{ number_format($totalPurchasedThisPeriod, 0) }} total bought</div>
                </div>
            </div>

            {{-- ── TWO-COLUMN: Income Statement + Period Table ───────── --}}
            <div class="row g-4 mb-0">

                {{-- Income Statement --}}
                <div class="col-lg-5">
                    <div class="pnl-section h-100">
                        <div class="pnl-section-header">
                            <h5><i class="fas fa-file-invoice text-primary me-2"></i>Income Statement</h5>
                            <span style="font-size:11px;color:#64748b;">{{ \Carbon\Carbon::parse($start)->format('d M') }}
                                – {{ \Carbon\Carbon::parse($end)->format('d M Y') }}</span>
                        </div>
                        <div class="pnl-section-body p-0">
                            <table class="stmt-table">
                                {{-- REVENUE --}}
                                <tr class="rh">
                                    <td colspan="2">Revenue</td>
                                </tr>
                                <tr>
                                    <td class="ind">Gross Sales</td>
                                    <td class="num">{{ number_format($salesRevenue, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ind ded">Less: Sale Returns</td>
                                    <td class="num ded">({{ number_format($saleReturns, 2) }})</td>
                                </tr>
                                <tr class="subtotal">
                                    <td class="ind"><strong>Net Revenue</strong></td>
                                    <td class="num"><strong>{{ number_format($netRevenue, 2) }}</strong></td>
                                </tr>
                                <tr class="div-row">
                                    <td colspan="2"></td>
                                </tr>

                                {{-- COGS --}}
                                <tr class="rh">
                                    <td colspan="2">Cost of Goods Sold (Sold Items Only)</td>
                                </tr>
                                <tr class="subtotal">
                                    <td class="ind"><strong>COGS ({{ count($cogsPerProduct) }} products sold)</strong>
                                        <small class="d-block text-muted fw-normal" style="font-size:10px;">Qty Sold ×
                                            Purchase Price/pc</small>
                                    </td>
                                    <td class="num"><strong>{{ number_format($totalCOGS, 2) }}</strong></td>
                                </tr>
                                <tr class="div-row">
                                    <td colspan="2"></td>
                                </tr>

                                {{-- GROSS PROFIT --}}
                                <tr class="grand {{ $grossProfit >= 0 ? 'profit' : 'loss' }}">
                                    <td><strong>Gross Profit</strong></td>
                                    <td class="num"><strong>{{ number_format($grossProfit, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="ind" style="font-size:11px;color:#94a3b8;">Gross Margin</td>
                                    <td class="num" style="font-size:11px;color:#94a3b8;">{{ $grossProfitMargin }}%</td>
                                </tr>
                                <tr class="div-row">
                                    <td colspan="2"></td>
                                </tr>

                                {{-- EXPENSES --}}
                                <tr class="rh">
                                    <td colspan="2">Operating Expenses</td>
                                </tr>
                                <tr>
                                    <td class="ind">Purchase Expensive (Extra Cost)
                                        <small class="d-block text-muted" style="font-size:10px;">From extra_cost field on
                                            purchases</small>
                                    </td>
                                    <td class="num ded">{{ number_format($purchaseExpenses, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="ind">Other Expenses (Vouchers)
                                        <small class="d-block text-muted" style="font-size:10px;">Manual expense
                                            vouchers</small>
                                    </td>
                                    <td class="num ded">{{ number_format($otherExpenses, 2) }}</td>
                                </tr>
                                <tr class="subtotal">
                                    <td class="ind"><strong>Total Expenses</strong></td>
                                    <td class="num ded"><strong>{{ number_format($totalOperatingExpenses, 2) }}</strong>
                                    </td>
                                </tr>
                                <tr class="div-row">
                                    <td colspan="2"></td>
                                </tr>

                                {{-- NET PROFIT --}}
                                <tr class="grand {{ $netProfit >= 0 ? 'profit' : 'loss' }}">
                                    <td><strong>{{ $netProfit >= 0 ? 'Net Profit' : 'Net Loss' }}</strong></td>
                                    <td class="num"><strong>{{ number_format($netProfit, 2) }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="ind" style="font-size:11px;color:#94a3b8;">Net Margin</td>
                                    <td class="num" style="font-size:11px;color:#94a3b8;">{{ $netProfitMargin }}%</td>
                                </tr>

                                {{-- INFORMATIONAL: Inventory --}}
                                <tr class="div-row">
                                    <td colspan="2"></td>
                                </tr>
                                <tr style="background:#f0f9ff;">
                                    <td class="ind" style="font-size:12px;color:#0891b2;"><i
                                            class="fas fa-warehouse me-1"></i>Inventory On-Hand Value <small
                                            class="d-block text-muted">(not expensed — still in stock)</small></td>
                                    <td class="num" style="color:#0891b2;font-weight:700;">
                                        {{ number_format($inventoryOnHand, 2) }}</td>
                                </tr>
                                <tr style="background:#f0f9ff;">
                                    <td class="ind" style="font-size:12px;color:#0891b2;">Purchases This Period</td>
                                    <td class="num" style="color:#0891b2;font-weight:700;">
                                        {{ number_format($totalPurchasedThisPeriod, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Period Table --}}
                <div class="col-lg-7">
                    <div class="pnl-section mb-4">
                        <div class="pnl-section-header">
                            <h5><i class="fas fa-calendar-alt text-info me-2"></i>Revenue by {{ $groupLabel }}</h5>
                            <small class="text-muted">{{ count($salesByPeriod) }} period(s)</small>
                        </div>
                        <div class="pnl-section-body p-0" style="max-height:280px;overflow-y:auto;">
                            @if ($salesByPeriod->isEmpty())
                                <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No
                                    sales in this period</div>
                            @else
                                @php $maxRev = $salesByPeriod->max('net_revenue') ?: 1; @endphp
                                <table class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>{{ $groupLabel }}</th>
                                            <th class="thr"># Sales</th>
                                            <th class="thr">Subtotal</th>
                                            <th class="thr">Discount</th>
                                            <th class="thr">Net Revenue</th>
                                            <th style="width:18%;">Share</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($salesByPeriod as $row)
                                            @php $pct = $maxRev > 0 ? round(($row->net_revenue/$maxRev)*100) : 0; @endphp
                                            <tr>
                                                <td><strong>{{ $row->period }}</strong></td>
                                                <td class="amt">{{ number_format($row->txn_count) }}</td>
                                                <td class="amt">{{ number_format($row->subtotal, 0) }}</td>
                                                <td class="amt" style="color:#dc2626;">
                                                    {{ number_format($row->discount, 0) }}</td>
                                                <td class="amt" style="color:#2563eb;font-weight:800;">
                                                    {{ number_format($row->net_revenue, 0) }}</td>
                                                <td>
                                                    <div class="mini-bar">
                                                        <div class="mini-fill"
                                                            style="width:{{ $pct }}%;background:linear-gradient(90deg,#2563eb,#7c3aed);">
                                                        </div>
                                                    </div><small
                                                        style="font-size:10px;color:#94a3b8;">{{ $pct }}%</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0;">
                                            <td><strong>TOTAL</strong></td>
                                            <td class="amt">{{ number_format($salesByPeriod->sum('txn_count')) }}</td>
                                            <td class="amt">{{ number_format($salesByPeriod->sum('subtotal'), 0) }}
                                            </td>
                                            <td class="amt" style="color:#dc2626;">
                                                {{ number_format($salesByPeriod->sum('discount'), 0) }}</td>
                                            <td class="amt" style="color:#2563eb;">{{ number_format($netRevenue, 0) }}
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>

                    {{-- COGS per Product summary --}}
                    <div class="pnl-section">
                        <div class="pnl-section-header">
                            <h5><i class="fas fa-boxes text-warning me-2"></i>COGS per Product (Items Actually Sold)</h5>
                            <small class="text-muted">{{ count($cogsPerProduct) }} product(s)</small>
                        </div>
                        <div class="pnl-section-body p-0" style="max-height:260px;overflow-y:auto;">
                            @if (empty($cogsPerProduct))
                                <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No
                                    sales in this period</div>
                            @else
                                @php $maxCogs = collect($cogsPerProduct)->max('cogs') ?: 1; @endphp
                                <table class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th class="thr">Sold</th>
                                            <th class="thr">COGS</th>
                                            <th class="thr">Revenue</th>
                                            <th class="thr">Gross Margin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($cogsPerProduct as $cp)
                                            @php
                                                $mgPct =
                                                    $cp['sale_revenue'] > 0
                                                        ? round(($cp['gross_margin'] / $cp['sale_revenue']) * 100, 1)
                                                        : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ $cp['item_name'] }}</strong><br>
                                                    <small class="text-muted font-monospace">{{ $cp['item_code'] }} ·
                                                        {{ $cp['size_mode'] }}</small>
                                                </td>
                                                <td class="amt">{{ number_format($cp['pieces_sold'], 0) }}</td>
                                                <td class="amt" style="color:#d97706;">
                                                    {{ number_format($cp['cogs'], 0) }}</td>
                                                <td class="amt" style="color:#2563eb;">
                                                    {{ number_format($cp['sale_revenue'], 0) }}</td>
                                                <td class="amt">
                                                    <span
                                                        class="{{ $cp['gross_margin'] >= 0 ? 'mg-positive' : 'mg-negative' }}">
                                                        {{ number_format($cp['gross_margin'], 0) }}
                                                    </span>
                                                    <small
                                                        class="d-block {{ $mgPct >= 0 ? 'text-success' : 'text-danger' }}"
                                                        style="font-size:10px;">{{ $mgPct }}%</small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0;">
                                            <td><strong>TOTAL</strong></td>
                                            <td class="amt">—</td>
                                            <td class="amt" style="color:#d97706;">{{ number_format($totalCOGS, 0) }}
                                            </td>
                                            <td class="amt" style="color:#2563eb;">
                                                {{ number_format(collect($cogsPerProduct)->sum('sale_revenue'), 0) }}</td>
                                            <td class="amt {{ $grossProfit >= 0 ? 'mg-positive' : 'mg-negative' }}">
                                                {{ number_format($grossProfit, 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── BOTTOM ROW: Purchase + Expense Detail ──────────────── --}}
            <div class="row g-4 mt-0">
                {{-- Purchase Breakdown --}}
                <div class="col-lg-6">
                    <div class="pnl-section">
                        <div class="pnl-section-header">
                            <h5><i class="fas fa-shopping-cart me-2" style="color:#d97706;"></i>Purchase Breakdown (This
                                Period)</h5>
                            <small class="text-muted">{{ $purchasesThisPeriodCount }} purchases ·
                                {{ number_format($totalPurchasedThisPeriod, 0) }} total</small>
                        </div>
                        <div class="pnl-section-body p-0" style="max-height:320px;overflow-y:auto;">
                            @if ($purchaseBreakdown->isEmpty())
                                <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No
                                    purchases</div>
                            @else
                                <table class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Date</th>
                                            <th>Vendor</th>
                                            <th class="thr">Subtotal</th>
                                            <th class="thr">Disc.</th>
                                            <th class="thr">Extra Cost</th>
                                            <th class="thr">Net</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchaseBreakdown as $p)
                                            <tr>
                                                <td><code
                                                        style="font-size:11px;color:#6366f1;">{{ $p->invoice_no }}</code>
                                                </td>
                                                <td style="white-space:nowrap;font-size:12px;">{{ $p->purchase_date }}
                                                </td>
                                                <td style="font-size:12px;">{{ $p->vendor_name }}</td>
                                                <td class="amt">{{ number_format($p->subtotal, 0) }}</td>
                                                <td class="amt" style="color:#dc2626;">
                                                    {{ number_format($p->discount, 0) }}</td>
                                                <td class="amt" style="color:#d97706;">
                                                    {{ number_format($p->extra_cost, 0) }}</td>
                                                <td class="amt" style="font-weight:700;">
                                                    {{ number_format($p->net_amount, 0) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0;">
                                            <td colspan="3"><strong>Totals</strong></td>
                                            <td class="amt">{{ number_format($purchaseBreakdown->sum('subtotal'), 0) }}
                                            </td>
                                            <td class="amt" style="color:#dc2626;">
                                                {{ number_format($purchaseBreakdown->sum('discount'), 0) }}</td>
                                            <td class="amt" style="color:#d97706;">
                                                {{ number_format($purchaseBreakdown->sum('extra_cost'), 0) }}</td>
                                            <td class="amt">
                                                {{ number_format($purchaseBreakdown->sum('net_amount'), 0) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Expense Vouchers --}}
                <div class="col-lg-6">
                    <div class="pnl-section">
                        <div class="pnl-section-header">
                            <h5><i class="fas fa-receipt text-danger me-2"></i>Expense Voucher Detail</h5>
                        </div>
                        <div class="pnl-section-body p-0" style="max-height:320px;overflow-y:auto;">
                            @if ($expenseBreakdown->isEmpty())
                                <div class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>No
                                    expenses</div>
                            @else
                                <table class="rpt-table">
                                    <thead>
                                        <tr>
                                            <th>EVID</th>
                                            <th>Date</th>
                                            <th>Remarks</th>
                                            <th class="thr">Amount</th>
                                            <th>Type</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($expenseBreakdown as $ev)
                                            @php $isAuto = str_contains($ev->remarks ?? '', 'Auto: Purchase Expensive'); @endphp
                                            <tr>
                                                <td><code style="font-size:11px;color:#6366f1;">{{ $ev->evid }}</code>
                                                </td>
                                                <td style="white-space:nowrap;font-size:12px;">{{ $ev->entry_date }}</td>
                                                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12px;"
                                                    title="{{ $ev->remarks }}">{{ $ev->remarks ?? '—' }}</td>
                                                <td class="amt" style="color:#dc2626;font-weight:700;">
                                                    {{ number_format($ev->total_amount, 0) }}</td>
                                                <td>
                                                    @if ($isAuto)
                                                        <span
                                                            style="background:#fef9c3;color:#854d0e;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;">Auto</span>
                                                    @else
                                                        <span
                                                            style="background:#f0fdf4;color:#166534;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;">Manual</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc;font-weight:700;border-top:2px solid #e2e8f0;">
                                            <td colspan="3"><strong>Total</strong></td>
                                            <td class="amt" style="color:#dc2626;">
                                                {{ number_format($expenseBreakdown->sum('total_amount'), 0) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── SUMMARY FOOTER ───────────────────────────────────────── --}}
            <div class="pnl-section mt-4" style="border-top:3px solid {{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                <div class="pnl-section-body">
                    <div class="row text-center">
                        <div class="col-md-3 border-end">
                            <div
                                style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;">
                                Net Revenue</div>
                            <div style="font-size:22px;font-weight:800;color:#2563eb;">{{ number_format($netRevenue, 2) }}
                            </div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div
                                style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;">
                                COGS (Sold Only)</div>
                            <div style="font-size:22px;font-weight:800;color:#d97706;">{{ number_format($netCOGS, 2) }}
                            </div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div
                                style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;">
                                Gross Profit</div>
                            <div
                                style="font-size:22px;font-weight:800;color:{{ $grossProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ number_format($grossProfit, 2) }}</div>
                        </div>
                        <div class="col-md-3">
                            <div
                                style="font-size:11px;font-weight:700;text-transform:uppercase;color:#94a3b8;letter-spacing:.06em;">
                                Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</div>
                            <div
                                style="font-size:26px;font-weight:900;color:{{ $netProfit >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ number_format($netProfit, 2) }}</div>
                            <div style="font-size:13px;color:#64748b;">{{ $netProfitMargin }}% net margin</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /pnl-wrap --}}
    </div>{{-- /main-content --}}
@endsection
