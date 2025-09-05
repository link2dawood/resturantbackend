<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyReport;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get analytics data based on user role
        $analytics = $this->getAnalyticsData($user);
        
        return view('dashboard.index', compact('analytics'));
    }

    public function getAnalyticsData($user)
    {
        $query = DailyReport::with(['store', 'creator']);
        
        // Filter based on user role
        if ($user->hasPermission('view_reports')) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
        } elseif ($user->hasPermission('view_stores')) {
            $query->whereHas('store', function ($q) use ($user) {
                $q->whereHas('managers', function ($subQ) use ($user) {
                    $subQ->where('users.id', $user->id);
                });
            });
        }

        $baseQuery = clone $query;

        // Date ranges
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            // Overview stats
            'overview' => $this->getOverviewStats($baseQuery, $today, $thisWeek, $thisMonth),
            
            // Daily trends (last 30 days)
            'dailyTrends' => $this->getDailyTrends($baseQuery, $today),
            
            // Weekly trends (last 12 weeks)
            'weeklyTrends' => $this->getWeeklyTrends($baseQuery, $thisWeek),
            
            // Monthly comparison
            'monthlyComparison' => $this->getMonthlyComparison($baseQuery, $thisMonth, $lastMonth),
            
            // Store performance
            'storePerformance' => $this->getStorePerformance($baseQuery),
            
            // Insights and alerts
            'insights' => $this->getInsights($baseQuery),
            
            // Top performing days
            'topDays' => $this->getTopPerformingDays($baseQuery),
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
            ->groupBy('week')
            ->orderBy('week')
            ->get();
    }

    private function getMonthlyComparison($query, $thisMonth, $lastMonth)
    {
        $thisMonthData = $query->where('report_date', '>=', $thisMonth)
            ->selectRaw('
                SUM(gross_sales) as gross_sales,
                SUM(net_sales) as net_sales,
                COUNT(*) as reports,
                AVG(total_customers) as avg_customers
            ')
            ->first();

        $lastMonthData = $query->whereBetween('report_date', [$lastMonth, $lastMonth->copy()->endOfMonth()])
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
            'changes' => $this->calculateChanges($thisMonthData, $lastMonthData)
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

    private function getInsights($query)
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
            
            // Check for high short/over amounts
            if (abs($report->short) > 100 || abs($report->over) > 100) {
                $type = $report->short != 0 ? 'short' : 'over';
                $amount = $report->short != 0 ? abs($report->short) : $report->over;
                
                $insights[] = [
                    'type' => 'alert',
                    'icon' => '🔍',
                    'title' => 'Cash Discrepancy',
                    'message' => "Store '{$report->store->store_info}' was ${$amount} {$type} on {$report->report_date->format('M j')}",
                    'date' => $report->report_date,
                ];
            }
        }

        // Identify trends
        $weeklyTotals = $query->where('report_date', '>=', Carbon::now()->subWeeks(4))
            ->select(
                DB::raw('WEEK(report_date) as week'),
                DB::raw('SUM(gross_sales) as total')
            )
            ->groupBy('week')
            ->orderBy('week')
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
        if (!$previous) return null;

        return [
            'gross_sales' => $this->calculatePercentChange($current->gross_sales, $previous->gross_sales),
            'net_sales' => $this->calculatePercentChange($current->net_sales, $previous->net_sales),
            'reports' => $this->calculatePercentChange($current->reports, $previous->reports),
            'avg_customers' => $this->calculatePercentChange($current->avg_customers, $previous->avg_customers),
        ];
    }

    private function calculatePercentChange($current, $previous)
    {
        if ($previous == 0) return null;
        return round((($current - $previous) / $previous) * 100, 1);
    }
}
