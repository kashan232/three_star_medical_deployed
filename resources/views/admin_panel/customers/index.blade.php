@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --info: #06b6d4;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-glass: rgba(255, 255, 255, 0.9);
            --radius-lg: 12px;
            --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        .customer-listing-area {
            padding: 20px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .listing-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: #ffffff;
            padding: 20px 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-subtle);
            border: 1px solid #e2e8f0;
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-title p {
            font-size: 0.85rem;
            color: var(--secondary);
            margin: 5px 0 0 0;
        }

        /* Premium Table */
        .table-container {
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid #e2e8f0;
            box-shadow: var(--shadow-subtle);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-premium {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-premium th {
            background: #f1f5f9;
            padding: 15px 20px;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .table-premium td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .table-premium tr:hover td {
            background: #f8fafc;
        }

        /* Info Groups */
        .identity-group .name {
            font-weight: 800;
            color: #0f172a;
            font-size: 0.95rem;
            display: block;
        }

        .identity-group .id-tag {
            font-size: 0.75rem;
            color: var(--primary);
            font-weight: 700;
            background: #e0e7ff;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 4px;
            display: inline-block;
        }

        .contact-group .item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            margin-bottom: 4px;
        }

        .contact-group i {
            width: 16px;
            color: var(--secondary);
        }

        .legal-group {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            max-width: 250px;
        }

        .legal-badge {
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }

        .legal-badge.active {
            background: #ecfdf5;
            color: #059669;
            border-color: #6ee7b7;
        }

        .financial-group .balance {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .balance-debit { color: var(--danger); }
        .balance-credit { color: var(--success); }

        .limit-info {
            font-size: 0.7rem;
            color: var(--secondary);
            margin-top: 4px;
        }

        /* Badges */
        .badge-premium {
            padding: 5px 12px;
            border-radius: 6px;
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
        }

        .status-active { background: #dcfce7; color: #166534; }
        .status-inactive { background: #fee2e2; color: #991b1b; }

        .action-flex {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        /* Toggle Button Custom Styling */
        .btn-status-toggle {
            padding: 6px;
            border-radius: 8px;
            transition: 0.2s;
        }
        .btn-status-toggle.active { color: var(--success); background: #f0fdf4; }
        .btn-status-toggle.inactive { color: var(--secondary); background: #f8fafc; }

        .btn-status-toggle i { font-size: 1.25rem; }

        /* Urdu Text Support */
        .text-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 0.8rem;
            color: var(--secondary);
        }
    </style>

    <div class="customer-listing-area">
        <div class="container-fluid p-0">
            <!-- Dynamic Header -->
            <div class="listing-header">
                <div class="header-title">
                    <h1><i class="fa fa-users"></i> Customer Directory</h1>
                    <p>Financial oversight and identification records for all registered customers</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="{{ route('customer.payments') }}" class="btn btn-outline-info fw-bold" style="border-radius:10px;">
                        <i class="fa fa-money-bill-wave me-2"></i> Payments
                    </a>
                    <a href="{{ route('customers.ledger') }}" class="btn btn-outline-primary fw-bold" style="border-radius:10px;">
                        <i class="fa fa-book-open me-2"></i> General Ledger
                    </a>
                    <a href="{{ route('parties.create', ['type' => 'Customer']) }}" class="btn btn-primary fw-bold" style="border-radius:10px; padding: 10px 20px;">
                        <i class="fa fa-plus-circle me-2"></i> Register Customer
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:12px; border-left: 5px solid var(--success) !important;">
                    <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Main Table Wrapper -->
            <div class="table-container">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Identity & Type</th>
                            <th>Contact Node</th>
                            <th>Location & Zone</th>
                            <th>Legal / Tax / Licenses</th>
                            <th>Financial Summary</th>
                            <th class="text-center">Account</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($customers as $customer)
                            <tr>
                                <!-- Identity -->
                                <td>
                                    <div class="identity-group">
                                        <span class="name">{{ $customer->customer_name }}</span>
                                        @if($customer->customer_name_ur)
                                            <span class="text-urdu">{{ $customer->customer_name_ur }}</span>
                                        @endif
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="id-tag">ID: {{ $customer->customer_id }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem;">{{ $customer->category ?? 'General' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div class="contact-group">
                                        <div class="item"><i class="fa fa-phone-alt"></i> <b>{{ $customer->mobile }}</b></div>
                                        @if($customer->contact_person)
                                            <div class="item"><i class="fa fa-user"></i> {{ $customer->contact_person }}</div>
                                        @endif
                                        @if($customer->email_address)
                                            <div class="item"><i class="fa fa-envelope"></i> <small>{{ $customer->email_address }}</small></div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Location -->
                                <td>
                                    <div style="font-size:0.85rem;">
                                        <div class="mb-1 fw-bold text-dark">{{ $customer->zone ?? '(No Zone)' }}</div>
                                        <div class="text-muted" style="line-height:1.4; max-width: 200px;">
                                            {{ Str::limit($customer->address, 60) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Legal / Tax -->
                                <td>
                                    <div class="legal-group">
                                        @if($customer->cnic)
                                            <span class="legal-badge active" title="CNIC Number">ID: {{ $customer->cnic }}</span>
                                        @endif
                                        @if($customer->ntn_no)
                                            <span class="legal-badge active" title="NTN Number">NTN: {{ $customer->ntn_no }}</span>
                                        @endif
                                        @if($customer->gst_no)
                                            <span class="legal-badge" title="GST Number">GST: {{ $customer->gst_no }}</span>
                                        @endif
                                        @if($customer->dsl_no)
                                            <span class="legal-badge" title="Drug Sale License">DSL: {{ $customer->dsl_no }}</span>
                                        @endif
                                        @if($customer->drap_no)
                                            <span class="legal-badge" title="Medical Registration">DRAP: {{ $customer->drap_no }}</span>
                                        @endif
                                        <span class="legal-badge text-uppercase">{{ $customer->filer_type ?? 'Non-Filer' }}</span>
                                    </div>
                                </td>

                                <!-- Financial -->
                                <td>
                                    <div class="financial-group">
                                        @php
                                            $ob = floatval($customer->opening_balance ?? 0);
                                            $dr = floatval($customer->debit ?? 0);
                                            $cr = floatval($customer->credit ?? 0);
                                            $bal = $ob + $dr - $cr;
                                        @endphp
                                        @if($bal > 0)
                                            <div class="balance balance-debit">RS. {{ number_format($bal, 2) }} <small>(Dr)</small></div>
                                        @elseif($bal < 0)
                                            <div class="balance balance-credit">RS. {{ number_format(abs($bal), 2) }} <small>(Cr)</small></div>
                                        @else
                                            <div class="balance text-muted">RS. 0.00</div>
                                        @endif

                                        <div class="limit-info">
                                            Limit: RS. {{ number_format($customer->balance_range ?? 0, 0) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status & Branch -->
                                <td class="text-center">
                                    <div class="mb-2">
                                        <span class="badge-premium status-{{ strtolower($customer->status) }}">
                                            {{ $customer->status }}
                                        </span>
                                    </div>
                                    <span class="text-muted" style="font-size:0.65rem; font-weight:700;">
                                        {{ $customer->branch?->abr ?? 'HO' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-flex">
                                        @can('customers.edit')
                                            <a href="{{ route('customers.toggleStatus', $customer->id) }}"
                                                class="btn-status-toggle {{ $customer->status === 'active' ? 'active' : 'inactive' }}"
                                                title="Toggle Active Status">
                                                <i class="fa-solid {{ $customer->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </a>
                                        @endcan

                                        @include('admin_panel.partials.action_buttons', [
                                            'editRoute' => route('parties.edit', [$customer->id, 'type' => 'Customer']),
                                            'deleteRoute' => route('customers.destroy', $customer->id),
                                            'editIsLink' => true,
                                            'permissions' => ['edit' => 'customers.edit', 'delete' => 'customers.delete'],
                                            'dataId' => $customer->id,
                                        ])
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Meta -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small fw-bold">
                    Showing {{ $customers->count() }} registered customers
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('customers.inactive') }}" class="btn btn-sm btn-light border fw-bold text-secondary" style="border-radius:8px;">
                        <i class="fa fa-eye-slash me-1"></i> View Inactive Archive
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
