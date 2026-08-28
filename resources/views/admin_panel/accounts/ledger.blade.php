@extends('admin_panel.layout.app')

@section('content')
    <style>
        .ledger-header {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px 8px 0 0;
        }

        .balance-positive {
            color: #198754;
            font-weight: bold;
        }

        .balance-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .table-ledger th {
            background-color: #212529 !important;
            color: #fff;
            text-align: center;
        }

        .table-ledger td {
            vertical-align: middle;
        }

        .export-btn-group .btn {
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .export-btn-group .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .print-header-area {
            display: none;
        }

        @media print {
            .no-print,
            .ledger-header button,
            .card-footer,
            form {
                display: none !important;
            }
            .print-header-area {
                display: block !important;
                margin-bottom: 20px;
                text-align: center;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .table-ledger th {
                background-color: #f0f0f0 !important;
                color: #000 !important;
            }
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid mt-4">

                {{-- Hidden Header for Direct Print --}}
                <div class="print-header-area">
                    <h3 class="fw-bold mb-1">THREE STARS MEDICAL SUPPLIES</h3>
                    <h5 class="text-secondary mb-2">GENERAL LEDGER STATEMENT</h5>
                    <div class="small text-muted">
                        <strong>Account:</strong> {{ $account->title }} ({{ $account->account_code }}) &nbsp;|&nbsp;
                        <strong>Head:</strong> {{ $account->head->name ?? 'N/A' }} &nbsp;|&nbsp;
                        <strong>Period:</strong> {{ $fromDate ? date('d-M-Y', strtotime($fromDate)) : 'Beginning' }} to {{ $toDate ? date('d-M-Y', strtotime($toDate)) : date('d-M-Y') }}
                    </div>
                    <hr>
                </div>

                <div class="card shadow-sm border-0">
                    <!-- Ledger Header -->
                    <div class="ledger-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div>
                            <h4 class="mb-1 text-primary d-flex align-items-center gap-2">
                                <i class="bi bi-book me-2"></i> General Ledger
                                <button type="button"
                                    class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 ms-2 rounded-pill px-3 shadow-none no-print"
                                    data-toggle="modal" data-target="#journalInfoModal" title="How Journal Entries Work?">
                                    <i class="bi bi-info-circle"></i> Info
                                </button>
                            </h4>
                            <h5 class="text-dark mb-1">{{ $account->title }} <span
                                    class="text-muted fs-6">({{ $account->account_code }})</span></h5>
                            <p class="mb-0 text-muted">Head: {{ $account->head->name ?? 'N/A' }} | Type:
                                <span class="badge bg-light text-dark border">{{ $account->type }}</span>
                            </p>
                        </div>
                        <div class="text-end">
                            <h3 class="{{ ($account->calculated_balance ?? $account->current_balance) >= 0 ? 'balance-positive' : 'balance-negative' }} mb-0">
                                {{ number_format(abs($account->calculated_balance ?? $account->current_balance), 2) }}
                                <small class="fs-6 text-muted">{{ ($account->type === 'Credit' ? (($account->calculated_balance ?? $account->current_balance) >= 0 ? 'Cr' : 'Dr') : (($account->calculated_balance ?? $account->current_balance) >= 0 ? 'Dr' : 'Cr')) }}</small>
                            </h3>
                            <span class="badge bg-secondary">Current Balance</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Filters & Action Buttons -->
                        <div class="row g-3 mb-4 no-print align-items-end">
                            <div class="col-lg-7 col-md-12">
                                <form method="GET" action="{{ route('accounts.ledger', $account->id) }}" class="row g-2">
                                    <div class="col-sm-4">
                                        <label class="form-label small fw-bold text-muted mb-1">From Date</label>
                                        <input type="date" name="from_date" value="{{ request('from_date', $fromDate) }}"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small fw-bold text-muted mb-1">To Date</label>
                                        <input type="date" name="to_date" value="{{ request('to_date', $toDate) }}"
                                            class="form-control form-control-sm">
                                    </div>
                                    <div class="col-sm-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold">
                                            <i class="bi bi-filter"></i> Filter
                                        </button>
                                    </div>
                                    <div class="col-sm-2 d-flex align-items-end">
                                        <a href="{{ route('accounts.ledger', $account->id) }}"
                                            class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="bi bi-arrow-clockwise"></i> Reset
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <div class="col-lg-5 col-md-12 text-lg-end d-flex flex-wrap justify-content-lg-end gap-2 export-btn-group">
                                {{-- PDF Download Button --}}
                                <a href="{{ route('accounts.ledger.pdf', array_merge(['id' => $account->id], request()->all())) }}"
                                    class="btn btn-sm btn-danger"
                                    target="_blank"
                                    title="Download General Ledger in PDF Format">
                                    <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
                                </a>

                                {{-- Excel Download Button --}}
                                <a href="{{ route('accounts.ledger.excel', array_merge(['id' => $account->id], request()->all())) }}"
                                    class="btn btn-sm btn-success"
                                    title="Download General Ledger in Excel Format">
                                    <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
                                </a>

                                {{-- Direct Print Button --}}
                                <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-dark" title="Print this Report">
                                    <i class="bi bi-printer-fill"></i> Print
                                </button>

                                {{-- Back Button --}}
                                <a href="{{ route('view_all') }}" class="btn btn-sm btn-secondary">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>

                        <!-- Ledger Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover table-ledger align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th width="12%">Date</th>
                                        <th width="15%">Voucher No</th>
                                        <th width="33%" class="text-start">Description / Narration</th>
                                        <th width="12%" class="text-end">Debit</th>
                                        <th width="12%" class="text-end">Credit</th>
                                        <th width="16%" class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Opening Balance Row --}}
                                    <tr class="table-light">
                                        <td colspan="5" class="text-end fw-bold">Opening Balance</td>
                                        <td class="text-end fw-bold">
                                            {{ number_format(abs($openingBalance), 2) }}
                                            <small class="text-muted">{{ $openingBalanceType }}</small>
                                        </td>
                                    </tr>

                                    @forelse ($entries as $entry)
                                        @php
                                            $debit = (float)($entry->debit ?? 0);
                                            $credit = (float)($entry->credit ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="text-center">{{ $entry->entry_date ? $entry->entry_date->format('d-M-Y') : '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border">{{ $entry->computed_voucher_no }}</span>
                                            </td>
                                            <td>
                                                <span class="text-dark">{{ $entry->description }}</span>
                                                @if ($entry->computed_party_name)
                                                    <br><small class="text-primary fw-semibold"><i class="bi bi-person"></i>
                                                        {{ $entry->computed_party_name }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end text-success fw-semibold">
                                                {{ $debit > 0 ? number_format($debit, 2) : '-' }}
                                            </td>
                                            <td class="text-end text-danger fw-semibold">
                                                {{ $credit > 0 ? number_format($credit, 2) : '-' }}
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format(abs($entry->computed_running_balance), 2) }}
                                                <small class="text-muted">{{ $entry->computed_balance_type }}</small>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                                No transactions found for this account in the selected period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="3" class="text-end fw-bold">Total Period</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($totalDebit, 2) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ number_format($totalCredit, 2) }}</td>
                                        <td class="text-end fw-bold text-info">
                                            {{ number_format(abs($closingBalance), 2) }}
                                            <small class="text-white">({{ $closingBalanceType }})</small>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-muted text-center py-2 bg-light">
                        <small>End of Report &bull; Three Star Medical ERP</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Journal Info Modal --}}
    <div class="modal fade" id="journalInfoModal" tabindex="-1" role="dialog" aria-labelledby="journalInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="journalInfoModalLabel">
                        <i class="bi bi-info-circle me-1"></i> General Ledger Information
                    </h5>
                    <button type="button" class="close text-white border-0 bg-transparent fs-4" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h6 class="fw-bold text-dark">How Ledger Balances Work:</h6>
                    <ul class="small text-muted mb-3">
                        <li><strong>Debit Accounts (Assets, Expenses):</strong> Increase with Debit (+), Decrease with Credit (-).</li>
                        <li><strong>Credit Accounts (Liabilities, Equity, Revenue):</strong> Increase with Credit (+), Decrease with Debit (-).</li>
                        <li><strong>Dr / Cr Notation:</strong> Indicates whether the remaining balance is a Debit or Credit normal balance.</li>
                    </ul>
                    <h6 class="fw-bold text-dark">Export Options:</h6>
                    <p class="small text-muted mb-0">
                        You can download the full filtered statement in <strong>PDF</strong> or <strong>Excel</strong> format using the buttons above the table.
                    </p>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

