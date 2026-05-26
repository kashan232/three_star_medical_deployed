@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --background: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--background);
            color: var(--text-main);
        }

        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 24px;
        }

        .ledger-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .header-info {
            background: #fff;
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }

        .movement-table thead th {
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
        }

        .movement-table tbody td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
        }

        .badge-in { background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
        .badge-out { background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 600; }
        
        .running-balance {
            font-family: 'Inter', monospace;
            font-weight: 600;
            color: var(--primary);
        }

        .meta-label { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .meta-value { font-weight: 500; font-size: 0.95rem; }

        @media print {
            .btn, .sidebar, .navbar { display: none !important; }
            .page-container { padding: 0; max-width: 100%; }
        }
    </style>

    <div class="page-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url()->previous() }}" class="btn btn-light border rounded-circle p-0" style="width:40px;height:40px;display:grid;place-items:center;">
                    <i class="las la-arrow-left"></i>
                </a>
                <div>
                    <h4 class="fw-bold mb-0">Batch Movement Ledger</h4>
                    <p class="text-muted small mb-0">Detailed audit trail for specific stock batch</p>
                </div>
            </div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="las la-print me-1"></i> Print Ledger
            </button>
        </div>

        <div class="ledger-card mb-4">
            <div class="header-info">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="meta-label">Product</div>
                        <div class="meta-value">{{ $batch->product->item_name }}</div>
                        <div class="text-muted small">{{ $batch->product->item_code }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="meta-label">Batch No</div>
                        <div class="meta-value"><code>{{ $batch->batch_number }}</code></div>
                    </div>
                    <div class="col-md-3">
                        <div class="meta-label">Warehouse</div>
                        <div class="meta-value">{{ $batch->warehouse->warehouse_name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="meta-label">Current Balance</div>
                        <div class="meta-value fs-4 fw-bold text-primary">{{ number_format($batch->qty_remaining) }} Pcs</div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table movement-table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Reference</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Balance</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $running = 0; @endphp
                        @foreach($movements as $mv)
                            @php 
                                if($mv['type'] == 'IN') $running += $mv['qty'];
                                else $running -= $mv['qty'];
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ \Carbon\Carbon::parse($mv['date'])->format('d M Y, h:i A') }}</td>
                                <td>
                                    <span class="badge-{{ strtolower($mv['type']) }}">
                                        {{ $mv['type'] }}
                                    </span>
                                </td>
                                <td class="fw-500">{{ $mv['ref'] }}</td>
                                <td class="text-end fw-bold {{ $mv['type'] == 'IN' ? 'text-success' : 'text-danger' }}">
                                    {{ $mv['type'] == 'IN' ? '+' : '-' }}{{ number_format($mv['qty']) }}
                                </td>
                                <td class="text-end running-balance">
                                    {{ number_format($running) }}
                                </td>
                                <td class="small text-muted">{{ $mv['note'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
