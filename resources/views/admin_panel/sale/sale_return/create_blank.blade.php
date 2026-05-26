@extends('admin_panel.layout.app')

@section('content')
<style>
    :root {
        --erp-primary: #4a69bd;
        --erp-bg: #f5f6fa;
        --erp-border: #dcdde1;
        --erp-text: #2f3640;
        --erp-muted: #7f8fa6;
    }

    body {
        background-color: var(--erp-bg);
        color: var(--erp-text);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .erp-card {
        background: white;
        border: 1px solid var(--erp-border);
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        margin-bottom: 1.5rem;
    }

    .erp-header {
        background: white;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--erp-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: 8px 8px 0 0;
    }

    .erp-header h5 {
        margin: 0;
        font-weight: 600;
        color: var(--erp-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--erp-muted);
        text-transform: uppercase;
        margin-bottom: 0.3rem;
    }

    .form-control, .form-select {
        border-radius: 4px;
        border: 1px solid var(--erp-border);
        padding: 0.4rem 0.75rem;
        font-size: 0.9rem;
    }

    .form-control:focus {
        border-color: var(--erp-primary);
        box-shadow: 0 0 0 2px rgba(74, 105, 189, 0.2);
    }
    
    .select2-container .select2-selection--single {
        height: 38px;
        border: 1px solid var(--erp-border);
        border-radius: 4px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
    }

    .modal-content-glass {
        border-radius: 12px;
        border: none;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    
    .modal-header-glass {
        background: var(--erp-primary);
        color: white;
        border-radius: 12px 12px 0 0;
        padding: 1.5rem;
    }

    .modal-header-glass .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    .table-hover-custom tbody tr {
        transition: all 0.2s;
        cursor: pointer;
    }

    .table-hover-custom tbody tr:hover {
        background-color: #f1f2f6;
    }
</style>

<div class="container-fluid py-4">
    <div class="erp-card">
        <div class="erp-header">
            <h5><i class="fas fa-undo-alt me-2"></i> Initiate Sale Return</h5>
        </div>
        
        <div class="card-body p-4">
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="fas fa-info-circle me-2"></i> Please select a customer to load their posted invoices (SIN) for return.
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label">Select Customer <span class="text-danger">*</span></label>
                    <select class="form-select select2" id="customerSelect">
                        <option value="">-- Choose Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_name }} {{ $c->customer_id ? "({$c->customer_id})" : "" }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" id="btnLoadInvoices" style="height: 38px;">
                        <i class="fas fa-search me-1"></i> Load Invoices
                    </button>
                </div>
            </div>
            
            <div class="text-center py-5 text-muted" id="placeholderState">
                <i class="fas fa-file-invoice fa-4x mb-3" style="opacity: 0.2;"></i>
                <h4>Waiting for Customer Selection</h4>
                <p>Select a customer above and click Load Invoices to fetch their Sale Invoice Notes.</p>
            </div>
        </div>
    </div>
</div>

<!-- SIN Modal -->
<div class="modal fade" id="sinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content modal-content-glass">
            <div class="modal-header modal-header-glass">
                <h5 class="modal-title fw-bold m-0"><i class="fas fa-file-invoice-dollar me-2"></i> Select Invoice to Return Against</h5>
                <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="mb-4">
                    <input type="text" class="form-control" id="sinSearch" placeholder="Search by Invoice No or Date...">
                </div>
                <div class="table-responsive rounded border bg-white" style="max-height: 50vh; overflow-y:auto;">
                    <table class="table table-hover table-hover-custom align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer Name</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="sinListBody">
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin me-2"></i> Loading sales...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    $(document).ready(function() {
        if($.fn.select2) {
            $('#customerSelect').select2({ width: '100%' });
        }

        let allSins = [];

        $('#btnLoadInvoices').click(function() {
            const custId = $('#customerSelect').val();
            if(!custId) {
                alert('Please select a customer first.');
                return;
            }

            $('#btnLoadInvoices').html('<i class="fas fa-spinner fa-spin"></i> Loading...');
            $('#btnLoadInvoices').prop('disabled', true);

            fetch(`{{ route("sale.return.api_srns") }}?customer_id=${custId}`)
                .then(res => res.json())
                .then(data => {
                    allSins = data;
                    renderSinList(allSins);
                    $('#sinModal').modal('show');
                })
                .catch(e => {
                    console.error(e);
                    alert('Error loading invoices.');
                })
                .finally(() => {
                    $('#btnLoadInvoices').html('<i class="fas fa-search me-1"></i> Load Invoices');
                    $('#btnLoadInvoices').prop('disabled', false);
                });
        });

        function renderSinList(list) {
            const tbody = document.getElementById('sinListBody');
            if (!list || list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No posted sales found for this customer.</td></tr>';
                return;
            }
            
            let html = '';
            const baseUrl = "{{ route('sale.return.show', ['id' => ':id']) }}";
            list.forEach(sale => {
                const url = baseUrl.replace(':id', sale.id);
                html += `
                    <tr>
                        <td class="fw-bold text-primary">${sale.invoice_no}</td>
                        <td class="fw-600">${sale.customer_name}</td>
                        <td class="text-muted"><i class="far fa-calendar-alt me-1"></i>${sale.date}</td>
                        <td class="text-end fw-bold">Rs. ${parseFloat(sale.amount || 0).toFixed(2)}</td>
                        <td class="text-center">
                            <a href="${url}" class="btn btn-sm btn-primary rounded px-3">
                                <i class="fas fa-download me-1"></i> Import
                            </a>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        $('#sinSearch').on('input', function(e) {
            const val = e.target.value.toLowerCase();
            const filtered = allSins.filter(s => 
                (s.invoice_no || '').toLowerCase().includes(val) || 
                (s.date || '').toLowerCase().includes(val)
            );
            renderSinList(filtered);
        });
    });
</script>
@endsection