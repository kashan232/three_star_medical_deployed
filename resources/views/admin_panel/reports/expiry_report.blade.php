@extends('admin_panel.layout.app')

@section('content')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://maxst.icons8.com/vue-static/landings/line-awesome/line-awesome/1.3.0/css/line-awesome.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #0ea5e9;
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        /* Stats Cards */
        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0,0,0,0.08);
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
        }
        .stat-expired::after { background: var(--danger); }
        .stat-critical::after { background: var(--warning); }
        .stat-warning::after { background: var(--info); }
        .stat-ok::after { background: var(--success); }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .bg-danger-soft { background: #fef2f2; color: var(--danger); }
        .bg-warning-soft { background: #fffbeb; color: var(--warning); }
        .bg-info-soft { background: #f0f9ff; color: var(--info); }
        .bg-success-soft { background: #f0fdf4; color: var(--success); }

        /* Filter Section */
        .filter-section {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            padding: 20px;
            margin-bottom: 24px;
        }

        /* Table Design */
        .report-card {
            background: var(--card-bg);
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .custom-table {
            margin-bottom: 0;
        }
        .custom-table thead th {
            background: #f8fafc;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.025em;
            color: var(--text-muted);
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }
        .custom-table tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .custom-table tbody tr:last-child td {
            border-bottom: none;
        }
        .custom-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Badges & Pills */
        .badge-modern {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.75rem;
        }
        .badge-modern-danger { background: #fee2e2; color: #991b1b; }
        .badge-modern-warning { background: #fef3c7; color: #92400e; }
        .badge-modern-info { background: #e0f2fe; color: #075985; }
        .badge-modern-success { background: #dcfce7; color: #166534; }

        .qty-badge {
            background: #f1f5f9;
            color: #475569;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: 'Inter', monospace;
            font-size: 0.85rem;
        }

        .search-input-group {
            position: relative;
        }
        .search-input-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }
        .search-input-group .form-control {
            padding-left: 36px;
            border-radius: 10px;
        }

        /* Expiry Status Rows */
        tr.status-expired { background-color: rgba(239, 68, 68, 0.02); }
        tr.status-critical { background-color: rgba(245, 158, 11, 0.02); }

        .product-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .btn-modern {
            border-radius: 10px;
            font-weight: 500;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        .btn-primary-modern {
            background: var(--primary);
            border: none;
            color: white;
        }
        .btn-primary-modern:hover {
            background: #4338ca;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        @media print {
            .btn-group, .filter-section, .stat-card { display: none !important; }
            .page-container { padding: 0; max-width: 100%; }
            .report-card { border: none; box-shadow: none; }
        }
    </style>

    <div class="page-container">
        
        {{-- Header Section --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Stock Expiry Report</h3>
                <p class="text-muted mb-0">Manage and track batches reaching their expiration thresholds.</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-light border btn-modern">
                    <i class="las la-print me-1"></i> Print
                </button>
                <button onclick="exportTableToExcel('expiryTable', 'Expiry_Report')" class="btn btn-light border btn-modern">
                    <i class="las la-file-excel me-1"></i> Export
                </button>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card stat-expired">
                    <div class="stat-icon bg-danger-soft">
                        <i class="las la-calendar-times"></i>
                    </div>
                    <div class="text-muted small mb-1">Already Expired</div>
                    <h3 class="fw-bold mb-0">{{ $summary['expired'] }}</h3>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-critical">
                    <div class="stat-icon bg-warning-soft">
                        <i class="las la-exclamation-triangle"></i>
                    </div>
                    <div class="text-muted small mb-1">Critical (&lt; 90d)</div>
                    <h3 class="fw-bold mb-0">{{ $summary['critical'] }}</h3>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-warning">
                    <div class="stat-icon bg-info-soft">
                        <i class="las la-bell"></i>
                    </div>
                    <div class="text-muted small mb-1">Warning (&lt; 180d)</div>
                    <h3 class="fw-bold mb-0">{{ $summary['warning'] }}</h3>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card stat-ok">
                    <div class="stat-icon bg-success-soft">
                        <i class="las la-check-circle"></i>
                    </div>
                    <div class="text-muted small mb-1">Healthy Batches</div>
                    <h3 class="fw-bold mb-0">{{ $summary['ok'] }}</h3>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="filter-section">
            <form action="{{ route('reports.expiry') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label small fw-600">Timeframe</label>
                        <select name="days" class="form-select border-0 bg-light">
                            <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 Days</option>
                            <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 Days</option>
                            <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 Days</option>
                            <option value="180" {{ $days == 180 ? 'selected' : '' }}>180 Days</option>
                            <option value="365" {{ $days == 365 ? 'selected' : '' }}>1 Year</option>
                            <option value="all" {{ $days == 'all' ? 'selected' : '' }}>All Future</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-600">Category</label>
                        <select name="category_id" class="form-select border-0 bg-light">
                            <option value="all">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $catId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-600">Brand</label>
                        <select name="brand_id" class="form-select border-0 bg-light">
                            <option value="all">All Brands</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}" {{ $brandId == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-600">Warehouse</label>
                        <select name="warehouse_id" class="form-select border-0 bg-light">
                            <option value="all">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $whId == $wh->id ? 'selected' : '' }}>{{ $wh->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-600">Status</label>
                        <select name="status" class="form-select border-0 bg-light">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Status</option>
                            <option value="expired" {{ $status == 'expired' ? 'selected' : '' }}>Expired Only</option>
                            <option value="expiring" {{ $status == 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary-modern w-100 btn-modern">
                            <i class="las la-filter me-1"></i> Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Search and Meta --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="search-input-group w-50">
                <i class="las la-search"></i>
                <input type="text" id="batchSearch" class="form-control" placeholder="Search by medicine, batch number or code...">
            </div>
            <div class="text-muted small">
                Showing <strong>{{ $batches->count() }}</strong> batches
            </div>
        </div>

        {{-- Main Table --}}
        <div class="report-card">
            <div class="table-responsive">
                <table class="table custom-table" id="expiryTable">
                    <thead>
                        <tr>
                            <th>Medicine Info</th>
                            <th>Batch / Lot</th>
                            <th>Warehouse</th>
                            <th>Dates</th>
                            <th>Remaining Qty</th>
                            <th>Estimated Value</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            @php 
                                $status_key = $batch->expiry_status;
                                $ppb = $batch->product->pieces_per_box ?? 1;
                                $boxes = floor($batch->qty_remaining / $ppb);
                                $loose = (int)$batch->qty_remaining % $ppb;
                                $value = $batch->qty_remaining * ($batch->product->purchase_price_per_piece ?? 0);
                            @endphp
                            <tr class="status-{{ $status_key }}">
                                <td>
                                    <div class="fw-600">{{ $batch->product->item_name }}</div>
                                    <div class="product-meta">
                                        <span class="text-primary">{{ $batch->product->item_code }}</span> | 
                                        {{ $batch->product->category_relation->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td>
                                    <code class="px-2 py-1 bg-light rounded text-dark">{{ $batch->batch_number }}</code>
                                    <div class="small text-muted mt-1">{{ $batch->product->brand->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="small fw-500">{{ $batch->warehouse->warehouse_name ?? 'N/A' }}</div>
                                    <div class="small text-muted">Branch: {{ $batch->warehouse->branch->name ?? '-' }}</div>
                                </td>
                                <td>
                                    <div class="small">MFG: <span class="text-muted">{{ $batch->mfg_date ? $batch->mfg_date->format('d/m/y') : '-' }}</span></div>
                                    <div class="fw-500">EXP: <span class="{{ $status_key == 'expired' ? 'text-danger' : 'text-dark' }}">{{ $batch->exp_date->format('d M Y') }}</span></div>
                                </td>
                                <td>
                                    <div class="qty-badge">
                                        @if($ppb > 1)
                                            {{ $boxes }} Box + {{ $loose }} Pcs
                                        @else
                                            {{ number_format($batch->qty_remaining) }} Pcs
                                        @endif
                                    </div>
                                    <div class="small text-muted mt-1">{{ number_format($batch->qty_remaining) }} total pieces</div>
                                </td>
                                <td>
                                    <div class="fw-600 text-dark">{{ number_format($value, 2) }}</div>
                                    <div class="small text-muted">@ {{ number_format($batch->product->purchase_price_per_piece ?? 0, 2) }}</div>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($status_key) {
                                            'expired' => 'badge-modern-danger',
                                            'critical' => 'badge-modern-warning',
                                            'warning' => 'badge-modern-info',
                                            default => 'badge-modern-success'
                                        };
                                        $label = match($status_key) {
                                            'expired' => 'EXPIRED',
                                            'critical' => 'CRITICAL',
                                            'warning' => 'EXPIRING',
                                            default => 'HEALTHY'
                                        };
                                    @endphp
                                    <span class="badge-modern {{ $badgeClass }}">
                                        {{ $label }}
                                    </span>
                                    <div class="small mt-1 {{ $batch->days_to_expiry < 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $batch->days_to_expiry < 0 ? abs($batch->days_to_expiry) . ' days ago' : $batch->days_to_expiry . ' days left' }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle" data-toggle="dropdown">
                                            <i class="las la-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right shadow-sm border-0">
                                            <li><a class="dropdown-item" href="{{ route('batches.ledger', $batch->id) }}"><i class="las la-list-ul me-2"></i>View Ledger</a></li>
                                            <li><a class="dropdown-item" href="{{ route('product.batches', $batch->product_id) }}"><i class="las la-history me-2"></i>History</a></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="confirmDiscard({{ $batch->id }}, '{{ $batch->batch_number }}', {{ $batch->qty_remaining }})"><i class="las la-trash-alt me-2"></i>Discard Stock</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="{{ route('products.edit', $batch->product_id) }}"><i class="las la-pen me-2"></i>Edit Product</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="las la-search-minus fs-1 text-muted"></i>
                                    <p class="mt-2 text-muted">No batches found matching your filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Discard Modal --}}
    <div class="modal fade" id="discardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold"><i class="las la-exclamation-triangle me-2"></i>Discard Stock Batch</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <p>Are you sure you want to discard the remaining stock for batch <strong id="discardBatchNo"></strong>?</p>
                    <div class="alert alert-warning border-0 small">
                        This will set the batch quantity to <strong>0</strong> and update the warehouse stock accordingly. This action cannot be undone.
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Reason for Discard</label>
                        <input type="text" id="discardReason" class="form-control" placeholder="e.g. Expired Disposal, Damaged Stock...">
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="button" id="confirmDiscardBtn" class="btn btn-danger px-4">Confirm Discard</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple client-side search
        document.getElementById('batchSearch').addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#expiryTable tbody tr');
            
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });

        let selectedBatchId = null;

        function confirmDiscard(id, batchNo, qty) {
            selectedBatchId = id;
            document.getElementById('discardBatchNo').textContent = batchNo;
            document.getElementById('discardReason').value = '';
            $('#discardModal').modal('show');
        }

        document.getElementById('confirmDiscardBtn').addEventListener('click', function() {
            if (!selectedBatchId) return;
            const reason = document.getElementById('discardReason').value;
            const btn = this;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

            fetch(`/batches/${selectedBatchId}/discard`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ note: reason })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error discarding batch');
                    btn.disabled = false;
                    btn.innerHTML = 'Confirm Discard';
                }
            })
            .catch(err => {
                console.error(err);
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = 'Confirm Discard';
            });
        });

        function exportTableToExcel(tableID, filename = ''){
            var downloadLink;
            var dataType = 'application/vnd.ms-excel';
            var tableSelect = document.getElementById(tableID);
            var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            
            filename = filename?filename+'.xls':'excel_data.xls';
            downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            
            if(navigator.msSaveOrOpenBlob){
                var blob = new Blob(['\ufeff', tableHTML], {
                    type: dataType
                });
                navigator.msSaveOrOpenBlob( blob, filename);
            }else{
                downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
                downloadLink.download = filename;
                downloadLink.click();
            }
        }
    </script>
@endsection
