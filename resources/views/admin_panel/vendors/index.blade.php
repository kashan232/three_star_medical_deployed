@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-glass: rgba(255, 255, 255, 0.9);
            --radius-lg: 12px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .vendor-listing-area {
            padding: 20px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #ffffff;
            padding: 20px 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-subtle);
            border: 1px solid #e2e8f0;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title p {
            font-size: 0.85rem;
            color: var(--secondary);
            margin: 5px 0 0 0;
        }

        /* Premium Table */
        .table-container {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            box-shadow: var(--shadow-subtle);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-premium th {
            background: #f1f5f9;
            padding: 15px 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .table-premium td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .table-premium tr:hover td {
            background: #f8fafc;
        }

        /* Info Groups */
        .identity-group .name {
            font-weight: 800;
            color: #0f172a;
            font-size: 0.95rem;
            display: block;
        }

        .identity-group .id-tag {
            font-size: 0.75rem;
            color: #0d9488;
            font-weight: 700;
            background: #ccfbf1;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            display: inline-block;
        }

        .contact-group .item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .contact-group i {
            width: 16px;
            color: var(--secondary);
        }

        .legal-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 250px;
        }

        .legal-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }

        .legal-badge.active {
            background: #fff7ed;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .financial-group .balance {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .balance-debit { color: var(--danger); } /* Rare for vendor */
        .balance-credit { color: var(--success); } /* Normal: we owe vendor */

        .limit-info {
            font-size: 0.7rem;
            color: var(--secondary);
            margin-top: 4px;
        }

        /* Badges */
        .badge-premium {
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

        .action-flex {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        /* Quick Vendor Search Card */
        .vendor-search-card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 16px 22px;
            box-shadow: var(--shadow-subtle);
            border: 1px solid #e2e8f0;
        }

        .search-icon-circle {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-icon-circle:hover {
            background: #e0e7ff;
            transform: scale(1.05);
        }

        /* Select2 Premium Custom Styling */
        .vendor-search-card .select2-container--default .select2-selection--single {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 10px !important;
            height: 44px !important;
            background-color: #f8fafc !important;
            display: flex;
            align-items: center;
            transition: all 0.2s ease-in-out;
        }

        .vendor-search-card .select2-container--default .select2-selection--single:hover {
            border-color: #94a3b8 !important;
            background-color: #ffffff !important;
        }

        .vendor-search-card .select2-container--default.select2-container--focus .select2-selection--single,
        .vendor-search-card .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--primary) !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
            outline: none !important;
        }

        .vendor-search-card .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: 0.92rem !important;
            font-weight: 600;
            padding-left: 14px !important;
            padding-right: 32px !important;
            line-height: 42px !important;
        }

        .vendor-search-card .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 12px !important;
        }

        .vendor-search-card .select2-container--default .select2-selection--single .select2-selection__clear {
            font-size: 1.25rem;
            margin-right: 14px;
            color: #94a3b8;
            cursor: pointer;
        }

        .vendor-search-card .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #ef4444;
        }

        /* Select2 Dropdown Styling */
        .select2-dropdown {
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.06) !important;
            overflow: hidden;
            z-index: 9999 !important;
        }

        .select2-dropdown .select2-search--dropdown {
            padding: 8px 10px !important;
        }

        .select2-dropdown .select2-search__field {
            border: 1px solid #cbd5e1 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            font-size: 0.9rem !important;
            outline: none !important;
        }

        .select2-dropdown .select2-search__field:focus {
            border-color: var(--primary) !important;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.15) !important;
        }

        .select2-results__option--highlighted[aria-selected] {
            background-color: var(--primary) !important;
            color: #ffffff !important;
        }

        .select2-results__option--highlighted[aria-selected] .select2-vendor-title,
        .select2-results__option--highlighted[aria-selected] .select2-vendor-meta,
        .select2-results__option--highlighted[aria-selected] i {
            color: #ffffff !important;
        }

        .select2-results__option--highlighted[aria-selected] .badge {
            background-color: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
        }

        /* Row Highlight Flash Animation */
        @keyframes highlightFlash {
            0% { background-color: #fef08a !important; }
            50% { background-color: #fef9c3 !important; }
            100% { background-color: transparent; }
        }

        .row-highlight td {
            animation: highlightFlash 2.5s ease-out;
        }
    </style>

    <div class="vendor-listing-area">
        <div class="container-fluid p-0">
            <!-- Dynamic Header -->
            <div class="listing-header">
                <div class="header-title">
                    <h1><i class="fa fa-truck-loading"></i> Vendor Management</h1>
                    <p>Supply chain records and payable balances for procurement partners</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="{{ route('vendor.payments') }}" class="btn btn-outline-info fw-bold" style="border-radius:10px;">
                        <i class="fa fa-cash-register me-2"></i> Payments
                    </a>
                    <a href="{{ url('vendors-ledger') }}" class="btn btn-outline-secondary fw-bold" style="border-radius:10px;">
                        <i class="fa fa-list-alt me-2"></i> Ledger
                    </a>
                    <button type="button" id="openImportVendorModal" class="btn btn-outline-success fw-bold" style="border-radius:10px;">
                        <i class="fa fa-file-import me-2"></i> Import
                    </button>
                    <a href="{{ route('parties.create', ['type' => 'Vendor']) }}" class="btn btn-primary fw-bold" style="border-radius:10px; padding: 10px 20px;">
                        <i class="fa fa-plus-circle me-2"></i> Add Vendor
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:12px; border-left: 5px solid var(--success) !important;">
                    <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Quick Vendor Search Bar -->
            <div class="vendor-search-card mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width: 320px; max-width: 720px;">
                        <div class="search-icon-circle" title="Click to search vendors">
                            <i class="fa fa-search text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="vendorSearchSelect" class="form-label fw-bold text-dark small mb-0">
                                    <i class="fa fa-bolt text-warning me-1"></i> Quick Vendor Search
                                </label>
                                <span class="text-muted" style="font-size: 0.72rem;">Search by Name, Business, Code, Phone, or City</span>
                            </div>
                            <select id="vendorSearchSelect" class="form-control" style="width: 100%;">
                                <option value=""></option>
                                @foreach ($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        data-name="{{ $v->name }}"
                                        data-business="{{ $v->business_name ?? '' }}"
                                        data-code="{{ $v->vendor_code ?? '' }}"
                                        data-phone="{{ $v->phone ?? '' }}"
                                        data-contact="{{ $v->contact_person ?? '' }}"
                                        data-city="{{ $v->city ?? '' }}"
                                        data-cnic="{{ $v->cnic ?? '' }}"
                                        data-ntn="{{ $v->ntn_no ?? '' }}"
                                        data-status="{{ $v->is_active ? 'active' : 'inactive' }}">
                                        {{ $v->name }}{{ $v->vendor_code ? ' [CODE: ' . $v->vendor_code . ']' : '' }}{{ $v->phone ? ' • ' . $v->phone : '' }}{{ $v->city ? ' • ' . $v->city : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Filtered State Actions -->
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <div id="quickVendorActions" style="display: none;" class="d-flex gap-2 align-items-center">
                            <a id="btnQuickLedger" href="#" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" style="border-radius: 8px;">
                                <i class="fa fa-list-alt me-1"></i> Vendor Ledger
                            </a>
                            <a id="btnQuickEdit" href="#" class="btn btn-sm btn-outline-info fw-bold shadow-sm" style="border-radius: 8px;">
                                <i class="fa fa-edit me-1"></i> Edit Vendor
                            </a>
                        </div>
                        <button type="button" id="btnResetVendorSearch" class="btn btn-sm btn-outline-secondary fw-bold" style="border-radius: 8px; display: none;">
                            <i class="fa fa-times me-1"></i> Show All Vendors
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Table Wrapper -->
            <div class="table-container">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Supplier Identity</th>
                            <th>Communication Node</th>
                            <th>Mailing Point</th>
                            <th>Tax & Legal Identification</th>
                            <th>Payable Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendors as $v)
                            <tr class="vendor-row" id="vendor-row-{{ $v->id }}" data-vendor-id="{{ $v->id }}">
                                <!-- Identity -->
                                <td>
                                    <div class="identity-group">
                                        <span class="name">{{ $v->name }}</span>
                                        @if($v->business_name)
                                            <small class="text-muted d-block" style="font-size:0.75rem;">{{ $v->business_name }}</small>
                                        @endif
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="id-tag">CODE: {{ $v->vendor_code ?? 'N/A' }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem;">{{ $v->city ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div class="contact-group">
                                        <div class="item"><i class="fa fa-phone-alt"></i> <b>{{ $v->phone }}</b></div>
                                        @if($v->contact_person)
                                            <div class="item"><i class="fa fa-user-tie"></i> {{ $v->contact_person }}</div>
                                        @endif
                                        @if($v->email)
                                            <div class="item"><i class="fa fa-envelope"></i> <small>{{ $v->email }}</small></div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Location -->
                                <td>
                                    <div style="font-size:0.85rem;">
                                        <div class="mb-1 fw-bold text-dark">{{ $v->country ?? 'Pakistan' }}</div>
                                        <div class="text-muted" style="line-height:1.4; max-width: 200px;">
                                            {{ Str::limit($v->address, 60) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Legal / Tax -->
                                <td>
                                    <div class="legal-group">
                                        @if($v->cnic)
                                            <span class="legal-badge active" title="CNIC Number">ID: {{ $v->cnic }}</span>
                                        @endif
                                        @if($v->ntn_no)
                                            <span class="legal-badge active" title="NTN Number">NTN: {{ $v->ntn_no }}</span>
                                        @endif
                                        @if($v->gst_no)
                                            <span class="legal-badge" title="GST Number">GST: {{ $v->gst_no }}</span>
                                        @endif
                                        @if($v->dsl_no)
                                            <span class="legal-badge" title="Drug Sale License">DSL: {{ $v->dsl_no }}</span>
                                        @endif
                                        @if($v->drap_no)
                                            <span class="legal-badge" title="Medical Registration">DRAP: {{ $v->drap_no }}</span>
                                        @endif
                                        @if($v->ftn_no)
                                            <span class="legal-badge" title="FTN Number">FTN: {{ $v->ftn_no }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Financial -->
                                <td>
                                    <div class="financial-group">
                                        @php
                                            $ob = floatval($v->opening_balance ?? 0);
                                            $dr = floatval($v->debit ?? 0);
                                            $cr = floatval($v->credit ?? 0);
                                            // For vendors: Credit increases what we owe
                                            $bal = $ob + $cr - $dr;
                                        @endphp
                                        @if($bal > 0)
                                            <div class="balance balance-credit">RS. {{ number_format($bal, 2) }} <small>(To Pay)</small></div>
                                        @elseif($bal < 0)
                                            <div class="balance balance-debit">RS. {{ number_format(abs($bal), 2) }} <small>(Adv)</small></div>
                                        @else
                                            <div class="balance text-muted">RS. 0.00</div>
                                        @endif

                                        <div class="limit-info">
                                            Credit Limit: RS. {{ number_format($v->credit_limit ?? 0, 0) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="text-center">
                                    <div class="mb-2">
                                        <span class="badge-premium {{ $v->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $v->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <span class="text-muted" style="font-size:0.65rem; font-weight:700;">
                                        {{ $v->branch?->abr ?? 'HO' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-flex">
                                        @include('admin_panel.partials.action_buttons', [
                                            'editRoute' => route('parties.edit', [$v->id, 'type' => 'Vendor']),
                                            'deleteRoute' => route('vendors.delete', $v->id),
                                            'editIsLink' => true,
                                            'permissions' => ['edit' => 'vendors.edit', 'delete' => 'vendors.delete'],
                                            'dataId' => $v->id,
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="noVendorFoundRow" style="display: none;">
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <div class="mb-3 text-secondary opacity-50">
                                        <i class="fa fa-search fa-3x"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No matching vendor found</h6>
                                    <p class="text-muted small mb-3">Try searching with a different name, code, phone number, or city.</p>
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3" style="border-radius: 8px;" onclick="$('#vendorSearchSelect').val('').trigger('change');">
                                        <i class="fa fa-rotate-left me-1"></i> Show All Vendors
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Meta -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small fw-bold" id="vendorShowingCount">
                    System synchronizing {{ $vendors->count() }} active procurement accounts
                </div>
            </div>
        </div>
    </div>

    <!-- Import Vendor Modal -->
    <div class="modal fade" id="importVendorModal" tabindex="-1" role="dialog" aria-labelledby="importVendorModalLabel" aria-hidden="true" style="z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 600px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header bg-success text-white px-4 py-3 border-0">
                    <h5 class="modal-title fw-bold" id="importVendorModalLabel"><i class="fa fa-file-excel me-2"></i> Bulk Import Vendors</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="background: none; border: none; font-size: 1.5rem; color: white; line-height: 1;">&times;</button>
                </div>
                
                <form id="vendorImportForm" action="{{ route('vendors.import') }}" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                    @csrf
                    <div class="modal-body p-4">
                        <!-- Step 1: Download Template -->
                        <div class="p-3 mb-4 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-success mb-1"><i class="fa fa-info-circle me-1"></i> Step 1: Download Standard Template</h6>
                                    <p class="small text-muted mb-0">Use our pre-formatted Excel template for maximum compatibility.</p>
                                </div>
                                <a href="{{ route('vendors.download-template') }}" class="btn btn-success btn-sm fw-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="fa fa-download me-1"></i> Download Template (.xlsx)
                                </a>
                            </div>
                        </div>

                        <!-- Auto-Fill Dummy Data Toggle Switch -->
                        <div class="form-check form-switch p-3 mb-4 rounded-3 border" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="vendor_auto_fill_dummy" name="auto_fill_dummy" value="1" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark mb-0" for="vendor_auto_fill_dummy" style="cursor: pointer;">
                                    Auto-Fill missing Vendor Name & required fields with editable dummy data
                                </label>
                            </div>
                            <p class="small text-muted mt-1 mb-0 ms-5">
                                When enabled, rows missing required vendor names will automatically be populated with editable dummy names like <code>[DUMMY] Vendor Row 1</code> so the import completes without errors.
                            </p>
                        </div>

                        <!-- Step 2: Upload File -->
                        <div class="mb-3">
                            <label for="vendor_import_file" class="form-label fw-bold text-dark">
                                Step 2: Upload Excel / CSV File <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control form-control-lg" id="vendor_import_file" name="file" accept=".csv, .xlsx, .xls" required style="border-radius: 10px;">
                            <small class="text-muted mt-1 d-block">Supported file formats: <strong>.xlsx, .xls, .csv</strong></small>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" id="btnVendorImportSubmit" onclick="doVendorImport()" style="border-radius: 8px;">
                            <i class="fa fa-file-import me-1"></i> Import Vendors
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('load', function () {
            var openBtn = document.getElementById('openImportVendorModal');
            if (openBtn) {
                openBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    try {
                        $('#importVendorModal').modal('show');
                    } catch(err) {
                        if (typeof bootstrap !== 'undefined') {
                            new bootstrap.Modal(document.getElementById('importVendorModal')).show();
                        }
                    }
                });
            }
        });

        function doVendorImport() {
            var form = document.getElementById('vendorImportForm');
            var fileInput = document.getElementById('vendor_import_file');

            if (!fileInput || !fileInput.files.length) {
                Swal.fire({ icon: 'warning', title: 'No File Selected', text: 'Please select a CSV or Excel file first.', confirmButtonColor: '#f59e0b' });
                return;
            }

            var btn = document.getElementById('btnVendorImportSubmit');
            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) {
                var status = response.status;
                return response.text().then(function (text) {
                    var data;
                    try { data = JSON.parse(text); } catch (e) { data = null; }
                    return { ok: response.ok, status: status, data: data, raw: text };
                });
            })
            .then(function (result) {
                btn.disabled = false;
                btn.innerHTML = origHtml;

                if (result.ok) {
                    try {
                        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined' && typeof bootstrap.Modal.getInstance === 'function') {
                            var bsModal = bootstrap.Modal.getInstance(document.getElementById('importVendorModal'));
                            if (bsModal) bsModal.hide();
                        } else {
                            $('#importVendorModal').modal('hide');
                        }
                    } catch(me) {
                        try { $('#importVendorModal').modal('hide'); } catch(e2) {}
                    }
                    form.reset();

                    var res       = result.data;
                    var imported  = res.imported_count  || 0;
                    var skipped   = res.skipped_count   || 0;
                    var dummy     = res.dummy_count     || 0;
                    var duplicate = res.duplicate_count || 0;

                    var statsHtml =
                        '<div style="display:flex;gap:10px;justify-content:center;margin:18px 0 10px;flex-wrap:wrap">' +

                        '<div style="flex:1;min-width:80px;background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1.5px solid #6ee7b7;border-radius:12px;padding:14px 10px;text-align:center">' +
                        '<div style="font-size:2rem;font-weight:900;color:#059669;line-height:1">' + imported + '</div>' +
                        '<div style="font-size:0.72rem;font-weight:700;color:#065f46;text-transform:uppercase;letter-spacing:.04em;margin-top:4px">✅ Imported</div>' +
                        '</div>' +

                        (duplicate > 0 ?
                        '<div style="flex:1;min-width:80px;background:linear-gradient(135deg,#fef2f2,#fecaca);border:1.5px solid #f87171;border-radius:12px;padding:14px 10px;text-align:center">' +
                        '<div style="font-size:2rem;font-weight:900;color:#dc2626;line-height:1">' + duplicate + '</div>' +
                        '<div style="font-size:0.72rem;font-weight:700;color:#7f1d1d;text-transform:uppercase;letter-spacing:.04em;margin-top:4px">⛔ Duplicates</div>' +
                        '</div>' : '') +

                        (skipped > 0 ?
                        '<div style="flex:1;min-width:80px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1.5px solid #fbbf24;border-radius:12px;padding:14px 10px;text-align:center">' +
                        '<div style="font-size:2rem;font-weight:900;color:#d97706;line-height:1">' + skipped + '</div>' +
                        '<div style="font-size:0.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.04em;margin-top:4px">⏭ Skipped</div>' +
                        '</div>' : '') +

                        (dummy > 0 ?
                        '<div style="flex:1;min-width:80px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1.5px solid #93c5fd;border-radius:12px;padding:14px 10px;text-align:center">' +
                        '<div style="font-size:2rem;font-weight:900;color:#2563eb;line-height:1">' + dummy + '</div>' +
                        '<div style="font-size:0.72rem;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.04em;margin-top:4px">🤖 Dummy</div>' +
                        '</div>' : '') +

                        '</div>' +

                        (duplicate > 0 ?
                        '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:0.78rem;color:#b91c1c;margin-top:6px">' +
                        '<b>⛔ Duplicates Skipped:</b> ' + duplicate + ' row(s) already exist by name, ID or phone and were not imported again.' +
                        '</div>' : '') +

                        (dummy > 0 ?
                        '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:8px 12px;font-size:0.78rem;color:#1d4ed8;margin-top:6px">' +
                        '<b>ℹ️ Note:</b> ' + dummy + ' row(s) auto-filled with dummy names — edit from Vendor Directory.' +
                        '</div>' : '') +

                        (res.errors && res.errors.length ?
                        '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:0.75rem;color:#475569;margin-top:6px;max-height:100px;overflow-y:auto;text-align:left">' +
                        '<b>Details:</b><ul style="margin:4px 0 0 12px;padding:0">' +
                        res.errors.map(function(e){ return '<li>' + e + '</li>'; }).join('') +
                        '</ul></div>' : '');

                    Swal.fire({
                        icon: 'success',
                        title: '<span style="font-size:1.15rem;font-weight:800;color:#065f46">✅ Import Successful!</span>',
                        html: statsHtml,
                        confirmButtonText: 'Great, Refresh Page!',
                        confirmButtonColor: '#059669',
                        customClass: { confirmButton: 'btn btn-success fw-bold px-5' }
                    }).then(function () { location.reload(); });

                } else {
                    var errRes  = result.data;
                    var errMsg;

                    if (errRes && errRes.message) {
                        errMsg = errRes.message;
                        if (errRes.errors && Array.isArray(errRes.errors) && errRes.errors.length) {
                            errMsg += '<br><div class="text-start mt-2 p-2 bg-light rounded text-danger small" style="max-height:150px;overflow-y:auto"><ul>' +
                                errRes.errors.map(function (e) { return '<li>' + e + '</li>'; }).join('') +
                                '</ul></div>';
                        }
                    } else {
                        var snippet = (result.raw || '').replace(/<[^>]+>/g, '').trim().substring(0, 300);
                        errMsg = 'Server returned HTTP ' + result.status + '.<br><small class="text-muted">' + (snippet || 'No details available.') + '</small>';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import Failed (HTTP ' + result.status + ')',
                        html: errMsg,
                        showCancelButton: true,
                        confirmButtonText: '⚡ Auto-Fill Dummy & Retry',
                        cancelButtonText: 'Close',
                        confirmButtonColor: '#d97706',
                        customClass: {
                            confirmButton: 'btn btn-warning fw-bold text-dark px-3',
                            cancelButton:  'btn btn-secondary px-3'
                        }
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            document.getElementById('vendor_auto_fill_dummy').checked = true;
                            doVendorImport();
                        }
                    });
                }
            })
            .catch(function (err) {
                btn.disabled = false;
                btn.innerHTML = origHtml;
                Swal.fire({ icon: 'error', title: 'Connection Error', html: 'Could not reach server.<br><small class="text-muted">' + (err.message || '') + '</small>', confirmButtonColor: '#ef4444' });
            });
        }
    </script>
@endsection

