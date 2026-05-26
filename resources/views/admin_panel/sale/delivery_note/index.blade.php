@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --white: #ffffff;
        }

        body {
            background: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .main-content {
            padding: 1.5rem;
        }

        .page-glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .page-glass-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(79, 70, 229, .12);
            border-radius: 50%;
            filter: blur(50px);
            pointer-events: none;
        }

        .header-title h4 {
            color: #fff;
            font-weight: 800;
            font-size: 1.75rem;
            margin: 0;
        }

        .header-title p {
            color: #94a3b8;
            font-size: .9rem;
            margin: 0;
        }

        .kpi-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all .3s;
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, .06);
            border-color: var(--primary);
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .kpi-icon.blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .kpi-icon.green {
            background: #ecfdf5;
            color: #059669;
        }

        .kpi-icon.orange {
            background: #fff7ed;
            color: #c2410c;
        }

        .kpi-info h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .kpi-info p {
            font-size: .72rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin: 0;
        }

        .table-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, .04);
            overflow: hidden;
        }

        #dc-table thead th {
            background: #f8fafc;
            padding: 1.1rem 1rem;
            font-size: .72rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .05em;
            border-bottom: 2px solid #f1f5f9;
        }

        #dc-table tbody tr {
            transition: all .2s;
        }

        #dc-table tbody tr:hover {
            background: #f8fafc;
        }

        #dc-table td {
            padding: .9rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: .875rem;
            color: #334155;
        }

        .badge-dc {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
            padding: .3rem .7rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .68rem;
        }

        .badge-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
            padding: .3rem .7rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .68rem;
        }

        .badge-delivered {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
            padding: .3rem .7rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: .68rem;
        }

        .customer-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #fff;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .9rem;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all .2s;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
        }

        .btn-action:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            transform: scale(1.1);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            color: #64748b;
            font-weight: 600;
        }

        .empty-state p {
            color: #94a3b8;
            font-size: .875rem;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            {{-- Flash --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Header --}}
            <div class="page-glass-header">
                <div class="header-title">
                    <h4><i class="fas fa-truck me-3"></i>Delivery Note Registry</h4>
                    <p>Track goods dispatched to customers. Sale Orders under active delivery.</p>
                </div>
                <div>
                    @can('sales.create')
                        <a href="{{ route('delivery.note.create') }}"
                            class="btn btn-primary btn-lg px-4 shadow-lg fw-bold rounded-pill">
                            <i class="fas fa-plus-circle me-2"></i>NEW DC NOTE
                        </a>
                    @endcan
                </div>
            </div>

            {{-- KPIs --}}
            <div class="row mb-4 g-3">
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fas fa-truck-loading"></i></div>
                        <div class="kpi-info">
                            <h3>{{ $dcNotes->count() }}</h3>
                            <p>Total DC Notes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon orange"><i class="fas fa-clock"></i></div>
                        <div class="kpi-info">
                             <h3>{{ $dcNotes->filter(fn($d) => $d->sale && $d->sale->sale_status === 'draft')->count() }}
                            </h3>
                            <p>In Delivery</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-coins"></i></div>
                        <div class="kpi-info">
                            <h3>{{ number_format($dcNotes->sum('net_amount'), 0) }}</h3>
                            <p>Total Value (PKR)</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-card">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Active Deliveries</h5>
                </div>
                <div class="table-responsive p-3">
                    @if ($dcNotes->isEmpty())
                        <div class="empty-state">
                            <i class="fas fa-truck"></i>
                            <h5>No Delivery Notes Yet</h5>
                            <p>Create a DC Note to start dispatching products to customers.</p>
                            <a href="{{ route('delivery.note.create') }}" class="btn btn-primary rounded-pill px-4 mt-2">
                                <i class="fas fa-plus me-2"></i>Create DC Note
                            </a>
                        </div>
                    @else
                        <table id="dc-table" class="table align-middle datanew">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>DC No</th>
                                    <th>SO No</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Products</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-center">Sale Status</th>
                                    <th class="text-center">DC Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dcNotes as $dc)
                                    <tr>
                                        <td class="fw-bold text-muted" style="width:50px;">{{ $dc->id }}</td>
                                        <td>
                                            <span class="fw-800 font-monospace text-dark">{{ $dc->dc_no }}</span>
                                            @if ($dc->is_sample)
                                                <span class="badge bg-info text-white ms-1" style="font-size: 0.6rem;">SAMPLE</span>
                                            @endif
                                            <br>
                                            <small class="text-muted"><i
                                                    class="far fa-calendar-alt me-1"></i>{{ \Carbon\Carbon::parse($dc->delivery_date)->format('d M, Y') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge-dc">{{ $dc->sale->invoice_no ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="customer-avatar">
                                                    {{ strtoupper(substr($dc->customer->customer_name ?? 'C', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <span
                                                        class="fw-bold text-dark d-block">{{ $dc->customer->customer_name ?? 'N/A' }}</span>
                                                    <small
                                                        class="text-muted">{{ $dc->customer->business_name ?? '' }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($dc->delivery_date)->format('d M, Y') }}</td>
                                        <td>
                                            @foreach ($dc->items->take(3) as $item)
                                                 <span class="badge bg-light text-dark border me-1" style="font-size:.7rem;">
                                                     {{ $item->product->item_name ?? 'Item' }} ({{ $item->qty }} Pcs)
                                                 </span>
                                            @endforeach
                                            @if ($dc->items->count() > 3)
                                                <span class="text-muted small">+{{ $dc->items->count() - 3 }} more</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span
                                                class="fw-800 text-primary fs-6">{{ number_format($dc->net_amount, 2) }}</span><br>
                                            <small class="text-muted fw-bold">PKR</small>
                                        </td>
                                        <td class="text-center">
                                              @if ($dc->sale && in_array($dc->sale->sale_status, ['draft', 'in_delivery']))
                                                 <span class="badge-pending"><i class="fas fa-spinner me-1"></i>Partially Delivered</span>
                                            @elseif($dc->sale && $dc->sale->sale_status === 'delivered')
                                                <span class="badge-delivered"><i
                                                        class="fas fa-check me-1"></i>Delivered</span>
                                            @else
                                                <span class="badge-dc">{{ $dc->sale->sale_status ?? '' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($dc->status === 'cancelled')
                                                <span class="badge bg-danger text-white">Cancelled</span>
                                            @else
                                                <span class="badge bg-success text-white">Completed</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                 <a href="{{ route('delivery.note.print', $dc->id) }}" class="btn-action" title="Print Delivery Challan" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                @if($dc->status !== 'cancelled')
                                                    <a href="{{ route('delivery.note.edit', $dc->id) }}" class="btn-action text-primary" title="Edit DC">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('delivery.note.cancel', $dc->id) }}" method="POST" id="cancel-form-{{ $dc->id }}" style="display:inline;">
                                                        @csrf
                                                        <button type="button" class="btn-action text-danger" title="Cancel DC" onclick="confirmCancel({{ $dc->id }})">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                                 @if ($dc->sale && in_array($dc->sale->sale_status, ['draft', 'in_delivery']) && $dc->status !== 'cancelled')
                                                    <a href="{{ route('delivery.note.create', ['sale_id' => $dc->sale->id]) }}"
                                                        class="btn btn-sm btn-primary" title="Continue Delivery">
                                                        <i class="fas fa-arrow-right"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            if ($('#dc-table tbody tr').length > 0) {
                $('#dc-table').DataTable({
                    pageLength: 15,
                    aaSorting: [],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search...',
                        lengthMenu: '_MENU_ per page'
                    },
                    dom: "<'row mb-3'<'col-sm-6'l><'col-sm-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row mt-3'<'col-sm-5'i><'col-sm-7'p>>",
                });
            }
        });

        function confirmCancel(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This DC will be cancelled and stock will be returned to the warehouse/batches!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep it'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel-form-' + id).submit();
                }
            });
        }
    </script>
@endsection
