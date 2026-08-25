@extends('admin_panel.layout.app')

@section('content')
<!-- Select2 CSS & JS loaded directly to guarantee instant availability -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    :root {
        --primary: #4f46e5;
        --primary-light: #e0e7ff;
        --secondary: #64748b;
        --success: #10b981;
        --success-light: #d1fae5;
        --danger: #ef4444;
        --danger-light: #fee2e2;
        --warning: #f59e0b;
        --warning-light: #fef3c7;
        --purple: #8b5cf6;
        --purple-light: #ede9fe;
        --radius-xl: 16px;
        --radius-lg: 12px;
        --shadow-subtle: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        --shadow-bold: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.06);
    }

    /* Balance Preview Badge */
    .balance-preview-wrap {
        margin-top: 6px;
        display: block;
        min-height: 24px;
    }

    .balance-preview-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        animation: fadeIn 0.2s ease-out;
    }

    .balance-preview-badge.badge-green {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .balance-preview-badge.badge-red {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .balance-preview-badge.badge-blue {
        background: #e0e7ff;
        color: #3730a3;
        border: 1px solid #c7d2fe;
    }

    .balance-preview-badge.badge-amber {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }

    .balance-preview-badge.badge-purple {
        background: #ede9fe;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }

    body {
        background-color: #f8fafc;
        color: #1e293b;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .voucher-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 16px 20px 100px 20px;
        animation: fadeIn 0.35s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .voucher-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 16px 24px;
        background: #ffffff;
        border-radius: var(--radius-xl);
        border: 1px solid #e2e8f0;
        box-shadow: var(--shadow-subtle);
    }

    .title-group {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .title-group .header-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    .title-group h1 {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .title-group p {
        font-size: 0.82rem;
        color: var(--secondary);
        margin: 0;
        margin-top: 2px;
        font-weight: 500;
    }

    .premium-card {
        background: #ffffff;
        border-radius: var(--radius-xl);
        border: 1px solid #e2e8f0;
        padding: 22px;
        height: 100%;
        box-shadow: var(--shadow-subtle);
    }

    .card-header-modern {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .icon-box {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .card-header-modern h3 {
        font-size: 0.92rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .form-group-modern {
        margin-bottom: 14px;
    }

    .label-modern {
        display: block;
        font-size: 0.73rem;
        font-weight: 700;
        color: #475569;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .input-modern {
        width: 100%;
        height: 44px;
        padding: 8px 14px;
        background: #f8fafc;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 600;
        color: #0f172a;
        transition: all 0.2s;
    }

    .input-modern:focus {
        outline: none;
        background: #ffffff;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single {
        height: 44px !important;
        background: #f8fafc !important;
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 10px !important;
        padding: 8px 14px !important;
        display: flex !important;
        align-items: center !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #0f172a !important;
        font-weight: 600 !important;
        font-size: 0.88rem !important;
        padding-left: 0 !important;
        line-height: normal !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        right: 12px !important;
    }

    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--single {
        background: #ffffff !important;
        border-color: var(--primary) !important;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12) !important;
        outline: none !important;
    }

    .select2-dropdown {
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 10px !important;
        box-shadow: var(--shadow-bold) !important;
        z-index: 99999 !important;
        overflow: hidden;
    }

    .select2-search--dropdown {
        padding: 8px !important;
    }

    .select2-search--dropdown .select2-search__field {
        border: 1.5px solid #e2e8f0 !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
    }

    .select2-results__option {
        padding: 9px 14px !important;
        font-size: 0.88rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary) !important;
        color: white !important;
    }

    .floating-action-pill {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(15, 23, 42, 0.94);
        backdrop-filter: blur(16px);
        padding: 8px 16px;
        border-radius: 100px;
        box-shadow: var(--shadow-bold);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1000;
        border: 1px solid rgba(255, 255, 255, 0.15);
        animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideUp {
        from { transform: translate(-50%, 60px); opacity: 0; }
        to { transform: translate(-50%, 0); opacity: 1; }
    }

    .pill-btn {
        padding: 9px 24px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .pill-btn-save {
        background: var(--primary);
        color: white;
    }

    .pill-btn-save:hover {
        background: #4338ca;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
    }

    .pill-btn-cancel {
        background: rgba(255, 255, 255, 0.12);
        color: #f1f5f9;
    }

    /* Ultra-Compact Table Select2 & Controls */
    #jvTable .select2-container--default .select2-selection--single,
    #editVoucherTable .select2-container--default .select2-selection--single {
        height: 32px !important;
        min-height: 32px !important;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 5px !important;
        padding: 2px 8px !important;
        font-size: 0.80rem !important;
    }

    #jvTable .select2-container--default .select2-selection--single .select2-selection__rendered,
    #editVoucherTable .select2-container--default .select2-selection--single .select2-selection__rendered {
        font-size: 0.80rem !important;
        line-height: 26px !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        max-width: calc(100% - 20px) !important;
        display: block !important;
    }

    #jvTable .select2-container--default .select2-selection--single .select2-selection__arrow,
    #editVoucherTable .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 30px !important;
        right: 6px !important;
    }

    #jvTable .select2-container--default .select2-selection--single .select2-selection__clear,
    #editVoucherTable .select2-container--default .select2-selection--single .select2-selection__clear {
        margin-right: 18px !important;
        font-size: 0.9rem !important;
        line-height: 26px !important;
    }

    #jvTable .form-control, #jvTable .form-select,
    #editVoucherTable .form-control, #editVoucherTable .form-select {
        height: 32px !important;
        min-height: 32px !important;
        padding: 2px 6px !important;
        font-size: 0.82rem !important;
        border-radius: 5px !important;
    }

    #jvTable td, #editVoucherTable td {
        padding: 3px 5px !important;
        vertical-align: middle !important;
    }
    
    #jvTable th, #editVoucherTable th {
        padding: 5px 6px !important;
        font-size: 0.76rem !important;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .jv-balance-preview {
        margin-top: 1px !important;
        line-height: 1 !important;
    }

    .jv-balance-preview span {
        font-size: 0.68rem !important;
        padding: 0px 5px !important;
        border-radius: 3px !important;
        font-weight: 700 !important;
    }
</style>

<div class="main-content">
    <div class="main-content-inner">
        <div class="voucher-container">
            
            @php
                $type = strtolower($voucher->voucher_type);
                $typeConfigs = [
                    'crv' => ['title' => 'Edit Cash Receiving Voucher (CRV)', 'theme' => '#10b981', 'light' => '#d1fae5', 'icon' => 'fa-hand-holding-usd'],
                    'brv' => ['title' => 'Edit Bank Receiving Voucher (BRV)', 'theme' => '#4f46e5', 'light' => '#e0e7ff', 'icon' => 'fa-university'],
                    'cpv' => ['title' => 'Edit Cash Payment Voucher (CPV)', 'theme' => '#f59e0b', 'light' => '#fef3c7', 'icon' => 'fa-money-bill-wave'],
                    'bpv' => ['title' => 'Edit Bank Payment Voucher (BPV)', 'theme' => '#8b5cf6', 'light' => '#ede9fe', 'icon' => 'fa-file-invoice-dollar'],
                    'jv'  => ['title' => 'Edit Journal Voucher (JV)', 'theme' => '#334155', 'light' => '#f1f5f9', 'icon' => 'fa-book'],
                ];
                $v = $typeConfigs[$type] ?? ['title' => 'Edit Voucher', 'theme' => '#4f46e5', 'light' => '#e0e7ff', 'icon' => 'fa-file-invoice'];
            @endphp

            <!-- Ultra-Modern Header -->
            <div class="voucher-header">
                <div class="title-group">
                    <div class="header-icon" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                        <i class="fas {{ $v['icon'] }}"></i>
                    </div>
                    <div>
                        <h1>{{ $v['title'] }}</h1>
                        <p>Voucher #: <span class="font-monospace fw-bold" style="color: {{ $v['theme'] }};">{{ $voucher->voucher_no }}</span> | Auto Ledger Recalculation on Update</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('vouchers.list', ['type' => $type]) }}" class="btn btn-sm btn-light border px-3 py-2 fw-bold text-secondary d-flex align-items-center gap-2" style="border-radius: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            @if(isset($errors) && $errors->any())
                <div class="alert alert-danger alert-dismissible fade show py-2 px-3 rounded-3 shadow-sm mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    <strong>Please check errors:</strong>
                    <ul class="mb-0 mt-1 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close btn-close" data-dismiss="alert" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('vouchers.update_action', ['id' => $voucher->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="voucher_type" value="{{ $type }}">

                <!-- 2-CARD SIDE-BY-SIDE LAYOUT -->
                <div class="row g-4 mb-5">
                    
                    <!-- LEFT CARD: Header Info -->
                    <div class="col-xl-3 col-lg-4 col-md-12">
                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3>VOUCHER HEADER INFO</h3>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Voucher Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" class="input-modern" value="{{ $voucher->date ? \Carbon\Carbon::parse($voucher->date)->format('Y-m-d') : date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Location / Branch <span class="text-danger">*</span></label>
                                        @if(isset($isSuperAdmin) && $isSuperAdmin && count($branches) > 0)
                                            <select name="branch_id" class="select2-search form-control" style="width:100%" required>
                                                @foreach($branches as $b)
                                                    <option value="{{ $b->id }}" {{ ($voucher->branch_id == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="input-modern" value="{{ $userBranch->name ?? 'HEAD OFFICE' }}" style="background: #f1f5f9; font-weight: 700; color: #4338ca;" readonly>
                                            <input type="hidden" name="branch_id" value="{{ $userBranch->id ?? 1 }}">
                                        @endif
                                    </div>
                                </div>

                                @if(in_array($type, ['brv', 'bpv', 'payment', 'receipt']))
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Cheque No</label>
                                        <input type="text" name="cheque_no" class="input-modern" value="{{ $voucher->cheque_no }}" placeholder="e.g. CHQ 1145628">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Cheque Date</label>
                                        <input type="date" name="cheque_date" class="input-modern" value="{{ $voucher->cheque_date ? \Carbon\Carbon::parse($voucher->cheque_date)->format('Y-m-d') : date('Y-m-d') }}">
                                    </div>
                                </div>
                                @endif

                                <div class="col-12">
                                    <div class="form-group-modern mb-0">
                                        <label class="label-modern">Remarks / Narration <span class="text-danger">*</span></label>
                                        <textarea name="remarks" rows="3" class="input-modern" style="height:auto;" required>{{ $voucher->remarks }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT CARD: Line Items Table -->
                    <div class="col-xl-9 col-lg-8 col-md-12">
                        <div class="premium-card">
                            <div class="card-header-modern justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-box" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                                        <i class="fas fa-balance-scale"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">LINE ITEMS (DR = CR)</h3>
                                        <div class="d-flex align-items-center gap-2 mt-1">
                                            <span class="badge bg-success" style="font-size: 0.70rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; box-shadow: 0 1px 2px rgba(16,185,129,0.2);">
                                                <i class="fas fa-arrow-circle-down me-1"></i> DEBIT = CASH IN (+)
                                            </span>
                                            <span class="badge bg-danger" style="font-size: 0.70rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; box-shadow: 0 1px 2px rgba(239,68,68,0.2);">
                                                <i class="fas fa-arrow-circle-up me-1"></i> CREDIT = CASH OUT (-)
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 d-flex align-items-center gap-1 shadow-sm" style="border-radius:8px;" onclick="addEditRow()">
                                    <i class="fas fa-plus"></i> Add Line
                                </button>
                            </div>

                            <div class="table-responsive mt-2">
                                <table class="table table-bordered table-sm align-middle mb-0" id="editVoucherTable" style="font-size:0.84rem;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="width: 11%">Type / Party <span class="text-danger">*</span></th>
                                            <th style="width: 20%">Account / Party Title <span class="text-danger">*</span></th>
                                            <th style="width: 31%">Line Narration</th>
                                            <th style="width: 17%" class="text-end">
                                                <div class="d-flex flex-column align-items-end">
                                                    <span>Debit (Rs.) <span class="text-danger">*</span></span>
                                                    <span class="badge bg-success mt-1" style="font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 3px; letter-spacing: 0.3px;">
                                                        📥 CASH IN (+)
                                                    </span>
                                                </div>
                                            </th>
                                            <th style="width: 17%" class="text-end">
                                                <div class="d-flex flex-column align-items-end">
                                                    <span>Credit (Rs.) <span class="text-danger">*</span></span>
                                                    <span class="badge bg-danger mt-1" style="font-size: 0.65rem; font-weight: 700; padding: 1px 6px; border-radius: 3px; letter-spacing: 0.3px;">
                                                        📤 CASH OUT (-)
                                                    </span>
                                                </div>
                                            </th>
                                            <th style="width: 4%" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($voucher->details as $idx => $detail)
                                        @php
                                            $itemType = 'account';
                                            $itemId = 'account_' . $detail->account_id;
                                            if ($detail->party_type === \App\Models\Customer::class || ($voucher->party instanceof \App\Models\Customer && $detail->party_id)) {
                                                $itemType = 'customer';
                                                $itemId = 'customer_' . ($detail->party_id ?: $voucher->party_id);
                                            } elseif ($detail->party_type === \App\Models\Vendor::class || ($voucher->party instanceof \App\Models\Vendor && $detail->party_id)) {
                                                $itemType = 'vendor';
                                                $itemId = 'vendor_' . ($detail->party_id ?: $voucher->party_id);
                                            } else {
                                                $acc = $detail->account;
                                                if ($acc) {
                                                    $headName = strtolower($acc->head?->name ?? '');
                                                    $code = $acc->account_code ?? '';
                                                    if (str_starts_with($code, '1-02-040') || str_contains($headName, 'cash')) {
                                                        $itemType = 'cash';
                                                    } elseif (str_starts_with($code, '1-02-052') || str_contains($headName, 'bank')) {
                                                        $itemType = 'bank';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <tr data-row-index="{{ $idx }}">
                                            <td>
                                                <select name="rows[{{ $idx }}][item_type]" class="form-select form-select-sm jv-type-select fw-semibold" onchange="onEditJvTypeChange(this)" style="height: 34px; font-size: 0.82rem; padding: 2px 4px; border-radius: 4px;">
                                                    <option value="customer" {{ $itemType === 'customer' ? 'selected' : '' }}>Customer</option>
                                                    <option value="vendor" {{ $itemType === 'vendor' ? 'selected' : '' }}>Vendor</option>
                                                    <option value="cash" {{ $itemType === 'cash' ? 'selected' : '' }}>Cash</option>
                                                    <option value="bank" {{ $itemType === 'bank' ? 'selected' : '' }}>Bank</option>
                                                    <option value="account" {{ $itemType === 'account' ? 'selected' : '' }}>Account</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="jv-target-wrap">
                                                    <select name="rows[{{ $idx }}][account_id]" class="form-control jv-target-select select2-search" data-initial-val="{{ $itemId }}" style="width:100%" required>
                                                        <option value="">-- Search & Select --</option>
                                                    </select>
                                                    <div class="jv-balance-preview"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" name="rows[{{ $idx }}][narration]" class="form-control form-control-sm" value="{{ $detail->narration }}" placeholder="Line Narration" style="height: 34px; font-size: 0.83rem;">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="rows[{{ $idx }}][debit]" class="form-control form-control-sm text-end edit-debit fw-bold" value="{{ (float)$detail->debit }}" oninput="calculateEditTotals()" style="height: 34px; font-size: 0.84rem;">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="rows[{{ $idx }}][credit]" class="form-control form-control-sm text-end edit-credit fw-bold" value="{{ (float)$detail->credit }}" oninput="calculateEditTotals()" style="height: 34px; font-size: 0.84rem;">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-xs text-danger p-0" onclick="removeEditRow(this)"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="fw-bold bg-light">
                                            <td colspan="3" class="text-end small">Total:</td>
                                            <td class="text-end" id="totalDebitCell">0.00</td>
                                            <td class="text-end" id="totalCreditCell">0.00</td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-center py-2">
                                                <span class="badge bg-success px-3 py-1" id="editBalanceBadge" style="font-size:0.82rem; border-radius: 20px;">Balanced: DR = CR</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- FLOATING CAPSULE ACTION BAR -->
                <div class="floating-action-pill">
                    <a href="{{ route('vouchers.list', ['type' => $type]) }}" class="pill-btn pill-btn-cancel">
                        <i class="fas fa-times"></i> Discard Changes
                    </a>
                    <button type="submit" class="pill-btn pill-btn-save" id="submitBtn" style="background: {{ $v['theme'] }};">
                        <i class="fas fa-check-circle"></i> Save & Re-Post Voucher
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection

@section('js')
<script>
let editRowIndex = {{ count($voucher->details) }};

@php
    $cashList = $allAccounts->filter(fn($a) => str_starts_with($a->account_code ?? '', '1-02-040') || str_contains(strtolower($a->title ?? ''), 'cash') || str_contains(strtolower($a->head?->name ?? ''), 'cash'));
    $bankList = $allAccounts->filter(fn($a) => str_starts_with($a->account_code ?? '', '1-02-052') || str_contains(strtolower($a->title ?? ''), 'bank') || str_contains(strtolower($a->head?->name ?? ''), 'bank'));
    $otherList = $allAccounts->reject(fn($a) => $cashList->contains('id', $a->id) || $bankList->contains('id', $a->id));
@endphp

window.JV_CUSTOMERS = {!! json_encode($customers->map(function($c) {
    return [
        'id' => 'customer_' . $c->id,
        'raw_id' => $c->id,
        'name' => $c->customer_name,
        'balance' => (float)$c->closing_balance,
        'label' => '[CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT) . '] ' . $c->customer_name,
        'type' => 'customer'
    ];
})->values()) !!};

window.JV_VENDORS = {!! json_encode($vendors->map(function($v) {
    return [
        'id' => 'vendor_' . $v->id,
        'raw_id' => $v->id,
        'name' => $v->name,
        'balance' => (float)$v->closing_balance,
        'label' => '[VEND-' . str_pad($v->id, 4, '0', STR_PAD_LEFT) . '] ' . $v->name,
        'type' => 'vendor'
    ];
})->values()) !!};

window.JV_CASH = {!! json_encode($cashList->map(function($a) {
    return [
        'id' => 'account_' . $a->id,
        'raw_id' => $a->id,
        'name' => $a->title,
        'balance' => (float)$a->calculated_balance,
        'label' => ($a->account_code ? '[' . $a->account_code . '] ' : '') . $a->title,
        'type' => 'cash'
    ];
})->values()) !!};

window.JV_BANK = {!! json_encode($bankList->map(function($a) {
    return [
        'id' => 'account_' . $a->id,
        'raw_id' => $a->id,
        'name' => $a->title,
        'balance' => (float)$a->calculated_balance,
        'label' => ($a->account_code ? '[' . $a->account_code . '] ' : '') . $a->title,
        'type' => 'bank'
    ];
})->values()) !!};

window.JV_OTHER = {!! json_encode($otherList->map(function($a) {
    return [
        'id' => 'account_' . $a->id,
        'raw_id' => $a->id,
        'name' => $a->title,
        'balance' => (float)$a->calculated_balance,
        'label' => ($a->account_code ? '[' . $a->account_code . '] ' : '') . $a->title,
        'type' => 'account'
    ];
})->values()) !!};

$(document).ready(function() {
    initEditJvRows();
});

function initEditJvRows() {
    $('#editVoucherTable tbody tr').each(function() {
        const typeSelect = $(this).find('.jv-type-select');
        const targetSelect = $(this).find('.jv-target-select');
        const initialVal = targetSelect.data('initial-val');
        if (typeSelect.length) {
            populateEditJvTarget(typeSelect[0], initialVal);
        }
    });
}

function onEditJvTypeChange(elem) {
    populateEditJvTarget(elem);
}

function populateEditJvTarget(typeSelectElem, selectedVal = null) {
    const type = $(typeSelectElem).val();
    const row = $(typeSelectElem).closest('tr');
    const targetSelect = row.find('.jv-target-select');
    const previewContainer = row.find('.jv-balance-preview');

    let dataList = [];
    let placeholder = '-- Search & Select --';

    if (type === 'customer') {
        dataList = window.JV_CUSTOMERS || [];
        placeholder = '-- Search & Select Customer --';
    } else if (type === 'vendor') {
        dataList = window.JV_VENDORS || [];
        placeholder = '-- Search & Select Vendor --';
    } else if (type === 'cash') {
        dataList = window.JV_CASH || [];
        placeholder = '-- Search & Select Cash Account --';
    } else if (type === 'bank') {
        dataList = window.JV_BANK || [];
        placeholder = '-- Search & Select Bank Account --';
    } else {
        dataList = window.JV_OTHER || [];
        placeholder = '-- Search & Select Other Account --';
    }

    if (targetSelect.hasClass("select2-hidden-accessible")) {
        targetSelect.select2('destroy');
    }

    targetSelect.empty();
    targetSelect.append(new Option(placeholder, ''));

    dataList.forEach(function(item) {
        const isSelected = selectedVal && (selectedVal == item.id || selectedVal == item.raw_id);
        const opt = new Option(item.label, item.id, false, isSelected);
        $(opt).attr('data-balance', item.balance);
        $(opt).attr('data-type', item.type);
        targetSelect.append(opt);
    });

    targetSelect.select2({
        placeholder: placeholder,
        allowClear: true,
        width: '100%'
    });

    previewContainer.empty();

    targetSelect.off('change.jvBadge').on('change.jvBadge', function() {
        const selected = $(this).find('option:selected');
        const bal = selected.attr('data-balance');
        const itemType = selected.attr('data-type') || type;

        if (bal !== undefined && bal !== '' && $(this).val()) {
            const numBal = parseFloat(bal);
            const isNegative = numBal < 0;
            const absBal = Math.abs(numBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            let suffix = '';
            let isGreen = true;

            if (itemType === 'customer') {
                suffix = numBal >= 0 ? 'Dr (Receivable)' : 'Cr (Advance)';
                isGreen = numBal >= 0;
            } else if (itemType === 'vendor') {
                suffix = numBal >= 0 ? 'Cr (Payable)' : 'Dr (Advance)';
                isGreen = numBal <= 0;
            } else {
                suffix = isNegative ? 'Cr' : 'Dr';
                isGreen = !isNegative;
            }

            const badgeBg = isGreen ? '#d1fae5' : '#fee2e2';
            const badgeColor = isGreen ? '#065f46' : '#991b1b';
            const badgeBorder = isGreen ? '#a7f3d0' : '#fecaca';

            previewContainer.html(`
                <span style="display:inline-flex; align-items:center; gap:4px; padding:1px 6px; border-radius:4px; font-size:0.72rem; font-weight:700; background:${badgeBg}; color:${badgeColor}; border:1px solid ${badgeBorder}; margin-top:2px;">
                    <i class="fas fa-wallet" style="font-size:0.68rem;"></i> Bal: Rs. ${absBal} ${suffix}
                </span>
            `);
        } else {
            previewContainer.empty();
        }
    });

    if (selectedVal) {
        targetSelect.val(selectedVal).trigger('change');
    }
}

function addEditRow() {
    var tableBody = document.querySelector('#editVoucherTable tbody');
    var row = document.createElement('tr');
    row.setAttribute('data-row-index', editRowIndex);
    row.innerHTML = `
        <td>
            <select name="rows[${editRowIndex}][item_type]" class="form-select form-select-sm jv-type-select fw-semibold" onchange="onEditJvTypeChange(this)" style="height: 34px; font-size: 0.82rem; padding: 2px 4px; border-radius: 4px;">
                <option value="customer">Customer</option>
                <option value="vendor">Vendor</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="account" selected>Account</option>
            </select>
        </td>
        <td>
            <div class="jv-target-wrap">
                <select name="rows[${editRowIndex}][account_id]" class="form-control jv-target-select select2-search" style="width:100%" required>
                    <option value="">-- Search & Select --</option>
                </select>
                <div class="jv-balance-preview mt-1"></div>
            </div>
        </td>
        <td>
            <input type="text" name="rows[${editRowIndex}][narration]" class="form-control form-control-sm" placeholder="Narration" style="height: 38px;">
        </td>
        <td>
            <input type="number" step="0.01" name="rows[${editRowIndex}][debit]" class="form-control form-control-sm text-end edit-debit fw-bold" value="0.00" oninput="calculateEditTotals()" style="height: 38px;">
        </td>
        <td>
            <input type="number" step="0.01" name="rows[${editRowIndex}][credit]" class="form-control form-control-sm text-end edit-credit fw-bold" value="0.00" oninput="calculateEditTotals()" style="height: 38px;">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-xs text-danger" onclick="removeEditRow(this)"><i class="fas fa-trash"></i></button>
        </td>
    `;
    tableBody.appendChild(row);
    populateEditJvTarget($(row).find('.jv-type-select')[0]);
    editRowIndex++;
}

function removeEditRow(btn) {
    var row = btn.closest('tr');
    if (document.querySelectorAll('#editVoucherTable tbody tr').length > 2) {
        row.remove();
        calculateEditTotals();
    } else {
        alert('A minimum of 2 rows are required.');
    }
}

function calculateEditTotals() {
    let debits = 0;
    let credits = 0;
    let hasOverdraft = false;
    let overdraftMsg = '';

    $('#editVoucherTable tbody tr').each(function() {
        const row = $(this);
        const typeSelect = row.find('.jv-type-select');
        const targetSelect = row.find('.jv-target-select');
        const creditInput = row.find('.edit-credit');
        const debitInput = row.find('.edit-debit');

        const itemType = typeSelect.val();
        const selectedOpt = targetSelect.find('option:selected');
        const rawBal = selectedOpt.attr('data-balance');
        const availBal = parseFloat(rawBal !== undefined ? rawBal : 0);
        const creditVal = parseFloat(creditInput.val() || 0);

        debits += parseFloat(debitInput.val() || 0);
        credits += creditVal;

        // Restriction Check: If Cash, Bank or general Asset account is Credited (withdrawn/deducted)
        let rowError = row.find('.jv-row-overdraft-error');
        if ((itemType === 'cash' || itemType === 'bank' || itemType === 'account') && creditVal > availBal) {
            hasOverdraft = true;
            creditInput.addClass('is-invalid');
            const accTitle = selectedOpt.text().split('(')[0].trim() || 'Account';
            const fAvail = 'Rs. ' + Math.max(0, availBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fCredit = 'Rs. ' + creditVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            overdraftMsg = `Credit on ${accTitle} (${fCredit}) exceeds available balance (${fAvail})!`;

            if (!rowError.length) {
                rowError = $('<div class="jv-row-overdraft-error text-danger fw-bold small mt-1" style="font-size:0.75rem;"><i class="fas fa-ban"></i> Exceeds balance (' + fAvail + ')</div>');
                row.find('.jv-target-wrap').append(rowError);
            } else {
                rowError.html('<i class="fas fa-ban"></i> Exceeds balance (' + fAvail + ')').show();
            }
        } else {
            creditInput.removeClass('is-invalid');
            if (rowError.length) {
                rowError.hide();
            }
        }
    });

    document.getElementById('totalDebitCell').innerText = debits.toFixed(2);
    document.getElementById('totalCreditCell').innerText = credits.toFixed(2);

    let badge = document.getElementById('editBalanceBadge');
    let submitBtn = document.getElementById('submitBtn');

    if (hasOverdraft) {
        badge.className = 'badge bg-danger px-3 py-1';
        badge.innerText = '⚠️ Insufficient Balance: ' + overdraftMsg;
        submitBtn.disabled = true;
    } else if (Math.abs(debits - credits) < 0.01 && debits > 0) {
        badge.className = 'badge bg-success px-3 py-1';
        badge.innerText = 'Balanced: Total Debit = Total Credit (Rs. ' + debits.toFixed(2) + ')';
        submitBtn.disabled = false;
    } else {
        badge.className = 'badge bg-danger px-3 py-1';
        badge.innerText = 'Unbalanced! Difference: Rs. ' + Math.abs(debits - credits).toFixed(2);
        submitBtn.disabled = true;
    }
}
</script>
@endsection
