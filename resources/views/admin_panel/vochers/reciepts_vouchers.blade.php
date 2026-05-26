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
            color: #4f6ef7;
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
            background: linear-gradient(135deg, #4f6ef7, #7b5ef8);
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
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }

        .erp-input:focus {
            outline: none;
            border-color: #4f6ef7;
            box-shadow: 0 0 0 3px rgba(79, 110, 247, .12);
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
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238897b0' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 12px center;
            padding-right: 36px;
            appearance: none;
        }

        .erp-select:focus {
            outline: none;
            border-color: #4f6ef7;
            box-shadow: 0 0 0 3px rgba(79, 110, 247, .12);
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
            background: #f7f9ff;
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
            border-color: #4f6ef7;
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
            border-color: #4f6ef7;
        }

        .tfoot-total {
            background: linear-gradient(135deg, #f7f9ff, #eef2ff);
            padding: 14px 20px;
            border-top: 2px solid #e8ecf4;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 16px;
        }

        .total-label {
            font-weight: 700;
            color: #4f6ef7;
            font-size: .95rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .total-amount-input {
            border: 2px solid #c7d2fe;
            border-radius: 10px;
            padding: 10px 18px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #4f6ef7;
            background: #eef2ff;
            width: 200px;
            text-align: right;
            pointer-events: none;
        }

        .btn-add-row {
            background: #eef2ff;
            color: #4f6ef7;
            border: 1.5px dashed #c7d2fe;
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
            background: #4f6ef7;
            color: #fff;
            border-color: #4f6ef7;
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
            transition: all .15s;
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
            background: linear-gradient(135deg, #4f6ef7, #7b5ef8);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 32px;
            font-weight: 700;
            font-size: .95rem;
            box-shadow: 0 4px 14px rgba(79, 110, 247, .35);
            transition: all .22s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 110, 247, .45);
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
            transition: all .18s;
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
    </style>

    <div class="vch-create-page">
        <div class="container-fluid px-4">
            <div class="page-topbar">
                <div>
                    <div class="page-title">New Receipt Voucher <small>Fill in the details to record an incoming
                            payment</small></div>
                    <div class="breadcrumb-bar"><a href="{{ route('all_recepit_vochers') }}">Receipt Vouchers</a> &rsaquo;
                        New Entry</div>
                </div>
                <a href="{{ route('all_recepit_vochers') }}" class="btn-cancel"><i class="bi bi-arrow-left"></i> Back to
                    List</a>
            </div>

            @if (session('success'))
                <div class="alert-success-erp"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            <form action="{{ route('store_rec_vochers') }}" method="POST">
                @csrf

                {{-- STEP 1: Voucher Info --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">1</div>
                        <h6>Voucher Information</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label class="lbl">Voucher No (RVID)</label>
                                <input type="text" class="erp-input" name="rvid" value="{{ $nextRvid }}" readonly>
                            </div>
                            <div class="col-md-2">
                                <label class="lbl">Payment Mode</label>
                                <select name="payment_mode" class="erp-select" id="paymentMode">
                                    <option value="cash">Cash</option>
                                    <option value="cheque">Cheque</option>
                                </select>
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
                            <div class="col-md-4">
                                <label class="lbl">Remarks / Notes</label>
                                <input type="text" name="remarks" class="erp-input" id="remarks"
                                    placeholder="Optional remarks...">
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
                                <label class="lbl text-warning">Drawing Bank</label>
                                <input type="text" name="bank_name" id="bank_name" class="erp-input"
                                    placeholder="e.g. Meezan Bank">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: Source Party --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">2</div>
                        <h6>Source Party Details</h6>
                    </div>
                    <div class="form-card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="lbl">Party Type</label>
                                <select name="vendor_type" class="erp-select" id="partyType">
                                    <option value="">— Select Type —</option>
                                    <option value="customer">Customer</option>
                                    <option value="walkin">Walk-in</option>
                                    <option value="vendor">Vendor</option>
                                    @foreach ($AccountHeads as $head)
                                        <option value="{{ $head->id }}">{{ $head->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="lbl">Party / Account</label>
                                <select name="vendor_id" class="erp-select" id="partyId">
                                    <option disabled selected>— Select Party —</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="lbl">Tel / Account Code</label>
                                <input type="text" name="tel" id="tel" class="erp-input" readonly
                                    placeholder="Auto-filled">
                            </div>
                            <div class="col-md-3">
                                <label class="lbl">Opening Balance</label>
                                <input type="text" id="openingBal" class="erp-input" readonly
                                    placeholder="Auto-filled">
                            </div>
                            <div class="col-md-4" id="saleSection" style="display:none;">
                                <label class="lbl" style="color:#4f6ef7;">Link to Sale (Optional)</label>
                                <select name="sale_id" class="erp-select" id="saleId">
                                    <option value="">General Payment</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STEP 3: Line Items (No Rate / No Discount) --}}
                <div class="form-card">
                    <div class="form-card-header">
                        <div class="step-badge">3</div>
                        <h6>Destination Accounts (Line Items)</h6>
                    </div>
                    <div class="form-card-body pb-0">
                        <div class="line-table-wrap">
                            <table class="line-table" id="voucherTable">
                                <thead>
                                    <tr>
                                        <th style="width:30%">Narration</th>
                                        <th style="width:15%">Reference #</th>
                                        <th style="width:22%">Account Head</th>
                                        <th style="width:22%">Account</th>
                                        <th style="width:10%">Amount</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <div style="display:flex;gap:5px;">
                                                <select name="narration_id[]" class="line-select narrationSelect">
                                                    <option value="">Select / Add</option>
                                                    @foreach ($narrations as $id => $name)
                                                        <option value="{{ $id }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" class="line-input narrationInput"
                                                    name="narration_text[]" placeholder="Custom...">
                                            </div>
                                        </td>
                                        <td><input type="text" name="reference_no[]" class="line-input"></td>
                                        <td>
                                            <select name="row_account_head[]" class="line-select rowAccountHead">
                                                <option value="">Select Head</option>
                                                @foreach ($AccountHeads as $head)
                                                    <option value="{{ $head->id }}">{{ $head->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="row_account_id[]" class="line-select rowAccountSub">
                                                <option disabled selected>Select Account</option>
                                            </select>
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
                            <i class="bi bi-plus-lg"></i> Add Line Item
                        </button>
                        <div class="tfoot-total">
                            <span class="total-label">Grand Total:</span>
                            <input type="text" name="total_amount" class="total-amount-input" id="totalAmount"
                                readonly value="0.00">
                        </div>
                    </div>
                </div>

                <div class="action-footer">
                    <a href="{{ route('all_recepit_vochers') }}" class="btn-cancel"><i class="bi bi-x-lg"></i>
                        Cancel</a>
                    <button type="submit" class="btn-save"><i class="bi bi-save2"></i> Save Receipt Voucher</button>
                </div>
            </form>
        </div>
    </div>

@section('js')
    <script>
        // Toggle Cheque Fields based on Payment Mode
        $('#paymentMode').on('change', function() {
            if ($(this).val() === 'cheque') {
                $('#chequeDetailsSection').slideDown();
                $('#cheque_no, #cheque_date, #bank_name').prop('required', true);
            } else {
                $('#chequeDetailsSection').slideUp();
                $('#cheque_no, #cheque_date, #bank_name').prop('required', false).val('');
            }
        });

        $('#partyType').on('change', function() {
            let type = $(this).val(),
                $select = $('#partyId');
            $select.html('<option disabled selected>Loading...</option>');
            $('#tel').val('');
            $('#openingBal').val('');
            if (type === 'vendor' || type === 'customer' || type === 'walkin') {
                $.get('{{ route('party.list') }}?type=' + type, function(data) {
                    $select.empty().append('<option disabled selected>— Select Party —</option>');
                    data.forEach(item => $select.append(
                        `<option value="${item.id}" data-phone="${item.mobile||''}" data-bal="${item.closing_balance}">${item.text}</option>`
                    ));
                });
            } else if (type) {
                $.get('{{ url('get-accounts-by-head') }}/' + type, function(data) {
                    $select.empty().append('<option disabled selected>— Select Account —</option>');
                    data.forEach(acc => $select.append(
                        `<option value="${acc.id}" data-phone="${acc.account_code}" data-bal="${acc.opening_balance}">${acc.title}</option>`
                    ));
                });
            }
        });
        $('#partyId').on('change', function() {
            let $opt = $(this).find(':selected');
            $('#tel').val($opt.data('phone'));
            $('#openingBal').val($opt.data('bal'));
            let id = $(this).val(),
                type = $('#partyType').val();
            if (type === 'customer' || type === 'walkin') {
                $('#saleSection').fadeIn();
                $.get('{{ route('customer.unpaid.sales') }}?customer_id=' + id, function(res) {
                    let $s = $('#saleId');
                    $s.html('<option value="">General Payment (No Commission)</option>');
                    if (res.sales && res.sales.length > 0) res.sales.forEach(sale => $s.append(
                        `<option value="${sale.id}">Invoice #${sale.invoice_no} (Total: ${sale.total_net}, Due: ${sale.due_amount})</option>`
                    ));
                });
            } else {
                $('#saleSection').hide();
                $('#saleId').html('<option value="">General Payment</option>');
            }
            $.get('{{ route('salecustomers.show', ['id' => '__ID__']) }}'.replace('__ID__', id) + '?type=' + type,
                function(d) {
                    if (d.remarks && !$('#remarks').val()) $('#remarks').val(d.remarks);
                });
        });
        $(document).on('change', '.rowAccountHead', function() {
            let headId = $(this).val(),
                $sub = $(this).closest('tr').find('.rowAccountSub');
            if (!headId) {
                $sub.html('<option value="">Select Account</option>');
                return;
            }
            $.get('{{ url('get-accounts-by-head') }}/' + headId, function(res) {
                let html = '<option value="">Select Account</option>';
                res.forEach(acc => {
                    html += `<option value="${acc.id}">${acc.title}</option>`;
                });
                $sub.html(html);
            });
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

        function newRowHtml() {
            return `<tr>
            <td><div style="display:flex;gap:5px;">
                <select name="narration_id[]" class="line-select narrationSelect"><option value="">Select / Add</option>@foreach ($narrations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                <input type="text" class="line-input narrationInput" name="narration_text[]" placeholder="Custom...">
            </div></td>
            <td><input type="text" name="reference_no[]" class="line-input"></td>
            <td><select name="row_account_head[]" class="line-select rowAccountHead"><option value="">Select Head</option>@foreach ($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach</select></td>
            <td><select name="row_account_id[]" class="line-select rowAccountSub"><option disabled selected>Select Account</option></select></td>
            <td><input type="hidden" name="discount_value[]" value="0"><input type="hidden" name="rate[]" value="0"><input type="number" name="amount[]" class="line-input amount" placeholder="0.00" style="text-align:right;font-weight:700;"></td>
            <td><button type="button" class="btn-remove-row removeRow"><i class="bi bi-trash3"></i></button></td>
        </tr>`;
        }
        $('#addNewRow').on('click', function() {
            $('#voucherTable tbody').append(newRowHtml());
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
