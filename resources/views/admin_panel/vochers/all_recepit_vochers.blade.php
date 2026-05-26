@extends('admin_panel.layout.app')

@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .vch-page {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 28px 0 40px;
        }

        .vch-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .vch-title {
            font-size: 1.55rem;
            font-weight: 700;
            color: #1a2340;
            letter-spacing: -.3px;
        }

        .vch-badge {
            display: inline-block;
            background: #e8edff;
            color: #4f6ef7;
            font-size: .72rem;
            font-weight: 600;
            border-radius: 20px;
            padding: 2px 10px;
            margin-left: 8px;
            vertical-align: middle;
        }

        .btn-vch-new {
            background: linear-gradient(135deg, #4f6ef7, #7b5ef8);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 10px 22px;
            font-weight: 600;
            font-size: .92rem;
            box-shadow: 0 4px 14px rgba(79, 110, 247, .35);
            transition: all .22s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            text-decoration: none;
        }

        .btn-vch-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 110, 247, .45);
            color: #fff;
        }

        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px 24px;
            border: 1px solid #e8ecf4;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .05);
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }

        .stat-icon.blue {
            background: #e8edff;
            color: #4f6ef7;
        }

        .stat-icon.green {
            background: #e6f9f1;
            color: #18b870;
        }

        .stat-icon.orange {
            background: #fff4e6;
            color: #f59e0b;
        }

        .stat-val {
            font-size: 1.45rem;
            font-weight: 700;
            color: #1a2340;
            line-height: 1;
        }

        .stat-lbl {
            font-size: .78rem;
            color: #8897b0;
            font-weight: 500;
            margin-top: 4px;
        }

        .vch-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf4;
            box-shadow: 0 2px 18px rgba(0, 0, 0, .05);
            overflow: hidden;
            margin-top: 24px;
        }

        .vch-card-header {
            background: linear-gradient(135deg, #4f6ef7 0%, #7b5ef8 100%);
            padding: 18px 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .vch-card-header h5 {
            color: #fff;
            font-weight: 600;
            font-size: 1.05rem;
            margin: 0;
        }

        .vch-search {
            background: rgba(255, 255, 255, .18);
            border: 1px solid rgba(255, 255, 255, .35);
            border-radius: 8px;
            color: #fff;
            padding: 7px 14px;
            font-size: .88rem;
            width: 220px;
        }

        .vch-search::placeholder {
            color: rgba(255, 255, 255, .7);
        }

        .vch-search:focus {
            outline: none;
            background: rgba(255, 255, 255, .28);
        }

        .vch-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .vch-table thead th {
            background: #f7f9fc;
            color: #8897b0;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 12px 16px;
            border-bottom: 1px solid #e8ecf4;
            white-space: nowrap;
        }

        .vch-table tbody tr {
            transition: background .15s;
        }

        .vch-table tbody tr:hover {
            background: #f7f9ff;
        }

        .vch-table tbody td {
            padding: 13px 16px;
            font-size: .9rem;
            color: #3c4a6b;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }

        .vch-no {
            font-weight: 700;
            color: #4f6ef7;
            font-size: .92rem;
        }

        .party-name {
            font-weight: 600;
            color: #1a2340;
            font-size: .9rem;
        }

        .party-sub {
            font-size: .76rem;
            color: #8897b0;
            margin-top: 2px;
        }

        .amount-cell {
            font-weight: 700;
            color: #1a2340;
            font-size: .95rem;
            font-family: 'Consolas', monospace;
        }

        .badge-posted {
            background: #e6f9f1;
            color: #18b870;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .76rem;
            font-weight: 600;
        }

        .badge-draft {
            background: #f0f4f8;
            color: #8897b0;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .76rem;
            font-weight: 600;
        }

        .badge-other {
            background: #fff0f0;
            color: #ef4444;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: .76rem;
            font-weight: 600;
        }

        .badge-type {
            background: #e8edff;
            color: #4f6ef7;
            border-radius: 20px;
            padding: 3px 10px;
            font-size: .74rem;
            font-weight: 600;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            background: #eef2ff;
            border: 1.5px solid #c7d2fe;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #4f6ef7;
            font-size: 1rem;
            transition: all .2s;
            text-decoration: none;
        }

        .action-btn:hover {
            background: #4f6ef7;
            color: #fff;
            border-color: #4f6ef7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 110, 247, .3);
        }

        .row-num {
            color: #b0bac9;
            font-size: .82rem;
            font-weight: 600;
        }

        .empty-state {
            padding: 60px;
            text-align: center;
            color: #b0bac9;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 12px;
            display: block;
        }
    </style>

    <div class="vch-page">
        <div class="container-fluid px-4">
            <div class="vch-header">
                <div>
                    <div class="vch-title">Receipt Vouchers <span class="vch-badge">{{ count($receipts) }} Records</span>
                    </div>
                    <div style="color:#8897b0;font-size:.85rem;margin-top:4px;">Manage all incoming payment receipts</div>
                </div>
                @can('receipts.voucher.create')
                    <a class="btn-vch-new" href="{{ route('recepit_vochers') }}">
                        <i class="bi bi-plus-lg"></i> New Receipt Voucher
                    </a>
                @endcan
            </div>

            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="bi bi-receipt"></i></div>
                        <div>
                            <div class="stat-val">{{ count($receipts) }}</div>
                            <div class="stat-lbl">Total Vouchers</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
                        <div>
                            <div class="stat-val">{{ collect($receipts)->where('status', 'posted')->count() }}</div>
                            <div class="stat-lbl">Posted</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="bi bi-currency-dollar"></i></div>
                        <div>
                            <div class="stat-val">{{ number_format(collect($receipts)->sum('total_amount'), 0) }}</div>
                            <div class="stat-lbl">Total Amount</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="vch-card">
                <div class="vch-card-header">
                    <h5><i class="bi bi-table me-2"></i>Receipt Vouchers List</h5>
                    <input type="text" class="vch-search" id="vchSearch" placeholder="🔍 Search vouchers...">
                </div>
                <div style="overflow-x:auto;">
                    <table class="vch-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Voucher No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Party / Account</th>
                                <th>Remarks</th>
                                <th style="text-align:right;">Amount</th>
                                <th style="text-align:center;">Status</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="vchBody">
                            @forelse ($receipts as $item)
                                <tr>
                                    <td class="row-num">{{ $loop->iteration }}</td>
                                    <td><span class="vch-no">{{ $item->voucher_no }}</span></td>
                                    <td style="color:#6b7a99;font-size:.86rem;"><i
                                            class="bi bi-calendar3 me-1"></i>{{ $item->date ? $item->date->format('d M Y') : '-' }}
                                    </td>
                                    <td><span class="badge-type">{{ ucfirst($item->payment_from ?? 'Receipt') }}</span></td>
                                    <td>
                                        <div class="party-name">{{ $item->party_name }}</div>
                                        <div class="party-sub">{{ $item->type_label }}</div>
                                    </td>
                                    <td style="color:#6b7a99;font-size:.86rem;max-width:180px;">
                                        {{ Str::limit($item->remarks, 45) }}</td>
                                    <td style="text-align:right;"><span
                                            class="amount-cell">{{ number_format($item->total_amount, 2) }}</span></td>
                                    <td style="text-align:center;">
                                        @if ($item->status == 'posted')
                                            <span class="badge-posted"><i class="bi bi-check2 me-1"></i>Posted</span>
                                        @elseif($item->status == 'draft')
                                            <span class="badge-draft"><i class="bi bi-pencil me-1"></i>Draft</span>
                                        @else
                                            <span class="badge-other">{{ ucfirst($item->status) }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="{{ route('print', $item->id) }}" target="_blank" class="action-btn"
                                            title="Print Voucher">
                                            <i class="bi bi-printer-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state"><i class="bi bi-inbox"></i>No receipt vouchers found.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @section('js')
        <script>
            document.getElementById('vchSearch').addEventListener('input', function() {
                let q = this.value.toLowerCase();
                document.querySelectorAll('#vchBody tr').forEach(function(row) {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        </script>
    @endsection
@endsection
