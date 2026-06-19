<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Store;
use App\Models\ThirdPartyStatement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, \App\Services\DashboardMetricsService $metrics)
    {
        $user = auth()->user();

        // Get analytics data based on user role
        $analytics = $this->getAnalyticsData($user);

        $now = Carbon::now();

        // Store selector — Admin: all stores; Owner/Manager: only accessible ones.
        // accessibleStores() is already role/tenant aware, so the list is correct.
        $storeOptions = $user->accessibleStores()->orderBy('store_info')->get();
        $selectedStore = (string) $request->query('store', 'all');
        $selectedStoreId = null;
        if ($selectedStore !== 'all' && ctype_digit($selectedStore) && $storeOptions->contains('id', (int) $selectedStore)) {
            $selectedStoreId = (int) $selectedStore;
        } else {
            $selectedStore = 'all';
        }

        // Unified time filter — drives BOTH the rings and the performance summary.
        // Either a preset, or "month" mode using the Month + Year pickers.
        $periodLabels = [
            'month' => 'Specific month',
            'current_month' => 'Current month',
            'last_month' => 'Last month',
            'last_3_months' => 'Last 3 months',
            'current_year' => 'Current year',
            'last_year' => 'Last year',
            'all_time' => 'All time',
        ];
        $periodPreset = (string) $request->query('period', 'month');
        if (! array_key_exists($periodPreset, $periodLabels)) {
            $periodPreset = 'month';
        }

        // Month + Year pickers (used in "month" mode); default to the latest month
        // that has reports (tenant-scoped).
        $latest = DailyReport::max('report_date');
        $earliest = DailyReport::min('report_date');
        $default = $latest ? Carbon::parse($latest) : $now;
        $minYear = min($earliest ? (int) Carbon::parse($earliest)->year : $now->year, $now->year - 4);
        $yearOptions = range($now->year + 1, $minYear); // newest first
        $selectedMonthNum = (int) $request->query('m', $default->month);
        $selectedYear = (int) $request->query('y', $default->year);
        if ($selectedMonthNum < 1 || $selectedMonthNum > 12) {
            $selectedMonthNum = $default->month;
        }
        if (! in_array($selectedYear, $yearOptions, true)) {
            $selectedYear = $default->year;
        }

        // One window (+ comparison windows) used across the whole page.
        $pw = $this->resolveWindow($periodPreset, $selectedYear, $selectedMonthNum);

        $circularMetrics = $metrics->forUser($pw['start'], $pw['end'], $selectedStoreId);
        $circularMetricsPeriod = $pw['label'];

        $perfNet = $metrics->netSales($pw['start'], $pw['end'], $selectedStoreId);
        $performance = [
            'preset' => $periodPreset,
            'label' => $pw['label'],
            'net_sales' => $perfNet,
            'pop_label' => $pw['pop_label'],
            'show_pop' => $pw['prev'] !== null,
            'pop' => $pw['prev'] ? $this->pctChange($perfNet, $metrics->netSales($pw['prev'][0], $pw['prev'][1], $selectedStoreId)) : null,
            'show_yoy' => $pw['yoy'] !== null,
            'yoy' => $pw['yoy'] ? $this->pctChange($perfNet, $metrics->netSales($pw['yoy'][0], $pw['yoy'][1], $selectedStoreId)) : null,
        ];

        // Daily sales flow — one net-sales line per store, anchored to the latest data.
        $storeTrends = $this->getStoreSalesTrends($selectedStoreId);

        // Prepare data for impersonation modal (admin only)
        $modalOwnersData = [];
        $modalManagersData = [];

        if ($user && $user->isAdmin()) {
            $allOwners = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)->orderBy('name')->get();
            $allManagers = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)->with('store')->orderBy('name')->get();

            $modalOwnersData = $allOwners->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                ];
            })->toArray();

            $modalManagersData = $allManagers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'store_name' => $user->store ? $user->store->store_info : null,
                ];
            })->toArray();
        }

        return view('dashboard.index', compact('analytics', 'modalOwnersData', 'modalManagersData', 'circularMetrics', 'circularMetricsPeriod', 'yearOptions', 'selectedYear', 'selectedMonthNum', 'storeOptions', 'selectedStore', 'performance', 'periodLabels', 'storeTrends'));
    }

    /**
     * Resolve a period preset to its window, plus the previous-period and
     * year-over-year comparison windows (null when a comparison doesn't apply).
     *
     * @return array{start:Carbon, end:Carbon, label:string, pop_label:string, prev:?array, yoy:?array}
     */
    private function resolveWindow(string $preset, int $year, int $month): array
    {
        $now = Carbon::now();

        if ($preset === 'month') {
            $a = Carbon::create($year, $month, 1)->startOfMonth();

            return [
                'start' => $a->copy()->startOfMonth(), 'end' => $a->copy()->endOfMonth(),
                'label' => $a->format('F Y'), 'pop_label' => 'vs previous month',
                'prev' => [$a->copy()->subMonthNoOverflow()->startOfMonth(), $a->copy()->subMonthNoOverflow()->endOfMonth()],
                'yoy' => [$a->copy()->subYear()->startOfMonth(), $a->copy()->subYear()->endOfMonth()],
            ];
        }

        return match ($preset) {
            'last_month' => (function () use ($now) {
                $a = $now->copy()->subMonthNoOverflow();
                return [
                    'start' => $a->copy()->startOfMonth(), 'end' => $a->copy()->endOfMonth(),
                    'label' => $a->format('F Y'), 'pop_label' => 'vs previous month',
                    'prev' => [$a->copy()->subMonthNoOverflow()->startOfMonth(), $a->copy()->subMonthNoOverflow()->endOfMonth()],
                    'yoy' => [$a->copy()->subYear()->startOfMonth(), $a->copy()->subYear()->endOfMonth()],
                ];
            })(),
            'last_3_months' => (function () use ($now) {
                $start = $now->copy()->subMonthsNoOverflow(2)->startOfMonth();
                $end = $now->copy()->endOfMonth();
                return [
                    'start' => $start, 'end' => $end, 'label' => 'Last 3 months', 'pop_label' => 'vs prior 3 months',
                    'prev' => [$start->copy()->subMonthsNoOverflow(3), $start->copy()->subDay()],
                    'yoy' => [$start->copy()->subYear(), $end->copy()->subYear()],
                ];
            })(),
            'last_year' => (function () use ($now) {
                $a = $now->copy()->subYear();
                return [
                    'start' => $a->copy()->startOfYear(), 'end' => $a->copy()->endOfYear(),
                    'label' => $a->format('Y'), 'pop_label' => 'vs previous year',
                    'prev' => [$a->copy()->subYear()->startOfYear(), $a->copy()->subYear()->endOfYear()], 'yoy' => null,
                ];
            })(),
            'current_year' => (function () use ($now) {
                return [
                    'start' => $now->copy()->startOfYear(), 'end' => $now->copy()->endOfYear(),
                    'label' => $now->format('Y'), 'pop_label' => 'vs last year',
                    'prev' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()], 'yoy' => null,
                ];
            })(),
            'all_time' => (function () {
                $min = DailyReport::min('report_date');
                return [
                    'start' => $min ? Carbon::parse($min)->startOfDay() : Carbon::now()->subYears(20),
                    'end' => Carbon::now()->endOfDay(), 'label' => 'All time', 'pop_label' => '',
                    'prev' => null, 'yoy' => null,
                ];
            })(),
            default => (function () use ($now) { // current_month
                return [
                    'start' => $now->copy()->startOfMonth(), 'end' => $now->copy()->endOfMonth(),
                    'label' => $now->format('F Y'), 'pop_label' => 'vs last month',
                    'prev' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
                    'yoy' => [$now->copy()->subYear()->startOfMonth(), $now->copy()->subYear()->endOfMonth()],
                ];
            })(),
        };
    }

    /**
     * Percentage change vs a baseline. Null when the baseline is 0 (can't divide).
     *
     * @return array{pct:float, up:bool}|null
     */
    private function pctChange(float $current, float $base): ?array
    {
        if ($base <= 0) {
            return null;
        }

        $pct = round(($current - $base) / $base * 100, 1);

        return ['pct' => $pct, 'up' => $pct >= 0];
    }

    public function getAnalyticsData($user)
    {
        $query = DailyReport::with(['store', 'creator'])
            ->withSum('transactions', 'amount')
            ->withSum('revenues', 'amount');

        // Filter based on user role
        if ($user->isAdmin()) {
            // Admin can see all data - no filters
        } elseif ($user->isOwner()) {
            // Owner can see only stores they created
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user->isManager()) {
            // Manager can see only assigned stores
            $query->where('store_id', $user->store_id);
        }

        $baseQuery = clone $query;

        // Date ranges
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            // Overview stats
            'overview' => $this->getOverviewStats(clone $baseQuery, $today, $thisWeek, $thisMonth),

            // Daily trends (last 30 days)
            'dailyTrends' => $this->getDailyTrends(clone $baseQuery, $today),

            // Weekly trends (last 12 weeks)
            'weeklyTrends' => $this->getWeeklyTrends(clone $baseQuery, $thisWeek),

            // Monthly comparison
            'monthlyComparison' => $this->getMonthlyComparison(clone $baseQuery, $thisMonth, $lastMonth, $user),

            // Store performance
            'storePerformance' => $this->getStorePerformance(clone $baseQuery),

            // Insights and alerts
            'insights' => $this->getInsights(clone $baseQuery, $user),

            // Top performing days
            'topDays' => $this->getTopPerformingDays(clone $baseQuery),

            // Financial analysis
            'financialAnalysis' => $this->getFinancialAnalysis(clone $baseQuery, $user),

            // Customer analytics
            'customerAnalytics' => $this->getCustomerAnalytics(clone $baseQuery, $user),

            // Third-party platform fees (all-time, role-scoped)
            'thirdParty' => $this->getThirdPartyDashboardStats($user),
        ];
    }

    private function getOverviewStats($query, $today, $thisWeek, $thisMonth)
    {
        $todayQuery = clone $query;
        $weekQuery = clone $query;
        $monthQuery = clone $query;

        return [
            'today' => [
                'reports' => $todayQuery->whereDate('report_date', $today)->count(),
                'grossSales' => $todayQuery->whereDate('report_date', $today)->sum('gross_sales'),
                'netSales' => $todayQuery->whereDate('report_date', $today)->sum('net_sales'),
            ],
            'thisWeek' => [
                'reports' => $weekQuery->where('report_date', '>=', $thisWeek)->count(),
                'grossSales' => $weekQuery->where('report_date', '>=', $thisWeek)->sum('gross_sales'),
                'netSales' => $weekQuery->where('report_date', '>=', $thisWeek)->sum('net_sales'),
            ],
            'thisMonth' => [
                'reports' => $monthQuery->where('report_date', '>=', $thisMonth)->count(),
                'grossSales' => $monthQuery->where('report_date', '>=', $thisMonth)->sum('gross_sales'),
                'netSales' => $monthQuery->where('report_date', '>=', $thisMonth)->sum('net_sales'),
            ],
        ];
    }

    private function getDailyTrends($query, $today)
    {
        return $query->where('report_date', '>=', $today->copy()->subDays(90))
            ->select(
                DB::raw('DATE(report_date) as date'),
                DB::raw('SUM(gross_sales) as total_gross'),
                DB::raw('SUM(net_sales) as total_net'),
                DB::raw('COUNT(*) as report_count'),
                DB::raw('AVG(gross_sales) as avg_gross')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Daily net-sales timeline with one series per store. Net sales come from the
     * revenue line items (the cached columns are usually 0), and the window is the
     * 90 days up to the latest report that exists — so the chart shows data even
     * when the most recent reports are older than 90 calendar days. Tenant-scoped;
     * optionally narrowed to a single store.
     *
     * @return array{labels: array<int, string>, datasets: array<int, array{store: string, data: array}>}
     */
    private function getStoreSalesTrends(?int $storeId = null): array
    {
        $latest = DailyReport::max('report_date');
        if (! $latest) {
            return ['labels' => [], 'datasets' => []];
        }

        $end = Carbon::parse($latest)->endOfDay();
        $start = $end->copy()->subDays(90)->startOfDay();

        $reports = DailyReport::withSum('revenues', 'amount')
            ->whereBetween('report_date', [$start, $end])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->orderBy('report_date')
            ->get();

        if ($reports->isEmpty()) {
            return ['labels' => [], 'datasets' => []];
        }

        $dates = [];
        $byStore = [];
        foreach ($reports as $r) {
            $date = Carbon::parse($r->report_date)->format('Y-m-d');
            $dates[$date] = true;

            $sid = (int) ($r->store_id ?? 0);
            $byStore[$sid]['name'] ??= ($r->store?->store_info ?? 'Unknown store');

            // net sales = revenues − coupons − adjustments, with the column fallback.
            $rev = (float) ($r->revenues_sum_amount ?? 0);
            $net = $rev - (float) $r->coupons_received - (float) $r->adjustments_overrings;
            if (abs($net) < 0.001) {
                $net = (float) ($r->getRawOriginal('net_sales') ?: $r->getRawOriginal('gross_sales') ?: 0);
            }
            $byStore[$sid]['points'][$date] = ($byStore[$sid]['points'][$date] ?? 0) + $net;
        }

        $labels = array_keys($dates);
        sort($labels);

        $datasets = [];
        foreach ($byStore as $info) {
            $data = [];
            foreach ($labels as $date) {
                $data[] = $info['points'][$date] ?? null; // null = no report that day (gap)
            }
            $datasets[] = ['store' => $info['name'], 'data' => $data];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    private function getWeeklyTrends($query, $thisWeek)
    {
        return $query->where('report_date', '>=', $thisWeek->copy()->subWeeks(26))
            ->select(
                DB::raw('YEARWEEK(report_date) as week'),
                DB::raw('SUM(gross_sales) as total_gross'),
                DB::raw('SUM(net_sales) as total_net'),
                DB::raw('COUNT(*) as report_count')
            )
            ->groupBy(DB::raw('YEARWEEK(report_date)'))
            ->orderBy(DB::raw('YEARWEEK(report_date)'))
            ->get();
    }

    private function getMonthlyComparison($query, $thisMonth, $lastMonth, $user)
    {
        // Create fresh queries without the withSum relationships for aggregate calculations
        $baseFilters = $query->getQuery();
        $thisMonthQuery = DailyReport::query();
        $lastMonthQuery = DailyReport::query();

        // Apply the same role-based filters to both queries
        if ($user->isOwner()) {
            $thisMonthQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
            $lastMonthQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user->isManager()) {
            $thisMonthQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
            $lastMonthQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $thisMonthData = $thisMonthQuery->where('report_date', '>=', $thisMonth)
            ->selectRaw('
                SUM(gross_sales) as gross_sales,
                SUM(net_sales) as net_sales,
                COUNT(*) as reports,
                AVG(total_customers) as avg_customers
            ')
            ->first();

        $lastMonthData = $lastMonthQuery->whereBetween('report_date', [$lastMonth, $lastMonth->copy()->endOfMonth()])
            ->selectRaw('
                SUM(gross_sales) as gross_sales,
                SUM(net_sales) as net_sales,
                COUNT(*) as reports,
                AVG(total_customers) as avg_customers
            ')
            ->first();

        return [
            'current' => $thisMonthData,
            'previous' => $lastMonthData,
            'changes' => $this->calculateChanges($thisMonthData, $lastMonthData),
        ];
    }

    private function getStorePerformance($query)
    {
        return $query->where('report_date', '>=', Carbon::now()->subDays(90))
            ->join('stores', 'daily_reports.store_id', '=', 'stores.id')
            ->select(
                'stores.store_info',
                'stores.id as store_id',
                DB::raw('SUM(daily_reports.gross_sales) as total_gross'),
                DB::raw('SUM(daily_reports.net_sales) as total_net'),
                DB::raw('COUNT(*) as report_count'),
                DB::raw('AVG(daily_reports.gross_sales) as avg_gross')
            )
            ->groupBy('stores.id', 'stores.store_info')
            ->orderBy('total_gross', 'desc')
            ->get();
    }

    private function getInsights($query, $user)
    {
        $insights = [];

        // Missing daily reports — flag accessible stores that haven't reported
        // recently (or ever). accessibleStores() is role/tenant aware.
        $stores = $user->accessibleStores()->get();
        if ($stores->isNotEmpty()) {
            $lastByStore = DailyReport::whereIn('store_id', $stores->pluck('id'))
                ->selectRaw('store_id, MAX(report_date) as last_date')
                ->groupBy('store_id')
                ->pluck('last_date', 'store_id');

            $today = Carbon::today();
            foreach ($stores as $store) {
                $last = $lastByStore->get($store->id);

                if (! $last) {
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => '📋',
                        'title' => 'Missing daily reports',
                        'message' => "'{$store->store_info}' has no daily reports yet.",
                        'date' => Carbon::now(),
                    ];

                    continue;
                }

                $lastDate = Carbon::parse($last)->startOfDay();
                $daysBehind = (int) $lastDate->diffInDays($today);

                if ($daysBehind >= 2) {
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => '📋',
                        'title' => 'Missing daily reports',
                        'message' => "'{$store->store_info}' — no report since {$lastDate->format(config('dates.display'))} ({$daysBehind} days behind).",
                        'date' => Carbon::now(),
                    ];
                }
            }
        }

        // Monthly statement uploads — after the 10th, last month's statement should
        // be in. For each statement type the tenant actually uses, warn when nothing
        // has been uploaded yet this month. Models are tenant-scoped automatically.
        if (Carbon::now()->day > 10) {
            $lastMonth = Carbon::now()->subMonthNoOverflow();
            $monthStart = Carbon::now()->startOfMonth();

            $statementSources = [
                ['Bank', \App\Models\ImportBatch::where('import_type', 'bank_statement')],
                ['Owner credit card', \App\Models\OwnerCcStatementImport::query()],
                ['Third-party delivery', \App\Models\ThirdPartyStatement::query()],
            ];

            foreach ($statementSources as [$name, $base]) {
                // Only remind about statement types this tenant has used before.
                if (! (clone $base)->exists()) {
                    continue;
                }

                if (! (clone $base)->where('created_at', '>=', $monthStart)->exists()) {
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => '📄',
                        'title' => 'Statement upload due',
                        'message' => "Last month's {$name} statement ({$lastMonth->format('F Y')}) hasn't been uploaded yet.",
                        'date' => Carbon::now(),
                    ];
                }
            }
        }

        // Get recent reports for analysis
        $recentReports = $query->where('report_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('report_date', 'desc')
            ->get();

        // Check for significant variances
        foreach ($recentReports as $report) {
            $storeName = $report->store?->store_info ?? 'Unknown Store';

            if ($report->projected_sales > 0) {
                $variance = abs($report->gross_sales - $report->projected_sales) / $report->projected_sales * 100;

                if ($variance > 25) {
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => '⚠️',
                        'title' => 'High Sales Variance',
                        'message' => "Store '{$storeName}' had {$variance}% variance from projected on {$report->report_date->format(config('dates.display'))}",
                        'date' => $report->report_date,
                    ];
                }
            }

            // Check for high cancels/voids amounts
            if ($report->amount_of_cancels > 100 || $report->amount_of_voids > 100) {
                $type = $report->amount_of_cancels > $report->amount_of_voids ? 'cancels' : 'voids';
                $amount = $report->amount_of_cancels > $report->amount_of_voids ? $report->amount_of_cancels : $report->amount_of_voids;

                $insights[] = [
                    'type' => 'alert',
                    'icon' => '🔍',
                    'title' => 'High '.ucfirst($type),
                    'message' => "Store '{$storeName}' had ${$amount} in {$type} on {$report->report_date->format(config('dates.display'))}",
                    'date' => $report->report_date,
                ];
            }
        }

        // Identify trends - create fresh query to avoid conflicts with withSum
        $trendsQuery = DailyReport::query();

        // Apply the same role-based filters
        if ($user->isOwner()) {
            $trendsQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user->isManager()) {
            $trendsQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $weeklyTotals = $trendsQuery->where('report_date', '>=', Carbon::now()->subWeeks(4))
            ->select(
                DB::raw('WEEK(report_date) as week'),
                DB::raw('SUM(gross_sales) as total')
            )
            ->groupBy(DB::raw('WEEK(report_date)'))
            ->orderBy(DB::raw('WEEK(report_date)'))
            ->pluck('total')
            ->toArray();

        if (count($weeklyTotals) >= 2) {
            $trend = end($weeklyTotals) - $weeklyTotals[count($weeklyTotals) - 2];
            if (abs($trend) > 1000) {
                $direction = $trend > 0 ? 'increasing' : 'decreasing';
                $insights[] = [
                    'type' => $trend > 0 ? 'success' : 'info',
                    'icon' => $trend > 0 ? '📈' : '📉',
                    'title' => 'Sales Trend',
                    'message' => "Sales are {$direction} week over week by \${$trend}",
                    'date' => Carbon::now(),
                ];
            }
        }

        return collect($insights)->sortByDesc('date')->take(10)->values();
    }

    private function getTopPerformingDays($query)
    {
        return $query->where('report_date', '>=', Carbon::now()->subDays(90))
            ->orderBy('gross_sales', 'desc')
            ->take(5)
            ->get();
    }

    private function calculateChanges($current, $previous)
    {
        if (! $previous) {
            return null;
        }

        return [
            'gross_sales' => $this->calculatePercentChange($current->gross_sales, $previous->gross_sales),
            'net_sales' => $this->calculatePercentChange($current->net_sales, $previous->net_sales),
            'reports' => $this->calculatePercentChange($current->reports, $previous->reports),
            'avg_customers' => $this->calculatePercentChange($current->avg_customers, $previous->avg_customers),
        ];
    }

    private function calculatePercentChange($current, $previous)
    {
        if ($previous == 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get financial analysis including profit margins and tax ratios
     */
    private function getFinancialAnalysis($query, $user)
    {
        // Create fresh query without the withSum relationships for aggregate calculations
        $financialQuery = DailyReport::query();

        // Apply the same role-based filters
        if ($user && $user->isOwner()) {
            $financialQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user && $user->isManager()) {
            $financialQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $financialData = $financialQuery->where('report_date', '>=', Carbon::now()->subDays(90))
            ->selectRaw('
                SUM(gross_sales) as total_gross,
                SUM(net_sales) as total_net,
                SUM(tax) as total_tax,
                SUM(amount_of_cancels) as total_cancels,
                SUM(amount_of_voids) as total_voids,
                SUM(coupons_received) as total_coupons,
                SUM(adjustments_overrings) as total_adjustments,
                SUM(credit_cards) as total_credit_cards,
                COUNT(*) as report_count,
                AVG(gross_sales) as avg_daily_gross,
                AVG(net_sales) as avg_daily_net
            ')
            ->first();

        if (! $financialData || $financialData->total_gross == 0) {
            return [];
        }

        return [
            'profitMargin' => round((($financialData->total_net / $financialData->total_gross) * 100), 2),
            'taxRate' => round((($financialData->total_tax / $financialData->total_gross) * 100), 2),
            'cancelRate' => round((($financialData->total_cancels / $financialData->total_gross) * 100), 2),
            'voidRate' => round((($financialData->total_voids / $financialData->total_gross) * 100), 2),
            'couponUsage' => round((($financialData->total_coupons / $financialData->total_gross) * 100), 2),
            'creditCardRatio' => round((($financialData->total_credit_cards / $financialData->total_gross) * 100), 2),
            'avgDailySales' => round($financialData->avg_daily_gross, 2),
            'totalGross' => round($financialData->total_gross, 2),
            'totalNet' => round($financialData->total_net, 2),
            'reportCount' => $financialData->report_count,
        ];
    }

    /**
     * Get customer analytics including average ticket and customer trends
     */
    private function getCustomerAnalytics($query, $user)
    {
        // Create fresh query without the withSum relationships for aggregate calculations
        $customerQuery = DailyReport::query();

        // Apply the same role-based filters
        if ($user && $user->isOwner()) {
            $customerQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user && $user->isManager()) {
            $customerQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $customerData = $customerQuery->where('report_date', '>=', Carbon::now()->subDays(90))
            ->selectRaw('
                SUM(total_customers) as total_customers,
                AVG(total_customers) as avg_daily_customers,
                AVG(average_ticket) as avg_ticket_amount,
                SUM(gross_sales) as total_sales,
                COUNT(*) as report_count
            ')
            ->first();

        if (! $customerData || $customerData->total_customers == 0) {
            return [];
        }

        // Daily customer trends - create another fresh query
        $trendsQuery = DailyReport::query();

        // Apply the same role-based filters
        if ($user && $user->isOwner()) {
            $trendsQuery->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user && $user->isManager()) {
            $trendsQuery->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $dailyCustomerTrends = $trendsQuery->where('report_date', '>=', Carbon::now()->subDays(90))
            ->select(
                DB::raw('DATE(report_date) as date'),
                DB::raw('SUM(total_customers) as customers'),
                DB::raw('SUM(gross_sales) as sales'),
                DB::raw('AVG(average_ticket) as avg_ticket')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate actual average ticket from data
        $actualAvgTicket = $customerData->total_sales / $customerData->total_customers;

        return [
            'totalCustomers' => (int) $customerData->total_customers,
            'avgDailyCustomers' => round($customerData->avg_daily_customers, 0),
            'avgTicketAmount' => round($actualAvgTicket, 2),
            'reportedAvgTicket' => round($customerData->avg_ticket_amount, 2),
            'totalSales' => round($customerData->total_sales, 2),
            'customerTrends' => $dailyCustomerTrends,
            'revenuePerCustomer' => round($actualAvgTicket, 2),
        ];
    }

    /**
     * Get all-time third-party platform fee totals, scoped to the user's accessible stores.
     */
    private function getThirdPartyDashboardStats($user): array
    {
        $query = ThirdPartyStatement::query();

        if ($user->isOwner()) {
            $ownedStoreIds = Store::where('created_by', $user->id)->pluck('id');
            $query->whereIn('store_id', $ownedStoreIds);
        } elseif ($user->isManager()) {
            $query->where('store_id', $user->store_id);
        }

        $stats = $query->selectRaw('
            COALESCE(SUM(gross_sales), 0)                                                      as total_gross_sales,
            COALESCE(SUM(marketing_fees + delivery_fees + processing_fees + CASE WHEN platform = \'grubhub\' THEN 0 ELSE COALESCE(adjustments, 0) END), 0) as total_fees,
            COALESCE(SUM(net_deposit), 0)                                                      as total_net_deposit,
            COUNT(*)                                                                            as statement_count
        ')->first();

        $avgFeePercent = $stats->total_gross_sales > 0
            ? round(($stats->total_fees / $stats->total_gross_sales) * 100, 2)
            : 0;

        // Per-platform breakdown (all time)
        $breakdownQuery = ThirdPartyStatement::query();
        if ($user->isOwner()) {
            $ownedStoreIds = Store::where('created_by', $user->id)->pluck('id');
            $breakdownQuery->whereIn('store_id', $ownedStoreIds);
        } elseif ($user->isManager()) {
            $breakdownQuery->where('store_id', $user->store_id);
        }

        $breakdown = $breakdownQuery->select(
            'platform',
            DB::raw('SUM(gross_sales) as total_gross_sales'),
            DB::raw('SUM(marketing_fees + delivery_fees + processing_fees + CASE WHEN platform = \'grubhub\' THEN 0 ELSE COALESCE(adjustments, 0) END) as total_fees'),
            DB::raw('SUM(net_deposit) as total_net_deposit'),
            DB::raw('COUNT(*) as statement_count')
        )
            ->groupBy('platform')
            ->orderByDesc('total_fees')
            ->get();

        return [
            'total_gross_sales'   => (float) $stats->total_gross_sales,
            'total_fees'          => (float) $stats->total_fees,
            'total_net_deposit'   => (float) $stats->total_net_deposit,
            'statement_count'     => (int) $stats->statement_count,
            'avg_fee_percentage'  => $avgFeePercent,
            'breakdown'           => $breakdown,
        ];
    }

    /**
     * API endpoint for AJAX chart data updates
     */
    public function getChartData(Request $request)
    {
        $user = auth()->user();
        $type = $request->get('type', 'daily');
        $analytics = $this->getAnalyticsData($user);

        switch ($type) {
            case 'daily':
                return response()->json($analytics['dailyTrends']);
            case 'weekly':
                return response()->json($analytics['weeklyTrends']);
            case 'store':
                return response()->json($analytics['storePerformance']);
            case 'overview':
                return response()->json($analytics['overview']);
            default:
                return response()->json($analytics);
        }
    }

    /**
     * Export analytics data
     */
    public function exportData(Request $request)
    {
        $user = auth()->user();
        $format = $request->get('format', 'csv');
        $type = $request->get('type', 'overview');

        $analytics = $this->getAnalyticsData($user);

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($analytics, $type);
            case 'pdf':
                return $this->exportToPdf($analytics, $type);
            case 'excel':
                return $this->exportToExcel($analytics, $type);
            default:
                return response()->json(['error' => 'Invalid format'], 400);
        }
    }

    private function exportToCsv($analytics, $type)
    {
        $filename = "analytics_{$type}_".date('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($analytics, $type) {
            $file = fopen('php://output', 'w');

            if ($type === 'store_performance') {
                fputcsv($file, ['Store', 'Total Gross Sales', 'Total Net Sales', 'Report Count', 'Average Daily Sales']);
                foreach ($analytics['storePerformance'] as $store) {
                    fputcsv($file, [
                        $store->store_info,
                        number_format($store->total_gross, 2),
                        number_format($store->total_net, 2),
                        $store->report_count,
                        number_format($store->avg_gross, 2),
                    ]);
                }
            } elseif ($type === 'daily_trends') {
                fputcsv($file, ['Date', 'Gross Sales', 'Net Sales', 'Report Count']);
                foreach ($analytics['dailyTrends'] as $trend) {
                    fputcsv($file, [
                        $trend->date,
                        number_format($trend->total_gross, 2),
                        number_format($trend->total_net, 2),
                        $trend->report_count,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToPdf($analytics, $type)
    {
        // Implementation would require a PDF library like DOMPDF
        // For now, return a simple response
        return response()->json(['message' => 'PDF export not yet implemented'], 501);
    }

    private function exportToExcel($analytics, $type)
    {
        // Implementation would require Laravel Excel package
        // For now, return a simple response
        return response()->json(['message' => 'Excel export not yet implemented'], 501);
    }

    /**
     * Get users for impersonation (Admin only)
     */
    public function getUsersForImpersonation(Request $request)
    {
        $user = auth()->user();

        // Only admins can access this
        if (! $user->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // 'all', 'owners', 'managers'

        $owners = collect();
        $managers = collect();

        if ($type === 'all' || $type === 'owners') {
            $ownersQuery = \App\Models\User::where('role', \App\Enums\UserRole::OWNER)
                ->orderBy('name');

            if ($query) {
                $ownersQuery->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%");
                });
            }

            $owners = $ownersQuery->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'role' => 'owner',
                ];
            });
        }

        if ($type === 'all' || $type === 'managers') {
            $managersQuery = \App\Models\User::where('role', \App\Enums\UserRole::MANAGER)
                ->with('store')
                ->orderBy('name');

            if ($query) {
                $managersQuery->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('email', 'LIKE', "%{$query}%")
                        ->orWhereHas('store', function ($sq) use ($query) {
                            $sq->where('store_info', 'LIKE', "%{$query}%");
                        });
                });
            }

            $managers = $managersQuery->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'role' => 'manager',
                    'store_name' => $user->store ? $user->store->store_info : null,
                ];
            });
        }

        return response()->json([
            'owners' => $owners,
            'managers' => $managers,
            'total' => $owners->count() + $managers->count(),
        ]);
    }
}
