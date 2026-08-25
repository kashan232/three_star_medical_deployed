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

    /* Modern Top Header */
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

    /* Modern Cards */
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

    /* Form Controls */
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

    .input-modern::placeholder {
        color: #94a3b8;
        font-weight: 500;
    }

    /* Select2 Modern Match */
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
        transition: all 0.2s !important;
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
        outline: none !important;
    }

    .select2-results__option {
        padding: 9px 14px !important;
        font-size: 0.88rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary) !important;
        color: #ffffff !important;
    }

    /* Floating Pill UI Action Component */
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

    .pill-btn-cancel:hover {
        background: rgba(239, 68, 68, 0.25);
        color: #fca5a5;
    }

    /* Currency Badge Input Group */
    .currency-group {
        display: flex;
        align-items: center;
    }
    .currency-addon {
        background: #f1f5f9;
        border: 1.5px solid #e2e8f0;
        border-right: none;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        padding: 0 14px;
        height: 44px;
        display: flex;
        align-items: center;
        font-weight: 700;
        font-size: 0.88rem;
        color: #475569;
    }
    .currency-input {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        font-size: 1.05rem !important;
        font-weight: 800 !important;
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
                $configs = [
                    'crv' => [
                        'title' => 'Cash Receiving Voucher (CRV)',
                        'sub' => 'DR: Cash In Hand | CR: Party / Customer',
                        'theme' => '#10b981',
                        'light' => '#d1fae5',
                        'icon' => 'fa-hand-holding-usd',
                        'save_btn' => 'Post Cash Receipt',
                    ],
                    'brv' => [
                        'title' => 'Bank Receiving Voucher (BRV)',
                        'sub' => 'DR: Bank Account | CR: Party / Customer',
                        'theme' => '#4f46e5',
                        'light' => '#e0e7ff',
                        'icon' => 'fa-university',
                        'save_btn' => 'Post Bank Receipt',
                    ],
                    'cpv' => [
                        'title' => 'Cash Payment Voucher (CPV)',
                        'sub' => 'DR: Vendor / Expense | CR: Cash In Hand',
                        'theme' => '#f59e0b',
                        'light' => '#fef3c7',
                        'icon' => 'fa-money-bill-wave',
                        'save_btn' => 'Post Cash Payment',
                    ],
                    'bpv' => [
                        'title' => 'Bank Payment Voucher (BPV)',
                        'sub' => 'DR: Vendor / Expense / CDR | CR: Bank Account',
                        'theme' => '#8b5cf6',
                        'light' => '#ede9fe',
                        'icon' => 'fa-file-invoice-dollar',
                        'save_btn' => 'Post Bank Payment',
                    ],
                    'jv' => [
                        'title' => 'Journal Voucher (JV)',
                        'sub' => 'Multi-Line Double Entry (Total DR = Total CR)',
                        'theme' => '#334155',
                        'light' => '#f1f5f9',
                        'icon' => 'fa-book',
                        'save_btn' => 'Post Journal Voucher',
                    ],
                ];
                $v = $configs[strtolower($type)] ?? $configs['crv'];
            @endphp

            <!-- Ultra-Premium Header -->
            <div class="voucher-header">
                <div class="title-group">
                    <div class="header-icon" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                        <i class="fas {{ $v['icon'] }}"></i>
                    </div>
                    <div>
                        <h1>{{ $v['title'] }}</h1>
                        <p>{{ $v['sub'] }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('vouchers.list', ['type' => $type]) }}" class="btn btn-sm btn-light border px-3 py-2 fw-bold text-secondary d-flex align-items-center gap-2" style="border-radius: 10px;">
                        <i class="fas fa-list-ul"></i> View All {{ strtoupper($type) }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 px-3 rounded-3 shadow-sm mb-3" role="alert">
                    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="close btn-close" data-dismiss="alert" data-bs-dismiss="alert"></button>
                </div>
            @endif

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

            <form action="{{ route('vouchers.store_action', ['type' => $type]) }}" method="POST" id="voucherForm">
                @csrf
                <input type="hidden" name="voucher_type" value="{{ $type }}">

                @if($type !== 'jv')
                <!-- 2-CARD ULTRA-MODERN SIDE-BY-SIDE LAYOUT -->
                <div class="row g-4">
                    
                    <!-- LEFT CARD: Location & Voucher Header -->
                    <div class="col-xl-3 col-lg-4 col-md-12">
                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <h3>VOUCHER HEADER & LOCATION</h3>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Voucher Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" class="input-modern" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Location / Branch <span class="text-danger">*</span></label>
                                        @if(isset($isSuperAdmin) && $isSuperAdmin && count($branches) > 0)
                                            <select name="branch_id" class="select2-search form-control" style="width:100%" required>
                                                @foreach($branches as $b)
                                                    <option value="{{ $b->id }}" {{ (auth()->user()->getBranchId() == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="input-modern" value="{{ $userBranch->name ?? 'HEAD OFFICE' }}" style="background: #f1f5f9; font-weight: 700; color: #4338ca;" readonly>
                                            <input type="hidden" name="branch_id" value="{{ $userBranch->id ?? 1 }}">
                                        @endif
                                    </div>
                                </div>

                                @if(in_array($type, ['brv', 'bpv']))
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Cheque Number</label>
                                        <input type="text" name="cheque_no" class="input-modern" placeholder="e.g. CHQ 1145628">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Cheque Date</label>
                                        <input type="date" name="cheque_date" class="input-modern" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                @endif

                                <div class="col-12">
                                    <div class="form-group-modern mb-0">
                                        <label class="label-modern">Main Remarks / Purpose <span class="text-danger">*</span></label>
                                        <input type="text" name="remarks" class="input-modern" placeholder="e.g. Payment for supplies / Receipt from customer" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT CARD: Accounts & Transaction Details -->
                    <div class="col-xl-9 col-lg-8 col-md-12">
                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <h3>ACCOUNTS & FINANCIAL ENTRY</h3>
                            </div>

                            @if($type === 'crv')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-success"><i class="fas fa-arrow-down me-1"></i> Debit Account (Cash in Hand) <span class="text-danger">*</span></label>
                                        <select name="cash_account_id" id="cash_account_id" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            @foreach($cashAccounts as $cash)
                                                <option value="{{ $cash->id }}" data-balance="{{ $cash->calculated_balance }}" data-type="cash">{{ $cash->account_code ?? '1-02-040' }} - {{ $cash->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group-modern">
                                        <label class="label-modern"><i class="fas fa-users-cog me-1"></i> Party Type <span class="text-danger">*</span></label>
                                        <select name="party_type" id="crvPartyType" class="input-modern" onchange="onCrvPartyTypeChange(this.value)" required>
                                            <option value="customer" selected>Customer</option>
                                            <option value="vendor">Vendor</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-primary" id="crvPartyLabel"><i class="fas fa-user-check me-1"></i> Credit Customer <span class="text-danger">*</span></label>
                                        <select name="party_id" id="crvPartyId" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <option value="" id="crvPartyPlaceholder">-- Search Customer --</option>
                                            <optgroup label="Customers" id="crvCustomerGroup">
                                                @foreach($customers as $c)
                                                    <option value="customer_{{ $c->id }}" data-balance="{{ $c->previous_balance }}" data-type="customer">1-02-051-{{ str_pad($c->id, 5, '0', STR_PAD_LEFT) }} - {{ $c->customer_name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Vendors / Suppliers" id="crvVendorGroup" style="display:none;">
                                                @foreach($vendors as $vItem)
                                                    <option value="vendor_{{ $vItem->id }}" data-balance="{{ $vItem->previous_balance }}" data-type="vendor">2-02-010-{{ str_pad($vItem->id, 5, '0', STR_PAD_LEFT) }} - {{ $vItem->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Received Amount <span class="text-danger">*</span></label>
                                        <div class="currency-group">
                                            <span class="currency-addon text-success">Rs.</span>
                                            <input type="number" step="0.01" name="amount" class="input-modern currency-input text-success" placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Line Narration / Description</label>
                                        <input type="text" name="line_narration" class="input-modern" placeholder="e.g. CASH REC FROM CUSTOMER / VENDOR REFUND">
                                    </div>
                                </div>
                            </div>

                            @elseif($type === 'brv')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-primary"><i class="fas fa-university me-1"></i> Debit Account (Receiving Bank) <span class="text-danger">*</span></label>
                                        <select name="bank_account_id" id="bank_account_id" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <option value="">-- Select Bank Account --</option>
                                            @foreach($bankAccounts as $bank)
                                                <option value="{{ $bank->id }}" data-balance="{{ $bank->calculated_balance }}" data-type="bank">{{ $bank->account_code ?? '1-02-052' }} - {{ $bank->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="form-group-modern">
                                        <label class="label-modern"><i class="fas fa-users-cog me-1"></i> Party Type <span class="text-danger">*</span></label>
                                        <select name="party_type" id="brvPartyType" class="input-modern" onchange="onBrvPartyTypeChange(this.value)" required>
                                            <option value="customer" selected>Customer</option>
                                            <option value="vendor">Vendor</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-success" id="brvPartyLabel"><i class="fas fa-user-check me-1"></i> Credit Customer <span class="text-danger">*</span></label>
                                        <select name="party_id" id="brvPartyId" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <option value="" id="brvPartyPlaceholder">-- Search Customer --</option>
                                            <optgroup label="Customers" id="brvCustomerGroup">
                                                @foreach($customers as $c)
                                                    <option value="customer_{{ $c->id }}" data-balance="{{ $c->previous_balance }}" data-type="customer">1-02-051-{{ str_pad($c->id, 5, '0', STR_PAD_LEFT) }} - {{ $c->customer_name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Vendors / Suppliers" id="brvVendorGroup" style="display:none;">
                                                @foreach($vendors as $vItem)
                                                    <option value="vendor_{{ $vItem->id }}" data-balance="{{ $vItem->previous_balance }}" data-type="vendor">2-02-010-{{ str_pad($vItem->id, 5, '0', STR_PAD_LEFT) }} - {{ $vItem->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Received Amount <span class="text-danger">*</span></label>
                                        <div class="currency-group">
                                            <span class="currency-addon text-primary">Rs.</span>
                                            <input type="number" step="0.01" name="amount" class="input-modern currency-input text-primary" placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Line Narration / Description</label>
                                        <input type="text" name="line_narration" class="input-modern" placeholder="e.g. CHQ#10617175 REC FROM CUSTOMER / VENDOR REFUND DEPOSIT IN BANK">
                                    </div>
                                </div>
                            </div>

                            @elseif($type === 'cpv')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Category <span class="text-danger">*</span></label>
                                        <select name="pay_target_type" id="cpvTargetType" class="input-modern" onchange="toggleCpvParty(this.value)" required>
                                            <option value="vendor">Vendor / Supplier</option>
                                            <option value="expense">Expense Account</option>
                                            <option value="customer">Customer Refund</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-danger"><i class="fas fa-arrow-down me-1"></i> Debit Target (Party / Head) <span class="text-danger">*</span></label>
                                        <select name="target_id" id="cpvTargetId" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <optgroup label="Vendors / Suppliers" id="vendorOptGroup">
                                                @foreach($vendors as $vItem)
                                                    <option value="vendor_{{ $vItem->id }}" data-balance="{{ $vItem->previous_balance }}" data-type="vendor">2-02-010-{{ str_pad($vItem->id, 5, '0', STR_PAD_LEFT) }} - {{ $vItem->name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Expense Accounts" id="expenseOptGroup" style="display:none;">
                                                @foreach($expenseAccounts as $exp)
                                                    <option value="account_{{ $exp->id }}" data-balance="{{ $exp->calculated_balance }}" data-type="expense">{{ $exp->account_code ?? '5-02' }} - {{ $exp->title }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Customers" id="customerOptGroup" style="display:none;">
                                                @foreach($customers as $c)
                                                    <option value="customer_{{ $c->id }}" data-balance="{{ $c->previous_balance }}" data-type="customer">1-02-051-{{ str_pad($c->id, 5, '0', STR_PAD_LEFT) }} - {{ $c->customer_name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-warning"><i class="fas fa-money-bill-wave me-1"></i> Credit Account (Cash in Hand) <span class="text-danger">*</span></label>
                                        <select name="cash_account_id" id="cash_account_id" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            @foreach($cashAccounts as $cash)
                                                <option value="{{ $cash->id }}" data-balance="{{ $cash->calculated_balance }}" data-type="cash">{{ $cash->account_code ?? '1-02-040' }} - {{ $cash->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="currency-group">
                                            <span class="currency-addon text-danger">Rs.</span>
                                            <input type="number" step="0.01" name="amount" class="input-modern currency-input text-danger" placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group-modern mb-0">
                                        <label class="label-modern">Line Narration / Description</label>
                                        <input type="text" name="line_narration" class="input-modern" placeholder="e.g. Cash paid for office utilities / repair">
                                    </div>
                                </div>
                            </div>

                            @elseif($type === 'bpv')
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Category <span class="text-danger">*</span></label>
                                        <select name="pay_target_type" id="bpvTargetType" class="input-modern" onchange="toggleBpvParty(this.value)" required>
                                            <option value="vendor">Vendor / Supplier</option>
                                            <option value="expense">Expense Account</option>
                                            <option value="cdr">CDR / Tender Party</option>
                                            <option value="customer">Customer Refund</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="form-group-modern">
                                        <label class="label-modern" style="color:#8b5cf6;"><i class="fas fa-arrow-down me-1"></i> Debit Target (Vendor / Expense / CDR) <span class="text-danger">*</span></label>
                                        <select name="target_id" id="bpvTargetId" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <optgroup label="Vendors / Suppliers" id="bpvVendorGroup">
                                                @foreach($vendors as $vItem)
                                                    <option value="vendor_{{ $vItem->id }}" data-balance="{{ $vItem->previous_balance }}" data-type="vendor">2-02-010-{{ str_pad($vItem->id, 5, '0', STR_PAD_LEFT) }} - {{ $vItem->name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Expense Accounts" id="bpvExpenseGroup" style="display:none;">
                                                @foreach($expenseAccounts as $exp)
                                                    <option value="account_{{ $exp->id }}" data-balance="{{ $exp->calculated_balance }}" data-type="expense">{{ $exp->account_code ?? '5-02' }} - {{ $exp->title }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Customers / CDR Parties" id="bpvCustomerGroup" style="display:none;">
                                                @foreach($customers as $c)
                                                    <option value="customer_{{ $c->id }}" data-balance="{{ $c->previous_balance }}" data-type="customer">1-02-051-{{ str_pad($c->id, 5, '0', STR_PAD_LEFT) }} - {{ $c->customer_name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="label-modern text-primary"><i class="fas fa-university me-1"></i> Credit Account (Paying Bank) <span class="text-danger">*</span></label>
                                        <select name="bank_account_id" id="bank_account_id" class="select2-search form-control account-balance-select" style="width:100%" required>
                                            <option value="">-- Select Bank Account --</option>
                                            @foreach($bankAccounts as $bank)
                                                <option value="{{ $bank->id }}" data-balance="{{ $bank->calculated_balance }}" data-type="bank">{{ $bank->account_code ?? '1-02-052' }} - {{ $bank->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Payment Amount <span class="text-danger">*</span></label>
                                        <div class="currency-group">
                                            <span class="currency-addon text-danger">Rs.</span>
                                            <input type="number" step="0.01" name="amount" class="input-modern currency-input text-danger" placeholder="0.00" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="form-group-modern mb-0">
                                        <label class="label-modern">Line Narration / Description</label>
                                        <input type="text" name="line_narration" class="input-modern" placeholder="e.g. DATED 13/03/2026 CHQ 1145628 FIC 2%CDR">
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>

                </div>

                @else
                {{-- JOURNAL VOUCHER (JV) 2-CARD SIDE-BY-SIDE LAYOUT --}}
                <div class="row g-4 mb-5">
                    <!-- LEFT CARD: Header Details -->
                    <div class="col-xl-3 col-lg-4 col-md-12">
                        <div class="premium-card">
                            <div class="card-header-modern">
                                <div class="icon-box" style="background: {{ $v['light'] }}; color: {{ $v['theme'] }};">
                                    <i class="fas fa-book"></i>
                                </div>
                                <h3>JV HEADER DETAILS</h3>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Voucher Date <span class="text-danger">*</span></label>
                                        <input type="date" name="date" class="input-modern" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-modern">
                                        <label class="label-modern">Location / Branch <span class="text-danger">*</span></label>
                                        @if(isset($isSuperAdmin) && $isSuperAdmin && count($branches) > 0)
                                            <select name="branch_id" class="select2-search form-control" style="width:100%" required>
                                                @foreach($branches as $b)
                                                    <option value="{{ $b->id }}" {{ (auth()->user()->getBranchId() == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="input-modern" value="{{ $userBranch->name ?? 'HEAD OFFICE' }}" style="background: #f1f5f9; font-weight: 700; color: #4338ca;" readonly>
                                            <input type="hidden" name="branch_id" value="{{ $userBranch->id ?? 1 }}">
                                        @endif
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-modern mb-0">
                                        <label class="label-modern">Remarks / Main Narration <span class="text-danger">*</span></label>
                                        <textarea name="remarks" rows="3" class="input-modern" style="height:auto;" placeholder="e.g. Sales return adjustment, Bank to Bank transfer..." required></textarea>
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
                                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 d-flex align-items-center gap-1 shadow-sm" style="border-radius:8px;" onclick="addJvRow()">
                                    <i class="fas fa-plus"></i> Add Line
                                </button>
                            </div>

                            <div class="table-responsive mt-2">
                                <table class="table table-bordered table-sm align-middle mb-0" id="jvTable" style="font-size:0.84rem;">
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
                                        <tr data-row-index="0">
                                            <td>
                                                <select name="rows[0][item_type]" class="form-select form-select-sm jv-type-select fw-semibold" onchange="onJvTypeChange(this)" style="height: 34px; font-size: 0.82rem; padding: 2px 4px; border-radius: 4px;">
                                                    <option value="customer" selected>Customer</option>
                                                    <option value="vendor">Vendor</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="bank">Bank</option>
                                                    <option value="account">Account</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="jv-target-wrap">
                                                    <select name="rows[0][account_id]" class="form-control jv-target-select select2-search" style="width:100%" required>
                                                        <option value="">-- Search & Select --</option>
                                                    </select>
                                                    <div class="jv-balance-preview"></div>
                                                </div>
                                            </td>
                                            <td><input type="text" name="rows[0][narration]" class="form-control form-control-sm" placeholder="Line Narration" style="height: 34px; font-size: 0.83rem;"></td>
                                            <td><input type="number" step="0.01" name="rows[0][debit]" class="form-control form-control-sm text-end jv-debit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;"></td>
                                            <td><input type="number" step="0.01" name="rows[0][credit]" class="form-control form-control-sm text-end jv-credit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;"></td>
                                            <td class="text-center"><button type="button" class="btn btn-xs text-danger p-0" onclick="removeJvRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
                                        <tr data-row-index="1">
                                            <td>
                                                <select name="rows[1][item_type]" class="form-select form-select-sm jv-type-select fw-semibold" onchange="onJvTypeChange(this)" style="height: 34px; font-size: 0.82rem; padding: 2px 4px; border-radius: 4px;">
                                                    <option value="customer">Customer</option>
                                                    <option value="vendor">Vendor</option>
                                                    <option value="cash">Cash</option>
                                                    <option value="bank" selected>Bank</option>
                                                    <option value="account">Account</option>
                                                </select>
                                            </td>
                                            <td>
                                                <div class="jv-target-wrap">
                                                    <select name="rows[1][account_id]" class="form-control jv-target-select select2-search" style="width:100%" required>
                                                        <option value="">-- Search & Select --</option>
                                                    </select>
                                                    <div class="jv-balance-preview"></div>
                                                </div>
                                            </td>
                                            <td><input type="text" name="rows[1][narration]" class="form-control form-control-sm" placeholder="Line Narration" style="height: 34px; font-size: 0.83rem;"></td>
                                            <td><input type="number" step="0.01" name="rows[1][debit]" class="form-control form-control-sm text-end jv-debit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;"></td>
                                            <td><input type="number" step="0.01" name="rows[1][credit]" class="form-control form-control-sm text-end jv-credit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;"></td>
                                            <td class="text-center"><button type="button" class="btn btn-xs text-danger p-0" onclick="removeJvRow(this)"><i class="fas fa-trash"></i></button></td>
                                        </tr>
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
                                                <span class="badge bg-success px-3 py-1" id="jvBalanceBadge" style="font-size:0.82rem; border-radius: 20px;">Balanced: DR = CR</span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- FLOATING CAPSULE ACTION BAR -->
                <div class="floating-action-pill">
                    <a href="{{ route('vouchers.list', ['type' => $type]) }}" class="pill-btn pill-btn-cancel">
                        <i class="fas fa-times"></i> Discard Changes
                    </a>
                    <button type="submit" class="pill-btn pill-btn-save" id="submitBtn" style="background: {{ $v['theme'] }};">
                        <i class="fas fa-check-circle"></i> {{ $v['save_btn'] }}
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    runSelect2Init();
    initAccountBalanceBadges();
    initJvRows();
});

function runSelect2Init() {
    if ($.fn.select2) {
        $('.select2-search').each(function() {
            if (!$(this).hasClass("select2-hidden-accessible")) {
                $(this).select2({
                    placeholder: $(this).find('option:first').text() || "-- Search & Select --",
                    allowClear: true,
                    width: '100%'
                });
            }
        });
    }
}

// Live Account Balance Badge Display
function initAccountBalanceBadges() {
    $(document).on('change', '.account-balance-select', function() {
        const select = $(this);
        const selected = select.find('option:selected');
        const balance = selected.attr('data-balance') !== undefined ? selected.attr('data-balance') : selected.data('balance');
        const type = selected.attr('data-type') || selected.data('type') || 'Account';
        
        let parentWrap = select.closest('.form-group-modern, .form-group, .form-group-compact, td');
        let container = parentWrap.find('.balance-preview-wrap');
        if (!container.length) {
            container = $('<div class="balance-preview-wrap mt-1"></div>');
            parentWrap.append(container);
        }

        if (balance !== undefined && balance !== null && balance !== '' && select.val()) {
            const numBal = parseFloat(balance) || 0;
            const absBal = Math.abs(numBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            let label = "Closing Balance";
            let suffix = "";
            let badgeClass = "badge-blue";
            let icon = "fa-wallet";

            if (type === 'customer') {
                label = "Customer Closing Balance";
                icon = "fa-user-check";
                if (numBal > 0) {
                    suffix = "Dr (Receivable)";
                    badgeClass = "badge-green";
                } else if (numBal < 0) {
                    suffix = "Cr (Advance)";
                    badgeClass = "badge-amber";
                } else {
                    suffix = "Nil";
                    badgeClass = "badge-blue";
                }
            } else if (type === 'vendor') {
                label = "Vendor Closing Balance";
                icon = "fa-truck";
                if (numBal > 0) {
                    suffix = "Cr (Payable)";
                    badgeClass = "badge-red";
                } else if (numBal < 0) {
                    suffix = "Dr (Advance Paid)";
                    badgeClass = "badge-green";
                } else {
                    suffix = "Nil";
                    badgeClass = "badge-blue";
                }
            } else if (type === 'cash') {
                label = "Cash Balance";
                icon = "fa-money-bill-wave";
                badgeClass = numBal < 0 ? "badge-red" : "badge-green";
                suffix = numBal < 0 ? "Cr (Overdrawn)" : "Dr";
            } else if (type === 'bank') {
                label = "Bank Balance";
                icon = "fa-university";
                badgeClass = numBal < 0 ? "badge-red" : "badge-blue";
                suffix = numBal < 0 ? "Cr (Overdrawn)" : "Dr";
            } else {
                label = "Closing Balance";
                badgeClass = numBal < 0 ? "badge-red" : "badge-green";
                suffix = numBal < 0 ? "Cr" : "Dr";
            }

            container.html(`
                <span class="balance-preview-badge ${badgeClass}">
                    <i class="fas ${icon}"></i> ${label}: <strong>Rs. ${absBal}</strong> <span style="opacity:0.85; font-size:0.72rem; font-weight:600;">(${suffix})</span>
                </span>
            `);
        } else {
            container.empty();
        }
    });

    // Auto-trigger on initial page load if any select has pre-filled value
    setTimeout(function() {
        $('.account-balance-select').each(function() {
            if ($(this).val()) {
                $(this).trigger('change');
            }
        });
    }, 200);
}

function onCrvPartyTypeChange(val) {
    var custOpt = $('#crvCustomerGroup');
    var vendOpt = $('#crvVendorGroup');
    var isVendor = (val === 'vendor');
    
    custOpt.toggle(!isVendor);
    vendOpt.toggle(isVendor);

    var placeholderText = isVendor ? "-- Search Vendor --" : "-- Search Customer --";
    var labelText = isVendor ? '<i class="fas fa-truck me-1"></i> Credit Vendor <span class="text-danger">*</span>' : '<i class="fas fa-user-check me-1"></i> Credit Customer <span class="text-danger">*</span>';
    
    $('#crvPartyLabel').html(labelText);
    $('#crvPartyPlaceholder').text(placeholderText);

    var $select = $('#crvPartyId');
    $select.val('');
    
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        placeholder: placeholderText,
        allowClear: true,
        width: '100%'
    });
    $select.trigger('change');
}

function onBrvPartyTypeChange(val) {
    var custOpt = $('#brvCustomerGroup');
    var vendOpt = $('#brvVendorGroup');
    var isVendor = (val === 'vendor');
    
    custOpt.toggle(!isVendor);
    vendOpt.toggle(isVendor);

    var placeholderText = isVendor ? "-- Search Vendor --" : "-- Search Customer --";
    var labelText = isVendor ? '<i class="fas fa-truck me-1"></i> Credit Vendor <span class="text-danger">*</span>' : '<i class="fas fa-user-check me-1"></i> Credit Customer <span class="text-danger">*</span>';
    
    $('#brvPartyLabel').html(labelText);
    $('#brvPartyPlaceholder').text(placeholderText);

    var $select = $('#brvPartyId');
    $select.val('');
    
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        placeholder: placeholderText,
        allowClear: true,
        width: '100%'
    });
    $select.trigger('change');
}

function toggleCpvParty(val) {
    var vendorOpt = $('#vendorOptGroup');
    var expenseOpt = $('#expenseOptGroup');
    var customerOpt = $('#customerOptGroup');
    
    vendorOpt.toggle(val === 'vendor');
    expenseOpt.toggle(val === 'expense');
    customerOpt.toggle(val === 'customer');

    var placeholderText = "-- Search & Select --";
    if (val === 'vendor') placeholderText = "-- Search Vendor --";
    else if (val === 'expense') placeholderText = "-- Search Expense Account --";
    else if (val === 'customer') placeholderText = "-- Search Customer --";

    var $select = $('#cpvTargetId');
    $select.val('');
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        placeholder: placeholderText,
        allowClear: true,
        width: '100%'
    });
    $select.trigger('change');
}
var onCpvCategoryChange = toggleCpvParty;

function toggleBpvParty(val) {
    var vendorOpt = $('#bpvVendorGroup');
    var expenseOpt = $('#bpvExpenseGroup');
    var customerOpt = $('#bpvCustomerGroup');
    
    vendorOpt.toggle(val === 'vendor');
    expenseOpt.toggle(val === 'expense');
    customerOpt.toggle(val === 'cdr' || val === 'customer');

    var placeholderText = "-- Search & Select --";
    if (val === 'vendor') placeholderText = "-- Search Vendor --";
    else if (val === 'expense') placeholderText = "-- Search Expense Account --";
    else if (val === 'cdr' || val === 'customer') placeholderText = "-- Search Customer / CDR --";

    var $select = $('#bpvTargetId');
    $select.val('');
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.select2({
        placeholder: placeholderText,
        allowClear: true,
        width: '100%'
    });
    $select.trigger('change');
}
var onBpvCategoryChange = toggleBpvParty;

// ----------------------------------------------------
// JV MULTI-PARTY DYNAMIC DATA & EVENT HANDLERS
// ----------------------------------------------------
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

function initJvRows() {
    $('#jvTable tbody tr').each(function() {
        const typeSelect = $(this).find('.jv-type-select');
        if (typeSelect.length) {
            populateJvTarget(typeSelect[0]);
        }
    });
}

function onJvTypeChange(elem) {
    populateJvTarget(elem);
}

function populateJvTarget(typeSelectElem, selectedVal = null) {
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

    // Destroy existing select2 if initialized
    if (targetSelect.hasClass("select2-hidden-accessible")) {
        targetSelect.select2('destroy');
    }

    targetSelect.empty();
    targetSelect.append(new Option(placeholder, ''));

    dataList.forEach(function(item) {
        const opt = new Option(item.label, item.id, false, selectedVal && selectedVal == item.id);
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

function validateSinglePaymentBalance() {
    const isCpv = '{{ $type }}' === 'cpv';
    const isBpv = '{{ $type }}' === 'bpv';
    if (!isCpv && !isBpv) return true;

    const selectEl = isCpv ? $('#cash_account_id') : $('#bank_account_id');
    const amountInput = $('input[name="amount"]');
    const selected = selectEl.find('option:selected');
    const rawBal = selected.attr('data-balance');
    const availBal = parseFloat(rawBal !== undefined ? rawBal : 0);
    const enteredAmt = parseFloat(amountInput.val() || 0);

    let errorBox = $('#paymentBalanceAlert');
    if (!errorBox.length) {
        errorBox = $('<div id="paymentBalanceAlert" class="alert alert-danger p-2 mt-2 fw-bold" style="display:none; font-size:0.85rem; border-radius:8px;"></div>');
        amountInput.closest('.form-group-modern').append(errorBox);
    }

    if (enteredAmt > availBal) {
        const typeLabel = isCpv ? 'Cash in Hand' : 'Bank';
        const formattedAvail = 'Rs. ' + Math.max(0, availBal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formattedReq = 'Rs. ' + enteredAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        errorBox.html(`<i class="fas fa-exclamation-triangle"></i> <strong>Insufficient Balance!</strong> Entered amount (${formattedReq}) exceeds available balance in ${typeLabel} (${formattedAvail}).`).show();
        amountInput.addClass('is-invalid');
        $('#submitBtn').prop('disabled', true);
        return false;
    } else {
        errorBox.hide();
        amountInput.removeClass('is-invalid');
        if (enteredAmt > 0) {
            $('#submitBtn').prop('disabled', false);
        }
        return true;
    }
}

$(document).on('input change', 'input[name="amount"], #cash_account_id, #bank_account_id', function() {
    validateSinglePaymentBalance();
});

let jvIndex = 2;
function addJvRow() {
    var tableBody = document.querySelector('#jvTable tbody');
    var row = document.createElement('tr');
    row.setAttribute('data-row-index', jvIndex);
    row.innerHTML = `
        <td>
            <select name="rows[${jvIndex}][item_type]" class="form-select form-select-sm jv-type-select fw-semibold" onchange="onJvTypeChange(this)" style="height: 34px; font-size: 0.82rem; padding: 2px 4px; border-radius: 4px;">
                <option value="customer">Customer</option>
                <option value="vendor">Vendor</option>
                <option value="cash">Cash</option>
                <option value="bank">Bank</option>
                <option value="account" selected>Account</option>
            </select>
        </td>
        <td>
            <div class="jv-target-wrap">
                <select name="rows[${jvIndex}][account_id]" class="form-control jv-target-select select2-search" style="width:100%" required>
                    <option value="">-- Search & Select --</option>
                </select>
                <div class="jv-balance-preview"></div>
            </div>
        </td>
        <td>
            <input type="text" name="rows[${jvIndex}][narration]" class="form-control form-control-sm" placeholder="Line Narration" style="height: 34px; font-size: 0.83rem;">
        </td>
        <td>
            <input type="number" step="0.01" name="rows[${jvIndex}][debit]" class="form-control form-control-sm text-end jv-debit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;">
        </td>
        <td>
            <input type="number" step="0.01" name="rows[${jvIndex}][credit]" class="form-control form-control-sm text-end jv-credit fw-bold" value="0.00" oninput="calculateJvTotals()" style="height: 34px; font-size: 0.84rem;">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-xs text-danger p-0" onclick="removeJvRow(this)"><i class="fas fa-trash"></i></button>
        </td>
    `;
    tableBody.appendChild(row);
    populateJvTarget($(row).find('.jv-type-select')[0]);
    jvIndex++;
}

function removeJvRow(btn) {
    var row = btn.closest('tr');
    if (document.querySelectorAll('#jvTable tbody tr').length > 2) {
        row.remove();
        calculateJvTotals();
    } else {
        alert('A minimum of 2 rows are required for a Journal Voucher.');
    }
}

function calculateJvTotals() {
    let debits = 0;
    let credits = 0;
    let hasOverdraft = false;
    let overdraftMsg = '';

    $('#jvTable tbody tr').each(function() {
        const row = $(this);
        const typeSelect = row.find('.jv-type-select');
        const targetSelect = row.find('.jv-target-select');
        const creditInput = row.find('.jv-credit');
        const debitInput = row.find('.jv-debit');

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

    let badge = document.getElementById('jvBalanceBadge');
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
