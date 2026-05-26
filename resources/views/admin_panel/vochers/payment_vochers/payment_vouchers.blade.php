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
                                            <div style="display:flex;gap:5px;">
                                                <select name="narration_id[]" class="line-select narrationSelect">
                                                    <option value="">Select / Add</option>
                                                    @foreach ($narrations as $id => $name)
                                                        <option value="{{ $id }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" class="line-input narrationInput"
                                                    name="narration_text[]" style="display:none;" placeholder="Text...">
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

        $('#payFromHead').on('change', function() {
            let headId = $(this).val(),
                $acc = $('#payFromAccount');
            $acc.html('<option disabled selected>Loading...</option>');
            if (headId) {
                $.get('{{ url('get-accounts-by-head') }}/' + headId, function(data) {
                    $acc.empty().append('<option disabled selected>— Select Account —</option>');
                    data.forEach(acc => $acc.append(`<option value="${acc.id}">${acc.title}</option>`));
                });
            }
        });
        $(document).on('change', '.rowType', function() {
            let type = $(this).val(),
                $row = $(this).closest('tr'),
                $sel = $row.find('.rowParty');
            $sel.html('<option disabled selected>Loading...</option>');
            if (type === 'vendor' || type === 'customer' || type === 'walkin') {
                $.get('{{ route('party.list') }}?type=' + type, function(data) {
                    $sel.empty().append('<option disabled selected>Select</option>');
                    data.forEach(item => $sel.append(`<option value="${item.id}">${item.text}</option>`));
                });
            } else if (type) {
                $.get('{{ url('get-accounts-by-head') }}/' + type, function(data) {
                    $sel.empty().append('<option disabled selected>Select</option>');
                    data.forEach(acc => $sel.append(`<option value="${acc.id}">${acc.title}</option>`));
                });
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

        function newPayRow() {
            return `<tr>
            <td><div style="display:flex;gap:5px;">
                <select name="narration_id[]" class="line-select narrationSelect"><option value="">Select/Add</option>@foreach ($narrations as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                <input type="text" class="line-input narrationInput" name="narration_text[]" style="display:none;" placeholder="Text...">
            </div></td>
            <td><input type="text" name="reference_no[]" class="line-input"></td>
            <td><select name="vendor_type[]" class="line-select rowType">
                <option disabled selected>Select</option>
                <option value="vendor">Vendor</option><option value="customer">Customer</option><option value="walkin">Walkin</option>
                @foreach ($AccountHeads as $head)<option value="{{ $head->id }}">{{ $head->name }}</option>@endforeach
            </select></td>
            <td><select name="vendor_id[]" class="line-select rowParty"><option disabled selected>Select Party</option></select></td>
            <td><input type="hidden" name="discount_value[]" value="0"><input type="hidden" name="rate[]" value="0">
                <input type="number" name="amount[]" class="line-input amount" placeholder="0.00" style="text-align:right;font-weight:700;"></td>
            <td><button type="button" class="btn-remove-row removeRow"><i class="bi bi-trash3"></i></button></td>
        </tr>`;
        }
        $('#addNewRow').on('click', function() {
            $('#voucherTable tbody').append(newPayRow());
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
