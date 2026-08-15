@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --c-primary: #0f172a;
            --c-accent: #3b82f6;
            --c-success: #10b981;
            --c-warning: #f59e0b;
            --c-danger: #ef4444;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .global-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            color: #fff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .global-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .global-header h2 {
            font-weight: 800;
            letter-spacing: -1px;
            margin: 0;
            font-size: 2.2rem;
        }

        .global-header p {
            opacity: 0.7;
            font-size: 1rem;
            margin-top: 5px;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--card-shadow);
            border: 1px solid #f1f5f9;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: block;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            display: block;
        }

        .stat-card.accent { border-top: 4px solid var(--c-accent); }
        .stat-card.success { border-top: 4px solid var(--c-success); }
        .stat-card.warning { border-top: 4px solid var(--c-warning); }

        /* Filter Row Styles */
        .filter-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }

        .filter-inputs-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .filter-group label {
            font-size: .62rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .75px;
        }

        .filter-group select,
        .filter-group input {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: .8rem;
            color: #1e293b;
            outline: none;
            background: #f8fafc;
            height: 32px;
            min-height: 32px;
            box-sizing: border-box;
            transition: border-color 0.2s, background-color 0.2s;
            width: 100%;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #0ea5e9;
            background: #fff;
        }

        .filter-buttons-col {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-left: 20px;
            justify-content: center;
            align-self: flex-end;
        }

        .btn-filter-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 6px;
            font-size: .8rem;
            font-weight: 600;
            cursor: pointer;
            padding: 6px 12px;
            min-width: 100px;
            height: 32px;
            border: none;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-filter-action:active {
            transform: scale(0.98);
        }

        .btn-filter-search {
            background: #0ea5e9;
            color: #fff;
        }

        .btn-filter-search:hover {
            background: #0284c7;
        }

        .btn-filter-reset {
            background: #94a3b8;
            color: #fff;
        }

        .btn-filter-reset:hover {
            background: #64748b;
        }

        /* Select2 Theme Overrides */
        .select2-container--default .select2-selection--single {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            height: 32px !important;
            background-color: #f8fafc !important;
            display: flex;
            align-items: center;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: .8rem !important;
            padding-left: 8px !important;
            padding-right: 20px !important;
            line-height: 30px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px !important;
            right: 6px !important;
        }
        .select2-container--default .select2-selection--single:focus,
        .select2-container--open .select2-selection--single {
            border-color: #0ea5e9 !important;
            background-color: #fff !important;
        }

        .table-container {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .global-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .global-table th {
            background: #f8fafc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #f1f5f9;
        }

        .global-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .global-table tr:last-child td { border-bottom: none; }
        .global-table tr:hover { background: #f8fafc; }

        .val-pill {
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .val-pur { background: #eff6ff; color: #1d4ed8; }
        .val-sal { background: #ecfdf5; color: #059669; }
        .val-stk { background: #fff7ed; color: #ea580c; }

        .loader-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1000;
            display: none;
            place-items: center;
        }

        .pulse-loader {
            width: 80px;
            height: 80px;
            background: var(--c-accent);
            border-radius: 50%;
            animation: pulse 1.5s infinite ease-in-out;
        }

        @keyframes pulse {
            0% { transform: scale(0.8); opacity: 0.5; }
            50% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0.8); opacity: 0.5; }
        }
    </style>

    <div class="main-content">
        <div class="global-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Global Summary Report</h2>
                    <p>Comprehensive overview of Inventory, Sales, and Purchases</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-light rounded-pill px-4 fw-bold" onclick="window.print()"><i class="fas fa-print me-2"></i>Print Report</button>
                </div>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat-card accent">
                <span class="stat-label">Stock Value</span>
                <span class="stat-value" id="kpiStockVal">PKR 0.00</span>
            </div>
            <div class="stat-card success">
                <span class="stat-label">Sales (Period)</span>
                <span class="stat-value" id="kpiSalesVal">PKR 0.00</span>
            </div>
            <div class="stat-card warning">
                <span class="stat-label">Purchases (Period)</span>
                <span class="stat-value" id="kpiPurchVal">PKR 0.00</span>
            </div>
            <div class="stat-card accent">
                <span class="stat-label">Closing Balance</span>
                <span class="stat-value" id="kpiClosing"><i class="fas fa-cubes me-2"></i>0</span>
            </div>
        </div>

        <form id="filterForm" class="filter-card">
            <div class="filter-inputs-container">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 130px;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 130px;">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="filter-group" style="flex: 1.2; min-width: 160px;">
                        <label>Warehouse</label>
                        <select name="warehouse_id" id="warehouse_id" class="select2">
                            <option value="all">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1.2; min-width: 160px;">
                        <label>Company (Brand)</label>
                        <select name="brand_id" id="brand_id" class="select2">
                            <option value="all">All Companies</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Category</label>
                        <select name="category_id" id="category_id" class="select2">
                            <option value="all">All Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 150px;">
                        <label>Sub-Category</label>
                        <select name="sub_category_id" id="sub_category_id" class="select2">
                            <option value="all">All Sub-Categories</option>
                            @foreach($subCategories as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 2; min-width: 250px;">
                        <label>Product</label>
                        <select name="product_id" id="product_id" class="select2">
                            <option value="all">All Products</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->item_code }} - {{ $p->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="filter-buttons-col">
                <button type="submit" class="btn-filter-action btn-filter-search">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Search
                </button>
                <button type="button" id="btnReset" class="btn-filter-action btn-filter-reset">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.5" />
                    </svg>
                    Reset
                </button>
                <button type="button" class="btn-filter-action btn-excel-action" id="btnExportExcel" style="background:#10b981; color:#fff;">
                    📊 Excel
                </button>
                <button type="button" class="btn-filter-action btn-pdf-action" id="btnExportPdf" style="background:#ef4444; color:#fff;">
                    📄 PDF
                </button>
            </div>
        </form>

        <div class="table-container">
            <div class="table-responsive">
                <table class="global-table" id="summaryTable">
                    <thead>
                        <tr>
                            <th>Product Information</th>
                            <th>Opening (Pcs)</th>
                            <th>Purchased</th>
                            <th>Sold</th>
                            <th>Adjusted</th>
                            <th>Closing (Pcs)</th>
                            <th class="text-end">Pur. Value</th>
                            <th class="text-end">Sale Value</th>
                            <th class="text-end">Stock Value</th>
                        </tr>
                    </thead>
                    <tbody id="summaryBody">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-info-circle me-2"></i> Select filters and click "Update Report" to view data.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot id="summaryFoot"></tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="loader-overlay">
        <div class="pulse-loader"></div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({ width: '100%', dropdownCssClass: 'select2-custom-dropdown' });

            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                fetchReport();
            });

            $('#btnReset').on('click', function() {
                $('#filterForm')[0].reset();
                $('.select2').val('all').trigger('change');
                $('#summaryBody').html('<tr><td colspan="9" class="text-center py-5 text-muted"><i class="fas fa-info-circle me-2"></i> Select filters and click "Update Report" to view data.</td></tr>');
            });

            function fetchReport() {
                $('.loader-overlay').css('display', 'grid');
                
                const formData = $('#filterForm').serialize();

                $.ajax({
                    url: "{{ route('report.global_summary.fetch') }}",
                    method: 'POST',
                    data: formData + '&_token=' + '{{ csrf_token() }}',
                    success: function(res) {
                        $('.loader-overlay').hide();
                        if (res.success) {
                            renderTable(res.data, res.summary);
                            Swal.fire({
                                icon: 'success',
                                title: 'Report Updated',
                                text: 'Data has been refreshed based on your filters.',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        } else {
                            Swal.fire('Error', res.message || 'Failed to fetch data', 'error');
                        }
                    },
                    error: function() {
                        $('.loader-overlay').hide();
                        Swal.fire('Error', 'Server communication failure', 'error');
                    }
                });
            }

            function renderTable(data, summary) {
                let html = '';
                
                if (data.length === 0) {
                    html = '<tr><td colspan="9" class="text-center py-5">No records found for the selected filters.</td></tr>';
                } else {
                    data.forEach(row => {
                        html += `
                            <tr>
                                <td>
                                    <div class="fw-bold">${row.item}</div>
                                    <small class="text-muted">${row.code}</small>
                                </td>
                                <td><span class="badge bg-light text-dark fw-bold px-3">${row.opening}</span></td>
                                <td><span class="text-primary fw-bold">+${row.purchased}</span></td>
                                <td><span class="text-danger fw-bold">-${row.sold}</span></td>
                                <td><span class="text-muted fw-bold">${row.adjusted >= 0 ? '+' : ''}${row.adjusted}</span></td>
                                <td><span class="badge bg-dark text-white fw-bold px-3">${row.closing}</span></td>
                                <td class="text-end"><span class="val-pill val-pur">PKR ${new Intl.NumberFormat().format(row.purchase_value)}</span></td>
                                <td class="text-end"><span class="val-pill val-sal">PKR ${new Intl.NumberFormat().format(row.sale_value)}</span></td>
                                <td class="text-end"><span class="val-pill val-stk">PKR ${new Intl.NumberFormat().format(row.stock_value)}</span></td>
                            </tr>
                        `;
                    });
                }

                $('#summaryBody').html(html);

                // Update KPIs
                $('#kpiStockVal').text('PKR ' + new Intl.NumberFormat().format(summary.total_stock_value));
                $('#kpiSalesVal').text('PKR ' + new Intl.NumberFormat().format(summary.sale_value));
                $('#kpiPurchVal').text('PKR ' + new Intl.NumberFormat().format(summary.purch_value));
                $('#kpiClosing').html('<i class="fas fa-cubes me-2"></i>' + new Intl.NumberFormat().format(summary.closing));
            }
            
            $('#btnExportExcel').on('click', function() {
                let formData = $('#filterForm').serialize();
                window.location.href = `{{ route('report.global_summary.export.excel') }}?${formData}`;
            });

            $('#btnExportPdf').on('click', function() {
                let formData = $('#filterForm').serialize();
                window.location.href = `{{ route('report.global_summary.export.pdf') }}?${formData}`;
            });
        });
    </script>
@endsection
