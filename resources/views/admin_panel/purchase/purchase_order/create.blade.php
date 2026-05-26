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
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
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
            padding: 0.375rem 0.625rem;
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
            -m
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

                <form id="purchaseForm" action="{{ route('store.Purchase') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" id="action" name="action" value="save_only">
                    <input type="hidden" id="draft_id_input" name="draft_id" value="">
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">

                    <!-- Page Header Top -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="page-title"><i class="bi bi-file-earmark-plus me-2 text-primary"></i>
                            Purchase Order
                        </h1>
                        <p class="page-subtitle mb-0">Prepare procurement requests and save as draft.</p>
                        <div class="d-flex gap-3">
                            {{-- <button type="button" class="btn-erp btn-erp-secondary" data-toggle="modal"
                                data-target="#bookedProductsModal" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                <i class="bi bi-card-checklist"></i> Purchase Order
                            </button> --}}
                            <a href="{{ route('purchase.order.index') }}" class="btn-erp btn-erp-secondary">
                                <i class="bi bi-arrow-left"></i> Back to PO List
                            </a>
                            <!-- Shortcut Actions -->
                        </div>
                    </div>

                    <div class="erp-card mb-3">
                        <div class="erp-card-header">
                            <h3 class="erp-card-title"><i class="bi bi-info-square text-primary"></i>
                                Purchase Order Information
                            </h3>
                            <span class="erp-badge"><i class="bi bi-clock-history me-1 text-info"></i>Draft Order</span>
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
                                                        <input type="text" class="form-control fw-bold shadow-sm"
                                                            style="max-width: 300px;" value="{{ $branchName }}" readonly
                                                            disabled>
                                                        <input type="hidden" name="warehouse_id"
                                                            value="{{ $Warehouse->first()->id ?? '' }}">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">PO #</td>
                                                    <td class="text-secondary fw-bold compact-td" style="width: 35%;">
                                                        <input type="hidden" name="invoice_no"
                                                            value="{{ $nextInvoice ?? 'PO-0001' }}">
                                                        <input type="text" readonly
                                                            value="{{ $nextInvoice ?? 'PO-0001' }}"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100 fw-bold"
                                                            style="outline: none;">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td" style="width: 25%;">VENDOR
                                                        BILL #</td>
                                                    <td style="width: 25%;" class="compact-td"><input type="text"
                                                            name="vendor_bill_no" class="compact-input shadow-sm"></td>
                                                </tr>
                                                <tr>
                                                    <td class="compact-lbl compact-td">PO DATE :</td>
                                                    <td class="compact-td" colspan="3"><input type="date"
                                                            name="purchase_date" value="{{ date('Y-m-d') }}"
                                                            class="compact-input shadow-sm text-secondary"
                                                            style="max-width: 200px;"></td>
                                                </tr>
                                                <tr style="display:none;">
                                                    <td class="compact-lbl compact-td">PO #</td>
                                                    <td class="text-secondary compact-td">
                                                        <!-- Note: the name was requested as purchase_order_no in the form -->
                                                        <input type="text" name="purchase_order_no"
                                                            class="bg-transparent border-0 text-secondary p-0 w-100"
                                                            style="outline: none;" readonly value="000000">
                                                    </td>
                                                    <td class="compact-lbl text-end compact-td">PO DATE :</td>
                                                    <td class="compact-td"><input type="datetime-local" name="po_date"
                                                            value="{{ date('Y-m-d\TH:i') }}"
                                                            class="compact-input shadow-sm text-secondary"></td>
                                                </tr>
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
                                                    <span id="selected_vendor_name" class="text-muted small">Select Vendor Account</span>
                                                    <i class="bi bi-chevron-down ms-2 text-primary" style="font-size: 0.7rem;"></i>
                                                </button>
                                                <input type="hidden" name="vendor_id" id="vendor_id" value="">
                                            </div>
                                        </div>

                                        <div class="mt-auto bg-light p-3 rounded border">
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                                                <input class="form-check-input mt-0" type="checkbox" id="gst_invoice"
                                                    name="is_gst_invoice" checked
                                                    style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl"
                                                    for="gst_invoice" style="cursor:pointer; padding-top:2px;">GST
                                                    INVOICE</label>
                                            </div>
                                            <div class="form-check form-switch d-flex align-items-center gap-2 mt-2 mb-0">
                                                <input class="form-check-input mt-0" type="checkbox" id="enable_hs_code"
                                                    name="enable_hs_code" value="1" checked
                                                    style="width: 2.2em; height: 1.1em; cursor:pointer;">
                                                <label class="form-check-label fw-bold text-dark compact-lbl"
                                                    for="enable_hs_code" style="cursor:pointer; padding-top:2px;">ENABLE HS CODE</label>
                                            </div>
                                            <div class="text-muted mt-2" style="font-size: 11px; font-weight: 600;">
                                                STATUS: <span class="text-primary">UN-POSTED</span></div>
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
                                            <th style="width:200px">Product</th>
                                            <th style="width:120px" class="hs-code-col">HS Code</th>
                                            <th style="width:160px">Warehouse</th>
                                            <th style="width:160px">Packing (UOM)</th>
                                            <th style="width:80px">Pkt Size</th>
                                            <th style="min-width:180px" class="text-end">Paid (Box / Loose)</th>
                                            <th style="min-width:100px" class="text-end">Free Pcs</th>
                                            <th style="width:110px" class="text-end">Rate/PC</th>
                                            <th style="width:110px" class="text-end">Disc</th>
                                            <th style="width:130px" class="text-end">Sub Total</th>
                                            <th style="width:90px" class="text-end">GST(%)</th>
                                            <th style="width:140px" class="text-end">Net Total</th>
                                            <th style="width:50px; text-align:center;">Del</th>
                                        </tr>
                                    </thead>
                                    <tbody id="purchaseItems">
                                        <!-- Embedded Rows Go Here -->
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
                                                placeholder="Any internal notes">
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
                                            id="summary_total_qty" readonly tabindex="-1" value="0 Pcs">
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">Free Qty</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-info"
                                            id="summary_free_qty" readonly tabindex="-1" value="0 Pcs">
                                    </div>
                                    <div class="summary-row" style="background: #f1f5f9; border-top: 2px solid #e2e8f0; margin-top: 5px;">
                                        <span class="summary-label fw-bold">Grand Total Qty</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-dark fw-bold"
                                            id="summary_grand_qty" readonly tabindex="-1" value="0 Pcs">
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
                                        <span class="summary-label">Sub Total (After Disc)</span>
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
                                            <span class="summary-label">Vendor Expenses</span>
                                            <small class="text-muted" style="font-size: 0.65rem;">(Added to cost)</small>
                                        </div>
                                        <input type="number"
                                            class="form-control form-control-sm text-end w-50 input_summary"
                                            name="extra_cost" id="sum_expense" value="0.00">
                                    </div>

                                    <div class="summary-row text-danger mt-1">
                                        <span class="summary-label text-danger">Total GST Tax</span>
                                        <input type="text"
                                            class="form-control form-control-sm text-end w-50 bg-transparent border-0 summary-value text-danger"
                                            id="total_gst" readonly tabindex="-1" value="0.00">
                                    </div>

                                    <!-- FINAL NET TOTAL -->
                                    <div
                                        class="summary-total-row d-flex justify-content-between align-items-center mt-3 shadow-sm">
                                        <div class="summary-total-label">NET PAYABLE</div>
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
                                        <input type="hidden" name="action" value="save_only">
                                        <button type="submit"
                                            class="btn-erp btn-erp-primary justify-content-center pt-3 pb-3"
                                            id="btnSaveOnly" style="font-size: 1.1rem;">
                                            <i class="bi bi-save2"></i> SAVE PURCHASE ORDER (DRAFT)
                                        </button>
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
                                    <tr class="booked-item-row"
                                        data-search="{{ strtolower($draft->invoice_no . ' ' . ($draft->vendor->name ?? '')) }}">
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-import-single"
                                                title="Import Purchase" data-vendor-id="{{ $draft->vendor_id }}"
                                                data-draft-id="{{ $draft->id }}"
                                                data-warehouse-id="{{ $draft->warehouse_id }}"
                                                data-purchase-date="{{ $draft->purchase_date ? substr($draft->purchase_date, 0, 10) : date('Y-m-d') }}"
                                                data-po-date="{{ $draft->created_at ? \Carbon\Carbon::parse($draft->created_at)->format('Y-m-d\TH:i') : date('Y-m-d\TH:i') }}"
                                                data-vendor-bill="{{ $draft->vendor_bill_no }}"
                                                data-invoice-no="{{ $draft->invoice_no }}"
                                                data-grn-no="{{ $draft->grn_no }}" data-note="{{ $draft->note }}"
                                                data-items="{{ json_encode(
                                                    $draft->items->map(function ($i) {
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
                                                            'uom_factor' => $i->uom_factor ?? 1,
                                                            'ppb' => $i->pieces_per_box ?? 1,
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
                                        <td class="text-end fw-bold text-primary">{{ (float) $draft->items->sum('qty') }}
                                        </td>
                                        <td class="text-end text-success fw-bold">Rs.
                                            {{ number_format($draft->net_amount, 2) }}</td>
                                        <td class="text-end">
                                            <!-- imported via row button -->
                                        </td>
                                    </tr>
                                @endforeach

                                @if (!$hasDraftItems)
                                    <tr id="emptyBookedRow">
                                        <td colspan="7" class="text-center py-5 text-muted">
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

{{-- Advanced Product Selection Modal --}}
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
                        let redirectUrl = "{{ route('Purchase.home') }}";
                        @if (request()->query('mode') == 'po')
                            redirectUrl += "?status=draft&mode=po";
                        @endif
                        window.location.href = redirectUrl;
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

            let warningText =
                "This will officially update inventory stock and financial accounts. This action is irreversible.";
            let warningTitle = "Confirm & Post Purchase?";
            let warningIcon = "warning";
            let confirmBtnColor = '#059669';
            let confirmBtnText = 'Yes, Post it!';

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

            if (boxesVal.includes('.')) {
                let parts = boxesVal.split('.');
                boxes = parseInt(parts[0]) || 0;
                pieces = parseInt(parts[1]) || 0;

                if (pieces >= pack && pack > 0) {
                    let extraBoxes = Math.floor(pieces / pack);
                    boxes += extraBoxes;
                    pieces = pieces % pack;
                }
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

            let paid = parseBoxPiece($row.find('.quantity'), $row.find('.loose_qty'), pack, sizeMode);
            // Free qty is directly in pieces as per user request
            let freePieces = num($row.find('.free_qty_pieces').val());
            let free = { total_pieces: freePieces, boxes: 0, pieces: freePieces };

            const price = num($row.find('.price').val()); 
            const disc_val = num($row.find('.item_discount').val()); 
            const disc_type = $row.find('.item_discount_type').val() || 'amount';

            let line_gross = paid.total_pieces * price;
            // Per user request: Flat discount amount, not per piece
            let line_disc = (disc_type === 'percent') ? (line_gross * disc_val / 100) : disc_val;

            let amount = line_gross - line_disc;
            if (amount < 0) amount = 0;

            const stTaxPercent = num($row.find('.st-tax').val());
            const stTaxAmount = amount * (stTaxPercent / 100);

            let total_amount = amount + stTaxAmount;

            $row.find('.st-tax-amount-row').val(stTaxAmount.toFixed(2));
            $row.find('.row-subtotal').val(amount.toFixed(2));
            $row.find('.row-nettotal').val(total_amount.toFixed(2));
        }

        function recalcSummary() {
            let true_gross = 0;
            let total_row_disc = 0;
            let totalStTaxAmt = 0;
            let sum_paid_pieces = 0;
            let sum_free_pieces = 0;
            let sum_boxes = 0;
            let sum_loose = 0;

            $('#purchaseItems tr').each(function() {
                const $row = $(this);
                const pack = parseFloat($row.find('.item-uom').val()) || 1;
                const sizeMode = $row.find('.size-mode').val() || 'by_cartons';

                let paid = parseBoxPiece($row.find('.quantity'), $row.find('.loose_qty'), pack, sizeMode);
                // Free qty is directly in pieces as per user request
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

                totalStTaxAmt += num($row.find('.st-tax-amount-row').val());
            });

            $('#gross_total').val(true_gross.toFixed(2));
            $('#total_row_disc').val(total_row_disc.toFixed(2));
            $('#total_gst').val(totalStTaxAmt.toFixed(2));

            // Display total pieces only
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

            let finalNet = summarySub + totalStTaxAmt + sumFreight + sumExpense;
            $('#final_net_total').val(finalNet.toFixed(2));
        }

        function addBlankRow() {
            rowCount++;
            // Pre-collect warehouses from server-side variable
            let warehouseOptions = `<option value="">-- Select --</option>`;
            @foreach($warehouses as $wh)
                warehouseOptions += `<option value="{{ $wh->id }}">{{ $wh->warehouse_name }}</option>`;
            @endforeach

            const newRow = `
              <tr class="item-row">
                <td>
                    <input type="hidden" name="product_id[]" class="item-id">
                    <input type="text" name="item_code[]" class="form-control bg-transparent border-0 px-0 item-code" readonly>
                </td>
                <td style="font-weight:600; color:#334155;">
                    <input type="hidden" name="item_name[]" class="item-name">
                    <input type="hidden" name="size_mode[]" class="size-mode">
                    <button type="button" class="product-select-btn">Select Product <span class="psm-btn-arrow">&#9660;</span></button>
                </td>
                <td class="hs-code-col">
                    <input type="text" name="hs_code[]" class="form-control text-center hs-code" readonly tabindex="-1" style="background:#f8fafc;">
                </td>
                <td>
                    <select name="item_warehouse_id[]" class="form-select item-warehouse" style="min-width:140px;">
                        ${warehouseOptions}
                    </select>
                </td>
                <td>
                    <select name="uom_id[]" class="form-select item-uom-select row-input" style="min-width:120px;">
                        <option value="">-- Base --</option>
                    </select>
                    <input type="hidden" name="uom_name[]" class="uom-name-hidden">
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
                <td><input type="number" step="0.01" name="price[]" class="form-control price text-end row-input" value="0" ></td>
                <td>
                    <div class="input-group input-group-sm shadow-sm" style="min-width: 100px;">
                        <input type="number" step="any" name="item_discount[]" class="form-control item_discount text-end row-input" value="0">
                        <button type="button" class="btn btn-sm btn-outline-primary disc-type-toggle p-1" data-type="amount" style="width: 32px; font-size: 0.7rem; font-weight: 700;">Rs</button>
                        <input type="hidden" name="item_discount_type[]" class="item_discount_type" value="amount">
                    </div>
                </td>
                <td><input type="text" name="amount[]" class="form-control row-subtotal input-highlight text-end" readonly></td>
                <td>
                    <input type="number" step="any" name="st_tax_percent[]" class="form-control st-tax text-end row-input" value="0" placeholder="%">
                    <input type="hidden" name="st_tax_amount[]" class="st-tax-amount-row" value="0">
                </td>
                <td><input type="text" name="total_amount[]" class="form-control row-nettotal input-highlight text-end" readonly></td>
                <td style="text-align:center;">
                    <button type="button" class="btn-erp-danger-ghost remove-row" title="Remove Item"><i class="bi bi-x-circle-fill"></i></button>
                </td>
              </tr>`;

            $('#purchaseItems').append(newRow);
            const $inserted = $('#purchaseItems tr:last');

            // Auto-select header warehouse if set
            let headerWh = $('#main_warehouse_id').val() || $('#warehouse_id').val();
            if (headerWh) {
                $inserted.find('.item-warehouse').val(headerWh);
            }

            recalcRow($inserted);
            recalcSummary();
            checkEmptyState();
            return $inserted;
        }

        /* ── Row Product Button → Modal ── */
        $(document).on('click', '.product-select-btn', function() {
            var $triggerBtn = $(this);
            var $triggerRow = $triggerBtn.closest('tr');
            var triggerRowEmpty = !$triggerRow.find('.item-id').val();

            var currentId = $triggerRow.find('.item-id').val();
            var allIds = [];
            $('#purchaseItems tr').each(function() {
                var id = $(this).find('.item-id').val();
                if (id) allIds.push(parseInt(id));
            });

            ERPProductModal.open({
                priceField: 'purchase',
                targetRow: $triggerRow,
                selectedIds: currentId ? [parseInt(currentId)] : [],
                existingIds: allIds,
                onSelect: function(products) {
                    products.forEach(function(p, idx) {
                        // Duplicate guard: bump qty if already in table
                        var $existing = null;
                        $('#purchaseItems tr').each(function() {
                            if (parseInt($(this).find('.item-id').val()) === parseInt(p.id)) {
                                $existing = $(this); return false;
                            }
                        });
                        if ($existing) {
                            $existing.find('.quantity').val((parseInt($existing.find('.quantity').val()) || 0) + 1).trigger('input');
                            return;
                        }

                        // First product replaces the triggering row, subsequent products get new rows
                        var $row = (idx === 0) ? $triggerRow : addBlankRow();
                        populateProductRow($row, p);

                        recalcRow($row);
                        recalcSummary();
                    });
                    checkEmptyState();
                    setTimeout(function() { $('#purchaseItems tr:last').find('.quantity').focus().select(); }, 150);
                }
            });
        });

        function populateProductRow($row, p) {
            var $btn = $row.find('.product-select-btn');
            // Update button label
            $btn.text(p.item_name + (p.item_code ? ' (' + p.item_code + ')' : '') + ' ');
            $btn.append('<span class="psm-btn-arrow">&#9660;</span>');
            $btn.addClass('has-value');

            // Populate hidden fields
            $row.find('.item-id').val(p.id);
            $row.find('.item-code').val(p.item_code || '');
            $row.find('.item-name').val(p.item_name || '');
            $row.find('.hs-code').val(p.hs_code || '');
            $row.find('.size-mode').val(p.size_mode || 'by_cartons');
            $row.find('.price').val(p.purchase_price_per_piece || 0);
            $row.find('.item-uom').val(p.pieces_per_box || 1);
            
            // HS Code visibility sync
            if ($('#enable_hs_code').is(':checked')) {
                $row.find('.hs-code-col').show();
            } else {
                $row.find('.hs-code-col').hide();
            }

            // Packing dropdown
            var $uomSelect = $row.find('.item-uom-select');
            var ppb = p.pieces_per_box || 1;
            $uomSelect.html('<option value="" data-price="'+(p.purchase_price_per_piece||0)+'" data-factor="'+ppb+'">-- Base ('+(p.uom_name||'Pcs')+')</option>');
            if (p.packings && p.packings.length > 0) {
                p.packings.forEach(function(pkg) {
                    var f = pkg.pieces_per_box > 0 ? pkg.pieces_per_box : 1;
                    $uomSelect.append('<option value="'+pkg.id+'" data-price="'+pkg.purchase_price+'" data-factor="'+f+'">'+pkg.name+'</option>');
                });
            }
            $uomSelect.append('<option value="new_uom" style="background:#f0f0f0;font-weight:bold;color:#2563eb;">+ Add New Packing</option>');
        }

        function initSelect2($el) {  /* kept for legacy — no longer called for rows */
            $el.select2({
                placeholder: 'Search product...',
                allowClear: true,
                ajax: {
                    url: "{{ route('search-products') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term,
                            branch_id: $('input[name="branch_id"]').val()
                        };
                    },
                    processResults: function(data) {
                        var items = Array.isArray(data) ? data : (data.results || []);
                        return {
                            results: items.map(function(item) {
                                return {
                                    id: item.id,
                                    text: (item.item_code ? item.item_code + ' - ' : '') + item.item_name,
                                    item_name: item.item_name,
                                    item_code: item.item_code,
                                    uom: item.pieces_per_box || 1,
                                    size_mode: item.size_mode || 'standard',
                                    price: item.purchase_price_per_piece || 0,
                                    packings: item.packings || []
                                };
                            }),
                            pagination: data.pagination || { more: false }
                        };
                    },
                    cache: false
                }
            });

            $el.on('select2:select', function(e) {
                var data = e.params.data;
                var $row = $(this).closest('tr');
                $row.find('.item-code').val(data.item_code);
                $row.find('.item-id').val(data.id);
                $row.find('.item-name').val(data.item_name);
                $row.find('.size-mode').val(data.size_mode);
                $row.find('.item-uom').val(data.uom);
                $row.find('.price').val(data.price);

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

                recalcRow($row);
                recalcSummary();
                setTimeout(() => $row.find('.quantity').focus().select(), 100);
            });

            // Header Warehouse Sync
            $('#warehouse_id, #main_warehouse_id').on('change', function() {
                let whId = $(this).val();
                if (whId) {
                    $('.item-warehouse').val(whId);
                }
            });

            $el.closest('tr').on('change', '.item-uom-select', async function() {
                var $row = $(this).closest('tr');
                var val = $(this).val();
                
                if (val === 'new_uom') {
                    const { value: formValues } = await Swal.fire({
                        title: 'Create New Packing',
                        html:
                            '<div class="mb-3 text-start"><label class="form-label small fw-bold">Packing Name</label>' +
                            '<input id="swal-uom-name" class="form-control" placeholder="e.g. Box of 24"></div>' +
                            '<div class="mb-3 text-start"><label class="form-label small fw-bold">Pieces Per Box</label>' +
                            '<input id="swal-uom-ppb" type="number" class="form-control" placeholder="Quantity"></div>',
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Add Packing',
                        preConfirm: () => {
                            const name = document.getElementById('swal-uom-name').value;
                            const ppb = document.getElementById('swal-uom-ppb').value;
                            if (!name || !ppb || ppb <= 0) {
                                Swal.showValidationMessage('Please enter valid name and pieces');
                                return false;
                            }
                            return { name: name, ppb: ppb };
                        }
                    });

                    if (formValues) {
                        const newName = formValues.name;
                        const factorVal = parseFloat(formValues.ppb);

                        var $opt = $('<option>', {
                            value: 'NEW',
                            text: newName + ' (' + factorVal + ')',
                            selected: true
                        }).attr({
                            'data-factor': factorVal,
                            'data-is-new': 'true',
                            'data-name': newName,
                            'data-price': 0 
                        });
                        
                        $(this).find('option[value="NEW"]').remove();
                        $(this).append($opt);
                        
                        $row.find('.item-uom').val(factorVal);
                        $row.find('.uom-name-hidden').val(newName);
                        $row.find('.is-new-uom').val('1');
                        
                        recalcRow($row);
                    } else {
                        $(this).val($(this).find('option:first').val()).trigger('change');
                    }
                } else {
                    var $opt = $(this).find('option:selected');
                    var factor = parseFloat($opt.data('factor')) || 1;
                    var name = $opt.text().split('(')[0].trim();
                    var price = $opt.data('price');

                    $row.find('.item-uom').val(factor);
                    $row.find('.uom-name-hidden').val(name);
                    $row.find('.is-new-uom').val('0');

                    if (price !== undefined && price > 0) {
                        $row.find('.price').val(price);
                    }

                    recalcRow($row);
                }
            });

            $el.on('select2:unselect', function(e) {
                var $row = $(this).closest('tr');
                $row.find('.item-code').val('');
                $row.find('.item-id').val('');
                $row.find('.item-name').val('');
                $row.find('.item-uom').val('');
                $row.find('.price').val(0);
                recalcRow($row);
                recalcSummary();
            });
        }

        // HS Code Toggle Logic
        $('#enable_hs_code').on('change', function() {
            if($(this).is(':checked')) {
                $('.hs-code-col').show();
            } else {
                $('.hs-code-col').hide();
            }
        }).trigger('change');

        // Generate Blank Row by default
        addBlankRow();

        // Add row via button
        $('#btnAddRow').click(function() {
            addBlankRow();
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

        // Add row via Enter Key on any row-input
        $('#purchaseItems').on('keydown', '.row-input', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                // Check if this is the last row in the table
                const isLastRow = $(this).closest('tr').is(':last-child');
                if (isLastRow) {
                    addBlankRow();
                    // Focus the new row's product button
                    setTimeout(function() {
                        $('#purchaseItems tr:last').find('.product-select-btn').focus();
                    }, 100);
                } else {
                    // Just move to the next row's quantity or select2
                    $(this).closest('tr').next().find('.quantity').focus().select();
                }
            }
        });

        // Calculations & Removals
        $('#purchaseItems').on('input', '.quantity, .loose_qty, .free_qty, .free_qty_pieces, .price, .item_discount, .st-tax', function() {
            recalcRow($(this).closest('tr'));
            recalcSummary();
        });

        $('#purchaseItems').on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            recalcSummary();
            checkEmptyState();
        });
        $('.input_summary, #sum_expense').on('input', recalcSummary);

        // Init Vendor Search Select2
        $('#vendor_select').select2({
            placeholder: 'Select Vendor A/C...',
            allowClear: true
        });

        recalcSummary();
        checkEmptyState();

        // Vendor Modal Logic
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
            $('#selected_vendor_name').text(name);
            $('#vendorModal').modal('hide');
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
            if ($btn.data('vendor-id')) $('#vendor_select').val($btn.data('vendor-id')).trigger('change');
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

            // Insert Products
            items.forEach(function(item) {
                let $lastRow = addBlankRow();

                // Fetch full product details for dropdowns
                $.getJSON('/productview/' + item.product_id, function(p) {
                    populateProductRow($lastRow, p);

                    // Set imported values
                    $lastRow.find('.quantity').val((item.qty === 0 || item.qty === "0") ? 0 : (item.qty || 1));
                    $lastRow.find('.loose_qty').val((item.loose_qty === 0 || item.loose_qty === "0") ? 0 : (item.loose_qty || 0));
                    $lastRow.find('.free_qty_pieces').val(item.free_qty_pieces ?? item.free_qty ?? 0);
                    $lastRow.find('.item_discount').val(item.discount || 0);
                    $lastRow.find('.item_discount_type').val(item.discount_type || 'amount');
                    $lastRow.find('.price').val(item.price || p.purchase_price_per_piece || 0);
                    $lastRow.find('.gst').val(item.gst || 0).trigger('input'); 
                    
                    if (item.uom_id) {
                        $lastRow.find('.item-uom-select').val(item.uom_id).trigger('change');
                    }

                    if ((item.discount_type || 'amount') === 'percent') {
                        $lastRow.find('.disc-type-toggle').attr('data-type', 'percent').text('%').removeClass('btn-outline-primary').addClass('btn-primary text-white');
                    }
                    
                    recalcRow($lastRow);
                    recalcSummary();
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

        // ── Product Browse Modal Integration ──
        $('#btnBrowseProducts').on('click', function() {
            // Collect IDs already in the table
            let existingIds = [];
            $('#purchaseItems tr').each(function() {
                let id = parseInt($(this).find('.item-id').val());
                if (id) existingIds.push(id);
            });

            ERPProductModal.open({
                priceField: 'purchase',
                existingIds: existingIds,
                onSelect: function(products) {
                    products.forEach(function(p) {
                        // Duplicate guard: if product already in table, bump qty
                        let $existingRow = null;
                        $('#purchaseItems tr').each(function() {
                            if (parseInt($(this).find('.item-id').val()) === parseInt(p.id)) {
                                $existingRow = $(this);
                                return false;
                            }
                        });

                        if ($existingRow) {
                            let cur = parseInt($existingRow.find('.quantity').val()) || 0;
                            $existingRow.find('.quantity').val(cur + 1).trigger('input');
                            return;
                        }

                        // Add new row
                        let $row = addBlankRow();

                        // Populate fields
                        $row.find('.item-id').val(p.id);
                        $row.find('.item-code').val(p.item_code || '');
                        $row.find('.item-name').val(p.item_name || '');
                        $row.find('.size-mode').val(p.size_mode || 'by_cartons');
                        $row.find('.price').val(p.purchase_price_per_piece || 0);
                        $row.find('.quantity').val(1);
                        $row.find('.loose_qty').val(0);

                        // UOM/Packing dropdown
                        let $uomSelect = $row.find('.item-uom-select');
                        $uomSelect.html('<option value="" data-price="'+(p.purchase_price_per_piece||0)+'" data-factor="'+(p.pieces_per_box||1)+'">-- Base ('+( p.uom_name||'Pcs')+')</option>');
                        if (p.packings && p.packings.length > 0) {
                            p.packings.forEach(function(pkg) {
                                let ppb = pkg.pieces_per_box > 0 ? pkg.pieces_per_box : 1;
                                $uomSelect.append('<option value="'+pkg.id+'" data-price="'+pkg.purchase_price+'" data-factor="'+ppb+'">'+pkg.name+'</option>');
                            });
                        }
                        $uomSelect.append('<option value="new_uom" style="background:#f0f0f0;font-weight:bold;color:#2563eb;">+ Add New Packing</option>');

                        // Set UOM factor
                        $row.find('.item-uom').val(p.pieces_per_box || 1);

                        // Inject Select2 display option
                        let label = (p.item_code ? p.item_code + ' - ' : '') + p.item_name;
                        let opt = new Option(label, p.id, true, true);
                        $row.find('.product-select2').append(opt).trigger('change.select2');

                        recalcRow($row);
                        recalcSummary();
                    });
                    checkEmptyState();
                }
            });
        });

    });
</script>
