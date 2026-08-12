@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #059669;
            /* Emerald 600 */
            --primary-dark: #047857;
            --primary-light: #ecfdf5;
            --secondary: #64748b;
            --accent: #10b981;
            --white: #ffffff;
            --slate-50: #f8fafc;
            --slate-100: #f1f5f9;
            --slate-200: #e2e8f0;
            --slate-800: #1e293b;
            --slate-900: #0f172a;
        }

        body {
            background-color: #f4f7fa;
            font-family: 'Outfit', sans-serif;
            color: var(--slate-800);
        }

        .main-content {
            padding: 1.5rem;
        }

        /* Hero Header Section */
        .hero-header {
            background: linear-gradient(135deg, #065f46 0%, #064e3b 100%);
            border-radius: 24px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero-text h4 {
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
            color: white;
        }

        .hero-text p {
            font-size: 1rem;
            opacity: 0.8;
            margin: 0;
            font-weight: 400;
        }

        /* KPI Dashboard */
        .stat-card {
            background: var(--white);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .stat-icon.emerald {
            background: #d1fae5;
            color: #059669;
        }

        .stat-icon.amber {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-icon.rose {
            background: #ffe4e6;
            color: #e11d48;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--slate-900);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary);
            font-weight: 600;
            margin-top: 0.25rem;
        }

        /* Verified Table Styling */
        .premium-table-container {
            background: var(--white);
            border-radius: 24px;
            border: 1px solid var(--slate-200);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
        }

        #grn-table {
            width: 100% !important;
        }

        #grn-table thead th {
            padding: 1.25rem 1rem;
            font-weight: 700;
            color: var(--slate-800);
            background: var(--slate-50);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid var(--slate-200);
        }

        #grn-table tbody tr {
            border-bottom: 1px solid var(--slate-100);
            transition: background 0.2s;
        }

        #grn-table tbody tr:hover {
            background: var(--slate-50);
        }

        #grn-table td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Branding Elements */
        .grn-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.8rem;
            background: var(--primary-light);
            color: var(--primary-dark);
            border-radius: 100px;
            font-weight: 700;
            font-size: 0.75rem;
            border: 1px solid #a7f3d0;
        }

        .vendor-box {
            display: flex;
            flex-direction: column;
        }

        .vendor-name {
            font-weight: 700;
            color: var(--slate-900);
        }

        .business-tag {
            font-size: 0.75rem;
            color: var(--secondary);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Batch Information Grid */
        .batch-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
            max-width: 200px;
        }

        .batch-badge {
            font-size: 0.7rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lot-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .mfg-badge {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
        }

        .exp-badge {
            background: #fff1f2;
            color: #be123c;
            border: 1px solid #fecdd3;
        }

        /* Action Menus */
        .btn-dropdown {
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .btn-dropdown:hover {
            background: var(--slate-200);
        }

        .dropdown-menu {
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--slate-200);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 0.6rem 1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-item:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
        }

        /* Financials Column */
        .amt-verified {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 800;
            color: var(--primary-dark);
            text-align: right;
        }

        .amt-status {
            font-size: 0.7rem;
            text-align: right;
            padding-top: 2px;
        }

        /* DataTables Premium Look */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 14px;
            padding: 0.75rem 1.25rem;
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            width: 300px;
            transition: all 0.2s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            background-color: white;
        }
    </style>

    <div class="main-content">
        <div class="container-fluid">

            <!-- Hero Header -->
            <div class="hero-header">
                <div class="hero-text">
                    <h4>Goods Receipt Notes</h4>
                    <p>Verified invoices and finalized inventory entries for Three Star Medical.</p>
                </div>
                <div>
                    @can('purchases.create')
                        <a class="btn btn-light btn-lg px-5 shadow-lg fw-800 rounded-pill text-emerald" style="color: #065f46"
                            href="{{ route('add_purchase', ['mode' => 'grn']) }}">
                            <i class="fas fa-file-signature me-2"></i> CREATE NEW GRN
                        </a>
                    @endcan
                </div>
            </div>

            <!-- Dashboard Stats -->
            <div class="row mb-5">
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-check-double"></i></div>
                        <div class="stat-value">{{ number_format($Purchase->count()) }}</div>
                        <div class="stat-label">Verified GRNs</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon amber"><i class="fas fa-money-check-alt"></i></div>
                        <div class="stat-value">Rs. {{ number_format($Purchase->sum('net_amount'), 0) }}</div>
                        <div class="stat-label">Total Inventory Value</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4 mb-xl-0">
                    <div class="stat-card">
                        <div class="stat-icon rose"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-value">Rs. {{ number_format($Purchase->sum('due_amount'), 0) }}</div>
                        <div class="stat-label">Pending Liabilities</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon emerald"><i class="fas fa-box-open"></i></div>
                        <div class="stat-value">Stocked</div>
                        <div class="stat-label">System Updated</div>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <div class="premium-table-container">
                <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                    <h5 class="fw-800 m-0 text-slate-800"><i class="fas fa-stream me-2 text-primary"></i>Verified Registry
                    </h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                                class="fas fa-download me-2"></i>Excel</button>
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                                class="fas fa-print me-2"></i>Print Registry</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="grn-table" class="table align-middle datanew">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Verification Date</th>
                                <th>GRN Number</th>
                                <th>Vendor / Company Entity</th>
                                <th class="text-center">Total Qty</th>
                                <th>Batch Information</th>
                                <th class="text-end">Billing Detail</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($Purchase as $purchase)
                                <tr>
                                    <td class="fw-bold text-slate-400">#{{ $purchase->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="text-emerald" style="font-size: 1.1rem;"><i
                                                    class="fas fa-calendar-check"></i></div>
                                            <span
                                                class="fw-600">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="grn-pill" style="width: fit-content;">{{ $purchase->invoice_no }}</span>
                                            

                                            @if($purchase->po_ref)
                                                <div class="small text-muted mt-1" style="font-size: 0.7rem;">PO: {{ $purchase->po_ref }}</div>
                                            @endif
                                            @if($purchase->status_purchase == 'un-post')
                                                <span class="badge badge-danger" style="font-size: 0.6rem; width: fit-content;">UNPOSTED</span>
                                            @endif
                                            @if($purchase->createdBy)
                                                <div class="mt-1">
                                                    <span class="badge badge-light text-muted fw-bold" style="font-size: 0.65rem; border: 1px dashed var(--slate-200); padding: 2px 6px; border-radius: 4px;">
                                                        <i class="fas fa-user me-1"></i>{{ $purchase->createdBy->name }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="vendor-box">
                                            <span
                                                class="vendor-name">{{ $purchase->vendor->name ?? 'System Vendor' }}</span>
                                            <span class="business-tag"><i
                                                    class="fas fa-building me-1"></i>{{ $purchase->vendor->business_name ?? 'Health Institution' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-800 text-slate-800 fs-6">{{ number_format($purchase->total_original_pieces, 0) }}</div>
                                        <div class="text-muted fw-600" style="font-size: 0.65rem;">Total Pcs Received</div>
                                    </td>
                                    <td>
                                        <div class="batch-grid">
                                            @if ($purchase->batch_summary != '-')
                                                <div class="batch-badge lot-badge">LOT: {{ $purchase->batch_summary }}</div>
                                            @endif
                                            @if ($purchase->mfg_summary != '-')
                                                <div class="batch-badge mfg-badge">MFG: {{ $purchase->mfg_summary }}</div>
                                            @endif
                                            @if ($purchase->exp_summary != '-')
                                                <div class="batch-badge exp-badge">EXP: {{ $purchase->exp_summary }}</div>
                                            @endif
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
                                            <div class="amt-verified fs-6 mb-0">
                                                {{ number_format($purchase->net_amount, 2) }}
                                            </div>
                                            <div class="amt-status">
                                                @if ($purchase->due_amount > 0)
                                                    <span class="text-danger fw-700">Due:
                                                        {{ number_format($purchase->due_amount, 2) }}</span>
                                                @else
                                                    <span class="text-success fw-800"><i
                                                            class="fas fa-check-circle me-1"></i>Fully Paid</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-dropdown" type="button" data-toggle="dropdown"
                                                aria-expanded="false">
                                                <i class="fas fa-ellipsis-h me-1"></i> Manage
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right shadow-lg">
                                                @can('purchases.view')
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('purchase.invoice', $purchase->id) }}">
                                                            <i class="fas fa-file-invoice text-emerald"></i> View Invoice</a>
                                                    </li>
                                                    <li><a class="dropdown-item"
                                                            href="{{ route('purchase.grn_report', $purchase->id) }}" target="_blank">
                                                            <i class="fas fa-receipt text-amber"></i> Stock Receipt</a></li>
                                                @endcan
                                                @if (!$purchase->is_fully_returned && $purchase->status_purchase == 'post')
                                                    @can('purchases.create')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-danger"
                                                                href="{{ route('purchase.return.show', $purchase->id) }}">
                                                                <i class="fas fa-undo-alt"></i> Process Return</a></li>
                                                    @endcan
                                                @endif
                                                @if($purchase->status_purchase == 'post')
                                                    @can('purchases.unpost')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-warning btn-unpost"
                                                                href="javascript:void(0);" 
                                                                data-id="{{ $purchase->id }}"
                                                                data-invoice="{{ $purchase->invoice_no }}">
                                                                <i class="fas fa-history"></i> Un-post GRN</a></li>
                                                    @endcan
                                                @else
                                                    @can('purchases.edit')
                                                        <div class="dropdown-divider"></div>
                                                        <li><a class="dropdown-item text-info"
                                                                href="{{ route('purchase.edit', $purchase->id) }}">
                                                                <i class="fas fa-edit"></i> Edit Draft GRN</a></li>
                                                    @endcan
                                                @endif
                                            </ul>
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
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search GRNs, Vendors or Batches..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            $(document).on('click', '.btn-unpost', function() {
                let id = $(this).data('id');
                let invoice = $(this).data('invoice');
                
                Swal.fire({
                    title: 'Un-post GRN?',
                    text: "You are about to revert GRN " + invoice + " to UNPOSTED status. Accounting entries and batches will be PERMANENTLY DELETED. Continue?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, Un-post Now',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return $.ajax({
                            url: '/purchase/' + id + '/unpost',
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
