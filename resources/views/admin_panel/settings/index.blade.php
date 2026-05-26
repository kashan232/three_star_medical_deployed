@extends('admin_panel.layout.app')

@section('style')
    <style>
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            color: #4e73df;
            background: transparent;
            border-bottom: 2px solid #4e73df;
        }
        .nav-tabs .nav-link:hover {
            border-bottom: 2px solid #dee2e6;
        }
        .nav-tabs .nav-link i {
            margin-right: 8px;
        }
        .settings-card {
            border-radius: 12px;
            overflow: hidden;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        .user-row:hover {
            background-color: #f8f9fc !important;
        }
        .user-checkbox:checked + .custom-control-label::before {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .avatar-sm {
            flex-shrink: 0;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">ERP Settings</h3>
                    </div>
                    <div class="card-body">
                        <!-- Advanced Settings & Navigation -->
                        <div class="mb-4 pb-3 border-bottom">
                            <h6 class="text-muted mb-3 font-weight-bold text-uppercase"
                                style="font-size: 0.8rem; letter-spacing: 1px;">Advanced Actions</h6>
                            <div class="d-flex flex-wrap">
                                <a href="{{ route('settings.return-policy') }}"
                                    class="btn btn-outline-primary mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-undo-alt mr-2"></i> Return Policy
                                </a>
                                <a href="{{ route('settings.return-approvers') }}"
                                    class="btn btn-outline-info mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-user-shield mr-2"></i> Return Approvers
                                </a>
                                <a href="#" class="btn btn-outline-dark mr-2 mb-2 shadow-sm">
                                    <i class="fas fa-exchange-alt mr-2"></i> Switch Account
                                </a>
                            </div>
                        </div>

                        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="company-tab" data-toggle="tab" href="#company"
                                    role="tab">
                                    <i class="fas fa-building"></i> Company
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="sales-tab" data-toggle="tab" href="#sales" role="tab">
                                    <i class="fas fa-shopping-cart"></i> Sales
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="inventory-tab" data-toggle="tab" href="#inventory" role="tab">
                                    <i class="fas fa-boxes"></i> Inventory
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="accounting-tab" data-toggle="tab" href="#accounting" role="tab">
                                    <i class="fas fa-calculator"></i> Accounting
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="notifications-tab" data-toggle="tab" href="#notifications" role="tab">
                                    <i class="fas fa-bell"></i> Notifications
                                </a>
                            </li>
                        </ul>

                        <form id="settingsForm" class="mt-4">
                            @csrf
                            <div class="tab-content" id="settingsTabContent">
                                <!-- Company Tab -->
                                <div class="tab-pane fade show active" id="company" role="tabpanel">
                                    @if (isset($settings['company']))
                                        @foreach ($settings['company'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                @if ($setting['type'] === 'text')
                                                    <textarea name="settings[{{ $setting['key'] }}]" class="form-control" rows="3">{{ $setting['value'] }}</textarea>
                                                @else
                                                    <input type="text" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @endif
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Sales Tab -->
                                <div class="tab-pane fade" id="sales" role="tabpanel">
                                    @if (isset($settings['sales']))
                                        @foreach ($settings['sales'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                @if ($setting['type'] === 'text')
                                                    <textarea name="settings[{{ $setting['key'] }}]" class="form-control" rows="3">{{ $setting['value'] }}</textarea>
                                                @elseif($setting['type'] === 'integer')
                                                    <input type="number" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @else
                                                    <input type="text" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @endif
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Inventory Tab -->
                                <div class="tab-pane fade" id="inventory" role="tabpanel">
                                    @if (isset($settings['inventory']))
                                        @foreach ($settings['inventory'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                <input type="number" name="settings[{{ $setting['key'] }}]"
                                                    class="form-control" value="{{ $setting['value'] }}">
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Accounting Tab -->
                                <div class="tab-pane fade" id="accounting" role="tabpanel">
                                    @if (isset($settings['accounting']))
                                        @foreach ($settings['accounting'] as $setting)
                                            <div class="form-group">
                                                <label>{{ $setting['label'] }}</label>
                                                <input type="text" name="settings[{{ $setting['key'] }}]"
                                                    class="form-control" value="{{ $setting['value'] }}">
                                                @if ($setting['description'])
                                                    <small
                                                        class="form-text text-muted">{{ $setting['description'] }}</small>
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                <!-- Notifications Tab -->
                                <div class="tab-pane fade" id="notifications" role="tabpanel">
                                    @if (isset($settings['notifications']))
                                        @foreach ($settings['notifications'] as $setting)
                                            <div class="form-group mb-5">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="font-weight-bold text-dark mb-0" style="font-size: 1rem;">
                                                        <i class="fas {{ str_contains($setting['key'], 'sale') ? 'fa-cart-arrow-down text-success' : 'fa-truck-loading text-warning' }} mr-2"></i> 
                                                        {{ $setting['label'] }}
                                                    </label>
                                                </div>
                                                
                                                @if($setting['type'] === 'json')
                                                    <div class="card border shadow-sm rounded-lg overflow-hidden">
                                                        <div class="card-header bg-light py-2 px-3">
                                                            <div class="input-group input-group-sm">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text bg-white border-right-0">
                                                                        <i class="fas fa-search text-muted"></i>
                                                                    </span>
                                                                </div>
                                                                <input type="text" class="form-control border-left-0 userSearch" 
                                                                    data-target="container-{{ $setting['key'] }}"
                                                                    placeholder="Search users...">
                                                                <div class="input-group-append">
                                                                    <button type="button" class="btn btn-outline-primary btn-select-all" data-target="container-{{ $setting['key'] }}">All</button>
                                                                    <button type="button" class="btn btn-outline-secondary btn-clear-all" data-target="container-{{ $setting['key'] }}">Clear</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="card-body p-0" style="max-height: 280px; overflow-y: auto;" id="container-{{ $setting['key'] }}">
                                                            <div class="list-group list-group-flush">
                                                                @foreach($users as $user)
                                                                    <div class="list-group-item d-flex align-items-center py-2 px-3 border-bottom user-row" 
                                                                         style="cursor: pointer; transition: background 0.2s;">
                                                                        <div class="mr-3">
                                                                            <input type="checkbox" name="settings[{{ $setting['key'] }}][]" 
                                                                                value="{{ $user->id }}" 
                                                                                class="user-checkbox" 
                                                                                style="width: 18px; height: 18px; cursor: pointer;"
                                                                                {{ is_array($setting['value']) && in_array((string)$user->id, array_map('strval', $setting['value'])) ? 'checked' : '' }}>
                                                                        </div>
                                                                        <div class="avatar-sm mr-3 rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" 
                                                                             style="width: 32px; height: 32px; font-size: 12px; font-weight: bold; background: linear-gradient(135deg, #4e73df, #224abe);">
                                                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                                                        </div>
                                                                        <div class="user-info flex-grow-1">
                                                                            <h6 class="mb-0 user-name" style="font-size: 14px; color: #333;">{{ $user->name }}</h6>
                                                                            <small class="text-muted user-email" style="font-size: 11px;">{{ $user->email }}</small>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="card-footer bg-white py-2 px-3 border-top">
                                                            <small class="text-muted" style="font-size: 11px;">
                                                                <i class="fas fa-info-circle mr-1 text-primary"></i> 
                                                                {{ $setting['description'] }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                @elseif ($setting['type'] === 'text')
                                                    <textarea name="settings[{{ $setting['key'] }}]" class="form-control" rows="3">{{ $setting['value'] }}</textarea>
                                                @else
                                                    <input type="text" name="settings[{{ $setting['key'] }}]"
                                                        class="form-control" value="{{ $setting['value'] }}">
                                                @endif
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // User Search Logic
            $('.userSearch').on('keyup', function() {
                let value = $(this).val().toLowerCase();
                let targetId = $(this).data('target');
                $('#' + targetId + ' .user-row').filter(function() {
                    let name = $(this).find('.user-name').text().toLowerCase();
                    let email = $(this).find('.user-email').text().toLowerCase();
                    $(this).toggle(name.indexOf(value) > -1 || email.indexOf(value) > -1);
                });
            });

            // Select All Users
            $('.btn-select-all').on('click', function() {
                let targetId = $(this).data('target');
                $('#' + targetId + ' .user-checkbox:visible').prop('checked', true);
            });

            // Clear All Users
            $('.btn-clear-all').on('click', function() {
                let targetId = $(this).data('target');
                $('#' + targetId + ' .user-checkbox:visible').prop('checked', false);
            });

            // Toggle checkbox when clicking the row
            $(document).on('click', '.user-row', function(e) {
                if ($(e.target).is('input')) return;
                let checkbox = $(this).find('.user-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked'));
            });

            $('#settingsForm').on('submit', function(e) {
                e.preventDefault();
                
                // Simple indicator that the process started
                let btn = $(this).find('button[type="submit"]');
                let originalHtml = btn.html();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route('settings.update') }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        btn.prop('disabled', false).html(originalHtml);
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000
                        }).then(() => {
                            // Reload to show the ticks from database
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(originalHtml);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to update settings: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'),
                        });
                    }
                });
            });
        });
    </script>
@endsection
