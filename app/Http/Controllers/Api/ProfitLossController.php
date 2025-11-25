<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\ThirdPartyStatement;
use App\Models\ChartOfAccount;
use App\Models\PlSnapshot;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    /**
     * Get complete P&L report
     */
    public function index(Request $request)
    {
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $comparisonPeriod = $request->input('comparison_period'); // 'previous_period', 'previous_year', null
        
        // Calculate current period P&L
        $pl = $this->calculatePL($storeId, $startDate, $endDate);
        
        // Calculate comparison period if requested
        $comparison = null;
        if ($comparisonPeriod && $startDate && $endDate) {
            $comparisonDates = $this->getComparisonDates($startDate, $endDate, $comparisonPeriod);
            $comparison = $this->calculatePL($storeId, $comparisonDates['start'], $comparisonDates['end']);
            
            // Add variance calculations
            $pl = $this->addVariance($pl, $comparison);
        }
        
        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'comparison_period' => $comparisonPeriod,
            'pl' => $pl,
        ]);
    }

    /**
     * Get P&L summary (high-level only)
     */
    public function summary(Request $request)
    {
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $pl = $this->calculatePL($storeId, $startDate, $endDate);
        
        return response()->json([
            'revenue' => $pl['revenue']['total'],
            'cogs' => $pl['cogs']['total'],
            'gross_profit' => $pl['gross_profit'],
            'operating_expenses' => $pl['operating_expenses']['total'],
            'net_profit' => $pl['net_profit'],
            'gross_margin' => $pl['gross_margin'],
            'net_margin' => $pl['net_margin'],
        ]);
    }

    /**
     * Save P&L snapshot
     */
    public function snapshot(Request $request)
    {
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'name' => 'required|string|max:255',
        ]);
        
        $pl = $this->calculatePL(
            $request->input('store_id'),
            $request->input('start_date'),
            $request->input('end_date')
        );
        
        $snapshot = PlSnapshot::create([
            'name' => $request->input('name'),
            'store_id' => $request->input('store_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'pl_data' => $pl,
            'created_by' => auth()->id(),
        ]);
        
        return response()->json([
            'message' => 'Snapshot saved successfully',
            'snapshot_id' => $snapshot->id,
        ], 201);
    }

    /**
     * Get list of P&L snapshots
     */
    public function snapshots(Request $request)
    {
        $query = PlSnapshot::with(['store', 'creator']);
        
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }
        
        $snapshots = $query->orderBy('created_at', 'desc')->paginate(25);
        
        return response()->json($snapshots);
    }

    /**
     * Get drill-down transactions for a specific COA
     * Returns all transactions at the transaction level for detailed analysis
     */
    public function drillDown(Request $request)
    {
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator'])
            ->where('coa_id', $request->coa_id)
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        
        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }
        
        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);
        
        $coa = ChartOfAccount::find($request->coa_id);
        
        // Calculate summary statistics
        $totalAmount = $transactions->sum('amount');
        $transactionCount = $transactions->total();
        $averageAmount = $transactionCount > 0 ? $totalAmount / $transactionCount : 0;
        
        return response()->json([
            'coa' => $coa,
            'transactions' => $transactions,
            'summary' => [
                'total_amount' => $totalAmount,
                'transaction_count' => $transactionCount,
                'average_amount' => $averageAmount,
                'date_range' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                'store_id' => $request->store_id,
            ],
        ]);
    }

    /**
     * Get consolidated multi-store P&L
     */
    public function consolidated(Request $request)
    {
        $request->validate([
            'store_ids' => 'required|array',
            'store_ids.*' => 'exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $storeIds = $request->input('store_ids');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Calculate consolidated P&L
        $consolidated = $this->calculatePL($storeIds, $startDate, $endDate);
        
        // Calculate per-store breakdown
        $storeBreakdown = [];
        foreach ($storeIds as $storeId) {
            $storeBreakdown[] = [
                'store' => Store::find($storeId),
                'pl' => $this->calculatePL($storeId, $startDate, $endDate),
            ];
        }
        
        return response()->json([
            'consolidated' => $consolidated,
            'store_breakdown' => $storeBreakdown,
        ]);
    }

    /**
     * Get store comparison
     */
    public function storeComparison(Request $request)
    {
        $request->validate([
            'store_ids' => 'required|array|min:2',
            'store_ids.*' => 'exists:stores,id',
            'metric' => 'required|in:revenue,profit,margin',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $storeIds = $request->input('store_ids');
        $metric = $request->input('metric');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $comparison = [];
        foreach ($storeIds as $storeId) {
            $pl = $this->calculatePL($storeId, $startDate, $endDate);
            $store = Store::find($storeId);
            
            $comparison[] = [
                'store_id' => $storeId,
                'store_name' => $store->store_info,
                'revenue' => $pl['revenue']['total'],
                'profit' => $pl['net_profit'],
                'margin' => $pl['net_margin'],
                'metric_value' => $pl[$metric === 'margin' ? 'net_margin' : ($metric === 'profit' ? 'net_profit' : 'revenue')][$metric === 'revenue' ? 'total' : ''] ?? $pl[$metric === 'margin' ? 'net_margin' : 'net_profit'],
            ];
        }
        
        // Sort by metric value descending
        usort($comparison, function($a, $b) use ($metric) {
            $valueA = $a['metric_value'];
            $valueB = $b['metric_value'];
            return $valueB <=> $valueA;
        });
        
        return response()->json([
            'metric' => $metric,
            'comparison' => $comparison,
        ]);
    }

    /**
     * Calculate P&L for given parameters
     */
    protected function calculatePL($storeId, $startDate, $endDate)
    {
        // Revenue
        $revenue = $this->calculateRevenue($storeId, $startDate, $endDate);
        
        // COGS
        $cogs = $this->calculateCOGS($storeId, $startDate, $endDate);
        
        // Gross Profit
        $grossProfit = $revenue['total'] - $cogs['total'];
        $grossMargin = $revenue['total'] > 0 ? ($grossProfit / $revenue['total']) * 100 : 0;
        
        // Operating Expenses
        $operatingExpenses = $this->calculateOperatingExpenses($storeId, $startDate, $endDate);
        
        // Net Profit
        $netProfit = $grossProfit - $operatingExpenses['total'];
        $netMargin = $revenue['total'] > 0 ? ($netProfit / $revenue['total']) * 100 : 0;
        
        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 2),
            'operating_expenses' => $operatingExpenses,
            'net_profit' => $netProfit,
            'net_margin' => round($netMargin, 2),
        ];
    }

    /**
     * Calculate revenue from daily reports and third-party statements
     */
    protected function calculateRevenue($storeId, $startDate, $endDate)
    {
        $items = [];
        
        // Food Sales and Beverage Sales from daily_reports
        $dailyReportsQuery = DailyReport::whereBetween('report_date', [$startDate, $endDate]);
        
        if ($storeId) {
            if (is_array($storeId)) {
                $dailyReportsQuery->whereIn('store_id', $storeId);
            } else {
                $dailyReportsQuery->where('store_id', $storeId);
            }
        }
        
        // Get gross sales (this is food + beverage combined)
        $grossSales = $dailyReportsQuery->sum('gross_sales');
        
        // Get credit card sales (part of gross sales)
        $creditCardSales = $dailyReportsQuery->sum('credit_cards');
        
        // Try to get food/beverage breakdown from revenue income types
        $foodRevenue = DailyReport::query()
            ->whereBetween('report_date', [$startDate, $endDate])
            ->whereHas('revenues.revenueIncomeType', function($q) {
                $q->where('name', 'like', '%Food%');
            });
        
        if ($storeId) {
            if (is_array($storeId)) {
                $foodRevenue->whereIn('store_id', $storeId);
            } else {
                $foodRevenue->where('store_id', $storeId);
            }
        }
        
        $foodSales = $foodRevenue->join('daily_report_revenues', 'daily_reports.id', '=', 'daily_report_revenues.daily_report_id')
            ->join('revenue_income_types', 'daily_report_revenues.revenue_income_type_id', '=', 'revenue_income_types.id')
            ->where('revenue_income_types.name', 'like', '%Food%')
            ->sum('daily_report_revenues.amount');
        
        $beverageSales = DailyReport::query()
            ->whereBetween('report_date', [$startDate, $endDate])
            ->whereHas('revenues.revenueIncomeType', function($q) {
                $q->where('name', 'like', '%Beverage%');
            });
        
        if ($storeId) {
            if (is_array($storeId)) {
                $beverageSales->whereIn('store_id', $storeId);
            } else {
                $beverageSales->where('store_id', $storeId);
            }
        }
        
        $beverageSales = $beverageSales->join('daily_report_revenues', 'daily_reports.id', '=', 'daily_report_revenues.daily_report_id')
            ->join('revenue_income_types', 'daily_report_revenues.revenue_income_type_id', '=', 'revenue_income_types.id')
            ->where('revenue_income_types.name', 'like', '%Beverage%')
            ->sum('daily_report_revenues.amount');
        
        // If no breakdown, estimate 80% food, 20% beverage
        if ($foodSales == 0 && $beverageSales == 0) {
            $foodSales = $grossSales * 0.8;
            $beverageSales = $grossSales * 0.2;
        }
        
        $items[] = [
            'name' => 'Food Sales',
            'amount' => $foodSales,
            'coa_id' => null,
        ];
        
        $items[] = [
            'name' => 'Beverage Sales',
            'amount' => $beverageSales,
            'coa_id' => null,
        ];
        
        // Third-Party Sales (from third_party_statements)
        $thirdPartyQuery = ThirdPartyStatement::whereBetween('statement_date', [$startDate, $endDate]);
        
        if ($storeId) {
            if (is_array($storeId)) {
                $thirdPartyQuery->whereIn('store_id', $storeId);
            } else {
                $thirdPartyQuery->where('store_id', $storeId);
            }
        }
        
        $thirdPartySales = $thirdPartyQuery->sum('gross_sales');
        
        $items[] = [
            'name' => 'Third-Party Sales',
            'amount' => $thirdPartySales,
            'coa_id' => null,
        ];
        
        // Other Income (from revenue entries that don't fit above)
        $otherIncome = DailyReport::query()
            ->whereBetween('report_date', [$startDate, $endDate])
            ->with('revenues.revenueIncomeType');
        
        if ($storeId) {
            if (is_array($storeId)) {
                $otherIncome->whereIn('store_id', $storeId);
            } else {
                $otherIncome->where('store_id', $storeId);
            }
        }
        
        $otherIncome = $otherIncome->get()->sum(function($report) {
            return $report->revenues->filter(function($rev) {
                $type = strtolower($rev->revenueIncomeType->name ?? '');
                return !str_contains($type, 'food') && 
                       !str_contains($type, 'beverage') && 
                       !str_contains($type, 'grubhub') && 
                       !str_contains($type, 'ubereats') && 
                       !str_contains($type, 'doordash');
            })->sum('amount');
        });
        
        $items[] = [
            'name' => 'Other Income',
            'amount' => $otherIncome,
            'coa_id' => null,
        ];
        
        $total = array_sum(array_column($items, 'amount'));
        
        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Calculate COGS from expense transactions
     */
    protected function calculateCOGS($storeId, $startDate, $endDate)
    {
        $query = ExpenseTransaction::select(
                'chart_of_accounts.id as coa_id',
                'chart_of_accounts.account_name',
                DB::raw('SUM(expense_transactions.amount) as total')
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'COGS')
            ->whereBetween('expense_transactions.transaction_date', [$startDate, $endDate]);
        
        if ($storeId) {
            if (is_array($storeId)) {
                $query->whereIn('expense_transactions.store_id', $storeId);
            } else {
                $query->where('expense_transactions.store_id', $storeId);
            }
        }
        
        $cogsItems = $query->groupBy('chart_of_accounts.id', 'chart_of_accounts.account_name')
            ->orderBy('chart_of_accounts.account_code')
            ->get();
        
        $items = $cogsItems->map(function($item) {
            return [
                'name' => $item->account_name,
                'amount' => $item->total,
                'coa_id' => $item->coa_id,
            ];
        })->toArray();
        
        $total = array_sum(array_column($items, 'amount'));
        
        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Calculate operating expenses from expense transactions
     */
    protected function calculateOperatingExpenses($storeId, $startDate, $endDate)
    {
        $query = ExpenseTransaction::select(
                'chart_of_accounts.id as coa_id',
                'chart_of_accounts.account_name',
                'chart_of_accounts.parent_account_id',
                'chart_of_accounts.account_code',
                DB::raw('SUM(expense_transactions.amount) as total')
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->whereBetween('expense_transactions.transaction_date', [$startDate, $endDate]);
        
        if ($storeId) {
            if (is_array($storeId)) {
                $query->whereIn('expense_transactions.store_id', $storeId);
            } else {
                $query->where('expense_transactions.store_id', $storeId);
            }
        }
        
        $expenseItems = $query->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_name',
                'chart_of_accounts.parent_account_id',
                'chart_of_accounts.account_code'
            )
            ->orderBy('chart_of_accounts.account_code')
            ->get();
        
        // Organize by parent categories
        $organized = [];
        $topLevel = [];
        
        foreach ($expenseItems as $item) {
            if ($item->parent_account_id) {
                $parent = ChartOfAccount::find($item->parent_account_id);
                if ($parent) {
                    if (!isset($organized[$parent->account_name])) {
                        $organized[$parent->account_name] = [
                            'name' => $parent->account_name,
                            'items' => [],
                            'total' => 0,
                        ];
                    }
                    $organized[$parent->account_name]['items'][] = [
                        'name' => $item->account_name,
                        'amount' => $item->total,
                        'coa_id' => $item->coa_id,
                    ];
                    $organized[$parent->account_name]['total'] += $item->total;
                }
            } else {
                $topLevel[] = [
                    'name' => $item->account_name,
                    'amount' => $item->total,
                    'coa_id' => $item->coa_id,
                ];
            }
        }
        
        // Combine top-level and organized items
        $items = array_merge($topLevel, array_values($organized));
        
        $total = array_sum(array_column($items, 'amount'));
        
        // Add subtotal for organized categories
        foreach ($organized as $parentName => $data) {
            $total += $data['total'];
        }
        
        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Add variance calculations to P&L
     */
    protected function addVariance($current, $comparison)
    {
        // Calculate variance for revenue
        foreach ($current['revenue']['items'] as $index => &$item) {
            $comparisonItem = $comparison['revenue']['items'][$index] ?? null;
            $item['comparison_amount'] = $comparisonItem['amount'] ?? 0;
            $item['variance'] = $item['amount'] - ($comparisonItem['amount'] ?? 0);
            $item['variance_percent'] = ($comparisonItem['amount'] ?? 0) > 0 
                ? (($item['variance'] / $comparisonItem['amount']) * 100) 
                : 0;
        }
        
        $current['revenue']['comparison_total'] = $comparison['revenue']['total'];
        $current['revenue']['variance'] = $current['revenue']['total'] - $comparison['revenue']['total'];
        $current['revenue']['variance_percent'] = $comparison['revenue']['total'] > 0 
            ? (($current['revenue']['variance'] / $comparison['revenue']['total']) * 100) 
            : 0;
        
        // Calculate variance for COGS
        foreach ($current['cogs']['items'] as $index => &$item) {
            $comparisonItem = $comparison['cogs']['items'][$index] ?? null;
            $item['comparison_amount'] = $comparisonItem['amount'] ?? 0;
            $item['variance'] = $item['amount'] - ($comparisonItem['amount'] ?? 0);
            $item['variance_percent'] = ($comparisonItem['amount'] ?? 0) > 0 
                ? (($item['variance'] / $comparisonItem['amount']) * 100) 
                : 0;
        }
        
        $current['cogs']['comparison_total'] = $comparison['cogs']['total'];
        $current['cogs']['variance'] = $current['cogs']['total'] - $comparison['cogs']['total'];
        $current['cogs']['variance_percent'] = $comparison['cogs']['total'] > 0 
            ? (($current['cogs']['variance'] / $comparison['cogs']['total']) * 100) 
            : 0;
        
        // Calculate variance for gross profit
        $current['comparison_gross_profit'] = $comparison['gross_profit'];
        $current['gross_profit_variance'] = $current['gross_profit'] - $comparison['gross_profit'];
        $current['gross_profit_variance_percent'] = $comparison['gross_profit'] > 0 
            ? (($current['gross_profit_variance'] / $comparison['gross_profit']) * 100) 
            : 0;
        $current['comparison_gross_margin'] = $comparison['gross_margin'];
        
        // Calculate variance for operating expenses
        // Note: This is simplified - would need to match items by name/coa_id for accuracy
        $current['operating_expenses']['comparison_total'] = $comparison['operating_expenses']['total'];
        $current['operating_expenses']['variance'] = $current['operating_expenses']['total'] - $comparison['operating_expenses']['total'];
        $current['operating_expenses']['variance_percent'] = $comparison['operating_expenses']['total'] > 0 
            ? (($current['operating_expenses']['variance'] / $comparison['operating_expenses']['total']) * 100) 
            : 0;
        
        // Calculate variance for net profit
        $current['comparison_net_profit'] = $comparison['net_profit'];
        $current['net_profit_variance'] = $current['net_profit'] - $comparison['net_profit'];
        $current['net_profit_variance_percent'] = $comparison['net_profit'] > 0 
            ? (($current['net_profit_variance'] / $comparison['net_profit']) * 100) 
            : 0;
        $current['comparison_net_margin'] = $comparison['net_margin'];
        
        return $current;
    }

    /**
     * Get comparison dates based on period type
     */
    protected function getComparisonDates($startDate, $endDate, $periodType)
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end);
        
        switch ($periodType) {
            case 'previous_period':
                return [
                    'start' => $start->copy()->subDays($daysDiff + 1)->format('Y-m-d'),
                    'end' => $start->copy()->subDay()->format('Y-m-d'),
                ];
                
            case 'previous_year':
                return [
                    'start' => $start->copy()->subYear()->format('Y-m-d'),
                    'end' => $end->copy()->subYear()->format('Y-m-d'),
                ];
                
            default:
                return [
                    'start' => $startDate,
                    'end' => $endDate,
                ];
        }
    }
}
