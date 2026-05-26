@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --brand: #0ea5e9;
            --brand-dark: #0284c7;
            --brand-light: #e0f2fe;
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
            --purple: #7c3aed;
            --purple-lt: #ede9fe;
        }

        body {
            background: var(--bg);
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: var(--ink);
        }

        /* ── Page Header ─────────────────────────────────── */
        .page-header {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 50%, #0369a1 100%);
            border-radius: 16px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 6px 24px rgba(14, 165, 233, .28);
        }

        .page-header .head-left h4 {
            color: #fff;
            margin: 0;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: .3px;
        }

        .page-header .head-left small {
            color: rgba(255, 255, 255, .75);
            font-size: .8rem;
        }

        .page-header .head-right {
            display: flex;
            gap: .6rem;
            align-items: center;
        }

        .btn-hdr {
            padding: .42rem 1.05rem;
            border-radius: 9px;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all .15s;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .btn-hdr-white {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .35);
            color: #fff;
        }

        .btn-hdr-white:hover {
            background: rgba(255, 255, 255, .3);
            color: #fff;
        }

        .btn-hdr-solid {
            background: #fff;
            color: var(--brand-dark);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
        }

        .btn-hdr-solid:hover {
            background: #f0f9ff;
            color: var(--brand-dark);
            transform: translateY(-1px);
        }

        /* ── KPI Cards ───────────────────────────────────── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .kpi-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 1.1rem 1.2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .05);
            transition: box-shadow .2s, transform .2s;
        }

        .kpi-box:hover {
            box-shadow: 0 6px 22px rgba(0, 0, 0, .09);
            transform: translateY(-2px);
        }

        .kpi-box .kpi-indicator {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            border-radius: 13px 0 0 13px;
        }

        .kpi-box .kpi-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            margin-bottom: .55rem;
        }

        .kpi-box .kpi-label {
            font-size: .7rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: .25rem;
        }

        .kpi-box .kpi-value {
            font-size: 1.38rem;
            font-weight: 800;
            color: var(--ink);
            line-height: 1.1;
        }

        .kpi-box .kpi-sub {
            font-size: .7rem;
            color: var(--muted);
            margin-top: .2rem;
        }

        /* ── Table Card ──────────────────────────────────── */
        .tbl-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
        }

        .tbl-card-header {
            padding: 1rem 1.5rem;
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

        /* DataTable overrides */
        table.dataTable thead th {
            background: #f1f5f9 !important;
            color: var(--muted) !important;
            font-size: .71rem !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            letter-spacing: .5px !important;
            border-bottom: 2px solid var(--border) !important;
            padding: .8rem 1rem !important;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            font-size: .85rem;
            padding: .7rem 1rem !important;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9 !important;
            color: var(--ink);
        }

        table.dataTable tbody tr:last-child td {
            border-bottom: none !important;
        }

        table.dataTable tbody tr:hover td {
            background: #f0f9ff !important;
        }

        /* ── Status Badges ───────────────────────────────── */
        .s-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: .7rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .s-posted {
            background: var(--brand-light);
            color: var(--brand-dark);
        }

        .s-draft {
            background: #f1f5f9;
            color: var(--muted);
        }

        .s-returned {
            background: var(--red-lt);
            color: var(--red);
        }

        .s-partial {
            background: var(--yellow-lt);
            color: var(--yellow);
        }

        .s-sale {
            background: var(--green-lt);
            color: var(--green);
        }

        /* ── Invoice Tag ─────────────────────────────────── */
        .inv-tag {
            font-size: .78rem;
            font-weight: 700;
            color: var(--brand-dark);
            background: var(--brand-light);
            padding: 2px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        /* ── Amounts ─────────────────────────────────────── */
        .amt-green {
            color: var(--green);
            font-weight: 600;
        }

        .amt-red {
            color: var(--red);
            font-weight: 600;
        }

        .amt-muted {
            color: var(--muted);
            font-size: .8rem;
        }

        .amt-bold {
            font-weight: 700;
        }

        /* ── Action buttons ──────────────────────────────── */
        .act-btn {
            padding: .27rem .65rem;
            border-radius: 6px;
            font-size: .74rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            white-space: nowrap;
            transition: opacity .15s, transform .15s;
            border: none;
            cursor: pointer;
            line-height: 1.4;
        }

        .act-btn:hover {
            opacity: .85;
            transform: translateY(-1px);
        }

        .act-invoice {
            background: var(--brand-light);
            color: var(--brand-dark);
        }

        .act-dc {
            background: #f1f5f9;
            color: var(--muted);
        }

        .act-receipt {
            background: var(--green-lt);
            color: var(--green);
        }

        .act-return {
            background: var(--red-lt);
            color: var(--red);
        }

        .act-confirm {
            background: var(--yellow-lt);
            color: var(--yellow);
        }

        .act-disabled {
            background: #f1f5f9;
            color: #b0b8c8;
            cursor: not-allowed;
        }

        /* DataTables toolbar */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: .38rem .85rem;
            font-size: .85rem;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--brand);
            outline: none;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, .15);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: .3rem .5rem;
            font-size: .85rem;
        }

        .dataTables_paginate .paginate_button.current {
            background: var(--brand) !important;
            color: #fff !important;
            border-color: var(--brand) !important;
            border-radius: 7px !important;
        }

        .dataTables_paginate .paginate_button:hover {
            background: var(--brand-light) !important;
            color: var(--brand-dark) !important;
            border-color: var(--border) !important;
            border-radius: 7px !important;
        }

        /* Product names tooltip */
        td[title] {
            cursor: help;
        }
    </style>

    <div class="container-fluid py-4">

        {{-- ── Page Header ── --}}
        <div class="page-header">
            <div class="head-left">
                <h4><i class="fas fa-shopping-cart me-2"></i>Sales</h4>
                <small>Manage and track all sale transactions</small>
            </div>
            <div class="head-right">
                <a href="{{ route('sale.return.index') }}" class="btn-hdr btn-hdr-white">
                    <i class="fas fa-undo-alt"></i> All Returns
                </a>
                <a href="{{ url('bookings') }}" class="btn-hdr btn-hdr-white">
                    <i class="fas fa-calendar-check"></i> All Bookings
                </a>
                @can('sales.create')
                    <a href="{{ route('sale.add') }}" class="btn-hdr btn-hdr-solid">
                        <i class="fas fa-plus"></i> Add Sale
                    </a>
                @endcan
            </div>
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

        {{-- ── KPI Cards ── --}}
        @php
            $totalSales = $sales->count();
            $totalNet = $sales->sum('total_net');
            $totalGross = $sales->sum('total_bill_amount');
            $totalDisc = $sales->sum('total_extradiscount');
            $postedCount = $sales->where('sale_status', 'posted')->count();
            $draftCount = $sales->whereIn('sale_status', ['draft', 'booked'])->count();
            $returnedCount = $sales->where('sale_status', 'returned')->count();
        @endphp
        <div class="kpi-grid">
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--brand);"></div>
                <div class="kpi-icon" style="background:var(--brand-light);color:var(--brand-dark);">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="kpi-label">Total Sales</div>
                <div class="kpi-value">{{ $totalSales }}</div>
                <div class="kpi-sub">All transactions</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--green);"></div>
                <div class="kpi-icon" style="background:var(--green-lt);color:var(--green);">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="kpi-label">Net Revenue</div>
                <div class="kpi-value amt-green">{{ number_format($totalNet, 0) }}</div>
                <div class="kpi-sub">After discounts</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--muted);"></div>
                <div class="kpi-icon" style="background:#f1f5f9;color:var(--muted);">
                    <i class="fas fa-tag"></i>
                </div>
                <div class="kpi-label">Total Discounts</div>
                <div class="kpi-value amt-red">{{ number_format($totalDisc, 0) }}</div>
                <div class="kpi-sub">Extra discounts given</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--brand-dark);"></div>
                <div class="kpi-icon" style="background:var(--brand-light);color:var(--brand-dark);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="kpi-label">Posted</div>
                <div class="kpi-value">{{ $postedCount }}</div>
                <div class="kpi-sub">Confirmed sales</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--yellow);"></div>
                <div class="kpi-icon" style="background:var(--yellow-lt);color:var(--yellow);">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="kpi-label">Draft / Booked</div>
                <div class="kpi-value">{{ $draftCount }}</div>
                <div class="kpi-sub">Pending confirmation</div>
            </div>
            <div class="kpi-box">
                <div class="kpi-indicator" style="background:var(--red);"></div>
                <div class="kpi-icon" style="background:var(--red-lt);color:var(--red);">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <div class="kpi-label">Returned</div>
                <div class="kpi-value">{{ $returnedCount }}</div>
                <div class="kpi-sub">Fully returned sales</div>
            </div>
        </div>

        {{-- ── Table Card ── --}}
        <div class="tbl-card">
            <div class="tbl-card-header">
                <h6><i class="fas fa-table me-2 text-muted"></i>Sale Transactions</h6>
                <span class="badge"
                    style="background:var(--brand-light);color:var(--brand-dark);font-size:.78rem;padding:.38rem .9rem;border-radius:20px;">
                    {{ $totalSales }} Records
                </span>
            </div>

            <div class="table-responsive p-3">
                <table id="sales-table" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Products</th>
                            <th>Qty</th>
                            <th>Gross</th>
                            <th>Discount</th>
                            <th>Net Total</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sales as $sale)
                            @php
                                // Product Names
                                $pNames = 'N/A';
                                if ($sale->items && $sale->items->count() > 0) {
                                    $pNames = $sale->items
                                        ->map(fn($item) => (optional($item->product)->item_name ?? '?') . ' ' . (optional($item->product->brand)->name ?? ''))
                                        ->implode(', ');
                                } elseif ($sale->product) {
                                    $pNames = $sale->product;
                                }

                                // Status logic including partial returns
                                $totalReturned = $sale->returns->sum('net_amount');
                                $isFullyReturned = $totalReturned >= $sale->total_net && $sale->total_net > 0;
                                $isPartiallyReturned = !$isFullyReturned && $totalReturned > 0;

                                $statusClass = 's-draft';
                                $statusIcon = 'fa-circle';
                                $statusLabel = 'Draft';

                                if ($isFullyReturned) {
                                    $statusClass = 's-returned';
                                    $statusIcon = 'fa-undo-alt';
                                    $statusLabel = 'Returned';
                                } elseif ($isPartiallyReturned) {
                                    $statusClass = 's-partial';
                                    $statusIcon = 'fa-chart-pie';
                                    $statusLabel = 'Partial Return';
                                } elseif ($sale->sale_status === 'posted') {
                                    $statusClass = 's-posted';
                                    $statusIcon = 'fa-check-circle';
                                    $statusLabel = 'Posted';
                                } elseif ($sale->sale_status === null) {
                                    $statusClass = 's-sale';
                                    $statusIcon = 'fa-shopping-bag';
                                    $statusLabel = 'Sale';
                                } elseif ($sale->sale_status === 'booked') {
                                    $statusClass = 's-draft';
                                    $statusIcon = 'fa-calendar-check';
                                    $statusLabel = 'Booked';
                                }

                                // Return sub-badge
                                $returnBadge = '';
                                if ($sale->returns) {
                                    if ($sale->returns->where('return_status', 'approved')->isNotEmpty()) {
                                        $returnBadge =
                                            '<span class="s-badge s-returned mt-1"><i class="fas fa-check-circle"></i> Return Approved</span>';
                                    } elseif ($sale->returns->where('return_status', 'pending')->isNotEmpty()) {
                                        $returnBadge =
                                            '<span class="s-badge s-posted mt-1"><i class="fas fa-clock"></i> Return Pending</span>';
                                    }
                                }

                                $isDraft = in_array($sale->sale_status, ['draft', 'booked']);
                                $canReturn = !$isFullyReturned && !$isDraft;

                                $paidAmount = $sale->payments ? $sale->payments->sum('amount') : 0;
                                $updatedNet = max(0, $sale->total_net - $totalReturned);
                                $dueAmount = max(0, $updatedNet - $paidAmount);
                            @endphp
                            <tr>
                                <td class="amt-muted">{{ $sale->id }}</td>

                                <td>
                                    <span class="inv-tag">{{ $sale->invoice_no ?? 'SLE-' . $sale->id }}</span>
                                    @if ($sale->reference)
                                        <div style="font-size:.7rem;color:var(--muted);margin-top:2px;">
                                            {{ $sale->reference }}</div>
                                    @endif
                                </td>

                                <td>
                                    <span style="font-weight:600;">
                                        {{ optional($sale->customer_relation)->customer_name ?? 'Walk-in' }}
                                    </span>
                                </td>

                                <td title="{{ $pNames }}">
                                    <span
                                        style="font-size:.82rem;">{{ \Illuminate\Support\Str::limit($pNames, 38) }}</span>
                                </td>

                                <td class="text-center" style="font-weight:600;">
                                    {{ ($sale->total_items > 0 ? $sale->total_items : $sale->qty) }} Pcs
                                </td>

                                <td>{{ number_format($sale->total_bill_amount > 0 ? $sale->total_bill_amount : (float) $sale->per_total, 0) }}
                                </td>

                                <td class="amt-red">
                                    @if ($sale->total_extradiscount > 0)
                                        - {{ number_format($sale->total_extradiscount, 0) }}
                                    @else
                                        <span class="amt-muted">—</span>
                                    @endif
                                </td>

                                <td class="amt-green amt-bold">
                                    @if ($totalReturned > 0)
                                        <div><small
                                                class="text-muted text-decoration-line-through">{{ number_format($sale->total_net, 0) }}</small>
                                        </div>
                                        <div class="text-success">{{ number_format($updatedNet, 0) }}</div>
                                        <small class="text-danger">(-{{ number_format($totalReturned, 0) }})</small>
                                    @else
                                        {{ number_format($sale->total_net, 0) }}
                                    @endif
                                </td>

                                <td class="text-success fw-bold">{{ number_format($paidAmount, 0) }}</td>
                                <td>
                                    @if ($dueAmount > 0)
                                        <span
                                            class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">{{ number_format($dueAmount, 0) }}</span>
                                    @else
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Paid</span>
                                    @endif
                                </td>

                                <td style="font-size:.82rem;font-weight:600;white-space:nowrap;">
                                    <i class="far fa-calendar-alt me-1 text-muted"></i>
                                    {{ $sale->created_at->format('d M Y') }}
                                </td>

                                <td>
                                    <span class="s-badge {{ $statusClass }}">
                                        <i class="fas {{ $statusIcon }}"></i> {{ $statusLabel }}
                                    </span>
                                    @if ($returnBadge)
                                        <br>{!! $returnBadge !!}
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($isDraft)
                                            <a href="{{ route('sales.edit', $sale->id) }}" class="act-btn act-confirm">
                                                <i class="fas fa-edit"></i> Confirm
                                            </a>
                                            <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
                                                class="act-btn act-invoice">
                                                <i class="fas fa-file-invoice"></i> Invoice
                                            </a>
                                        @else
                                            <a href="{{ route('sales.invoice', $sale->id) }}" target="_blank"
                                                class="act-btn act-invoice">
                                                <i class="fas fa-file-invoice"></i> Invoice
                                            </a>
                                            <a href="{{ route('sales.dc', $sale->id) }}" target="_blank"
                                                class="act-btn act-dc">
                                                <i class="fas fa-truck"></i> DC
                                            </a>
                                            <a href="{{ route('sales.receipt', $sale->id) }}" target="_blank"
                                                class="act-btn act-receipt">
                                                <i class="fas fa-receipt"></i> Receipt
                                            </a>
                                            @if ($canReturn && $sale->sale_status === 'posted')
                                                <a href="{{ route('sale.return.show', $sale->id) }}"
                                                    class="act-btn act-return">
                                                    <i class="fas fa-undo-alt"></i> Return
                                                </a>
                                            @else
                                                <span class="act-btn act-disabled">
                                                    <i class="fas fa-ban"></i> Returned
                                                </span>
                                            @endif
                                            @if($sale->sale_status === 'posted')
                                                @can('sales.unpost')
                                                    <a href="javascript:void(0);" class="act-btn act-confirm btn-unpost-srn"
                                                       data-id="{{ $sale->id }}" data-invoice="{{ $sale->invoice_no }}">
                                                        <i class="fas fa-history"></i> Un-post
                                                    </a>
                                                @endcan
                                            @elseif($sale->sale_status === 'booked')
                                                @can('sales.edit')
                                                    <a href="{{ route('sales.edit', $sale->id) }}" class="act-btn act-edit">
                                                        <i class="fas fa-edit"></i> Edit Draft
                                                    </a>
                                                @endcan
                                            @endif
                                        @endif
                                    </div>
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
            $('#sales-table').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    [0, 'desc']
                ],
                columnDefs: [{
                        orderable: false,
                        targets: [3, 10]
                    } // Products & Actions not sortable
                ],
                language: {
                    search: '',
                    searchPlaceholder: '🔍  Search sales...',
                    lengthMenu: 'Show _MENU_',
                    paginate: {
                        previous: '‹',
                        next: '›'
                    }
                }
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
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: '/sales/' + id + '/unpost',
                            type: 'GET',
                            dataType: 'json'
                        }).catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error.responseJSON.message || 'Unknown error'}`
                            )
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed && result.value && result.value.success) {
                        Swal.fire('Reverted!', result.value.message, 'success').then(() => {
                            location.reload();
                        });
                    }
                });
            });
        });
    </script>
@endsection
