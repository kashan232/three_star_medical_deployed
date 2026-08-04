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
            color: #f59e0b;
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
            background: linear-gradient(135deg, #f59e0b, #ef6c00);
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

        .form-card-header .step-badge {
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
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }

        .erp-input:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .12);
        }

        .erp-input[readonly] {
            background: #f7f9fc;
            color: #8897b0;
            cursor: not-allowed;
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
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238897b0' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px;
        }

        .erp-select:focus {
            outline: none;
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .12);
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
            background: #fffdf5;
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
            min-width: 80px;
        }

        .line-input:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .line-select {
            border: 1.5px solid #e2e8f0;
            border-radius: 7px;
            padding: 8px 10px;
            font-size: .84rem;
            width: 100%;
            min-width: 110px;
            background-color: #fff;
        }

        .line-select:focus {
            outline: none;
            border-color: #f59e0b;
        }

        .tfoot-total {
            background: linear-gradient(135deg, #fff8e8, #fff3e6);
            padding: 14px 20px;
            border-top: 2px solid #e8ecf4;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }

        .total-label {
            font-weight: 700;
            color: #f59e0b;
            font-size: .95rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .total-amount-input {
            border: 2px solid #fde68a;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #d97706;
            background: #fff8e8;
            width: 200px;
            text-align: right;
            pointer-events: none;
        }

        .btn-add-row {
            background: #fff8e8;
            color: #f59e0b;
            border: 1.5px dashed #fde68a;
            border-radius: 9px;
            padding: 9px 18px;
            font-size: .86rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .18s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
        }

        .btn-add-row:hover {
            background: #f59e0b;
            color: #fff;
            border-color: #f59e0b;
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
            border-color: #ef4444;
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
            background: linear-gradient(135deg, #f59e0b, #ef6c00);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 700;
            font-size: .95rem;
            box-shadow: 0 4px 14px rgba(245, 158, 11, .35);
            transition: all .22s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, .45);
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

        /* Custom Select2 Styling for Expense Voucher */
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
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.12);
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
            border: 1.5px solid #f59e0b;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.15);
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
            border-color: #f59e0b;
        }

        .select2-container--default .select2-results__option {
            padding: 8px 12px;
            font-size: 0.88rem;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #f59e0b;
            color: #fff;
        }
    </style>

    <div class="vch-create-page">
        <div class="container-fluid px-4">

            <div class="page-topbar">
                <div>
                    <div class="page-title">New Expense Voucher <small>Record an outgoing business expense</small></div>
                    <div class="breadcrumb-bar">
                        <a href="{{ route('all_expense_vochers') }}">Expense Vouchers</a> &rsaquo; New Entry
                    </div>
                </div>
                <a href="{{ route('all_expense_vochers') }}" class="btn-cancel">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>

            @if (session('success'))
                <div class="alert-success-erp"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert-danger-erp" style="background:#fff0f0;border:1.5px solid #f87171;border-radius:10px;padding:14px 18px;margin-bottom:16px;color:#b91c1c;display:flex;align-items:center;gap:10px;font-weight:500;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size:1.2rem;"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <form action="{{ route('store_expense_vochers') }}" method="POST">
                @csrf

                {{-- STEP 1: Voucher Meta --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">1</div>
                        <h6>Voucher Information</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="lbl">Voucher No (EVID)</label>
                                <input type="text" class="erp-input" name="evid" value="{{ $nextRvid }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="lbl">Entry Date</label>
                                <input type="date" name="entry_date" class="erp-input"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-4">
                                <label class="lbl">Paid From (Source Account Head)</label>
                                <div style="display:flex;gap:8px;">
                                    <select name="vendor_type" class="erp-select" id="payFromHead">
                                        <option value="">— Select Head —</option>
                                        @foreach ($AccountHeads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="lbl">Source Account</label>
                                <select name="vendor_id" class="erp-select" id="payFromAccount">
                                    <option disabled selected>— Select Account —</option>
                                </select>
                                <div class="balance-display"
                                    style="display:none;font-size:.8rem;color:#8897b0;margin-top:5px;">
                                    Balance: <strong id="balanceVal">0.00</strong>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="lbl">Reference / Cheque #</label>
                                <input type="text" name="ref_no_header" class="erp-input" placeholder="Optional">
                            </div>
                            <div class="col-md-8">
                                <label class="lbl">Global Remarks</label>
                                <input type="text" name="remarks" class="erp-input"
                                    placeholder="Any general notes for this voucher...">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Expense Line Items --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">2</div>
                        <h6>Expense Line Items</h6>
                    </div>
                    <div class="form-card-body pb-0">
                        <div class="line-table-wrap">
                            <table class="line-table" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width:28%">Expense Account</th>
                                        <th style="width:25%">Narration</th>
                                        <th style="width:18%">Qty / Rate</th>
                                        <th style="width:15%">Amount</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select name="row_account_head[]" class="line-select rowAccountHead"
                                                style="margin-bottom:5px;">
                                                <option value="">Select Head</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                            <select name="row_account_id[]" class="line-select rowAccountSub">
                                                <option value="">Select Expense Account</option>
                                            </select>
                                            <div class="row-balance-display" style="display:none; font-size:.78rem; color:#ef6c00; margin-top:4px; font-weight:700;">
                                                <i class="bi bi-wallet2"></i> Balance: Rs. <span class="rowBalanceVal">0.00</span>
                                            </div>
                                        </td>
                                         <td>
                                             <div style="display:flex;gap:5px;align-items:center;">
                                                 <select name="narration_id[]" class="line-select narrationSelect" style="flex:1;">
                                                     <option value="">Select / Type</option>
                                                     @foreach ($narrations as $id => $name)
                                                         <option value="{{ $id }}">{{ $name }}</option>
                                                     @endforeach
                                                 </select>
                                                 <button type="button" class="btn btn-sm btn-outline-warning btn-quick-narration" title="Quick Add Narration" style="padding: 2px 6px; border-radius: 4px; line-height: 1.2;">
                                                     <i class="bi bi-plus-lg"></i>
                                                 </button>
                                             </div>
                                             <input type="text" class="line-input narrationInput mt-1"
                                                 name="narration_text[]" placeholder="Custom narration...">
                                         </td>
                                        <td>
                                            <div style="display:flex;gap:5px;align-items:center;">
                                                <span style="font-size:.76rem;color:#8897b0;white-space:nowrap;">Qty</span>
                                                <input type="number" name="kg[]" class="line-input kg"
                                                    placeholder="1" style="width:70px;">
                                            </div>
                                            <div style="display:flex;gap:5px;align-items:center;margin-top:5px;">
                                                <span
                                                    style="font-size:.76rem;color:#8897b0;white-space:nowrap;">Rate</span>
                                                <input type="number" name="rate[]" class="line-input rate"
                                                    placeholder="0" style="width:70px;">
                                            </div>
                                        </td>
                                        <td>
                                            <input name="amount[]" type="number" step="0.01"
                                                class="line-input amount" placeholder="0.00"
                                                style="text-align:right;font-weight:700;">
                                            <input type="hidden" name="discount_value[]" value="0">
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
                            <i class="bi bi-plus-lg"></i> Add Expense Line
                        </button>
                        <div class="tfoot-total">
                            <span class="total-label">Total Expense:</span>
                            <input type="text" name="total_amount" class="total-amount-input" id="totalAmount"
                                readonly value="0.00">
                        </div>
                    </div>
                </div>

                <div class="action-footer">
                    <a href="{{ route('all_expense_vochers') }}" class="btn-cancel"><i class="bi bi-x-lg"></i>
                        Cancel</a>
                    <button type="submit" class="btn-save"><i class="bi bi-save2"></i> Save Expense Voucher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Add Narration Modal -->
    <div class="modal fade" id="quickNarrationModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow border-0">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #f59e0b, #ef6c00);">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle-fill"></i> Quick Add Narration</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="quickNarrationForm">
                    @csrf
                    <input type="hidden" name="expense_head" id="quick_expense_head" value="Expense voucher">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">Voucher Type</label>
                            <input type="text" class="form-control bg-light border-0" value="Expense Voucher" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="quick_narration" class="form-label fw-bold text-secondary">Narration / Description</label>
                            <textarea class="form-control" name="narration" id="quick_narration" rows="3" required placeholder="Type narration text here..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light">
                        <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn text-white px-4 fw-bold" style="background: linear-gradient(135deg, #f59e0b, #ef6c00);">Save Narration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@section('js')
    <script>
        $(document).on('change', '.narrationSelect', function() {
            let $input = $(this).closest('td').find('.narrationInput');
            $(this).val() === '' ? $input.show().focus().attr('name', 'narration_text[]') : $input.hide().val('')
                .attr('name', 'narration_text_dummy');
        });
        // Initialize Select2 on header & table row selects
        $('#payFromHead, #payFromAccount').select2({ width: '100%' });
        $('.rowAccountHead, .rowAccountSub').select2({ width: '100%' });

        $('#payFromHead').on('change', function() {
            let headId = $(this).val();
            let $acc = $('#payFromAccount');
            $acc.html('<option disabled selected value="">Loading...</option>').trigger('change.select2');
            $('.balance-display').hide();
            if (headId) {
                $.get('{{ url('get-accounts-by-head') }}/' + headId, function(data) {
                    $acc.empty().append('<option disabled selected value="">— Select Account —</option>');
                    data.forEach(function(acc) {
                        $acc.append(
                            `<option value="${acc.id}" data-bal="${acc.opening_balance}">${acc.title}</option>`
                        );
                    });
                    $acc.trigger('change.select2');
                    setTimeout(() => { $acc.select2('open'); }, 100);
                });
            } else {
                $acc.empty().append('<option disabled selected value="">— Select Account —</option>').trigger('change.select2');
            }
        });

        $('#payFromAccount').on('change', function() {
            let bal = $(this).find(':selected').data('bal');
            if (bal !== undefined && bal !== null && $(this).val() !== '') {
                $('#balanceVal').text(parseFloat(bal).toFixed(2));
                $('.balance-display').show();
            } else {
                $('.balance-display').hide();
            }
        });

        $(document).on('change', '.rowAccountHead', function() {
            let headId = $(this).val();
            let $row = $(this).closest('tr');
            let $sub = $row.find('.rowAccountSub');
            $row.find('.row-balance-display').hide();
            if (!headId) {
                $sub.html('<option value="">Select Account</option>').trigger('change.select2');
                return;
            }
            $sub.html('<option disabled selected value="">Loading...</option>').trigger('change.select2');
            $.get('{{ url('get-accounts-by-head') }}/' + headId, function(res) {
                let html = '<option value="">Select Account</option>';
                res.forEach(acc => {
                    html += `<option value="${acc.id}" data-bal="${acc.opening_balance}">${acc.title}</option>`;
                });
                $sub.html(html).trigger('change.select2');
                setTimeout(() => { $sub.select2('open'); }, 100);
            });
        });

        $(document).on('change', '.rowAccountSub', function() {
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

        function calcTotal() {
            let t = 0;
            $('.amount').each(function() {
                t += parseFloat($(this).val()) || 0;
            });
            $('#totalAmount').val(t.toFixed(2));
        }
        $(document).on('input', '.kg, .rate', function() {
            let row = $(this).closest('tr');
            let kg = parseFloat(row.find('.kg').val()) || 0;
            let rate = parseFloat(row.find('.rate').val()) || 0;
            if (kg > 0 && rate > 0) row.find('.amount').val((kg * rate).toFixed(2));
            calcTotal();
        });
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

        function newExpenseRow() {
            let row = `
        <tr>
            <td>
                <select name="row_account_head[]" class="line-select rowAccountHead" style="margin-bottom:5px;">
                    <option value="">Select Head</option>
                    @foreach ($AccountHeads as $head)
                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                    @endforeach
                </select>
                <select name="row_account_id[]" class="line-select rowAccountSub">
                    <option value="">Select Expense Account</option>
                </select>
                <div class="row-balance-display" style="display:none; font-size:.78rem; color:#ef6c00; margin-top:4px; font-weight:700;">
                    <i class="bi bi-wallet2"></i> Balance: Rs. <span class="rowBalanceVal">0.00</span>
                </div>
            </td>
            <td>
                <div style="display:flex;gap:5px;align-items:center;">
                    <select name="narration_id[]" class="line-select narrationSelect" style="flex:1;">
                        <!-- Options will be copied from first row -->
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-warning btn-quick-narration" title="Quick Add Narration" style="padding: 2px 6px; border-radius: 4px; line-height: 1.2;">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <input type="text" class="line-input narrationInput mt-1" name="narration_text[]" placeholder="Custom narration...">
            </td>
            <td>
                <div style="display:flex;gap:5px;align-items:center;"><span style="font-size:.76rem;color:#8897b0;">Qty</span><input type="number" name="kg[]" class="line-input kg" placeholder="1" style="width:70px;"></div>
                <div style="display:flex;gap:5px;align-items:center;margin-top:5px;"><span style="font-size:.76rem;color:#8897b0;">Rate</span><input type="number" name="rate[]" class="line-input rate" placeholder="0" style="width:70px;"></div>
            </td>
            <td>
                <input name="amount[]" type="number" step="0.01" class="line-input amount" placeholder="0.00" style="text-align:right;font-weight:700;">
                <input type="hidden" name="discount_value[]" value="0">
            </td>
            <td><button type="button" class="btn-remove-row removeRow"><i class="bi bi-trash3"></i></button></td>
        </tr>`;
            let $row = $(row);
            let firstSelectOptions = $('#voucherTable tbody tr:first-child .narrationSelect').html();
            $row.find('.narrationSelect').html(firstSelectOptions);
            return $row;
        }
        $('#addNewRow').on('click', function() {
            let $row = newExpenseRow();
            $('#voucherTable tbody').append($row);
            $row.find('.rowAccountHead, .rowAccountSub').select2({ width: '100%' });
        });
        $(document).on('click', '.removeRow', function() {
            if ($('#voucherTable tbody tr').length > 1) {
                $(this).closest('tr').remove();
                calcTotal();
            }
        });
        $(document).on('keypress', '.amount', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#addNewRow').click();
            }
        });
    </script>
@endsection
@endsection
