@extends('admin_panel.layout.app')

@section('content')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            background-color: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #334155;
        }

        /* Page Header */
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.025em;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        /* ERP Cards */
        .erp-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            margin-bottom: 1.25rem;
            transition: box-shadow 0.2s ease-in-out;
        }

        .erp-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        }

        .erp-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            border-radius: 12px 12px 0 0;
        }

        .erp-card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .erp-card-body {
            padding: 1.5rem;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.375rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 0.5rem 0.875rem;
            font-size: 0.875rem;
            background-color: #f8fafc;
            color: #1e293b;
            transition: all 0.2s ease;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.01);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        .form-control:read-only {
            background-color: #f1f5f9;
            border-color: #e2e8f0;
            color: #64748b;
            cursor: not-allowed;
        }

        /* Table Styles */
        .erp-table-wrapper {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }

        .erp-table {
            margin-bottom: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .erp-table thead {
            background-color: #f8fafc;
        }

        .erp-table thead th {
            padding: 0.875rem 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            vertical-align: middle;
        }

        .erp-table tbody td {
            padding: 0.5rem 0.4rem;
            font-size: 0.85rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            background: #ffffff;
        }

        .erp-table tbody tr:hover td {
            background-color: #f8fafc;
        }

        .erp-table tbody tr:last-child td {
            border-bottom: none;
        }

        .erp-table input.form-control {
            padding: 0.375rem 0.3rem;
            background-color: #ffffff;
            border: 1px solid transparent;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 4px;
            box-shadow: none;
        }

        .erp-table input.form-control:focus {
            border-color: #3b82f6;
            background-color: #ffffff;
            box-shadow: inset 0 0 0 1px #3b82f6;
        }

        .erp-table input.form-control:read-only {
            background-color: transparent;
            border-color: transparent;
            font-weight: 500;
            cursor: default;
            text-align: right;
        }

        /* Input Group Polish */
        .erp-table .input-group {
            flex-wrap: nowrap;
        }

        .erp-table .input-group .form-control {
            border: 1px solid #e2e8f0;
            border-right: none;
            border-radius: 6px 0 0 6px !important;
            flex: 1 1 auto !important;
            min-width: 60px !important;
            width: 100% !important;
        }

        .erp-table .input-group .btn,
        .erp-table .input-group .input-group-text {
            border: 1px solid #e2e8f0 !important;
            border-radius: 0 6px 6px 0 !important;
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            padding: 0 0.4rem;
            transition: all 0.2s;
            min-width: 32px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Highlight inputs (Net Totals etc) */
        .input-highlight {
            font-weight: 700 !important;
            color: #059669 !important;
            /* Emerald 600 */
            font-size: 0.95rem;
        }

        /* Product Search */
        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            padding-left: 2.75rem !important;
            border-color: #cbd5e1;
            background-color: #fff;
            height: 42px;
            border-radius: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .search-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1), 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .searchResults {
            position: absolute;
            z-index: 9999;
            width: 100%;
            max-height: 250px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin-top: 5px;
            padding: 0.5rem;
        }

        .search-result-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: none;
            margin-bottom: 2px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-result-item:hover,
        .search-result-item.active {
            background-color: #eff6ff;
            color: #1e40af;
        }

        /* Summary Totals */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        #btn_import_po {
            cursor: pointer;
        }

        #btn_import_po:disabled {
            cursor: not-allowed !important;
            opacity: 0.65;
        }

        .summary-row:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
        }

        .summary-total-row {
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-top: 1rem;
        }

        .summary-total-label {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .summary-total-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2563eb;
        }

        /* Buttons */
        .btn-erp {
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-erp-primary {
            background: linear-gradient(to bottom, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 1px 2px rgba(37, 99, 235, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-erp-primary:hover {
            background: linear-gradient(to bottom, #2563eb, #1d4ed8);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-erp-success {
            background: linear-gradient(to bottom, #10b981, #059669);
            color: white;
            box-shadow: 0 1px 2px rgba(5, 150, 105, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        .btn-erp-success:hover {
            background: linear-gradient(to bottom, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .btn-erp-secondary {
            background-color: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-erp-secondary:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #1e293b;
        }

        .btn-erp-danger-ghost {
            background: transparent;
            color: #ef4444;
            padding: 0.375rem;
            border-radius: 6px;
        }

        .btn-erp-danger-ghost:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Badges */
        .erp-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Chrome, Safari, Edge */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Firefox */
        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">
                <meta name="csrf-token" content="{{ csrf_token() }}">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

                @php 
                    $isPO = ($purchase->status_purchase == 'draft');
                @endphp
                <form id="purchaseForm" action="{{ route('store.Purchase') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" id="action" name="action" value="save_only">
                    <input type="hidden" id="draft_id_input" name="draft_id" value="{{ $purchase->id }}">
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">

                    <!-- Page Header Top -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title {{ $isPO ? 'text-primary' : 'text-success' }}">
                            <i class="bi {{ $isPO ? 'bi-file-earmark-plus' : 'bi-patch-check' }} me-2"></i>
                            {{ $isPO ? 'Purchase Order (PO)' : 'Goods Receipt Note (GRN)' }}
                        </h1>
                        <p class="page-subtitle mb-0">
                            {{ $isPO ? 'Modify your procurement request details.' : 'Post final stock arrivals and update inventory status.' }}
                        </p>
                        <div class="d-flex gap-3">
                             @if(!$isPO)
                             <button type="button" class="btn-erp btn-erp-primary" id="btn_import_po" data-toggle="modal" 
                                 data-target="#bookedProductsModal" style="padding: 0.4rem 1rem;" disabled title="Please select a vendor first">
                                 <i class="bi bi-download me-1"></i> Import PO
                             </button>
                             @endif
                             <a href="{{ $isPO ? route('purchase.order.index') : route('purchase.grn.index') }}" class="btn-erp btn-erp-secondary">
                                 <i class="bi bi-arrow-left"></i> Back to {{ $isPO ? 'PO Registry' : 'GRN List' }}
                             </a>
                            <!-- Shortcut Actions -->
                        </div>
                    </div>

                    <div class="erp-card mb-3">
                        <div class="erp-card-header">
                            <h3 class="erp-card-title {{ $isPO ? 'text-primary' : 'text-success' }}"><i class="bi {{ $isPO ? 'bi-info-circle' : 'bi-check2-circle' }}"></i>
                                {{ $isPO ? 'Order Information' : 'GRN Verification Information' }}
                            </h3>
                            @if(!$isPO)
                            <span class="erp-badge bg-success-subtle text-success border-success-subtle"><i
                                    class="bi bi-shield-check me-1"></i>Official Receipt</span>
                            @endif
                        </div>
                        <div class="erp-card-body p-3 bg-light">
                            <div class="row gx-3 gy-3">
                                <!-- Left Section (Matches Screenshot) -->
                                <div class="col-lg-8">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100">
                                        <table class="table table-borderless table-sm mb-0">
                                            <style>
                                                .compact-lbl {
                                                    font-size: 12px;
                                                    font-weight: 700;
                                                    color: #475569;
                                                    white-space: nowrap;
                                                    vertical-align: middle;
                                                }

                                                .compact-input {
                                                    padding: 0.25rem 0.5rem;
                                                    font-size: 13px;
                                                    border-radius: 4px;
                                                    border: 1px solid #cbd5e1;
                                                    width: 100%;
                                                    transition: all 0.2s;
                                                }

                                                .compact-input:focus {
                                                    border-color: #3b82f6;
                                                    outline: none;
                                                    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
                                                }

                                                .compact-select {
                                                    padding: 0.25rem 0.5rem;
                                                    font-size: 13px;
                                                    border-radius: 4px;
                                                    border: 1px solid #cbd5e1;
                                                    width: 100%;
                                                    background-color: #fff;
                                                }

                                                .compact-td {
                                                    padding: 0.3rem 0.5rem !important;
                                                    vertical-align: middle;
                                                }
                                            </style>
                                            <tbody>
                                                <tr>
                                                    <td class="compact-lbl compact-td" style="width: 15%;">LOCATION :</td>
                                                    <td colspan="3" class="compact-td">
                                                        <div class="d-flex gap-2 align-items-center">
                                                            <input type="text" class="form-control fw-bold shadow-sm"
                                                                style="max-width: 150px;" value="{{ $branchName }}" readonly
                                                                disabled title="Branch Scoped">
                                                            <input type="hidden" name="warehouse_id" id="main_warehouse_id" value="{{ $purchase->warehouse_id }}">
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">{{ $isPO ? 'PO #' : 'GRN #' }}</td>
                                                    <td class="fw-bold compact-td {{ $isPO ? 'text-primary' : 'text-success' }}" style="width: 35%;">
                                                        <input type="hidden" name="invoice_no"
                                                            value="{{ $nextInvoice ?? ($isPO ? 'PO-0001' : 'GRN-0001') }}">
                                                        <input type="text" readonly
                                                            value="{{ $nextInvoice ?? ($isPO ? 'PO-0001' : 'GRN-0001') }}"
                                                            class="bg-transparent border-0 p-0 w-100 fw-bold {{ $isPO ? 'text-primary' : 'text-success' }}"
                                                            style="outline: none;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td" style="width: 25%;">VENDOR
                                                        BILL #</td>
                                                    <td style="width: 25%;" class="compact-td"><input type="text"
                                                            name="vendor_bill_no" class="compact-input shadow-sm" value="{{ $purchase->vendor_bill_no ?? '' }}"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">{{ $isPO ? 'PO DATE :' : 'GRN DATE :' }}</td>
                                                    <td class="compact-td" colspan="3"><input type="date"
                                                            name="purchase_date" value="{{ $purchase->purchase_date ? date('Y-m-d', strtotime($purchase->purchase_date)) : date('Y-m-d') }}"
                                                            class="compact-input shadow-sm text-secondary"
                                                            style="max-width: 200px;"></td>
                                                </tr>
                                                @if(!$isPO)
                                                <tr>
                                                    <td class="compact-lbl compact-td">PO #</td>
                                                    <td class="text-secondary compact-td">
                                                        <!-- Note: the name was requested as purchase_order_no in the form -->
                                                        <input type="text" name="purchase_order_no"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100"
                                                            style="outline: none;" readonly value="{{ $purchase->po_ref ?? '000000' }}">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">PO DATE :</td>
                                                    <td class="compact-td"><input type="datetime-local" name="po_date"
                                                            value="{{ $purchase->po_date ? date('Y-m-d\TH:i', strtotime($purchase->po_date)) : date('Y-m-d\TH:i') }}"
                                                            class="compact-input shadow-sm text-secondary"></td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td class="compact-lbl compact-td">PROJECT :</td>
                                                    <td class="compact-td">
                                                        <select class="compact-select text-secondary shadow-sm">
                                                            <option>(N/A)</option>
                                                        </select>
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">JOB #</td>
                                                    <td class="compact-td"><input type="text"
                                                            class="compact-input shadow-sm"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Right Section (Vendor & Settings) -->
                                <div class="col-lg-4">
                                    <div class="bg-white border rounded p-3 shadow-sm h-100 d-flex flex-column">
                                        <div class="form-group mb-3">
                                            <label class="form-label" style="font-size: 11px;">VENDOR ACCOUNT</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-light border-end-0"><i
                                                        class="bi bi-building text-secondary"></i></span>
                                                <button type="button" class="btn btn-outline-primary border-start-0 text-start fw-bold shadow-sm w-100 d-flex justify-content-between align-items-center" 
                                                    id="vendor_modal_btn" data-toggle="modal" data-target="#vendorModal" style="padding: 0.25rem 0.75rem; font-size: 13px;">
                                                    <span id="selected_vendor_name">{{ $purchase->vendor->name ?? 'Select Vendor Account' }}</span>
                                                    <i class="bi bi-chevron-down ms-2 text-primary" style="font-size: 0.7rem;"></i>
                                                </button>
                                                <input type="hidden" name="vendor_id" id="vendor_id" value="{{ $purchase->vendor_id }}">
                                            </div>
                                        </div>

                                            <div class="mt-auto bg-light p-3 rounded border">
                                                <div class="d-flex flex-column gap-2">
                                                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input mt-0" type="checkbox" id="gst_invoice" name="is_gst_invoice" {{ $purchase->is_gst_invoice ? 'checked' : '' }} style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                        <label class="form-check-label fw-bold text-dark compact-lbl" for="gst_invoice">GST INVOICE</label>
                                                    </div>
                                                    <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                        <input class="form-check-input mt-0" type="checkbox" id="enable_hs_code" name="enable_hs_code" checked style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                        <label class="form-check-label fw-bold text-dark compact-lbl" for="enable_hs_code">ENABLE HS CODE</label>
                                                    </div>
                                                </div>
                                                <div class="text-muted mt-2" style="font-size: 11px; font-weight: 600;">
                                                    STATUS: <span class="text-primary">UN-POSTED</span>
                                                </div>
                                            </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MAIN TABLE AREA -->
                    <div class="erp-card mt-2">
                        <div class="erp-card-header d-flex justify-content-between align-items-center"
                            style="padding-bottom: 1rem; border-bottom: none;">
                            <h3 class="erp-card-title"><i class="bi bi-boxes text-primary"></i> Product Line Items</h3>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn-erp btn-erp-primary" id="btnAddRow"
                                    style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                    <i class="bi bi-plus-lg"></i> Add New Row
                                </button>
                            </div>
                        </div>

                        <div class="erp-card-body pt-0">
                            <!-- Table -->
                            <div class="erp-table-wrapper" style="overflow-x: auto;">
                                <table class="erp-table">
                                    <thead>
                                        <tr>
                                            <th style="width:120px">Item Code</th>
                                            <th style="width:120px" class="hs-code-col">HS Code</th>
                                            <th style="width:200px">Product</th>

                                            <th style="width:160px">Packing (UOM)</th>
                                            <th style="width:80px">Pkt Size</th>
                                            <th style="min-width:180px" class="text-end">Paid (Box / Loose)</th>
                                            <th style="min-width:100px" class="text-end">Free Pcs</th>
                                            <th style="min-width:110px" class="text-end">Rate/PC</th>
                                            <th style="min-width:180px" class="text-end">Disc</th>
                                            <th style="min-width:150px" class="text-end">Sub Total</th>
                                            @if(!$isPO)
                                            <th style="min-width:110px" class="text-end tax-col">GST %</th>
                                            <th style="min-width:120px" class="text-end tax-col">GST Amt</th>
                                            <th style="min-width:100px" class="text-end tax-col">IT %</th>
                                            <th style="min-width:100px" class="text-end tax-col">Adv %</th>
                                            <th style="min-width:150px" class="text-end tax-col">Net Total</th>
                                            <th style="width:130px">Mfg Date</th>
                                            <th style="width:130px">Expiry</th>
                                            <th style="width:100px">Lot#</th>
                                            @else
                                            <th style="min-width:150px" class="text-end">Total</th>
                                            @endif
                                            <th style="width:50px; text-align:center;">Del</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseItems">
                                        @foreach($purchase->items as $item)
                                          <tr class="item-row">
                                            <td>
                                                <input type="hidden" name="product_id[]" class="item-id" value="{{ $item->product_id }}">
                                                <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly value="{{ $item->product->item_code ?? '' }}">
                                            </td>
                                            <td class="hs-code-col">
                                                <input type="text" name="hs_code[]" class="form-control bg-transparent border-0 px-0 item-hs-code" readonly value="{{ $item->hs_code }}">
                                            </td>
                                            <td style="font-weight:600; color:#334155;">
                                                <input type="hidden" name="item_name[]" class="item-name" value="{{ $item->product->item_name ?? '' }}">
                                                <input type="hidden" name="size_mode[]" class="size-mode" value="{{ $item->product->size_mode ?? 'by_pieces' }}">
                                                <button type="button" class="product-select-btn has-value"
                                                    title="{{ $item->product->item_name ?? '' }} ({{ $item->product->item_code ?? '' }})">
                                                    {{ $item->product->item_name ?? 'Select Product' }}<br>
                                                    <small class="text-muted" style="font-size:0.7rem;font-weight:400;">{{ $item->product->item_code ?? '' }}</small>
                                                    <span class="psm-btn-arrow">&#9660;</span>
                                                </button>
                                            </td>
                                            <input type="hidden" name="item_warehouse_id[]" class="item-warehouse" value="{{ $item->warehouse_id ?? $purchase->warehouse_id }}">
                                            <td>
                                                <select name="uom_id[]" class="form-select item-uom-select row-input" style="min-width:120px;">
                                                    <option value="">-- Base --</option>
                                                    @if($item->product->packings)
                                                        @foreach($item->product->packings as $uom)
                                                            <option value="{{ $uom->id }}" {{ $item->uom_id == $uom->id ? 'selected' : '' }} data-factor="{{ (float)$uom->pieces_per_box }}">
                                                                {{ $uom->name }} ({{ (float)$uom->pieces_per_box }})
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <input type="hidden" name="uom_name[]" class="uom-name-hidden" value="{{ $item->uom->name ?? 'Piece' }}">
                                                <input type="hidden" name="is_new_uom[]" class="is-new-uom" value="0">
                                            </td>
                                            <td>
                                                <input type="number" name="item_uom_factor[]" class="form-control text-center item-uom row-input" value="{{ (float)$item->pieces_per_box }}" readonly>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <input type="number" step="any" name="qty[]" class="form-control quantity text-end fw-bold row-input" style="background:#eff6ff;" value="{{ (float)$item->qty }}" min="0" title="Cartons/Kits">
                                                    <input type="number" step="any" name="loose_qty[]" class="form-control loose_qty text-end row-input" style="background:#f0f9ff; border-color:#bae6fd;" value="{{ (float)$item->loose_qty }}" min="0" title="Loose Pieces">
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" step="any" name="free_qty_pieces[]" class="form-control free_qty_pieces text-end row-input" value="{{ (float)$item->free_qty_pieces }}" min="0" title="Free Total Pieces">
                                            </td>
                                            <td><input type="number" step="any" name="price[]" class="form-control price text-end row-input" value="{{ (float)$item->price }}" ></td>
                                            <td>
                                                <div class="input-group input-group-sm shadow-sm" style="width: 100%; min-width: 160px;">
                                                    <input type="number" step="any" name="item_discount[]" class="form-control item_discount text-end row-input" value="{{ (float)$item->discount }}">
                                                    <button type="button" class="btn btn-sm btn-outline-primary disc-type-toggle p-1" data-type="{{ $item->discount_type ?? 'amount' }}" style="width: 32px; font-size: 0.7rem; font-weight: 700;">{{ ($item->discount_type ?? 'amount') == 'percent' ? '%' : 'Rs' }}</button>
                                                    <input type="hidden" name="item_discount_type[]" class="item_discount_type" value="{{ $item->discount_type ?? 'amount' }}">
                                                </div>
                                            </td>
                                            <td><input type="text" name="sub_total[]" class="form-control row-sub-total text-end" readonly value="{{ (float)$item->subtotal }}"></td>
                                            @if(!$isPO)
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" name="gst_percent[]" class="form-control gst text-end row-input" value="{{ (float)$item->gst_percent }}">
                                                    <span class="input-group-text p-1">%</span>
                                                </div>
                                            </td>
                                            <td><input type="text" class="form-control gst-amount-display text-end" value="{{ (float)$item->gst_amount }}" readonly tabindex="-1" style="background:#f8fafc;">
                                                <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="{{ (float)$item->gst_amount }}">
                                            </td>
                                            <td><input type="number" step="any" name="it_percent[]" class="form-control inc-tax text-end row-input" value="{{ (float)$item->it_percent }}"></td>
                                            <td><input type="number" step="any" name="adv_tax_percent[]" class="form-control adv-tax text-end row-input" value="{{ (float)$item->adv_tax_percent }}"></td>
                                            <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly value="{{ (float)$item->total_amount }}"></td>
                                            <td><input type="date" name="mfg_date[]" class="form-control text-secondary row-input" value="{{ $item->mfg_date ? date('Y-m-d', strtotime($item->mfg_date)) : '' }}"></td>
                                            <td><input type="date" name="expiry[]" class="form-control text-secondary row-input" value="{{ $item->exp_date ? date('Y-m-d', strtotime($item->exp_date)) : '' }}"></td>
                                            <td><input type="text" name="lot_no[]" class="form-control text-center row-input" value="{{ $item->batch_no ?? '' }}"></td>
                                            @else
                                            <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly value="{{ (float)$item->subtotal }}"></td>
                                            <input type="hidden" name="gst_percent[]" value="0">
                                            <input type="hidden" name="gst_amount[]" value="0">
                                            <input type="hidden" name="it_percent[]" value="0">
                                            <input type="hidden" name="adv_tax_percent[]" value="0">
                                            <input type="hidden" name="mfg_date[]" value="">
                                            <input type="hidden" name="expiry[]" value="">
                                            <input type="hidden" name="lot_no[]" value="">
                                            @endif
                                            <td style="text-align:center;">
                                                <button type="button" class="btn-erp-danger-ghost remove-row" title="Remove Item"><i class="bi bi-x-circle-fill"></i></button>
                                            </td>
                                          </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <!-- Empty State Background -->
                                <div id="emptyTableState" class="text-center py-5 text-muted"
                                    style="background: #f8fafc; border-top: 1px dashed #cbd5e1;">
                                    <i class="bi bi-cart-x fs-1 mb-2"></i>
                                    <p class="mb-0">No items added yet. Search products above to begin.</p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- BOTTOM AREA -->
                    <div class="row">
                        <!-- Setup & Payments -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="erp-card mb-4">
                                <div class="erp-card-header">
                                    <h3 class="erp-card-title"><i class="bi bi-wallet2 text-primary"></i> Setup & Payments
                                    </h3>
                                </div>
                                <div class="erp-card-body">
                                    <div class="row">
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Received By (Transport/Person)</label>
                                            <input type="text" name="transport_name" class="form-control"
                                                placeholder="Name or vehicle details">
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="form-label">Purchase Remarks</label>
                                            <input type="text" name="note" class="form-control"
                                                placeholder="Any internal notes" value="{{ $purchase->note }}">
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="printPreview"
                                                    name="print_preview" value="1" checked>
                                                <label class="form-check-label fw-bold text-dark" for="printPreview">
                                                    Auto Print Receipt
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    @if(!$isPO)
                                    <hr class="my-4" style="border-color: #e2e8f0; border-style: dashed;">

                                    <h5 class="fw-bold mb-3" style="font-size:0.95rem; color:#1e293b;">Payment Voucher
                                        Assignments</h5>
                                    <div id="paymentWrapper">
                                        @php $hasPayments = $purchase->payments->count() > 0; @endphp
                                        @if($hasPayments)
                                            @foreach($purchase->payments as $payment)
                                                <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                                                    <div class="input-group" style="max-width:350px;">
                                                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-bank"></i></span>
                                                        <select class="form-select rv-account" name="payment_account_id[]">
                                                            <option value="" disabled>Select Payment Account</option>
                                                            @foreach ($accounts as $acc)
                                                                <option value="{{ $acc->id }}" {{ $payment->account_id == $acc->id ? 'selected' : '' }}>{{ $acc->title }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="input-group" style="max-width:180px;">
                                                        <span class="input-group-text bg-light fw-bold">Rs.</span>
                                                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" value="{{ (float)$payment->amount }}">
                                                    </div>
                                                    @if($loop->first)
                                                        <button type="button" class="btn btn-erp-primary rounded px-3 shadow-sm" id="btnAddPayment" style="padding: 0.5rem 0.75rem;"><i class="bi bi-plus-lg"></i> Add</button>
                                                    @else
                                                        <button type="button" class="btn btn-erp-danger-ghost border-0 remove-payment" style="padding: 0.5rem 0.75rem;"><i class="bi bi-trash fs-5"></i></button>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                                                <div class="input-group" style="max-width:350px;">
                                                    <span class="input-group-text bg-light text-secondary"><i class="bi bi-bank"></i></span>
                                                    <select class="form-select rv-account" name="payment_account_id[]">
                                                        <option value="" selected disabled>Select Payment Account</option>
                                                        @foreach ($accounts as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="input-group" style="max-width:180px;">
                                                    <span class="input-group-text bg-light fw-bold">Rs.</span>
                                                    <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="0.00">
                                                </div>
                                                <button type="button" class="btn btn-erp-primary rounded px-3 shadow-sm" id="btnAddPayment" style="padding: 0.5rem 0.75rem;"><i class="bi bi-plus-lg"></i> Add</button>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="mt-3 p-3 bg-light rounded-3 d-inline-block border">
                                        <span class="text-muted fw-bold me-3">Total Applied Payment:</span>
                                        <span class="fw-bold text-success fs-5" id="totalPaid">Rs. 0.00</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Financial Summary -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="erp-card sticky-top" style="top: 20px;">
                                <div class="erp-card-header bg-primary text-white"
                                    style="border-radius: 12px 12px 0 0; background: linear-gradient(135deg, #1e293b, #0f172a);">
                                    <h3 class="erp-card-title text-white"><i class="bi bi-calculator text-info"></i>
                                        Financial Summary</h3>
                                </div>
                                <div class="erp-card-body" style="background: #f8fafc; border-radius: 0 0 12px 12px;">

                                    <div class="summary-row">
                                        <span class="summary-label">Paid Qty</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-primary"
                                            id="summary_total_qty" readonly tabindex="-1" value="0 Boxes">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Free Qty</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-info"
                                            id="summary_free_qty" readonly tabindex="-1" value="0 Boxes">
                                    </div>
                                    <div class="summary-row" style="background: #f1f5f9; border-top: 2px solid #e2e8f0; margin-top: 5px;">
                                        <span class="summary-label fw-bold">Grand Total Qty</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-dark fw-bold"
                                            id="summary_grand_qty" readonly tabindex="-1" value="0 Boxes">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Gross Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value"
                                            id="gross_total" readonly tabindex="-1" value="0.00">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label text-danger">Product Discounts</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_row_disc" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row align-items-center py-2">
                                        <span class="summary-label">Bill Discount</span>
                                        <div class="input-group shadow-sm w-50">
                                            <input type="number" step="any"
                                                class="form-control form-control-sm text-end input_summary"
                                                id="sum_discount" name="discount" value="0.00" placeholder="0.00">
                                            <button type="button" class="btn btn-sm btn-outline-primary disc-type-toggle-bill" 
                                                id="bill_disc_type_btn" data-type="amount" style="font-size: 0.75rem; font-weight: 700; width: 45px;">
                                                Rs
                                            </button>
                                            <input type="hidden" name="bill_discount_type" id="bill_discount_type" value="amount">
                                        </div>
                                    </div>

                                    <div class="summary-row">
                                        <span class="summary-label">Sub Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value"
                                            name="subtotal" id="summary_sub_total" readonly tabindex="-1"
                                            value="0.00">
                                    </div>

                                    <div class="summary-row py-2">
                                        <span class="summary-label">Freight Charges</span>
                                        <input type="number"
                                            class="form-control form-control-sm text-end w-50 input_summary"
                                            name="freight_charges" id="sum_freight" value="0.00">
                                    </div>

                                    <div class="summary-row py-2">
                                        <div class="d-flex flex-column text-start">
                                            <span class="summary-label">Bilti Expenses</span>
                                            <small class="text-muted" style="font-size: 0.65rem;">(Added to cost)</small>
                                        </div>
                                        <input type="number"
                                            class="form-control form-control-sm text-end w-50 input_summary"
                                            name="extra_cost" id="sum_expense" value="0.00">
                                    </div>

                                    @if(!$isPO)
                                    <div class="summary-row text-success mt-1 tax-summary-row">
                                        <span class="summary-label text-success fw-bold">Total GST <small>(Added ➕)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-success"
                                            id="total_gst" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row tax-summary-row" style="border-top:1px dashed #e2e8f0; padding-top:6px; margin-top:4px;">
                                        <span class="summary-label fw-bold text-dark">Invoice Total</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 fw-bold text-dark"
                                            id="invoice_total" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row text-danger tax-summary-row">
                                        <span class="summary-label text-danger">Income Tax (WHT) <small>(Deducted ➖)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_it" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <div class="summary-row text-danger tax-summary-row">
                                        <span class="summary-label text-danger">Advance Tax <small>(Deducted ➖)</small></span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_adv" readonly tabindex="-1" value="0.00">
                                    </div>
                                    @endif

                                    <!-- FINAL NET TOTAL -->
                                    <div
                                        class="summary-total-row d-flex justify-content-between align-items-center mt-3 shadow-sm">
                                        <div class="summary-total-label">Net Payable</div>
                                        <div class="text-end">
                                            <div class="text-muted small fw-bold">PKR</div>
                                            <input type="text"
                                                class="form-control bg-transparent border-0 p-0 text-end summary-total-value input-highlight"
                                                id="final_net_total" name="net_amount" readonly tabindex="-1"
                                                value="0.00">
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-grid gap-2 mt-4 mt-xl-5">
                                        <input type="hidden" name="action" value="{{ $isPO ? 'save_only' : 'post' }}">
                                        @if($isPO)
                                        <button type="button"
                                            class="btn-erp btn-erp-primary justify-content-center shadow-lg pt-3 pb-3"
                                            id="btnSaveOnly" style="font-size: 1.1rem;">
                                            <i class="bi bi-save-fill"></i> UPDATE PURCHASE ORDER
                                        </button>
                                        @else
                                        <button type="button"
                                            class="btn-erp btn-erp-success justify-content-center shadow-lg pt-3 pb-3"
                                            id="btnConfirm" style="font-size: 1.1rem;">
                                            <i class="bi bi-check-circle-fill"></i> CONFIRM & POST GRN
                                        </button>
                                        @endif
                                    </div>
                                    <div class="mt-3 text-center">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="printPreview" checked>
                                            <label class="form-check-label text-muted small" for="printPreview">Auto-print
                                                receipt</label>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
    <!-- Vendor Selection Modal -->
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="vendorModalLabel">
                        <i class="bi bi-people text-primary fs-4"></i> Vendor Directory
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row mb-3">
                        <div class="col-md-7">
                            <div class="search-wrapper">
                                <i class="bi bi-search search-icon text-muted"></i>
                                <input type="text" id="searchVendors" class="form-control form-control-sm search-input"
                                    placeholder="Search by name, code, or business...">
                            </div>
                        </div>
                    </div>
                    <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="erp-table table-hover align-middle mb-0" id="vendorTable">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Title / Name</th>
                                    <th>Business Name</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="vendorTableBody">
                                @foreach($Vendor as $v)
                                <tr class="vendor-row" data-id="{{ $v->id }}" 
                                    data-name="{{ $v->vendor_code ?? '' }} - {{ $v->title ?? $v->name }} {{ $v->business_name ? '(' . $v->business_name . ')' : '' }}" 
                                    data-search="{{ strtolower(($v->vendor_code ?? '') . ' ' . ($v->title ?? $v->name) . ' ' . ($v->business_name ?? '')) }}">
                                    <td><span class="badge bg-light text-dark border">{{ $v->vendor_code ?? 'N/A' }}</span></td>
                                    <td class="fw-bold">{{ $v->title ?? $v->name }}</td>
                                    <td>{{ $v->business_name ?? '---' }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-primary select-vendor-btn px-3">Select</button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booked Products Modal -->
    <div class="modal fade" id="bookedProductsModal" tabindex="-1" aria-labelledby="bookedProductsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-light border-bottom border-light px-4 py-3">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2"
                        id="bookedProductsModalLabel">
                        <i class="bi bi-card-checklist text-primary fs-4"></i>
                        Purchase Orders
                    </h5>
                    <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <div class="search-wrapper">
                                <i class="bi bi-search search-icon text-muted"></i>
                                <input type="text" id="searchBookedProducts"
                                    class="form-control form-control-sm search-input"
                                    placeholder="Search by vendor, component, code, sys ID...">
                            </div>
                        </div>
                    </div>

                    <div class="erp-table-wrapper" style="max-height: 400px; overflow-y: auto;">
                        <table class="erp-table table-hover align-middle mb-0" id="bookedProductsTable">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th style="width: 5%;"></th>
                                    <th>SYS ID / PO #</th>
                                    <th>Date</th>
                                    <th>Vendor</th>
                                    <th class="text-end">Total Items</th>
                                    <th class="text-end">Total Qty</th>
                                    <th class="text-end">Free Pieces</th>
                                    <th class="text-end">Net Amount</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="bookedProductsTableBody">
                                @php
                                    $draftPurchases = $Purchase->where('status_purchase', 'draft');
                                    $hasDraftItems = false;
                                @endphp
                                @foreach ($draftPurchases as $draft)
                                    @php $hasDraftItems = true; @endphp
                                    <tr class="booked-item-row" data-vendor-id="{{ $draft->vendor_id }}"
                                        data-search="{{ strtolower($draft->invoice_no . ' ' . ($draft->vendor->name ?? '')) }}">
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-import-single"
                                                title="Import Purchase" data-vendor-id="{{ $draft->vendor_id }}"
                                                data-vendor-name="{{ $draft->vendor->name ?? 'WALKING VENDOR' }}"
                                                data-draft-id="{{ $draft->id }}"
                                                data-warehouse-id="{{ $draft->warehouse_id }}"
                                                data-purchase-date="{{ $draft->purchase_date ? substr($draft->purchase_date, 0, 10) : date('Y-m-d') }}"
                                                data-po-date="{{ $draft->created_at ? \Carbon\Carbon::parse($draft->created_at)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i') }}"
                                                data-vendor-bill="{{ $draft->vendor_bill_no }}"
                                                data-invoice-no="{{ $draft->invoice_no }}"
                                                data-grn-no="{{ $draft->grn_no }}" data-note="{{ $draft->note }}"
                                                data-items="{{ json_encode(
                                                    $draft->items->map(function ($i) use ($draft) {
                                                        return [
                                                            'product_id' => $i->product_id,
                                                            'product_name' => $i->product->item_name ?? '',
                                                            'item_code' => $i->product->item_code ?? '',
                                                            'unit' => $i->unit,
                                                            'qty' => $i->qty,
                                                            'loose_qty' => $i->loose_qty ?? 0,
                                                            'price' => $i->price,
                                                            'discount' => $i->item_discount,
                                                            'discount_type' => $i->item_discount_type ?? 'amount',
                                                            'mode' => $i->size_mode,
                                                            'uom_id' => $i->uom_id,
                                                            'warehouse_id' => $i->warehouse_id ?? $draft->warehouse_id,
                                                            'ppb' => $i->uom_factor ?? 1,
                                                            'm2' => $i->pieces_per_m2,
                                                            'exp_date' => $i->exp_date,
                                                            'mfg_date' => $i->mfg_date,
                                                            'batch_no' => $i->batch_no,
                                                            'free_qty' => $i->free_qty ?? 0,
                                                            'free_qty_pieces' => $i->free_qty_pieces ?? 0,
                                                            'gst' => $i->gst_percent ?? 0,
                                                        ];
                                                    }),
                                                ) }}">
                                                <i class="bi bi-arrow-down-square"></i> Import
                                            </button>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $draft->invoice_no }}</span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($draft->purchase_date)->format('d M, Y') }}</td>
                                        <td>{{ $draft->vendor->name ?? 'WALKING VENDOR' }}</td>
                                        <td class="text-end fw-bold">{{ $draft->items->count() }}</td>
                                        <td class="text-end fw-bold text-primary">{{ (float) $draft->items->sum('total_pieces') }} Pieces</td>
                                        <td class="text-end fw-bold text-info">{{ (float) $draft->items->sum('free_qty_pieces') }} Pieces</td>
                                        <td class="text-end text-success fw-bold">Rs.
                                            {{ number_format($draft->net_amount, 2) }}</td>
                                        <td class="text-end">
                                            <!-- imported via row button -->
                                        </td>
                                    </tr>
                                @endforeach

                                @if (!$hasDraftItems)
                                    <tr id="emptyBookedRow">
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                            No booked/draft products found.
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top border-light px-4 py-3">
                    <button type="button" class="btn-erp btn-erp-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ERP Product Modal (shared component) --}}
    @include('admin_panel.components.product_select_modal')

@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Custom Select2 Overrides for Modern UI */
    .select2-container--default .select2-selection--single {
        border: 1px solid transparent;
        border-bottom: 1px solid #e2e8f0;
        border-radius: 4px;
        height: 38px;
        display: flex;
        align-items: center;
        background-color: transparent;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-bottom: 1px solid #3b82f6;
        box-shadow: inset 0 -1px 0 0 #3b82f6;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #334155;
        font-weight: 600;
    }
</style>

<script>
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        function toggleGstFields() {
            let isGst = $('#gst_invoice').is(':checked');
            if (isGst) {
                $('.gst, .inc-tax, .adv-tax').prop('readonly', false).css('background', '');
                $('.tax-col, .tax-summary-row').show();
                $('.item-row').each(function() {
                    $(this).find('.gst, .gst-amount-display, .inc-tax, .adv-tax').closest('td').show();
                    $(this).find('.row-net-total').closest('td').show();
                });
            } else {
                $('.gst, .inc-tax, .adv-tax').val(0).prop('readonly', true).css('background', '#e9ecef');
                $('.tax-col, .tax-summary-row').hide();
                $('.item-row').each(function() {
                    $(this).find('.gst, .gst-amount-display, .inc-tax, .adv-tax').closest('td').hide();
                    $(this).find('.row-net-total').closest('td').hide();
                });
            }
            // Trigger recalculation on all rows
            $('#purchaseItems tr').each(function() {
                let $row = $(this);
                if (typeof recalcRow === "function" && ($row.find('.item-id').val() || $row.find('.product-select2').val())) {
                    recalcRow($row);
                }
            });
            if (typeof recalcSummary === "function") recalcSummary();
        }

        $('#gst_invoice').on('change', function() {
            toggleGstFields();
        });
        
        setTimeout(toggleGstFields, 500);

        // Vendor Modal Logic (Moved higher for reliability)
        $('#searchVendors').on('input', function() {
            let val = $(this).val().toLowerCase();
            $('#vendorTableBody tr').each(function() {
                let text = $(this).attr('data-search') || '';
                $(this).toggle(text.includes(val));
            });
        });

        $('#vendorTableBody').on('click', '.select-vendor-btn', function() {
            let $row = $(this).closest('tr');
            let id = $row.attr('data-id');
            let name = $row.attr('data-name');

            $('#vendor_id').val(id);
            // Update both span and button text if necessary
            $('#selected_vendor_name').text(name);
            $('#vendorModal').modal('hide');
            
            // Enable Import PO button
            $('#btn_import_po').prop('disabled', false).attr('title', 'Import products from PO');
        });


        // Payment Voucher Add/Remove Logic
        $('#btnAddPayment').click(function() {
            const html = `
                <div class="d-flex gap-2 align-items-center mb-2 payment-row">
                    <div class="input-group" style="max-width:350px;">
                        <span class="input-group-text bg-light text-secondary"><i class="bi bi-bank"></i></span>
                        <select class="form-select rv-account" name="payment_account_id[]">
                            <option value="" selected disabled>Select Payment Account</option>
                            @if (isset($accounts))
                                @foreach ($accounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->title }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="input-group" style="max-width:180px;">
                        <span class="input-group-text bg-light fw-bold">Rs.</span>
                        <input type="number" class="form-control text-end payment-amount" name="payment_amount[]" placeholder="0.00">
                    </div>
                    <button type="button" class="btn btn-erp-danger-ghost border-0 remove-payment" style="padding: 0.5rem 0.75rem;"><i class="bi bi-trash fs-5"></i></button>
                </div>`;
            $('#paymentWrapper').append(html);
        });

        $(document).on('click', '.remove-payment', function() {
            $(this).closest('.payment-row').remove();
            calcTotalPaid();
        });

        $(document).on('input', '.payment-amount', function() {
            calcTotalPaid();
        });

        function calcTotalPaid() {
            let total = 0;
            $('.payment-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#totalPaid').text('Rs. ' + total.toFixed(2));
        }

        // Prevent natural form submission (e.g. on Enter key)
        $('#purchaseForm').on('submit', function(e) {
            e.preventDefault();
        });

        // AJAX Form Submission
        $('#btnSaveOnly').click(function(e) {
            e.preventDefault();

            // Check for empty rows and mandatory fields (Expiry, MFG, Lot)
            let validationErrors = [];
            @if(!$isPO)
            $('#purchaseItems tr').each(function(index) {
                let pid = $(this).find('.item-id').val();
                if (pid) {
                    let mfg = $(this).find('input[name="mfg_date[]"]').val();
                    let exp = $(this).find('input[name="expiry[]"]').val();
                    let lot = $(this).find('input[name="lot_no[]"]').val();
                    let name = $(this).find('.item-name').val();

                    if (!mfg) validationErrors.push(
                        `Row ${index + 1} (${name}): Mfg Date is required.`);
                    if (!exp) validationErrors.push(
                        `Row ${index + 1} (${name}): Expiry Date is required.`);
                    if (!lot || lot.trim() === '') validationErrors.push(
                        `Row ${index + 1} (${name}): Lot Number is required.`);
                }
            });
            @endif

            if (validationErrors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Incomplete Item Data!',
                    html: `<ul class="text-start">${validationErrors.map(e => `<li>${e}</li>`).join('')}</ul>`,
                });
                return;
            }

            // Check for duplicate lots
            let duplicateLots = getDuplicateLots();
            if (duplicateLots.length > 0) {
                let dupeList = duplicateLots.map(d => `<li><strong>${d.name}</strong>: ${d.lot}</li>`)
                    .join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Lot Numbers!',
                    html: `<p>The following products have the same Lot Number in your list. Each Lot Number must be unique per product:</p><ul class="text-start">${dupeList}</ul>`,
                });
                return;
            }

            let $btn = $(this);
            let ogHtml = $btn.html();
            $btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2"></span>Saving...');
            $('#action').val('save_only');

            $.ajax({
                url: "{{ route('store.Purchase') }}",
                method: "POST",
                data: $('#purchaseForm').serialize(),
                success: function(response) {
                    if (response.invoice_url && response.print_preview && $('#action')
                        .val() !== 'save_only') {
                        window.open(response.invoice_url, '_blank');
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Draft Saved!',
                        text: 'Purchase saved as draft successfully.',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = "{{ $isPO ? route('purchase.order.index') : route('Purchase.home') }}";
                    });
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html(ogHtml);
                    let msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON
                        .message;
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        msg += '\n' + Object.values(xhr.responseJSON.errors).flat().join(
                            '\n');
                    }
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

        $('#btnConfirm').click(function(e) {
            e.preventDefault();

            // Check for empty rows and mandatory fields (Expiry, MFG, Lot)
            let validationErrors = [];
            $('#purchaseItems tr').each(function(index) {
                let pid = $(this).find('.item-id').val();
                if (pid) {
                    let mfg = $(this).find('input[name="mfg_date[]"]').val();
                    let exp = $(this).find('input[name="expiry[]"]').val();
                    let lot = $(this).find('input[name="lot_no[]"]').val();
                    let name = $(this).find('.item-name').val();

                    if (!mfg) validationErrors.push(
                        `Row ${index + 1} (${name}): Mfg Date is required.`);
                    if (!exp) validationErrors.push(
                        `Row ${index + 1} (${name}): Expiry Date is required.`);
                    if (!lot || lot.trim() === '') validationErrors.push(
                        `Row ${index + 1} (${name}): Lot Number is required.`);
                }
            });

            if (validationErrors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Incomplete Item Data!',
                    html: `<ul class="text-start">${validationErrors.map(e => `<li>${e}</li>`).join('')}</ul>`,
                });
                return;
            }

            // Check for duplicate lots
            let duplicateLots = getDuplicateLots();
            if (duplicateLots.length > 0) {
                let dupeList = duplicateLots.map(d => `<li><strong>${d.name}</strong>: ${d.lot}</li>`)
                    .join('');
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Lot Numbers!',
                    html: `<p>The following products have the same Lot Number in your list. Each Lot Number must be unique per product:</p><ul class="text-start">${dupeList}</ul>`,
                });
                return;
            }

            // Check for expired items first
            let expiredItems = getExpiredItems();
            let warningText =
                "This will officially update inventory stock and financial accounts. This action is irreversible.";
            let warningTitle = "Confirm & Post Purchase?";
            let warningIcon = "warning";
            let confirmBtnColor = '#059669';
            let confirmBtnText = 'Yes, Post it!';

            if (expiredItems.length > 0) {
                warningTitle = "Expired Products Detected!";
                warningIcon = "error";
                confirmBtnColor = '#dc3545';
                confirmBtnText = 'Yes, Purchase Expired Items';

                let listHtml =
                    '<div class="text-start mt-3" style="max-height: 200px; overflow-y: auto; border: 1px solid #fee2e2; border-radius: 8px; padding: 10px; background: #fff1f2;"><ul class="list-group list-group-flush" style="background: transparent;">';
                expiredItems.forEach(item => {
                    listHtml +=
                        `<li class="list-group-item small text-danger p-1" style="background: transparent;"><i class="bi bi-calendar-x me-2"></i> <strong>${item.name}</strong> <br> <span class="ms-4">Code: ${item.code} | Exp: ${item.date}</span></li>`;
                });
                listHtml += '</ul></div>';

                warningText =
                    `<p class="mb-2">Careful! You are attempting to purchase <strong>${expiredItems.length} product(s)</strong> that are already expired.</p>${listHtml}<p class="mt-3"><strong>Do you still want to proceed with this purchase?</strong></p>`;
            }

            Swal.fire({
                title: warningTitle,
                html: warningText,
                icon: warningIcon,
                showCancelButton: true,
                confirmButtonColor: confirmBtnColor,
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: confirmBtnText,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    let $btn = $('#btnConfirm');
                    let ogHtml = $btn.html();
                    $btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'
                    );
                    $('#action').val('approved');
                    $.ajax({
                        url: "{{ route('store.Purchase') }}",
                        method: "POST",
                        data: $('#purchaseForm').serialize(),
                        success: function(response) {
                            if (response.invoice_url && response.print_preview) {
                                window.open(response.invoice_url, '_blank');
                            }
                            Swal.fire({
                                icon: 'success',
                                title: 'Posted Successfully!',
                                text: 'Purchase processed and ledger updated.',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = response
                                    .redirect_url ||
                                    "{{ route('Purchase.home') }}";
                            });
                        },
                        error: function(xhr) {
                            $btn.prop('disabled', false).html(ogHtml);
                            let msg = 'Something went wrong.';
                            if (xhr.responseJSON && xhr.responseJSON.message) msg =
                                xhr.responseJSON.message;
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                msg += '\n' + Object.values(xhr.responseJSON.errors)
                                    .flat().join('\n');
                            }
                            Swal.fire('Error', msg, 'error');
                        }
                    });
                }
            });
        });

        // Product Table & Search Logic
        let rowCount = 0;

        function num(n) {
            return isNaN(parseFloat(n)) ? 0 : parseFloat(n);
        }

        function checkEmptyState() {
            if ($('#purchaseItems tr').length > 0) {
                $('#emptyTableState').hide();
            } else {
                $('#emptyTableState').show();
            }
        }

        function parseBoxPiece($boxesInput, $looseInput, pack, sizeMode) {
            let boxesVal = String($boxesInput.val() || '0');
            let piecesVal = String($looseInput.val() || '0');
            
            let total_pieces = 0;
            let boxes = 0;
            let pieces = 0;

            // Handle legacy decimal input in the BOXES field
            if (boxesVal.includes('.')) {
                let parts = boxesVal.split('.');
                boxes = parseInt(parts[0]) || 0;
                pieces = parseInt(parts[1]) || 0;

                // Handle overflow and legacy sync
                if (pieces >= pack && pack > 0) {
                    let extraBoxes = Math.floor(pieces / pack);
                    boxes += extraBoxes;
                    pieces = pieces % pack;
                }
                // Update fields to new standard
                $boxesInput.val(boxes);
                $looseInput.val(pieces);
            } else {
                boxes = parseInt(boxesVal) || 0;
                pieces = parseInt(piecesVal) || 0;
            }

            // Overflow normalization removed as per user request to allow loose pieces to exceed pack size
            
            total_pieces = (boxes * pack) + pieces;
            return {
                total_pieces,
                boxes,
                pieces
            };
        }

        function recalcRow($row) {
            const pack = parseFloat($row.find('.item-uom').val()) || 1;
            const sizeMode = $row.find('.size-mode').val() || 'by_cartons';

            // Paid Qty Calculation
            let paid = parseBoxPiece($row.find('.quantity'), $row.find('.loose_qty'), pack, sizeMode);
            // Free qty is directly in pieces as per user request
            let freePieces = num($row.find('.free_qty_pieces').val());
            let free = { total_pieces: freePieces, boxes: 0, pieces: freePieces };

            const price = num($row.find('.price').val()); 
            const disc_val = num($row.find('.item_discount').val()); 
            const disc_type = $row.find('.item_discount_type').val() || 'amount';

            // Calculations are based on TOTAL PIECES and RATE PER PIECE
            let line_gross = paid.total_pieces * price;
            // Per user request: Flat discount amount, not per piece
            let line_disc = (disc_type === 'percent') ? (line_gross * disc_val / 100) : disc_val;

            let subTotal = line_gross - line_disc;
            if (subTotal < 0) subTotal = 0;

            const gstPercent = num($row.find('.gst').val());
            const gstAmount = subTotal * (gstPercent / 100);

            const itPercent = num($row.find('.inc-tax').val());
            const itAmount = subTotal * (itPercent / 100);

            const advPercent = num($row.find('.adv-tax').val());
            const advAmount = subTotal * (advPercent / 100);

            // Line net: subtotal + GST - WHT - Adv
            let netTotal = subTotal + gstAmount - itAmount - advAmount;

            $row.find('.gst-amount-row').val(gstAmount.toFixed(2));
            $row.find('.gst-amount-display').val(gstAmount.toFixed(2));
            
            $row.find('.row-sub-total').val(subTotal.toFixed(2));
            $row.find('.row-net-total').val(netTotal.toFixed(2));
        }

        function recalcSummary() {
            let true_gross = 0;
            let total_row_disc = 0;
            let totalItAmt = 0;
            let totalAdvAmt = 0;
            let totalGstOnly = 0;
            let sum_paid_pieces = 0;
            let sum_free_pieces = 0;
            let sum_boxes = 0;
            let sum_loose = 0;
            let totalGstAmt = 0;

            $('#purchaseItems tr').each(function() {
                const $row = $(this);
                const pack = parseFloat($row.find('.item-uom').val()) || 1;
                const sizeMode = $row.find('.size-mode').val() || 'by_cartons';

                let paid = parseBoxPiece($row.find('.quantity'), $row.find('.loose_qty'), pack, sizeMode);
                let freePieces = num($row.find('.free_qty_pieces').val());
                let free = { total_pieces: freePieces, boxes: 0, pieces: freePieces };

                const price = num($row.find('.price').val());
                const disc_val = num($row.find('.item_discount').val());
                const disc_type = $row.find('.item_discount_type').val() || 'amount';

                let line_gross = paid.total_pieces * price;
                // Per user request: Flat discount amount, not per piece
                let line_disc = (disc_type === 'percent') ? (line_gross * disc_val / 100) : disc_val;

                true_gross += line_gross;
                total_row_disc += line_disc;

                sum_paid_pieces += paid.total_pieces;
                sum_free_pieces += free.total_pieces;
                
                sum_boxes += (paid.boxes || 0);
                sum_loose += (paid.pieces || 0);

                // Accumulate GST separately from WHT/Adv (correct sign tracking)
                const rowSubTotal = num($row.find('.row-sub-total').val());
                const rowGst = num($row.find('.gst-amount-row').val());
                totalGstOnly += rowGst;
                
                // WHT and Adv are deducted — track separately
                const rowIt  = rowSubTotal * (num($row.find('.inc-tax').val()) / 100);
                const rowAdv = rowSubTotal * (num($row.find('.adv-tax').val()) / 100);
                
                totalItAmt  += rowIt;
                totalAdvAmt += rowAdv;
                
                totalGstAmt += rowGst; // Only GST in gstAmt total
            });

            $('#gross_total').val(true_gross.toFixed(2));
            $('#total_row_disc').val(total_row_disc.toFixed(2));
            $('#total_gst').val(totalGstOnly.toFixed(2));
            $('#total_it').val(totalItAmt.toFixed(2));
            $('#total_adv').val(totalAdvAmt.toFixed(2));

            // Helper to format as "X Boxes, Y Pcs"
            const formatStock = (total, pack) => {
                let b = Math.floor(total / pack);
                let p = total % pack;
                return p > 0 ? `${b} B, ${p} P` : `${b} Boxes`;
            };

            // Since different products have different pack sizes, we show base total pieces if mixed,
            // or use a generic "Items" label. For medical, often pieces/units is preferred in summary.
            // Display totals in "Box / Pcs" format
            $('#summary_total_qty').val(sum_paid_pieces + ' Pcs');
            $('#summary_free_qty').val(sum_free_pieces + ' Pcs');
            $('#summary_grand_qty').val((sum_paid_pieces + sum_free_pieces) + ' Pcs');

            const sumDiscVal = num($('#sum_discount').val());
            const sumDiscType = $('#bill_discount_type').val() || 'amount';
            
            // Subtotal here is True Gross - Item Discounts
            let runningSub = true_gross - total_row_disc;

            let bill_disc_amount = (sumDiscType === 'percent') ? (runningSub * sumDiscVal / 100) : sumDiscVal;

            let summarySub = runningSub - bill_disc_amount;
            $('#summary_sub_total').val(summarySub.toFixed(2));

            const sumFreight = num($('#sum_freight').val());
            const sumExpense = num($('#sum_expense').val());

            // Pakistan Standard:
            // GST Base = summarySub + freight + expense
            // Invoice Total = GST Base + GST
            // Net Payable = Invoice Total - WHT - Adv
            const gstBase      = summarySub + sumFreight + sumExpense;
            const invoiceTotal = gstBase + totalGstOnly;
            const finalNet     = invoiceTotal - totalItAmt - totalAdvAmt;

            $('#invoice_total').val(invoiceTotal.toFixed(2));
            $('#final_net_total').val(finalNet.toFixed(2));
        }

        function getExpiredItems() {
            let expired = [];
            // Get today's date in local midnight format
            let today = new Date();
            today.setHours(0, 0, 0, 0);

            $('#purchaseItems tr').each(function() {
                let expDateVal = $(this).find('input[name="expiry[]"]').val();
                if (expDateVal) {
                    let expDate = new Date(expDateVal);
                    expDate.setHours(0, 0, 0, 0);

                    if (expDate <= today) {
                        let name = $(this).find('.item-name').val();
                        let code = $(this).find('.item-code').val();
                        expired.push({
                            name: name,
                            code: code,
                            date: expDateVal
                        });
                    }
                }
            });
            return expired;
        }

        function getDuplicateLots() {
            let lots = {};
            let duplicates = [];
            $('#purchaseItems tr').each(function() {
                let pid = $(this).find('.item-id').val();
                let lot = $(this).find('input[name="lot_no[]"]').val();
                let name = $(this).find('.item-name').val();
                if (pid && lot && lot.trim() !== '') {
                    lot = lot.trim().toLowerCase();
                    let key = pid + '_' + lot;
                    if (lots[key]) {
                        duplicates.push({
                            name: name,
                            lot: lot
                        });
                    } else {
                        lots[key] = true;
                    }
                }
            });
            return duplicates;
        }

        function addBlankRow() {
            rowCount++;
            // Pre-collect warehouses from server-side variable
            let warehouseOptions = `<option value="">-- Select --</option>`;
            @foreach($warehouses as $wh)
                warehouseOptions += `<option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>`;
            @endforeach
            const html = `
            <tr class="item-row">
                <td>
                    <input type="hidden" name="product_id[]" class="item-id" value="">
                    <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly value="">
                </td>
                <td class="hs-code-col">
                    <input type="text" name="hs_code[]" class="form-control bg-transparent border-0 px-0 item-hs-code" readonly value="">
                </td>
                <td style="font-weight:600; color:#334155;">
                    <input type="hidden" name="item_name[]" class="item-name" value="">
                    <input type="hidden" name="size_mode[]" class="size-mode" value="by_pieces">
                    <button type="button" class="product-select-btn">
                        Select Product <span class="psm-btn-arrow">&#9660;</span>
                    </button>
                    <input type="hidden" name="item_warehouse_id[]" class="item-warehouse" value="${$('#main_warehouse_id').val() || ''}">
                </td>
                <td>
                    <select name="uom_id[]" class="form-control item-uom-select p-1">
                        <option value="">-- Base --</option>
                    </select>
                    <input type="hidden" name="uom_name[]" class="uom-name-hidden" value="Piece">
                    <input type="hidden" name="is_new_uom[]" class="is-new-uom" value="0">
                </td>
                <td>
                    <input type="number" name="item_uom_factor[]" class="form-control text-center item-uom row-input" value="1" readonly>
                </td>
                <td>
                    <div class="d-flex gap-1">
                        <input type="number" step="any" name="qty[]" class="form-control quantity text-end fw-bold row-input" style="background:#eff6ff;" value="1" min="0" title="Cartons/Kits">
                        <input type="number" step="any" name="loose_qty[]" class="form-control loose_qty text-end row-input" style="background:#f0f9ff; border-color:#bae6fd;" value="0" min="0" title="Loose Pieces">
                    </div>
                </td>
                <td>
                    <input type="number" step="any" name="free_qty_pieces[]" class="form-control free_qty_pieces text-end row-input" value="0" min="0" title="Free Total Pieces">
                </td>
                <td><input type="number" step="any" name="price[]" class="form-control price text-end row-input" value="0" ></td>
                <td>
                    <div class="input-group input-group-sm shadow-sm" style="width: 100%; min-width: 160px;">
                        <input type="number" step="any" name="item_discount[]" class="form-control item_discount text-end row-input" value="0">
                        <button type="button" class="btn btn-sm btn-outline-primary disc-type-toggle p-1" data-type="amount" style="width: 32px; font-size: 0.7rem; font-weight: 700;">Rs</button>
                        <input type="hidden" name="item_discount_type[]" class="item_discount_type" value="amount">
                    </div>
                </td>
                <td><input type="text" name="sub_total[]" class="form-control row-sub-total text-end" readonly></td>
                @if(!$isPO)
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" step="any" name="gst_percent[]" class="form-control gst text-end row-input" value="0">
                        <span class="input-group-text p-1">%</span>
                    </div>
                </td>
                <td><input type="text" class="form-control gst-amount-display text-end" value="0.00" readonly tabindex="-1" style="background:#f8fafc;">
                    <input type="hidden" name="gst_amount[]" class="gst-amount-row" value="0">
                </td>
                <td><input type="number" step="any" name="it_percent[]" class="form-control inc-tax text-end row-input" value="0"></td>
                <td><input type="number" step="any" name="adv_tax_percent[]" class="form-control adv-tax text-end row-input" value="0"></td>
                <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly></td>
                <td><input type="date" name="mfg_date[]" class="form-control text-secondary row-input"></td>
                <td><input type="date" name="expiry[]" class="form-control text-secondary row-input"></td>
                <td><input type="text" name="lot_no[]" class="form-control text-center row-input"></td>
                @else
                <td><input type="text" name="total[]" class="form-control row-net-total input-highlight text-end" readonly></td>
                <input type="hidden" name="gst_percent[]" value="0">
                <input type="hidden" name="gst_amount[]" value="0">
                <input type="hidden" name="it_percent[]" value="0">
                <input type="hidden" name="adv_tax_percent[]" value="0">
                @endif
                <td style="text-align:center;">
                    <button type="button" class="btn-erp-danger-ghost remove-row" title="Remove Item"><i class="bi bi-x-circle-fill"></i></button>
                </td>
            </tr>`;
            $('#purchaseItems').append(html);
            const $row = $('#purchaseItems tr:last');
            recalcRow($row);
            return $row;
        }

        /* ── Product Select Button → ERP Modal ── */
        $(document).on('click', '.product-select-btn', function() {
            var $btn = $(this);
            var $row = $btn.closest('tr');

            var currentId = $row.find('.item-id').val();
            var allIds = [];
            $('#purchaseItems tr').each(function() {
                var id = $(this).find('.item-id').val();
                if (id) allIds.push(parseInt(id));
            });

            ERPProductModal.open({
                priceField: 'purchase',
                targetRow: $row,
                selectedIds: currentId ? [parseInt(currentId)] : [],
                existingIds: allIds,
                onSelect: function(products) {
                    products.forEach(function(p, idx) {
                        var $targetRow = (idx === 0) ? $row : addBlankRow();
                        // Map modal result to populateProductRow format
                        populateProductRow($targetRow, {
                            id: p.id,
                            item_name: p.item_name,
                            item_code: p.item_code,
                            hs_code: p.hs_code || '',
                            uom: p.pieces_per_box || 1,
                            size_mode: p.size_mode || 'by_cartons',
                            price: p.purchase_price_per_piece || 0,
                            packings: p.packings || []
                        }, false);
                        // Update button label
                        $targetRow.find('.product-select-btn')
                            .addClass('has-value')
                            .html(
                                (p.item_name || '') +
                                '<br><small class="text-muted" style="font-size:0.7rem;font-weight:400;">' +
                                (p.item_code || '') +
                                '</small><span class="psm-btn-arrow">&#9660;</span>'
                            );
                    });
                    checkEmptyState();
                }
            });
        });

        // Header Warehouse Sync
        $('#main_warehouse_id').on('change', function() {
            let whId = $(this).val();
            if (whId) {
                $('.item-warehouse').val(whId);
            }
        });

        function populateProductRow($row, data, isImport = false) {
            $row.find('.item-code').val(data.item_code);
            $row.find('.item-hs-code').val(data.hs_code || '');
            $row.find('.item-id').val(data.id);
            $row.find('.item-name').val(data.item_name);
            $row.find('.size-mode').val(data.size_mode);

            // Populate Packing Dropdown
            var $uomSelect = $row.find('.item-uom-select');
            $uomSelect.html('<option value="" data-price="'+data.price+'" data-factor="'+data.uom+'">-- Base --</option>');
            if (data.packings && data.packings.length > 0) {
                data.packings.forEach(function(pkg) {
                    var ppb = pkg.pieces_per_box > 0 ? pkg.pieces_per_box : 1;
                    var piecePrice = pkg.purchase_price;
                    $uomSelect.append('<option value="'+pkg.id+'" data-price="'+piecePrice+'" data-factor="'+ppb+'">'+pkg.name+'</option>');
                });
            }
            $uomSelect.append('<option value="new_uom" style="background:#f0f0f0; font-weight:bold; color:#2563eb;">+ Add New Packing</option>');

            // Set product default price immediately
            $row.find('.price').val(data.price);

            if (data.id && (!isImport || (!data.mfg_date && !data.exp_date && !data.batch_no))) {
                // Fetch last confirmed purchase price
                $.getJSON("{{ url('purchase/product') }}/" + data.id + "/last-price", function(res) {
                    if (res && res.price && res.price > 0 && !isImport) {
                        $row.find('.price').val(res.price);
                        recalcRow($row);
                        recalcSummary();
                    }
                }).fail(function(err) {
                    console.error('Failed to fetch last purchase price:', err);
                });
            }

            // Ensure batch info stays empty for manual entry
            $row.find('input[name="mfg_date[]"]').val('');
            $row.find('input[name="expiry[]"]').val('');
            $row.find('input[name="lot_no[]"]').val('');

            recalcRow($row);
            recalcSummary();
            setTimeout(() => $row.find('.quantity').focus().select(), 100);
        }

        // Generate Blank Row only if empty
        if ($('#purchaseItems tr.item-row').length === 0) {
            addBlankRow();
        }

        // Add row via button
        $('#btnAddRow').click(function() {
            addBlankRow();
        });

        // Add row via Enter Key on any row-input
        $('#purchaseItems').on('keydown', '.row-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Check if this is the last row in the table
                const isLastRow = $(this).closest('tr').is(':last-child');
                if (isLastRow) {
                    addBlankRow();
                    setTimeout(() => {
                        $('#purchaseItems tr:last').find('.product-select-btn').focus();
                    }, 100);
                } else {
                    // Just move to the next row's quantity or select2
                    $(this).closest('tr').next().find('.quantity').focus().select();
                }
            }
        });

        // Calculations & Removals
        $('#purchaseItems').on('input', '.quantity, .loose_qty, .free_qty, .free_qty_pieces, .price, .item_discount, .gst, .inc-tax, .adv-tax',
            function() {
                let $row = $(this).closest('tr');
                recalcRow($row, false);
                recalcSummary();
            });

        $('#purchaseItems').on('change', 'input[name="lot_no[]"]', function() {
            let $row = $(this).closest('tr');
            let pid = $row.find('.item-id').val();
            let lot = $(this).val();

            if (pid && lot && lot.trim() !== '') {
                $.get("{{ route('purchase.check_lot') }}", {
                    product_id: pid,
                    lot_no: lot
                }, function(res) {
                    if (res.exists) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Batch Number Already Exists',
                            text: `This batch/lot number (${lot}) was already purchased for this product in the past. Please verify if this is a repeat purchase or a typing error.`
                        });
                    }
                });
            }
        });

        // Packing Change -> Update Price
        $('#purchaseItems').on('change', '.item-uom-select', function() {
            var $row = $(this).closest('tr');
            var $opt = $(this).find(':selected');
            var selectedPrice = $opt.data('price');
            
            if (selectedPrice !== undefined) {
                $row.find('.price').val(selectedPrice);
            }
            // User requested: Pkt Size should not change when selecting different UOM
            recalcRow($row);
            recalcSummary();
        });

        $('#purchaseItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
            checkEmptyState();
        });
        $('.input_summary, #sum_expense').on('input', recalcSummary);


        $('#btn_import_po').click(function() {
            let selectedVendorId = $('#vendor_id').val();
            if (!selectedVendorId) return;

            // Show matching rows, hide non-matching
            let found = false;
            $('.booked-item-row').each(function() {
                let rowVendorId = $(this).attr('data-vendor-id');
                if (rowVendorId == selectedVendorId) {
                    $(this).show();
                    found = true;
                } else {
                    $(this).hide();
                }
            });

            if (found) {
                $('#emptyBookedRow').hide();
            } else {
                $('#emptyBookedRow').show().find('td').text('No Purchase Orders found for the selected vendor.');
            }
        });

        // Discount Toggles Row
        $('#purchaseItems').on('click', '.disc-type-toggle', function() {
            let $btn = $(this);
            let $hidden = $btn.siblings('.item_discount_type');
            let current = $btn.attr('data-type');
            let next = (current === 'amount') ? 'percent' : 'amount';

            $btn.attr('data-type', next).text(next === 'amount' ? 'Rs' : '%');
            $hidden.val(next);
            
            if (next === 'percent') {
                $btn.removeClass('btn-outline-primary').addClass('btn-primary text-white');
            } else {
                $btn.removeClass('btn-primary text-white').addClass('btn-outline-primary');
            }

            recalcRow($btn.closest('tr'));
            recalcSummary();
        });

        // Discount Toggle Bill
        $('#bill_disc_type_btn').click(function() {
            let $btn = $(this);
            let $hidden = $('#bill_discount_type');
            let current = $btn.attr('data-type');
            let next = (current === 'amount') ? 'percent' : 'amount';

            $btn.attr('data-type', next).text(next === 'amount' ? 'Rs' : '%');
            $hidden.val(next);

            if (next === 'percent') {
                $btn.removeClass('btn-outline-primary').addClass('btn-primary text-white');
            } else {
                $btn.removeClass('btn-primary text-white').addClass('btn-outline-primary');
            }

            recalcSummary();
        });


        recalcSummary();
        checkEmptyState();

        // --- Booked Products Logic ---
        $('#searchBookedProducts').on('input', function() {
            let val = $(this).val().toLowerCase();
            $('.booked-item-row').each(function() {
                let searchable = $(this).attr('data-search') || '';
                $(this).toggle(searchable.includes(val));
            });
        });

        function importBookedItem(btn) {
            let $btn = $(btn);
            let items = $btn.data('items');

            if (typeof items === 'string') {
                try {
                    items = JSON.parse(items);
                } catch (e) {
                    items = [];
                }
            }
            if (!items || !items.length) return;

            // Set Form Data
            if ($btn.data('draft-id')) $('#draft_id_input').val($btn.data('draft-id'));
            if ($btn.data('vendor-id')) {
                $('#vendor_id').val($btn.data('vendor-id'));
                if ($btn.data('vendor-name')) {
                    $('#selected_vendor_name').text($btn.data('vendor-name'));
                }
            }
            if ($btn.data('warehouse-id')) $('input[name="warehouse_id"]').val($btn.data('warehouse-id'));
            if ($btn.data('purchase-date')) $('input[name="purchase_date"]').val($btn.data('purchase-date'));
            if ($btn.data('po-date')) $('input[name="po_date"]').val($btn.data('po-date'));
            if ($btn.data('vendor-bill')) $('input[name="vendor_bill_no"]').val($btn.data('vendor-bill'));
            if ($btn.data('invoice-no')) $('input[name="purchase_order_no"]').val($btn.data('invoice-no'));
            if ($btn.data('grn-no')) $('input[name="grn_no"]').val($btn.data('grn-no'));
            if ($btn.data('note')) $('input[name="note"]').val($btn.data('note'));

            // Clear existing table
            $('#purchaseItems').empty();
            rowCount = 0;

            items.forEach(function(item) {
                let $lastRow = addBlankRow();

                // We need full product details (packings) to populate UOM dropdown correctly
                $.getJSON('/productview/' + item.product_id, function(product) {
                    const mappedData = {
                        id: product.id,
                        item_name: product.item_name,
                        item_code: product.item_code,
                        uom: item.ppb || product.pieces_per_box || 1,
                        size_mode: item.mode || product.size_mode || 'by_cartons',
                        price: item.price,
                        packings: product.packings || []
                    };

                    $lastRow.find('.item_discount').val(item.discount || 0);
                    $lastRow.find('.item_discount_type').val(item.discount_type || 'amount');
                    $lastRow.find('.gst').val(parseFloat(item.gst) || 0);

                    // Ensure batch info stays empty for manual entry
                    $lastRow.find('input[name="mfg_date[]"]').val('');
                    $lastRow.find('input[name="expiry[]"]').val('');
                    $lastRow.find('input[name="lot_no[]"]').val('');

                    // populateProductRow will automatically fetch last batch info if these were empty
                    populateProductRow($lastRow, mappedData, true);

                    $lastRow.find('.price').val(item.price || 0); 
                    
                    if (item.uom_id) {
                        $lastRow.find('.item-uom-select').val(item.uom_id).trigger('change');
                    }

                    if (item.warehouse_id) {
                        $lastRow.find('.item-warehouse').val(item.warehouse_id);
                    }

                    // Update product-select-btn label
                    $lastRow.find('.product-select-btn')
                        .addClass('has-value')
                        .html(
                            (item.product_name || '') +
                            '<br><small class="text-muted" style="font-size:0.7rem;font-weight:400;">' +
                            (item.item_code || '') +
                            '</small><span class="psm-btn-arrow">&#9660;</span>'
                        );

                    // FINAL STEP: Set quantities and recalculate
                    setTimeout(() => {
                        let q = parseFloat(item.qty);
                        let l = parseFloat(item.loose_qty);
                        let f = parseFloat(item.free_qty_pieces ?? 0);
                        
                        console.log('Async Setting Qty for ' + item.product_name, { q, l, f });
                        
                        $lastRow.find('.quantity').val(isNaN(q) ? 0 : q);
                        $lastRow.find('.loose_qty').val(isNaN(l) ? 0 : l);
                        $lastRow.find('.free_qty_pieces').val(isNaN(f) ? 0 : f);

                        if (item.discount_type === 'percent') {
                            $lastRow.find('.disc-type-toggle').attr('data-type', 'percent').text('%').removeClass('btn-outline-primary').addClass('btn-primary text-white');
                        }
                        
                        recalcRow($lastRow);
                        recalcSummary();
                    }, 100);
                });
            });

            recalcSummary();
            checkEmptyState();
        }

        $('.btn-import-single').on('click', function() {
            if (window.ERPImportLoader) window.ERPImportLoader.start();
            importBookedItem(this);
            $('#bookedProductsModal').modal('hide');
            if (window.ERPImportLoader) window.ERPImportLoader.success();
        });

        function initPackingSelect2($el, $row) {
            $el.select2({
                tags: true,
                placeholder: 'Type or Select Packing...',
                width: '100%',
                dropdownParent: $row 
            }).on('change', function() {
                var selected = $(this).select2('data')[0];
                var $opt = $(this).find('option:selected');
                
                if ($opt.data('factor')) {
                    // Existing Packing
                    $row.find('.item_uom_factor').val($opt.data('factor'));
                    $row.find('.packing-id').val($opt.data('id'));
                    if ($opt.data('p-price') > 0) {
                        $row.find('.price').val($opt.data('p-price'));
                    }
                } else {
                    $row.find('.packing-id').val('');
                }
                recalcRow($row);
                recalcSummary();
            });
        }

        // Initialize existing row recalcs (no Select2 needed — using ERP Modal)

        $('#purchaseItems tr.item-row').each(function() {
            recalcRow($(this), false);
        });

        // Enable Import button if vendor is present
        if ($('#vendor_id').val()) {
            $('#btn_import_po').prop('disabled', false).attr('title', 'Import products from PO');
        }

        recalcSummary();

        // HS Code Toggle Logic
        function toggleHsCodeFields() {
            const isEnabled = $('#enable_hs_code').is(':checked');
            if (isEnabled) {
                $('.hs-code-col').show();
            } else {
                $('.hs-code-col').hide();
            }
        }
        $('#enable_hs_code').on('change', toggleHsCodeFields);
        toggleHsCodeFields(); // Initial call
    });
</script>
