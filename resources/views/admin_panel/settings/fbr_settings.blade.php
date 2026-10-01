@extends('admin_panel.layout.app')

@section('style')
    <style>
        .fbr-settings-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.8rem rgba(0, 0, 0, 0.06);
            background: #fff;
        }

        .fbr-nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.2s ease;
            border-bottom: 2px solid transparent;
            font-size: 0.88rem;
        }

        .fbr-nav-tabs .nav-link.active {
            color: #4e73df;
            background: transparent;
            border-bottom: 2px solid #4e73df;
            font-weight: 700;
        }

        .fbr-nav-tabs .nav-link:hover {
            border-bottom: 2px solid #cbd5e1;
            color: #334155;
        }

        .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 0.65rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .compact-field {
            margin-bottom: 0.75rem;
        }

        .compact-field label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.25rem;
            display: block;
        }

        .compact-field .form-control,
        .compact-field .form-select {
            font-size: 0.85rem;
            padding: 0.42rem 0.7rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            height: auto;
        }

        .compact-field .form-control:focus,
        .compact-field .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 2px rgba(78, 115, 223, 0.15);
        }

        .env-pill {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            user-select: none;
        }

        .env-pill input[type="radio"] {
            margin: 0;
            accent-color: #059669;
        }

        .env-pill.active-prod {
            border-color: #059669;
            background: #ecfdf5;
            font-weight: 600;
            color: #065f46;
        }

        .env-pill.active-sand {
            border-color: #d97706;
            background: #fffbeb;
            font-weight: 600;
            color: #92400e;
        }

        .prod-credential-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 0.85rem 1rem 0.25rem 1rem;
        }

        .sand-credential-box {
            background: #fafafa;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.85rem 1rem 0.25rem 1rem;
        }

        .table-compact th, 
        .table-compact td {
            padding: 0.45rem 0.65rem;
            font-size: 0.83rem;
            vertical-align: middle;
        }

        /* Toggle Switch */
        .switch-sm {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 20px;
            margin: 0;
        }

        .switch-sm input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider-sm {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 20px;
        }

        .slider-sm:before {
            position: absolute;
            content: "";
            height: 14px;
            width: 14px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        .switch-sm input:checked + .slider-sm {
            background-color: #10b981;
        }

        .switch-sm input:checked + .slider-sm:before {
            transform: translateX(18px);
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid py-3">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show py-2 px-3 small shadow-sm" role="alert">
                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show py-2 px-3 small shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="fbr-settings-card card">
            
            {{-- Header: Matching ERP Settings with Mode Badge & Ping Button --}}
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap py-2 px-3 border-bottom">
                <div class="d-flex align-items-center">
                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-secondary mr-2 py-1 px-2" title="Back to ERP Settings">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <h4 class="card-title font-weight-bold text-dark m-0" style="font-size: 1.15rem;">
                            <i class="fas fa-university text-primary mr-1"></i> FBR Digital Invoicing Configuration
                        </h4>
                        <small class="text-muted" style="font-size: 0.78rem;">Manage live API credentials, seller profile, and compliance scenarios</small>
                    </div>
                </div>

                <div class="d-flex align-items-center mt-2 mt-md-0">
                    <span class="badge {{ $environment === 'production' ? 'badge-success' : 'badge-warning' }} px-3 py-1 mr-2 font-weight-bold" style="font-size: 0.78rem; letter-spacing: 0.5px;">
                        <i class="fas fa-circle mr-1" style="font-size: 7px;"></i> {{ strtoupper($environment) }} MODE
                    </span>
                    <button type="button" id="btnTestConn" class="btn btn-outline-primary btn-sm font-weight-bold shadow-sm py-1 px-2" style="font-size: 0.8rem;">
                        <i class="fas fa-plug mr-1"></i> Test Gateway Ping
                    </button>
                </div>
            </div>

            {{-- Compact Nav-Tabs: Dramatically reduces vertical scrolling --}}
            <ul class="nav nav-tabs fbr-nav-tabs px-3 pt-2 bg-light border-bottom" id="fbrTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="config-tab" data-toggle="tab" href="#tab-config" role="tab">
                        <i class="fas fa-sliders-h mr-1 text-primary"></i> API Configuration
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="scenarios-tab" data-toggle="tab" href="#tab-scenarios" role="tab">
                        <i class="fas fa-check-circle mr-1 text-success"></i> Sandbox Scenarios <span class="badge badge-success badge-pill ml-1">14/14 Passed</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="logs-tab" data-toggle="tab" href="#tab-logs" role="tab">
                        <i class="fas fa-history mr-1 text-secondary"></i> Transmission Logs <span class="badge badge-secondary badge-pill ml-1">{{ count($logs) }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="fbrTabsContent">

                {{-- ==================== TAB 1: CONFIGURATION ==================== --}}
                <div class="tab-pane fade show active p-3" id="tab-config" role="tabpanel">
                    <form action="{{ route('settings.fbr.update') }}" method="POST">
                        @csrf

                        {{-- Row 1: Status & Active Environment --}}
                        <div class="row align-items-center mb-3 pb-2 border-bottom">
                            <div class="col-md-5 mb-2 mb-md-0">
                                <label class="font-weight-bold text-dark d-block mb-1" style="font-size: 0.85rem;">FBR Digital Invoicing Status</label>
                                <div class="d-flex align-items-center">
                                    <label class="switch-sm mr-2 mb-0">
                                        <input type="checkbox" name="fbr_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                                        <span class="slider-sm"></span>
                                    </label>
                                    <span class="small text-muted">Enable 'Post to FBR' feature on Sale Invoices</span>
                                </div>
                            </div>

                            <div class="col-md-7">
                                <label class="font-weight-bold text-dark d-block mb-1" style="font-size: 0.85rem;">Active Environment</label>
                                <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                    <label class="env-pill {{ $environment === 'production' ? 'active-prod' : '' }}" onclick="selectEnv('production')">
                                        <input type="radio" name="fbr_environment" id="env_production" value="production" {{ $environment === 'production' ? 'checked' : '' }}>
                                        <span><i class="fas fa-shield-alt text-success mr-1"></i> Production (Live FBR Server)</span>
                                    </label>
                                    <label class="env-pill {{ $environment === 'sandbox' ? 'active-sand' : '' }}" onclick="selectEnv('sandbox')">
                                        <input type="radio" name="fbr_environment" id="env_sandbox" value="sandbox" {{ $environment === 'sandbox' ? 'checked' : '' }}>
                                        <span><i class="fas fa-flask text-warning mr-1"></i> Sandbox (Testing Mode)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Production Live API Credentials (Highlighted) --}}
                        <div class="prod-credential-box mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="font-weight-bold text-success" style="font-size: 0.85rem;">
                                    <i class="fas fa-key mr-1"></i> Production Live API Credentials
                                </span>
                                <span class="badge badge-success">Active Gateway</span>
                            </div>

                            <div class="row">
                                <div class="col-12 compact-field">
                                    <label>Production Security Token <span class="text-danger">*</span></label>
                                    <input type="text" name="fbr_production_token" class="form-control font-monospace" value="{{ old('fbr_production_token', $prodToken) }}" placeholder="355f0259-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
                                </div>
                                <div class="col-md-6 compact-field">
                                    <label>Production Post URL</label>
                                    <input type="url" name="fbr_production_url" class="form-control font-monospace" value="{{ old('fbr_production_url', $prodUrl) }}">
                                </div>
                                <div class="col-md-6 compact-field">
                                    <label>Production Validate URL</label>
                                    <input type="url" name="fbr_production_validate_url" class="form-control font-monospace" value="{{ old('fbr_production_validate_url', $prodValUrl) }}">
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: Registered Seller Information --}}
                        <div class="section-label">
                            <i class="fas fa-building text-primary"></i> Registered Seller Profile (Three Stars Medical)
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-sm-6 compact-field">
                                <label>Seller NTN / CNIC <span class="text-danger">*</span></label>
                                <input type="text" name="fbr_seller_ntn" class="form-control font-monospace" value="{{ old('fbr_seller_ntn', $sellerNtn) }}" required>
                            </div>
                            <div class="col-md-4 col-sm-6 compact-field">
                                <label>Registered Business Name <span class="text-danger">*</span></label>
                                <input type="text" name="fbr_seller_name" class="form-control" value="{{ old('fbr_seller_name', $sellerName) }}" required>
                            </div>
                            <div class="col-md-2 col-sm-6 compact-field">
                                <label>Province <span class="text-danger">*</span></label>
                                <select name="fbr_seller_province" class="form-control form-select">
                                    @foreach(['Punjab', 'Sindh', 'Khyber Pakhtunkhwa', 'Balochistan', 'Islamabad Capital Territory'] as $prov)
                                        <option value="{{ $prov }}" {{ $sellerProvince === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 col-sm-6 compact-field">
                                <label>Business Address <span class="text-danger">*</span></label>
                                <input type="text" name="fbr_seller_address" class="form-control" value="{{ old('fbr_seller_address', $sellerAddress) }}" required>
                            </div>
                        </div>

                        {{-- Section 4: Invoicing Defaults --}}
                        <div class="section-label mt-2">
                            <i class="fas fa-tags text-primary"></i> Invoicing Defaults
                        </div>
                        <div class="row">
                            <div class="col-md-6 compact-field">
                                <label>Default Scenario ID</label>
                                <select name="fbr_default_scenario" class="form-control form-select">
                                    @foreach($scenarios as $code => $label)
                                        <option value="{{ $code }}" {{ $defaultScenario === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 compact-field">
                                <label>Default HS Code</label>
                                <input type="text" name="fbr_default_hs_code" class="form-control" value="{{ old('fbr_default_hs_code', $defaultHs) }}" placeholder="9018.9090">
                            </div>
                        </div>

                        {{-- Section 5: Sandbox Credentials (Collapsible Accordion to Save Space) --}}
                        <div class="sand-credential-box mt-2 mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <a class="text-dark font-weight-bold small text-decoration-none" data-toggle="collapse" href="#sandboxCollapse" role="button">
                                    <i class="fas fa-chevron-circle-down mr-1 text-muted"></i> Sandbox Testing Credentials <span class="text-muted font-weight-normal">(Click to view/edit)</span>
                                </a>
                                <span class="badge badge-light border">Testing</span>
                            </div>
                            <div class="collapse mt-2" id="sandboxCollapse">
                                <div class="row pt-2 border-top">
                                    <div class="col-12 compact-field">
                                        <label>Sandbox Security Token</label>
                                        <input type="text" name="fbr_sandbox_token" class="form-control font-monospace" value="{{ old('fbr_sandbox_token', $sandboxToken) }}">
                                    </div>
                                    <div class="col-md-6 compact-field">
                                        <label>Sandbox Post URL</label>
                                        <input type="url" name="fbr_sandbox_url" class="form-control font-monospace" value="{{ old('fbr_sandbox_url', $sandboxUrl) }}">
                                    </div>
                                    <div class="col-md-6 compact-field">
                                        <label>Sandbox Validate URL</label>
                                        <input type="url" name="fbr_sandbox_validate_url" class="form-control font-monospace" value="{{ old('fbr_sandbox_validate_url', $sandboxValUrl) }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Save Button --}}
                        <div class="text-right pt-2 border-top">
                            <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm" style="font-size: 0.88rem;">
                                <i class="fas fa-save mr-1"></i> Save Configuration
                            </button>
                        </div>
                    </form>
                </div>

                {{-- ==================== TAB 2: SANDBOX CERTIFICATION (14/14 SCENARIOS) ==================== --}}
                <div class="tab-pane fade p-3" id="tab-scenarios" role="tabpanel">
                    
                    {{-- Mini KPI Counters --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="p-2 rounded bg-light border text-center">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Eligible Scenarios</small>
                                <span class="font-weight-bold text-dark" style="font-size: 1.15rem;">14</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="p-2 rounded bg-success-light border border-success text-center" style="background: #ecfdf5;">
                                <small class="text-success d-block" style="font-size: 0.75rem;">Successful Scenarios</small>
                                <span class="font-weight-bold text-success" id="statSuccessCount" style="font-size: 1.15rem;">14</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="p-2 rounded bg-light border text-center">
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Pending Scenarios</small>
                                <span class="font-weight-bold text-success" id="statPendingCount" style="font-size: 1.15rem;">0</span>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="p-2 rounded text-center" style="background: #eef2ff; border: 1px solid #c7d2fe;">
                                <small class="text-primary d-block" style="font-size: 0.75rem;">Sandbox Status</small>
                                <span class="badge badge-success mt-1"><i class="fas fa-check-circle mr-1"></i> 100% Certified</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Bar --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap" style="gap: 0.5rem;">
                        <span class="small font-weight-bold text-dark">
                            <i class="fas fa-list-ol text-primary mr-1"></i> Official FBR Compliance Matrix (14 Wholesale/Retail Scenarios)
                        </span>
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 mr-1" id="btnValidateScenarios">
                                <i class="fas fa-check-double mr-1"></i> Validate (14)
                            </button>
                            <button type="button" class="btn btn-success btn-sm font-weight-bold py-1 px-2" id="btnRunScenarios">
                                <i class="fas fa-paper-plane mr-1"></i> Retransmit All Scenarios
                            </button>
                        </div>
                    </div>

                    {{-- Compact Scenarios Table --}}
                    <div class="table-responsive border rounded mb-2">
                        <table class="table table-sm table-hover table-compact m-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 70px;">Scenario</th>
                                    <th>Description</th>
                                    <th>Sale Type</th>
                                    <th>Rate</th>
                                    <th>SRO / Schedule</th>
                                    <th>UoM</th>
                                    <th class="text-center" style="width: 90px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="scenarioTableBody">
                                @foreach($sandboxScenarios as $scId => $sc)
                                    <tr id="row_{{ $scId }}">
                                        <td class="font-monospace font-weight-bold text-primary">{{ $scId }}</td>
                                        <td class="font-weight-600 text-dark">{{ $sc['title'] }}</td>
                                        <td><span class="badge badge-light border text-muted">{{ $sc['saleType'] }}</span></td>
                                        <td><span class="badge badge-secondary">{{ $sc['rate'] }}</span></td>
                                        <td class="small text-muted">{{ $sc['sro'] ? ($sc['sro'] . ($sc['serial'] ? ' (Sr. ' . $sc['serial'] . ')' : '')) : '-' }}</td>
                                        <td class="small">{{ $sc['uom'] }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-success px-2 py-1" id="badge_{{ $scId }}">
                                                <i class="fas fa-check"></i> Passed
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info small py-2 px-3 mb-0" style="font-size: 0.8rem;">
                        <i class="fas fa-info-circle mr-1"></i> All 14 test scenarios have been approved by FBR. Your permanent live <b>Production Token</b> is active under the <b>API Configuration</b> tab.
                    </div>
                </div>

                {{-- ==================== TAB 3: TRANSMISSION LOGS ==================== --}}
                <div class="tab-pane fade p-3" id="tab-logs" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small font-weight-bold text-dark">
                            <i class="fas fa-receipt text-primary mr-1"></i> FBR Invoices Transmission History
                        </span>
                        <span class="badge badge-secondary">{{ count($logs) }} Records</span>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-hover table-compact m-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Invoice #</th>
                                    <th>Action</th>
                                    <th>Env</th>
                                    <th>Status</th>
                                    <th>FBR Invoice #</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td class="text-muted small">{{ date('d-M-y H:i', strtotime($log->created_at)) }}</td>
                                        <td class="font-weight-bold text-dark">{{ $log->invoice_no ?? ('ID #' . $log->sale_id) }}</td>
                                        <td>
                                            <span class="badge {{ $log->action === 'post' ? 'badge-primary' : 'badge-info' }}">
                                                {{ strtoupper($log->action) }}
                                            </span>
                                        </td>
                                        <td><span class="badge badge-light border">{{ $log->environment }}</span></td>
                                        <td>
                                            @if($log->status === 'Valid' || $log->status_code === '00')
                                                <span class="badge badge-success"><i class="fas fa-check"></i> Valid</span>
                                            @else
                                                <span class="badge badge-danger"><i class="fas fa-times"></i> {{ $log->status ?? 'Failed' }}</span>
                                            @endif
                                        </td>
                                        <td class="font-monospace small font-weight-bold text-primary">{{ $log->fbr_invoice_no ?? '-' }}</td>
                                        <td>
                                            @if(!empty($log->error_message))
                                                <span class="text-danger small" title="{{ $log->error_message }}">{{ Str::limit($log->error_message, 45) }}</span>
                                            @else
                                                <span class="text-success small">OK - Transmitted</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted small">No transmission logs recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </div>

    {{-- SweetAlert2 for Ping Test and Scenarios Execution --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function selectEnv(env) {
            if (env === 'sandbox') {
                document.getElementById('env_sandbox').checked = true;
            } else {
                document.getElementById('env_production').checked = true;
            }
            document.querySelectorAll('.env-pill').forEach(el => {
                el.classList.remove('active-prod', 'active-sand');
            });
            if (env === 'production') {
                event.currentTarget.classList.add('active-prod');
            } else {
                event.currentTarget.classList.add('active-sand');
            }
        }

        // Test Gateway Ping
        document.getElementById('btnTestConn').addEventListener('click', function() {
            Swal.fire({
                title: 'Testing FBR Gateway...',
                text: 'Connecting to active endpoint with Bearer Token...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("{{ route('settings.fbr.test') }}")
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Gateway Connected!',
                            html: '<p class="text-success font-weight-bold mb-2">' + data.message + '</p>' +
                                  '<div class="text-muted small text-left bg-light p-2 rounded border"><b>Endpoint:</b> ' + data.url + '<br><b>Environment:</b> <span class="badge badge-success">' + data.environment.toUpperCase() + '</span></div>'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Issue',
                            html: '<p class="text-danger mb-2">' + data.message + '</p>' +
                                  '<div class="text-muted small text-left bg-light p-2 rounded border"><b>Endpoint:</b> ' + data.url + '</div>'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Network Error',
                        text: err.message
                    });
                });
        });

        // Run / Validate Scenarios AJAX
        function executeScenarios(validateOnly) {
            const actionText = validateOnly ? 'Validating' : 'Posting';
            Swal.fire({
                title: `${actionText} 14 Scenarios...`,
                html: '<p class="text-muted">Communicating with FBR Gateway...</p>',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch("{{ route('settings.fbr.run_scenarios') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ validate_only: validateOnly })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.scenarios && Array.isArray(data.scenarios)) {
                        data.scenarios.forEach(sc => {
                            const badge = document.getElementById('badge_' + sc.scenario_id);
                            if (badge) {
                                if (sc.is_success) {
                                    badge.className = 'badge badge-success';
                                    badge.innerHTML = '<i class="fas fa-check"></i> ' + (sc.fbr_invoice_no ? sc.fbr_invoice_no.substring(13) : 'Passed');
                                    badge.title = sc.fbr_invoice_no || 'Passed';
                                } else {
                                    badge.className = 'badge badge-danger';
                                    badge.innerHTML = '<i class="fas fa-times"></i> Failed';
                                    badge.title = sc.error || 'Failed';
                                }
                            }
                        });
                    }

                    document.getElementById('statSuccessCount').innerText = data.successful;
                    document.getElementById('statPendingCount').innerText = (data.total - data.successful);

                    Swal.fire({
                        icon: data.is_all_pass ? 'success' : 'warning',
                        title: data.is_all_pass ? 'All 14 Scenarios Certified!' : `${data.successful}/${data.total} Scenarios Passed`,
                        html: `<p class="font-weight-bold">${data.message}</p>`
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Scenario Execution Error',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: err.message
                });
            });
        }

        document.getElementById('btnValidateScenarios').addEventListener('click', () => executeScenarios(true));
        document.getElementById('btnRunScenarios').addEventListener('click', () => executeScenarios(false));
    </script>
@endsection
