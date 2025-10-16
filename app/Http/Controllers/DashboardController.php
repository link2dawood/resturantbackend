<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get analytics data based on user role
        $analytics = $this->getAnalyticsData($user);

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

        return view('dashboard.index', compact('analytics', 'modalOwnersData', 'modalManagersData'));
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
        return $query->where('report_date', '>=', $today->copy()->subDays(30))
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

    private function getWeeklyTrends($query, $thisWeek)
    {
        return $query->where('report_date', '>=', $thisWeek->copy()->subWeeks(12))
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
        return $query->where('report_date', '>=', Carbon::now()->subDays(30))
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

        // Get recent reports for analysis
        $recentReports = $query->where('report_date', '>=', Carbon::now()->subDays(7))
            ->orderBy('report_date', 'desc')
            ->get();

        // Check for significant variances
        foreach ($recentReports as $report) {
            if ($report->projected_sales > 0) {
                $variance = abs($report->gross_sales - $report->projected_sales) / $report->projected_sales * 100;

                if ($variance > 25) {
                    $insights[] = [
                        'type' => 'warning',
                        'icon' => '⚠️',
                        'title' => 'High Sales Variance',
                        'message' => "Store '{$report->store->store_info}' had {$variance}% variance from projected on {$report->report_date->format('M j')}",
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
                    'message' => "Store '{$report->store->store_info}' had ${$amount} in {$type} on {$report->report_date->format('M j')}",
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
        return $query->where('report_date', '>=', Carbon::now()->subDays(30))
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

        $financialData = $financialQuery->where('report_date', '>=', Carbon::now()->subDays(30))
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

        $customerData = $customerQuery->where('report_date', '>=', Carbon::now()->subDays(30))
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

        $dailyCustomerTrends = $trendsQuery->where('report_date', '>=', Carbon::now()->subDays(30))
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
