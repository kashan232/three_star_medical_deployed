@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --info: #0ea5e9;
            --premium-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Premium Header */
        .page-header {
            background: linear-gradient(135deg, #4338ca 0%, #4f46e5 50%, #6366f1 100%);
            padding: 24px 30px;
            border-radius: 16px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(79, 70, 229, 0.25);
        }

        .header-title h1 {
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: -0.02em;
        }

        .header-title p {
            margin: 4px 0 0;
            opacity: 0.85;
            font-size: 0.9rem;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        .btn-print { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
        .btn-pdf { background: #ef4444; color: white; }
        .btn-excel { background: #10b981; color: white; }

        /* KPI Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        .stat-label { font-size: 0.75rem; font-weight: 700; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-value { font-size: 1.6rem; font-weight: 800; color: #1e293b; display: block; margin-top: 4px; }

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
        .premium-table th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            padding: 14px 20px;
            border-bottom: 2px solid #f1f5f9;
        }
        .premium-table td { padding: 14px 20px; font-size: 0.9rem; color: #334155; border-bottom: 1px solid #f1f5f9; }

        .amt-chip {
            background: #eef2ff;
            color: #4338ca;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .status-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-pending { background: #fef3c7; color: #b45309; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-cleared { background: #dbeafe; color: #1d4ed8; }

        @media print {
            .filter-section, .page-header .d-flex, .stat-grid { display: none !important; }
        }
    </style>

    <div class="container-fluid py-4">
        <!-- Premium Header -->
        <div class="page-header">
            <div class="header-title">
                <h1>CDR & Tender Report</h1>
                <p>Call Deposit Receipt analysis and bank guarantee tracking</p>
            </div>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn-action btn-print"><i class="fas fa-print"></i> Print</button>
                <button id="btnExportPdf" class="btn-action btn-pdf"><i class="fas fa-file-pdf"></i> Export PDF</button>
                <button onclick="exportToExcel()" class="btn-action btn-excel"><i class="fas fa-file-excel"></i> Export Excel</button>
            </div>
        </div>

        <!-- KPI Grid -->
        <div class="stat-grid">
            <div class="stat-card" style="border-left-color: var(--primary);">
                <span class="stat-label">Total CDR Count</span>
                <span id="statCount" class="stat-value">0</span>
            </div>
            <div class="stat-card" style="border-left-color: var(--success);">
                <span class="stat-label">Total Amount</span>
                <span id="statAmount" class="stat-value">PKR 0.00</span>
            </div>
            <div class="stat-card" style="border-left-color: var(--info);">
                <span class="stat-label">Active Tenders</span>
                <span id="statActive" class="stat-value">0</span>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form id="filterForm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-01-01') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Customer / Department</label>
                        <select name="customer_id" class="form-control select2">
                            <option value="all">All Departments</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->customer_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Bank Account</label>
                        <select name="account_id" class="form-control select2">
                            <option value="all">All Accounts</option>
                            @foreach($accounts as $a)
                                <option value="{{ $a->id }}">{{ $a->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control select2">
                            <option value="all">All Status</option>
                            <option value="PENDING">Pending</option>
                            <option value="APPROVED">Approved</option>
                            <option value="CLEARED">Cleared</option>
                        </select>
                    </div>
                    @if($isSuperAdmin)
                    <div class="col-md-2">
                        <label class="form-label">Branch</label>
                        <select name="branch_id" class="form-control select2">
                            <option value="all">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" style="height:40px; border-radius:8px;">
                            <i class="fas fa-filter"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="report-section">
            <div class="table-responsive">
                <table id="cdrTable" class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>CDR No</th>
                            <th>Customer / Dept</th>
                            <th>Bank Account</th>
                            <th class="text-right">Amount</th>
                            <th class="text-center">Status</th>
                            <th>City</th>
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

            var dt = $('#cdrTable').DataTable({
                order: [[1, 'desc']],
                pageLength: 25,
                language: { search: "🔍", searchPlaceholder: "Quick search report..." }
            });

            $('#filterForm').on('submit', function(e) { e.preventDefault(); fetchReport(); });

            function fetchReport() {
                $.ajax({
                    url: "{{ route('report.cdr.fetch') }}",
                    method: "POST",
                    data: $('#filterForm').serialize() + "&_token={{ csrf_token() }}",
                    success: function(res) {
                        if (res.success) {
                            _currentData = res.data;
                            dt.clear();
                            let totalAmt = 0;
                            let activeCount = 0;

                            res.data.forEach(r => {
                                totalAmt += parseFloat(r.amount);
                                if(r.status.toUpperCase() === 'PENDING') activeCount++;

                                dt.row.add([
                                    `<span class="font-weight-bold text-primary">${r.code}</span>`,
                                    moment(r.cdr_date).format('DD/MM/YYYY'),
                                    r.cdr_no,
                                    r.customer ? (r.customer.customer_name) : '-',
                                    r.bank_account ? r.bank_account.title : '-',
                                    `<div class="text-right"><span class="amt-chip">${fmtPKR(r.amount)}</span></div>`,
                                    `<div class="text-center"><span class="status-badge status-${r.status.toLowerCase()}">${r.status}</span></div>`,
                                    r.city || '-'
                                ]);
                            });
                            dt.draw();
                            $('#statCount').text(res.data.length);
                            $('#statAmount').text(fmtPKRK(totalAmt));
                            $('#statActive').text(activeCount);
                        }
                    }
                });
            }

            function fmtPKR(v) { return parseFloat(v).toLocaleString('en-PK', { minimumFractionDigits: 2 }); }
            function fmtPKRK(v) { return 'PKR ' + parseFloat(v).toLocaleString('en-PK', { minimumFractionDigits: 2 }); }

            $('#btnExportPdf').on('click', function() {
                if (!_currentData.length) return alert("No data available.");
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF('l', 'mm', 'a4');
                const start = $('input[name="start_date"]').val();
                const end = $('input[name="end_date"]').val();

                doc.setFontSize(18); doc.setTextColor(79, 70, 229);
                doc.text('THREE STARS MEDICAL SUPPLIES', 148, 14, { align: 'center' });
                doc.setFontSize(9); doc.setTextColor(100);
                doc.text('{{ $activeBranch->name ?? "Head Office" }}: {{ $activeBranch->address ?? "Lahore, Pakistan" }} | Phone: {{ $activeBranch->number ?? "+92 42 37353433" }}', 148, 20, { align: 'center' });
                doc.setFontSize(11); doc.setTextColor(0);
                doc.text(`CDR & Tender Report (${start} to ${end})`, 148, 27, { align: 'center' });

                const rows = _currentData.map(r => [
                    r.code, moment(r.cdr_date).format('DD/MM/YYYY'), r.cdr_no,
                    r.customer ? r.customer.customer_name : '-',
                    r.bank_account ? r.bank_account.title : '-',
                    parseFloat(r.amount).toLocaleString('en-PK', { minimumFractionDigits: 2 }),
                    r.status, r.city || '-'
                ]);

                doc.autoTable({
                    startY: 30,
                    head: [['Code', 'Date', 'CDR No', 'Customer', 'Account', 'Amount', 'Status', 'City']],
                    body: rows,
                    headStyles: { fillColor: [79, 70, 229] },
                    styles: { fontSize: 8 },
                    columnStyles: { 5: { halign: 'right' }, 6: { halign: 'center' } }
                });
                doc.save(`cdr_report_${start}.pdf`);
            });

            fetchReport();
        });

        function exportToExcel() { alert('Export Excel triggered'); }
    </script>
@endsection
