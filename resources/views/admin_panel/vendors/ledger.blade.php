@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">Vendor Ledger Details</h4>
                        <p class="text-muted mb-0 small">Transaction history for <span
                                class="fw-bold text-primary">{{ $vendor->name }}</span></p>
                    </div>
                    <div>
                        <a href="{{ route('vendors-ledger') }}"
                            class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="fas fa-arrow-left"></i> Back to Ledger List
                        </a>
                    </div>
                </div>

                <!-- Date Filter -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold">Start Date</label>
                                <input type="date" name="start_date" class="form-control"
                                    value="{{ request('start_date', now()->startOfMonth()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-secondary fw-bold">End Date</label>
                                <input type="date" name="end_date" class="form-control"
                                    value="{{ request('end_date', now()->endOfMonth()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary px-4 fw-medium shadow-sm">
                                        <i class="fas fa-filter me-1"></i> Filter
                                    </button>
                                    <a href="{{ route('vendor.ledger', $vendor->id) }}"
                                        class="btn btn-light fw-medium">Reset</a>
                                    <button type="button" onclick="window.print()" class="btn btn-secondary ms-auto">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Dual Party Banner if applicable -->
                @if (!empty($is_dual) && !empty($twin_party))
                    <div style="background:#f0fdf4; border:1px solid #86efac; border-radius:10px; padding:12px 18px; margin-bottom:20px; color:#166534; font-size:.87rem; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="font-size:1.25rem;">🤝</span>
                            <span><strong>Dual Party (Customer & Vendor):</strong> Combined statement showing all <strong>Purchases</strong> from Vendor and <strong>Sales</strong> to Customer <strong>{{ $twin_party->customer_name ?? $twin_party->name }}</strong> (Customer Code: <code>{{ $twin_party->customer_id ?? $twin_party->vendor_code }}</code>).</span>
                        </div>
                        <span style="background:#dcfce7; color:#15803d; padding:4px 12px; border-radius:20px; font-weight:700; font-size:.78rem;">Merged Ledger Active</span>
                    </div>
                @endif

                <!-- Summary Cards -->
                <div class="row mb-4 g-3">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-light">
                            <div class="card-body p-4">
                                <h6 class="text-secondary text-uppercase small fw-bold mb-2">Opening Balance</h6>
                                <h3 class="fw-bold text-dark mb-0">Rs. {{ number_format(abs($opening_balance), 2) }}</h3>
                                <p class="small text-muted mb-0 mt-1">
                                    {{ $opening_balance > 0.01 ? 'Receivable (Dr)' : ($opening_balance < -0.01 ? 'Payable (Cr)' : 'Zero Balance') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="card border-0 shadow-sm rounded-4 h-100 {{ $closing_balance > 0.01 ? 'bg-danger text-white' : ($closing_balance < -0.01 ? 'bg-success text-white' : 'bg-primary text-white') }}">
                            <div class="card-body p-4">
                                <h6 class="text-white-50 text-uppercase small fw-bold mb-2">Net Closing Balance</h6>
                                <h3 class="fw-bold mb-0">
                                    Rs. {{ number_format(abs($closing_balance), 2) }}
                                </h3>
                                <p class="small text-white-50 mb-0 mt-1">
                                    {{ $closing_balance > 0.01 ? 'Party Owes Us (Receivable / Dr)' : ($closing_balance < -0.01 ? 'We Owe Party (Payable / Cr)' : 'Fully Settled (0.00)') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                            <div class="card-body p-4">
                                <h6 class="text-secondary text-uppercase small fw-bold mb-2">Total Transactions</h6>
                                <h3 class="fw-bold text-dark mb-0">{{ is_countable($transactions) ? count($transactions) : $transactions->count() }}</h3>
                                <p class="small text-muted mb-0 mt-1">In selected period</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ledger Table -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-0">
                        <div class="table-responsive rounded-4">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-4 text-secondary fw-semibold text-uppercase small">Date</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small"
                                            style="width: 30%;">Description</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Debit (Dr)
                                        </th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Credit
                                            (Cr)</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small text-end">Running Balance
                                        </th>
                                        <th class="py-3 pe-4 text-secondary fw-semibold text-uppercase small text-center">
                                            Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($transactions->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-5">
                                                <i class="fas fa-inbox fa-3x mb-3 text-light"></i>
                                                <p class="mb-0">No transactions found in this period.</p>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($transactions as $txn)
                                            @php
                                                $tDate = is_array($txn) ? ($txn['date'] ?? $txn['created_at'] ?? '') : ($txn->created_at ?? $txn->date ?? '');
                                                $tDesc = is_array($txn) ? ($txn['description'] ?? '') : ($txn->description ?? '');
                                                $tDr   = (float)(is_array($txn) ? ($txn['debit'] ?? 0) : ($txn->debit ?? 0));
                                                $tCr   = (float)(is_array($txn) ? ($txn['credit'] ?? 0) : ($txn->credit ?? 0));
                                                $tBal  = (float)(is_array($txn) ? ($txn['balance'] ?? $txn['running_balance'] ?? 0) : ($txn->running_balance ?? $txn->balance ?? $txn->closing_balance ?? 0));
                                                $tType = is_array($txn) ? ($txn['type'] ?? '') : ($txn->type ?? '');
                                                $tSource = is_array($txn) ? ($txn['source'] ?? $txn['source_type'] ?? '') : ($txn->source ?? $txn->source_type ?? '');

                                                $descLower = strtolower($tDesc);
                                                $isDr = $tBal > 0.01;
                                                $isCr = $tBal < -0.01;
                                            @endphp
                                            <tr class="border-light-subtle">
                                                <td class="ps-4 fw-medium text-dark">
                                                    {{ \Carbon\Carbon::parse($tDate)->format('d/m/Y') }}</td>
                                                <td class="text-muted small">{{ $tDesc }}</td>
                                                <td class="text-end font-monospace text-dark">
                                                    {{ $tDr > 0 ? number_format($tDr, 2) : '-' }}
                                                </td>
                                                <td class="text-end font-monospace text-dark">
                                                    {{ $tCr > 0 ? number_format($tCr, 2) : '-' }}
                                                </td>
                                                <td
                                                    class="text-end fw-bold font-monospace {{ $isDr ? 'text-danger' : ($isCr ? 'text-success' : 'text-muted') }}">
                                                    {{ number_format(abs($tBal), 2) }}
                                                    <small class="fw-normal text-muted ms-1"
                                                        style="font-size: 0.7em;">{{ $isDr ? 'Dr' : ($isCr ? 'Cr' : '—') }}</small>
                                                </td>
                                                <td class="pe-4 text-center">
                                                    @if ($tType === 'purchase' || str_contains($descLower, 'purchase') || str_contains($descLower, 'grn'))
                                                        <span
                                                            class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3">📦 Purchase</span>
                                                    @elseif($tType === 'sale' || str_contains($descLower, 'sale') || str_contains($descLower, 'sin') || str_contains($descLower, 'inv'))
                                                        <span
                                                            class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">💰 Sale</span>
                                                    @elseif($tType === 'payment' || str_contains($descLower, 'payment') || str_contains($descLower, 'cpv'))
                                                        <span
                                                            class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3">💳 Payment</span>
                                                    @elseif($tType === 'receipt' || str_contains($descLower, 'receipt') || str_contains($descLower, 'crv'))
                                                        <span
                                                            class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">✔ Receipt</span>
                                                    @elseif($tType === 'return' || str_contains($descLower, 'return') || str_contains($descLower, 'prtn') || str_contains($descLower, 'srn'))
                                                        <span
                                                            class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">↩ Return</span>
                                                    @else
                                                        <span
                                                            class="badge bg-light text-secondary border rounded-pill px-3">📖 Journal</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot class="bg-light border-top">
                                    <tr>
                                        <td colspan="4" class="text-end py-3 fw-bold text-dark text-uppercase small">
                                            Net Closing Balance:</td>
                                        <td
                                            class="text-end py-3 fw-bold font-monospace {{ $closing_balance > 0.01 ? 'text-danger' : ($closing_balance < -0.01 ? 'text-success' : 'text-muted') }}">
                                            Rs. {{ number_format(abs($closing_balance), 2) }}
                                            <small
                                                class="fw-normal text-muted ms-1">{{ $closing_balance > 0.01 ? 'Dr (Receivable)' : ($closing_balance < -0.01 ? 'Cr (Payable)' : 'Settled') }}</small>
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .main-content {
                margin: 0;
                padding: 0;
            }

            .btn,
            form,
            .page-header .col-lg-4,
            .text-end a {
                display: none !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .card-body {
                padding: 0 !important;
            }

            .table-responsive {
                overflow: visible !important;
            }
        }
    </style>
@endsection
