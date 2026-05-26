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

        .filter-section {
            background: #fff;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-item {
            flex: 1;
            min-width: 180px;
        }

        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #334155;
            margin-bottom: 8px;
        }

        .custom-input {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .custom-input:focus {
            border-color: var(--c-accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .btn-global {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-search { background: var(--c-accent); color: #fff; }
        .btn-search:hover { background: #2563eb; transform: scale(1.02); }

        .btn-reset { background: #f1f5f9; color: #475569; }
        .btn-reset:hover { background: #e2e8f0; }

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

        <div class="filter-section">
            <form id="filterForm">
                <div class="filter-row">
                    <div class="filter-item">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control custom-input" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="filter-item">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control custom-input" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="filter-item">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select custom-input">
                            <option value="all">All Warehouses</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="form-label">Company (Brand)</label>
                        <select name="brand_id" id="brand_id" class="form-select custom-input">
                            <option value="all">All Companies</option>
                            @foreach($brands as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filter-row mt-3">
                    <div class="filter-item">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="category_id" class="form-select custom-input">
                            <option value="all">All Categories</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-item">
                        <label class="form-label">Sub-Category</label>
                        <select name="sub_category_id" id="sub_category_id" class="form-select custom-input">
                            <option value="all">All Sub-Categories</option>
                            @foreach($subCategories as $sc)
                                <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filter-item" style="flex: 2;">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="product_id" class="form-select custom-input select2">
                            <option value="all">All Products</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->item_code }} - {{ $p->item_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-global btn-search">
                            <i class="fas fa-sync-alt"></i> Update Report
                        </button>
                        <button type="button" id="btnReset" class="btn-global btn-reset">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

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
        });
    </script>
@endsection
