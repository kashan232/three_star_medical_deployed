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

        .vendor-listing-area {
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
            color: #0d9488;
            font-weight: 700;
            background: #ccfbf1;
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
            background: #fff7ed;
            color: #9a3412;
            border-color: #fed7aa;
        }

        .financial-group .balance {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .balance-debit { color: var(--danger); } /* Rare for vendor */
        .balance-credit { color: var(--success); } /* Normal: we owe vendor */

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
    </style>

    <div class="vendor-listing-area">
        <div class="container-fluid p-0">
            <!-- Dynamic Header -->
            <div class="listing-header">
                <div class="header-title">
                    <h1><i class="fa fa-truck-loading"></i> Vendor Management</h1>
                    <p>Supply chain records and payable balances for procurement partners</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="{{ route('vendor.payments') }}" class="btn btn-outline-info fw-bold" style="border-radius:10px;">
                        <i class="fa fa-cash-register me-2"></i> Payments
                    </a>
                    <a href="{{ url('vendors-ledger') }}" class="btn btn-outline-secondary fw-bold" style="border-radius:10px;">
                        <i class="fa fa-list-alt me-2"></i> Ledger
                    </a>
                    <a href="{{ route('parties.create', ['type' => 'Vendor']) }}" class="btn btn-primary fw-bold" style="border-radius:10px; padding: 10px 20px;">
                        <i class="fa fa-plus-circle me-2"></i> Add Vendor
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
                            <th>Supplier Identity</th>
                            <th>Communication Node</th>
                            <th>Mailing Point</th>
                            <th>Tax & Legal Identification</th>
                            <th>Payable Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($vendors as $v)
                            <tr>
                                <!-- Identity -->
                                <td>
                                    <div class="identity-group">
                                        <span class="name">{{ $v->name }}</span>
                                        @if($v->business_name)
                                            <small class="text-muted d-block" style="font-size:0.75rem;">{{ $v->business_name }}</small>
                                        @endif
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="id-tag">CODE: {{ $v->vendor_code ?? 'N/A' }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size:0.65rem;">{{ $v->city ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact -->
                                <td>
                                    <div class="contact-group">
                                        <div class="item"><i class="fa fa-phone-alt"></i> <b>{{ $v->phone }}</b></div>
                                        @if($v->contact_person)
                                            <div class="item"><i class="fa fa-user-tie"></i> {{ $v->contact_person }}</div>
                                        @endif
                                        @if($v->email)
                                            <div class="item"><i class="fa fa-envelope"></i> <small>{{ $v->email }}</small></div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Location -->
                                <td>
                                    <div style="font-size:0.85rem;">
                                        <div class="mb-1 fw-bold text-dark">{{ $v->country ?? 'Pakistan' }}</div>
                                        <div class="text-muted" style="line-height:1.4; max-width: 200px;">
                                            {{ Str::limit($v->address, 60) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Legal / Tax -->
                                <td>
                                    <div class="legal-group">
                                        @if($v->cnic)
                                            <span class="legal-badge active" title="CNIC Number">ID: {{ $v->cnic }}</span>
                                        @endif
                                        @if($v->ntn_no)
                                            <span class="legal-badge active" title="NTN Number">NTN: {{ $v->ntn_no }}</span>
                                        @endif
                                        @if($v->gst_no)
                                            <span class="legal-badge" title="GST Number">GST: {{ $v->gst_no }}</span>
                                        @endif
                                        @if($v->dsl_no)
                                            <span class="legal-badge" title="Drug Sale License">DSL: {{ $v->dsl_no }}</span>
                                        @endif
                                        @if($v->drap_no)
                                            <span class="legal-badge" title="Medical Registration">DRAP: {{ $v->drap_no }}</span>
                                        @endif
                                        @if($v->ftn_no)
                                            <span class="legal-badge" title="FTN Number">FTN: {{ $v->ftn_no }}</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Financial -->
                                <td>
                                    <div class="financial-group">
                                        @php
                                            $ob = floatval($v->opening_balance ?? 0);
                                            $dr = floatval($v->debit ?? 0);
                                            $cr = floatval($v->credit ?? 0);
                                            // For vendors: Credit increases what we owe
                                            $bal = $ob + $cr - $dr;
                                        @endphp
                                        @if($bal > 0)
                                            <div class="balance balance-credit">RS. {{ number_format($bal, 2) }} <small>(To Pay)</small></div>
                                        @elseif($bal < 0)
                                            <div class="balance balance-debit">RS. {{ number_format(abs($bal), 2) }} <small>(Adv)</small></div>
                                        @else
                                            <div class="balance text-muted">RS. 0.00</div>
                                        @endif

                                        <div class="limit-info">
                                            Credit Limit: RS. {{ number_format($v->credit_limit ?? 0, 0) }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="text-center">
                                    <div class="mb-2">
                                        <span class="badge-premium {{ $v->is_active ? 'status-active' : 'status-inactive' }}">
                                            {{ $v->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <span class="text-muted" style="font-size:0.65rem; font-weight:700;">
                                        {{ $v->branch?->abr ?? 'HO' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-flex">
                                        @include('admin_panel.partials.action_buttons', [
                                            'editRoute' => route('parties.edit', [$v->id, 'type' => 'Vendor']),
                                            'deleteRoute' => route('vendors.delete', $v->id),
                                            'editIsLink' => true,
                                            'permissions' => ['edit' => 'vendors.edit', 'delete' => 'vendors.delete'],
                                            'dataId' => $v->id,
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
                    System synchronizing {{ $vendors->count() }} active procurement accounts
                </div>
            </div>
        </div>
    </div>
@endsection
