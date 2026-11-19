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

        .customer-listing-area {
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
            color: var(--primary);
            font-weight: 700;
            background: #e0e7ff;
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
            background: #ecfdf5;
            color: #059669;
            border-color: #6ee7b7;
        }

        .financial-group .balance {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .balance-debit { color: var(--danger); }
        .balance-credit { color: var(--success); }

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

        /* Toggle Button Custom Styling */
        .btn-status-toggle {
            padding: 6px;
            border-radius: 8px;
            transition: 0.2s;
        }
        .btn-status-toggle.active { color: var(--success); background: #f0fdf4; }
        .btn-status-toggle.inactive { color: var(--secondary); background: #f8fafc; }

        .btn-status-toggle i { font-size: 1.25rem; }

        /* Urdu Text Support */
        .text-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 0.8rem;
            color: var(--secondary);
        }

        /* Quick Customer Search Card */
        .customer-search-card {
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
        .customer-search-card .select2-container--default .select2-selection--single {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 10px !important;
            height: 44px !important;
            background-color: #f8fafc !important;
            display: flex;
            align-items: center;
            transition: all 0.2s ease-in-out;
        }

        .customer-search-card .select2-container--default .select2-selection--single:hover {
            border-color: #94a3b8 !important;
            background-color: #ffffff !important;
        }

        .customer-search-card .select2-container--default.select2-container--focus .select2-selection--single,
        .customer-search-card .select2-container--default.select2-container--open .select2-selection--single {
            border-color: var(--primary) !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
            outline: none !important;
        }

        .customer-search-card .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1e293b !important;
            font-size: 0.92rem !important;
            font-weight: 600;
            padding-left: 14px !important;
            padding-right: 32px !important;
            line-height: 42px !important;
        }

        .customer-search-card .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 42px !important;
            right: 12px !important;
        }

        .customer-search-card .select2-container--default .select2-selection--single .select2-selection__clear {
            font-size: 1.25rem;
            margin-right: 14px;
            color: #94a3b8;
            cursor: pointer;
        }

        .customer-search-card .select2-container--default .select2-selection--single .select2-selection__clear:hover {
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

        .select2-results__option--highlighted[aria-selected] .select2-customer-title,
        .select2-results__option--highlighted[aria-selected] .select2-customer-meta,
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

    <div class="customer-listing-area">
        <div class="container-fluid p-0">
            <!-- Dynamic Header -->
            <div class="listing-header">
                <div class="header-title">
                    <h1><i class="fa fa-users"></i> Customer Directory</h1>
                    <p>Financial oversight and identification records for all registered customers</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="{{ route('customer.payments') }}" class="btn btn-outline-info fw-bold" style="border-radius:10px;">
                        <i class="fa fa-money-bill-wave me-2"></i> Payments
                    </a>
                    <a href="{{ route('customers.ledger') }}" class="btn btn-outline-primary fw-bold" style="border-radius:10px;">
                        <i class="fa fa-book-open me-2"></i> General Ledger
                    </a>
                    <button type="button" id="openImportCustomerModal" class="btn btn-outline-success fw-bold" style="border-radius:10px; padding: 10px 18px;" data-bs-toggle="modal" data-bs-target="#importCustomerModal" data-toggle="modal" data-target="#importCustomerModal">
                        <i class="fa fa-file-excel me-2"></i> Import Customers
                    </button>
                    <a href="{{ route('parties.create', ['type' => 'Customer']) }}" class="btn btn-primary fw-bold" style="border-radius:10px; padding: 10px 20px;">
                        <i class="fa fa-plus-circle me-2"></i> Register Customer
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:12px; border-left: 5px solid var(--success) !important;">
                    <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Quick Customer Search Bar -->
            <div class="customer-search-card mb-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3 flex-grow-1" style="min-width: 320px; max-width: 720px;">
                        <div class="search-icon-circle" title="Click to search customers">
                            <i class="fa fa-search text-primary"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <label for="customerSearchSelect" class="form-label fw-bold text-dark small mb-0">
                                    <i class="fa fa-bolt text-warning me-1"></i> Quick Customer Search
                                </label>
                                <span class="text-muted" style="font-size: 0.72rem;">Search by Name, Urdu, ID, Mobile, or Zone</span>
                            </div>
                            <select id="customerSearchSelect" class="form-control" style="width: 100%;">
                                <option value=""></option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}"
                                        data-name="{{ $c->customer_name }}"
                                        data-name-ur="{{ $c->customer_name_ur ?? '' }}"
                                        data-code="{{ $c->customer_id }}"
                                        data-mobile="{{ $c->mobile ?? '' }}"
                                        data-contact="{{ $c->contact_person ?? '' }}"
                                        data-zone="{{ $c->zone ?? '' }}"
                                        data-cnic="{{ $c->cnic ?? '' }}"
                                        data-ntn="{{ $c->ntn_no ?? '' }}"
                                        data-status="{{ strtolower($c->status ?? 'active') }}">
                                        {{ $c->customer_name }} [{{ $c->customer_id }}]{{ $c->mobile ? ' • ' . $c->mobile : '' }}{{ $c->zone ? ' • ' . $c->zone : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Filtered State Actions -->
                    <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                        <div id="quickCustomerActions" style="display: none;" class="d-flex gap-2 align-items-center">
                            <a id="btnQuickLedger" href="#" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" style="border-radius: 8px;">
                                <i class="fa fa-book-open me-1"></i> General Ledger
                            </a>
                            <a id="btnQuickEdit" href="#" class="btn btn-sm btn-outline-info fw-bold shadow-sm" style="border-radius: 8px;">
                                <i class="fa fa-edit me-1"></i> Edit Customer
                            </a>
                        </div>
                        <button type="button" id="btnResetCustomerSearch" class="btn btn-sm btn-outline-secondary fw-bold" style="border-radius: 8px; display: none;">
                            <i class="fa fa-times me-1"></i> Show All Customers
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Table Wrapper -->
            <div class="table-container">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Identity & Type</th>
                            <th>Contact Node</th>
                            <th>Location & Zone</th>
                            <th>Legal / Tax / Licenses</th>
                            <th>Financial Summary</th>
                            <th class="text-center">Account</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr class="customer-row" id="customer-row-{{ $customer->id }}" data-customer-id="{{ $customer->id }}">
                                <!-- Identity -->
                                <td>
                                    <div class="identity-group">
                                        <span class="name">{{ $customer->customer_name }}</span>
                                        @if($customer->customer_name_ur)
                                            <span class="text-urdu">{{ $customer->customer_name_ur }}</span>
                                        @endif
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="id-tag">ID: {{ $customer->customer_id }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem;">{{ $customer->category ?? 'General' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div class="contact-group">
                                        <div class="item"><i class="fa fa-phone-alt"></i> <b>{{ $customer->mobile }}</b></div>
                                        @if($customer->contact_person)
                                            <div class="item"><i class="fa fa-user"></i> {{ $customer->contact_person }}</div>
                                        @endif
                                        @if($customer->email_address)
                                            <div class="item"><i class="fa fa-envelope"></i> <small>{{ $customer->email_address }}</small></div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Location -->
                                <td>
                                    <div style="font-size:0.85rem;">
                                        <div class="mb-1 fw-bold text-dark">{{ $customer->zone ?? '(No Zone)' }}</div>
                                        <div class="text-muted" style="line-height:1.4; max-width: 200px;">
                                            {{ Str::limit($customer->address, 60) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Legal / Tax -->
                                <td>
                                    <div class="legal-group">
                                        @if($customer->cnic)
                                            <span class="legal-badge active" title="CNIC Number">ID: {{ $customer->cnic }}</span>
                                        @endif
                                        @if($customer->ntn_no)
                                            <span class="legal-badge active" title="NTN Number">NTN: {{ $customer->ntn_no }}</span>
                                        @endif
                                        @if($customer->gst_no)
                                            <span class="legal-badge" title="GST Number">GST: {{ $customer->gst_no }}</span>
                                        @endif
                                        @if($customer->dsl_no)
                                            <span class="legal-badge" title="Drug Sale License">DSL: {{ $customer->dsl_no }}</span>
                                        @endif
                                        @if($customer->drap_no)
                                            <span class="legal-badge" title="Medical Registration">DRAP: {{ $customer->drap_no }}</span>
                                        @endif
                                        <span class="legal-badge text-uppercase">{{ $customer->filer_type ?? 'Non-Filer' }}</span>
                                    </div>
                                </td>

                                <!-- Financial -->
                                <td>
                                    <div class="financial-group">
                                        @php
                                            $ob = floatval($customer->opening_balance ?? 0);
                                            $dr = floatval($customer->debit ?? 0);
                                            $cr = floatval($customer->credit ?? 0);
                                            $bal = $ob + $dr - $cr;
                                        @endphp
                                        @if($bal > 0)
                                            <div class="balance balance-debit">RS. {{ number_format($bal, 2) }} <small>(Dr)</small></div>
                                        @elseif($bal < 0)
                                            <div class="balance balance-credit">RS. {{ number_format(abs($bal), 2) }} <small>(Cr)</small></div>
                                        @else
                                            <div class="balance text-muted">RS. 0.00</div>
                                        @endif

                                        <div class="limit-info">
                                            Limit: RS. {{ number_format($customer->balance_range ?? 0, 0) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status & Branch -->
                                <td class="text-center">
                                    <div class="mb-2">
                                        <span class="badge-premium status-{{ strtolower($customer->status) }}">
                                            {{ $customer->status }}
                                        </span>
                                    </div>
                                    <span class="text-muted" style="font-size:0.65rem; font-weight:700;">
                                        {{ $customer->branch?->abr ?? 'HO' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-flex">
                                        @can('customers.edit')
                                            <a href="{{ route('customers.toggleStatus', $customer->id) }}"
                                                class="btn-status-toggle {{ $customer->status === 'active' ? 'active' : 'inactive' }}"
                                                title="Toggle Active Status">
                                                <i class="fa-solid {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </a>
                                        @endcan

                                        @include('admin_panel.partials.action_buttons', [
                                            'editRoute' => route('parties.edit', [$customer->id, 'type' => 'Customer']),
                                            'deleteRoute' => route('customers.destroy', $customer->id),
                                            'editIsLink' => true,
                                            'permissions' => ['edit' => 'customers.edit', 'delete' => 'customers.delete'],
                                            'dataId' => $customer->id,
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="noCustomerFoundRow" style="display: none;">
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <div class="mb-3 text-secondary opacity-50">
                                        <i class="fa fa-search fa-3x"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">No matching customer found</h6>
                                    <p class="text-muted small mb-3">Try searching with a different name, customer ID, or phone number.</p>
                                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3" style="border-radius: 8px;" onclick="$('#customerSearchSelect').val('').trigger('change');">
                                        <i class="fa fa-rotate-left me-1"></i> Show All Customers
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Meta -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small fw-bold" id="customerShowingCount">
                    Showing {{ $customers->count() }} registered customers
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('customers.inactive') }}" class="btn btn-sm btn-light border fw-bold text-secondary" style="border-radius:8px;">
                        <i class="fa fa-eye-slash me-1"></i> View Inactive Archive
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Import Modal -->
    <div class="modal fade" id="importCustomerModal" tabindex="-1" aria-labelledby="importCustomerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 18px 24px;">
                    <h5 class="modal-title fw-bold text-white mb-0" id="importCustomerModalLabel">
                        <i class="fa fa-file-excel me-2"></i> Import Customers via Spreadsheet
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="customerImportForm" action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" onsubmit="return false;">
                    @csrf
                    <div class="modal-body p-4">
                        <!-- Step 1: Download Template -->
                        <div class="p-3 mb-4 rounded-3 border" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold text-success mb-1"><i class="fa fa-info-circle me-1"></i> Step 1: Download Standard Template</h6>
                                    <p class="small text-muted mb-0">Use our pre-formatted Excel template for maximum compatibility.</p>
                                </div>
                                <a href="{{ route('customers.download-template') }}" class="btn btn-success btn-sm fw-bold shadow-sm" style="border-radius: 8px;">
                                    <i class="fa fa-download me-1"></i> Download Template (.xlsx)
                                </a>
                            </div>
                        </div>

                        <!-- Auto-Fill Dummy Data Toggle Switch -->
                        <div class="form-check form-switch p-3 mb-4 rounded-3 border" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="customer_auto_fill_dummy" name="auto_fill_dummy" value="1" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark mb-0" for="customer_auto_fill_dummy" style="cursor: pointer;">
                                    Auto-Fill missing Customer Name &amp; required fields with editable dummy data
                                </label>
                            </div>
                            <p class="small text-muted mt-1 mb-0 ms-5">
                                When enabled, rows missing required customer names will automatically be populated with editable dummy names like <code>[DUMMY] Customer Row 1</code> so the import completes without errors.
                            </p>
                        </div>

                        <!-- Step 2: Upload File -->
                        <div class="mb-3">
                            <label for="customer_import_file" class="form-label fw-bold text-dark">
                                Step 2: Upload Excel / CSV File <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control form-control-lg" id="customer_import_file" name="file" accept=".csv, .xlsx, .xls" required style="border-radius: 10px;">
                            <small class="text-muted mt-1 d-block">Supported file formats: <strong>.xlsx, .xls, .csv</strong></small>
                        </div>
                    </div>

                    <div class="modal-footer bg-light px-4 py-3">
                        <button type="button" class="btn btn-secondary fw-bold px-4" data-bs-dismiss="modal" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                        <button type="button" class="btn btn-success fw-bold px-4 shadow-sm" id="btnCustomerImportSubmit" onclick="doCustomerImport()" style="border-radius: 8px;">
                            <i class="fa fa-file-import me-1"></i> Import Customers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Use window.onload so jQuery & Bootstrap are fully available
        window.addEventListener('load', function () {

            // ─── Open Modal ──────────────────────────────────────────────────────
            var openBtn = document.getElementById('openImportCustomerModal');
            if (openBtn) {
                openBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Bootstrap 4 uses jQuery .modal('show')
                    try {
                        $('#importCustomerModal').modal('show');
                    } catch(err) {
                        // Fallback for Bootstrap 5
                        if (typeof bootstrap !== 'undefined') {
                            new bootstrap.Modal(document.getElementById('importCustomerModal')).show();
                        }
                    }
                });
            }
        });

        // ─── AJAX Import (plain JS, no jQuery dependency) ─────────────────────
        var isBranchConfirmed = false;
        var isDummyConfirmed = false;

        function doCustomerImport(isRetry) {
            var form = document.getElementById('customerImportForm');
            var fileInput = document.getElementById('customer_import_file');

            if (!fileInput || !fileInput.files.length) {
                Swal.fire({ icon: 'warning', title: 'No File Selected', text: 'Please select a CSV or Excel file first.', confirmButtonColor: '#f59e0b' });
                return;
            }

            if (!isRetry) {
                isBranchConfirmed = false;
                isDummyConfirmed = false;
            }

            var btn = document.getElementById('btnCustomerImportSubmit');
            var origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Importing...';

            var formData = new FormData(form);

            // Append confirmation states directly to the FormData object
            if (isBranchConfirmed) {
                formData.append('create_missing_branches', '1');
            }
            if (isDummyConfirmed || document.getElementById('customer_auto_fill_dummy').checked) {
                formData.append('auto_fill_dummy', '1');
            }

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

                // Handle missing branches confirmation
                if (result.ok && result.data && result.data.status === 'confirm_branch') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'New Branch(es) Detected',
                        html: 'The following target branch(es) do not exist in the database:<br>' +
                              '<div class="text-danger mt-2 fw-bold" style="font-size:1.1rem;background:#fef2f2;border:1px solid #fecaca;padding:8px;border-radius:8px;">' + result.data.missing_branches.join(', ') + '</div><br>' +
                              'Would you like to automatically create these branch(es) and import all customers?',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, Create & Import',
                        cancelButtonText: 'No, Cancel',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        customClass: {
                            confirmButton: 'btn btn-success fw-bold px-3 me-2',
                            cancelButton: 'btn btn-secondary px-3'
                        }
                    }).then(function (r) {
                        if (r.isConfirmed) {
                            isBranchConfirmed = true;
                            doCustomerImport(true);
                        } else {
                            isBranchConfirmed = false;
                        }
                    });
                    return;
                }

                if (result.ok) {
                    // ── Close modal (Bootstrap 4 = jQuery, Bootstrap 5 = bootstrap.Modal) ──
                    try {
                        if (typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined' && typeof bootstrap.Modal.getInstance === 'function') {
                            // Bootstrap 5
                            var bsModal = bootstrap.Modal.getInstance(document.getElementById('importCustomerModal'));
                            if (bsModal) bsModal.hide();
                        } else {
                            // Bootstrap 4 (jQuery)
                            $('#importCustomerModal').modal('hide');
                        }
                    } catch(me) {
                        try { $('#importCustomerModal').modal('hide'); } catch(e2) {}
                    }
                    form.reset();
                    isBranchConfirmed = false;
                    isDummyConfirmed = false;

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
                        '<b>⛔ Duplicates Skipped:</b> ' + duplicate + ' row(s) already exist by name, ID or mobile and were not imported again.' +
                        '</div>' : '') +

                        (dummy > 0 ?
                        '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:8px 12px;font-size:0.78rem;color:#1d4ed8;margin-top:6px">' +
                        '<b>ℹ️ Note:</b> ' + dummy + ' row(s) auto-filled with dummy names — edit from Customer Directory.' +
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
                    // ── Error response ──
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
                        // Server returned non-JSON (e.g., HTML error page)
                        var snippet = (result.raw || '').replace(/<[^>]+>/g, '').trim().substring(0, 300);
                        errMsg = 'Server returned HTTP ' + result.status + '.<br><small class="text-muted">' + (snippet || 'No details available.') + '</small>';
                    }

                    if (result.data && result.data.can_auto_fill) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Validation Errors',
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
                                isDummyConfirmed = true;
                                doCustomerImport(true);
                            } else {
                                isDummyConfirmed = false;
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Failed (HTTP ' + result.status + ')',
                            html: errMsg,
                            confirmButtonColor: '#ef4444'
                        });
                    }
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

@section('js')
<script>
    (function() {
        var initialized = false;

        function initCustomerSelect2() {
            if (initialized) return;
            if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) {
                return;
            }

            var $select = $('#customerSearchSelect');
            if (!$select.length) return;

            initialized = true;

            $select.select2({
                placeholder: '🔍 Quick search customer by Name, ID, Mobile, or Zone...',
                allowClear: true,
                width: '100%',
                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }
                    if (!data.id) {
                        return null;
                    }
                    var term = params.term.toLowerCase().trim();
                    var $el = $(data.element);
                    var searchHaystack = [
                        $el.data('name') || '',
                        $el.data('name-ur') || '',
                        $el.data('code') || '',
                        $el.data('mobile') || '',
                        $el.data('contact') || '',
                        $el.data('zone') || '',
                        $el.data('cnic') || '',
                        $el.data('ntn') || '',
                        data.text || ''
                    ].join(' ').toLowerCase();

                    return searchHaystack.indexOf(term) > -1 ? data : null;
                },
                templateResult: function(customer) {
                    if (!customer.id) {
                        return customer.text;
                    }
                    var $el = $(customer.element);
                    var name = $el.data('name') || customer.text;
                    var code = $el.data('code') || '';
                    var mobile = $el.data('mobile') || '';
                    var contact = $el.data('contact') || '';
                    var zone = $el.data('zone') || '';
                    var status = $el.data('status') || '';

                    var statusBadge = status === 'active'
                        ? '<span class="badge" style="background:#dcfce7; color:#166534; font-size:0.65rem; padding: 3px 7px; border-radius: 4px;">ACTIVE</span>'
                        : (status ? '<span class="badge" style="background:#fee2e2; color:#991b1b; font-size:0.65rem; padding: 3px 7px; border-radius: 4px;">INACTIVE</span>' : '');

                    var codeHtml = code ? '<span class="badge bg-light text-primary border" style="font-size:0.72rem; font-weight:700;">' + $('<div>').text(code).html() + '</span>' : '';
                    var mobileHtml = mobile ? '<span><i class="fa fa-phone-alt me-1 text-secondary" style="font-size:0.7rem;"></i>' + $('<div>').text(mobile).html() + '</span>' : '';
                    var contactHtml = contact ? '<span><i class="fa fa-user me-1 text-secondary" style="font-size:0.7rem;"></i>' + $('<div>').text(contact).html() + '</span>' : '';
                    var zoneHtml = zone ? '<span><i class="fa fa-map-marker-alt me-1 text-secondary" style="font-size:0.7rem;"></i>' + $('<div>').text(zone).html() + '</span>' : '';

                    var html =
                        '<div class="d-flex justify-content-between align-items-center py-1">' +
                            '<div>' +
                                '<div class="fw-bold text-dark select2-customer-title" style="font-size:0.92rem;">' + $('<div>').text(name).html() + '</div>' +
                                '<div class="d-flex align-items-center flex-wrap gap-2 mt-1 text-muted select2-customer-meta" style="font-size:0.75rem;">' +
                                    codeHtml + mobileHtml + contactHtml + zoneHtml +
                                '</div>' +
                            '</div>' +
                            '<div class="ms-2">' + statusBadge + '</div>' +
                        '</div>';

                    return $(html);
                },
                templateSelection: function(customer) {
                    if (!customer.id) {
                        return customer.text;
                    }
                    var $el = $(customer.element);
                    var name = $el.data('name') || customer.text;
                    var code = $el.data('code');
                    return name + (code ? ' (' + code + ')' : '');
                }
            });

            // Filter table on customer select
            $select.on('change', function() {
                var selectedId = $(this).val();

                if (!selectedId) {
                    $('.customer-row').show();
                    $('#noCustomerFoundRow').hide();
                    $('#btnResetCustomerSearch').hide();
                    $('#quickCustomerActions').hide();
                    $('#customerShowingCount').html('Showing {{ $customers->count() }} registered customers');
                } else {
                    $('.customer-row').hide();
                    var $targetRow = $('#customer-row-' + selectedId);

                    if ($targetRow.length) {
                        $targetRow.show();
                        $('#noCustomerFoundRow').hide();

                        $('html, body').stop().animate({
                            scrollTop: $targetRow.offset().top - 140
                        }, 300);

                        $targetRow.removeClass('row-highlight');
                        void $targetRow[0].offsetWidth;
                        $targetRow.addClass('row-highlight');

                        var editUrl = "{{ url('parties') }}/" + selectedId + "/edit?type=Customer";
                        var ledgerUrl = "{{ route('customers.ledger') }}?customer_id=" + selectedId;
                        $('#btnQuickLedger').attr('href', ledgerUrl);
                        $('#btnQuickEdit').attr('href', editUrl);
                        $('#quickCustomerActions').fadeIn(200);
                        $('#btnResetCustomerSearch').show();

                        var custName = $targetRow.find('.identity-group .name').text().trim();
                        $('#customerShowingCount').html('Showing <b>1</b> of {{ $customers->count() }} registered customers (Filtered: <span class="text-primary fw-bold">' + custName + '</span>)');
                    } else {
                        $('#noCustomerFoundRow').show();
                        $('#btnResetCustomerSearch').show();
                        $('#quickCustomerActions').hide();
                        $('#customerShowingCount').html('Showing <b>0</b> registered customers');
                    }
                }
            });

            // Reset button click
            $('#btnResetCustomerSearch').on('click', function() {
                $select.val('').trigger('change');
            });

            // Search icon click opens dropdown
            $('.search-icon-circle').on('click', function() {
                $select.select2('open');
            });

            // Keyboard shortcut '/' to focus search
            $(document).on('keydown', function(e) {
                if (e.key === '/' && !$(e.target).is('input, textarea, select')) {
                    e.preventDefault();
                    $select.select2('open');
                }
            });

            // Auto-filter if customer_id is provided in URL
            var urlParams = new URLSearchParams(window.location.search);
            var paramCustId = urlParams.get('customer_id');
            if (paramCustId) {
                $select.val(paramCustId).trigger('change');
            }
        }

        // Initialize on DOM ready
        if (typeof $ !== 'undefined') {
            $(document).ready(initCustomerSelect2);
        }
        // Fallback on window load
        window.addEventListener('load', initCustomerSelect2);
    })();
</script>
@endsection

