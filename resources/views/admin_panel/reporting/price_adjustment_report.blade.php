@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #059669;
            --secondary: #64748b;
            --success: #10b981;
            --premium-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --ink: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --white: #ffffff;
            --brand: #4f46e5;
            --brand-light: #ede9fe;
            --green: #10b981;
            --green-light: #d1fae5;
            --red: #ef4444;
            --red-light: #fee2e2;
            --blue: #3b82f6;
            --amber: #f59e0b;
        }

        /* Topbar */
        .rpt-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
            flex-wrap: wrap;
            gap: 12px;
            background: linear-gradient(135deg, #064e3b 0%, #059669 55%, #10b981 100%);
            border-radius: 14px;
            padding: 22px 28px;
            color: #fff;
            box-shadow: 0 6px 24px rgba(5, 150, 105, .32);
        }

        .rpt-title h4 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rpt-title p {
            margin: 3px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: .85rem;
        }

        /* Stat/KPI Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 14px;
            box-shadow: var(--premium-shadow);
            border-left: 5px solid var(--primary);
        }
        .stat-label { font-size: 0.75rem; font-weight: 700; color: var(--secondary); text-transform: uppercase; }
        .stat-value { font-size: 1.4rem; font-weight: 800; color: #1e293b; display: block; margin-top: 4px; }

        /* Filter Row Styles */
        .filter-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            box-shadow: var(--premium-shadow);
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

        .btn-filter-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border-radius: 6px;
            font-size: .72rem;
            font-weight: 600;
            cursor: pointer;
            padding: 4px 8px;
            min-width: 80px;
            height: 32px;
            border: none;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-filter-action:active {
            transform: scale(0.98);
        }

        .btn-filter-search {
            background: #059669;
            color: #fff;
        }

        .btn-filter-search:hover {
            background: #047857;
        }

        .btn-filter-reset {
            background: #94a3b8;
            color: #fff;
        }

        .btn-filter-reset:hover {
            background: #64748b;
        }

        .btn-pdf-action {
            background: #ef4444;
            color: #fff;
        }

        .btn-pdf-action:hover {
            background: #dc2626;
        }

        .btn-print-action {
            background: #7c3aed;
            color: #fff;
        }

        .btn-print-action:hover {
            background: #6d28d9;
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

        .report-section {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--premium-shadow);
        }
        .premium-table thead { background: #f8fafc; }
        .premium-table th { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; color: #64748b; padding: 14px 20px; border-bottom: 2px solid #f1f5f9; }
        .premium-table td { padding: 14px 20px; font-size: 0.9rem; color: #334155; border-bottom: 1px solid #f1f5f9; }

        .amt-chip { padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 0.85rem; }
        .amt-old { background: #fee2e2; color: #b91c1c; }
        .amt-new { background: #dcfce7; color: #15803d; }

        @media print { .filter-card, .rpt-topbar, .stat-grid { display: none !important; } }
    </style>

    <div class="container-fluid py-4">
        <!-- Topbar -->
        <div class="rpt-topbar">
            <div class="rpt-title">
                <h4>
                    <i class="fas fa-sliders-h" style="color:#fff; font-size:1.35rem;"></i>
                    Price Adjustment Report
                </h4>
                <p>Strategic analysis of cost and retail price fluctuations</p>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="stat-grid">
            <div class="stat-card" style="border-left-color:#1e40af;">
                <span class="stat-label">Total Logs</span>
                <span id="statCount" class="stat-value">0</span>
            </div>
            <div class="stat-card" style="border-left-color:#059669;">
                <span class="stat-label">Price Increases</span>
                <span id="statInc" class="stat-value">0</span>
            </div>
            <div class="stat-card" style="border-left-color:#dc2626;">
                <span class="stat-label">Price Decreases</span>
                <span id="statDec" class="stat-value">0</span>
            </div>
            <div class="stat-card" style="border-left-color:#7c3aed;">
                <span class="stat-label">Latest Change</span>
                <span id="statLatest" class="stat-value">-</span>
            </div>
        </div>

        <!-- Filters -->
        <form id="filterForm" class="filter-card">
            <div class="filter-inputs-container">
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1; min-width: 120px; max-width: 150px;">
                        <label>Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="filter-group" style="flex: 1; min-width: 120px; max-width: 150px;">
                        <label>End Date</label>
                        <input type="date" name="end_date" id="end_date" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="filter-group" style="flex: 1.2; min-width: 130px; max-width: 160px;">
                        <label>Adjustment Type</label>
                        <select name="type" id="filterType" class="select2">
                            <option value="all">All Types</option>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1.2; min-width: 140px; max-width: 180px;">
                        <label>Brand</label>
                        <select name="brand_id" id="filterBrand" class="select2">
                            <option value="all">All Brands</option>
                            @foreach($brands as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 1.2; min-width: 140px; max-width: 180px;">
                        <label>Category</label>
                        <select name="category_id" id="filterCategory" class="select2">
                            <option value="all">All Categories</option>
                            @foreach($categories as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                        </select>
                    </div>
                </div>
                <div class="filter-row">
                    <div class="filter-group" style="flex: 1.2; min-width: 140px; max-width: 180px;">
                        <label>Sub-Category</label>
                        <select name="sub_category_id" id="filterSubCategory" class="select2">
                            <option value="all">All Sub-Categories</option>
                            @foreach($subCategories as $sc) <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex: 2; min-width: 220px; max-width: 350px;">
                        <label>Product</label>
                        <select name="product_id" id="filterProduct" class="select2">
                            <option value="all">All Products</option>
                            @foreach($products as $p) <option value="{{ $p->id }}" data-cat="{{ $p->category_id }}" data-sub="{{ $p->sub_category_id }}" data-brand="{{ $p->brand_id }}">{{ $p->item_name }} {{ $p->brand->name ?? '' }} ({{ $p->item_code }})</option> @endforeach
                        </select>
                    </div>
                    <div class="filter-group" style="flex-direction: row; gap: 6px; align-items: flex-end; min-width: 320px; flex: 1.5;">
                        <button type="submit" class="btn-filter-action btn-filter-search" style="flex: 1;">🔍 Search</button>
                        <button type="button" id="btnReset" class="btn-filter-action btn-filter-reset" style="flex: 1;">↺ Reset</button>
                        <button type="button" id="btnExportExcel" class="btn-filter-action btn-excel-action" style="flex: 1;">📊 Excel</button>
                        <button type="button" id="btnExportPdf" class="btn-filter-action btn-pdf-action" style="flex: 1;">📄 PDF</button>
                        <button type="button" onclick="window.print()" class="btn-filter-action btn-print-action" style="flex: 1;">🖨 Print</button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Report Results -->
        <div class="report-section">
            <div class="table-responsive">
                <table id="reportTable" class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Product Details</th>
                            <th>Type</th>
                            <th>Old Price</th>
                            <th>New Price</th>
                            <th>Reference</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });
            var _currentData = [];

            // Simple product dependency filter
            $('#filterCategory').on('change', function() {
                let catId = $(this).val();
                $('#filterSubCategory option').each(function() {
                    if (catId === 'all' || $(this).data('cat') == catId || $(this).val() === 'all') $(this).show(); else $(this).hide();
                });
                $('#filterSubCategory').val('all').trigger('change');
            });

            var dt = $('#reportTable').DataTable({ order: [[0, 'desc']], pageLength: 50 });

            $('#btnReset').on('click', function() {
                $('#filterForm')[0].reset();
                $('.select2').val('all').trigger('change.select2');
                fetchReport();
            });

            $('#filterForm').on('submit', function(e) { e.preventDefault(); fetchReport(); });

            function fetchReport() {
                $.ajax({
                    url: "{{ route('report.price_adjustment.fetch') }}",
                    method: "POST",
                    data: $('#filterForm').serialize() + "&_token={{ csrf_token() }}",
                    success: function(res) {
                        _currentData = res.data;
                        dt.clear();
                        let inc = 0, dec = 0, latest = '-';
                        
                        res.data.forEach((r, idx) => {
                            if(idx === 0) latest = moment(r.created_at).format('DD MMM');
                            if(parseFloat(r.new_price) > parseFloat(r.old_price)) inc++;
                            else if(parseFloat(r.new_price) < parseFloat(r.old_price)) dec++;

                            dt.row.add([
                                `<strong>${moment(r.created_at).format('DD/MM/YYYY')}</strong><br><small class="text-muted">${moment(r.created_at).format('HH:mm')}</small>`,
                                `<strong>${r.product ? r.product.item_name : '-'}</strong><br><small>${r.product ? r.product.item_code : '-'}</small>`,
                                `<span class="badge badge-${r.type === 'purchase' ? 'info' : 'primary'} text-uppercase">${r.type}</span>`,
                                `<span class="amt-chip amt-old">PKR ${parseFloat(r.old_price).toFixed(2)}</span>`,
                                `<span class="amt-chip amt-new">PKR ${parseFloat(r.new_price).toFixed(2)}</span>`,
                                `<strong>${r.ref_no || '-'}</strong><br><small class="text-muted">${r.description || ''}</small>`,
                                `<span class="font-weight-bold">${r.user ? r.user.name : '-'}</span>`
                            ]);
                        });
                        dt.draw();
                        $('#statCount').text(res.data.length);
                        $('#statInc').text(inc);
                        $('#statDec').text(dec);
                        $('#statLatest').text(latest);
                    }
                });
            }

            $('#btnExportExcel').on('click', function() {
                let formData = $('#filterForm').serialize();
                window.location.href = `{{ route('report.price_adjustment.export.excel') }}?${formData}`;
            });

            $('#btnExportPdf').on('click', function() {
                let formData = $('#filterForm').serialize();
                window.location.href = `{{ route('report.price_adjustment.export.pdf') }}?${formData}`;
            });

            fetchReport();
        });
    </script>
@endsection
