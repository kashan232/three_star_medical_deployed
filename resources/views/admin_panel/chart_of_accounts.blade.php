@extends('admin_panel.layout.app')

@section('content')
    <div class="main-content">
        <div class="main-content-inner">
            <div class="container-fluid py-4">

                {{-- Page Header --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-sitemap text-primary"></i> Chart Of Accounts
                            <button
                                class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 ms-2 rounded-pill px-3 shadow-none"
                                data-toggle="modal" data-target="#coaInfoModal" title="How does Chart of Accounts work?">
                                <i class="fas fa-info-circle"></i> How it works?
                            </button>
                        </h4>
                        <p class="text-muted mb-0 small">Manage your custom financial accounts and categories</p>
                    </div>
                    @can('chart.of.accounts.create')
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="{{ route('journal.voucher') }}"
                                class="btn btn-outline-primary d-flex align-items-center gap-2 shadow-sm">
                                <i class="fas fa-book"></i> Journal Voucher
                            </a>
                            <button class="btn btn-primary px-4 shadow-sm fw-bold d-flex align-items-center gap-2"
                                data-toggle="modal" data-target="#addAccountModal">
                                <i class="fas fa-plus-circle"></i> + Add New Account
                            </button>
                            <button class="btn btn-outline-secondary d-flex align-items-center gap-2 shadow-sm" data-toggle="modal"
                                data-target="#addHeadModal">
                                <i class="fas fa-folder-plus"></i> Add Category
                            </button>
                            <button class="btn btn-outline-dark d-flex align-items-center gap-2 shadow-sm" data-toggle="modal"
                                data-target="#manageHeadsModal">
                                <i class="fas fa-folder-open"></i> Manage Categories
                            </button>
                        </div>
                    @endcan
                </div>

                {{-- Summary Stats Cards --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #4f46e5 !important;">
                            <div class="card-body p-3">
                                <div class="text-muted small fw-semibold text-uppercase">Total Accounts</div>
                                <div class="fs-4 fw-bold text-dark">{{ $accounts->count() }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">All Financial Accounts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #0284c7 !important;">
                            <div class="card-body p-3">
                                <div class="text-info small fw-bold text-uppercase">Categories / Heads</div>
                                <div class="fs-4 fw-bold text-dark">{{ $heads->count() }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Active Categories</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #16a34a !important;">
                            <div class="card-body p-3">
                                <div class="text-success small fw-bold text-uppercase">Active Accounts</div>
                                <div class="fs-4 fw-bold text-dark">{{ $accounts->where('status', 1)->count() }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Ready for Vouchers</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm rounded-3 h-100" style="border-left: 4px solid #ea580c !important;">
                            <div class="card-body p-3">
                                <div class="text-warning small fw-bold text-uppercase">Total Branches</div>
                                <div class="fs-4 fw-bold text-dark">{{ count($branches) ?: 1 }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Company Branches</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        @if (session('success'))
                            <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 mb-4">
                                <i class="fas fa-check-circle fs-5"></i>
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
                            <table class="table table-hover align-middle datanew" id="coaTable" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 ps-3 text-secondary fw-bold text-uppercase small" style="width: 5%">#</th>
                                        <th class="py-3 text-secondary fw-bold text-uppercase small" style="width: 15%">Account Code</th>
                                        <th class="py-3 text-secondary fw-bold text-uppercase small" style="width: 20%">Category</th>
                                        <th class="py-3 text-secondary fw-bold text-uppercase small" style="width: 25%">Account Title</th>
                                        <th class="py-3 text-secondary fw-bold text-uppercase small text-center" style="width: 8%">Type</th>
                                        <th class="py-3 text-secondary fw-bold text-uppercase small text-end" style="width: 12%">Balance</th>
                                        @if (auth()->user()->isSuperAdmin())
                                            <th class="py-3 text-secondary fw-bold text-uppercase small text-center" style="width: 8%">Branch</th>
                                        @endif
                                        <th class="py-3 text-secondary fw-bold text-uppercase small text-center" style="width: 7%">Status</th>
                                        <th class="py-3 pe-3 text-secondary fw-bold text-uppercase small text-center" style="width: 10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accounts as $acc)
                                        <tr class="account-row border-bottom">
                                            <td class="ps-3 fw-bold text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-6">
                                                    {{ $acc->account_code ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark">{{ $acc->head->name ?? '-' }}</span>
                                                @if ($acc->head && $acc->head->code)
                                                    <small class="text-muted font-monospace d-block" style="font-size: 0.75rem;">[{{ $acc->head->code }}]</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark">{{ $acc->title }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($acc->type == 'Debit')
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Debit</span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Credit</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @php
                                                    $bal = $acc->calculated_balance;
                                                    $isNegative = $bal < 0;
                                                    $displaySuffix = $acc->type === 'Debit'
                                                        ? ($isNegative ? 'Cr' : 'Dr')
                                                        : ($isNegative ? 'Dr' : 'Cr');
                                                @endphp
                                                <div class="{{ $isNegative ? 'text-danger' : 'text-success' }} fw-bold">
                                                    {{ number_format(abs($bal), 2) }}
                                                    <small class="text-secondary fw-semibold ms-1">{{ $displaySuffix }}</small>
                                                </div>
                                            </td>
                                            @if (auth()->user()->isSuperAdmin())
                                                <td class="text-center">
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2">
                                                        {{ $acc->branch->name ?? 'Head Office' }}
                                                    </span>
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                @if ($acc->status)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Active</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="{{ route('accounts.ledger', $acc->id) }}"
                                                        class="btn btn-sm btn-outline-info" title="View Ledger">
                                                        <i class="fas fa-book"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-warning edit-account-btn"
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
                                                            <i class="fas {{ $acc->status ? 'fa-ban' : 'fa-check-circle' }}"></i>
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

                {{-- =========================================================================
                     MODAL: Add New Account
                     ========================================================================= --}}
                <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog" aria-labelledby="addAccountModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4" action="{{ route('accounts.store') }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <div>
                                    <h5 class="modal-title fw-bold text-primary" id="addAccountModalLabel">
                                        <i class="fas fa-plus-circle me-1"></i> Add New Account
                                    </h5>
                                    <p class="text-muted small mb-0">Create a financial account under your categories.</p>
                                </div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">

                                {{-- Category Selector (Only User's Categories) --}}
                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">
                                        Select Category (Account Head) <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control" name="head_id" id="newAccHeadId" required style="height: 48px; font-weight: 500;">
                                        <option value="">-- Select Category --</option>
                                        @foreach ($heads as $h)
                                            <option value="{{ $h->id }}" data-code="{{ $h->code }}" data-type="{{ $h->type }}">
                                                {{ $h->name }} {{ $h->code ? "({$h->code})" : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Live Generated Account Code Preview Badge --}}
                                <div class="p-3 mb-3 rounded-3" style="background:#f8fafc; border: 1px dashed #cbd5e1;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="text-secondary small fw-bold d-block">ACCOUNT CODE:</span>
                                            <span id="nextCodeBadge" class="fs-5 fw-bold font-monospace text-primary">
                                                Select a category above...
                                            </span>
                                        </div>
                                        <div class="text-end">
                                            <span class="text-secondary small fw-bold d-block">DEFAULT NATURE:</span>
                                            <span id="naturePreviewBadge" class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                                                -
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Account Title --}}
                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">
                                        Account Title / Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="title" id="newAccTitle" class="form-control text-uppercase fw-semibold"
                                        placeholder="e.g. MEEZAN BANK, FAYSAL BANK, CASH IN HAND" required style="height: 48px;">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Normal Account Type</label>
                                            <select class="form-control" name="type" id="newAccType" style="height: 45px; font-weight:600;">
                                                <option value="Debit">Debit (Dr)</option>
                                                <option value="Credit">Credit (Cr)</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="small text-secondary fw-bold mb-1">Opening Balance (Rs.)</label>
                                            <input type="number" step="0.01" name="opening_balance"
                                                class="form-control fw-bold" value="0.00" style="height: 45px;">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="statusCheck" name="status" checked>
                                        <label class="custom-control-label small text-secondary fw-semibold" for="statusCheck">
                                            Active Account (Ready for transactions & vouchers)
                                        </label>
                                    </div>
                                </div>

                                @if (auth()->user()->isSuperAdmin())
                                    <div class="form-group mb-3 mt-3">
                                        <label class="small text-secondary fw-bold mb-1">Target Branch <span class="text-danger">*</span></label>
                                        <select class="form-control" name="branch_id" required style="height: 45px;">
                                            <option value="">-- Select Target Branch * --</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                    <i class="fas fa-save me-1"></i> Save Account
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- =========================================================================
                     MODAL: Add New Category / Head
                     ========================================================================= --}}
                <div class="modal fade" id="addHeadModal" tabindex="-1" role="dialog" aria-labelledby="addHeadLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <form class="modal-content border-0 shadow-lg rounded-4" action="{{ route('account-heads.store') }}" method="POST">
                            @csrf
                            <div class="modal-header border-bottom-0 pb-0">
                                <h5 class="modal-title fw-bold text-dark ms-2" id="addHeadLabel">
                                    <i class="fas fa-folder-plus text-primary me-1"></i> Add Account Category
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body p-4 pt-3">
                                <p class="text-muted small mb-4 ms-1">Create a new account category.</p>

                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Category Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g., Bank, Cash in Hand, Expenses" required style="height: 45px;">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small text-secondary fw-bold mb-1">Category Code (Optional)</label>
                                    <input type="text" name="code" class="form-control font-monospace" placeholder="e.g., bank-001, cash-001" style="height: 45px;">
                                </div>

                                @if (auth()->user()->isSuperAdmin())
                                    <div class="form-group mb-3">
                                        <label class="small text-secondary fw-bold mb-1">Target Branch <span class="text-danger">*</span></label>
                                        <select class="form-control" name="branch_id" required style="height: 45px;">
                                            <option value="">-- Select Target Branch * --</option>
                                            @foreach ($branches as $b)
                                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer border-top-0 px-4 pb-4">
                                <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">Save Category</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- =========================================================================
         MODAL: Edit Account
         ========================================================================= --}}
    <div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog" aria-labelledby="editAccountLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content border-0 shadow-lg rounded-4" id="editAccountForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark ms-2" id="editAccountLabel">
                        <i class="fas fa-edit text-warning me-1"></i> Edit Account
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <p class="text-muted small mb-4 ms-1">Update account details, category, and opening balance.</p>

                    <div class="form-group mb-3">
                        <label class="small text-secondary fw-bold mb-1">Category (Head)</label>
                        <select class="form-control" name="head_id" id="editHeadId" required style="height:45px;">
                            <option value="">Select Category</option>
                            @foreach ($heads as $head)
                                <option value="{{ $head->id }}">{{ $head->name }} {{ $head->code ? "({$head->code})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="small text-secondary fw-bold mb-1">Account Title</label>
                        <input type="text" name="title" id="editTitle" class="form-control text-uppercase fw-semibold" required style="height:45px;">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small text-secondary fw-bold mb-1">Type</label>
                                <select class="form-control" name="type" id="editType" style="height:45px; font-weight:600;">
                                    <option value="Debit">Debit</option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="small text-secondary fw-bold mb-1">Opening Balance</label>
                                <input type="number" step="0.01" name="opening_balance" id="editOpeningBalance" class="form-control fw-bold" placeholder="0.00" style="height:45px;">
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

    {{-- =========================================================================
         MODAL: Manage Categories
         ========================================================================= --}}
    <div class="modal fade" id="manageHeadsModal" tabindex="-1" role="dialog" aria-labelledby="manageHeadsLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark ms-2" id="manageHeadsLabel">
                        <i class="fas fa-folder-open text-primary me-1"></i> Manage Account Categories
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <p class="text-muted small mb-3 ms-1">Update or delete existing categories.</p>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="width:100%;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-2 text-secondary fw-semibold text-uppercase small" style="width: 15%;">Code</th>
                                    <th class="py-2 text-secondary fw-semibold text-uppercase small" style="width: 45%;">Category Name</th>
                                    <th class="py-2 text-secondary fw-semibold text-uppercase small text-center" style="width: 20%;">Accounts</th>
                                    <th class="py-2 text-secondary fw-semibold text-uppercase small text-center" style="width: 20%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($heads as $head)
                                    @php
                                        $linkedAccounts = $head->accounts;
                                        $hasBalance = false;
                                        $accountNames = [];
                                        foreach ($linkedAccounts as $la) {
                                            $accountNames[] = $la->title;
                                            if (abs($la->calculated_balance) > 0.01) {
                                                $hasBalance = true;
                                            }
                                        }
                                        $accountsListJson = json_encode($accountNames);
                                    @endphp
                                    <tr id="head-row-{{ $head->id }}">
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace">{{ $head->code ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="head-name-text fw-bold text-dark">{{ $head->name }}</span>
                                            <div class="head-edit-form d-none mt-1">
                                                <form action="{{ route('account-heads.update', $head->id) }}" method="POST" class="d-flex gap-2 align-items-center">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $head->name }}" required style="max-width: 250px;">
                                                    <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i></button>
                                                    <button type="button" class="btn btn-sm btn-light cancel-edit-head-btn"><i class="fas fa-times"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ $linkedAccounts->count() }} accounts</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-sm btn-outline-warning edit-head-btn" title="Edit Category">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-head-trigger-btn"
                                                    data-id="{{ $head->id }}"
                                                    data-name="{{ $head->name }}"
                                                    data-has-accounts="{{ $linkedAccounts->isNotEmpty() ? 'true' : 'false' }}"
                                                    data-has-balance="{{ $hasBalance ? 'true' : 'false' }}"
                                                    data-account-names="{{ $accountsListJson }}"
                                                    title="Delete Category">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Delete Category Confirmation Modal --}}
    <div class="modal fade" id="deleteHeadModal" tabindex="-1" role="dialog" aria-labelledby="deleteHeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger ms-2" id="deleteHeadModalLabel">Delete Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3">
                    <div id="delHeadErrorView" class="d-none">
                        <div class="alert alert-danger d-flex align-items-start gap-2 rounded-3">
                            <i class="fas fa-exclamation-triangle mt-1"></i>
                            <div>
                                <strong class="d-block">Deletion Denied</strong>
                                <span id="delHeadErrorText">This category has linked accounts with active balances.</span>
                            </div>
                        </div>
                        <ul id="delHeadActiveAccountsList" class="text-danger fw-bold small"></ul>
                    </div>

                    <div id="delHeadSuperAdminView" class="d-none">
                        <div class="alert alert-danger d-flex align-items-start gap-2 rounded-3 mb-3">
                            <i class="fas fa-shield-alt mt-1 fs-5"></i>
                            <div>
                                <strong class="d-block">⚠️ Force Delete Warning</strong>
                                <span>The following accounts have active balances. Deleting them will erase associated transaction records.</span>
                            </div>
                        </div>
                        <ul id="delHeadSuperAdminAccountsList" class="text-danger fw-bold small mb-3"></ul>
                        <p class="fw-bold text-dark mb-0">Confirm force delete category <strong id="delSuperAdminHeadName" class="text-danger"></strong>?</p>
                    </div>

                    <div id="delHeadConfirmView" class="d-none">
                        <p class="text-dark">The following account(s) are linked to this category:</p>
                        <ul id="delHeadLinkedAccountsList" class="text-secondary fw-semibold small mb-3"></ul>
                        <div class="alert alert-warning rounded-3 small">
                            <i class="fas fa-exclamation-circle me-1"></i>
                            <strong>Warning:</strong> Deleting this category will also delete all its linked accounts.
                        </div>
                    </div>

                    <div id="delHeadSimpleView" class="d-none">
                        <p class="text-dark">Are you sure you want to delete category <strong id="delSimpleHeadName"></strong>?</p>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-medium" data-dismiss="modal">Close</button>
                    <form id="deleteHeadForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" id="confirmDeleteHeadBtn" class="btn btn-danger px-4 fw-bold shadow-sm d-none">Yes, Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- COA Info Modal --}}
    <div class="modal fade" id="coaInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-info ms-2"><i class="fas fa-info-circle me-2"></i> How Chart Of Accounts Works</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close" style="background:none;border:none;font-size:1.5rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3 text-dark">
                    <p class="small text-muted mb-3">The Chart of Accounts (COA) is the foundation of your financial system:</p>
                    <div class="alert alert-light border shadow-sm rounded-3 mb-4">
                        <ul class="mb-0 ps-3 small text-dark" style="line-height: 1.8;">
                            <li><strong>Categories (Heads):</strong> You can create your own custom categories (e.g. Bank, Cash in Hand, Utility Expenses).</li>
                            <li><strong>Accounts:</strong> Create your specific accounts under your categories (e.g. Meezan Bank under Bank, Office Cash under Cash).</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-primary fw-medium px-4 rounded-pill shadow-sm" data-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script>
        const IS_SUPER_ADMIN = {{ $isSuperAdmin ? 'true' : 'false' }};

        $(document).ready(function() {
            let dataTable = null;
            if ($.fn.DataTable.isDataTable('#coaTable')) {
                dataTable = $('#coaTable').DataTable();
            } else {
                dataTable = $('#coaTable').DataTable({
                    "pageLength": 25,
                    "aaSorting": [],
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search accounts, codes, categories..."
                    },
                    "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                        "<'row'<'col-sm-12'tr>>" +
                        "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                });
            }

            // Live Next Code & Type Generator when Head is selected
            $('#newAccHeadId').on('change', function() {
                const headId = $(this).val();
                if (!headId) {
                    $('#nextCodeBadge').text('Select a category above...').removeClass('text-success').addClass('text-primary');
                    $('#naturePreviewBadge').text('-').removeClass('bg-primary-subtle text-primary bg-warning-subtle text-warning').addClass('bg-secondary-subtle text-secondary');
                    return;
                }

                const selectedOption = $(this).find('option:selected');
                const headCode = selectedOption.data('code') || '';
                const headType = selectedOption.data('type') || '';

                // Call next-code endpoint
                $.ajax({
                    url: "{{ url('/accounts-head') }}/" + headId + "/next-code",
                    type: 'GET',
                    success: function(res) {
                        if (res && res.code) {
                            $('#nextCodeBadge').text(res.code).removeClass('text-primary').addClass('text-success');
                            $('#newAccType').val(res.type);

                            if (res.type === 'Debit') {
                                $('#naturePreviewBadge').text('Debit (Dr)').removeClass('bg-warning-subtle text-warning bg-secondary-subtle text-secondary').addClass('bg-primary-subtle text-primary');
                            } else {
                                $('#naturePreviewBadge').text('Credit (Cr)').removeClass('bg-primary-subtle text-primary bg-secondary-subtle text-secondary').addClass('bg-warning-subtle text-warning');
                            }
                        }
                    },
                    error: function() {
                        if (headCode) {
                            $('#nextCodeBadge').text(headCode + '-00001');
                        }
                    }
                });
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

        // Inline Category Name Edit
        $(document).on('click', '.edit-head-btn', function() {
            const row = $(this).closest('tr');
            row.find('.head-name-text').addClass('d-none');
            row.find('.head-edit-form').removeClass('d-none');
            row.find('.edit-head-btn').addClass('d-none');
            row.find('.delete-head-trigger-btn').addClass('d-none');
        });

        $(document).on('click', '.cancel-edit-head-btn', function() {
            const row = $(this).closest('tr');
            row.find('.head-name-text').removeClass('d-none');
            row.find('.head-edit-form').addClass('d-none');
            row.find('.edit-head-btn').removeClass('d-none');
            row.find('.delete-head-trigger-btn').removeClass('d-none');
        });

        // Delete Category Confirmation Trigger
        $(document).on('click', '.delete-head-trigger-btn', function() {
            const id          = $(this).data('id');
            const name        = $(this).data('name');
            const hasAccounts = $(this).data('has-accounts');
            const hasBalance  = $(this).data('has-balance');
            const accounts    = $(this).data('account-names') || [];
            const isSuperAdmin = IS_SUPER_ADMIN;

            $('#delHeadErrorView').addClass('d-none');
            $('#delHeadSuperAdminView').addClass('d-none');
            $('#delHeadConfirmView').addClass('d-none');
            $('#delHeadSimpleView').addClass('d-none');
            $('#confirmDeleteHeadBtn').addClass('d-none');
            $('#delHeadActiveAccountsList').empty();
            $('#delHeadSuperAdminAccountsList').empty();
            $('#delHeadLinkedAccountsList').empty();

            const actionUrl = "{{ url('/accounts-head') }}/" + id + "/delete";
            $('#deleteHeadForm').attr('action', actionUrl);

            if (!hasAccounts) {
                $('#delSimpleHeadName').text(name);
                $('#delHeadSimpleView').removeClass('d-none');
                $('#confirmDeleteHeadBtn').text('Yes, Delete').removeClass('d-none');
            } else if (hasBalance) {
                if (isSuperAdmin) {
                    accounts.forEach(accName => {
                        $('#delHeadSuperAdminAccountsList').append(`<li>${accName}</li>`);
                    });
                    $('#delSuperAdminHeadName').text('"' + name + '"');
                    $('#delHeadSuperAdminView').removeClass('d-none');
                    $('#confirmDeleteHeadBtn')
                        .text('Yes, Force Delete All')
                        .removeClass('d-none')
                        .addClass('btn-danger');
                } else {
                    accounts.forEach(accName => {
                        $('#delHeadActiveAccountsList').append(`<li>${accName}</li>`);
                    });
                    $('#delHeadErrorView').removeClass('d-none');
                }
            } else {
                accounts.forEach(accName => {
                    $('#delHeadLinkedAccountsList').append(`<li><strong>${accName}</strong></li>`);
                });
                $('#delHeadConfirmView').removeClass('d-none');
                $('#confirmDeleteHeadBtn').text('Yes, Delete All').removeClass('d-none');
            }

            $('#manageHeadsModal').modal('hide');
            $('#deleteHeadModal').modal('show');
        });

        $('#deleteHeadModal').on('hidden.bs.modal', function () {
            if ($('#deleteHeadModal').hasClass('show') === false) {
                $('#manageHeadsModal').modal('show');
            }
        });
    </script>
@endsection
