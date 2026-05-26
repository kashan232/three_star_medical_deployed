@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #059669;
            --secondary: #64748b;
            --success: #10b981;
            --premium-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Premium Header */
        .page-header {
            background: linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%);
            padding: 24px 30px;
            border-radius: 16px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(5, 150, 105, 0.25);
        }

        .header-title h1 { font-size: 1.6rem; font-weight: 800; margin: 0; letter-spacing: -0.02em; }
        .header-title p { margin: 4px 0 0; opacity: 0.85; font-size: 0.9rem; }

        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        .btn-print { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .btn-pdf { background: #ef4444; color: white; }

        /* KPI Grid */
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

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--premium-shadow);
        }
        .form-label { font-size: 0.8rem; font-weight: 700; color: #475569; margin-bottom: 6px; }

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

        @media print { .filter-section, .page-header .d-flex, .stat-grid { display: none !important; } }
    </style>

    <div class="container-fluid py-4">
        <!-- Premium Header -->
        <div class="page-header">
            <div class="header-title">
                <h1>Price Adjustment Report</h1>
                <p>Strategic analysis of cost and retail price fluctuations</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn-action btn-print"><i class="fas fa-print"></i> Print</button>
                <button id="btnExportPdf" class="btn-action btn-pdf"><i class="fas fa-file-pdf"></i> Export PDF</button>
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
        <div class="filter-section">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Adjustment Type</label>
                        <select name="type" class="form-control select2">
                            <option value="all">All Types</option>
                            <option value="purchase">Purchase</option>
                            <option value="sale">Sale</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Brand</label>
                        <select name="brand_id" id="filterBrand" class="form-control select2">
                            <option value="all">All Brands</option>
                            @foreach($brands as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="filterCategory" class="form-control select2">
                            <option value="all">All Categories</option>
                            @foreach($categories as $c) <option value="{{ $c->id }}">{{ $c->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Sub-Category</label>
                        <select name="sub_category_id" id="filterSubCategory" class="form-control select2">
                            <option value="all">All Sub-Categories</option>
                            @foreach($subCategories as $sc) <option value="{{ $sc->id }}" data-cat="{{ $sc->category_id }}">{{ $sc->name }}</option> @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="filterProduct" class="form-control select2">
                            <option value="all">All Products</option>
                            @foreach($products as $p) <option value="{{ $p->id }}" data-cat="{{ $p->category_id }}" data-sub="{{ $p->sub_category_id }}" data-brand="{{ $p->brand_id }}">{{ $p->item_name }} {{ $p->brand->name ?? '' }} ({{ $p->item_code }})</option> @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100" style="height:40px; border-radius:8px; font-weight:700;"><i class="fas fa-filter mr-2"></i> FILTER</button>
                    </div>
                </div>
            </form>
        </div>

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

            $('#btnExportPdf').on('click', function() {
                if (!_currentData.length) return alert("No data.");
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                const start = $('input[name="start_date"]').val(), end = $('input[name="end_date"]').val();

                doc.setFontSize(18); doc.setTextColor(5, 150, 105);
                doc.text('THREE STARS MEDICAL SUPPLIES', 148, 14, { align: 'center' });
                doc.setFontSize(9); doc.setTextColor(100);
                doc.text('{{ $activeBranch->name ?? "Head Office" }}: {{ $activeBranch->address ?? "Lahore, Pakistan" }} | Phone: {{ $activeBranch->number ?? "+92 42 37353433" }}', 148, 20, { align: 'center' });
                doc.setFontSize(11); doc.setTextColor(0);
                doc.text(`Price Adjustment Analysis Report (${start} to ${end})`, 148, 27, { align: 'center' });

                const rows = _currentData.map(r => [
                    moment(r.created_at).format('DD/MM/YYYY HH:mm'),
                    r.product ? r.product.item_name : '-',
                    r.type.toUpperCase(),
                    parseFloat(r.old_price).toFixed(2),
                    parseFloat(r.new_price).toFixed(2),
                    r.ref_no || '-',
                    r.user ? r.user.name : '-'
                ]);

                doc.autoTable({
                    startY: 30,
                    head: [['Date', 'Product', 'Type', 'Old Price', 'New Price', 'Ref No', 'User']],
                    body: rows,
                    headStyles: { fillColor: [5, 150, 105] },
                    styles: { fontSize: 8 },
                    columnStyles: { 3: { halign: 'right' }, 4: { halign: 'right' } }
                });
                doc.save(`price_report_${start}.pdf`);
            });

            fetchReport();
        });
    </script>
@endsection
