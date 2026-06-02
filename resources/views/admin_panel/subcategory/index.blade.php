@extends('admin_panel.layout.app')
@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3>Sub Category</h3>
                            @can('subcategories.create')
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#exampleModal" id="reset">Create</button>
                            @endcan
                        </div>

                        <div class="border mt-1 shadow rounded" style="background-color: white;">
                            <div class="col-lg-12 m-auto">
                                <div class="table-responsive mt-5 mb-5">
                                    <table id="default-datatable" class="table">
                                        <thead class="text-center">
                                            <tr>
                                                <th class="text-center">Id</th>
                                                <th class="text-center">sub category</th>
                                                <th class="text-center">category</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>

                                        <tbody class="text-center">
                                            @foreach ($subcategory as $company)
                                                <tr>
                                                    <td class="id">{{ $company->id }}</td>

                                                    <td class="name">{{ $company->name }}</td>

                                                    <td class="name"
                                                        data-category-id="{{ $company->category_id }}">
                                                        {{ $company->category->name ?? '' }}
                                                    </td>

                                                    <td>
                                                        @include('admin_panel.partials.action_buttons', [
                                                            'editRoute' => route('store.subcategory'),
                                                            'deleteRoute' => route('delete.subcategory', $company->id),
                                                            'editIsLink' => false,
                                                            'permissions' => [
                                                                'edit' => 'subcategories.edit',
                                                                'delete' => 'subcategories.delete',
                                                            ],
                                                            'dataId' => $company->id,
                                                        ])
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
        </div>
    </div>

    <!-- MODAL -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Add Sub category</h5>
                </div>

                <div class="modal-body">
                    <form class="myform" action="{{ route('store.subcategory') }}" method="POST">
                        @csrf

                        <input type="hidden" name="edit_id" id="id" />

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="name" class="form-control" id="name" />
                        </div>

                        <select name="category_id" class="form-control">
                            @foreach ($category as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    @can('subcategories.create')
                        <input type="submit" class="btn btn-primary save-btn">
                    @endcan
                </div>

                </form>

            </div>
        </div>
    </div>

    <!-- DATA TABLE -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- ONLY ONE jQuery (FIXED) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="{{ asset('assets/js/mycode.js') }}"></script>

    <!-- AJAX FIX -->
    <script>
        $(document).on('submit', '.myform', function(e) {
            e.preventDefault();

            var form = $(this);
            var formdata = new FormData(this);
            var url = form.attr('action');
            var method = form.attr('method');

            form.find(':submit').prop('disabled', true);

            $.ajax({
                url: url,
                type: method,
                data: formdata,
                processData: false,
                contentType: false,

                success: function(res) {

                    if (res.status === true) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: res.message,
                            timer: 1200,
                            showConfirmButton: false
                        });

                        // CLOSE MODAL
                        $('#exampleModal').modal('hide');

                        // RESET FORM
                        form[0].reset();
                        $('#id').val('');

                        form.find(':submit').prop('disabled', false);

                        setTimeout(() => {
                            location.reload();
                        }, 700);

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message
                        });

                        form.find(':submit').prop('disabled', false);
                    }
                },

                error: function(xhr) {

                    let msg = 'Server Error';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: msg
                    });

                    form.find(':submit').prop('disabled', false);
                }
            });
        });
    </script>

    <!-- EDIT BUTTON FIX -->
    <script>
        $(document).on('click', '.edit-btn', function() {

            var tr = $(this).closest("tr");

            var id = tr.find(".id").text();
            var name = tr.find(".name").first().text();

            $('#id').val(id);
            $('#name').val(name);

            // optional category fix if needed later
            var categoryId = tr.find('td[data-category-id]').data('category-id');
            $('select[name="category_id"]').val(categoryId);

            $("#exampleModal").modal("show");
        });
    </script>

    <!-- DATATABLE -->
    <script>
        $(document).ready(function() {
            $('#default-datatable').DataTable({
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                order: [[0, 'desc']],
                language: {
                    search: "Search Category:",
                    lengthMenu: "Show _MENU_ entries"
                }
            });
        });
    </script>

@endsection