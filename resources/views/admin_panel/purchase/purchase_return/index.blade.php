@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap');

        :root {
            --brand: #7c3aed;
            --brand-light: #f5f3ff;
            --brand-dark: #5b21b6;
            --success: #10b981;
            --danger: #ef4444;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-300: #cbd5e1;
            --slate-700: #334155;
            --slate-900: #0f172a;
        }

        body {
            background-color: #fcfcfd;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--slate-700);
        }

        .main-content {
            padding: 1.5rem;
        }

        /* Gradient Header */
        .return-header {
            background: linear-gradient(135deg, #4c1d95 0%, #1e1b4b 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 20px 25px -5px rgba(124, 58, 237, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .return-header::after {
            content: '';
            position: absolute;
            bottom: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-info h4 {
            font-weight: 800;
            font-size: 1.85rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            color: white;
        }

        .header-info p {
            font-size: 0.95rem;
            opacity: 0.7;
            margin: 0;
        }

        /* KPI Grid */
        .kpi-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .kpi-mini-card {
            background: white;
            border-radius: 20px;
            padding: 1.25rem;
            border: 1px solid var(--slate-100);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .kpi-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .bg-purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .bg-rose {
            background: #fff1f2;
            color: #e11d48;
        }

        .kpi-data h3 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--slate-900);
            margin: 0;
        }

        .kpi-data span {
            font-size: 0.75rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
        }

        /* The Return Table */
        .table-glass-container {
            background: white;
            border-radius: 24px;
            border: 1px solid var(--slate-100);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        #return-table {
            width: 100% !important;
            border-collapse: collapse;
        }

        #return-table thead th {
            background: var(--slate-50);
            padding: 1.25rem 1rem;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--slate-700);
            border-bottom: 1px solid var(--slate-100);
        }

        #return-table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--slate-50);
            font-size: 0.875rem;
        }

        #return-table tbody tr:hover {
            background: #fafafb;
        }

        /* Styling Components */
        .invoice-link {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: var(--brand);
            background: #f5f3ff;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .vendor-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vendor-initial {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: var(--brand);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.8rem;
        }

        .batch-summary-box {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .summary-tag {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 4px;
            width: fit-content;
        }

        .tag-lot {
            background: #f1f5f9;
            color: #475569;
        }

        .tag-mfg {
            background: #f0fdf4;
            color: #166534;
        }

        .tag-exp {
            background: #fef2f2;
            color: #991b1b;
        }

        .ret-amount {
            font-weight: 800;
            color: var(--danger);
            text-align: right;
        }

        /* Action Pill */
        .btn-view-premium {
            background: white;
            border: 1px solid var(--slate-300);
            color: var(--slate-700);
            padding: 0.4rem 1rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view-premium:hover {
            background: var(--brand);
            color: white;
            border-color: var(--brand);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        /* Continue Return Button */
        .btn-continue-return {
            background: #fefce8;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 0.4rem 1rem;
            border-radius: 100px;
            font-size: 0.75rem;
            font-weight: 700;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-continue-return:hover {
            background: #f59e0b;
            color: white;
            border-color: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.35);
        }

        .action-cell {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            align-items: center;
        }

        /* DataTables Override */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 100px;
            padding: 0.6rem 1.5rem;
            background: var(--slate-50);
            border: 1px solid var(--slate-200);
            width: 280px;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Header -->
            <div class="return-header">
                <div class="header-info">
                    <h4>Purchase Return Note (PRN)</h4>
                    <p>Track reversed procurement transactions and partial returns.</p>
                </div>
                <div>
                    @can('purchases.create')
                        <a href="{{ route('purchase.return.show') }}" class="btn btn-light px-4 fw-800 rounded-pill">
                            <i class="fas fa-undo-alt me-2 text-primary"></i> CREATE RETURN NOTE
                        </a>
                    @endcan
                </div>
            </div>

            @php
                $totalReturns = $returns->count();
                $totalReturnAmt = $returns->sum('net_amount');
                $fullReturns = $returns
                    ->filter(fn($r) => $r->purchase && $r->purchase->status_purchase === 'Returned')
                    ->count();
                $partialReturns = $returns
                    ->filter(fn($r) => $r->purchase && $r->purchase->status_purchase === 'Partial Return')
                    ->count();
            @endphp
            <div class="kpi-wrapper">
                <div class="kpi-mini-card">
                    <div class="kpi-icon-circle bg-purple"><i class="fas fa-exchange-alt"></i></div>
                    <div class="kpi-data">
                        <h3>{{ $totalReturns }}</h3>
                        <span>Total Records</span>
                    </div>
                </div>
                <div class="kpi-mini-card">
                    <div class="kpi-icon-circle bg-rose"><i class="fas fa-coins"></i></div>
                    <div class="kpi-data">
                        <h3>Rs. {{ number_format($totalReturnAmt, 0) }}</h3>
                        <span>Value Reversed</span>
                    </div>
                </div>
                <div class="kpi-mini-card">
                    <div class="kpi-icon-circle" style="background:#fff1f2;color:#dc2626;"><i
                            class="fas fa-check-double"></i></div>
                    <div class="kpi-data">
                        <h3>{{ $fullReturns }}</h3>
                        <span>Full Returns</span>
                    </div>
                </div>
                <div class="kpi-mini-card">
                    <div class="kpi-icon-circle" style="background:#fef9c3;color:#b45309;"><i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="kpi-data">
                        <h3>{{ $partialReturns }}</h3>
                        <span>Partial Returns</span>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-glass-container">
                <div class="table-responsive p-3">
                    <table id="return-table" class="table align-middle">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Invoice Details</th>
                                <th>Status</th>
                                <th>Vendor Entity</th>
                                <th>Batch / Lot Summary</th>
                                <th>Return Date</th>
                                <th class="text-end">Returned Amt</th>
                                <th class="text-end">Balance New</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($returns as $return)
                                <tr>
                                    <td class="fw-bold text-muted">#{{ $return->id }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="invoice-link mb-1">{{ $return->return_invoice }}</span>
                                            @if ($return->purchase)
                                                <div class="d-flex align-items-center gap-1 mt-1">
                                                    <i class="fas fa-file-alt text-muted" style="font-size: 0.65rem;"></i>
                                                    <small
                                                        class="text-muted fw-600">{{ $return->purchase->invoice_no }}</small>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($return->purchase)
                                            @if ($return->purchase->status_purchase === 'Returned')
                                                <span class="badge"
                                                    style="background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;border-radius:100px;font-size:0.72rem;padding:0.35rem 0.75rem;">
                                                    <i class="fas fa-check-circle me-1"></i>Full Return
                                                </span>
                                            @elseif($return->purchase->status_purchase === 'Partial Return')
                                                <span class="badge"
                                                    style="background:#fefce8;color:#92400e;border:1px solid #fde68a;border-radius:100px;font-size:0.72rem;padding:0.35rem 0.75rem;">
                                                    <i class="fas fa-chart-pie me-1"></i>Partial Return
                                                </span>
                                            @else
                                                <span class="badge"
                                                    style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:100px;font-size:0.72rem;padding:0.35rem 0.75rem;">
                                                    <i class="fas fa-boxes me-1"></i>GRN Active
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="vendor-profile">
                                            <div class="vendor-initial">
                                                {{ strtoupper(substr($return->vendor->name ?? 'V', 0, 1)) }}</div>
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="fw-bold text-slate-900">{{ $return->vendor->name ?? '—' }}</span>
                                                <small
                                                    class="text-muted fw-500">{{ $return->vendor->business_name ?? 'Business Unit' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="batch-summary-box">
                                            @if ($return->batch_summary != '-')
                                                <span class="summary-tag tag-lot">LOT: {{ $return->batch_summary }}</span>
                                            @endif
                                            @if ($return->mfg_summary != '-')
                                                <span class="summary-tag tag-mfg">MFG: {{ $return->mfg_summary }}</span>
                                            @endif
                                            @if ($return->exp_summary != '-')
                                                <span class="summary-tag tag-exp">EXP: {{ $return->exp_summary }}</span>
                                            @endif

                                            @if($return->batch_summary == '-' && $return->mfg_summary == '-' && $return->exp_summary == '-')
                                                <span class="text-muted small">---</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="fw-700 text-slate-900">{{ \Carbon\Carbon::parse($return->return_date)->format('d M, Y') }}</span>
                                    </td>
                                    <td class="ret-amount">
                                         @if($return->net_amount > 0)
                                             -{{ number_format($return->net_amount, 2) }}
                                         @else
                                             {{ number_format(0, 2) }}
                                         @endif
                                     </td>
                                    <td class="text-end">
                                        @if ($return->purchase)
                                            <span
                                                class="fw-800 text-slate-900">{{ number_format($return->new_net_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="action-cell">
                                            <a href="{{ route('purchase.return.view', $return->id) }}"
                                                class="btn-view-premium">
                                                <i class="fas fa-eye"></i> View Detail
                                            </a>
                                            @if ($return->purchase && $return->purchase->status_purchase === 'Partial Return')
                                                @can('purchases.create')
                                                    <a href="{{ route('purchase.return.show', $return->purchase->id) }}"
                                                        class="btn-continue-return">
                                                        <i class="fas fa-redo-alt"></i> Continue Return
                                                    </a>
                                                @endcan
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
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $('#return-table').DataTable({
                "pageLength": 10,
                "aaSorting": [
                    [0, 'desc']
                ],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search returns, vendors, batches..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });
        });
    </script>
@endsection
