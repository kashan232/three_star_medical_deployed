@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --premium-primary: #4f46e5;
            --premium-secondary: #6366f1;
            --premium-bg: #f8fafc;
            --premium-card: #ffffff;
            --premium-border: #e2e8f0;
            --premium-text: #1e293b;
        }

        .premium-container {
            padding: 20px 0;
        }

        .premium-card {
            background: var(--premium-card);
            border-radius: 16px;
            border: 1px solid var(--premium-border);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .premium-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .card-header-modern {
            padding: 20px 24px;
            background: #fff;
            border-bottom: 1px solid var(--premium-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-modern h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--premium-text);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header-modern h3 i {
            color: var(--premium-primary);
            background: #eef2ff;
            padding: 10px;
            border-radius: 12px;
        }

        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-premium th {
            background: #f8fafc;
            padding: 16px 24px;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--premium-border);
        }

        .table-premium td {
            padding: 16px 24px;
            font-size: 0.95rem;
            color: var(--premium-text);
            border-bottom: 1px solid #f1f5f9;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff7ed;
            color: #c2410c;
        }

        .status-approved {
            background: #f0fdf4;
            color: #15803d;
        }

        .status-cleared {
            background: #eff6ff;
            color: #1d4ed8;
        }

        /* Form Modal Styling */
        .modal-content-premium {
            border-radius: 24px;
            border: none;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-header-premium {
            padding: 30px;
            border-bottom: 1px solid #f1f5f9;
            background: #fff;
            border-radius: 24px 24px 0 0;
        }

        .modal-body-premium {
            padding: 30px;
        }

        .form-group-premium {
            margin-bottom: 20px;
        }

        .label-premium {
            display: block;
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 8px;
        }

        .input-premium {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            font-size: 1rem;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .input-premium:focus {
            outline: none;
            border-color: var(--premium-primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .btn-premium {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-premium {
            background: var(--premium-primary);
            color: white;
            border: none;
        }

        .btn-primary-premium:hover {
            background: var(--premium-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .percentage-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid premium-container">

                <div class="premium-card">
                    <div class="card-header-modern">
                        <h3><i class="fa fa-file-invoice-dollar"></i> CDR Entry Management</h3>
                        <button class="btn btn-premium btn-primary-premium" data-toggle="modal" data-target="#cdrModal"
                            onclick="resetForm()">
                            <i class="fa fa-plus-circle"></i> New CDR Entry
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>City</th>
                                    <th>CDR No</th>
                                    <th>Cdr Date</th>
                                    <th>Department (Customer)</th>
                                    <th>Bank</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cdrs as $cdr)
                                    <tr>
                                        <td style="font-weight:700; color:var(--premium-primary);">{{ $cdr->code }}</td>
                                        <td>{{ $cdr->city ?? 'N/A' }}</td>
                                        <td>{{ $cdr->cdr_no }}</td>
                                        <td>{{ Carbon\Carbon::parse($cdr->cdr_date)->format('d/m/Y') }}</td>
                                        <td>{{ $cdr->customer->title ?? ($cdr->customer->customer_name ?? 'N/A') }}</td>
                                        <td>{{ $cdr->bankAccount->title ?? 'N/A' }}</td>
                                        <td style="font-weight:700;">Rs {{ number_format($cdr->amount, 2) }}</td>
                                        <td>
                                            <div class="dropdown">
                                                @if ($cdr->status !== 'CLEARED')
                                                    <button
                                                        class="status-badge status-{{ strtolower($cdr->status) }} border-0"
                                                        style="cursor: pointer;" data-toggle="dropdown" aria-haspopup="true"
                                                        aria-expanded="false">
                                                        {{ $cdr->status }} <i class="fa fa-caret-down ms-1"
                                                            style="font-size: 0.6rem;"></i>
                                                    </button>
                                                    <div class="dropdown-menu">
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                            onclick="quickUpdateStatus({{ $cdr->id }}, 'PENDING')">PENDING</a>
                                                        <a class="dropdown-item" href="javascript:void(0)"
                                                            onclick="quickUpdateStatus({{ $cdr->id }}, 'APPROVED')">APPROVED</a>
                                                        <hr class="dropdown-divider">
                                                        <a class="dropdown-item text-primary fw-bold"
                                                            href="javascript:void(0)"
                                                            onclick="openClearModal({{ $cdr->id }})">
                                                            <i class="fa fa-check-circle me-1"></i> CLEAR THIS CDR
                                                        </a>
                                                    </div>
                                                @else
                                                    <span class="status-badge status-cleared">
                                                        <i class="fa fa-check-double ms-1"></i> {{ $cdr->status }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                @if ($cdr->status !== 'CLEARED')
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                                        onclick="editCdr({{ $cdr->id }})">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-success rounded-pill px-3"
                                                        onclick="openClearModal({{ $cdr->id }})">
                                                        <i class="fa fa-check-circle"></i> Clear
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                        onclick="deleteCdr({{ $cdr->id }})">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted small"><i class="fa fa-lock"></i> Locked</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-5 text-muted">
                                            No CDR entries found. Click "New CDR Entry" to create one.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- CDR Modal -->
    <div class="modal fade" id="cdrModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content modal-content-premium">
                <div class="modal-header modal-header-premium">
                    <h5 class="modal-title" id="modalTitle" style="font-weight:800; color:var(--premium-text);">
                        <i class="fa fa-edit text-primary me-2"></i> Create CDR Entry
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="font-size: 2rem; border: none; background: transparent;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="cdrForm">
                    @csrf
                    <input type="hidden" name="id" id="cdr_id">
                    <div class="modal-body modal-body-premium">
                        <div class="grid-2">
                            <div class="form-group-premium">
                                <label class="label-premium">Code</label>
                                <input type="text" name="code" id="form_code" class="input-premium" placeholder="000"
                                    readonly>
                            </div>
                            <div class="form-group-premium">
                                <label class="label-premium">City</label>
                                <input type="text" name="city" id="form_city" class="input-premium"
                                    placeholder="Enter City">
                            </div>

                            @if (auth()->user()->isSuperAdmin())
                                <div class="form-group-premium">
                                    <label class="label-premium" style="color:var(--premium-primary);">Target Branch</label>
                                    <select name="branch_id" id="form_branch_id" class="input-premium">
                                        <option value="">Default Branch</option>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>

                        <div class="grid-2">
                            <div class="form-group-premium">
                                <label class="label-premium">CDR No</label>
                                <input type="text" name="cdr_no" id="form_cdr_no" class="input-premium"
                                    placeholder="Enter CDR Number" required>
                            </div>

                            <div class="grid-2">
                                <div class="form-group-premium">
                                    <label class="label-premium">CDR Date</label>
                                    <input type="date" name="cdr_date" id="form_cdr_date" class="input-premium"
                                        required>
                                </div>
                                <div class="form-group-premium">
                                    <label class="label-premium">Fiscal Year</label>
                                    <input type="text" name="fiscal_year" id="form_fiscal_year" class="input-premium"
                                        placeholder="e.g. 2024-25">
                                </div>
                            </div>

                            <div class="form-group-premium">
                                <label class="label-premium">Department (Bring Customer)</label>
                                <select name="customer_id" id="form_customer_id" class="input-premium select2" required>
                                    <option value="">Select Department</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">
                                            {{ $customer->title ?? $customer->customer_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group-premium">
                                <label class="label-premium">Bank (Select Account)</label>
                                <select name="account_id" id="form_account_id" class="input-premium select2" required>
                                    <option value="">Select Bank Account</option>
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->title }}
                                            ({{ $bank->account_code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid-2">
                                <div class="form-group-premium">
                                    <label class="label-premium">Percentage (%)</label>
                                    <div class="percentage-input-group">
                                        <input type="number" step="0.01" name="percentage" id="form_percentage"
                                            class="input-premium" placeholder="0.00" value="0.00">
                                        <span style="font-weight:700; color:#64748b;">%</span>
                                    </div>
                                </div>
                                <div class="form-group-premium">
                                    <label class="label-premium">Amount (Rs)</label>
                                    <input type="number" step="0.01" name="amount" id="form_amount"
                                        class="input-premium" placeholder="0.00" required>
                                </div>
                            </div>

                            <div class="grid-2">
                                <div class="form-group-premium">
                                    <label class="label-premium">Status</label>
                                    <select name="status" id="form_status" class="input-premium" required>
                                        <option value="PENDING">PENDING</option>
                                        <option value="APPROVED">APPROVED</option>
                                        <option value="CLEARED">CLEARED</option>
                                    </select>
                                </div>
                                <div class="form-group-premium">
                                    <label class="label-premium">Dated</label>
                                    <input type="date" name="dated" id="form_dated" class="input-premium">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer px-4 pb-4 border-0">
                            <button type="button" class="btn btn-premium btn-outline-secondary"
                                data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-premium btn-primary-premium">
                                <i class="fa fa-save"></i> Save Record
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Clear CDR Modal -->
    <div class="modal fade" id="clearCdrModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content modal-content-premium">
                <div class="modal-header modal-header-premium">
                    <h5 class="modal-title" style="font-weight:800; color:var(--premium-text);">
                        <i class="fa fa-check-circle text-success me-2"></i> Clear CDR Entry
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        style="border:none; background:transparent;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="clearCdrForm">
                    @csrf
                    <input type="hidden" name="cdr_id" id="clear_cdr_id">
                    <div class="modal-body modal-body-premium">
                        <p class="text-muted mb-4 small">Select the Asset/Bank account where the CDR amount is being
                            returned/cleared.</p>

                        <div class="form-group-premium">
                            <label class="label-premium">Asset Account (Chart of Accounts)</label>
                            <select name="account_id" id="clear_account_id" class="input-premium select2" required
                                style="width:100%;">
                                <option value="">Select Account</option>
                                @foreach ($assetAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->title }} ({{ $acc->account_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group-premium">
                            <label class="label-premium">Clearing Date</label>
                            <input type="date" name="cleared_date" id="clear_date" class="input-premium"
                                value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pb-4 border-0">
                        <button type="button" class="btn btn-premium btn-outline-secondary"
                            data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-premium btn-primary-premium">
                            <i class="fa fa-check"></i> Done
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function resetForm() {
            $('#cdrForm')[0].reset();
            $('#cdr_id').val('');
            $('#modalTitle').html('<i class="fa fa-plus-circle text-primary me-2"></i> Create CDR Entry');
            $('#form_status').val('PENDING');
            $('#form_code').val('{{ $nextCode }}');

            // Re-initialize select2 if used
            if ($.fn.select2) {
                $('.select2').val('').trigger('change');
            }
        }

        function editCdr(id) {
            resetForm();
            $('#modalTitle').html('<i class="fa fa-edit text-primary me-2"></i> Edit CDR Entry');

            $.get(`/cdrs/${id}/edit`, function(data) {
                $('#cdr_id').val(data.id);
                $('#form_code').val(data.code);
                $('#form_city').val(data.city);
                $('#form_cdr_no').val(data.cdr_no);
                $('#form_cdr_date').val(data.cdr_date);
                $('#form_fiscal_year').val(data.fiscal_year);
                $('#form_customer_id').val(data.customer_id).trigger('change');
                $('#form_account_id').val(data.account_id).trigger('change');
                $('#form_percentage').val(data.percentage);
                $('#form_amount').val(data.amount);
                $('#form_status').val(data.status);
                $('#form_dated').val(data.dated);
                $('#form_branch_id').val(data.branch_id);

                $('#cdrModal').modal('show');
            });
        }

        $('#cdrForm').on('submit', function(e) {
            e.preventDefault();

            const id = $('#cdr_id').val();
            const url = id ? `/cdrs/${id}/update` : `/cdrs/store`;
            const formData = $(this).serialize();

            Swal.fire({
                title: 'Processing...',
                text: 'Please wait while we save the information',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: message
                    });
                }
            });
        });

        function openClearModal(id) {
            $('#clear_cdr_id').val(id);
            $('#clearCdrModal').modal('show');

            // Initialize select2 with parent for focus fix
            setTimeout(function() {
                if ($.fn.select2) {
                    $('#clear_account_id').select2({
                        dropdownParent: $('#clearCdrModal'),
                        width: '100%'
                    });
                }
            }, 300);
        }

        $('#clearCdrForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#clear_cdr_id').val();
            const formData = $(this).serialize();

            Swal.fire({
                title: 'Processing...',
                text: 'Updating status and generating accounting entries',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: `/cdrs/${id}/clear`,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleared!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message
                    });
                }
            });
        });

        function quickUpdateStatus(id, status) {
            Swal.fire({
                title: 'Change Status?',
                text: `Are you sure you want to set this CDR to ${status}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, update'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/cdrs/${id}/update`,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            status: status,
                            // Fetch full data first or allow partial in controller?
                            // For simplicity, we trigger the edit modal if it's complex, 
                            // but here we know the status is the main thing.
                            quick_status: true
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                        icon: 'success',
                                        title: 'Updated!',
                                        text: response.message,
                                        timer: 1500,
                                        showConfirmButton: false
                                    })
                                    .then(() => {
                                        location.reload();
                                    });
                            }
                        }
                    });
                }
            });
        }

        function deleteCdr(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/cdrs/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Failed to delete the record.'
                            });
                        }
                    });
                }
            });
        }
    </script>
@endsection
