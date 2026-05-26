<!-- Booked Products Modal (Import SO/DC) -->
<div class="modal fade" id="bookedProductsModal" tabindex="-1" aria-labelledby="bookedProductsModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                    id="bookedProductsModalLabel">
                    <i class="bi bi-card-checklist text-primary fs-4"></i>
                    Import Sale Order / DC
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <div class="search-wrapper position-relative">
                            <i class="bi bi-search text-muted position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                            <input type="text" id="searchBookedProducts"
                                class="form-control form-control-sm ps-5 py-2 rounded-pill"
                                placeholder="Search by SO #, Customer, etc...">
                        </div>
                    </div>
                </div>

                <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                    <table class="erp-table table-hover align-middle mb-0" id="bookedProductsTable">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th style="width: 5%;"></th>
                                <th>SYS ID / SO #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th class="text-end">Total Items</th>
                                <th class="text-end">Net Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="bookedProductsTableBody">
                            @php
                                $hasDraftItems = false;
                                $isSoMode = ($sale->sale_status == 'draft');
                            @endphp

                            @if (isset($sales))
                                @foreach ($sales->whereIn('sale_status', ['draft']) as $draft)
                                    @php $hasDraftItems = true; @endphp
                                    <tr class="booked-item-row"
                                        data-search="{{ strtolower($draft->invoice_no . ' ' . ($draft->customer_relation->customer_name ?? '')) }}">
                                        <td><i class="bi bi-file-earmark-text text-primary"></i></td>
                                        <td><span class="badge bg-light text-dark border">{{ $draft->invoice_no }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($draft->sale_date)->format('d M, Y') }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="vendor-initial bg-primary-subtle text-primary fw-bold rounded-3 p-2"
                                                    style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    {{ strtoupper(substr($draft->customer_relation->customer_name ?? 'C', 0, 1)) }}
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark">{{ $draft->customer_relation->customer_name ?? 'N/A' }}</span>
                                                    <span class="text-muted small">{{ $draft->customer_relation->business_name ?? 'Individual' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold">{{ $draft->items->count() }}</td>
                                        <td class="text-end fw-bold text-primary">{{ number_format($draft->total_net, 2) }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-import-single"
                                                data-customer-id="{{ $draft->customer_id }}"
                                                data-draft-id="{{ $draft->id }}"
                                                data-items="{{ json_encode($draft->items->map(fn($i) => [
                                                    'product_id' => $i->product_id,
                                                    'product_name' => $i->product->item_name ?? '',
                                                    'item_code' => $i->product->item_code ?? '',
                                                    'ppb' => $i->product->pieces_per_box ?? 1,
                                                    'total_pieces' => $i->total_pieces,
                                                    'price' => $i->price,
                                                    'unit_discount' => $i->total_pieces > 0 ? $i->discount_amount / $i->total_pieces : 0,
                                                ])) }}">
                                                Import
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            @if (isset($dcNotes))
                                @foreach ($dcNotes as $dc)
                                    @php $hasDraftItems = true; @endphp
                                    <tr class="booked-item-row" data-search="{{ strtolower($dc->dc_no . ' ' . ($dc->customer->customer_name ?? '')) }}">
                                        <td><i class="bi bi-file-earmark-text text-success"></i></td>
                                        <td><span class="badge bg-light text-dark border">{{ $dc->dc_no }}</span></td>
                                        <td>{{ \Carbon\Carbon::parse($dc->delivery_date)->format('d M, Y') }}</td>
                                        <td><strong>{{ $dc->customer->customer_name ?? 'N/A' }}</strong></td>
                                        <td class="text-end fw-bold">{{ $dc->items->count() }}</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($dc->net_amount, 2) }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-success btn-sm rounded-pill px-4 btn-import-single"
                                                data-dc-id="{{ $dc->id }}"
                                                data-items="{{ json_encode($dc->items->map(fn($i) => [
                                                    'product_id' => $i->product_id,
                                                    'product_name' => $i->product->item_name ?? '',
                                                    'item_code' => $i->product->item_code ?? '',
                                                    'ppb' => $i->product->pieces_per_box ?? 1,
                                                    'total_pieces' => $i->total_pieces,
                                                    'price' => $i->price,
                                                ])) }}">
                                                Import
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top border-light px-4 py-3">
                <button type="button" class="btn-erp btn-erp-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Modal -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="customerModalLabel">
                    <i class="bi bi-people text-primary fs-4"></i>
                    Select Customer
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="search-wrapper position-relative">
                            <i class="bi bi-search text-muted position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                            <input type="text" id="searchCustomer" class="form-control form-control-sm ps-5 py-2 rounded-pill" placeholder="Search by name or code...">
                        </div>
                    </div>
                </div>
                <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                    <table class="erp-table table-hover align-middle mb-0" id="customerTable">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Business</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="customerTableBody">
                            @foreach ($customer as $c)
                                <tr class="customer-item-row" data-search="{{ strtolower(($c->customer_id ?? '') . ' ' . ($c->customer_name ?? '') . ' ' . ($c->business_name ?? '')) }}">
                                    <td><span class="badge bg-light text-dark border">{{ $c->customer_id ?? 'N/A' }}</span></td>
                                    <td><strong>{{ $c->customer_name }}</strong></td>
                                    <td>{{ $c->business_name ?? '-' }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-customer" 
                                            data-id="{{ $c->id }}" 
                                            data-name="{{ $c->customer_name }}">
                                            Select
                                        </button>
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

<!-- Officer Modal -->
<div class="modal fade" id="officerModal" tabindex="-1" aria-labelledby="officerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="officerModalLabel">
                    <i class="bi bi-person-badge text-primary fs-4"></i>
                    Select Sales Officer
                </h5>
                <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="search-wrapper position-relative">
                            <i class="bi bi-search text-muted position-absolute" style="left: 15px; top: 50%; transform: translateY(-50%);"></i>
                            <input type="text" id="searchOfficer" class="form-control form-control-sm ps-5 py-2 rounded-pill" placeholder="Search officers...">
                        </div>
                    </div>
                </div>
                <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                    <table class="erp-table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="officerTableBody">
                            @foreach ($employees as $emp)
                                <tr class="officer-item-row" data-search="{{ strtolower(($emp->employee_id ?? '') . ' ' . ($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) }}">
                                    <td><span class="badge bg-light text-dark border">{{ $emp->employee_id ?? 'N/A' }}</span></td>
                                    <td><strong>{{ $emp->first_name }} {{ $emp->last_name }}</strong></td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 btn-select-officer" 
                                            data-id="{{ $emp->id }}" 
                                            data-name="{{ $emp->first_name }} {{ $emp->last_name }}">
                                            Select
                                        </button>
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
