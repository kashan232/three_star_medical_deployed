@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --brand: #4f46e5;
            --brand-light: #ede9fe;
            --ink: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --white: #ffffff;
            --bg: #f8fafc;
            --green: #059669;
            --green-lt: #d1fae5;
            --red: #dc2626;
            --red-lt: #fee2e2;
            --yellow: #d97706;
            --yellow-lt: #fef3c7;
            --blue: #2563eb;
            --blue-lt: #dbeafe;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        /* ── Page Header ─────────────────────────────────── */
        .page-header {
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            border-radius: 14px;
            padding: 1.4rem 1.8rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(5, 150, 105, .25);
        }

        .page-header h4 {
            color: #fff;
            margin: 0;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: .3px;
        }

        .page-header small {
            color: rgba(255, 255, 255, .7);
            font-size: .8rem;
        }

        .page-header .btn-back {
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .3);
            color: #fff;
            padding: .45rem 1.1rem;
            border-radius: 8px;
            font-size: .85rem;
            font-weight: 500;
            text-decoration: none;
            transition: background .2s;
        }

        .page-header .btn-back:hover {
            background: rgba(255, 255, 255, .28);
            color: #fff;
        }

        /* ── KPI Cards ───────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.1rem 1.2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            transition: box-shadow .2s, transform .2s;
        }

        .kpi-box:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, .09);
            transform: translateY(-2px);
        }

        .kpi-box .kpi-indicator {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 12px 0 0 12px;
        }

        .kpi-box .kpi-label {
            font-size: .72rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: .3rem;
        }

        .kpi-box .kpi-value {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1;
        }

        .kpi-box .kpi-sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .25rem;
        }

        /* ── Table Card ──────────────────────────────────── */
        .tbl-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        }

        .tbl-card-header {
            padding: .9rem 1.4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: #fafbfc;
        }

        .tbl-card-header h6 {
            margin: 0;
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        /* DataTable override */
        table.dataTable thead th {
            background: #f1f5f9 !important;
            color: var(--muted) !important;
            font-size: .72rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: .5px !important;
            border-bottom: 2px solid var(--border) !important;
            padding: .75rem 1rem !important;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: .85rem;
            padding: .7rem 1rem !important;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: var(--ink);
        }

        table.dataTable tbody tr:last-child td {
            border-bottom: none;
        }

        table.dataTable tbody tr:hover td {
            background: #f0fdf4 !important;
        }

        /* ── Badges ──────────────────────────────────────── */
        .badge-full {
            background: var(--red-lt);
            color: var(--red);
        }

        .badge-partial {
            background: var(--yellow-lt);
            color: var(--yellow);
        }

        .badge-standalone {
            background: #f1f5f9;
            color: var(--muted);
        }

        .ret-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ── Amount Colours ──────────────────────────────── */
        .amt-red {
            color: var(--red);
            font-weight: 600;
        }

        .amt-green {
            color: var(--green);
            font-weight: 600;
        }

        .amt-yellow {
            color: var(--yellow);
            font-weight: 600;
        }

        .amt-muted {
            color: var(--muted);
            font-size: .8rem;
        }

        /* ── Invoice Tag ─────────────────────────────────── */
        .inv-tag {
            font-size: .8rem;
            font-weight: 700;
            color: #059669;
            background: var(--green-lt);
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        .inv-orig {
            font-size: .72rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* ── Action Button ───────────────────────────────── */
        .btn-view {
            background: #059669;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: .3rem .85rem;
            font-size: .78rem;
            font-weight: 600;
            text-decoration: none;
            transition: background .15s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-view:hover {
            background: #047857;
            color: #fff;
        }

        /* DataTables toolbar */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .35rem .75rem;
            font-size: .85rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .3rem .5rem;
            font-size: .85rem;
        }

        .dataTables_paginate .paginate_button.current {
            background: #059669 !important;
            color: #fff !important;
            border-color: #059669 !important;
            border-radius: 6px !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--green-lt) !important;
            color: var(--green) !important;
            border-color: var(--border) !important;
            border-radius: 6px !important;
        }
    </style>

    <div class="container-fluid py-4">

        {{-- ── Page Header ── --}}
        <div class="page-header">
            <div>
                <h4><i class="fas fa-undo-alt me-2"></i>Sale Returns</h4>
                <small>All sale return transactions &amp; partial return status</small>
            </div>
            <a href="{{ route('sale.index') }}" class="btn-back">
                <i class="fas fa-arrow-left me-1"></i> Back to Sales
            </a>
        </div>

        {{-- ── Session Alerts ── --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ── KPI Summary Cards ── --}}
        @php
            $totalReturns = $returns->count();
            $totalReturnAmt = $returns->sum('net_amount');
            $fullReturns = $returns->filter(fn($r) => $r->sale && ($r->new_net_amount ?? 1) == 0)->count();
            $partialReturns = $returns->filter(fn($r) => $r->sale && ($r->new_net_amount ?? 0) > 0)->count();
            $standalone = $returns->filter(fn($r) => !$r->sale)->count();
        @endphp
        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:#059669;"></div>
                <div class="kpi-label">Total Returns</div>
                <div class="kpi-value">{{ $totalReturns }}</div>
                <div class="kpi-sub">All processed returns</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--red);"></div>
                <div class="kpi-label">Total Returned Amt</div>
                <div class="kpi-value" style="color:var(--red);">{{ number_format($totalReturnAmt, 0) }}</div>
                <div class="kpi-sub">PKR refunded to customer</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--red);"></div>
                <div class="kpi-label">Full Returns</div>
                <div class="kpi-value">{{ $fullReturns }}</div>
                <div class="kpi-sub">100% of sale returned</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--yellow);"></div>
                <div class="kpi-label">Partial Returns</div>
                <div class="kpi-value">{{ $partialReturns }}</div>
                <div class="kpi-sub">Part of sale returned</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--muted);"></div>
                <div class="kpi-label">Standalone</div>
                <div class="kpi-value">{{ $standalone }}</div>
                <div class="kpi-sub">Not linked to a sale</div>
            </div>
        </div>

        {{-- ── Table Card ── --}}
        <div class="tbl-card">
            <div class="tbl-card-header">
                <h6><i class="fas fa-list me-2 text-muted"></i>Return Transactions</h6>
                <span class="badge"
                    style="background:var(--green-lt);color:#059669;font-size:.78rem;padding:.35rem .8rem;border-radius:20px;">
                    {{ $totalReturns }} Records
                </span>
            </div>

            <div class="table-responsive p-3">
                <table id="return-table" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Warehouse</th>
                            <th>Return Date</th>
                            <th>Return Amt</th>
                            <th>Orig. Sale</th>
                            <th>Total Returned</th>
                            <th>New Net</th>
                            <th>New Due</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($returns as $return)
                            @php
                                $isPartialReturn = $return->sale && ($return->new_net_amount ?? 0) > 0;
                                $isFullReturn = $return->sale && ($return->new_net_amount ?? 1) == 0;
                            @endphp
                            <tr>
                                <td class="amt-muted">{{ $return->id }}</td>

                                <td>
                                    <span class="inv-tag">{{ $return->return_invoice }}</span>
                                    @if ($return->sale)
                                        <div class="inv-orig">Orig: {{ $return->sale->invoice_no }}</div>
                                    @endif
                                </td>

                                <td>
                                    <span style="font-weight:600;">
                                        {{ $return->customer->customer_name ?? '—' }}
                                    </span>
                                </td>

                                <td class="amt-muted">{{ $return->warehouse->warehouse_name ?? '—' }}</td>

                                <td>
                                    <div style="font-size:.82rem;font-weight:600;">
                                        {{ \Carbon\Carbon::parse($return->return_date)->format('d M Y') }}
                                    </div>
                                </td>

                                {{-- Return Amount --}}
                                <td class="amt-red">
                                    - {{ number_format($return->net_amount, 0) }}
                                </td>

                                {{-- Original Sale Amount --}}
                                <td>
                                    @if ($return->sale)
                                        {{ number_format($return->original_net_amount, 0) }}
                                    @else
                                        <span class="amt-muted">—</span>
                                    @endif
                                </td>

                                {{-- Total Returned --}}
                                <td class="amt-red">
                                    @if ($return->sale)
                                        {{ number_format($return->total_returned, 0) }}
                                    @else
                                        <span class="amt-muted">—</span>
                                    @endif
                                </td>

                                {{-- New Net --}}
                                <td class="amt-green">
                                    @if ($return->sale)
                                        {{ number_format($return->new_net_amount, 0) }}
                                    @else
                                        <span class="amt-muted">—</span>
                                    @endif
                                </td>

                                {{-- New Due --}}
                                <td class="amt-yellow">
                                    @if ($return->sale)
                                        {{ number_format($return->new_due_amount, 0) }}
                                    @else
                                        <span class="amt-muted">—</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td>
                                    @if ($isFullReturn)
                                        <span class="ret-badge badge-full"><i class="fas fa-times-circle"></i> Full
                                            Return</span>
                                    @elseif($isPartialReturn)
                                        <span class="ret-badge badge-partial"><i class="fas fa-chart-pie"></i>
                                            Partial</span>
                                    @else
                                        <span class="ret-badge badge-standalone"><i class="fas fa-minus-circle"></i>
                                            Standalone</span>
                                    @endif
                                </td>

                                <td>
                                    <a href="{{ route('sale.return.view', $return->id) }}" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

@section('js')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#return-table').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ],
                language: {
                    search: '<i class="fas fa-search me-1"></i>',
                    searchPlaceholder: 'Search returns...',
                    lengthMenu: 'Show _MENU_',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
            });
        });
    </script>
@endsection
