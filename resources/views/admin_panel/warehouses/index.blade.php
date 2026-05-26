@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid">

                <div class="page-header row">
                    <div class="page-title col-lg-6">
                        <h4>Warehouse List</h4>
                        <h6>Manage Warehouses</h6>
                    </div>
                    <div class="page-btn d-flex justify-content-end col-lg-6">
                        @can('warehouse.create')
                            <button class="btn btn-outline-primary mb-2" data-toggle="modal" data-target="#warehouseModal"
                                onclick="clearWarehouse()">Add Warehouse</button>
                        @endcan
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        {{-- @if (session()->has('success'))
                            <div class="alert alert-success"><strong>Success!</strong> {{ session('success') }}</div>
                        @endif --}}

                        <table class="table datanew">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Created By</th>
                                    @if (auth()->user()->hasRole('Super Admin'))
                                        <th>Branch ID</th>
                                        <th>Branch Name</th>
                                    @endif
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($warehouses as $key => $w)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $w->user->name ?? 'N/A' }}</td>
                                        @if (auth()->user()->hasRole('Super Admin'))
                                            <td>{{ $w->branch_id ?? 'N/A' }}</td>
                                            <td>{{ $w->branch->name ?? 'N/A' }}</td>
                                        @endif
                                        <td>{{ $w->warehouse_name }}</td>
                                        <td>{{ $w->location }}</td>
                                        <td>{{ $w->remarks }}</td>
                                        <td>
                                            @can('warehouse.edit')
                                                <button class="btn btn-primary btn-sm edit-warehouse-btn"
                                                    data-id="{{ $w->id }}" data-name="{{ $w->warehouse_name }}"
                                                    data-location="{{ $w->location }}" data-remarks="{{ $w->remarks }}"
                                                    data-branch="{{ $w->branch_id }}" data-toggle="modal"
                                                    data-target="#warehouseModal">
                                                    Edit
                                                </button>
                                            @endcan
                                            @can('warehouse.delete')
                                                <button class="btn btn-danger btn-sm delete-btn"
                                                    data-url="{{ url('warehouse/delete/' . $w->id) }}"
                                                    data-msg="Are you sure you want to delete this warehouse?" data-method="get"
                                                    onclick="logoutAndDeleteFunction(this)">
                                                    Delete
                                                </button>
                                            @endcan
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

    <div class="modal fade" id="warehouseModal">
        <div class="modal-dialog">
            <form action="{{ url('warehouse/store') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="warehouse_id">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add/Edit Warehouse</h5>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2"><input class="form-control" name="warehouse_name" id="warehouse_name"
                                placeholder="Name" required></div>
                        <div class="mb-2 d-none"><input class="form-control" name="creater_id" id=""
                                value="{{ Auth()->user()->id }}" placeholder="Name" required></div>
                        @if (auth()->user()->hasRole('Super Admin'))
                            <div class="mb-2">
                                <select class="form-control" name="branch_id" id="branch_id" required>
                                    <option value="">Select Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name ?? 'N/A' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="mb-2"><input class="form-control" name="location" id="location"
                                placeholder="Location"></div>
                        <div class="mb-2">
                            <textarea class="form-control" name="remarks" id="remarks" placeholder="Remarks"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @canany(['warehouse.create', 'warehouse.edit'])
                            <button class="btn btn-primary">Save</button>
                        @endcanany
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

{{-- @push('scripts') --}}
@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    @endif
    <script>
        function clearWarehouse() {
            $('#warehouse_id').val('');
            $('#warehouse_name').val('');
            $('#location').val('');
            $('#remarks').val('');
            if ($('#branch_id').length) {
                $('#branch_id').val('');
            }
        }

        // function editWarehouse(id,name,location,remarks){
        //     alert(id);
        //     $('#warehouse_id').val(id);
        //     $('#warehouse_name').val(name);
        //     $('#location').val(location);
        //     $('#remarks').val(remarks);
        //  }

        // Handle Edit button click
        $(document).on('click', '.edit-warehouse-btn', function() {
            // alert("Edit button clicked ✅"+ $(this).data('id'));

            $('#warehouse_id').val($(this).data('id'));
            $('#warehouse_name').val($(this).data('name'));
            $('#location').val($(this).data('location'));
            $('#remarks').val($(this).data('remarks'));
            if ($('#branch_id').length) {
                $('#branch_id').val($(this).data('branch'));
            }
        });


        $('.datanew').DataTable();
    </script>
    {{-- @endpush --}}
@endsection
