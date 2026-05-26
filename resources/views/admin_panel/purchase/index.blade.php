@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">
                            {{ request('mode') == 'po' ? 'Purchase Orders' : 'Purchase Management' }}</h4>
                        <p class="text-muted mb-0 small">View and manage your
                            {{ request('mode') == 'po' ? 'purchase orders' : 'purchase invoices' }}</p>
                    </div>
                    <div>

                        @can('purchases.create')
                            @if (request('mode') == 'po')
                                <a class="btn btn-primary px-4 shadow-sm fw-medium align-items-center gap-2"
                                    href="{{ route('add_purchase', ['mode' => 'po']) }}">
                                    <i class="fas fa-plus"></i> Create Purchase Order
                                </a>
                            @else
                                <a class="btn btn-primary px-4 shadow-sm fw-medium align-items-center gap-2"
                                    href="{{ route('add_purchase') }}">
                                    <i class="fas fa-plus"></i> Create Goods Receipt Note
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>

                {{-- Status Filters --}}
                <div class="mb-4  d-none">
                    <a href="{{ route('Purchase.home', array_merge(request()->query(), ['status' => 'all'])) }}"
                        class="btn btn-sm {{ request('status') == 'all' || !request('status') ? 'btn-secondary' : 'btn-outline-secondary' }}">
                        All
                    </a>
                    <a href="{{ route('Purchase.home', array_merge(request()->query(), ['status' => 'approved'])) }}"
                        class="btn btn-sm {{ request('status') == 'approved' ? 'btn-success' : 'btn-outline-success' }}">
                        Approved
                    </a>
                    <a href="{{ route('Purchase.home', array_merge(request()->query(), ['status' => 'draft'])) }}"
                        class="btn btn-sm {{ request('status') == 'draft' ? 'btn-warning' : 'btn-outline-warning' }}">
                        Draft
                    </a>
                    <a href="{{ route('Purchase.home', array_merge(request()->query(), ['status' => 'Returned'])) }}"
                        class="btn btn-sm {{ request('status') == 'Returned' ? 'btn-danger' : 'btn-outline-danger' }}">
                        Returned
                    </a>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table id="purchase-table" class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small">
                                            ID</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Status</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="min-width: 200px;">Items Detail</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-nowrap">PO / GRN Ref</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Vendor</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small">Warehouse</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Net Amount</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Paid</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Due</th>
                                        <th class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($Purchase as $purchase)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">#{{ $purchase->id }}</td>
                                            <td>
                                                @if ($purchase->status_purchase == 'draft')
                                                    <span class="badge badge-warning text-dark border border-warning">Purchase Order</span>
                                                @elseif ($purchase->status_purchase == 'Returned')
                                                    <span class="badge bg-danger text-white border border-danger">Returned</span>
                                                @else
                                                    <span class="badge badge-success border border-success">Approved</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1" style="max-width: 300px;">
                                                    @foreach ($purchase->items as $item)
                                                        @php
                                                            $prod = $item->product;
                                                            $ppb = (float)$item->pieces_per_box > 0 ? (float)$item->pieces_per_box : ($prod->pieces_per_box ?? 1);
                                                            $displayQty = $item->qty;
                                                            if ($prod && in_array($prod->size_mode, ['by_cartons', 'by_size']) && $ppb > 1) {
                                                                $boxes = floor($item->total_pieces / $ppb);
                                                                $rem = $item->total_pieces % $ppb;
                                                                $displayQty = $rem > 0 ? $boxes . "." . $rem : $boxes;
                                                            }
                                                            $uom = $item->uom->name ?? ($prod->unit->name ?? 'Piece');
                                                            if (strtolower($uom) == 'piece' && $ppb > 1) {
                                                                $uom = '1X' . (int)$ppb;
                                                            }
                                                        @endphp
                                                        <div class="border rounded p-1 bg-light mb-1" style="font-size: 0.72rem; line-height: 1.25; min-width: 140px; flex: 1 1 140px;">
                                                            <div class="fw-bold text-dark text-truncate" title="{{ ($prod->item_name ?? 'N/A') . ' ' . ($prod->brand->name ?? '') }}">{{ ($prod->item_name ?? 'N/A') . ' ' . ($prod->brand->name ?? '') }}</div>
                                                            <div class="text-muted" style="font-size: 0.65rem;">
                                                                {{ $prod->brand->name ?? 'No Brand' }}@if($prod->size && strtolower($prod->size) !== 'n/a') | {{ $prod->size }} @endif
                                                            </div>
                                                            <div class="text-primary fw-bold">{{ $displayQty }} {{ $uom }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark">{{ $purchase->invoice_no }}</span>
                                                    <span class="text-muted small">{{ \Carbon\Carbon::parse($purchase->purchase_date)->format('d M, Y') }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle bg-info-subtle text-info me-2 fw-bold d-flex align-items-center justify-content-center rounded-circle"
                                                        style="width: 32px; height: 32px; font-size: 14px;">
                                                        {{ strtoupper(substr($purchase->vendor->name ?? 'V', 0, 1)) }}
                                                    </div>
                                                    <span class="fw-medium text-dark">{{ $purchase->vendor->name ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td class="text-muted small">
                                                {{ $purchase->warehouse->warehouse_name ?? 'N/A' }}
                                            </td>
                                            <td class="text-end fw-bold text-dark">
                                                @if ($purchase->total_returned > 0)
                                                    <div><small class="text-muted text-decoration-line-through">{{ number_format($purchase->net_amount, 2) }}</small></div>
                                                    <div class="text-success">{{ number_format($purchase->updated_net_amount, 2) }}</div>
                                                    <small class="text-danger">(-{{ number_format($purchase->total_returned, 2) }})</small>
                                                @else
                                                    {{ number_format($purchase->net_amount, 2) }}
                                                @endif
                                            </td>
                                            <td class="text-end text-success">
                                                {{ number_format($purchase->paid_amount, 2) }}
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $displayDue = $purchase->total_returned > 0 ? $purchase->updated_due_amount : $purchase->due_amount;
                                                @endphp
                                                @if ($displayDue > 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">{{ number_format($displayDue, 2) }}</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Paid</span>
                                                @endif
                                                @if ($purchase->is_fully_returned)
                                                    <br><small class="badge bg-danger mt-1">Fully Returned</small>
                                                @elseif ($purchase->has_partial_return)
                                                    <br><small class="badge bg-warning text-dark mt-1">Partial Return</small>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                                                        <i class="fas fa-ellipsis-v small"></i> Actions
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right border-0 shadow-lg rounded-3">
                                                        @if ($purchase->status_purchase == 'draft')
                                                            @can('purchases.create')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2 text-success confirm-purchase-btn" href="{{ route('purchase.confirm', $purchase->id) }}">
                                                                        <i class="fas fa-check-circle fa-fw"></i> Confirm Purchase
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                            @endcan
                                                        @endif
                                                        @if ($purchase->status_purchase == 'draft')
                                                            @can('purchases.edit')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('purchase.edit', $purchase->id) }}">
                                                                        <i class="fas fa-edit text-primary fa-fw"></i> Edit
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                        @endif
                                                        @if ($purchase->status_purchase != 'draft')
                                                            @can('purchases.view')
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('purchase.invoice', $purchase->id) }}">
                                                                        <i class="fas fa-file-invoice text-info fa-fw"></i> View Invoice
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('purchase.receipt', $purchase->id) }}">
                                                                        <i class="fas fa-receipt text-secondary fa-fw"></i> View Receipt
                                                                    </a>
                                                                </li>
                                                            @endcan
                                                            @can('purchases.create')
                                                                @if (!$purchase->is_fully_returned)
                                                                    <li>
                                                                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('purchase.return.show', $purchase->id) }}">
                                                                            <i class="fas fa-undo text-warning fa-fw"></i> Return
                                                                        </a>
                                                                    </li>
                                                                @endif
                                                            @endcan
                                                        @endif
                                                        @if ($purchase->status_purchase == 'draft')
                                                            @can('purchases.delete')
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <form action="{{ route('purchase.destroy', $purchase->id) }}" method="POST" class="d-inline delete-form">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2 delete-btn text-danger">
                                                                            <i class="fas fa-trash-alt fa-fw"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                </li>
                                                            @endcan
                                                        @endif
                                                    </ul>
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
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().destroy();
            }
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search purchases..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });

            $(document).on('click', '.confirm-purchase-btn', function(e) {
                e.preventDefault();
                let url = $(this).attr('href');
                Swal.fire({
                    title: "Confirm Purchase?",
                    text: "This will finalize the purchase, update stocks, and post ledgers.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#28a745",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, Confirm it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url,
                            method: "GET",
                            success: function(response) {
                                if (response.invoice_url) {
                                    window.open(response.invoice_url, '_blank');
                                }
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.reload();
                                });
                            },
                            error: function(xhr) {
                                let msg = 'Something went wrong.';
                                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                                Swal.fire('Error', msg, 'error');
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                let form = $(this).closest("form");
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
