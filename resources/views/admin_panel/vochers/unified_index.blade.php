@extends('admin_panel.layout.app')

@section('content')
<div class="main-content">
    <div class="main-content-inner">
        <div class="container-fluid py-4">
            
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-file-invoice-dollar text-primary"></i>
                        Vouchers & Accounting Entries
                    </h4>
                    <p class="text-muted mb-0 small">Manage Cash Receiving, Bank Receiving, Cash Payment, Bank Payment, and Journal Vouchers</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="dropdown shadow-sm">
                        <button type="button" class="btn btn-primary fw-bold px-3 py-2 d-flex align-items-center gap-2 dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 8px;">
                            <i class="fas fa-plus-circle"></i> + Create New Voucher
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" style="min-width: 250px; z-index: 1050; border-radius: 10px; margin-top: 6px;">
                            <li class="dropdown-header text-uppercase small fw-bold text-muted px-3 py-1">Select Voucher Type</li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('vouchers.create_page', ['type' => 'crv']) }}"><i class="fas fa-hand-holding-usd text-success me-2"></i> Cash Receiving (CRV)</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('vouchers.create_page', ['type' => 'brv']) }}"><i class="fas fa-university text-primary me-2"></i> Bank Receiving (BRV)</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('vouchers.create_page', ['type' => 'cpv']) }}"><i class="fas fa-money-bill-wave text-warning me-2"></i> Cash Payment (CPV)</a></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('vouchers.create_page', ['type' => 'bpv']) }}"><i class="fas fa-file-invoice-dollar text-purple me-2" style="color:#8b5cf6;"></i> Bank Payment (BPV)</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 fw-semibold" href="{{ route('vouchers.create_page', ['type' => 'jv']) }}"><i class="fas fa-book text-secondary me-2"></i> Journal Voucher (JV)</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="close btn-close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="close btn-close" data-dismiss="alert" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- TYPE TABS -->
            <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-4 shadow-sm border">
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'all' ? 'active bg-dark text-white' : 'text-dark' }}" href="{{ route('vouchers.list', ['type' => 'all']) }}">
                        <i class="fas fa-th-large me-1"></i> All Vouchers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'crv' ? 'active bg-success text-white' : 'text-dark' }}" href="{{ route('vouchers.list', ['type' => 'crv']) }}">
                        <i class="fas fa-hand-holding-usd me-1"></i> Cash Receiving (CRV)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'brv' ? 'active bg-primary text-white' : 'text-dark' }}" href="{{ route('vouchers.list', ['type' => 'brv']) }}">
                        <i class="fas fa-university me-1"></i> Bank Receiving (BRV)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'cpv' ? 'active bg-warning text-dark fw-bold' : 'text-dark' }}" href="{{ route('vouchers.list', ['type' => 'cpv']) }}">
                        <i class="fas fa-money-bill-wave me-1"></i> Cash Payment (CPV)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'bpv' ? 'active text-white' : 'text-dark' }}" style="{{ $currentType === 'bpv' ? 'background:#8b5cf6;' : '' }}" href="{{ route('vouchers.list', ['type' => 'bpv']) }}">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Bank Payment (BPV)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link rounded-3 {{ $currentType === 'jv' ? 'active bg-secondary text-white' : 'text-dark' }}" href="{{ route('vouchers.list', ['type' => 'jv']) }}">
                        <i class="fas fa-book me-1"></i> Journal Voucher (JV)
                    </a>
                </li>
            </ul>

            <!-- Vouchers Table Card -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle datanew" style="width:100%">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3" style="width: 5%">#</th>
                                    <th style="width: 14%">Voucher #</th>
                                    <th style="width: 10%">Date</th>
                                    <th style="width: 12%">Type</th>
                                    <th style="width: 25%">Party / Account / Remarks</th>
                                    <th style="width: 14%" class="text-end">Total Amount</th>
                                    <th style="width: 8%" class="text-center">Status</th>
                                    <th class="pe-3 text-center" style="width: 12%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($vouchers as $v)
                                    @php
                                        $typeBadgeClass = match(strtolower($v->voucher_type)) {
                                            'crv', 'receipt' => 'bg-success-subtle text-success border border-success-subtle',
                                            'brv' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                            'cpv', 'payment', 'expense' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                            'bpv' => 'bg-purple text-white',
                                            'jv', 'journal' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                            default => 'bg-light text-dark',
                                        };

                                        $partyName = '-';
                                        if ($v->party) {
                                            $partyName = $v->party->customer_name ?? $v->party->name ?? $v->party->title ?? '-';
                                        } elseif ($v->details->isNotEmpty()) {
                                            $firstAcc = $v->details->first()->account;
                                            $partyName = $firstAcc->title ?? '-';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace fw-bold">{{ $v->voucher_no }}</span>
                                        </td>
                                        <td>{{ $v->date ? \Carbon\Carbon::parse($v->date)->format('d M, Y') : '-' }}</td>
                                        <td>
                                            <span class="badge {{ $typeBadgeClass }} px-2 py-1 uppercase">{{ strtoupper($v->voucher_type) }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $partyName }}</div>
                                            <small class="text-muted">{{ Str::limit($v->remarks ?? '-', 50) }}</small>
                                            @if($v->cheque_no)
                                                <small class="d-block text-primary"><i class="fas fa-money-check"></i> Chq # {{ $v->cheque_no }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold fs-6">
                                            Rs. {{ number_format((float)$v->total_amount, 2) }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                <i class="fas fa-check-circle"></i> POSTED
                                            </span>
                                        </td>
                                        <td class="pe-3 text-center">
                                            <div class="btn-group btn-group-sm">
                                                <!-- PRINT -->
                                                <a href="{{ route('vouchers.print_unified', ['id' => $v->id]) }}" class="btn btn-outline-success" title="Print Voucher" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                                
                                                <!-- EDIT -->
                                                <a href="{{ route('vouchers.edit_page', ['id' => $v->id]) }}" class="btn btn-outline-primary" title="Edit Voucher">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <!-- DELETE -->
                                                <button type="button" class="btn btn-outline-danger" title="Delete Voucher" onclick="confirmDeleteVoucher('{{ $v->id }}', '{{ $v->voucher_no }}')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                            <p class="mb-0">No vouchers found in this category.</p>
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
</div>

<!-- Delete Confirmation Form -->
<form id="deleteVoucherForm" method="POST" style="display:none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDeleteVoucher(id, voucherNo) {
    if (confirm("Are you sure you want to DELETE voucher " + voucherNo + "?\n\nAll connected journal entries and customer/vendor ledger balances will be automatically and safely reversed.")) {
        var form = document.getElementById('deleteVoucherForm');
        form.action = "{{ url('vouchers/destroy') }}/" + id;
        form.submit();
    }
}
</script>
@endsection
