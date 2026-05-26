@extends('admin_panel.layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .page-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1a2340;
        }

        .page-title small {
            color: #8897b0;
            font-size: .82rem;
            font-weight: 400;
            display: block;
            margin-top: 2px;
        }

        .table-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .04);
            border: 1px solid #e2e8f0;
            margin-top: 20px;
        }

        .table-container {
            padding: 20px;
        }

        .table thead th {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .badge-status {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-cleared {
            background: #d1fae5;
            color: #059669;
        }

        .status-bounced {
            background: #fee2e2;
            color: #dc2626;
        }

        .due-row {
            background-color: #fff6f6 !important;
        }

        .due-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            border-radius: 4px;
            padding: 2px 5px;
            margin-left: 5px;
        }
    </style>

    <div class="container-fluid px-4 py-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="page-title">Cheque Management <small>Monitor and process received cheques</small></div>
                <div class="text-muted mt-1" style="font-size: 0.85rem;">Manage pending, cleared, and bounced cheques.</div>
            </div>
            <div>
                <form action="{{ route('cheques.index') }}" method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Cheques</option>
                        <option value="pending" {{ request('status', 'pending') == 'pending' ? 'selected' : '' }}>Pending
                            Only</option>
                        <option value="cleared" {{ request('status') == 'cleared' ? 'selected' : '' }}>Cleared</option>
                        <option value="bounced" {{ request('status') == 'bounced' ? 'selected' : '' }}>Bounced</option>
                    </select>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success mt-3 mb-0">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger mt-3 mb-0">{{ session('error') }}</div>
        @endif

        <div class="table-card">
            <div class="table-container table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date Received</th>
                            <th>Voucher / Ref</th>
                            <th>Party</th>
                            <th>Cheque Info</th>
                            <th>Target Bank</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $cheque)
                            @php
                                $isDue =
                                    $cheque->status === 'pending' &&
                                    \Carbon\Carbon::parse($cheque->cheque_date)->lte(now());
                            @endphp
                            <tr class="{{ $isDue ? 'due-row' : '' }}">
                                <td>
                                    {{ $cheque->voucherMaster ? $cheque->voucherMaster->date->format('M d, Y') : '-' }}
                                </td>
                                <td>
                                    @if ($cheque->voucherMaster)
                                        <strong>{{ $cheque->voucherMaster->voucher_no }}</strong>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($cheque->voucherMaster && $cheque->voucherMaster->party)
                                        {{ $cheque->voucherMaster->party->customer_name ?? ($cheque->voucherMaster->party->name ?? '-') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $cheque->cheque_no }} <span class="text-muted"
                                            style="font-weight:400; font-size:0.8rem;">({{ $cheque->bank_name }})</span>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b;">
                                        Due: {{ \Carbon\Carbon::parse($cheque->cheque_date)->format('M d, Y') }}
                                        @if ($isDue)
                                            <span class="due-badge">DUE</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $cheque->actualAccount ? $cheque->actualAccount->title : '-' }}
                                </td>
                                <td class="fw-bold">
                                    {{ number_format($cheque->amount, 2) }}
                                </td>
                                <td>
                                    <span class="badge-status status-{{ $cheque->status }}">{{ $cheque->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($cheque->status === 'pending')
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border" type="button"
                                                data-toggle="dropdown" aria-expanded="false">
                                                Actions <i class="bi bi-chevron-down"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-right shadow-sm">
                                                <li>
                                                    <form action="{{ route('cheques.clear', $cheque->id) }}" method="POST"
                                                        onsubmit="return confirm('Clear this cheque and post to bank account?');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-success"><i
                                                                class="bi bi-check-circle me-2"></i> Mark Cleared</button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('cheques.bounce', $cheque->id) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Bounce this cheque and reinstate customer balance?');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger"><i
                                                                class="bi bi-x-circle me-2"></i> Mark Bounced</button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No cheques found matching the
                                    criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
