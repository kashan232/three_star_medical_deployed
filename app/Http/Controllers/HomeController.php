<?php

namespace App\Http\Controllers;

use App\Models\ProductBatch;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Http\Traits\BranchScoped;

class HomeController extends Controller
{
    use BranchScoped;

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $usertype = Auth::user()->usertype;
        $userId = Auth::id();
        $branchId = $this->getBranchId();

        if ($usertype == 'user') {
            return view('user_panel.dashboard', compact('userId'));
        } elseif ($usertype == 'admin' || $usertype == 'super_admin') {
            $range = request('range', 'all');
            $startDate = null;
            $endDate = now()->endOfDay();

            if ($range == 'today') {
                $startDate = now()->startOfDay();
            } elseif ($range == 'week') {
                $startDate = now()->startOfWeek();
            } elseif ($range == 'month') {
                $startDate = now()->startOfMonth();
            } elseif ($range == 'year') {
                $startDate = now()->startOfYear();
            }

            // Counts (usually remain absolute, but can be filtered if needed. User asked for card data to reflect filter)
            $categoryCount = Auth::user()->can('categories.view') ? DB::table('categories')->count() : 0;
            $subcategoryCount = Auth::user()->can('subcategories.view') ? DB::table('subcategories')->count() : 0;
            $productCount = Auth::user()->can('products.view') ? DB::table('products')->count() : 0;
            $customerscount = Auth::user()->can('customers.view') ? DB::table('customers')->when($branchId, fn($q) => $q->where('branch_id', $branchId))->count() : 0;

            // Stats with Date Filter
            $totalPurchases = Auth::user()->can('purchases.view') 
                ? DB::table('purchases')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->sum('net_amount') 
                : 0;
            $totalPurchaseReturns = Auth::user()->can('purchase.returns.view') 
                ? DB::table('purchase_returns')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->sum('net_amount') 
                : 0;
            $totalSales = Auth::user()->can('sales.view') 
                ? DB::table('sales')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->sum('total_net') 
                : 0;
            $totalSalesReturns = Auth::user()->can('sales.returns.view') 
                ? DB::table('sale_returns')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
                    ->sum('net_amount') 
                : 0;

            // Financial Summary (Accounting Based)
            $financialSummary = [];
            if (Auth::user()->can('purchases.view') || Auth::user()->can('sales.view')) {
                try {
                    $balanceService = app(\App\Services\BalanceService::class);
                    $fromDate = $startDate ? $startDate->format('Y-m-d') : now()->subYears(10)->format('Y-m-d');
                    $toDate   = $endDate->format('Y-m-d');
                    $financialSummary = $balanceService->getFinancialSummary($fromDate, $toDate, $branchId);
                } catch (\Exception $e) {
                     \Log::error("Dashboard Financial Summary Error: " . $e->getMessage());
                }
            }

            // Total Customer Advance
            $totalCustomerAdvance = 0;
            if (Auth::user()->can('customers.view')) {
                $customs = DB::table('customers')
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->select('id', 'opening_balance')
                    ->get();
                $journalBalances = DB::table('journal_entries')
                    ->where('party_type', \App\Models\Customer::class)
                    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                    ->selectRaw('party_id, SUM(debit) - SUM(credit) as balance')
                    ->groupBy('party_id')
                    ->pluck('balance', 'party_id');
                
                foreach($customs as $c) {
                    $bal = ($c->opening_balance ?? 0) + ($journalBalances[$c->id] ?? 0);
                    if($bal < 0) {
                        $totalCustomerAdvance += abs($bal);
                    }
                }
            }

            // ===== SALES REPORT CHARTS =====
            $salesChartStats = ['daily' => ['series' => [], 'categories' => []]];
            if (Auth::user()->can('sales.view')) {
                // DAILY (last 7 days)
                $dailyLabels = collect(range(6, 0))->map(fn($i) => \Carbon\Carbon::today()->subDays($i)->format('Y-m-d'));
                $dailyData = $dailyLabels->map(function ($date) use ($branchId) {
                    $salesSum = DB::table('sales')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereDate('created_at', $date)
                        ->sum('total_net');
                    $returnSum = DB::table('sale_returns')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereDate('created_at', $date)
                        ->sum('net_amount');
                    return $salesSum - $returnSum;
                });

                // WEEKLY (This + Last 2 weeks)
                $weeklyLabels = ['This Week', 'Last Week', '2 Weeks Ago'];
                $weeklyData = collect([0, 1, 2])->map(function ($i) use ($branchId) {
                    $start = \Carbon\Carbon::now()->startOfWeek()->subWeeks($i);
                    $end = $start->copy()->endOfWeek();
                    $salesSum = DB::table('sales')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('total_net');
                    $returnSum = DB::table('sale_returns')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('net_amount');
                    return $salesSum - $returnSum;
                })->reverse()->values();

                // MONTHLY (Jan → Current month)
                $months = range(1, \Carbon\Carbon::now()->month);
                $monthLabels = collect($months)->map(fn($m) => \Carbon\Carbon::create()->month($m)->format('F'));
                $monthlyData = collect($months)->map(function ($month) use ($branchId) {
                    $salesSum = DB::table('sales')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', \Carbon\Carbon::now()->year)
                        ->sum('total_net');
                    $returnSum = DB::table('sale_returns')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', \Carbon\Carbon::now()->year)
                        ->sum('net_amount');
                    return $salesSum - $returnSum;
                });

                $salesChartStats = [
                    'daily' => [
                        'categories' => $dailyLabels,
                        'series' => [['name' => 'Sales', 'data' => $dailyData]]
                    ],
                    'weekly' => [
                        'categories' => $weeklyLabels,
                        'series' => [['name' => 'Sales', 'data' => $weeklyData]]
                    ],
                    'monthly' => [
                        'categories' => $monthLabels,
                        'series' => [['name' => 'Sales', 'data' => $monthlyData]]
                    ]
                ];
            }

            // ===== PURCHASE CHARTS =====
            $purchaseChartStats = ['daily' => ['series' => [], 'categories' => []]];
            if (Auth::user()->can('purchases.view')) {
                // DAILY
                $purchaseDailyLabels = collect(range(6, 0))->map(fn($i) => Carbon::today()->subDays($i)->format('Y-m-d'));
                $purchaseDailySeries = [[
                    'name' => 'Purchases',
                    'data' => $purchaseDailyLabels->map(function ($date) use ($branchId) {
                        $purchSum = DB::table('purchases')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereDate('created_at', $date)
                            ->sum('net_amount');
                        $retSum = DB::table('purchase_returns')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereDate('created_at', $date)
                            ->sum('net_amount');
                        return $purchSum - $retSum;
                    })
                ]];

                // WEEKLY
                $purchaseWeeklyLabels = ['This Week', 'Last Week', '2 Weeks Ago'];
                $purchaseWeeklySeries = [[
                    'name' => 'Purchases',
                    'data' => collect([0, 1, 2])->map(function ($i) use ($branchId) {
                        $start = Carbon::now()->startOfWeek()->subWeeks($i);
                        $end = $start->copy()->endOfWeek();
                        $purchSum = DB::table('purchases')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereBetween('created_at', [$start, $end])
                            ->sum('net_amount');
                        $retSum = DB::table('purchase_returns')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereBetween('created_at', [$start, $end])
                            ->sum('net_amount');
                        return $purchSum - $retSum;
                    })->reverse()->values()
                ]];

                // MONTHLY
                $months = range(1, Carbon::now()->month);
                $purchaseMonthLabels = collect($months)->map(fn($m) => Carbon::create()->month($m)->format('F'));
                $purchaseMonthlySeries = [[
                    'name' => 'Purchases',
                    'data' => collect($months)->map(function ($month) use ($branchId) {
                        $purchSum = DB::table('purchases')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('net_amount');
                        $retSum = DB::table('purchase_returns')
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                            ->whereMonth('created_at', $month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('net_amount');
                        return $purchSum - $retSum;
                    })
                ]];

                $purchaseChartStats = [
                    'daily' => [
                        'categories' => $purchaseDailyLabels,
                        'series' => $purchaseDailySeries
                    ],
                    'weekly' => [
                        'categories' => $purchaseWeeklyLabels,
                        'series' => $purchaseWeeklySeries
                    ],
                    'monthly' => [
                        'categories' => $purchaseMonthLabels,
                        'series' => $purchaseMonthlySeries
                    ]
                ];
            }

            // UC2: Expiry Alerts
            $batchExpiredCount  = ProductBatch::expired()
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();
            $batchExpiringCount = ProductBatch::expiringSoon(180)
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->count();

            return view('admin_panel.dashboard', compact(
                'categoryCount',
                'subcategoryCount',
                'productCount',
                'customerscount',
                'totalPurchases',
                'totalPurchaseReturns',
                'totalSales',
                'totalSalesReturns',
                'salesChartStats',
                'purchaseChartStats',
                'financialSummary',
                'totalCustomerAdvance',
                'batchExpiredCount',
                'batchExpiringCount',
                'branchId',
                'range'
            ));
        } else {
            return redirect()->back()->with('error', 'Unauthorized access');
        }
    }
}
