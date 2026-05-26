@extends('admin_panel.layout.app')

@section('content')
    <style>
        :root {
            --dash-primary: #6366f1;
            --dash-success: #22c55e;
            --dash-warning: #f59e0b;
            --dash-danger: #ef4444;
            --dash-info: #0ea5e9;
            --dash-purple: #8b5cf6;
            --dash-bg: #f8fafc;
            --dash-card: #ffffff;
            --dash-border: #e2e8f0;
            --dash-text: #1e293b;
            --dash-muted: #64748b;
        }

        .dashboard-container {
            padding: 0;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border-radius: 20px;
            padding: 32px 40px;
            color: white;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .welcome-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .welcome-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.9rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--dash-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--dash-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card.primary::before {
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }

        .stat-card.success::before {
            background: linear-gradient(180deg, #22c55e, #16a34a);
        }

        .stat-card.warning::before {
            background: linear-gradient(180deg, #f59e0b, #d97706);
        }

        .stat-card.danger::before {
            background: linear-gradient(180deg, #ef4444, #dc2626);
        }

        .stat-card.info::before {
            background: linear-gradient(180deg, #0ea5e9, #0284c7);
        }

        .stat-card.purple::before {
            background: linear-gradient(180deg, #8b5cf6, #7c3aed);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }

        .stat-card.primary .stat-icon {
            background: #eef2ff;
            color: #6366f1;
        }

        .stat-card.success .stat-icon {
            background: #dcfce7;
            color: #22c55e;
        }

        .stat-card.warning .stat-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .stat-card.danger .stat-icon {
            background: #fee2e2;
            color: #ef4444;
        }

        .stat-card.info .stat-icon {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .stat-card.purple .stat-icon {
            background: #f3e8ff;
            color: #8b5cf6;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 20px;
        }

        .stat-trend.up {
            background: #dcfce7;
            color: #16a34a;
        }

        .stat-trend.down {
            background: #fee2e2;
            color: #dc2626;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--dash-text);
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--dash-muted);
            margin-top: 4px;
        }

        /* Chart Cards */
        .chart-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 28px;
        }

        .chart-card {
            background: var(--dash-card);
            border-radius: 16px;
            border: 1px solid var(--dash-border);
            overflow: hidden;
        }

        .chart-card.full-width {
            grid-column: span 2;
        }

        .chart-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--dash-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--dash-primary);
        }

        .chart-filter {
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid var(--dash-border);
            background: white;
            color: var(--dash-muted);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--dash-primary);
            color: white;
            border-color: var(--dash-primary);
        }

        .chart-body {
            padding: 24px;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .action-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.15);
            border-color: var(--dash-primary);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.2rem;
        }

        .action-card.sales .action-icon {
            background: #dcfce7;
            color: #22c55e;
        }

        .action-card.purchase .action-icon {
            background: #e0f2fe;
            color: #0ea5e9;
        }

        .action-card.products .action-icon {
            background: #fef3c7;
            color: #f59e0b;
        }

        .action-card.hr .action-icon {
            background: #f3e8ff;
            color: #8b5cf6;
        }

        .action-title {
            font-weight: 600;
            color: var(--dash-text);
            font-size: 0.95rem;
        }

        .action-desc {
            font-size: 0.8rem;
            color: var(--dash-muted);
            margin-top: 4px;
        }

        /* Summary Cards Row */
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .summary-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 20px;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .summary-title {
            font-size: 0.85rem;
            color: var(--dash-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dash-text);
        }

        .summary-change {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            margin-top: 6px;
        }

        .summary-change.positive {
            color: #22c55e;
        }

        .summary-change.negative {
            color: #ef4444;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .chart-section {
                grid-template-columns: 1fr;
            }

            .chart-card.full-width {
                grid-column: span 1;
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .summary-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .summary-row {
                grid-template-columns: 1fr;
            }

            .welcome-section {
                padding: 24px;
            }
        }
    </style>

    <div class="main-content">
        <div class="main-content-inner">
            <div class="container dashboard-container">

                <!-- Welcome Section -->
                <div class="welcome-section" style="display: flex; justify-content: space-between; align-items: center;">
                    <div class="welcome-content">
                        <h1 class="welcome-title">Welcome back, {{ auth()->user()->name ?? 'Admin' }}! 👋</h1>
                        <p class="welcome-subtitle">Here's what's happening with your business today.</p>
                        <div class="welcome-date">
                            <i class="fa fa-calendar-alt"></i>
                            {{ now()->format('l, F d, Y') }}
                        </div>
                    </div>
                    <div class="welcome-filter" style="position: relative; z-index: 10;">
                        <form action="{{ route('home') }}" method="GET" id="rangeFilterForm">
                            <select name="range" onchange="document.getElementById('rangeFilterForm').submit()" 
                                style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 10px 20px; border-radius: 12px; font-weight: 600; cursor: pointer; outline: none;">
                                <option value="all" {{ ($range ?? 'all') == 'all' ? 'selected' : '' }} style="color: #000;">All Time</option>
                                <option value="today" {{ ($range ?? '') == 'today' ? 'selected' : '' }} style="color: #000;">Today</option>
                                <option value="week" {{ ($range ?? '') == 'week' ? 'selected' : '' }} style="color: #000;">This Week</option>
                                <option value="month" {{ ($range ?? '') == 'month' ? 'selected' : '' }} style="color: #000;">This Month</option>
                                <option value="year" {{ ($range ?? '') == 'year' ? 'selected' : '' }} style="color: #000;">This Year</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- UC2: Batch Expiry Alerts -->
                @if (($batchExpiredCount ?? 0) > 0)
                    <div
                        style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 16px 22px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <span style="font-size: 1.6rem;">🚨</span>
                            <div>
                                <div style="font-weight: 700; color: #dc2626; font-size: 1rem;">{{ $batchExpiredCount }}
                                    batch(es) have EXPIRED stock!</div>
                                <div style="font-size: 0.85rem; color: #991b1b;">These medicines must NOT be sold. Remove
                                    from shelf immediately.</div>
                            </div>
                        </div>
                        <a href="{{ route('reports.expiry') }}"
                            style="background: #dc2626; color: white; padding: 8px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; white-space: nowrap;">
                            View Report →
                        </a>
                    </div>
                @endif

                @if (($batchExpiringCount ?? 0) > 0)
                    <div
                        style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 14px; padding: 16px 22px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <span style="font-size: 1.6rem;">⚠️</span>
                            <div>
                                <div style="font-weight: 700; color: #d97706; font-size: 1rem;">{{ $batchExpiringCount }}
                                    batch(es) expire within 30 days</div>
                                <div style="font-size: 0.85rem; color: #92400e;">Prioritize selling these items first
                                    (FEFO). Consider promotions.</div>
                            </div>
                        </div>
                        <a href="{{ route('reports.expiry') }}"
                            style="background: #f59e0b; color: white; padding: 8px 20px; border-radius: 8px; font-size: 0.85rem; font-weight: 600; text-decoration: none; white-space: nowrap;">
                            View Report →
                        </a>
                    </div>
                @endif

                <!-- Quick Actions -->
                <div class="quick-actions">
                    @can('sales.create')
                        <a href="{{ route('sale.index') }}" class="action-card sales">
                            <div class="action-icon"><i class="fa fa-shopping-cart"></i></div>
                            <div class="action-title">New Sale</div>
                            <div class="action-desc">Create invoice</div>
                        </a>
                    @endcan

                    @can('purchases.create')
                        <a href="{{ route('Purchase.home') }}" class="action-card purchase">
                            <div class="action-icon"><i class="fa fa-truck"></i></div>
                            <div class="action-title">New Purchase</div>
                            <div class="action-desc">Add stock</div>
                        </a>
                    @endcan

                    @can('products.view')
                        <a href="{{ route('product') }}" class="action-card products">
                            <div class="action-icon"><i class="fa fa-box"></i></div>
                            <div class="action-title">Products</div>
                            <div class="action-desc">Manage inventory</div>
                        </a>
                    @endcan

                    @can('hr.employees.view')
                        <a href="{{ route('hr.employees.index') }}" class="action-card hr">
                            <div class="action-icon"><i class="fa fa-users"></i></div>
                            <div class="action-title">HR Module</div>
                            <div class="action-desc">Manage employees</div>
                        </a>
                    @endcan
                </div>

                <!-- Financial Health (Accounting Based) -->
                @if (isset($financialSummary) && !empty($financialSummary))
                    <h5 class="mb-3 text-muted d-flex align-items-center gap-2"
                        style="font-weight: 600; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        Financial Health ({{ ucfirst($range == 'all' ? 'All Time' : $range) }})
                        <button type="button"
                            class="btn btn-sm btn-outline-info d-flex align-items-center gap-1 rounded-pill px-3 shadow-none"
                            data-toggle="modal" data-target="#financialHealthModal" title="What do these mean?">
                            <i class="fas fa-info-circle"></i> Info
                        </button>
                    </h5>
                    <div class="stats-grid mb-4" style="grid-template-columns: repeat(3, 1fr);">
                        <!-- Sales Revenue -->
                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-hand-holding-usd"></i></div>
                                <div class="stat-trend up">Accounting</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format(abs($financialSummary['sales'] ?? 0), 0) }}</div>
                            <div class="stat-label">Sales Revenue</div>
                        </div>

                        <!--  Expenses -->
                        <div class="stat-card danger">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-money-bill-wave"></i></div>
                                <div class="stat-trend down">Accounting</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format(abs($financialSummary['purchases'] ?? 0), 0) }}
                            </div>
                            <div class="stat-label">Expenses</div>
                        </div>

                        <!-- Net Profit / Loss -->
                        @php
                            $absSales = abs($financialSummary['sales'] ?? 0);
                            $absPurchases = abs($financialSummary['purchases'] ?? 0);
                            $profit = $absSales - $absPurchases;
                            $isLoss = $profit < 0;
                        @endphp
                        <div class="stat-card {{ $isLoss ? 'danger' : 'success' }}"
                            style="background: {{ $isLoss ? '#fff5f5' : '#f0fdf4' }}; border: 1px solid {{ $isLoss ? '#fecaca' : '#bbf7d0' }};">
                            <div class="stat-header">
                                <div class="stat-icon"
                                    style="background: {{ $isLoss ? '#fef2f2' : '#dcfce7' }}; color: {{ $isLoss ? '#ef4444' : '#22c55e' }};">
                                    <i class="fa {{ $isLoss ? 'fa-arrow-down' : 'fa-chart-line' }}"></i>
                                </div>
                                <div class="stat-trend {{ $isLoss ? 'down' : 'up' }}">Bottom Line</div>
                            </div>
                            <div class="stat-value" style="color: {{ $isLoss ? '#ef4444' : '#22c55e' }};">Rs
                                {{ number_format(abs($profit), 0) }}</div>
                            <div class="stat-label fw-bold text-dark">{{ $isLoss ? 'Net Loss' : 'Net Profit' }}</div>
                        </div>

                        <!-- Receivables -->
                        <div class="stat-card info">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-user-clock"></i></div>
                                <div class="stat-trend">Assets</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format(abs($financialSummary['receivables'] ?? 0), 0) }}
                            </div>
                            <div class="stat-label">Total Receivables (Due)</div>
                        </div>

                        <!-- Payables -->
                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-file-invoice"></i></div>
                                <div class="stat-trend">Liabilities</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format(abs($financialSummary['payables'] ?? 0), 0) }}
                            </div>
                            <div class="stat-label">Total Payables (Owe)</div>
                        </div>

                        <!-- Customer Advance -->
                        <div class="stat-card purple">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-piggy-bank"></i></div>
                                <div class="stat-trend up">Liabilities</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalCustomerAdvance ?? 0, 0) }}</div>
                            <div class="stat-label">Customer Advances</div>
                        </div>
                    </div>
                @endif

                <!-- Main Stats (Legacy/Ops) -->
                <div class="stats-grid">
                    @can('sales.view')
                        <div class="stat-card success">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-shopping-cart"></i></div>
                                <div class="stat-trend up"><i class="fa fa-arrow-up"></i> Sales</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalSales, 0) }}</div>
                            <div class="stat-label">Total Sales</div>
                        </div>
                    @endcan

                    @can('purchases.view')
                        <div class="stat-card primary">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-file-invoice-dollar"></i></div>
                                <div class="stat-trend up"><i class="fa fa-arrow-up"></i> Purchases</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalPurchases, 0) }}</div>
                            <div class="stat-label">Total Purchases</div>
                        </div>
                    @endcan

                    @can('sales.returns.view')
                        <div class="stat-card danger">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-undo-alt"></i></div>
                                <div class="stat-trend down"><i class="fa fa-arrow-down"></i> Returns</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalSalesReturns, 0) }}</div>
                            <div class="stat-label">Sales Returns</div>
                        </div>
                    @endcan

                    @can('purchase.returns.view')
                        <div class="stat-card warning">
                            <div class="stat-header">
                                <div class="stat-icon"><i class="fa fa-undo"></i></div>
                                <div class="stat-trend down"><i class="fa fa-arrow-down"></i> Returns</div>
                            </div>
                            <div class="stat-value">Rs {{ number_format($totalPurchaseReturns, 0) }}</div>
                            <div class="stat-label">Purchase Returns</div>
                        </div>
                    @endcan
                </div>

                <!-- Inventory Summary -->
                <div class="summary-row">
                    @can('categories.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Categories</span>
                                <div class="summary-icon" style="background: #eef2ff; color: #6366f1;"><i
                                        class="fa fa-layer-group"></i></div>
                            </div>
                            <div class="summary-value">{{ $categoryCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-folder"></i> Product groups</div>
                        </div>
                    @endcan

                    @can('subcategories.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Subcategories</span>
                                <div class="summary-icon" style="background: #dcfce7; color: #22c55e;"><i
                                        class="fa fa-sitemap"></i></div>
                            </div>
                            <div class="summary-value">{{ $subcategoryCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-tags"></i> Sub-groups</div>
                        </div>
                    @endcan

                    @can('products.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Products</span>
                                <div class="summary-icon" style="background: #fef3c7; color: #f59e0b;"><i
                                        class="fa fa-box-open"></i></div>
                            </div>
                            <div class="summary-value">{{ $productCount }}</div>
                            <div class="summary-change positive"><i class="fa fa-cubes"></i> In inventory</div>
                        </div>
                    @endcan

                    @can('customers.view')
                        <div class="summary-card">
                            <div class="summary-header">
                                <span class="summary-title">Customers</span>
                                <div class="summary-icon" style="background: #e0f2fe; color: #0ea5e9;"><i
                                        class="fa fa-users"></i></div>
                            </div>
                            <div class="summary-value">{{ $customerscount }}</div>
                            <div class="summary-change positive"><i class="fa fa-user-plus"></i> Registered</div>
                        </div>
                    @endcan
                </div>

                <!-- Charts Section -->
                <div class="chart-section">
                    @can('sales.view')
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <div class="chart-title">
                                    <i class="fa fa-chart-line"></i> Sales Analytics
                                </div>
                                <div class="chart-filter" id="salesFilterBtns">
                                    <button class="filter-btn {{ in_array($range, ['today', 'all']) ? 'active' : '' }}" data-filter="daily">Daily</button>
                                    <button class="filter-btn {{ $range == 'week' ? 'active' : '' }}" data-filter="weekly">Weekly</button>
                                    <button class="filter-btn {{ $range == 'month' ? 'active' : '' }}" data-filter="monthly">Monthly</button>
                                </div>
                            </div>
                            <div class="chart-body">
                                <div id="salesReportChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    @endcan

                    @can('purchases.view')
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <div class="chart-title">
                                    <i class="fa fa-chart-area"></i> Purchase Analytics
                                </div>
                                <div class="chart-filter" id="purchaseFilterBtns">
                                    <button class="filter-btn {{ in_array($range, ['today', 'all']) ? 'active' : '' }}" data-filter="daily">Daily</button>
                                    <button class="filter-btn {{ $range == 'week' ? 'active' : '' }}" data-filter="weekly">Weekly</button>
                                    <button class="filter-btn {{ $range == 'month' ? 'active' : '' }}" data-filter="monthly">Monthly</button>
                                </div>
                            </div>
                            <div class="chart-body">
                                <div id="purchaseReportChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    @endcan
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const salesStats = @json($salesChartStats);
            const purchaseStats = @json($purchaseChartStats);

            const range = @json($range);
            const initialFilter = range === 'week' ? 'weekly' : (range === 'month' ? 'monthly' : 'daily');

            // Sales Chart
            const salesOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 2,
                        blur: 4,
                        opacity: 0.1
                    }
                },
                series: salesStats[initialFilter].series,
                xaxis: {
                    categories: salesStats[initialFilter].categories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#22c55e'],
                yaxis: {
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        },
                        formatter: val => 'Rs ' + val.toLocaleString()
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 5,
                    colors: ['#fff'],
                    strokeColors: '#22c55e',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: val => "Rs " + val.toLocaleString()
                    }
                }
            };

            const salesChart = new ApexCharts(document.querySelector("#salesReportChart"), salesOptions);
            salesChart.render();

            // Sales Filter
            document.querySelectorAll('#salesFilterBtns .filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#salesFilterBtns .filter-btn').forEach(b => b
                        .classList.remove('active'));
                    this.classList.add('active');
                    const selected = this.dataset.filter;
                    salesChart.updateOptions({
                        series: salesStats[selected].series,
                        xaxis: {
                            categories: salesStats[selected].categories
                        }
                    });
                });
            });

            // Purchase Chart
            const purchaseOptions = {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    fontFamily: 'inherit',
                    dropShadow: {
                        enabled: true,
                        top: 3,
                        left: 2,
                        blur: 4,
                        opacity: 0.1
                    }
                },
                series: purchaseStats[initialFilter].series,
                xaxis: {
                    categories: purchaseStats[initialFilter].categories,
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                colors: ['#6366f1'],
                yaxis: {
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontSize: '12px'
                        },
                        formatter: val => 'Rs ' + val.toLocaleString()
                    }
                },
                dataLabels: {
                    enabled: false
                },
                markers: {
                    size: 5,
                    colors: ['#fff'],
                    strokeColors: '#6366f1',
                    strokeWidth: 2,
                    hover: {
                        size: 7
                    }
                },
                fill: {
                    type: "gradient",
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: "light",
                    y: {
                        formatter: val => "Rs " + val.toLocaleString()
                    }
                }
            };

            const purchaseChart = new ApexCharts(document.querySelector("#purchaseReportChart"), purchaseOptions);
            purchaseChart.render();

            // Purchase Filter
            document.querySelectorAll('#purchaseFilterBtns .filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('#purchaseFilterBtns .filter-btn').forEach(b => b
                        .classList.remove('active'));
                    this.classList.add('active');
                    const selected = this.dataset.filter;
                    purchaseChart.updateOptions({
                        series: purchaseStats[selected].series,
                        xaxis: {
                            categories: purchaseStats[selected].categories
                        }
                    });
                });
            });
        });
    </script>

    {{-- Financial Health Info Modal --}}
    <div class="modal fade" id="financialHealthModal" tabindex="-1" aria-hidden="true" style="z-index: 1050;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-info ms-2"><i class="fas fa-info-circle me-2"></i> Dashboard
                        Financial Health</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close"
                        style="background:none;border:none;font-size:1.5rem;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 pt-3 text-dark">
                    <p class="small text-muted mb-3">These top 5 cards represent your true accounting picture for the
                        current month. They are automatically calculated from your Journal Entries to give you an accurate
                        snapshot of the business.</p>

                    <div class="table-responsive small mb-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Indicator</th>
                                    <th>What it means</th>
                                    <th>How it is calculated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Sales Revenue</strong></td>
                                    <td>Total value of goods successfully sold.</td>
                                    <td class="text-success text-nowrap">Gross Sales - Sales Returns</td>
                                </tr>
                                <tr>
                                    <td><strong> Expense</strong></td>
                                    <td>Total cost of acquiring inventory.</td>
                                    <td class="text-danger text-nowrap">Gross Purchases - Purchase Returns</td>
                                </tr>
                                <tr>
                                    <td><strong>Net Profit / Loss</strong></td>
                                    <td>The actual money you made (or lost) this month.</td>
                                    <td class="text-primary fw-bold text-nowrap">Sales Revenue -  Expense</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Receivables</strong></td>
                                    <td>Unpaid debts owed <strong>to you</strong> by customers.</td>
                                    <td class="text-muted text-nowrap">Sum of all Customer Ledgers (Dr)</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Payables</strong></td>
                                    <td>Unpaid debts you owe <strong>to suppliers</strong>.</td>
                                    <td class="text-muted text-nowrap">Sum of all Vendor Ledgers (Cr)</td>
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
