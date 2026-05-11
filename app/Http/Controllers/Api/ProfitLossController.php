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
use Illuminate\Support\Str;

class ProfitLossController extends Controller
{
    /**
     * Get complete P&L report
     */
    public function index(Request $request)
    {
        $storeId = $this->resolveAuthorizedStoreFilter($request->input('store_id'));
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
        $storeId = $this->resolveAuthorizedStoreFilter($request->input('store_id'));
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
            $this->resolveAuthorizedStoreFilter($request->input('store_id')),
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
        $user = auth()->user();
        $accessibleStoreIds = $user?->getAccessibleStoreIds() ?? [];
        $storeId = $request->input('store_id');

        if (! $user?->isAdmin()) {
            $query->where(function ($snapshotQuery) use ($accessibleStoreIds, $user) {
                $snapshotQuery->whereIn('store_id', $accessibleStoreIds)
                    ->orWhere(function ($nullStoreQuery) use ($user) {
                        $nullStoreQuery->whereNull('store_id')
                            ->where('created_by', $user->id);
                    });
            });
        }

        if ($storeId) {
            $resolvedStoreId = $this->resolveAuthorizedStoreFilter($storeId);
            $query->where('store_id', $resolvedStoreId);
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
        
        $storeId = $this->resolveAuthorizedStoreFilter($request->input('store_id'));

        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator'])
            ->where('coa_id', $request->coa_id)
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date]);

        $this->applyStoreFilter($query, 'store_id', $storeId);
        
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
                'store_id' => $storeId,
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
        
        $storeIds = $this->resolveAuthorizedStoreFilter($request->input('store_ids'));
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
     * Get annual P&L broken down by month (12 columns)
     */
    public function annual(Request $request)
    {
        $year    = (int) $request->input('year', now()->year);
        $storeId = $this->resolveAuthorizedStoreFilter($request->input('store_id'));

        $data = $this->calculateAnnualPL($storeId, $year);

        return response()->json([
            'year' => $year,
            'pl'   => $data,
        ]);
    }

    /**
     * Calculate full-year P&L with monthly breakdown using GROUP BY MONTH queries
     */
    protected function calculateAnnualPL($storeId, $year)
    {
        $months = range(1, 12);
        $coaTypeSummary = $this->calculateAnnualCoaTypeSummary($storeId, $year);
        $coaActivitySummary = [
            'income'  => $this->calculateAnnualIncomeCoaActivitySummary($storeId, $year),
            'expense' => $this->calculateAnnualExpenseCoaActivitySummary($storeId, $year),
        ];

        // ── REVENUE ──────────────────────────────────────────────────────────

        // In-Store Sales: SUM of daily_report_revenues line items, grouped by month.
        // Sourced from line items (not daily_reports.gross_sales) so the headline
        // TOTAL REVENUE reconciles with the COA Activity Summary's Income by COA total.
        $drQuery = DB::table('daily_report_revenues')
            ->join('daily_reports', 'daily_report_revenues.daily_report_id', '=', 'daily_reports.id')
            ->whereYear('daily_reports.report_date', $year)
            ->selectRaw('MONTH(daily_reports.report_date) as month, SUM(daily_report_revenues.amount) as total')
            ->groupBy(DB::raw('MONTH(daily_reports.report_date)'));
        $this->applyStoreFilter($drQuery, 'daily_reports.store_id', $storeId);
        $drRaw = $drQuery->pluck('total', 'month');

        $inStoreMonthly    = [];
        $inStoreAnnual     = 0;
        foreach ($months as $m) {
            $val               = (float) ($drRaw[$m] ?? 0);
            $inStoreMonthly[$m] = $val;
            $inStoreAnnual     += $val;
        }

        // Third-Party Sales: third_party_statements.gross_sales grouped by month
        $tpQuery = ThirdPartyStatement::selectRaw('MONTH(statement_date) as month, SUM(gross_sales) as total')
            ->whereYear('statement_date', $year)
            ->groupBy(DB::raw('MONTH(statement_date)'));
        $this->applyStoreFilter($tpQuery, 'store_id', $storeId);
        $tpRaw = $tpQuery->pluck('total', 'month');

        $tpMonthly  = [];
        $tpAnnual   = 0;
        foreach ($months as $m) {
            $val          = (float) ($tpRaw[$m] ?? 0);
            $tpMonthly[$m] = $val;
            $tpAnnual     += $val;
        }

        // Revenue monthly totals
        $revMonthlyTotals = [];
        foreach ($months as $m) {
            $revMonthlyTotals[$m] = $inStoreMonthly[$m] + $tpMonthly[$m];
        }
        $revAnnual = $inStoreAnnual + $tpAnnual;

        $revenue = [
            'items' => [
                [
                    'name'         => 'In-Store Sales',
                    'coa_id'       => null,
                    'monthly'      => $inStoreMonthly,
                    'annual_total' => $inStoreAnnual,
                ],
                [
                    'name'         => 'Third-Party Sales',
                    'coa_id'       => null,
                    'monthly'      => $tpMonthly,
                    'annual_total' => $tpAnnual,
                ],
            ],
            'monthly_totals' => $revMonthlyTotals,
            'annual_total'   => $revAnnual,
        ];

        // ── COGS ─────────────────────────────────────────────────────────────

        $cogsQuery = ExpenseTransaction::selectRaw(
                'chart_of_accounts.id as coa_id,
                 chart_of_accounts.account_name,
                 chart_of_accounts.account_code,
                 MONTH(expense_transactions.transaction_date) as month,
                 SUM(expense_transactions.amount) as total'
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'COGS')
            ->whereYear('expense_transactions.transaction_date', $year)
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_code',
                DB::raw('MONTH(expense_transactions.transaction_date)')
            )
            ->orderBy('chart_of_accounts.account_code');
        $this->applyStoreFilter($cogsQuery, 'expense_transactions.store_id', $storeId);
        $cogsRaw = $cogsQuery->get();

        // Pivot into per-COA monthly arrays
        $cogsAccounts = [];
        foreach ($cogsRaw as $row) {
            $key = $row->coa_id;
            if (!isset($cogsAccounts[$key])) {
                $cogsAccounts[$key] = [
                    'name'         => $row->account_name,
                    'coa_id'       => $row->coa_id,
                    'monthly'      => array_fill_keys($months, 0),
                    'annual_total' => 0,
                ];
            }
            $cogsAccounts[$key]['monthly'][$row->month]  += (float) $row->total;
            $cogsAccounts[$key]['annual_total']           += (float) $row->total;
        }

        $cogsMonthlyTotals = array_fill_keys($months, 0);
        $cogsAnnual        = 0;
        foreach ($cogsAccounts as $acct) {
            foreach ($months as $m) {
                $cogsMonthlyTotals[$m] += $acct['monthly'][$m];
            }
            $cogsAnnual += $acct['annual_total'];
        }

        $cogs = [
            'items'          => array_values($cogsAccounts),
            'monthly_totals' => $cogsMonthlyTotals,
            'annual_total'   => $cogsAnnual,
        ];

        // ── GROSS PROFIT ─────────────────────────────────────────────────────

        $gpMonthly = [];
        $gpMargins = [];
        foreach ($months as $m) {
            $gp                = $revMonthlyTotals[$m] - $cogsMonthlyTotals[$m];
            $gpMonthly[$m]     = $gp;
            $gpMargins[$m]     = $revMonthlyTotals[$m] > 0 ? round(($gp / $revMonthlyTotals[$m]) * 100, 2) : 0;
        }
        $gpAnnual    = $revAnnual - $cogsAnnual;
        $gpAvgMargin = $revAnnual > 0 ? round(($gpAnnual / $revAnnual) * 100, 2) : 0;

        $grossProfit = [
            'monthly'         => $gpMonthly,
            'annual_total'    => $gpAnnual,
            'monthly_margins' => $gpMargins,
            'avg_margin'      => $gpAvgMargin,
        ];

        // ── OPERATING EXPENSES ───────────────────────────────────────────────

        $expQuery = ExpenseTransaction::selectRaw(
                'chart_of_accounts.id as coa_id,
                 chart_of_accounts.account_name,
                 chart_of_accounts.account_code,
                 chart_of_accounts.parent_account_id,
                 MONTH(expense_transactions.transaction_date) as month,
                 SUM(expense_transactions.amount) as total'
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->whereYear('expense_transactions.transaction_date', $year)
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_code',
                'chart_of_accounts.parent_account_id',
                DB::raw('MONTH(expense_transactions.transaction_date)')
            )
            ->orderBy('chart_of_accounts.account_code');
        $this->applyStoreFilter($expQuery, 'expense_transactions.store_id', $storeId);
        $expRaw = $expQuery->get();

        // Pivot into per-COA monthly arrays
        $expAccounts = [];
        foreach ($expRaw as $row) {
            $key = $row->coa_id;
            if (!isset($expAccounts[$key])) {
                $expAccounts[$key] = [
                    'name'              => $row->account_name,
                    'coa_id'            => $row->coa_id,
                    'parent_account_id' => $row->parent_account_id,
                    'monthly'           => array_fill_keys($months, 0),
                    'annual_total'      => 0,
                ];
            }
            $expAccounts[$key]['monthly'][$row->month]  += (float) $row->total;
            $expAccounts[$key]['annual_total']           += (float) $row->total;
        }

        // Group by parent account
        $expOrganized = [];
        $expTopLevel  = [];
        foreach ($expAccounts as $acct) {
            if ($acct['parent_account_id']) {
                $parent = ChartOfAccount::find($acct['parent_account_id']);
                if ($parent) {
                    $pk = $parent->account_name;
                    if (!isset($expOrganized[$pk])) {
                        $expOrganized[$pk] = [
                            'name'         => $parent->account_name,
                            'coa_id'       => null,
                            'monthly'      => array_fill_keys($months, 0),
                            'annual_total' => 0,
                            'items'        => [],
                        ];
                    }
                    $expOrganized[$pk]['items'][] = $acct;
                    foreach ($months as $m) {
                        $expOrganized[$pk]['monthly'][$m] += $acct['monthly'][$m];
                    }
                    $expOrganized[$pk]['annual_total'] += $acct['annual_total'];
                    continue;
                }
            }
            $expTopLevel[] = $acct;
        }

        $expItems          = array_merge($expTopLevel, array_values($expOrganized));
        $expMonthlyTotals  = array_fill_keys($months, 0);
        $expAnnual         = 0;
        foreach ($expAccounts as $acct) {
            foreach ($months as $m) {
                $expMonthlyTotals[$m] += $acct['monthly'][$m];
            }
            $expAnnual += $acct['annual_total'];
        }

        $operatingExpenses = [
            'items'          => $expItems,
            'monthly_totals' => $expMonthlyTotals,
            'annual_total'   => $expAnnual,
        ];

        // ── NET PROFIT ───────────────────────────────────────────────────────

        $npMonthly = [];
        $npMargins = [];
        foreach ($months as $m) {
            $np                = $gpMonthly[$m] - $expMonthlyTotals[$m];
            $npMonthly[$m]     = $np;
            $npMargins[$m]     = $revMonthlyTotals[$m] > 0 ? round(($np / $revMonthlyTotals[$m]) * 100, 2) : 0;
        }
        $npAnnual    = $gpAnnual - $expAnnual;
        $npAvgMargin = $revAnnual > 0 ? round(($npAnnual / $revAnnual) * 100, 2) : 0;

        $netProfit = [
            'monthly'         => $npMonthly,
            'annual_total'    => $npAnnual,
            'monthly_margins' => $npMargins,
            'avg_margin'      => $npAvgMargin,
        ];

        return compact('revenue', 'cogs', 'grossProfit', 'operatingExpenses', 'netProfit', 'coaTypeSummary', 'coaActivitySummary');
    }

    protected function calculateAnnualExpenseCoaActivitySummary($storeId, int $year): array
    {
        $months = range(1, 12);
        $expenseDirectoryRows = $this->buildCoaDirectoryRows(['Expense']);

        $expenseQuery = ExpenseTransaction::query()
            ->selectRaw(
                'chart_of_accounts.id as coa_id,
                 MONTH(expense_transactions.transaction_date) as month,
                 COUNT(expense_transactions.id) as entry_count,
                 SUM(expense_transactions.amount) as total_amount'
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->whereYear('expense_transactions.transaction_date', $year)
            ->groupBy(
                'chart_of_accounts.id',
                DB::raw('MONTH(expense_transactions.transaction_date)')
            )
            ->orderBy('chart_of_accounts.id');

        $this->applyStoreFilter($expenseQuery, 'expense_transactions.store_id', $storeId);

        $rawExpenseActivity = $expenseQuery->get();

        $activityRows = $rawExpenseActivity
            ->groupBy('coa_id')
            ->map(function ($coaRows) use ($months) {
                $monthlyAmounts = array_fill_keys($months, 0.0);
                $entryCount = 0;
                $totalAmount = 0.0;

                foreach ($coaRows as $coaRow) {
                    $month = (int) ($coaRow->month ?? 0);
                    if ($month >= 1 && $month <= 12) {
                        $monthlyAmounts[$month] += (float) ($coaRow->total_amount ?? 0);
                    }

                    $entryCount += (int) ($coaRow->entry_count ?? 0);
                    $totalAmount += (float) ($coaRow->total_amount ?? 0);
                }

                return [
                    'coa_id' => (int) $coaRows->first()->coa_id,
                    'entry_count' => $entryCount,
                    'total_amount' => $totalAmount,
                    'monthly_amounts' => $monthlyAmounts,
                ];
            })
            ->values()
            ->all();

        $rows = $this->mergeAnnualCoaDirectoryWithActivity(
            $expenseDirectoryRows,
            $activityRows
        );

        // Sum totals from leaf rows only — rollup rows (is_rollup=true) are parent
        // aggregates whose children are already counted as individual leaf rows.
        // Using raw query rows instead caused footer totals to diverge from the table.
        $monthlyTotals = array_fill_keys($months, 0.0);
        $totalAmount   = 0.0;
        $entryCount    = 0;
        foreach ($rows as $row) {
            if (! ($row['is_rollup'] ?? false)) {
                foreach ($months as $month) {
                    $monthlyTotals[$month] += (float) ($row['monthly_amounts'][$month] ?? 0);
                }
                $totalAmount += (float) ($row['total_amount'] ?? 0);
                $entryCount  += (int)   ($row['entry_count']  ?? 0);
            }
        }

        return [
            'rows'           => $rows,
            'entry_count'    => $entryCount,
            'total_amount'   => $totalAmount,
            'monthly_totals' => $monthlyTotals,
        ];
    }

    protected function calculateAnnualIncomeCoaActivitySummary($storeId, int $year): array
    {
        $months = range(1, 12);
        $revenueCoaLookup = $this->getRevenueCoaLookup();
        $revenueDirectoryRows = $this->buildCoaDirectoryRows(['Revenue']);

        $dailyRevenueQuery = DB::table('daily_report_revenues')
            ->join('daily_reports', 'daily_report_revenues.daily_report_id', '=', 'daily_reports.id')
            ->join('revenue_income_types', 'daily_report_revenues.revenue_income_type_id', '=', 'revenue_income_types.id')
            ->whereYear('daily_reports.report_date', $year)
            ->selectRaw(
                'revenue_income_types.id as revenue_income_type_id,
                 revenue_income_types.name as revenue_income_type_name,
                 revenue_income_types.category,
                 revenue_income_types.default_coa_id,
                 MONTH(daily_reports.report_date) as month,
                 COUNT(daily_report_revenues.id) as entry_count,
                 SUM(daily_report_revenues.amount) as total_amount'
            )
            ->groupBy(
                'revenue_income_types.id',
                'revenue_income_types.name',
                'revenue_income_types.category',
                'revenue_income_types.default_coa_id',
                DB::raw('MONTH(daily_reports.report_date)')
            );
        $this->applyStoreFilter($dailyRevenueQuery, 'daily_reports.store_id', $storeId);

        $activityRows = collect()
            ->merge($this->mapRevenueActivityRowsToCoas($dailyRevenueQuery->get(), $revenueCoaLookup))
            ->merge($this->calculateAnnualThirdPartyIncomeActivityRows($storeId, $year, $revenueCoaLookup))
            ->groupBy(fn ($row) => $row['coa_id'] ?? $row['account_code'])
            ->map(function ($rows) use ($months) {
                $first = $rows->first();
                $monthlyAmounts = array_fill_keys($months, 0.0);
                $entryCount = 0;
                $totalAmount = 0.0;

                foreach ($rows as $row) {
                    $month = (int) ($row['month'] ?? 0);
                    if ($month >= 1 && $month <= 12) {
                        $monthlyAmounts[$month] += (float) ($row['total_amount'] ?? 0);
                    }

                    $entryCount += (int) ($row['entry_count'] ?? 0);
                    $totalAmount += (float) ($row['total_amount'] ?? 0);
                }

                return [
                    'coa_id' => $first['coa_id'],
                    'account_code' => $first['account_code'],
                    'account_name' => $first['account_name'],
                    'parent_account_code' => $first['parent_account_code'] ?? '',
                    'parent_account_name' => $first['parent_account_name'] ?? '',
                    'account_type' => $first['account_type'],
                    'entry_count' => $entryCount,
                    'total_amount' => $totalAmount,
                    'monthly_amounts' => $monthlyAmounts,
                    'is_unmapped' => false,
                ];
            })
            ->values()
            ->all();

        $rows = $this->mergeAnnualCoaDirectoryWithActivity(
            $revenueDirectoryRows,
            $activityRows
        );

        // Sum totals from leaf rows only — rollup rows (is_rollup=true) are parent
        // aggregates whose children are already counted as individual leaf rows.
        // Using raw activityRows instead caused footer totals to diverge from the table.
        $monthlyTotals = array_fill_keys($months, 0.0);
        $totalAmount   = 0.0;
        $entryCount    = 0;
        foreach ($rows as $row) {
            if (! ($row['is_rollup'] ?? false)) {
                foreach ($months as $month) {
                    $monthlyTotals[$month] += (float) ($row['monthly_amounts'][$month] ?? 0);
                }
                $totalAmount += (float) ($row['total_amount'] ?? 0);
                $entryCount  += (int)   ($row['entry_count']  ?? 0);
            }
        }

        return [
            'rows'           => $rows,
            'entry_count'    => $entryCount,
            'total_amount'   => $totalAmount,
            'monthly_totals' => $monthlyTotals,
        ];
    }

    protected function mergeAnnualCoaDirectoryWithActivity(array $directoryRows, array $activityRows): array
    {
        $months = range(1, 12);
        $activityByCoaId = collect($activityRows)->keyBy('coa_id');
        $directoryById = collect($directoryRows)->keyBy('coa_id');
        $childrenByParentId = collect($directoryRows)
            ->filter(fn ($row) => ! empty($row['parent_account_id']))
            ->groupBy('parent_account_id');
        $computed = [];

        $computeTotals = function (int $coaId) use (&$computeTotals, &$computed, $activityByCoaId, $directoryById, $childrenByParentId, $months) {
            if (isset($computed[$coaId])) {
                return $computed[$coaId];
            }

            $directoryRow = $directoryById->get($coaId);
            if (! $directoryRow) {
                return [
                    'entry_count' => 0,
                    'total_amount' => 0.0,
                    'monthly_amounts' => array_fill_keys($months, 0.0),
                ];
            }

            $children = $childrenByParentId->get($coaId, collect());
            if ($children->isEmpty()) {
                $activityRow = $activityByCoaId->get($coaId);

                return $computed[$coaId] = [
                    'entry_count' => (int) ($activityRow['entry_count'] ?? 0),
                    'total_amount' => (float) ($activityRow['total_amount'] ?? 0),
                    'monthly_amounts' => $activityRow['monthly_amounts'] ?? array_fill_keys($months, 0.0),
                ];
            }

            $entryCount = 0;
            $totalAmount = 0.0;
            $monthlyAmounts = array_fill_keys($months, 0.0);

            foreach ($children as $childRow) {
                $childTotals = $computeTotals((int) $childRow['coa_id']);
                $entryCount += (int) ($childTotals['entry_count'] ?? 0);
                $totalAmount += (float) ($childTotals['total_amount'] ?? 0);

                foreach ($months as $month) {
                    $monthlyAmounts[$month] += (float) ($childTotals['monthly_amounts'][$month] ?? 0);
                }
            }

            return $computed[$coaId] = [
                'entry_count' => $entryCount,
                'total_amount' => $totalAmount,
                'monthly_amounts' => $monthlyAmounts,
            ];
        };

        return collect($directoryRows)
            ->map(function ($directoryRow) use ($computeTotals) {
                $totals = $computeTotals((int) $directoryRow['coa_id']);
                $directoryRow['entry_count'] = (int) ($totals['entry_count'] ?? 0);
                $directoryRow['total_amount'] = (float) ($totals['total_amount'] ?? 0);
                $directoryRow['monthly_amounts'] = $totals['monthly_amounts'] ?? [];

                return $directoryRow;
            })
            ->sortBy(fn ($row) => sprintf('%s|%s', $row['account_type'], str_pad($row['account_code'], 10, '0', STR_PAD_LEFT)))
            ->values()
            ->all();
    }

    protected function calculateAnnualCoaTypeSummary($storeId, int $year): array
    {
        $orderedTypes = [
            'Assets',
            'Liability',
            'Taxes',
            'Revenue',
            'COGS',
            'Expense',
            'Adjustments',
            'Equity',
        ];

        $allTypes = ChartOfAccount::query()
            ->select('account_type')
            ->distinct()
            ->pluck('account_type')
            ->filter()
            ->map(fn ($type) => (string) $type)
            ->values()
            ->all();

        $types = array_values(array_unique(array_merge($orderedTypes, $allTypes)));

        $coaCounts = ChartOfAccount::query()
            ->select('account_type', DB::raw('COUNT(*) as account_count'))
            ->groupBy('account_type')
            ->pluck('account_count', 'account_type');

        $expenseCountsQuery = ExpenseTransaction::query()
            ->select('chart_of_accounts.account_type', DB::raw('COUNT(expense_transactions.id) as expense_count'))
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->whereYear('expense_transactions.transaction_date', $year)
            ->groupBy('chart_of_accounts.account_type');

        $this->applyStoreFilter($expenseCountsQuery, 'expense_transactions.store_id', $storeId);

        $expenseCounts = $expenseCountsQuery->pluck('expense_count', 'chart_of_accounts.account_type');

        return array_map(function (string $type) use ($coaCounts, $expenseCounts) {
            return [
                'account_type' => $type,
                'account_count' => (int) ($coaCounts[$type] ?? 0),
                'expense_count' => (int) ($expenseCounts[$type] ?? 0),
            ];
        }, $types);
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
        
        $storeIds = $this->resolveAuthorizedStoreFilter($request->input('store_ids'));
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
                'metric_value' => $metric === 'revenue'
                    ? $pl['revenue']['total']
                    : ($metric === 'profit' ? $pl['net_profit'] : $pl['net_margin']),
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
        $coaActivitySummary = $this->calculateCoaActivitySummary($storeId, $startDate, $endDate);
        
        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => round($grossMargin, 2),
            'operating_expenses' => $operatingExpenses,
            'net_profit' => $netProfit,
            'net_margin' => round($netMargin, 2),
            'coa_activity_summary' => $coaActivitySummary,
        ];
    }

    protected function calculateAnnualThirdPartyIncomeActivityRows($storeId, int $year, array $revenueCoaLookup)
    {
        $query = ThirdPartyStatement::query()
            ->selectRaw(
                'platform,
                 MONTH(statement_date) as month,
                 COUNT(id) as entry_count,
                 SUM(gross_sales) as total_amount'
            )
            ->whereYear('statement_date', $year)
            ->groupBy('platform', DB::raw('MONTH(statement_date)'));
        $this->applyStoreFilter($query, 'store_id', $storeId);

        return $query->get()->map(function ($row) use ($revenueCoaLookup) {
            $coa = $this->resolveRevenueCoaReference(
                $revenueCoaLookup,
                'Third-Party ' . Str::title((string) $row->platform),
                'online',
                null
            );

            return [
                'coa_id' => $coa['id'] ?? null,
                'account_code' => $coa['account_code'] ?? '',
                'account_name' => $coa['account_name'] ?? 'Revenue',
                'parent_account_code' => $coa['parent_account_code'] ?? '',
                'parent_account_name' => $coa['parent_account_name'] ?? '',
                'account_type' => $coa['account_type'] ?? 'Revenue',
                'month' => (int) ($row->month ?? 0),
                'entry_count' => (int) $row->entry_count,
                'total_amount' => (float) $row->total_amount,
                'is_unmapped' => false,
            ];
        });
    }

    protected function calculateCoaActivitySummary($storeId, $startDate, $endDate): array
    {
        $yearMonths = [];
        $cursor = \Carbon\Carbon::parse($startDate)->startOfMonth();
        $endMonth = \Carbon\Carbon::parse($endDate)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $yearMonths[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $revenueCoaLookup = $this->getRevenueCoaLookup();
        $revenueDirectoryRows = $this->buildCoaDirectoryRows(['Revenue']);
        $expenseDirectoryRows = $this->buildCoaDirectoryRows(['Expense']);

        $dailyRevenueQuery = DB::table('daily_report_revenues')
            ->join('daily_reports', 'daily_report_revenues.daily_report_id', '=', 'daily_reports.id')
            ->join('revenue_income_types', 'daily_report_revenues.revenue_income_type_id', '=', 'revenue_income_types.id')
            ->whereBetween('daily_reports.report_date', [$startDate, $endDate])
            ->selectRaw(
                'revenue_income_types.id as revenue_income_type_id,
                 revenue_income_types.name as revenue_income_type_name,
                 revenue_income_types.category,
                 revenue_income_types.default_coa_id,
                 YEAR(daily_reports.report_date) as year,
                 MONTH(daily_reports.report_date) as month,
                 COUNT(daily_report_revenues.id) as entry_count,
                 SUM(daily_report_revenues.amount) as total_amount'
            )
            ->groupBy(
                'revenue_income_types.id',
                'revenue_income_types.name',
                'revenue_income_types.category',
                'revenue_income_types.default_coa_id',
                DB::raw('YEAR(daily_reports.report_date)'),
                DB::raw('MONTH(daily_reports.report_date)')
            );
        $this->applyStoreFilter($dailyRevenueQuery, 'daily_reports.store_id', $storeId);

        $rawIncomeRows = collect()
            ->merge($this->mapRevenueActivityRowsToCoas($dailyRevenueQuery->get(), $revenueCoaLookup))
            ->merge($this->calculateThirdPartyIncomeActivityRows($storeId, $startDate, $endDate, $revenueCoaLookup));

        $incomeActivityRows = $rawIncomeRows
            ->groupBy(fn ($row) => $row['coa_id'] ?? $row['account_code'])
            ->map(function ($rows) use ($yearMonths) {
                $first = $rows->first();
                $monthlyAmounts = array_fill_keys($yearMonths, 0.0);
                foreach ($rows as $row) {
                    if (!empty($row['year']) && !empty($row['month'])) {
                        $ym = sprintf('%04d-%02d', $row['year'], $row['month']);
                        if (isset($monthlyAmounts[$ym])) {
                            $monthlyAmounts[$ym] += (float) ($row['total_amount'] ?? 0);
                        }
                    }
                }
                return [
                    'coa_id' => $first['coa_id'],
                    'account_code' => $first['account_code'],
                    'account_name' => $first['account_name'],
                    'parent_account_code' => $first['parent_account_code'] ?? '',
                    'parent_account_name' => $first['parent_account_name'] ?? '',
                    'account_type' => $first['account_type'],
                    'entry_count' => $rows->sum('entry_count'),
                    'total_amount' => $rows->sum('total_amount'),
                    'monthly_amounts' => $monthlyAmounts,
                    'is_unmapped' => false,
                ];
            });

        $incomeRows = $this->mergeCoaDirectoryWithActivity(
            $revenueDirectoryRows,
            $incomeActivityRows,
            $yearMonths
        );

        $expenseQuery = ExpenseTransaction::query()
            ->selectRaw(
                'chart_of_accounts.id as coa_id,
                 chart_of_accounts.account_code,
                 chart_of_accounts.account_name,
                 chart_of_accounts.account_type,
                 YEAR(expense_transactions.transaction_date) as year,
                 MONTH(expense_transactions.transaction_date) as month,
                 COUNT(expense_transactions.id) as entry_count,
                 SUM(expense_transactions.amount) as total_amount,
                 0 as is_unmapped'
            )
            ->join('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'Expense')
            ->whereBetween('expense_transactions.transaction_date', [$startDate, $endDate])
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                DB::raw('YEAR(expense_transactions.transaction_date)'),
                DB::raw('MONTH(expense_transactions.transaction_date)')
            )
            ->orderBy('chart_of_accounts.account_type')
            ->orderBy('chart_of_accounts.account_code');
        $this->applyStoreFilter($expenseQuery, 'expense_transactions.store_id', $storeId);

        $rawExpenseRows = $expenseQuery->get()->map(fn ($row) => [
            'coa_id'             => $row->coa_id ? (int) $row->coa_id : null,
            'account_code'       => (string) ($row->account_code ?? ''),
            'account_name'       => (string) ($row->account_name ?? ''),
            'parent_account_code'=> '',
            'parent_account_name'=> '',
            'account_type'       => (string) ($row->account_type ?? ''),
            'year'               => isset($row->year)  ? (int) $row->year  : null,
            'month'              => isset($row->month) ? (int) $row->month : null,
            'entry_count'        => (int) ($row->entry_count ?? 0),
            'total_amount'       => (float) ($row->total_amount ?? 0),
            'is_unmapped'        => false,
        ]);

        $expenseActivityRows = collect($rawExpenseRows)
            ->groupBy(fn ($row) => $row['coa_id'] ?? $row['account_code'])
            ->map(function ($rows) use ($yearMonths) {
                $first = $rows->first();
                $monthlyAmounts = array_fill_keys($yearMonths, 0.0);
                foreach ($rows as $row) {
                    if (!empty($row['year']) && !empty($row['month'])) {
                        $ym = sprintf('%04d-%02d', $row['year'], $row['month']);
                        if (isset($monthlyAmounts[$ym])) {
                            $monthlyAmounts[$ym] += (float) ($row['total_amount'] ?? 0);
                        }
                    }
                }
                return [
                    'coa_id'             => $first['coa_id'],
                    'account_code'       => $first['account_code'],
                    'account_name'       => $first['account_name'],
                    'parent_account_code'=> $first['parent_account_code'] ?? '',
                    'parent_account_name'=> $first['parent_account_name'] ?? '',
                    'account_type'       => $first['account_type'],
                    'entry_count'        => $rows->sum('entry_count'),
                    'total_amount'       => $rows->sum('total_amount'),
                    'monthly_amounts'    => $monthlyAmounts,
                    'is_unmapped'        => false,
                ];
            });

        $expenseRows = $this->mergeCoaDirectoryWithActivity(
            $expenseDirectoryRows,
            $expenseActivityRows,
            $yearMonths
        );

        $incomeTotals  = $this->buildSectionTotals($incomeRows, $yearMonths);
        $expenseTotals = $this->buildSectionTotals($expenseRows, $yearMonths);

        return [
            'year_months' => $yearMonths,
            'income' => [
                'rows'          => $incomeRows,
                'entry_count'   => $incomeTotals['entry_count'],
                'total_amount'  => $incomeTotals['total_amount'],
                'monthly_totals'=> $incomeTotals['monthly_totals'],
            ],
            'expense' => [
                'rows'          => $expenseRows,
                'entry_count'   => $expenseTotals['entry_count'],
                'total_amount'  => $expenseTotals['total_amount'],
                'monthly_totals'=> $expenseTotals['monthly_totals'],
            ],
        ];
    }

    protected function calculateThirdPartyIncomeActivityRows($storeId, $startDate, $endDate, array $revenueCoaLookup)
    {
        $query = ThirdPartyStatement::query()
            ->selectRaw(
                'platform,
                 YEAR(statement_date) as year,
                 MONTH(statement_date) as month,
                 COUNT(id) as entry_count,
                 SUM(gross_sales) as total_amount'
            )
            ->whereBetween('statement_date', [$startDate, $endDate])
            ->groupBy('platform', DB::raw('YEAR(statement_date)'), DB::raw('MONTH(statement_date)'));
        $this->applyStoreFilter($query, 'store_id', $storeId);

        return $query->get()->map(function ($row) use ($revenueCoaLookup) {
            $coa = $this->resolveRevenueCoaReference(
                $revenueCoaLookup,
                'Third-Party ' . Str::title((string) $row->platform),
                'online',
                null
            );

            return [
                'coa_id' => $coa['id'] ?? null,
                'account_code' => $coa['account_code'] ?? '',
                'account_name' => $coa['account_name'] ?? 'Revenue',
                'parent_account_code' => $coa['parent_account_code'] ?? '',
                'parent_account_name' => $coa['parent_account_name'] ?? '',
                'account_type' => $coa['account_type'] ?? 'Revenue',
                'year' => (int) ($row->year ?? 0),
                'month' => (int) ($row->month ?? 0),
                'entry_count' => (int) $row->entry_count,
                'total_amount' => (float) $row->total_amount,
                'is_unmapped' => false,
            ];
        });
    }

    protected function mapRevenueActivityRowsToCoas($rows, array $revenueCoaLookup)
    {
        return collect($rows)->map(function ($row) use ($revenueCoaLookup) {
            $coa = $this->resolveRevenueCoaReference(
                $revenueCoaLookup,
                $row->revenue_income_type_name ?? null,
                $row->category ?? null,
                $row->default_coa_id ?? null
            );

            return [
                'coa_id'              => $coa['id'] ?? null,
                'account_code'        => $coa['account_code'] ?? '',
                'account_name'        => $coa['account_name'] ?? 'Revenue',
                'parent_account_code' => $coa['parent_account_code'] ?? '',
                'parent_account_name' => $coa['parent_account_name'] ?? '',
                'account_type'        => $coa['account_type'] ?? 'Revenue',
                'year'                => isset($row->year)  ? (int) $row->year  : null,
                'month'               => isset($row->month) ? (int) $row->month : null,
                'entry_count'         => (int)   ($row->entry_count  ?? 0),
                'total_amount'        => (float)  ($row->total_amount ?? 0),
                'is_unmapped'         => false,
            ];
        });
    }

    protected function getRevenueCoaLookup(): array
    {
        $revenueCoas = ChartOfAccount::query()
            ->with('parent:id,account_code,account_name')
            ->where('account_type', 'Revenue')
            ->orderByRaw('CAST(account_code AS UNSIGNED) ASC')
            ->get(['id', 'account_code', 'account_name', 'account_type', 'parent_account_id']);

        $formatted = $revenueCoas->map(function ($coa) {
            return [
                'id' => (int) $coa->id,
                'account_code' => (string) $coa->account_code,
                'account_name' => (string) $coa->account_name,
                'account_type' => (string) $coa->account_type,
                'parent_account_code' => (string) ($coa->parent?->account_code ?? ''),
                'parent_account_name' => (string) ($coa->parent?->account_name ?? ''),
            ];
        });

        return [
            'by_id' => $formatted->keyBy('id')->all(),
            'by_code' => $formatted->keyBy('account_code')->all(),
            'fallback' => $formatted->first(),
        ];
    }

    protected function resolveRevenueCoaReference(array $lookup, ?string $name, ?string $category, $defaultCoaId = null): ?array
    {
        if ($defaultCoaId && isset($lookup['by_id'][(int) $defaultCoaId])) {
            return $lookup['by_id'][(int) $defaultCoaId];
        }

        $name = Str::lower(trim((string) $name));
        $category = Str::lower(trim((string) $category));

        $targetCode = match (true) {
            $category === 'cash' || str_contains($name, 'cash') => '4010',
            $category === 'card' || str_contains($name, 'card') || str_contains($name, 'credit') => '4020',
            $category === 'check' || str_contains($name, 'check') => '4030',
            str_contains($name, 'crypto') => '4050',
            str_contains($name, 'food') => '4100',
            str_contains($name, 'beverage') => '4200',
            $category === 'online'
                || str_contains($name, 'doordash')
                || str_contains($name, 'uber')
                || str_contains($name, 'grubhub')
                || str_contains($name, 'relish')
                || str_contains($name, 'ez catering')
                || str_contains($name, 'third-party')
                || str_contains($name, 'third party') => '4300',
            default => '4400',
        };

        return $lookup['by_code'][$targetCode]
            ?? $lookup['fallback']
            ?? null;
    }

    protected function formatCoaActivityRow($row): array
    {
        return [
            'coa_id' => $row->coa_id ? (int) $row->coa_id : null,
            'account_code' => (string) ($row->account_code ?? ''),
            'account_name' => (string) ($row->account_name ?? ''),
            'parent_account_code' => (string) ($row->parent_account_code ?? ''),
            'parent_account_name' => (string) ($row->parent_account_name ?? ''),
            'account_type' => (string) ($row->account_type ?? ''),
            'entry_count' => (int) ($row->entry_count ?? 0),
            'total_amount' => (float) ($row->total_amount ?? 0),
            'is_unmapped' => (bool) ($row->is_unmapped ?? false),
        ];
    }

    protected function buildCoaDirectoryRows(array $types): array
    {
        $coas = ChartOfAccount::query()
            ->with('parent:id,account_code,account_name')
            ->whereIn('account_type', $types)
            ->orderByRaw('CAST(account_code AS UNSIGNED) ASC')
            ->get(['id', 'account_code', 'account_name', 'account_type', 'parent_account_id']);

        $coasByCode = $coas->keyBy('account_code');

        $rows = $coas->map(function ($coa) use ($coasByCode) {
            $resolvedParent = $coa->parent;

            if (! $resolvedParent) {
                $inferredParentCode = $this->inferParentAccountCode((string) $coa->account_code, (string) $coa->account_type);
                $resolvedParent = $inferredParentCode ? $coasByCode->get($inferredParentCode) : null;
            }

            return [
                'coa_id' => (int) $coa->id,
                'account_code' => (string) $coa->account_code,
                'account_name' => (string) $coa->account_name,
                'parent_account_id' => $resolvedParent?->id ? (int) $resolvedParent->id : null,
                'parent_account_code' => (string) ($resolvedParent?->account_code ?? ''),
                'parent_account_name' => (string) ($resolvedParent?->account_name ?? ''),
                'account_type' => (string) $coa->account_type,
                'entry_count' => 0,
                'total_amount' => 0.0,
                'is_unmapped' => false,
                'is_rollup' => false,
            ];
        });

        $childrenByParentId = $rows
            ->filter(fn ($row) => ! empty($row['parent_account_id']))
            ->groupBy('parent_account_id');

        return $rows->map(function ($row) use ($childrenByParentId) {
            $row['is_rollup'] = $childrenByParentId->has($row['coa_id']);
            return $row;
        })->all();
    }

    protected function buildSectionTotals(array $rows, array $yearMonths): array
    {
        $monthlyTotals = array_fill_keys($yearMonths, 0.0);
        $totalAmount   = 0.0;
        $entryCount    = 0;
        foreach ($rows as $row) {
            if (! ($row['is_rollup'] ?? false)) {
                foreach ($yearMonths as $ym) {
                    $monthlyTotals[$ym] += (float) ($row['monthly_amounts'][$ym] ?? 0);
                }
                $totalAmount += (float) ($row['total_amount'] ?? 0);
                $entryCount  += (int)   ($row['entry_count']  ?? 0);
            }
        }
        return ['monthly_totals' => $monthlyTotals, 'total_amount' => $totalAmount, 'entry_count' => $entryCount];
    }

    protected function mergeCoaDirectoryWithActivity(array $directoryRows, $activityRows, array $yearMonths = []): array
    {
        $activityByCoaId = collect($activityRows)->keyBy('coa_id');
        $directoryById = collect($directoryRows)->keyBy('coa_id');
        $childrenByParentId = collect($directoryRows)
            ->filter(fn ($row) => ! empty($row['parent_account_id']))
            ->groupBy('parent_account_id');
        $computed = [];

        $computeTotals = function (int $coaId) use (&$computeTotals, &$computed, $directoryById, $activityByCoaId, $childrenByParentId, $yearMonths) {
            if (isset($computed[$coaId])) {
                return $computed[$coaId];
            }

            $directoryRow = $directoryById->get($coaId);
            if (! $directoryRow) {
                return ['entry_count' => 0, 'total_amount' => 0.0, 'monthly_amounts' => array_fill_keys($yearMonths, 0.0)];
            }

            $children = $childrenByParentId->get($coaId, collect());
            if ($children->isEmpty()) {
                $activityRow = $activityByCoaId->get($coaId);
                return $computed[$coaId] = [
                    'entry_count'    => (int) ($activityRow['entry_count'] ?? 0),
                    'total_amount'   => (float) ($activityRow['total_amount'] ?? 0),
                    'monthly_amounts'=> $activityRow['monthly_amounts'] ?? array_fill_keys($yearMonths, 0.0),
                ];
            }

            $entryCount     = 0;
            $totalAmount    = 0.0;
            $monthlyAmounts = array_fill_keys($yearMonths, 0.0);

            foreach ($children as $childRow) {
                $childTotals  = $computeTotals((int) $childRow['coa_id']);
                $entryCount  += (int) ($childTotals['entry_count'] ?? 0);
                $totalAmount += (float) ($childTotals['total_amount'] ?? 0);
                foreach ($yearMonths as $ym) {
                    $monthlyAmounts[$ym] += (float) ($childTotals['monthly_amounts'][$ym] ?? 0);
                }
            }

            return $computed[$coaId] = [
                'entry_count'    => $entryCount,
                'total_amount'   => $totalAmount,
                'monthly_amounts'=> $monthlyAmounts,
            ];
        };

        return collect($directoryRows)
            ->map(function ($directoryRow) use ($computeTotals) {
                $totals = $computeTotals((int) $directoryRow['coa_id']);
                $directoryRow['entry_count']     = (int) ($totals['entry_count'] ?? 0);
                $directoryRow['total_amount']    = (float) ($totals['total_amount'] ?? 0);
                $directoryRow['monthly_amounts'] = $totals['monthly_amounts'] ?? [];
                return $directoryRow;
            })
            ->sortBy(fn ($row) => sprintf('%s|%s', $row['account_type'], str_pad($row['account_code'], 10, '0', STR_PAD_LEFT)))
            ->values()
            ->all();
    }

    protected function inferParentAccountCode(string $accountCode, string $accountType): ?string
    {
        if (! ctype_digit($accountCode) || strlen($accountCode) !== 4) {
            return null;
        }

        if (substr($accountCode, -3) === '000') {
            return null;
        }

        if (substr($accountCode, -2) === '00') {
            $parentCode = substr($accountCode, 0, 1) . '000';
            return $parentCode !== $accountCode ? $parentCode : null;
        }

        $parentCode = substr($accountCode, 0, 2) . '00';
        return $parentCode !== $accountCode ? $parentCode : null;
    }

    protected function isRollupCoaCode(string $accountCode): bool
    {
        return ctype_digit($accountCode) && substr($accountCode, -3) === '000';
    }

    /**
     * Calculate revenue from daily reports and third-party statements
     */
    protected function calculateRevenue($storeId, $startDate, $endDate)
    {
        $items = [];
        
        // Food Sales and Beverage Sales from daily_reports
        $dailyReportsQuery = DailyReport::whereBetween('report_date', [$startDate, $endDate]);
        $this->applyStoreFilter($dailyReportsQuery, 'store_id', $storeId);
        
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
        
        $this->applyStoreFilter($foodRevenue, 'store_id', $storeId);
        
        $foodSales = $foodRevenue->join('daily_report_revenues', 'daily_reports.id', '=', 'daily_report_revenues.daily_report_id')
            ->join('revenue_income_types', 'daily_report_revenues.revenue_income_type_id', '=', 'revenue_income_types.id')
            ->where('revenue_income_types.name', 'like', '%Food%')
            ->sum('daily_report_revenues.amount');
        
        $beverageSales = DailyReport::query()
            ->whereBetween('report_date', [$startDate, $endDate])
            ->whereHas('revenues.revenueIncomeType', function($q) {
                $q->where('name', 'like', '%Beverage%');
            });
        
        $this->applyStoreFilter($beverageSales, 'store_id', $storeId);
        
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
        
        $this->applyStoreFilter($thirdPartyQuery, 'store_id', $storeId);
        
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
        
        $this->applyStoreFilter($otherIncome, 'store_id', $storeId);
        
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
        
        $this->applyStoreFilter($query, 'expense_transactions.store_id', $storeId);
        
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
        
        $this->applyStoreFilter($query, 'expense_transactions.store_id', $storeId);
        
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
        $current['revenue']['items'] = $this->attachComparisonMetrics(
            $current['revenue']['items'],
            $comparison['revenue']['items'] ?? []
        );
        
        $current['revenue']['comparison_total'] = $comparison['revenue']['total'];
        $current['revenue']['variance'] = $current['revenue']['total'] - $comparison['revenue']['total'];
        $current['revenue']['variance_percent'] = $comparison['revenue']['total'] > 0 
            ? (($current['revenue']['variance'] / $comparison['revenue']['total']) * 100) 
            : 0;
        
        // Calculate variance for COGS
        $current['cogs']['items'] = $this->attachComparisonMetrics(
            $current['cogs']['items'],
            $comparison['cogs']['items'] ?? []
        );
        
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
        $current['operating_expenses']['items'] = $this->attachOperatingExpenseComparisonMetrics(
            $current['operating_expenses']['items'],
            $comparison['operating_expenses']['items'] ?? []
        );
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

    protected function resolveAuthorizedStoreFilter($storeFilter)
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return $storeFilter;
        }

        $accessibleStoreIds = $user->getAccessibleStoreIds();

        if (is_array($storeFilter)) {
            $normalized = array_values(array_unique(array_map('intval', array_filter($storeFilter, fn ($id) => filled($id)))));
            if (empty($normalized)) {
                return $accessibleStoreIds;
            }

            foreach ($normalized as $storeId) {
                if (! in_array($storeId, $accessibleStoreIds, true)) {
                    abort(403, 'Access denied to one or more stores');
                }
            }

            return $normalized;
        }

        if (filled($storeFilter)) {
            $storeId = (int) $storeFilter;
            if (! in_array($storeId, $accessibleStoreIds, true)) {
                abort(403, 'Access denied to this store');
            }

            return $storeId;
        }

        return $accessibleStoreIds;
    }

    protected function applyStoreFilter($query, string $column, $storeFilter): void
    {
        if (is_array($storeFilter)) {
            if (empty($storeFilter)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereIn($column, $storeFilter);
            return;
        }

        if (filled($storeFilter)) {
            $query->where($column, $storeFilter);
        }
    }

    protected function attachComparisonMetrics(array $currentItems, array $comparisonItems, string $amountField = 'amount'): array
    {
        $comparisonLookup = [];
        foreach ($comparisonItems as $comparisonItem) {
            $comparisonLookup[$this->itemComparisonKey($comparisonItem)] = $comparisonItem;
        }

        foreach ($currentItems as &$item) {
            $comparisonItem = $comparisonLookup[$this->itemComparisonKey($item)] ?? null;
            $comparisonAmount = (float) ($comparisonItem[$amountField] ?? 0);
            $amount = (float) ($item[$amountField] ?? 0);

            $item['comparison_amount'] = $comparisonAmount;
            $item['variance'] = $amount - $comparisonAmount;
            $item['variance_percent'] = $comparisonAmount > 0
                ? (($item['variance'] / $comparisonAmount) * 100)
                : 0;
        }
        unset($item);

        return $currentItems;
    }

    protected function attachOperatingExpenseComparisonMetrics(array $currentItems, array $comparisonItems): array
    {
        $comparisonLookup = [];
        foreach ($comparisonItems as $comparisonItem) {
            $comparisonLookup[$this->itemComparisonKey($comparisonItem)] = $comparisonItem;
        }

        foreach ($currentItems as &$item) {
            $comparisonItem = $comparisonLookup[$this->itemComparisonKey($item)] ?? null;

            if (isset($item['items'])) {
                $comparisonAmount = (float) ($comparisonItem['total'] ?? 0);
                $amount = (float) ($item['total'] ?? 0);
                $item['comparison_amount'] = $comparisonAmount;
                $item['variance'] = $amount - $comparisonAmount;
                $item['variance_percent'] = $comparisonAmount > 0
                    ? (($item['variance'] / $comparisonAmount) * 100)
                    : 0;
                $item['items'] = $this->attachComparisonMetrics(
                    $item['items'],
                    $comparisonItem['items'] ?? []
                );
                continue;
            }

            $comparisonAmount = (float) ($comparisonItem['amount'] ?? 0);
            $amount = (float) ($item['amount'] ?? 0);
            $item['comparison_amount'] = $comparisonAmount;
            $item['variance'] = $amount - $comparisonAmount;
            $item['variance_percent'] = $comparisonAmount > 0
                ? (($item['variance'] / $comparisonAmount) * 100)
                : 0;
        }
        unset($item);

        return $currentItems;
    }

    protected function itemComparisonKey(array $item): string
    {
        if (! empty($item['coa_id'])) {
            return 'coa:' . (int) $item['coa_id'];
        }

        return 'name:' . Str::lower(trim((string) ($item['name'] ?? '')));
    }
}
