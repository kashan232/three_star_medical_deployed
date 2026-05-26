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

        /* ── Filter card ─────────────────────────────────────────── */
        .filter-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px 22px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 14px;
            align-items: flex-end;
        }

        .fg label {
            font-size: .73rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--muted);
            display: block;
            margin-bottom: 5px;
        }

        .fg select,
        .fg input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: .88rem;
            color: var(--ink);
            background: var(--bg);
            outline: none;
            transition: border-color .15s, background .15s;
            box-sizing: border-box;
        }

        .fg select:focus,
        .fg input:focus {
            border-color: var(--brand);
            background: var(--white);
            box-shadow: 0 0 0 3px #ede9fe80;
        }

        .generate-btn-wrap {
            padding-bottom: 0;
        }

        @media (max-width:768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Customer profile card ───────────────────────────────── */
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

            .filter-card,
            .topbar-actions,
            .no-print {
                display: none !important;
            }

            .cust-profile {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .tbl-card {
                border: none !important;
            }

            .print-header {
                display: block !important;
            }

            body {
                background: #fff !important;
            }

            .led-page {
                background: #fff !important;
                padding: 0;
            }
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="led-page container-fluid">

                {{-- Top Bar --}}
                <div class="led-topbar">
                    <div>
                        <h4>
                            <svg style="width:22px;height:22px;color:#4f46e5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Customer Ledger
                        </h4>
                        <p>Detailed statement of account — debits, credits & running balance</p>
                    </div>
                    <div class="topbar-actions no-print">
                        <a href="{{ route('customers.index') }}" class="btn-led btn-reset-form"
                            style="text-decoration:none;">
                            Users
                        </a>
                        @if (request('customer_id') && $CustomerLedgers->count() > 0)
                            <button class="btn-led btn-print" onclick="window.print()">🖨 Print</button>
                        @endif
                    </div>
                </div>

                {{-- Filter Card --}}
                <div class="filter-card no-print">
                    <form method="GET" action="{{ route('customers.ledger') }}">
                        <div class="filter-grid">
                            <div class="fg">
                                <label>Customer</label>
                                <select name="customer_id" class="select2">
                                    <option value="">— Select Customer —</option>
                                    @foreach ($customers as $c)
                                        <option value="{{ $c->id }}"
                                            {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                            {{ $c->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="fg">
                                <label>Start Date</label>
                                <input type="date" name="from_date" value="{{ request('from_date', date('Y-m-01')) }}">
                            </div>
                            <div class="fg">
                                <label>End Date</label>
                                <input type="date" name="to_date" value="{{ request('to_date', date('Y-m-d')) }}">
                            </div>
                            <div class="fg generate-btn-wrap">
                                <button type="submit" class="btn-led btn-gen w-100"
                                    style="width:100%;justify-content:center;padding:9px 20px;">
                                    <span style="font-size:1rem;margin-right:2px;position:relative;top:1px;">🛡</span>
                                    Generate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                @if (request('customer_id'))
                    @if ($CustomerLedgers->count() > 0)
                        @php
                            $ob = $CustomerLedgers->first()->previous_balance ?? 0;
                            $cb = $CustomerLedgers->last()->closing_balance ?? 0;
                            $totalDebit = $CustomerLedgers->sum('debit');
                            $totalCredit = $CustomerLedgers->sum('credit');
                            $cust = $CustomerLedgers->first()->customer;
                            $start = request('from_date');
                            $end = request('to_date');

                            $netBal = $cb;
                            $balLabel = $netBal >= 0 ? 'Receivable (Dr)' : 'Advance/Credit (Cr)';
                            $balKlass = $netBal >= 0 ? 'k-red' : 'k-green';
                        @endphp

                        {{-- Customer Profile --}}
                        <div class="cust-profile">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px;">
                                    <span
                                        style="font-size:1.4rem;font-weight:900;">{{ $cust->customer_name ?? 'N/A' }}</span>
                                    <span class="cust-badge">{{ $cust->customer_type ?? 'Main Customer' }}</span>
                                    <span class="cust-badge">{{ $cust->customer_id ?? 'N/A' }}</span>
                                </div>
                                <div class="cust-meta">
                                    <span>📱 {{ $cust->mobile ?? 'N/A' }}</span>
                                    <span>📍 {{ $cust->address ?? 'N/A' }}</span>
                                    <span>💳 Default Balance: Rs {{ number_format($cust->opening_balance ?? 0, 2) }}</span>
                                </div>
                            </div>
                            <div style="text-align:right;">
                                    <div class="period-badge">📅 {{ \Carbon\Carbon::parse($start)->format('d-m-Y') }} → {{ \Carbon\Carbon::parse($end)->format('d-m-Y') }}</div>
                            </div>
                        </div>

                        {{-- KPI Row --}}
                        <div class="kpi-row">
                            <div class="kpi-box k-blue">
                                <div class="kpi-lbl">Opening Balance</div>
                                <div class="kpi-val">Rs {{ number_format(abs($ob), 2) }}</div>
                                <div class="kpi-sub">{{ $ob >= 0 ? 'Receivable' : 'Credit/Advance' }}</div>
                            </div>
                            <div class="kpi-box k-sky">
                                <div class="kpi-lbl">Total Invoiced (Dr)</div>
                                <div class="kpi-val" style="color:var(--red)">Rs {{ number_format($totalDebit, 2) }}</div>
                                <div class="kpi-sub">Charged to customer</div>
                            </div>
                            <div class="kpi-box k-green">
                                <div class="kpi-lbl">Total Received (Cr)</div>
                                <div class="kpi-val" style="color:var(--green)">Rs {{ number_format($totalCredit, 2) }}
                                </div>
                                <div class="kpi-sub">Payments collected</div>
                            </div>
                            <div class="kpi-box k-amber">
                                <div class="kpi-lbl">Transactions</div>
                                <div class="kpi-val">{{ $CustomerLedgers->count() }}</div>
                                <div class="kpi-sub">In period</div>
                            </div>
                            <div class="kpi-box {{ $balKlass }}">
                                <div class="kpi-lbl">Closing Balance</div>
                                <div class="kpi-val" style="color:{{ $netBal >= 0 ? 'var(--red)' : 'var(--green)' }}">Rs
                                    {{ number_format(abs($cb), 2) }}</div>
                                <div class="kpi-sub">{{ $balLabel }}</div>
                            </div>
                        </div>

                        {{-- Ledger Table --}}
                        <div class="tbl-card">
                            <div style="overflow-x:auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:110px;">Date</th>
                                            <th style="width:120px;">Ref / Invoice</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th class="tr">Debit (Dr)</th>
                                            <th class="tr">Credit (Cr)</th>
                                            <th class="tr" style="width:150px;">Running Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Opening row --}}
                                        @php
                                            $obClass = $ob >= 0 ? 'bal-dr' : ($ob < 0 ? 'bal-cr' : 'bal-zero');
                                            $obLabel = $ob > 0.01 ? 'Dr' : ($ob < -0.01 ? 'Cr' : '—');
                                        @endphp
                                        <tr class="row-opening">
                                            <td>—</td>
                                            <td>—</td>
                                            <td><span class="tx-type tx-journal">B/F</span></td>
                                            <td>Opening Balance Brought Forward</td>
                                            <td class="tr amt-nil">—</td>
                                            <td class="tr amt-nil">—</td>
                                            <td class="tr {{ $obClass }}">
                                                Rs {{ number_format(abs($ob), 2) }}
                                                <small style="font-size:.7em;opacity:.7;">{{ $obLabel }}</small>
                                            </td>
                                        </tr>

                                        {{-- Transaction rows --}}
                                        @foreach ($CustomerLedgers as $ledger)
                                            @php
                                                $dr = $ledger->debit ?? 0;
                                                $cr = $ledger->credit ?? 0;
                                                $bal = $ledger->closing_balance;
                                                $bc = $bal > 0 ? 'bal-dr' : ($bal < 0 ? 'bal-cr' : 'bal-zero');
                                                $bl = $bal > 0.01 ? 'Dr' : ($bal < -0.01 ? 'Cr' : '—');

                                                // Extract ref / invoice from description if any
                                                $descLower = strtolower($ledger->description);
                                                preg_match('/#(inv|sr|pv|jv)-?\d+/i', $ledger->description, $matches);
                                                $invoice = $matches[0] ?? '—';

                                                if (str_contains($descLower, 'return')) {
                                                    $badgeHtml = '<span class="tx-type tx-return">↩ Return</span>';
                                                } elseif (
                                                    str_contains($descLower, 'payment') ||
                                                    str_contains($descLower, 'receipt')
                                                ) {
                                                    $badgeHtml = '<span class="tx-type tx-receipt">✔ Receipt</span>';
                                                } elseif (
                                                    str_contains($descLower, 'sale') ||
                                                    str_contains($descLower, 'invoice')
                                                ) {
                                                    $badgeHtml = '<span class="tx-type tx-sale">💰 Sale</span>';
                                                } else {
                                                    $badgeHtml = '<span class="tx-type tx-journal">📖 Journal</span>';
                                                }
                                            @endphp
                                            <tr>
                                                <td style="color:var(--muted);font-size:.82rem;">
                                                        {{ \Carbon\Carbon::parse($ledger->created_at)->format('d-m-Y') }}
                                                </td>
                                                <td>
                                                    @if ($invoice !== '—')
                                                        <span class="inv-badge">{{ $invoice }}</span>
                                                    @else
                                                        <span style="color:var(--muted)">—</span>
                                                    @endif
                                                </td>
                                                <td>{!! $badgeHtml !!}</td>
                                                <td style="max-width:280px;font-size:.82rem;">{{ $ledger->description }}
                                                </td>
                                                <td class="tr {{ $dr > 0 ? 'amt-dr' : 'amt-nil' }}">
                                                    {{ $dr > 0 ? 'Rs ' . number_format($dr, 2) : '—' }}</td>
                                                <td class="tr {{ $cr > 0 ? 'amt-cr' : 'amt-nil' }}">
                                                    {{ $cr > 0 ? 'Rs ' . number_format($cr, 2) : '—' }}</td>
                                                <td class="tr {{ $bc }}">
                                                    Rs {{ number_format(abs($bal), 2) }}
                                                    <small style="font-size:.7em;opacity:.7">{{ $bl }}</small>
                                                </td>
                                            </tr>
                                        @endforeach

                                        {{-- Period Totals --}}
                                        <tr class="row-total">
                                            <td colspan="3" style="text-align:right;color:var(--muted);">Period Totals:
                                            </td>
                                            <td style="color:var(--muted);font-size:.8rem;">{{ $CustomerLedgers->count() }}
                                                transaction(s)</td>
                                            <td class="tr amt-dr">Rs {{ number_format($totalDebit, 2) }}</td>
                                            <td class="tr amt-cr">Rs {{ number_format($totalCredit, 2) }}</td>
                                            <td class="tr">—</td>
                                        </tr>

                                        {{-- Closing Balance --}}
                                        @php
                                            $cbClass = $cb >= 0 ? 'bal-dr' : 'bal-cr';
                                            $cbLabel =
                                                $cb > 0.01
                                                    ? 'Receivable (Dr)'
                                                    : ($cb < -0.01
                                                        ? 'Advance (Cr)'
                                                        : 'Settled');
                                        @endphp
                                        <tr class="row-closing">
                                            <td colspan="4" style="text-align:right;">
                                                Closing Balance as of <strong>{{ $end }}</strong>:
                                            </td>
                                            <td colspan="2"></td>
                                            <td class="tr {{ $cbClass }}" style="font-size:1rem;">
                                                Rs {{ number_format(abs($cb), 2) }}
                                                <div style="font-size:.72rem;font-weight:500;opacity:.75;">
                                                    {{ $cbLabel }}</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="bottom-bar">
                            <small>🕐 Generated: {{ now()->format('d/m/Y, h:i:s a') }}</small>
                            <small>{{ $CustomerLedgers->count() }} transaction(s) in period</small>
                        </div>
                    @else
                        {{-- Empty State --}}
                        <div class="tbl-card">
                            <div class="empty-ledger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <p>No transactions found for this period.</p>
                            </div>
                        </div>
                    @endif
                @else
                    {{-- Initial Blank State --}}
                    <div class="tbl-card">
                        <div class="empty-ledger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                style="width:52px;opacity:.3;margin-bottom:12px;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <h6>Select a Customer</h6>
                            <p>Choose a customer above to view their ledger statement.</p>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            if ($('.select2').length) {
                $('.select2').select2({
                    width: '100%'
                });
            }
        });
    </script>
@endpush
