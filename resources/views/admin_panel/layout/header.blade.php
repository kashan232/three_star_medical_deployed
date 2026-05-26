<!DOCTYPE html>
<html class="no-js" lang="zxx">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Zare Bootstrap 4 Admin Template">
    <title>Home</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.carousel.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/owl.theme.default.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/font-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ionicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/et-line.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/flag-icon.min.css') }}">
    <script src="{{ asset('assets/js/modernizr-2.8.3.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/metisMenu.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/slicknav.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/am-charts/css/am-charts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/morris-bundle/morris.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/charts/c3charts/c3.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/jquery.dataTables.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendors/data-table/css/responsive.jqueryui.min.css') }}">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    {{-- Online Links --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/brands.min.css"
        integrity="sha512-58P9Hy7II0YeXLv+iFiLCv1rtLW47xmiRpC1oFafeKNShp8V5bKV/ciVtYqbk2YfxXQMt58DjNfkXFOn62xE+g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="container-scroller">
        <nav class="rt_nav_header horizontal-layout col-lg-12 col-12 p-0">
            <div class="top_nav flex-grow-1">
                <div class="container d-flex flex-row h-100 align-items-center">
                    <div class="text-center rt_nav_wrapper d-flex align-items-center">
                        {{-- <a class="nav_logo rt_logo" href="index.html"><img src="{{asset('assets/images/WIJDAN-removebg-preview.png')}}" alt="logo" /></a> --}}
                        {{-- <a class="nav_logo nav_logo_mob" href="index.html"><img src="{{asset('assets/images/WIJDAN-removebg-preview.png')}}" alt="logo"/></a> --}}
                    </div>
                    <div class="nav_wrapper_main d-flex align-items-center justify-content-between flex-grow-1">
                        <ul class="navbar-nav navbar-nav-right mr-0 ml-auto">

                            {{-- ===== BRANCH INDICATOR / SWITCHER ===== --}}
                            @auth
                                @php $allBranches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get(); @endphp
                                <li class="nav-item d-flex align-items-center mr-3">
                                    @if (auth()->user()->isSuperAdmin())
                                        <div class="dropdown">
                                            <button class="badge dropdown-toggle border-0 px-3 py-2"
                                                style="background:linear-gradient(135deg,#6f42c1,#d63384);color:#fff;font-size:0.78rem;border-radius:20px;cursor:pointer;"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-crown mr-1"></i>
                                                @if (isset($activeBranch) && $activeBranch)
                                                    {{ $activeBranch->name }}
                                                @else
                                                    All Branches
                                                @endif
                                                <i class="fas fa-chevron-down ml-1" style="font-size:0.65rem;"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right"
                                                style="min-width:200px;z-index:9999;">
                                                <h6 class="dropdown-header"><i class="fas fa-exchange-alt mr-1"></i> Switch
                                                    Branch</h6>
                                                <form method="POST" action="{{ route('branch.switch') }}"
                                                    id="branchSwitchForm">
                                                    @csrf
                                                    <input type="hidden" name="branch_id" id="branchSwitchInput"
                                                        value="">
                                                </form>
                                                <a class="dropdown-item {{ !isset($activeBranch) || !$activeBranch ? 'font-weight-bold' : '' }}"
                                                    href="#" onclick="event.preventDefault();switchBranch('')">
                                                    <i class="fas fa-globe mr-2"></i> All Branches
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                @foreach ($allBranches as $br)
                                                    <a class="dropdown-item {{ isset($activeBranch) && $activeBranch && $activeBranch->id == $br->id ? 'font-weight-bold text-primary' : '' }}"
                                                        href="#"
                                                        onclick="event.preventDefault();switchBranch({{ $br->id }})">
                                                        <i class="fas fa-code-branch mr-2"></i> {{ $br->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif(isset($activeBranch) && $activeBranch)
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #0d6efd, #06c0d4); color:#fff; font-size:0.78rem; border-radius:20px; letter-spacing:0.5px;">
                                            <i class="fas fa-code-branch mr-1"></i> {{ $activeBranch->name }}
                                        </span>
                                    @endif
                                </li>
                                <script>
                                    function switchBranch(branchId) {
                                        document.getElementById('branchSwitchInput').value = branchId;
                                        document.getElementById('branchSwitchForm').submit();
                                    }
                                </script>
                            @endauth
                            {{-- ===== END BRANCH INDICATOR ===== --}}


                            <!-- Notification Bell -->

                            <li class="nav-item dropdown" id="notificationLi">
                                <a class="nav-link count-indicator dropdown-toggle" id="notificationDropdown"
                                    href="#" data-toggle="dropdown">
                                    <i class="fas fa-bell mx-0"></i>
                                    <span class="badge badge-danger notification-badge"
                                        style="display: none; position: absolute; top: 0px; right: 0px; font-size: 10px; padding: 3px 5px;">0</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                                    aria-labelledby="notificationDropdown" style="width: 300px;">
                                    <p class="mb-0 font-weight-normal float-left dropdown-header">Notifications</p>
                                    <div id="notificationList" style="max-height: 300px; overflow-y: auto;">
                                        <!-- Items will be injected here -->
                                        <p class="text-center p-3 text-muted">Loading...</p>
                                    </div>
                                    {{-- <a href="{{ route('notifications.index') }}" class="dropdown-item text-center text-primary">View all</a> --}}
                                </div>
                            </li>

                            <style>
                                /* CSS Hover Fallback */
                                #notificationLi:hover .dropdown-menu {
                                    display: block;
                                    animation: fadeIn 0.3s;
                                }

                                @keyframes fadeIn {
                                    from {
                                        opacity: 0;
                                    }

                                    to {
                                        opacity: 1;
                                    }
                                }
                            </style>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Initial fetch
                                    fetchNotifications();

                                    // Listen for real-time notifications via Reverb
                                    if (window.Echo) {
                                        window.Echo.private('App.Models.User.{{ auth()->id() }}')
                                            .listen('.notification.sent', (e) => {
                                                console.log('Notification received:', e);
                                                updateBadge(e.unread_count);
                                                prependNotification(e);
                                                
                                                // Premium Toast Alert
                                                if (typeof Swal !== 'undefined') {
                                                    Swal.fire({
                                                        title: e.title,
                                                        text: e.message,
                                                        icon: 'info',
                                                        toast: true,
                                                        position: 'top-end',
                                                        showConfirmButton: false,
                                                        timer: 5000,
                                                        timerProgressBar: true
                                                    });
                                                }
                                            });
                                    }
                                });

                                function updateBadge(count) {
                                    if (count > 0) {
                                        $('.notification-badge').text(count).show();
                                    } else {
                                        $('.notification-badge').hide();
                                    }
                                }

                                function prependNotification(n) {
                                    let html = createNotificationHtml(n);
                                    if ($('#notificationList .no-notifications').length > 0) {
                                        $('#notificationList').html(html);
                                    } else {
                                        $('#notificationList').prepend(html);
                                    }
                                }

                                function createNotificationHtml(n) {
                                    let iconClass = 'bg-info';
                                    let icon = 'fa-info';

                                    if (n.type === 'credit_alert') {
                                        iconClass = 'bg-danger';
                                        icon = 'fa-exclamation-triangle';
                                    } else if (n.type === 'sale_return') {
                                        iconClass = 'bg-warning';
                                        icon = 'fa-undo';
                                    }

                                    return `
                                    <div class="notification-item-container" id="notif-${n.id}">
                                        <a class="dropdown-item preview-item py-3" href="${n.action_url || '#'}">
                                            <div class="d-flex align-items-center">
                                                <div class="preview-thumbnail me-3">
                                                    <div class="preview-icon ${iconClass} rounded-circle d-flex align-items-center justify-content-center" style="width:35px; height:35px;">
                                                        <i class="fas ${icon} text-white" style="font-size:14px;"></i>
                                                    </div>
                                                </div>
                                                <div class="preview-item-content flex-grow-1 ms-2">
                                                    <h6 class="preview-subject font-weight-bold mb-1" style="font-size:13px; color: #333;">${n.title}</h6>
                                                    <p class="font-weight-light small-text mb-0 text-muted" style="font-size:11px; white-space: normal; line-height: 1.4;">
                                                        ${n.message}
                                                    </p>
                                                     <p class="font-weight-light small-text mb-0 text-muted mt-1 d-flex justify-content-between align-items-center" style="font-size:10px">
                                                        <span><i class="far fa-clock me-1"></i> ${n.created_at}</span>
                                                        <span class="notification-actions">
                                                            <button onclick="event.preventDefault(); markAsRead(${n.id})" class="btn btn-link p-0 text-success me-2" title="Mark as clear"><i class="fas fa-check-circle"></i></button>
                                                            <button onclick="event.preventDefault(); deleteNotification(${n.id})" class="btn btn-link p-0 text-danger" title="Delete"><i class="fas fa-trash-alt"></i></button>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </a>
                                        <div class="dropdown-divider m-0"></div>
                                    </div>
                                    `;
                                }

                                function fetchNotifications() {
                                    if (typeof $ === 'undefined') return; 

                                    $.get("{{ route('notifications.fetch') }}", function(data) {
                                        updateBadge(data.count);

                                        let html = '';
                                        if (data.notifications.length === 0) {
                                            html = '<p class="text-center p-4 text-muted no-notifications">No new notifications</p>';
                                        } else {
                                            data.notifications.forEach(n => {
                                                // Formatting date for JS if needed, but fetch usually returns human readable or ISO
                                                let dateStr = n.created_at;
                                                if (typeof dateStr === 'string' && dateStr.includes('T')) {
                                                    dateStr = new Date(dateStr).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                                                }
                                                n.created_at = dateStr;
                                                html += createNotificationHtml(n);
                                            });
                                        }
                                        $('#notificationList').html(html);
                                    });
                                }

                                function markAsRead(id) {
                                    $.post("{{ url('/notifications') }}/" + id + "/read", { _token: "{{ csrf_token() }}" }, function() {
                                        $(`#notif-${id}`).fadeOut(300, function() {
                                            $(this).remove();
                                            let count = parseInt($('.notification-badge').text()) - 1;
                                            updateBadge(count);
                                            if ($('.notification-item-container').length === 0) {
                                                $('#notificationList').html('<p class="text-center p-4 text-muted no-notifications">No new notifications</p>');
                                            }
                                        });
                                    });
                                }

                                function deleteNotification(id) {
                                    $.ajax({
                                        url: "{{ url('/notifications') }}/" + id,
                                        type: 'DELETE',
                                        data: { _token: "{{ csrf_token() }}" },
                                        success: function() {
                                            $(`#notif-${id}`).fadeOut(300, function() {
                                                $(this).remove();
                                                if ($('.notification-item-container').length === 0) {
                                                    $('#notificationList').html('<p class="text-center p-4 text-muted no-notifications">No new notifications</p>');
                                                }
                                            });
                                        }
                                    });
                                }
                            </script>

                            <li class="nav-item nav-profile dropdown">
                                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown"
                                    id="profileDropdown">
                                    <span class="profile_name">{{ Auth::user()->name }} <i
                                            class="feather ft-chevron-down"></i></span>
                                    <img src="assets/images/user.jpg" alt="profile" />
                                </a>
                                <div class="dropdown-menu dropdown-menu-right navbar-dropdown pt-2"
                                    aria-labelledby="profileDropdown">
                                    <a class="dropdown-item">
                                        <i class="ti-user text-dark mr-3"></i> Profile
                                    </a>
                                    <a class="dropdown-item">
                                        <i class="ti-settings text-dark mr-3"></i> Account Settings
                                    </a>
                                    <span role="separator" class="divider"></span>
                                    {{-- <a class="dropdown-item"> --}}
                                    {{-- <i class="ti-power-off text-dark mr-3"></i> --}}
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="ti-power-off text-dark mr-3"></i> Logout
                                        </button>
                                    </form>
                                    {{-- </a> --}}
                                </div>
                            </li>
                            <!--==================================*
                                 End Profile Menu
                        *====================================-->
                        </ul>
                        <!--=========================*
                               Mobile Menu
                   *===========================-->
                        <button class="navbar-toggler align-self-center" type="button" data-toggle="minimize">
                            <span class="feather ft-menu text-white"></span>
                        </button>
                        <!--=========================*
                           End Mobile Menu
                   *===========================-->
                    </div>
                </div>
            </div>
            <div class="nav-bottom">
                <div class="container">
                    <ul class="nav page-navigation">
                        <!--=========================*
                              Home
                    *===========================-->
                        <li class="nav-item">
                            <a href="{{ url('/home') }}" class="nav-link"><i
                                    class="menu_icon feather ft-home"></i><span
                                    class="menu-title">Dashboard</span></a>

                        </li>
                        <li class="nav-item">
                            <a href="{{ route('cdrs.index') }}" class="nav-link"><i
                                    class="menu_icon feather ft-file-text"></i><span class="menu-title">CDR /
                                    Tender</span></a>
                        </li>

                        <!--=========================*
                              UI Features
                    *===========================-->
                        <li class="nav-item mega-menu">
                            <a href="#" class="nav-link"><i class="menu_icon ti-layout-slider"></i><span
                                    class="menu-title">Management</span><i class="menu-arrow"></i></a>
                            <div class="submenu">
                                <div class="col-group-wrapper row">
                                    <div class="col-group col-md-3 mb-mob-0">
                                        <div class="row">
                                            <div class="col-12">
                                                <!--=========================*
                                                      Basic Elements
                                                *===========================-->
                                                <p class="category-heading">Product Managment</p>
                                                <div class="submenu-item">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Category.home') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Category</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('subcategory.home') }}"><i
                                                                            class="menu_icon ti-id-badge"></i><span>Sub
                                                                            Category</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('warehouse') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>warehouse</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('vendors.index') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>vendor</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('customer') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>customer</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('zone') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>zone</span></a>
                                                                </li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('sales-officers') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Sales
                                                                            Officer</span></a></li>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ url('transport') }}"><i
                                                                            class="menu_icon ti-alert"></i><span>Transport</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="accordion.html"><i class="menu_icon ti-layout-accordion-separated"></i><span>Accordion</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="buttons.html"><i class="menu_icon icon-focus"></i><span>Buttons</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="badges.html"><i class="menu_icon icon-ribbon"></i><span>Badges</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>

                                                                {{-- <li class="nav-item"><a class="nav-link" href="carousel.html"><i class="menu_icon ti-layout-slider"></i><span>Carousels</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="dropdown.html"><i class="menu_icon icon-layers"></i><span>Dropdown</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="tabs.html"><i class="menu_icon ti-layout-tab"></i><span>Tabs</span></a></li> --}}

                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-group col-md-3">
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="submenu-item pt-5 mt-2 pt-mob-0 mt-mob-0">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Brand.home') }}"><i
                                                                            class="menu_icon ti-smallcap"></i><span>Company</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="list-group.html"><i class="menu_icon ti-list"></i><span>List Group</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="modals.html"><i class="menu_icon ti-layers-alt"></i><span>Modals</span></a></li>
                                                                <li class="nav-item"><a class="nav-link" href="pagination.html"><i class="menu_icon ion-android-more-horizontal"></i><span>Pagination</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <ul>
                                                                <li class="nav-item"><a class="nav-link"
                                                                        href="{{ route('Unit.home') }}"><i
                                                                            class="menu_icon ion-ios-photos"></i><span>Units</span></a>
                                                                </li>
                                                                {{-- <li class="nav-item"><a class="nav-link" href="progressbar.html"><i class="menu_icon ion-ios-settings-strong"></i><span>Progressbar</span></a></li> --}}
                                                                {{-- <li class="nav-item"><a class="nav-link" href="grid.html"><i class="menu_icon ti-layout-grid4"></i><span>Grid</span></a></li> --}}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!--=========================*
                                          Icons
                                *===========================-->
                                </div>
                                <div class="col-group col-md-3">
                                    <p class="category-heading">Products</p>
                                    <ul class="submenu-item">
                                        {{-- <li class="nav-item"><a class="nav-link" href="font-awesome.html"><i class="menu_icon ti-flag-alt"></i> <span>Font Awesome</span></a></li> --}}
                                        {{-- <li class="nav-item"><a class="nav-link" href="themify.html"><i class="menu_icon ti-themify-favicon"></i><span>Themify</span></a></li> --}}
                                        {{-- <li class="nav-item"><a class="nav-link" href="ionicons.html"><i class="menu_icon ion-ionic"></i><span>Ionicons V2</span></a></li> --}}
                                        @if (auth()->user()->can('View Product') || auth()->user()->email === 'admin@admin.com')
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('product') }}"><i
                                                        class="menu_icon icon-basket"></i><span>Products</span></a>
                                            </li>
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('batches.opening') }}"><i
                                                        class="menu_icon fa-solid fa-boxes-stacked mr-2"></i><span>Opening
                                                        Stock Batches</span></a>
                                            </li>
                                        @endif
                                        <li class="nav-item">
                                            <a href="#" class="nav-link"><i
                                                    class="menu_icon icon-basket"></i><span>Purchase</span><i
                                                    class="menu-arrow"></i></a>
                                            <div class="submenu">
                                                <ul class="submenu-item">
                                                    <li class="nav-item"><a class="nav-link"
                                                            href="{{ route('purchase.order.index') }}"><span>Purchase
                                                                Order</span></a></li>
                                                    <li class="nav-item"><a class="nav-link"
                                                            href="{{ route('purchase.grn.index') }}"><span>Goods
                                                                Receipt Note</span></a></li>
                                                    <li class="nav-item"><a class="nav-link"
                                                            href="{{ route('purchase.return.index') }}"><span>Purchase
                                                                Return Note</span></a></li>
                                                </ul>
                                            </div>
                                        </li>
                                    </ul>
                                    <div class="col-group col-md-3">
                                        <p class="category-heading">Sales</p>
                                        <ul class="submenu-item">
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('sale.order.index') }}"><i
                                                        class="menu_icon ft-shopping-cart"></i><span>Sale
                                                        Order</span></a>
                                            </li>
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('sale.receipt.index') }}"><i
                                                        class="menu_icon ft-file-text"></i><span>Sale Invoice
                                                        Note</span></a>
                                            </li>
                                            <li class="nav-item"><a class="nav-link"
                                                    href="{{ route('sale.return.index') }}"><i
                                                        class="menu_icon ft-rotate-ccw"></i><span>Sale Return
                                                        Note</span></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                </div>
                </li>

                @if (auth()->user()->email === 'admin@admin.com')
                    <li class="nav-item">
                        <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span
                                class="menu-title">User
                                Managment</span><i class="menu-arrow"></i></a>
                        <div class="submenu">
                            <ul class="submenu-item">
                                <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}"><i
                                            class="fa-solid fa-users mr-2"></i><span>Users</span></a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('roles.index') }}"><i
                                            class="fa-solid fa-user-lock mr-2"></i><span>Roles</span></a></li>
                                <li class="nav-item"><a class="nav-link" href="{{ route('permissions.index') }}"><i
                                            class="fa-solid fa-user-lock mr-2"></i><span>Permissions</span></a>
                                </li>
                                @if (auth()->check() && auth()->user()->isSuperAdmin())
                                    <li class="nav-item"><a class="nav-link" href="{{ route('branch.index') }}"><i
                                                class="fa-solid fa-code-branch mr-2"></i><span>Branches</span></a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </li>
                @endif

                <li class="nav-item">
                    <a href="#" class="nav-link"><i class="menu_icon feather ft-clipboard"></i><span
                            class="menu-title">Report</span><i class="menu-arrow"></i></a>
                    <div class="submenu">
                        <ul class="submenu-item">
                            <li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}"><i
                                        class="fa-solid fa-users mr-2"></i><span>Users</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('roles.index') }}"><i
                                        class="fa-solid fa-user-lock mr-2"></i><span>Roles</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('permissions.index') }}"><i
                                        class="fa-solid fa-user-lock mr-2"></i><span>Permissions</span></a>
                            </li>
                            @if (auth()->check() && auth()->user()->isSuperAdmin())
                                <li class="nav-item"><a class="nav-link" href="{{ route('branch.index') }}"><i
                                            class="fa-solid fa-code-branch mr-2"></i><span>Branches</span></a></li>
                            @endif
                            <li class="nav-item"><a class="nav-link" href="{{ route('reports.expiry') }}"><i
                                        class="fa-solid fa-clock-rotate-left mr-2"></i><span>Expiry
                                        Report</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('report.cdr') }}"><i
                                        class="fa-solid fa-file-invoice-dollar mr-2"></i><span>CDR
                                        Report</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('report.price_adjustment') }}"><i
                                        class="fa-solid fa-tags mr-2"></i><span>Price Adjustment
                                        Report</span></a></li>
                            <li class="nav-item"><a class="nav-link" href="{{ route('report.dc') }}"><i
                                        class="fa-solid fa-truck mr-2"></i><span>DC Report</span></a></li>
                        </ul>
                    </div>
                </li>

                <!-- Settings -->
                @if (auth()->user()->email === 'admin@admin.com')
                    <li class="nav-item">
                        <a href="{{ route('settings.index') }}" class="nav-link"><i
                                class="menu_icon feather ft-settings"></i><span class="menu-title">Settings</span></a>
                    </li>
                @endif

                </ul>
            </div>
    </div>
    </nav>
