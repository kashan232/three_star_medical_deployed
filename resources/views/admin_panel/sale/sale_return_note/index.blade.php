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
                    <h4>Sale Return Note</h4>
                    <p>Track reversed sales transactions and product returns.</p>
                </div>
                <div>
                    @can('sales.create')
                        <a href="{{ route('sale.return.create_blank') }}" class="btn btn-light px-4 fw-800 rounded-pill">
                            <i class="fas fa-undo-alt me-2 text-primary"></i> CREATE RETURN NOTE
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Stats -->
            @php
                $totalReturns = $returns->count();
                $totalReturnAmt = $returns->sum('net_amount');
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
                    <div class="kpi-icon-circle bg-purple"><i class="fas fa-user-tag"></i></div>
                    <div class="kpi-data">
                        <h3>{{ $returns->unique('customer_id')->count() }}</h3>
                        <span>Active Customers</span>
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
                                <th>Customer Entity</th>
                                <th>Product Details</th>
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
                                            @if ($return->sale)
                                                <small class="text-muted fw-600">Orig:
                                                    {{ $return->sale->invoice_no }}</small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vendor-profile">
                                            <div class="vendor-initial">
                                                {{ strtoupper(substr($return->customer->customer_name ?? 'C', 0, 1)) }}
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="fw-bold text-slate-900">{{ $return->customer->customer_name ?? '—' }}</span>
                                                <small
                                                    class="text-muted fw-500">{{ $return->customer->business_name ?? 'Business Unit' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="batch-summary-box">
                                            @foreach ($return->items as $item)
                                                <span class="summary-tag tag-lot">{{ $item->product->item_name ?? 'Item' }}
                                                    ({{ $item->qty }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="fw-700 text-slate-900">{{ \Carbon\Carbon::parse($return->return_date)->format('d M, Y') }}</span>
                                    </td>
                                    <td class="ret-amount">
                                        -{{ number_format($return->net_amount, 2) }}
                                    </td>
                                    <td class="text-end">
                                        @if ($return->sale)
                                            <span
                                                class="fw-800 text-slate-900">{{ number_format($return->new_net_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('sale.return.view', $return->id) }}" class="btn-view-premium">
                                            <i class="fas fa-eye"></i> View Detail
                                        </a>
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
