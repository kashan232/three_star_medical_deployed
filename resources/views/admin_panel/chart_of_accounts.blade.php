@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            Chart Of Accounts
                            <button
                                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 ms-2 rounded-pill px-3 shadow-none"
                                data-toggle="modal" data-target="#coaInfoModal" title="How does this work?">
                                <i class="fas fa-info-circle"></i> How it works?
                            </button>
                        </h4>
                        <p class="text-muted mb-0 small">Manage your financial accounts and categories</p>
                    </div>
                    @can('chart.of.accounts.create')
                        <div class="d-flex gap-2 align-items-center">

                            {{-- Smart Setup Button: only shows if any critical account missing --}}
                            @if ($anyMissing)
                                <button class="btn btn-warning d-flex align-items-center gap-2 fw-semibold" data-toggle="modal"
                                    data-target="#setupCOAModal">
                                    <i class="fas fa-magic"></i> Auto-Setup COA
                                    <span class="badge bg-danger rounded-pill">
                                        {{ $criticalCOA->where('complete', false)->count() }}
                                    </span>
                                </button>
                            @endif

                            <a href="{{ route('journal.voucher') }}"
                                class="btn btn-outline-primary d-flex align-items-center gap-2">
                                <i class="fas fa-book"></i> Journal Voucher
                            </a>
                            <button class="btn btn-primary px-4 shadow-sm fw-medium d-flex align-items-center gap-2"
                                data-toggle="modal" data-target="#addAccountModal">
                                <i class="fas fa-plus"></i> Add New Account
                            </button>
                            <button class="btn btn-outline-secondary d-flex align-items-center gap-2" data-toggle="modal"
                                data-target="#addHeadModal">
                                <i class="fas fa-folder-plus"></i> Add Category
                            </button>
                        </div>
                    @endcan
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger rounded-3 mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-hover align-middle datanew" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 rounded-start text-secondary fw-semibold text-uppercase small"
                                            style="width: 5%">#</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 10%">
                                            Code</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 15%">
                                            Head / Group</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 20%">
                                            Account Title</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 8%">
                                            Type</th>
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 12%">
                                            Balance</th>
                                        @if (auth()->user()->isSuperAdmin())
                                            <th class="py-3 text-secondary fw-semibold text-uppercase small"
                                                style="width: 10%">
                                                Branch</th>
                                        @endif
                                        <th class="py-3 text-secondary fw-semibold text-uppercase small" style="width: 8%">
                                            Status</th>
                                        <th class="py-3 pe-3 rounded-end text-secondary fw-semibold text-uppercase small text-center"
                                            style="width: 15%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accounts as $acc)
                                        <tr class="border-bottom-0">
                                            <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <span
                                                    class="badge bg-light text-dark border font-monospace">{{ $acc->account_code ?? 'N/A' }}</span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $acc->head->name ?? '-' }}</span>
                                                @if ($acc->head && $acc->head->parent_id)
                                                    <small class="text-muted d-block"
                                                        style="font-size: 0.8em;">({{ $acc->head->parent->name ?? '' }})</small>
                                                @endif
                                            </td>
                                            <td class="fw-medium text-dark">{{ $acc->title }}</td>
                                            <td>
                                                @if ($acc->type == 'Debit')
                                                    <span
                                                        class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Debit</span>
                                                @else
                                                    <span
                                                        class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Credit</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $bal = $acc->calculated_balance;
                                                    $isNegative = $bal < 0;
                                                    // Determine the sign:
                                                    // If normal type is Debit, and balance > 0, it's Dr. If < 0, it's Cr.
                                                    // If normal type is Credit, and balance > 0, it's Cr. If < 0, it's Dr.
                                                    $displaySuffix =
                                                        $acc->type === 'Debit'
                                                            ? ($isNegative
                                                                ? 'Cr'
                                                                : 'Dr')
                                                            : ($isNegative
                                                                ? 'Dr'
                                                                : 'Cr');
                                                @endphp
                                                <div class="{{ $isNegative ? 'text-danger' : 'text-success' }} fw-bold">
                                                    {{ number_format(abs($bal), 2) }}
                                                    <small
                                                        class="text-secondary fw-normal ms-1">{{ $displaySuffix }}</small>
                                                </div>
                                            </td>
                                            @if (auth()->user()->isSuperAdmin())
                                                <td>
                                                    <span
                                                        class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2">
                                                        {{ $acc->branch->name ?? 'Head Office' }}
                                                    </span>
                                                </td>
                                            @endif
                                            <td>
                                                @if ($acc->status)
                                                    <span
                                                        class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Active</span>
                                                @else
                                                    <span
                                                        class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('accounts.ledger', $acc->id) }}"
                                                        class="btn btn-sm btn-outline-info d-flex align-items-center gap-1"
                                                        title="View Ledger">
                                                        <i class="fas fa-book"></i> Ledger
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1 edit-account-btn"
                                                        data-id="{{ $acc->id }}" data-title="{{ $acc->title }}"
                                                        data-type="{{ $acc->type }}" data-head="{{ $acc->head_id }}"
                                                        data-balance="{{ $acc->opening_balance }}" title="Edit Account">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form action="{{ route('accounts.toggleStatus', $acc->id) }}"
                                                        method="POST" style="display:inline-block;">
                                                        @csrf
                                                        <button type="button" onclick="this.closest('form').submit()"
                                                            class="btn btn-sm {{ $acc->status ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                                            title="{{ $acc->status ? 'Deactivate' : 'Activate' }}">
                                                            <i
                                                                class="fas {{ $acc->status ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Add New Account Modal -->
                <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog"
                    aria-labelledby="addAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4" action="{{ route('accounts.store') }}"
                            method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold ms-2" id="addAccountModalLabel">Add New Account</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">
                                <p class="text-muted small mb-4 ms-1">Create a new financial account.</p>

                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Select Head (Category)</label>
                                    <select class="form-control" name="head_id" required style="height: 45px;">
                                        <option value="">Select Head</option>
                                        @foreach ($heads as $head)
                                            <option value="{{ $head->id }}">{{ $head->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Account Title</label>
                                    <input type="text" name="title" class="form-control"
                                        placeholder="e.g., UBL Current" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Type</label>
                                            <select class="form-control" name="type" style="height: 45px;">
                                                <option value="Debit">Debit</option>
                                                <option value="Credit">Credit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Opening Balance</label>
                                            <input type="number" step="0.01" name="opening_balance"
                                                class="form-control" value="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="statusCheck"
                                            name="status" checked>
                                        <label class="custom-control-label small text-secondary" for="statusCheck">Active
                                            Account</label>
                                    </div>
                                </div>

                                @if (auth()->user()->isSuperAdmin())
                                    <div class="form-group mb-3 mt-3">
                                        <label class="small text-secondary fw-bold mb-1">Target Branch</label>
                                        <select class="form-control" name="branch_id" style="height: 45px;">
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted" style="font-size:0.75rem;">Super Admin: Assign this
                                            account to a branch.</small>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium"
                                    data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save
                                    Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Add Head Modal -->
                <div class="modal fade" id="addHeadModal" tabindex="-1" role="dialog" aria-labelledby="addHeadLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4"
                            action="{{ route('account-heads.store') }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold ms-2" id="addHeadLabel">Add New Category</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">
                                <p class="text-muted small mb-4 ms-1">Create a new account category/head.</p>
                                <div class="form-group mb-0">
                                    <label class="small text-secondary fw-bold mb-1">Head Name</label>
                                    <input type="text" name="name" class="form-control"
                                        placeholder="e.g., Current Assets" required>
                                </div>

                                @if (auth()->user()->isSuperAdmin())
                                    <div class="form-group mb-3 mt-3">
                                        <label class="small text-secondary fw-bold mb-1">Target Branch</label>
                                        <select class="form-control" name="branch_id" style="height: 45px;">
                                            <option value="">Select Branch</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium"
                                    data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save
                                    Category</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Edit Account Modal --}}
    <div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog" aria-labelledby="editAccountLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content border-0 shadow-lg rounded-4" id="editAccountForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold ms-2" id="editAccountLabel">Edit Account</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <p class="text-muted small mb-4 ms-1">Update account details and opening balance.</p>

                    <div class="form-group mb-3">
                        <label class="small text-secondary fw-bold mb-1">Head (Category)</label>
                        <select class="form-control" name="head_id" id="editHeadId" required style="height:45px;">
                            <option value="">Select Head</option>
                            @foreach ($heads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="small text-secondary fw-bold mb-1">Account Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small text-secondary fw-bold mb-1">Type</label>
                                <select class="form-control" name="type" id="editType" style="height:45px;">
                                    <option value="Debit">Debit</option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small text-secondary fw-bold mb-1">Opening Balance</label>
                                <input type="number" step="0.01" name="opening_balance" id="editOpeningBalance"
                                    class="form-control" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================
         Setup COA Modal
    ============================= --}}
    <div class="modal fade" id="setupCOAModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-xl rounded-4" style="overflow:hidden;">

                {{-- Header --}}
                <div class="modal-header text-white" style="background:linear-gradient(135deg,#1e3a5f,#2563eb);">
                    <div>
                        <h5 class="modal-title fw-bold mb-0"><i class="fas fa-magic me-2"></i>Auto-Setup Chart of Accounts
                        </h5>
                        <p class="mb-0 small opacity-75 mt-1">Review the 5 critical accounts required for Sales &amp;
                            Purchase automation</p>
                    </div>
                    <button type="button" class="close text-white opacity-100" data-dismiss="modal"
                        style="font-size:1.4rem;background:none;border:none;">&times;</button>
                </div>

                <form action="{{ route('accounts.setupCOA') }}" method="POST">
                    @csrf

                    {{-- Column Headers --}}
                    <div class="px-4 pt-3 pb-1">
                        <div class="row g-0" style="background:#f8fafc; border-radius:8px; padding:8px 12px;">
                            <div class="col-1 text-muted small fw-bold">SELECT</div>
                            <div class="col-4 text-muted small fw-bold">ACCOUNT NAME</div>
                            <div class="col-2 text-muted small fw-bold text-center">TYPE</div>
                            <div class="col-2 text-muted small fw-bold text-center">NATURE</div>
                            <div class="col-3 text-muted small fw-bold">HEAD / CATEGORY</div>
                        </div>
                    </div>

                    {{-- Account Rows --}}
                    <div class="px-4 pb-2">
                        @foreach ($criticalCOA as $item)
                            <div class="row g-0 align-items-center py-3 border-bottom"
                                style="opacity: {{ $item['complete'] ? '0.5' : '1' }};">

                                {{-- Checkbox --}}
                                <div class="col-1">
                                    @if ($item['complete'])
                                        <span class="text-success fs-5" title="Already set up"><i
                                                class="fas fa-check-circle"></i></span>
                                    @else
                                        <div class="form-check">
                                            <input class="form-check-input coa-check" type="checkbox" name="keys[]"
                                                value="{{ $item['key'] }}" id="coa_{{ $item['key'] }}" checked>
                                        </div>
                                    @endif
                                </div>

                                {{-- Account Title --}}
                                <div class="col-4">
                                    <span class="fw-bold text-dark d-block">{{ $item['title'] }}</span>
                                    <small class="text-muted font-monospace">{{ $item['code'] }}</small>
                                    @if (!$item['complete'])
                                        @if (!$item['exists'])
                                            <span class="badge bg-danger-subtle text-danger ms-1"
                                                style="font-size:.7rem;">Missing</span>
                                        @elseif(!$item['has_head'])
                                            <span class="badge bg-warning-subtle text-warning ms-1"
                                                style="font-size:.7rem;">No Head</span>
                                        @endif
                                    @endif
                                </div>

                                {{-- Type (Debit/Credit) --}}
                                <div class="col-2 text-center">
                                    @if ($item['type'] === 'Debit')
                                        <span class="badge rounded-pill px-3"
                                            style="background:#dcfce7; color:#166534;">Dr</span>
                                    @else
                                        <span class="badge rounded-pill px-3"
                                            style="background:#fee2e2; color:#991b1b;">Cr</span>
                                    @endif
                                </div>

                                {{-- Nature --}}
                                <div class="col-2 text-center">
                                    @php
                                        $natureColors = [
                                            'Asset' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                                            'Liability' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                                            'Income' => ['bg' => '#dcfce7', 'text' => '#166534'],
                                            'Expense' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                                        ];
                                        $nc = $natureColors[$item['nature']] ?? [
                                            'bg' => '#f1f5f9',
                                            'text' => '#64748b',
                                        ];
                                    @endphp
                                    <span class="badge rounded-pill px-3"
                                        style="background:{{ $nc['bg'] }};color:{{ $nc['text'] }};">
                                        {{ $item['nature'] }}
                                    </span>
                                </div>

                                {{-- Head / Category --}}
                                <div class="col-3">
                                    @if ($item['head_name'])
                                        <span class="text-dark fw-medium"><i
                                                class="fas fa-folder text-warning me-1"></i>{{ $item['head_name'] }}</span>
                                    @else
                                        <span class="text-muted fst-italic small">
                                            <i class="fas fa-folder-plus text-secondary me-1"></i>Will create:
                                            <strong>{{ $item['head'] }}</strong>
                                        </span>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                    {{-- Footer --}}
                    <div class="modal-footer border-top px-4 py-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Only checked accounts will be created. ✅ = already complete.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="fas fa-magic me-1"></i> Create Selected
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- COA Info Modal --}}
    <div class="modal fade" id="coaInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-info ms-2"><i class="fas fa-info-circle me-2"></i> How Chart Of
                        Accounts Works</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"
                        style="background:none;border:none;font-size:1.5rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3 text-dark">
                    <p class="small text-muted mb-3">The Chart of Accounts (COA) is the foundation of your entire financial
                        system. It tracks where your money goes and where it comes from.</p>

                    <h6 class="fw-bold tracking-wide text-uppercase mb-2 text-primary" style="font-size: 0.85rem;">The 6
                        Critical Accounts</h6>
                    <div class="alert alert-light border shadow-sm rounded-3 mb-4">
                        <ul class="mb-0 ps-3 small text-dark" style="line-height: 1.6;">
                            <li><strong>1. Sales Revenue (Credit/Income):</strong> Automatically increases when you make a
                                Sale. Decreases when a customer returns an item (Sale Return).</li>
                            <li><strong>2. Purchase (Debit/Expense):</strong> Automatically increases with the <em>pure
                                    purchase price</em> when you buy stock. Decreases on Purchase Return.</li>
                            <li><strong>3. Purchase Expensive (Debit/Expense):</strong> Automatically created from the
                                <em>Extra Cost</em> field on a purchase. Also appears in Expense Vouchers. Can be created
                                manually.
                            </li>
                            <li><strong>4. Accounts Receivable (Debit/Asset):</strong> The money customers owe you.
                                Increases on unpaid sales. Decreases when you receive payment or process a Sale Return.</li>
                            <li><strong>5. Accounts Payable (Credit/Liability):</strong> The money you owe to vendors.
                                Increases on unpaid purchases. Decreases when you make a payment.</li>
                            <li><strong>6. Cash in Hand / Bank (Debit/Asset):</strong> Your actual money. Increases when you
                                receive a customer payment. Decreases when you pay vendors.</li>
                        </ul>
                    </div>

                    <h6 class="fw-bold tracking-wide text-uppercase mb-2 text-primary" style="font-size: 0.85rem;">How
                        Balances Are Calculated (Double Entry)</h6>
                    <p class="small mb-2 fw-medium">Every transaction automatically uses double-entry bookkeeping (Debits &
                        Credits). You do not need to do this manually; the system handles it.</p>
                    <ul class="small ps-3 text-secondary mb-0">
                        <li><strong>Debit Accounts (Assets/Expenses):</strong> Their balance GOES UP when Debited, and GOES
                            DOWN when Credited.</li>
                        <li><strong>Credit Accounts (Income/Liabilities/Equity):</strong> Their balance GOES UP when
                            Credited, and GOES DOWN when Debited.</li>
                    </ul>
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
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('.datanew')) {
                $('.datanew').DataTable().destroy();
            }
            $('.datanew').DataTable({
                "pageLength": 10,
                "aaSorting": [],
                "language": {
                    "search": "",
                    "searchPlaceholder": "Search accounts..."
                },
                "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            });
        });

        // Edit Account Modal
        $(document).on('click', '.edit-account-btn', function() {
            const id = $(this).data('id');
            const title = $(this).data('title');
            const type = $(this).data('type');
            const head = $(this).data('head');
            const balance = $(this).data('balance');

            $('#editTitle').val(title);
            $('#editType').val(type);
            $('#editHeadId').val(head);
            $('#editOpeningBalance').val(balance);

            const actionUrl = "{{ url('/accounts') }}/" + id + "/update";
            $('#editAccountForm').attr('action', actionUrl);

            $('#editAccountModal').modal('show');
        });
    </script>
@endsection
