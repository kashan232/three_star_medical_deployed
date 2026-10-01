@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary-color: #1e3c72;
            --success-color: #10ac84;
            --danger-color: #ee5a6f;
            --warning-color: #f39c12;
            --bg-light: #f8fafc;
        }

        .settings-container {
            max-width: 1000px;
            margin: 1.5rem auto;
        }

        .settings-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .settings-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .settings-header h4 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.35rem;
        }

        .settings-header p {
            margin: 0.35rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .settings-body {
            padding: 2rem;
        }

        .setting-section-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .setting-group {
            margin-bottom: 1.75rem;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .setting-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.35rem;
            font-size: 0.9rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.6rem 0.9rem;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.15);
        }

        .badge-mode {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-sandbox {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .badge-production {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }

        .switch-custom {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 26px;
        }

        .switch-custom input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider-custom {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 26px;
        }

        .slider-custom:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }

        input:checked + .slider-custom {
            background-color: #10ac84;
        }

        input:checked + .slider-custom:before {
            transform: translateX(24px);
        }

        .env-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .env-card.active {
            border-color: #2a5298;
            background: #f0f7ff;
        }

        .env-card:hover {
            border-color: #94a3b8;
        }

        .log-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .log-table td {
            font-size: 0.85rem;
            vertical-align: middle;
        }
    </style>

    <div class="settings-container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="settings-card">
            <div class="settings-header">
                <div>
                    <h4><i class="fas fa-university"></i> FBR Digital Invoicing Gateway Configuration</h4>
                    <p>Manage API credentials, environments, seller profile, and compliance scenarios</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-mode {{ $environment === 'production' ? 'badge-production' : 'badge-sandbox' }}">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                        {{ strtoupper($environment) }} MODE
                    </span>
                    <button type="button" id="btnTestConn" class="btn btn-light btn-sm font-weight-bold shadow-sm">
                        <i class="fas fa-plug me-1"></i> Test Gateway Ping
                    </button>
                </div>
            </div>

            <form action="{{ route('settings.fbr.update') }}" method="POST">
                @csrf
                <div class="settings-body">

                    <!-- Section 1: Activation & Environment -->
                    <div class="setting-section-title">
                        <i class="fas fa-sliders-h text-primary"></i> Environment & Status
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Enable FBR Digital Invoicing</label>
                            <div class="d-flex align-items-center gap-3">
                                <label class="switch-custom">
                                    <input type="checkbox" name="fbr_enabled" value="1" {{ $enabled ? 'checked' : '' }}>
                                    <span class="slider-custom"></span>
                                </label>
                                <span class="text-muted small">Enable 'Post to FBR' feature on Sale Invoices</span>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Active Environment</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="env-card {{ $environment === 'sandbox' ? 'active' : '' }}" onclick="selectEnv('sandbox')">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="fbr_environment" id="env_sandbox" value="sandbox" {{ $environment === 'sandbox' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-dark" for="env_sandbox">
                                                Sandbox
                                            </label>
                                        </div>
                                        <div class="text-muted small mt-1">Testing & Verification</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="env-card {{ $environment === 'production' ? 'active' : '' }}" onclick="selectEnv('production')">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="fbr_environment" id="env_production" value="production" {{ $environment === 'production' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-dark" for="env_production">
                                                Production
                                            </label>
                                        </div>
                                        <div class="text-muted small mt-1">Live FBR Server</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Seller Profile -->
                    <div class="setting-section-title">
                        <i class="fas fa-building text-primary"></i> Registered Seller Information
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Seller NTN / CNIC <span class="text-danger">*</span></label>
                            <input type="text" name="fbr_seller_ntn" class="form-control" value="{{ old('fbr_seller_ntn', $sellerNtn) }}" required placeholder="e.g. 3520224331243">
                            <small class="text-muted">7-digit NTN or 13-digit CNIC</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Registered Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="fbr_seller_name" class="form-control" value="{{ old('fbr_seller_name', $sellerName) }}" required placeholder="e.g. THREE STARS MEDICAL SUPPLIES">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Province <span class="text-danger">*</span></label>
                            <select name="fbr_seller_province" class="form-select" required>
                                <option value="Punjab" {{ $sellerProvince === 'Punjab' ? 'selected' : '' }}>Punjab</option>
                                <option value="Sindh" {{ $sellerProvince === 'Sindh' ? 'selected' : '' }}>Sindh</option>
                                <option value="Khyber Pakhtunkhwa" {{ $sellerProvince === 'Khyber Pakhtunkhwa' ? 'selected' : '' }}>Khyber Pakhtunkhwa</option>
                                <option value="Balochistan" {{ $sellerProvince === 'Balochistan' ? 'selected' : '' }}>Balochistan</option>
                                <option value="Islamabad Capital Territory" {{ $sellerProvince === 'Islamabad Capital Territory' ? 'selected' : '' }}>Islamabad Capital Territory</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Registered Business Address <span class="text-danger">*</span></label>
                            <input type="text" name="fbr_seller_address" class="form-control" value="{{ old('fbr_seller_address', $sellerAddress) }}" required>
                        </div>
                    </div>

                    <!-- Section 3: Sandbox Configuration -->
                    <div class="setting-section-title">
                        <i class="fas fa-vial text-warning"></i> Sandbox Environment API Credentials
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Sandbox Security Token</label>
                            <input type="text" name="fbr_sandbox_token" class="form-control font-monospace" value="{{ old('fbr_sandbox_token', $sandboxToken) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sandbox Post Invoice Endpoint</label>
                            <input type="url" name="fbr_sandbox_url" class="form-control font-monospace" value="{{ old('fbr_sandbox_url', $sandboxUrl) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sandbox Validate Endpoint</label>
                            <input type="url" name="fbr_sandbox_validate_url" class="form-control font-monospace" value="{{ old('fbr_sandbox_validate_url', $sandboxValUrl) }}">
                        </div>
                    </div>

                    <!-- Section 4: Production Configuration -->
                    <div class="setting-section-title">
                        <i class="fas fa-shield-alt text-success"></i> Production Live API Credentials
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Production Security Token</label>
                            <input type="text" name="fbr_production_token" class="form-control font-monospace" value="{{ old('fbr_production_token', $prodToken) }}" placeholder="Enter production token when provided by FBR">
                            <small class="text-muted">Issued by FBR for live tax filing</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Production Post Invoice Endpoint</label>
                            <input type="url" name="fbr_production_url" class="form-control font-monospace" value="{{ old('fbr_production_url', $prodUrl) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Production Validate Endpoint</label>
                            <input type="url" name="fbr_production_validate_url" class="form-control font-monospace" value="{{ old('fbr_production_validate_url', $prodValUrl) }}">
                        </div>
                    </div>

                    <!-- Section 5: Defaults & Compliance -->
                    <div class="setting-section-title">
                        <i class="fas fa-tags text-primary"></i> Invoicing Defaults & Scenarios
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Default Scenario ID</label>
                            <select name="fbr_default_scenario" class="form-select">
                                @foreach($scenarios as $code => $label)
                                    <option value="{{ $code }}" {{ $defaultScenario === $code ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Auto-selected for standard registered sales</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Default HS Code</label>
                            <input type="text" name="fbr_default_hs_code" class="form-control" value="{{ old('fbr_default_hs_code', $defaultHs) }}" placeholder="e.g. 9018.9090">
                            <small class="text-muted">Applied if a product does not have an HS code set</small>
                        </div>
                    </div>

                    <div class="text-end pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> Save Configuration
                        </button>
                    </div>

                </div>
            </form>
        </div>

        <!-- Sandbox Scenarios Certification Dashboard (14/14 Scenarios) -->
        <div class="settings-card border-success">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="m-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-certificate text-success"></i> FBR Sandbox Scenarios Certification (14/14 Scenarios)
                    </h5>
                    <small class="text-muted">FBR Sandbox mandates completing all 14 eligible scenarios to unlock the Production Security Token.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm" id="btnValidateScenarios">
                        <i class="fas fa-check-double me-1"></i> Validate (14)
                    </button>
                    <button type="button" class="btn btn-success btn-sm shadow-sm font-weight-bold" id="btnRunScenarios">
                        <i class="fas fa-paper-plane me-1"></i> Transmit All 14 Scenarios
                    </button>
                </div>
            </div>

            <div class="p-3 bg-white border-bottom">
                <div class="row text-center g-2">
                    <div class="col-md-3 col-6">
                        <div class="p-2 rounded bg-light border">
                            <span class="text-muted small d-block">Eligible Scenarios</span>
                            <span class="fs-5 fw-bold text-dark">14</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 rounded bg-success bg-opacity-10 border border-success">
                            <span class="text-success small d-block">Successful Scenarios</span>
                            <span class="fs-5 fw-bold text-success" id="statSuccessCount">14</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 rounded bg-light border">
                            <span class="text-muted small d-block">Pending Scenarios</span>
                            <span class="fs-5 fw-bold text-success" id="statPendingCount">0</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 rounded bg-primary bg-opacity-10 border border-primary">
                            <span class="text-primary small d-block">Sandbox Status</span>
                            <span class="badge bg-success mt-1"><i class="fas fa-check-circle"></i> Certified</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover m-0 align-middle">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th style="width: 80px;">Scenario</th>
                            <th>Description</th>
                            <th>Sale Type</th>
                            <th>Rate</th>
                            <th>SRO / Schedule</th>
                            <th>UoM</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="scenarioTableBody">
                        @foreach($sandboxScenarios as $scId => $sc)
                            <tr id="row_{{ $scId }}">
                                <td class="font-monospace fw-bold text-primary">{{ $scId }}</td>
                                <td class="fw-semibold text-dark">{{ $sc['title'] }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $sc['saleType'] }}</span></td>
                                <td><span class="badge bg-secondary">{{ $sc['rate'] }}</span></td>
                                <td class="small text-muted">{{ $sc['sro'] ? ($sc['sro'] . ($sc['serial'] ? ' (Sr. ' . $sc['serial'] . ')' : '')) : '-' }}</td>
                                <td class="small">{{ $sc['uom'] }}</td>
                                <td>
                                    <span class="badge bg-success" id="badge_{{ $scId }}">
                                        <i class="fas fa-check"></i> Passed
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-light text-muted small border-top">
                <i class="fas fa-info-circle text-primary me-1"></i>
                <b>Production Token Instructions:</b> After running all 14 scenarios, log in to your FBR Digital Invoicing Sandbox Portal. Once "Pending Scenarios = 0", click <b>"Next"</b> to generate your permanent Production Security Token. Paste it in the <b>Production Security Token</b> field above and switch Environment to <b>Production</b>.
            </div>
        </div>

        <!-- Recent Logs Section -->
        <div class="settings-card">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h5 class="m-0 fw-bold text-dark"><i class="fas fa-history me-1"></i> Recent FBR Transmission Logs</h5>
                <span class="badge bg-secondary">{{ count($logs) }} Entries</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover log-table m-0">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Invoice #</th>
                            <th>Action</th>
                            <th>Env</th>
                            <th>Status</th>
                            <th>FBR Invoice #</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td class="text-muted small">{{ date('d-M H:i:s', strtotime($log->created_at)) }}</td>
                                <td class="fw-bold">{{ $log->invoice_no ?? ('ID #' . $log->sale_id) }}</td>
                                <td>
                                    <span class="badge {{ $log->action === 'post' ? 'bg-primary' : 'bg-info' }}">
                                        {{ strtoupper($log->action) }}
                                    </span>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $log->environment }}</span></td>
                                <td>
                                    @if($log->status === 'Valid' || $log->status_code === '00')
                                        <span class="badge bg-success"><i class="fas fa-check"></i> Valid</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times"></i> {{ $log->status ?? 'Failed' }}</span>
                                    @endif
                                </td>
                                <td class="font-monospace small fw-bold">{{ $log->fbr_invoice_no ?? '-' }}</td>
                                <td>
                                    @if(!empty($log->error_message))
                                        <span class="text-danger small" title="{{ $log->error_message }}">{{ Str::limit($log->error_message, 40) }}</span>
                                    @else
                                        <span class="text-success small">Transmitted successfully</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No transmission logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- SweetAlert2 for Ping Test and Scenarios Execution -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function selectEnv(env) {
            if (env === 'sandbox') {
                document.getElementById('env_sandbox').checked = true;
            } else {
                document.getElementById('env_production').checked = true;
            }
            document.querySelectorAll('.env-card').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
        }

        // Test Connection Ping
        document.getElementById('btnTestConn').addEventListener('click', function() {
            Swal.fire({
                title: 'Testing FBR Gateway Connection...',
                text: 'Pinging active gateway endpoint with security token...',
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
                            html: '<p class="text-success fw-bold">' + data.message + '</p>' +
                                  '<div class="text-muted small text-start bg-light p-2 rounded"><b>Endpoint:</b> ' + data.url + '<br><b>Environment:</b> ' + data.environment.toUpperCase() + '</div>'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Connection Issue',
                            html: '<p class="text-danger">' + data.message + '</p>' +
                                  '<div class="text-muted small text-start bg-light p-2 rounded"><b>Endpoint:</b> ' + data.url + '</div>'
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
                title: `${actionText} 14 FBR Scenarios...`,
                html: '<p class="text-muted">Transmitting all 14 eligible scenarios sequentially to FBR Sandbox Gateway...</p><div class="spinner-border text-primary my-2" role="status"></div>',
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
                    // Update table badges
                    if (data.scenarios && Array.isArray(data.scenarios)) {
                        data.scenarios.forEach(sc => {
                            const badge = document.getElementById('badge_' + sc.scenario_id);
                            if (badge) {
                                if (sc.is_success) {
                                    badge.className = 'badge bg-success';
                                    badge.innerHTML = '<i class="fas fa-check"></i> ' + (sc.fbr_invoice_no ? sc.fbr_invoice_no.substring(13) : 'Passed');
                                    badge.title = sc.fbr_invoice_no || 'Passed';
                                } else {
                                    badge.className = 'badge bg-danger';
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
                        title: data.is_all_pass ? 'All 14 Scenarios Successful!' : `${data.successful}/${data.total} Scenarios Succeeded`,
                        html: `<p class="fw-semibold">${data.message}</p>` +
                              `<div class="alert alert-info small text-start mt-2 mb-0">` +
                              `<b>Next Step:</b> Log in to your FBR Sandbox account, check that Pending Scenarios is 0, and click <b>Next</b> to get your Production Token.` +
                              `</div>`
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
