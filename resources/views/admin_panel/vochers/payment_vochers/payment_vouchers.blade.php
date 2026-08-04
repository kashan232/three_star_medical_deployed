@extends('admin_panel.layout.app')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        .vch-create-page {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 28px 0 50px;
        }

        .page-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

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

        .breadcrumb-bar {
            font-size: .82rem;
            color: #8897b0;
        }

        .breadcrumb-bar a {
            color: #18b870;
            text-decoration: none;
        }

        .form-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf4;
            box-shadow: 0 2px 18px rgba(0, 0, 0, .05);
            overflow: hidden;
            margin-bottom: 22px;
        }

        .form-card-header {
            background: linear-gradient(135deg, #18b870, #059e5a);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-card-header h6 {
            color: #fff;
            font-weight: 600;
            font-size: .98rem;
            margin: 0;
        }

        .step-badge {
            background: rgba(255, 255, 255, .22);
            color: #fff;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .form-card-body {
            padding: 24px;
        }

        .lbl {
            font-size: .78rem;
            font-weight: 600;
            color: #6b7a99;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px;
            display: block;
        }

        .erp-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: .9rem;
            color: #1a2340;
            width: 100%;
            transition: border-color .2s;
            background: #fff;
        }

        .erp-input:focus {
            outline: none;
            border-color: #18b870;
            box-shadow: 0 0 0 3px rgba(24, 184, 112, .12);
        }

        .erp-input[readonly] {
            background: #f7f9fc;
            color: #8897b0;
        }

        .erp-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 10px 14px;
            font-size: .9rem;
            color: #1a2340;
            width: 100%;
            background: #fff;
            appearance: none;
        }

        .erp-select:focus {
            outline: none;
            border-color: #18b870;
            box-shadow: 0 0 0 3px rgba(24, 184, 112, .12);
        }

        .line-table-wrap {
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid #e8ecf4;
        }

        .line-table {
            width: 100%;
            border-collapse: collapse;
        }

        .line-table thead th {
            background: #f0f4f8;
            color: #6b7a99;
            font-size: .74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 11px 14px;
            border-bottom: 1.5px solid #e8ecf4;
            white-space: nowrap;
        }

        .line-table tbody tr {
            border-bottom: 1px solid #f0f4f8;
        }

        .line-table tbody tr:hover {
            background: #f5fff9;
        }

        .line-table td {
            padding: 10px 10px;
            vertical-align: middle;
        }

        .line-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 8px 10px;
            font-size: .86rem;
            width: 100%;
        }

        .line-input:focus {
            outline: none;
            border-color: #18b870;
        }

        .line-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 8px 10px;
            font-size: .84rem;
            width: 100%;
            background-color: #fff;
        }

        .line-select:focus {
            outline: none;
            border-color: #18b870;
        }

        .tfoot-total {
            background: linear-gradient(135deg, #f0fefb, #e6f9f1);
            padding: 14px 20px;
            border-top: 2px solid #e8ecf4;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }

        .total-label {
            font-weight: 700;
            color: #18b870;
            font-size: .95rem;
            text-transform: uppercase;
        }

        .total-amount-input {
            border: 2px solid #a7f3d0;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #059e5a;
            background: #e6f9f1;
            width: 200px;
            text-align: right;
            pointer-events: none;
        }

        .btn-add-row {
            background: #e6f9f1;
            color: #18b870;
            border: 1.5px dashed #a7f3d0;
            border-radius: 9px;
            padding: 9px 18px;
            font-size: .86rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            transition: all .18s;
        }

        .btn-add-row:hover {
            background: #18b870;
            color: #fff;
            border-color: #18b870;
        }

        .btn-remove-row {
            width: 30px;
            height: 30px;
            border-radius: 7px;
            border: 1.5px solid #fee2e2;
            background: #fff0f0;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: .85rem;
        }

        .btn-remove-row:hover {
            background: #ef4444;
            color: #fff;
        }

        .action-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 22px 24px;
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8ecf4;
        }

        .btn-save {
            background: linear-gradient(135deg, #18b870, #059e5a);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 700;
            font-size: .95rem;
            box-shadow: 0 4px 14px rgba(24, 184, 112, .35);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .22s;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(24, 184, 112, .45);
            color: #fff;
        }

        .btn-cancel {
            background: #f0f4f8;
            color: #6b7a99;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: .92rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #3c4a6b;
        }

        .alert-success-erp {
            background: #e6f9f1;
            border: 1px solid #a7f3d0;
            border-radius: 10px;
            padding: 12px 18px;
            color: #065f46;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        /* Custom Select2 Styling for Payment Voucher */
        .select2-container--default .select2-selection--single {
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            height: 42px;
            padding: 5px 8px;
            font-size: 0.9rem;
            color: #1a2340;
            background-color: #fff;
            display: flex;
            align-items: center;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .select2-container--default.select2-container--focus .select2-selection--single,
        .select2-container--default.select2-container--open .select2-selection--single {
            outline: none;
            border-color: #18b870;
            box-shadow: 0 0 0 3px rgba(24, 184, 112, 0.12);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #1a2340;
            line-height: 28px;
            padding-left: 4px;
            font-weight: 500;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #8897b0;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 8px;
        }

        .select2-dropdown {
            border: 1.5px solid #18b870;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(24, 184, 112, 0.15);
            overflow: hidden;
            z-index: 9999;
        }

        .select2-container--default .select2-search--dropdown {
            padding: 8px;
            background: #f8fafc;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1.5px solid #cbd5e1;
            border-radius: 7px;
            padding: 6px 12px;
            outline: none;
            font-size: 0.88rem;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #18b870;
        }

        .select2-container--default .select2-results__option {
            padding: 8px 12px;
            font-size: 0.88rem;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #18b870;
            color: #fff;
        }
    </style>

    <div class="vch-create-page">
        <div class="container-fluid px-4">
            <div class="page-topbar">
                <div>
                    <div class="page-title">New Payment Voucher <small>Record an outgoing payment to a party or
                            account</small></div>
                    <div class="breadcrumb-bar"><a href="{{ route('all_Payment_vochers') }}">Payment Vouchers</a> &rsaquo; New
                        Entry</div>
                </div>
                <a href="{{ route('all_Payment_vochers') }}" class="btn-cancel"><i class="bi bi-arrow-left"></i> Back to
                    List</a>
            </div>

            @if (session('success'))
                <div class="alert-success-erp"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            <form action="{{ route('store_Pay_vochers') }}" method="POST">
                @csrf

                {{-- STEP 1 --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">1</div>
                        <h6>Voucher Information</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="lbl">Voucher No (PVID)</label>
                                <input type="text" class="erp-input" name="pvid" value="{{ $nextPVID }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="lbl">Receipt Date</label>
                                <input type="date" name="receipt_date" class="erp-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="lbl">Entry Date</label>
                                <input type="date" name="entry_date" class="erp-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="lbl">Payment Source (Account Head)</label>
                                <select name="header_account_head" class="erp-select" id="payFromHead">
                                    <option value="">— Select Head —</option>
                                    @foreach ($AccountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="lbl">Source Account</label>
                                <select name="header_account_id" class="erp-select" id="payFromAccount">
                                    <option disabled selected>— Select Account —</option>
                                </select>
                                <div id="accountBalanceInfo" style="margin-top: 6px; font-size: 0.85rem; font-weight: 700; color: #059e5a; display: none;">
                                    <i class="bi bi-wallet2"></i> Balance: Rs. <span id="accountBalanceAmount">0.00</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="lbl">Payment Mode</label>
                                <select name="payment_mode" class="erp-select" id="paymentMode">
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="lbl">Remarks</label>
                                <input type="text" name="remarks" class="erp-input" placeholder="General notes...">
                            </div>
                        </div>

                        <!-- Cheque Details Section -->
                        <div class="row g-3 mt-1" id="chequeDetailsSection"
                            style="display:none; background: #fdfaf0; padding: 15px; border-radius: 8px; border: 1px dashed #fad881;">
                            <div class="col-md-4">
                                <label class="lbl text-warning">Cheque Number</label>
                                <input type="text" name="cheque_no" id="cheque_no" class="erp-input"
                                    placeholder="e.g. 12345678">
                            </div>
                            <div class="col-md-4">
                                <label class="lbl text-warning">Cheque / Due Date</label>
                                <input type="date" name="cheque_date" id="cheque_date" class="erp-input">
                            </div>
                            <div class="col-md-4">
                                <label class="lbl text-warning">Issuing Bank</label>
                                <input type="text" name="bank_name" id="bank_name" class="erp-input"
                                    placeholder="e.g. HBL Bank">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Payment Lines — NO Rate, NO Discount --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">2</div>
                        <h6>Payment Allocation (Line Items)</h6>
                    </div>
                    <div class="form-card-body pb-0">
                        <div class="line-table-wrap">
                            <table class="line-table" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width:25%">Narration</th>
                                        <th style="width:15%">Reference #</th>
                                        <th style="width:25%">Pay To (Type)</th>
                                        <th style="width:25%">Party / Account</th>
                                        <th style="width:8%">Amount</th>
                                        <th style="width:4%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div style="display:flex;gap:5px;flex-direction:column;">
                                                <div style="display:flex;gap:5px;align-items:center;">
                                                    <select name="narration_id[]" class="line-select narrationSelect" style="flex:1;">
                                                        <option value="">Select / Add</option>
                                                        @foreach ($narrations as $id => $name)
                                                            <option value="{{ $id }}">{{ $name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-quick-narration" title="Quick Add Narration" style="padding: 2px 6px; border-radius: 4px; line-height: 1.2;">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                                <input type="text" class="line-input narrationInput"
                                                    name="narration_text[]" placeholder="Text...">
                                            </div>
                                        </td>
                                        <td><input type="text" name="reference_no[]" class="line-input"></td>
                                        <td>
                                            <select name="vendor_type[]" class="line-select rowType">
                                                <option disabled selected>Select</option>
                                                <option value="vendor">Vendor</option>
                                                <option value="customer">Customer</option>
                                                <option value="walkin">Walkin</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                             <select name="vendor_id[]" class="line-select rowParty">
                                                 <option disabled selected>Select Party</option>
                                             </select>
                                             <div class="row-balance-display" style="display:none; font-size:.78rem; color:#18b870; margin-top:4px; font-weight:700;">
                                                 <i class="bi bi-wallet2"></i> Balance: Rs. <span class="rowBalanceVal">0.00</span>
                                             </div>
                                        </td>
                                        <td>
                                            <input type="hidden" name="discount_value[]" value="0">
                                            <input type="hidden" name="rate[]" value="0">
                                            <input type="number" name="amount[]" class="line-input amount"
                                                placeholder="0.00" style="text-align:right;font-weight:700;">
                                        </td>
                                        <td>
                                            <button type="button" class="btn-remove-row removeRow"><i
                                                    class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn-add-row" id="addNewRow">
                            <i class="bi bi-plus-lg"></i> Add Payment Line
                        </button>
                        <div class="tfoot-total">
                            <span class="total-label">Total Payment:</span>
                            <input type="text" name="total_amount" class="total-amount-input" id="totalAmount"
                                readonly value="0.00">
                        </div>
                    </div>
                </div>

                <div class="action-footer">
                    <a href="{{ route('all_Payment_vochers') }}" class="btn-cancel"><i class="bi bi-x-lg"></i>
                        Cancel</a>
                    <button type="submit" class="btn-save"><i class="bi bi-save2"></i> Save Payment Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Narration Modal -->
    <div class="modal fade" id="quickNarrationModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #18b870, #059e5a);">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill"></i> Quick Add Narration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickNarrationForm">
                    @csrf
                    <input type="hidden" name="expense_head" id="quick_expense_head" value="Payment voucher">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Voucher Type</label>
                            <input type="text" class="form-control bg-light border-0" value="Payment Voucher" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="quick_narration" class="form-label fw-bold text-secondary">Narration / Description</label>
                            <textarea class="form-control" name="narration" id="quick_narration" rows="3" required placeholder="Type narration text here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-bold" style="background: linear-gradient(135deg, #18b870, #059e5a);">Save Narration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@section('js')
    <script>
        // Toggle Cheque Fields based on Payment Mode
        $('#paymentMode').on('change', function() {
            if ($(this).val() === 'cheque') {
                $('#chequeDetailsSection').slideDown();
            } else {
                $('#chequeDetailsSection').slideUp();
                $('#cheque_no, #cheque_date, #bank_name').val('');
            }
        });

        // Initialize Select2 on header & table row selects
        $('#payFromHead, #payFromAccount').select2({ width: '100%' });
        $('.rowType, .rowParty').select2({ width: '100%' });

        $('#payFromHead').on('change', function() {
            let headId = $(this).val(),
                $acc = $('#payFromAccount');
            $acc.html('<option disabled selected value="">Loading...</option>').trigger('change.select2');
            $('#accountBalanceInfo').slideUp();
            if (headId) {
                $.get('{{ url('get-accounts-by-head') }}/' + headId, function(data) {
                    $acc.empty().append('<option disabled selected value="">— Select Account —</option>');
                    data.forEach(acc => $acc.append(`<option value="${acc.id}" data-balance="${acc.opening_balance}">${acc.title}</option>`));
                    $acc.trigger('change.select2');
                    setTimeout(() => { $acc.select2('open'); }, 100);
                });
            } else {
                $acc.empty().append('<option disabled selected value="">— Select Account —</option>').trigger('change.select2');
            }
        });

        $('#payFromAccount').on('change', function() {
            let balance = $(this).find(':selected').data('balance');
            if (balance !== undefined && balance !== null && $(this).val() !== '') {
                $('#accountBalanceAmount').text(parseFloat(balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#accountBalanceInfo').slideDown();
            } else {
                $('#accountBalanceInfo').slideUp();
            }
        });

        $(document).on('change', '.rowType', function() {
            let type = $(this).val(),
                $row = $(this).closest('tr'),
                $sel = $row.find('.rowParty');
            $sel.html('<option disabled selected value="">Loading...</option>').trigger('change.select2');
            $row.find('.row-balance-display').hide();
            if (type === 'vendor' || type === 'customer' || type === 'walkin') {
                $.get('{{ route('party.list') }}?type=' + type, function(data) {
                    $sel.empty().append('<option disabled selected value="">— Select Party —</option>');
                    data.forEach(item => $sel.append(`<option value="${item.id}" data-bal="${item.closing_balance}">${item.text}</option>`));
                    $sel.trigger('change.select2');
                    setTimeout(() => { $sel.select2('open'); }, 100);
                });
            } else if (type) {
                $.get('{{ url('get-accounts-by-head') }}/' + type, function(data) {
                    $sel.empty().append('<option disabled selected value="">— Select Account —</option>');
                    data.forEach(acc => $sel.append(`<option value="${acc.id}" data-bal="${acc.opening_balance}">${acc.title}</option>`));
                    $sel.trigger('change.select2');
                    setTimeout(() => { $sel.select2('open'); }, 100);
                });
            } else {
                $sel.empty().append('<option disabled selected value="">— Select Party / Account —</option>').trigger('change.select2');
            }
        });

        $(document).on('change', '.rowParty', function() {
            let $row = $(this).closest('tr');
            let bal = $(this).find(':selected').data('bal');
            let $balDiv = $row.find('.row-balance-display');
            if (bal !== undefined && bal !== null && $(this).val() !== '') {
                $balDiv.find('.rowBalanceVal').text(parseFloat(bal).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $balDiv.show();
            } else {
                $balDiv.hide();
            }
        });
        $(document).on('change', '.narrationSelect', function() {
            let $input = $(this).closest('td').find('.narrationInput');
            $(this).val() === '' ? $input.show().focus() : $input.hide().val('');
        });

        function calcTotal() {
            let t = 0;
            $('.amount').each(function() {
                t += parseFloat($(this).val()) || 0;
            });
            $('#totalAmount').val(t.toFixed(2));
        }
        $(document).on('input', '.amount', calcTotal);

        let activeNarrationSelect = null;

        $(document).on('click', '.btn-quick-narration', function() {
            activeNarrationSelect = $(this).closest('td').find('.narrationSelect');
            $('#quick_narration').val('');
            $('#quickNarrationModal').modal('show');
        });

        $('#quickNarrationForm').on('submit', function(e) {
            e.preventDefault();
            let formData = $(this).serialize();
            let saveBtn = $(this).find('button[type="submit"]');
            saveBtn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: '{{ route('narrations.store') }}',
                method: 'POST',
                data: formData,
                success: function(res) {
                    saveBtn.prop('disabled', false).text('Save Narration');
                    if (res.success) {
                        $('.narrationSelect').each(function() {
                            $(this).append(`<option value="${res.id}">${res.narration}</option>`);
                        });
                        if (activeNarrationSelect) {
                            activeNarrationSelect.val(res.id).trigger('change');
                        }
                        $('#quickNarrationModal').modal('hide');
                    } else {
                        alert('Failed to save narration. Please try again.');
                    }
                },
                error: function(xhr) {
                    saveBtn.prop('disabled', false).text('Save Narration');
                    alert('Something went wrong. Please check fields.');
                }
            });
        });

        function newPayRow() {
            let row = `<tr>
            <td>
                <div style="display:flex;gap:5px;flex-direction:column;">
                    <div style="display:flex;gap:5px;align-items:center;">
                        <select name="narration_id[]" class="line-select narrationSelect" style="flex:1;">
                            <!-- Options will be copied from first row -->
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-success btn-quick-narration" title="Quick Add Narration" style="padding: 2px 6px; border-radius: 4px; line-height: 1.2;">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                    <input type="text" class="line-input narrationInput" name="narration_text[]" placeholder="Text...">
                </div>
            </td>
            <td><input type="text" name="reference_no[]" class="line-input"></td>
            <td><select name="vendor_type[]" class="line-select rowType">
                <option disabled selected>Select</option>
                <option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option>
                @foreach ($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach
            </select></td>
            <td><select name="vendor_id[]" class="line-select rowParty"><option disabled selected>Select Party</option></select>
                <div class="row-balance-display" style="display:none; font-size:.78rem; color:#18b870; margin-top:4px; font-weight:700;">
                    <i class="bi bi-wallet2"></i> Balance: Rs. <span class="rowBalanceVal">0.00</span>
                </div>
            </td>
            <td><input type="hidden" name="discount_value[]" value="0"><input type="hidden" name="rate[]" value="0">
                <input type="number" name="amount[]" class="line-input amount" placeholder="0.00" style="text-align:right;font-weight:700;"></td>
            <td><button type="button" class="btn-remove-row removeRow"><i class="bi bi-trash3"></i></button></td>
        </tr>`;
            let $row = $(row);
            let firstSelectOptions = $('#voucherTable tbody tr:first-child .narrationSelect').html();
            $row.find('.narrationSelect').html(firstSelectOptions);
            return $row;
        }
        $('#addNewRow').on('click', function() {
            let $row = newPayRow();
            $('#voucherTable tbody').append($row);
            $row.find('.rowType, .rowParty').select2({ width: '100%' });
        });
        $(document).on('click', '.removeRow', function() {
            if ($('#voucherTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calcTotal();
            }
        });
    </script>
@endsection
@endsection
