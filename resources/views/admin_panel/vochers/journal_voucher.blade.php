@extends('admin_panel.layout.app')
@section('content')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <div class="main-content">
        <div class="container-fluid py-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-journal-bookmark-fill text-primary me-2"></i> Journal Voucher
                        <button type="button"
                            class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 ms-2 rounded-pill px-3 shadow-none"
                            data-toggle="modal" data-target="#journalVoucherInfoModal" title="How to use Journal Vouchers?">
                            <i class="bi bi-info-circle"></i> Info
                        </button>
                    </h4>
                    <p class="text-muted mb-0 small">Create a balanced double-entry ledger entry (Debit = Credit)</p>
                </div>
                <a href="{{ route('all_recepit_vochers') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show rounded-3 d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ route('journal.voucher.store') }}" method="POST" id="journalForm">
                        @csrf

                        {{-- Header --}}
                        <div class="row g-3 mb-4 pb-3 border-bottom">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-secondary small">JVID</label>
                                <input type="text" class="form-control bg-light fw-bold font-monospace"
                                    value="{{ $nextJVID }}" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold text-secondary small">Voucher Date <span
                                        class="text-danger">*</span></label>
                                <input type="date" name="voucher_date" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary small">Remarks / Narration</label>
                                <input type="text" name="remarks" class="form-control"
                                    placeholder="e.g., Depreciation entry for March">
                            </div>
                        </div>

                        {{-- Balance Indicator --}}
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="rounded-3 px-4 py-2 bg-primary-subtle border border-primary-subtle">
                                        <small class="text-secondary d-block">Total Debit</small>
                                        <span class="fw-bold text-primary fs-5" id="totalDebit">0.00</span>
                                    </div>
                                    <i class="bi bi-arrow-left-right text-muted fs-4"></i>
                                    <div class="rounded-3 px-4 py-2 bg-warning-subtle border border-warning-subtle">
                                        <small class="text-secondary d-block">Total Credit</small>
                                        <span class="fw-bold text-warning fs-5" id="totalCredit">0.00</span>
                                    </div>
                                    <div class="rounded-3 px-4 py-2 ms-2" id="balanceStatus"
                                        style="background:#f0fdf4; border:1px solid #bbf7d0;">
                                        <small class="text-secondary d-block">Status</small>
                                        <span class="fw-bold text-success" id="balanceLabel"><i
                                                class="bi bi-check-circle"></i> Balanced</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Journal Rows Table --}}
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="journalTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th style="width:35%">Account <span class="text-danger">*</span></th>
                                        <th style="width:30%">Narration / Description</th>
                                        <th style="width:15%" class="text-end">Debit (Dr)</th>
                                        <th style="width:15%" class="text-end">Credit (Cr)</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="journalBody">
                                    {{-- Row 1 (Debit row) --}}
                                    <tr class="journal-row">
                                        <td>
                                            <select name="rows[0][account_id]" class="form-select account-select" required>
                                                <option value="">-- Select Account --</option>
                                                @foreach ($accounts->groupBy(function ($a) {
            return optional($a->head)->name ?? 'Uncategorized';
        }) as $headName => $accs)
                                                    <optgroup label="{{ $headName }}">
                                                        @foreach ($accs as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}
                                                                ({{ $acc->account_code ?: $acc->id }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="rows[0][narration]" class="form-control"
                                                placeholder="Description..."></td>
                                        <td><input type="number" name="rows[0][debit]"
                                                class="form-control text-end debit-input" value="0" step="0.01"
                                                min="0"></td>
                                        <td><input type="number" name="rows[0][credit]"
                                                class="form-control text-end credit-input" value="0" step="0.01"
                                                min="0"></td>
                                        <td class="text-center"><button type="button"
                                                class="btn btn-sm btn-outline-danger remove-row" title="Remove"><i
                                                    class="bi bi-trash"></i></button></td>
                                    </tr>
                                    {{-- Row 2 (Credit row) --}}
                                    <tr class="journal-row">
                                        <td>
                                            <select name="rows[1][account_id]" class="form-select account-select"
                                                required>
                                                <option value="">-- Select Account --</option>
                                                @foreach ($accounts->groupBy(function ($a) {
            return optional($a->head)->name ?? 'Uncategorized';
        }) as $headName => $accs)
                                                    <optgroup label="{{ $headName }}">
                                                        @foreach ($accs as $acc)
                                                            <option value="{{ $acc->id }}">{{ $acc->title }}
                                                                ({{ $acc->account_code ?: $acc->id }})
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="rows[1][narration]" class="form-control"
                                                placeholder="Description..."></td>
                                        <td><input type="number" name="rows[1][debit]"
                                                class="form-control text-end debit-input" value="0" step="0.01"
                                                min="0"></td>
                                        <td><input type="number" name="rows[1][credit]"
                                                class="form-control text-end credit-input" value="0" step="0.01"
                                                min="0"></td>
                                        <td class="text-center"><button type="button"
                                                class="btn btn-sm btn-outline-danger remove-row" title="Remove"><i
                                                    class="bi bi-trash"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <button type="button" class="btn btn-outline-success btn-sm px-3" id="addRow">
                                <i class="bi bi-plus-circle"></i> Add Row
                            </button>
                            <div class="d-flex gap-2">
                                <a href="{{ route('journal.voucher') }}" class="btn btn-light px-4">Reset</a>
                                <button type="submit" class="btn btn-primary px-5 fw-bold" id="submitBtn">
                                    <i class="bi bi-save"></i> Post Journal Voucher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Journal Voucher Info Modal --}}
    <div class="modal fade" id="journalVoucherInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-info ms-2"><i class="bi bi-info-circle me-2"></i> How to use
                        Manual Journal Vouchers</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"
                        style="background:none;border:none;font-size:1.5rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3 text-dark">
                    <p class="small text-muted mb-3">A Journal Voucher allows you to manually create custom financial
                        transactions (Journal Entries) that the automated system doesn't handle natively. This is typically
                        used by accountants for manual adjustments.</p>

                    <h6 class="fw-bold tracking-wide text-uppercase mb-2 text-primary" style="font-size: 0.85rem;">The
                        Golden Rule: Balance</h6>
                    <div class="alert alert-light border shadow-sm rounded-3 mb-4">
                        <ul class="mb-0 ps-3 small text-dark" style="line-height: 1.6;">
                            <li><strong>Total Debits MUST equal Total Credits.</strong> A Journal Voucher cannot be saved if
                                the bottom indicators show you are unbalanced. Every action requires an equal and opposite
                                reaction.</li>
                        </ul>
                    </div>

                    <h6 class="fw-bold tracking-wide text-uppercase mb-2 text-primary" style="font-size: 0.85rem;">Common
                        Use Cases for Manual Vouchers</h6>
                    <div class="table-responsive small mb-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Scenario</th>
                                    <th class="text-success">Debit (Dr)</th>
                                    <th class="text-danger">Credit (Cr)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>1. Initial Capital Investment</strong><br><span class="text-muted">Owner
                                            puts cash into the business</span></td>
                                    <td>Cash in Hand</td>
                                    <td>Owner's Equity / Capital</td>
                                </tr>
                                <tr>
                                    <td><strong>2. Depreciation of Assets</strong><br><span class="text-muted">Reducing
                                            value of equipment over time</span></td>
                                    <td>Depreciation Expense</td>
                                    <td>Accumulated Depreciation</td>
                                </tr>
                                <tr>
                                    <td><strong>3. Bank Reconciliations</strong><br><span class="text-muted">Bank charged a
                                            fee automatically</span></td>
                                    <td>Bank Fees Expense</td>
                                    <td>Bank Account</td>
                                </tr>
                                <tr>
                                    <td><strong>4. Write-Offs / Bad Debt</strong><br><span class="text-muted">Customer goes
                                            bankrupt, unpaid debt</span></td>
                                    <td>Bad Debt Expense</td>
                                    <td>Accounts Receivable</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-primary fw-medium px-4 rounded-pill shadow-sm"
                        data-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @php
        $accountOptionsHtml = '';
        $groupedAccounts = $accounts->groupBy(function ($a) {
            return optional($a->head)->name ?? 'Uncategorized';
        });
        foreach ($groupedAccounts as $grpHead => $grpAccs) {
            $accountOptionsHtml .= '<optgroup label="' . e($grpHead) . '">';
            foreach ($grpAccs as $grpAcc) {
                $code = $grpAcc->account_code ?: $grpAcc->id;
                $accountOptionsHtml .=
                    '<option value="' . $grpAcc->id . '">' . e($grpAcc->title) . ' (' . e($code) . ')</option>';
            }
            $accountOptionsHtml .= '</optgroup>';
        }
    @endphp
    <script>
        const accountOptionsHtml = @json($accountOptionsHtml);


        let rowIndex = 2;

        function recalculate() {
            let totalD = 0,
                totalC = 0;
            $('.debit-input').each(function() {
                totalD += parseFloat($(this).val()) || 0;
            });
            $('.credit-input').each(function() {
                totalC += parseFloat($(this).val()) || 0;
            });

            $('#totalDebit').text(totalD.toFixed(2));
            $('#totalCredit').text(totalC.toFixed(2));

            const balanced = Math.abs(totalD - totalC) < 0.01 && totalD > 0;
            if (balanced) {
                $('#balanceStatus').css({
                    'background': '#f0fdf4',
                    'border': '1px solid #bbf7d0'
                });
                $('#balanceLabel').html(
                    '<i class="bi bi-check-circle-fill text-success"></i> <span class="text-success">Balanced</span>');
                $('#submitBtn').prop('disabled', false);
            } else {
                $('#balanceStatus').css({
                    'background': '#fff1f2',
                    'border': '1px solid #ffd6d6'
                });
                const diff = Math.abs(totalD - totalC).toFixed(2);
                $('#balanceLabel').html(
                    `<i class="bi bi-x-circle-fill text-danger"></i> <span class="text-danger">Off by ${diff}</span>`);
                $('#submitBtn').prop('disabled', totalD <= 0);
            }
        }

        $(document).on('input', '.debit-input, .credit-input', recalculate);

        $('#addRow').on('click', function() {
            const newRow = `
        <tr class="journal-row">
            <td>
                <select name="rows[${rowIndex}][account_id]" class="form-select account-select" required>
                    <option value="">-- Select Account --</option>
                    ${accountOptionsHtml}
                </select>
            </td>
            <td><input type="text" name="rows[${rowIndex}][narration]" class="form-control" placeholder="Description..."></td>
            <td><input type="number" name="rows[${rowIndex}][debit]" class="form-control text-end debit-input" value="0" step="0.01" min="0"></td>
            <td><input type="number" name="rows[${rowIndex}][credit]" class="form-control text-end credit-input" value="0" step="0.01" min="0"></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
        </tr>`;
            $('#journalBody').append(newRow);
            rowIndex++;
            recalculate();
        });

        $(document).on('click', '.remove-row', function() {
            if ($('.journal-row').length <= 2) {
                alert('A journal voucher must have at least 2 rows.');
                return;
            }
            $(this).closest('tr').remove();
            recalculate();
        });

        recalculate();
    </script>
@endsection
