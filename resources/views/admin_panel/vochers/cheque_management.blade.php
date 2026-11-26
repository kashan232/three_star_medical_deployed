@extends('admin_panel.layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .chq-page {
            background: #f4f7fb;
            min-height: calc(100vh - 80px);
            padding: 22px 16px 40px;
        }
        .chq-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 20px;
        }
        .chq-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chq-subtitle {
            color: #64748b;
            font-size: 0.86rem;
            margin-top: 2px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .kpi-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px 18px;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            text-decoration: none !important;
            display: block;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.07);
        }
        .kpi-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .kpi-value {
            font-size: 1.32rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 6px;
        }
        .kpi-meta {
            font-size: 0.78rem;
            color: #64748b;
            margin-top: 3px;
        }
        .bank-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .bank-pill {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #3b82f6;
            border-radius: 10px;
            padding: 12px 15px;
        }
        .filter-card, .table-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.04);
        }
        .filter-card {
            padding: 16px;
            margin-bottom: 18px;
        }
        .nav-pills-erp .nav-link {
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.84rem;
            color: #475569;
            padding: 7px 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            margin-right: 6px;
        }
        .nav-pills-erp .nav-link.active {
            background: #1e293b;
            color: #fff;
            border-color: #1e293b;
        }
        .chq-table thead th {
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #475569;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .chq-table tbody td {
            vertical-align: middle;
            font-size: 0.85rem;
        }
        .dir-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 0.71rem;
            font-weight: 800;
            text-transform: uppercase;
        }
        .dir-issued {
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
        }
        .dir-received {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-status {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-pending   { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-deposited { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-cleared   { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }
        .status-bounced   { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .status-cancelled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .due-row {
            background-color: #fff9f9 !important;
        }
        .due-chip {
            font-size: 0.68rem;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 2px;
        }
        .due-overdue { background: #dc2626; color: #fff; }
        .due-today   { background: #f59e0b; color: #111827; }
        .due-future  { background: #e0e7ff; color: #3730a3; }
        .pdc-quick-btn {
            font-size: 0.72rem;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 700;
        }
    </style>

    <div class="chq-page">
        <div class="container-fluid">

            <!-- Header -->
            <div class="chq-header">
                <div>
                    <h1 class="chq-title">
                        <span class="badge badge-primary p-2" style="border-radius:10px; background:#4f46e5;">
                            <i class="fas fa-money-check-alt"></i>
                        </span>
                        Cheque Management &amp; PDC Control Center
                    </h1>
                    <div class="chq-subtitle">
                        Track post-dated issued cheques (BPV), customer cheques in hand (BRV), bank clearing, bounces, and bank liquidity.
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @canany(['payment.voucher.create', 'receipts.voucher.create', 'sales.create'])
                        <button type="button" class="btn btn-primary fw-bold shadow-sm mr-2"
                            style="background:#7c3aed; border-color:#7c3aed; border-radius:8px;"
                            onclick="openCreateChequeModal('issued')">
                            <i class="fas fa-arrow-up mr-1"></i> + Issue Cheque (PDC)
                        </button>
                        <button type="button" class="btn btn-success fw-bold shadow-sm mr-2"
                            style="border-radius:8px;"
                            onclick="openCreateChequeModal('received')">
                            <i class="fas fa-arrow-down mr-1"></i> + Receive Cheque
                        </button>
                    @endcanany
                    <a href="{{ route('vouchers.list', 'bpv') }}" class="btn btn-light border font-weight-bold mr-1" style="border-radius:8px;">
                        <i class="fas fa-file-invoice-dollar text-primary mr-1"></i> BPV List
                    </a>
                    <a href="{{ route('vouchers.list', 'brv') }}" class="btn btn-light border font-weight-bold" style="border-radius:8px;">
                        <i class="fas fa-university text-success mr-1"></i> BRV List
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
                    <strong>Validation Error:</strong>
                    <ul class="mb-0 mt-1 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            <!-- KPI Summary Cards -->
            <div class="kpi-grid">
                <a href="{{ route('cheques.index', ['type' => 'issued', 'status' => 'active']) }}" class="kpi-card" style="border-top: 3px solid #7c3aed;">
                    <div class="kpi-label">
                        <span>Pending Issued (PDC)</span>
                        <i class="fas fa-arrow-circle-up" style="color:#7c3aed; font-size:1rem;"></i>
                    </div>
                    <div class="kpi-value" style="color:#6d28d9;">Rs. {{ number_format($stats['pending_issued_amount'], 2) }}</div>
                    <div class="kpi-meta">{{ $stats['pending_issued_count'] }} cheque(s) awaiting bank debit</div>
                </a>

                <a href="{{ route('cheques.index', ['type' => 'received', 'status' => 'active']) }}" class="kpi-card" style="border-top: 3px solid #2563eb;">
                    <div class="kpi-label">
                        <span>Cheques In Hand / Deposited</span>
                        <i class="fas fa-arrow-circle-down" style="color:#2563eb; font-size:1rem;"></i>
                    </div>
                    <div class="kpi-value" style="color:#1d4ed8;">Rs. {{ number_format($stats['pending_received_amount'], 2) }}</div>
                    <div class="kpi-meta">{{ $stats['pending_received_count'] }} incoming cheque(s) pending</div>
                </a>

                <a href="{{ route('cheques.index', ['status' => 'due']) }}" class="kpi-card" style="border-top: 3px solid #dc2626;">
                    <div class="kpi-label">
                        <span>Due Today / Overdue</span>
                        <i class="fas fa-clock" style="color:#dc2626; font-size:1rem;"></i>
                    </div>
                    <div class="kpi-value" style="color:#dc2626;">Rs. {{ number_format($stats['due_amount'], 2) }}</div>
                    <div class="kpi-meta">{{ $stats['due_count'] }} cheque(s) matured for clearing</div>
                </a>

                <a href="{{ route('cheques.index', ['status' => 'cleared']) }}" class="kpi-card" style="border-top: 3px solid #059669;">
                    <div class="kpi-label">
                        <span>Cleared Cheques</span>
                        <i class="fas fa-check-circle" style="color:#059669; font-size:1rem;"></i>
                    </div>
                    <div class="kpi-value" style="color:#047857;">Rs. {{ number_format($stats['cleared_amount'], 2) }}</div>
                    <div class="kpi-meta">{{ $stats['cleared_count'] }} cheque(s) honoured</div>
                </a>

                <a href="{{ route('cheques.index', ['status' => 'bounced']) }}" class="kpi-card" style="border-top: 3px solid #f59e0b;">
                    <div class="kpi-label">
                        <span>Bounced / Cancelled</span>
                        <i class="fas fa-ban" style="color:#d97706; font-size:1rem;"></i>
                    </div>
                    <div class="kpi-value" style="color:#b45309;">Rs. {{ number_format($stats['bounced_amount'], 2) }}</div>
                    <div class="kpi-meta">{{ $stats['bounced_count'] }} cheque(s) reversed</div>
                </a>
            </div>

            <!-- Bank Balances & Pending PDC Commitments Strip -->
            @if ($bankAccounts->isNotEmpty())
                <div class="bank-strip">
                    @foreach ($bankAccounts as $bank)
                        @php
                            $projected = $bank->live_balance + $bank->pending_received_total - $bank->pending_issued_total;
                        @endphp
                        <div class="bank-pill">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-dark" style="font-size:0.84rem;">
                                    <i class="fas fa-university text-primary mr-1"></i> {{ $bank->title }}
                                </span>
                                <span class="badge {{ $bank->live_balance >= 0 ? 'badge-success' : 'badge-danger' }}">
                                    Bal: Rs. {{ number_format($bank->live_balance, 2) }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between mt-2" style="font-size:0.75rem; color:#64748b;">
                                <span>Pending In: <strong class="text-primary">+{{ number_format($bank->pending_received_total, 0) }}</strong></span>
                                <span>Pending Out (PDC): <strong class="text-danger">-{{ number_format($bank->pending_issued_total, 0) }}</strong></span>
                                <span>Net Proj: <strong class="{{ $projected >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($projected, 0) }}</strong></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Direction Tabs + Filter Bar -->
            <div class="filter-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <ul class="nav nav-pills nav-pills-erp">
                        <li class="nav-item">
                            <a class="nav-link {{ $direction === 'all' ? 'active' : '' }}"
                                href="{{ route('cheques.index', array_merge(request()->query(), ['type' => 'all'])) }}">
                                All Cheques
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $direction === 'issued' ? 'active' : '' }}"
                                href="{{ route('cheques.index', array_merge(request()->query(), ['type' => 'issued'])) }}">
                                <i class="fas fa-arrow-up mr-1"></i> Issued (PDC / Payments)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $direction === 'received' ? 'active' : '' }}"
                                href="{{ route('cheques.index', array_merge(request()->query(), ['type' => 'received'])) }}">
                                <i class="fas fa-arrow-down mr-1"></i> Received (In Hand)
                            </a>
                        </li>
                    </ul>
                    <span class="text-muted small font-weight-bold mt-2 mt-md-0">
                        Showing {{ $cheques->count() }} record(s)
                    </span>
                </div>

                <form action="{{ route('cheques.index') }}" method="GET" class="row">
                    <input type="hidden" name="type" value="{{ $direction }}">

                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-secondary mb-1">Status</label>
                        <select name="status" class="form-control form-control-sm">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active (Pending + Deposited)</option>
                            <option value="due" {{ $status === 'due' ? 'selected' : '' }}>Due / Overdue Only</option>
                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending Only</option>
                            <option value="deposited" {{ $status === 'deposited' ? 'selected' : '' }}>Deposited</option>
                            <option value="cleared" {{ $status === 'cleared' ? 'selected' : '' }}>Cleared</option>
                            <option value="bounced" {{ $status === 'bounced' ? 'selected' : '' }}>Bounced</option>
                            <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-secondary mb-1">Bank Account</label>
                        <select name="bank_account_id" class="form-control form-control-sm">
                            <option value="all">All Bank Accounts</option>
                            @foreach ($bankAccounts as $bank)
                                <option value="{{ $bank->id }}" {{ (string) request('bank_account_id') === (string) $bank->id ? 'selected' : '' }}>
                                    {{ $bank->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-secondary mb-1">Due From</label>
                        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-2 col-sm-6 mb-2">
                        <label class="small font-weight-bold text-secondary mb-1">Due To</label>
                        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-3 col-sm-12 mb-2">
                        <label class="small font-weight-bold text-secondary mb-1">Search (Chq #, Voucher, Party)</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search...">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary font-weight-bold">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                                <a href="{{ route('cheques.index') }}" class="btn btn-outline-secondary" title="Reset">
                                    <i class="fas fa-undo"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Cheques Table -->
            <div class="table-card p-3">
                <div class="table-responsive">
                    <table class="table table-hover chq-table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Cheque Info</th>
                                <th>Due / Cheque Date</th>
                                <th>Voucher #</th>
                                <th>Party / Account</th>
                                <th>Bank Account</th>
                                <th class="text-right">Amount (Rs.)</th>
                                <th>Status</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($cheques as $cheque)
                                @php
                                    $isDue = $cheque->isDue();
                                    $daysDiff = $cheque->daysUntilDue();
                                    $isActive = in_array($cheque->status, ['pending', 'deposited'], true);
                                    $bankBal = $cheque->actualAccount ? (float) $cheque->actualAccount->calculated_balance : 0;
                                @endphp
                                <tr class="{{ $isDue ? 'due-row' : '' }}">
                                    <td class="text-muted font-weight-bold">{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($cheque->type === 'issued')
                                            <span class="dir-badge dir-issued"><i class="fas fa-arrow-up"></i> Issued</span>
                                        @else
                                            <span class="dir-badge dir-received"><i class="fas fa-arrow-down"></i> Received</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $cheque->cheque_no }}</div>
                                        @if ($cheque->bank_name)
                                            <div class="text-muted small">{{ $cheque->bank_name }}</div>
                                        @endif
                                        <div class="text-muted" style="font-size:0.72rem;">
                                            Issued: {{ $cheque->issue_date ? $cheque->issue_date->format('d M, Y') : ($cheque->voucherMaster?->date?->format('d M, Y') ?? '-') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">
                                            {{ $cheque->cheque_date ? $cheque->cheque_date->format('d M, Y') : '-' }}
                                        </div>
                                        @if ($isActive && $daysDiff !== null)
                                            @if ($daysDiff < 0)
                                                <span class="due-chip due-overdue">OVERDUE {{ abs($daysDiff) }}d</span>
                                            @elseif ($daysDiff === 0)
                                                <span class="due-chip due-today">DUE TODAY</span>
                                            @else
                                                <span class="due-chip due-future">IN {{ $daysDiff }}d</span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @if ($cheque->voucherMaster)
                                            <a href="{{ route('vouchers.print_unified', $cheque->voucherMaster->id) }}"
                                                target="_blank" class="badge badge-light border font-weight-bold text-primary">
                                                {{ $cheque->voucherMaster->voucher_no }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $cheque->party_display_name }}</div>
                                        <span class="text-muted" style="font-size:0.72rem;">{{ $cheque->party_category }}</span>
                                    </td>
                                    <td>
                                        @if ($cheque->actualAccount)
                                            <div class="font-weight-bold">{{ $cheque->actualAccount->title }}</div>
                                            <small class="{{ $bankBal >= 0 ? 'text-success' : 'text-danger' }}">
                                                Bal: Rs. {{ number_format($bankBal, 2) }}
                                            </small>
                                        @else
                                            <span class="text-muted">Not Assigned</span>
                                        @endif
                                    </td>
                                    <td class="text-right font-weight-bold" style="font-size:0.95rem;">
                                        {{ number_format((float) $cheque->amount, 2) }}
                                    </td>
                                    <td>
                                        <span class="badge-status status-{{ $cheque->status }}">{{ $cheque->status }}</span>
                                        @if ($cheque->status === 'cleared' && $cheque->clearance_date)
                                            <div class="text-muted mt-1" style="font-size:0.72rem;">
                                                On {{ $cheque->clearance_date->format('d M, Y') }}
                                                @if ($cheque->clearanceVoucher)
                                                    &middot; <a href="{{ route('vouchers.print_unified', $cheque->clearanceVoucher->id) }}" target="_blank">{{ $cheque->clearanceVoucher->voucher_no }}</a>
                                                @endif
                                            </div>
                                        @elseif (in_array($cheque->status, ['bounced', 'cancelled'], true))
                                            <div class="text-danger mt-1" style="font-size:0.72rem;">
                                                {{ $cheque->bounce_reason ?? '-' }}
                                                @if ($cheque->bounceVoucher)
                                                    &middot; <a href="{{ route('vouchers.print_unified', $cheque->bounceVoucher->id) }}" target="_blank">{{ $cheque->bounceVoucher->voucher_no }}</a>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle font-weight-bold" type="button"
                                                data-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow-sm">
                                                @if ($isActive)
                                                    @if ($cheque->type === 'received' && $cheque->status === 'pending')
                                                        <button type="button" class="dropdown-item text-info font-weight-bold btn-chq-deposit"
                                                            data-id="{{ $cheque->id }}"
                                                            data-no="{{ $cheque->cheque_no }}"
                                                            data-bank-id="{{ (int) ($cheque->actual_account_id ?? 0) }}">
                                                            <i class="fas fa-university mr-2"></i> Mark Deposited
                                                        </button>
                                                    @endif
                                                    <button type="button" class="dropdown-item text-success font-weight-bold btn-chq-clear"
                                                        data-id="{{ $cheque->id }}"
                                                        data-no="{{ $cheque->cheque_no }}"
                                                        data-type="{{ $cheque->type }}"
                                                        data-amount="{{ (float) $cheque->amount }}"
                                                        data-bank-id="{{ (int) ($cheque->actual_account_id ?? 0) }}"
                                                        data-party="{{ $cheque->party_display_name }}">
                                                        <i class="fas fa-check-circle mr-2"></i> Mark Cleared
                                                    </button>
                                                    <button type="button" class="dropdown-item text-danger font-weight-bold btn-chq-bounce"
                                                        data-id="{{ $cheque->id }}"
                                                        data-no="{{ $cheque->cheque_no }}"
                                                        data-type="{{ $cheque->type }}"
                                                        data-amount="{{ (float) $cheque->amount }}"
                                                        data-party="{{ $cheque->party_display_name }}">
                                                        <i class="fas fa-times-circle mr-2"></i> Mark Bounced
                                                    </button>
                                                    <button type="button" class="dropdown-item text-secondary font-weight-bold btn-chq-cancel"
                                                        data-id="{{ $cheque->id }}"
                                                        data-no="{{ $cheque->cheque_no }}"
                                                        data-type="{{ $cheque->type }}">
                                                        <i class="fas fa-ban mr-2"></i> Cancel / Stop Cheque
                                                    </button>
                                                    <div class="dropdown-divider"></div>
                                                @elseif ($cheque->status === 'cleared' && $cheque->clearance_voucher_id)
                                                    <form action="{{ route('cheques.undo_clear', $cheque->id) }}" method="POST"
                                                        onsubmit="return confirm('Reverse clearance of this cheque? This will delete the clearance journal entry and revert cheque to Pending.');">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-warning font-weight-bold">
                                                            <i class="fas fa-undo mr-2"></i> Reverse Clearance
                                                        </button>
                                                    </form>
                                                    <div class="dropdown-divider"></div>
                                                @endif
                                                @if ($cheque->voucherMaster)
                                                    <a href="{{ route('vouchers.print_unified', $cheque->voucherMaster->id) }}" target="_blank" class="dropdown-item">
                                                        <i class="fas fa-print mr-2 text-primary"></i> Print Source Voucher
                                                    </a>
                                                    @if ($cheque->status === 'pending')
                                                        <a href="{{ route('vouchers.edit_page', $cheque->voucherMaster->id) }}" class="dropdown-item">
                                                            <i class="fas fa-edit mr-2 text-secondary"></i> Edit Source Voucher
                                                        </a>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        <i class="fas fa-money-check fa-3x mb-3 d-block" style="opacity:0.3;"></i>
                                        No cheques found matching your current filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Issue / Receive Cheque Directly -->
    <div class="modal fade" id="chequeEntryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius:14px;">
                <div class="modal-header text-white" id="chequeModalHeader" style="background:#7c3aed; border-radius:14px 14px 0 0;">
                    <h5 class="modal-title font-weight-bold" id="chequeModalTitle">
                        <i class="fas fa-money-check-alt mr-2"></i> Issue Cheque (PDC / Bank Payment)
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form action="{{ route('cheques.store') }}" method="POST" id="chequeEntryForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Cheque Type <span class="text-danger">*</span></label>
                                <select name="type" id="modalChequeType" class="form-control" onchange="onModalChequeTypeChange(this.value)" required>
                                    <option value="issued">Issued Cheque (Payment Out / PDC)</option>
                                    <option value="received">Received Cheque (Payment In)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Party Category <span class="text-danger">*</span></label>
                                <select id="modalTargetCategory" class="form-control" onchange="populateModalTargets(this.value)">
                                    <option value="vendor">Vendor / Supplier</option>
                                    <option value="customer">Customer</option>
                                    <option value="expense">Expense Account</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Branch <span class="text-danger">*</span></label>
                                @if ($isSuperAdmin && count($branches) > 0)
                                    <select name="branch_id" class="form-control" required>
                                        @foreach ($branches as $b)
                                            <option value="{{ $b->id }}" {{ (auth()->user()->getBranchId() == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light font-weight-bold" value="{{ $userBranch->name ?? 'HEAD OFFICE' }}" readonly>
                                    <input type="hidden" name="branch_id" value="{{ $userBranch->id ?? 1 }}">
                                @endif
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small" id="modalTargetLabel">Pay To (Party / Account) <span class="text-danger">*</span></label>
                                <select name="target_id" id="modalTargetSelect" class="form-control" style="width:100%;" required></select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="font-weight-bold small" id="modalBankLabel">Bank Account <span class="text-danger">*</span></label>
                                <select name="bank_account_id" id="modalBankSelect" class="form-control" onchange="updateModalBankNotice()" required>
                                    <option value="">-- Select Bank Account --</option>
                                    @foreach ($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}" data-balance="{{ $bank->live_balance }}">
                                            {{ $bank->title }} (Bal: Rs. {{ number_format($bank->live_balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Cheque Number <span class="text-danger">*</span></label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="e.g. 1145628" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Entry / Issue Date <span class="text-danger">*</span></label>
                                <input type="date" name="issue_date" id="modalIssueDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="font-weight-bold small">Cheque / Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="cheque_date" id="modalChequeDate" class="form-control" value="{{ date('Y-m-d') }}" required>
                                <div class="mt-1">
                                    <button type="button" class="btn btn-outline-primary pdc-quick-btn" onclick="setPdcDays(7)">+7d</button>
                                    <button type="button" class="btn btn-outline-primary pdc-quick-btn" onclick="setPdcDays(10)">+10d</button>
                                    <button type="button" class="btn btn-outline-primary pdc-quick-btn" onclick="setPdcDays(15)">+15d</button>
                                    <button type="button" class="btn btn-outline-primary pdc-quick-btn" onclick="setPdcDays(30)">+30d</button>
                                </div>
                            </div>

                            <div class="col-md-5 mb-3">
                                <label class="font-weight-bold small">Amount (Rs.) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="modalChequeAmount" class="form-control font-weight-bold" placeholder="0.00" oninput="updateModalBankNotice()" required>
                            </div>

                            <div class="col-md-7 mb-3">
                                <label class="font-weight-bold small" id="modalBankNameLabel">Drawer / Issuing Bank Name (Optional)</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="e.g. Meezan Bank / Auto-filled if blank">
                            </div>

                            <div class="col-12 mb-2">
                                <label class="font-weight-bold small">Remarks / Narration</label>
                                <input type="text" name="remarks" class="form-control" placeholder="e.g. 10-day PDC against supplier invoice / customer recovery">
                            </div>
                        </div>

                        <div id="modalPdcNotice" class="alert alert-info mb-0 mt-2 small" style="display:none;"></div>
                    </div>
                    <div class="modal-footer bg-light" style="border-radius:0 0 14px 14px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary font-weight-bold" id="modalSaveChequeBtn">
                            <i class="fas fa-save mr-1"></i> Save Cheque &amp; Post Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Deposit Received Cheque -->
    <div class="modal fade" id="depositModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-university mr-2"></i> Deposit Cheque into Bank</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="depositForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-3">Select the bank account where Cheque <strong id="depChqNo"></strong> is being deposited:</p>
                        <div class="form-group">
                            <label class="font-weight-bold small">Target Deposit Bank <span class="text-danger">*</span></label>
                            <select name="actual_account_id" id="depBankSelect" class="form-control" required>
                                @foreach ($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->title }} (Bal: Rs. {{ number_format($bank->live_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info text-white font-weight-bold">Confirm Deposit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Clear Cheque -->
    <div class="modal fade" id="clearModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-2"></i> Clear Cheque in Bank</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="clearForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="p-3 bg-light rounded mb-3 small">
                            <div><strong>Cheque #:</strong> <span id="clrChqNo"></span> (<span id="clrDirection"></span>)</div>
                            <div><strong>Party:</strong> <span id="clrParty"></span></div>
                            <div><strong>Amount:</strong> Rs. <span id="clrAmountText" class="font-weight-bold"></span></div>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Bank Account <span class="text-danger">*</span></label>
                            <select name="actual_account_id" id="clrBankSelect" class="form-control" onchange="checkClearBankBalance()" required>
                                @foreach ($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}" data-balance="{{ $bank->live_balance }}">
                                        {{ $bank->title }} (Bal: Rs. {{ number_format($bank->live_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="clrOverdraftWarning" class="alert alert-warning small" style="display:none;"></div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Clearance Date <span class="text-danger">*</span></label>
                            <input type="date" name="clearance_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Clearance Notes (Optional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="e.g. Cleared via bank statement">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold">Confirm &amp; Clear Cheque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Bounce Cheque -->
    <div class="modal fade" id="bounceModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-times-circle mr-2"></i> Mark Cheque as Bounced</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="bounceForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning small">
                            Bouncing Cheque <strong id="bncChqNo"></strong> (<span id="bncParty"></span>, Rs. <span id="bncAmount"></span>) will automatically post a Reversal JV and reinstate the party's ledger balance.
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">Bounce / Dishonour Reason <span class="text-danger">*</span></label>
                            <select name="bounce_reason" class="form-control" required>
                                <option value="Insufficient Funds">Insufficient Funds</option>
                                <option value="Signature Differs / Mismatch">Signature Differs / Mismatch</option>
                                <option value="Refer to Drawer">Refer to Drawer</option>
                                <option value="Payment Stopped by Drawer">Payment Stopped by Drawer</option>
                                <option value="Stale / Outdated Cheque">Stale / Outdated Cheque</option>
                                <option value="Account Closed">Account Closed</option>
                                <option value="Other Dishonour Reason">Other Dishonour Reason</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Bounce Date <span class="text-danger">*</span></label>
                            <input type="date" name="bounce_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger font-weight-bold">Confirm Bounce &amp; Reverse</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL 5: Cancel / Stop Cheque -->
    <div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-ban mr-2"></i> Cancel / Stop Cheque</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="cancelForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="small">Cancelling Cheque <strong id="cncChqNo"></strong> will reverse the pending entry and reinstate the party / expense balance.</p>
                        <div class="form-group">
                            <label class="font-weight-bold small">Cancellation Reason</label>
                            <input type="text" name="cancel_reason" class="form-control" value="Cancelled / Stop Payment" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="font-weight-bold small">Cancellation Date</label>
                            <input type="date" name="cancel_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light border" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-dark font-weight-bold">Confirm Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    window.CHQ_VENDORS = @json($vendors->map(fn($v) => ['id' => 'vendor_' . $v->id, 'label' => $v->name]));
    window.CHQ_CUSTOMERS = @json($customers->map(fn($c) => ['id' => 'customer_' . $c->id, 'label' => $c->customer_name]));
    window.CHQ_EXPENSES = @json($expenseAccounts->map(fn($e) => ['id' => 'account_' . $e->id, 'label' => ($e->account_code ? $e->account_code . ' - ' : '') . $e->title]));

    let activeClearType = 'received';
    let activeClearAmount = 0;

    function openCreateChequeModal(direction) {
        $('#modalChequeType').val(direction);
        onModalChequeTypeChange(direction);
        $('#chequeEntryModal').modal('show');
    }

    function onModalChequeTypeChange(direction) {
        const isIssued = (direction === 'issued');
        $('#chequeModalHeader').css('background', isIssued ? '#7c3aed' : '#059669');
        $('#chequeModalTitle').html(isIssued
            ? '<i class="fas fa-arrow-up mr-2"></i> Issue Cheque (PDC / Bank Payment)'
            : '<i class="fas fa-arrow-down mr-2"></i> Receive Cheque (Bank Receipt)');
        $('#modalTargetLabel').html(isIssued ? 'Pay To (Vendor / Customer / Expense) <span class="text-danger">*</span>' : 'Received From (Customer / Vendor) <span class="text-danger">*</span>');
        $('#modalBankLabel').html(isIssued ? 'Drawn On Our Bank Account <span class="text-danger">*</span>' : 'Target Deposit Bank Account <span class="text-danger">*</span>');
        $('#modalBankNameLabel').text(isIssued ? 'Issuing Bank Name (Optional)' : 'Customer Drawing Bank (e.g. Meezan Bank)');

        const cat = $('#modalTargetCategory');
        if (isIssued) {
            cat.find('option[value="expense"]').show().prop('disabled', false);
            if (!cat.val() || cat.val() === 'customer') cat.val('vendor');
        } else {
            cat.find('option[value="expense"]').hide().prop('disabled', true);
            if (cat.val() === 'expense' || !cat.val()) cat.val('customer');
        }

        populateModalTargets(cat.val());
        updateModalBankNotice();
    }

    function populateModalTargets(category) {
        const select = $('#modalTargetSelect');
        if (select.hasClass('select2-hidden-accessible')) {
            select.select2('destroy');
        }
        select.empty().append('<option value="">-- Select --</option>');
        let list = [];
        if (category === 'vendor') list = window.CHQ_VENDORS || [];
        else if (category === 'customer') list = window.CHQ_CUSTOMERS || [];
        else if (category === 'expense') list = window.CHQ_EXPENSES || [];

        list.forEach(item => select.append(new Option(item.label, item.id)));

        if ($.fn.select2) {
            select.select2({
                dropdownParent: $('#chequeEntryModal'),
                width: '100%',
                placeholder: '-- Search & Select --'
            });
        }
    }

    function setPdcDays(days) {
        const base = $('#modalIssueDate').val() ? new Date($('#modalIssueDate').val()) : new Date();
        base.setDate(base.getDate() + days);
        const yyyy = base.getFullYear();
        const mm = String(base.getMonth() + 1).padStart(2, '0');
        const dd = String(base.getDate()).padStart(2, '0');
        $('#modalChequeDate').val(`${yyyy}-${mm}-${dd}`);
    }

    function updateModalBankNotice() {
        const dir = $('#modalChequeType').val();
        const opt = $('#modalBankSelect').find('option:selected');
        const bal = parseFloat(opt.attr('data-balance'));
        const amt = parseFloat($('#modalChequeAmount').val() || 0);
        const box = $('#modalPdcNotice');

        if (dir === 'issued' && opt.val()) {
            if (!isNaN(bal) && amt > bal) {
                box.html(`<i class="fas fa-info-circle mr-1"></i> <strong>PDC / Advance Cheque Allowed:</strong> Entered amount (Rs. ${amt.toLocaleString('en-US', {minimumFractionDigits:2})}) exceeds current bank balance (Rs. ${bal.toLocaleString('en-US', {minimumFractionDigits:2})}). Entry is fully permitted — bank balance will only be deducted when this cheque is cleared.`).show();
            } else {
                box.html(`<i class="fas fa-check-circle mr-1"></i> This cheque will be posted to <strong>Cheques Issued (PDC)</strong> and will deduct from your selected bank only when cleared.`).show();
            }
        } else if (dir === 'received' && opt.val()) {
            box.html(`<i class="fas fa-info-circle mr-1"></i> This cheque will be posted to <strong>Cheque In Hand</strong> and will credit your bank balance once cleared.`).show();
        } else {
            box.hide();
        }
    }

    function openDepositModal(id, chqNo, bankId) {
        $('#depositForm').attr('action', `{{ url('/cheques') }}/${id}/deposit`);
        $('#depChqNo').text('#' + chqNo);
        if (bankId) $('#depBankSelect').val(bankId);
        $('#depositModal').modal('show');
    }

    function openClearModal(id, chqNo, type, amount, bankId, partyName) {
        activeClearType = type;
        activeClearAmount = parseFloat(amount || 0);
        $('#clearForm').attr('action', `{{ url('/cheques') }}/${id}/clear`);
        $('#clrChqNo').text('#' + chqNo);
        $('#clrDirection').text(type === 'issued' ? 'Issued Payment' : 'Received Receipt');
        $('#clrParty').text(partyName || '-');
        $('#clrAmountText').text(activeClearAmount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        if (bankId) $('#clrBankSelect').val(bankId);
        checkClearBankBalance();
        $('#clearModal').modal('show');
    }

    function checkClearBankBalance() {
        const opt = $('#clrBankSelect').find('option:selected');
        const bal = parseFloat(opt.attr('data-balance'));
        const warn = $('#clrOverdraftWarning');
        if (activeClearType === 'issued' && !isNaN(bal) && activeClearAmount > bal) {
            warn.html(`<i class="fas fa-exclamation-triangle mr-1"></i> <strong>Note:</strong> Selected bank balance (Rs. ${bal.toLocaleString('en-US', {minimumFractionDigits:2})}) is less than cheque amount (Rs. ${activeClearAmount.toLocaleString('en-US', {minimumFractionDigits:2})}). Clearing will result in a negative / overdraft bank balance.`).show();
        } else {
            warn.hide();
        }
    }

    function openBounceModal(id, chqNo, type, amount, partyName) {
        $('#bounceForm').attr('action', `{{ url('/cheques') }}/${id}/bounce`);
        $('#bncChqNo').text('#' + chqNo);
        $('#bncParty').text(partyName || '-');
        $('#bncAmount').text(parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        $('#bounceModal').modal('show');
    }

    function openCancelModal(id, chqNo, type) {
        $('#cancelForm').attr('action', `{{ url('/cheques') }}/${id}/cancel`);
        $('#cncChqNo').text('#' + chqNo);
        $('#cancelModal').modal('show');
    }

    $(document).on('click', '.btn-chq-deposit', function() {
        const btn = $(this);
        openDepositModal(btn.data('id'), btn.data('no'), btn.data('bank-id'));
    });

    $(document).on('click', '.btn-chq-clear', function() {
        const btn = $(this);
        openClearModal(btn.data('id'), btn.data('no'), btn.data('type'), btn.data('amount'), btn.data('bank-id'), btn.data('party'));
    });

    $(document).on('click', '.btn-chq-bounce', function() {
        const btn = $(this);
        openBounceModal(btn.data('id'), btn.data('no'), btn.data('type'), btn.data('amount'), btn.data('party'));
    });

    $(document).on('click', '.btn-chq-cancel', function() {
        const btn = $(this);
        openCancelModal(btn.data('id'), btn.data('no'), btn.data('type'));
    });
</script>
@endsection
