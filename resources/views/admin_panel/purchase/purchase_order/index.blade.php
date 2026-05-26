@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --white: #ffffff;
            --bg-light: #f8fafc;
            --glass: rgba(255, 255, 255, 0.9);
        }

        body {
            background-color: #f1f5f9;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
        }

        .main-content {
            padding: 1.5rem;
        }

        /* Glassmorphism Header */
        .page-glass-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-glass-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            filter: blur(50px);
            pointer-events: none;
        }

        .header-title h4 {
            color: #fff;
            font-weight: 800;
            font-size: 1.75rem;
            letter-spacing: -0.025em;
            margin-bottom: 0.25rem;
        }

        .header-title p {
            color: #94a3b8;
            font-size: 0.95rem;
            margin: 0;
        }

        /* KPI Cards */
        .kpi-card {
            background: var(--white);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 1rem;
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
            border-color: var(--primary);
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .kpi-icon.blue {
            background: #eff6ff;
            color: #2563eb;
        }

        .kpi-icon.yellow {
            background: #fffbeb;
            color: #d97706;
        }

        .kpi-icon.green {
            background: #ecfdf5;
            color: #059669;
        }

        .kpi-info h3 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0;
        }

        .kpi-info p {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0;
        }

        /* Premium Table */
        .table-card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        #purchase-order-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        #purchase-order-table thead th {
            background-color: #f8fafc;
            padding: 1.25rem 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #f1f5f9;
        }

        #purchase-order-table tbody tr {
            transition: all 0.2s;
        }

        #purchase-order-table tbody tr:hover {
            background-color: #f1f5f9;
        }

        #purchase-order-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.875rem;
            color: #334155;
        }

        /* Custom Badges */
        .order-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .badge-draft {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #ffedd5;
        }

        .badge-invoice {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #dbeafe;
        }

        /* Vendor Branding */
        .vendor-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        /* Detail Tags */
        .detail-tag {
            background: #f1f5f9;
            color: #475569;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
            margin: 2px;
            border: 1px solid #e2e8f0;
        }

        .tag-exp {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .tag-mfg {
            background: #ecfdf5;
            color: #047857;
            border-color: #d1fae5;
        }

        /* Action Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
        }

        .btn-action:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: scale(1.1);
        }

        .btn-action.confirm:hover {
            background: var(--success);
            border-color: var(--success);
        }

        .btn-action.delete:hover {
            background: var(--danger);
            border-color: var(--danger);
        }

        /* DataTables Customization */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 12px;
            padding: 0.6rem 1rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            width: 250px;
        }

        .dataTables_wrapper .dataTables_length select {
            border-radius: 8px;
            padding: 0.4rem;
            border: 1px solid #e2e8f0;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Glass Header -->
            <div class="page-glass-header">
                <div class="header-title">
                    <h4>Purchase Order Registry</h4>
                    <p>Draft procurement requests waiting for verification & approval.</p>
                </div>
                <div>
                    @can('purchases.create')
                        <a class="btn btn-primary btn-lg px-4 shadow-lg fw-bold rounded-pill"
                            href="{{ route('add_purchase', ['mode' => 'po']) }}">
                            <i class="fas fa-plus-circle me-2"></i> NEW PURCHASE ORDER
                        </a>
                    @endcan
                </div>
            </div>

            <!-- KPI Row -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon blue"><i class="fas fa-file-invoice"></i></div>
                        <div class="kpi-info">
                            <h3>{{ count($Purchase) }}</h3>
                            <p>Total Drafts</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon yellow"><i class="fas fa-clock"></i></div>
                        <div class="kpi-info">
                            <h3>{{ count($Purchase->where('status_purchase', 'draft')) }}</h3>
                            <p>Pending Approval</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-icon green"><i class="fas fa-coins"></i></div>
                        <div class="kpi-info">
                            <h3>{{ number_format($Purchase->sum('net_amount'), 0) }}</h3>
                            <p>Committed Value</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="table-card">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold mb-0">Active Orders</h5>
                </div>
                <div class="table-responsive p-3">
                    <table id="purchase-order-table" class="table align-middle datanew">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order Ref</th>
                                <th style="min-width: 200px;">Vendor & Business</th>
                                <th>Logistics</th>
                                <th>Summary</th>
                                <th class="text-end">Amount Details</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Purchase as $purchase)
                                <tr>
                                    <td class="fw-bold text-muted" style="width: 50px;">{{ $purchase->id }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span
                                                class="fw-800 text-dark font-monospace mb-1">{{ $purchase->invoice_no }}</span>
                                            <span class="text-muted small"><i
                                                    class="far fa-calendar-alt me-1"></i>{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="vendor-avatar">
                                                {{ strtoupper(substr($purchase->vendor->name ?? 'V', 0, 1)) }}
                                            </div>
                                            <div class="d-flex flex-column">
                                                <span
                                                    class="fw-bold text-dark">{{ $purchase->vendor->name ?? 'N/A' }}</span>
                                                <span
                                                    class="text-muted small fw-600">{{ $purchase->vendor->business_name ?? 'Individual Vendor' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="small fw-bold text-secondary"><i
                                                    class="fas fa-warehouse me-1"></i>{{ $purchase->warehouse->warehouse_name ?? 'Main' }}</span>
                                            <span class="order-badge badge-draft mt-1">PURCHASE ORDER</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <span class="badge bg-light text-primary border px-2 py-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-cubes me-1"></i>{{ $purchase->items->count() }} Items
                                                </span>
                                            </div>
                                            <div class="small fw-600 text-secondary">
                                                <i class="fas fa-boxes me-1 text-muted"></i>Total Qty: {{ (float)$purchase->total_original_pieces }} Pcs
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-column align-items-end">
                                            <div class="small text-muted fw-bold" style="font-size: 0.7rem;">
                                                GROSS: <span class="text-dark">{{ number_format($purchase->gross_total, 2) }}</span>
                                            </div>
                                            <div class="small text-danger fw-bold" style="font-size: 0.7rem;">
                                                DISC: -{{ number_format($purchase->discount_amount, 2) }}
                                            </div>
                                            <div class="small text-success fw-bold mb-1" style="font-size: 0.7rem;">
                                                TAX: +{{ number_format($purchase->total_gst, 2) }}
                                            </div>
                                            <div class="fw-800 text-primary fs-6 mb-0">{{ number_format($purchase->net_amount, 2) }}</div>
                                            <div class="text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">PKR TOTAL</div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">

                                            @can('purchases.edit')
                                                <a href="{{ route('purchase.edit', $purchase->id) }}" class="btn-action"
                                                    title="Edit">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            @endcan
                                            @can('purchases.delete')
                                                <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="button" class="btn-action delete delete-btn" title="Delete">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            @endcan
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search and filter orders...",
                    "lengthMenu": "_MENU_ per page"
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            $(document).on('click', '.confirm-purchase-btn', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                Swal.fire({
                    title: "Confirm Purchase?",
                    text: "Validate this order and generate Goods Receipt Note.",
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#10b981",
                    confirmButtonText: "Confirm & Generate GRN",
                    borderRadius: '16px'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.get(url, function(res) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: res.message,
                                confirmButtonColor: "#4f46e5"
                            }).then(() => window.location.reload());
                        });
                    }
                });
            });

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                let form = $(this).closest("form");
                Swal.fire({
                    title: "Delete Order?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#ef4444",
                    confirmButtonText: "Yes, Delete",
                    borderRadius: '16px'
                }).then((result) => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endsection
