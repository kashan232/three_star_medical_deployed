@extends('admin_panel.layout.app')
@section('content')
    <style>
        /* Keep original theme colors & gradients intact */
        .type-badge-shop {
            background: linear-gradient(135deg, #7c3aed, #4f46e5);
            color: #fff;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            line-height: 1.2;
            box-shadow: 0 2px 4px rgba(124, 58, 237, 0.2);
        }
        .type-badge-warehouse {
            background: linear-gradient(135deg, #0891b2, #0369a1);
            color: #fff;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .03em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            line-height: 1.2;
            box-shadow: 0 2px 4px rgba(8, 145, 178, 0.2);
        }

        /* Layout & Table Spacing */
        .locations-table th,
        .locations-table td {
            vertical-align: middle !important;
            padding: 12px 14px !important;
        }
        .locations-table thead th {
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            background-color: #f8fafc;
            border-top: none;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .locations-table tbody tr {
            transition: background-color 0.15s ease-in-out;
        }
        .locations-table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* Action Buttons Row Alignment */
        .action-buttons-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }
        .action-buttons-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 5px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 5px;
            white-space: nowrap;
            line-height: 1.3;
        }
        .auto-managed-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            white-space: nowrap;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid">

                <!-- Page Header (Clean Flexbox Layout) -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h4 class="mb-1 font-weight-bold text-dark">Locations — Shops &amp; Warehouses</h4>
                        <p class="text-muted small mb-0">Manage all stock locations: Shops (Retail) and Warehouses (Storage)</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        @can('warehouse.create')
                            <button class="btn btn-primary px-3 py-2 shadow-sm font-weight-bold" data-toggle="modal" data-target="#warehouseModal"
                                onclick="clearWarehouse()">
                                <i class="fas fa-plus-circle mr-1"></i> Add Location
                            </button>
                        @endcan
                    </div>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <!-- Card with Responsive Table -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle datanew locations-table mb-0" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
                                        <th class="text-center" style="width: 130px;">Type</th>
                                        <th style="width: 140px;">Created By</th>
                                        @if (auth()->user()->hasRole('Super Admin'))
                                            <th style="width: 180px;">Branch</th>
                                        @endif
                                        <th style="min-width: 180px;">Name</th>
                                        <th style="min-width: 220px;">Location / Address</th>
                                        <th style="min-width: 150px;">Remarks</th>
                                        <th class="text-center" style="width: 160px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($warehouses as $key => $w)
                                        <tr>
                                            <td class="text-center font-weight-bold text-muted">{{ $key + 1 }}</td>
                                            <td class="text-center">
                                                @if($w->type === 'shop')
                                                    <span class="type-badge-shop">
                                                        <i class="fas fa-store"></i> Shop
                                                    </span>
                                                @else
                                                    <span class="type-badge-warehouse">
                                                        <i class="fas fa-warehouse"></i> Warehouse
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-muted">
                                                <i class="far fa-user text-muted mr-1"></i> {{ $w->user->name ?? 'N/A' }}
                                            </td>
                                            @if (auth()->user()->hasRole('Super Admin'))
                                                <td>
                                                    <span class="badge badge-light border text-dark px-2 py-1 font-weight-normal">
                                                        <i class="fas fa-building text-primary mr-1"></i> {{ $w->branch->name ?? ('Branch #' . $w->branch_id) }}
                                                    </span>
                                                </td>
                                            @endif
                                            <td>
                                                <span class="font-weight-bold text-dark" style="font-size: 0.92rem;">
                                                    {{ $w->warehouse_name }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(!empty($w->location))
                                                    <span class="text-dark">
                                                        <i class="fas fa-map-marker-alt text-muted mr-1"></i> {{ $w->location }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $w->remarks ?? '—' }}</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-buttons-group">
                                                    @if($w->type !== 'shop')
                                                        @can('warehouse.edit')
                                                            <button class="btn btn-primary btn-sm edit-warehouse-btn"
                                                                data-id="{{ $w->id }}"
                                                                data-name="{{ $w->warehouse_name }}"
                                                                data-location="{{ $w->location }}"
                                                                data-remarks="{{ $w->remarks }}"
                                                                data-branch="{{ $w->branch_id }}"
                                                                data-type="{{ $w->type }}"
                                                                data-toggle="modal"
                                                                data-target="#warehouseModal"
                                                                title="Edit Location">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </button>
                                                        @endcan
                                                        @can('warehouse.delete')
                                                            <button class="btn btn-danger btn-sm delete-btn"
                                                                data-url="{{ url('warehouse/delete/' . $w->id) }}"
                                                                data-msg="Are you sure you want to delete this warehouse?" data-method="get"
                                                                onclick="logoutAndDeleteFunction(this)"
                                                                title="Delete Location">
                                                                <i class="fas fa-trash-alt"></i> Delete
                                                            </button>
                                                        @endcan
                                                    @else
                                                        <span class="auto-managed-badge">
                                                            <i class="fas fa-lock text-muted"></i> Auto-managed
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Add / Edit Modal -->
    <div class="modal fade" id="warehouseModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form action="{{ url('warehouse/store') }}" method="POST" class="w-100">
                @csrf
                <input type="hidden" name="id" id="warehouse_id">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title font-weight-bold" id="warehouseModalLabel">Add Location</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold small text-muted">Location Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="type" id="wh_type" required>
                                <option value="warehouse">Warehouse (Storage)</option>
                                <option value="shop">Shop (Retail)</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold small text-muted">Location Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="warehouse_name" id="warehouse_name"
                                placeholder="e.g. WAREHOUSE 5 or Main Shop" required>
                        </div>

                        <input type="hidden" name="creater_id" value="{{ Auth()->user()->id ?? 1 }}">

                        @if (auth()->user()->hasRole('Super Admin'))
                            <div class="form-group mb-3">
                                <label class="form-label font-weight-bold small text-muted">Assigned Branch <span class="text-danger">*</span></label>
                                <select class="form-control" name="branch_id" id="branch_id" required>
                                    <option value="">Select Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold small text-muted">Physical Location / Address</label>
                            <input class="form-control" name="location" id="location"
                                placeholder="Physical location / address">
                        </div>

                        <div class="form-group mb-0">
                            <label class="form-label font-weight-bold small text-muted">Remarks</label>
                            <textarea class="form-control" name="remarks" id="remarks" rows="3" placeholder="Additional notes or description..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">Cancel</button>
                        @canany(['warehouse.create', 'warehouse.edit'])
                            <button type="submit" class="btn btn-primary px-4 font-weight-bold">
                                <i class="fas fa-save mr-1"></i> Save
                            </button>
                        @endcanany
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function clearWarehouse() {
            $('#warehouseModalLabel').text('Add Location');
            $('#warehouse_id').val('');
            $('#warehouse_name').val('');
            $('#wh_type').val('warehouse');
            $('#location').val('');
            $('#remarks').val('');
            if ($('#branch_id').length) {
                $('#branch_id').val('');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $ !== 'undefined') {
                $(document).on('click', '.edit-warehouse-btn', function () {
                    $('#warehouseModalLabel').text('Edit Warehouse');
                    $('#warehouse_id').val($(this).data('id'));
                    $('#warehouse_name').val($(this).data('name'));
                    $('#wh_type').val($(this).data('type') || 'warehouse');
                    $('#location').val($(this).data('location') || '');
                    $('#remarks').val($(this).data('remarks') || '');
                    if ($('#branch_id').length) {
                        $('#branch_id').val($(this).data('branch'));
                    }
                });
            }
        });
    </script>
@endsection
