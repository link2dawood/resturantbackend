<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\PlSnapshot;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\ThirdPartyStatement;
use App\Http\Controllers\Api\ProfitLossController as PLController;
use Illuminate\Http\Request;

class ProfitLossViewController extends Controller
{
    protected $plController;

    public function __construct()
    {
        $this->plController = new PLController();
    }

    /**
     * Display P&L report page
     * 
     * Permissions:
     * - Super Admin: Full access to all stores
     * - Owner/Admin: Generate P&L for their stores
     * - Manager: View store-level P&L only (no export, no generation)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Managers can only view P&L (not generate or export)
        if ($user->isManager()) {
            // Managers can view but not export or generate new reports
            // They can only view existing reports for their assigned stores
        }
        
        // Get accessible stores based on user role
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleStoreIds)->get();
        
        // Set default date range to current month
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->input('store_id');
        
        // If store_id is provided, ensure user has access
        if ($storeId && !$user->hasStoreAccess($storeId)) {
            abort(403, 'Access denied to this store');
        }
        
        // If no store selected and user is manager, use their store
        if (!$storeId && $user->isManager() && !empty($accessibleStoreIds)) {
            $storeId = $accessibleStoreIds[0];
        }
        
        // Managers can only view their assigned stores
        if ($user->isManager() && $storeId && !$user->hasStoreAccess($storeId)) {
            abort(403, 'Access denied to this store');
        }
        
        $comparisonPeriod = $request->input('comparison_period');
        [$allYearsStartDate, $allYearsEndDate] = $this->resolveAllYearsDateRange($storeId, $accessibleStoreIds);
        
        // Calculate P&L
        $request->merge([
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'comparison_period' => $comparisonPeriod,
        ]);
        
        $response = $this->plController->index($request);
        $data = json_decode($response->getContent(), true);
        
        return view('admin.reports.profit-loss.index', compact(
            'stores',
            'startDate',
            'endDate',
            'storeId',
            'comparisonPeriod',
            'allYearsStartDate',
            'allYearsEndDate',
            'data'
        ));
    }

    /**
     * Display drill-down for a specific COA
     */
    public function drillDown(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($request->filled('store_id') && ! $user->hasStoreAccess((int) $request->input('store_id'))) {
            abort(403, 'Access denied to this store');
        }

        $stores = Store::whereIn('id', $user->getAccessibleStoreIds())->get();
        
        $response = $this->plController->drillDown($request);
        $data = json_decode($response->getContent(), true);
        
        return view('admin.reports.profit-loss.drill-down', compact(
            'stores',
            'data'
        ));
    }

    /**
     * Display multi-store comparison
     */
    public function comparison(Request $request)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleStoreIds)->get();
        
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeIds = $request->input('store_ids', []);
        $metric = $request->input('metric', 'profit');
        
        if (empty($storeIds)) {
            $storeIds = $stores->pluck('id')->toArray();
        }

        $storeIds = array_values(array_unique(array_map('intval', array_filter($storeIds, fn ($id) => filled($id)))));

        foreach ($storeIds as $storeId) {
            if (! in_array($storeId, $accessibleStoreIds, true)) {
                abort(403, 'Access denied to one or more stores');
            }
        }

        $comparisonData = null;

        if (count($storeIds) >= 2) {
            $request->merge([
                'store_ids' => $storeIds,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'metric' => $metric,
            ]);

            $response = $this->plController->storeComparison($request);
            $comparisonData = json_decode($response->getContent(), true);
        }
        
        return view('admin.reports.profit-loss.comparison', compact(
            'stores',
            'startDate',
            'endDate',
            'storeIds',
            'metric',
            'comparisonData'
        ));
    }

    /**
     * Display Annual P&L with monthly columns for a selected year
     */
    public function annual(Request $request)
    {
        $user               = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores             = Store::whereIn('id', $accessibleStoreIds)->get();

        $selectedYear  = (int) $request->input('year', now()->year);
        $selectedMonth = (int) $request->input('month', now()->month);
        $reportType    = $request->input('report_type', 'annual');
        $storeId       = $request->input('store_id');

        if ($storeId && !$user->hasStoreAccess($storeId)) {
            abort(403, 'Access denied to this store');
        }

        if (!$storeId && $user->isManager() && !empty($accessibleStoreIds)) {
            $storeId = $accessibleStoreIds[0];
        }

        // Build available years from all financial data sources
        $years = collect(array_unique(array_merge(
            DailyReport::selectRaw('YEAR(report_date) as y')->distinct()->pluck('y')->toArray(),
            ExpenseTransaction::selectRaw('YEAR(transaction_date) as y')->distinct()->pluck('y')->toArray(),
            ThirdPartyStatement::selectRaw('YEAR(statement_date) as y')->distinct()->pluck('y')->toArray(),
            [now()->year]
        )))->sort()->values();

        $months = collect(range(1, 12))->mapWithKeys(function ($monthNumber) {
            return [$monthNumber => now()->setMonth($monthNumber)->format('F')];
        });

        if ($reportType === 'monthly') {
            $month = max(1, min(12, $selectedMonth));
            $startDate = now()->setYear($selectedYear)->setMonth($month)->startOfMonth()->format('Y-m-d');
            $endDate = now()->setYear($selectedYear)->setMonth($month)->endOfMonth()->format('Y-m-d');

            return redirect()->route('admin.reports.profit-loss.index', array_filter([
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ], fn ($value) => filled($value)));
        }

        if ($reportType === 'all_years') {
            [$startDate, $endDate] = $this->resolveAllYearsDateRange($storeId, $accessibleStoreIds);

            return redirect()->route('admin.reports.profit-loss.index', array_filter([
                'store_id' => $storeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ], fn ($value) => filled($value)));
        }

        $request->merge(['year' => $selectedYear, 'store_id' => $storeId]);
        $response = $this->plController->annual($request);
        $data     = json_decode($response->getContent(), true);

        return view('admin.reports.profit-loss.annual', compact(
            'stores', 'selectedYear', 'selectedMonth', 'reportType', 'storeId', 'years', 'months', 'data'
        ));
    }

    protected function resolveAllYearsDateRange($storeId, array $accessibleStoreIds): array
    {
        $applyStoreScope = function ($query, string $column) use ($storeId, $accessibleStoreIds) {
            if (filled($storeId)) {
                $query->where($column, $storeId);
                return;
            }

            if (empty($accessibleStoreIds)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereIn($column, $accessibleStoreIds);
        };

        $ranges = [];

        $dailyReportRange = DailyReport::query();
        $applyStoreScope($dailyReportRange, 'store_id');
        $ranges[] = [
            'start' => $dailyReportRange->min('report_date'),
            'end' => $dailyReportRange->max('report_date'),
        ];

        $expenseRange = ExpenseTransaction::query();
        $applyStoreScope($expenseRange, 'store_id');
        $ranges[] = [
            'start' => $expenseRange->min('transaction_date'),
            'end' => $expenseRange->max('transaction_date'),
        ];

        $thirdPartyRange = ThirdPartyStatement::query();
        $applyStoreScope($thirdPartyRange, 'store_id');
        $ranges[] = [
            'start' => $thirdPartyRange->min('statement_date'),
            'end' => $thirdPartyRange->max('statement_date'),
        ];

        $startDate = collect($ranges)->pluck('start')->filter()->min();
        $endDate = collect($ranges)->pluck('end')->filter()->max();

        if (!$startDate || !$endDate) {
            return [
                now()->startOfYear()->format('Y-m-d'),
                now()->endOfYear()->format('Y-m-d'),
            ];
        }

        return [$startDate, $endDate];
    }

    /**
     * Display P&L snapshots
     */
    public function snapshots(Request $request)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleStoreIds)->get();
        $storeId = $request->input('store_id');
        
        // Query snapshots directly to get a paginator object instead of JSON array
        $query = PlSnapshot::with(['store', 'creator']);

        if (! $user->isAdmin()) {
            $query->where(function ($snapshotQuery) use ($accessibleStoreIds, $user) {
                $snapshotQuery->whereIn('store_id', $accessibleStoreIds)
                    ->orWhere(function ($nullStoreQuery) use ($user) {
                        $nullStoreQuery->whereNull('store_id')
                            ->where('created_by', $user->id);
                    });
            });
        }

        if ($storeId) {
            if (! $user->hasStoreAccess((int) $storeId)) {
                abort(403, 'Access denied to this store');
            }

            $query->where('store_id', $storeId);
        }
        
        $snapshots = $query->orderBy('created_at', 'desc')->paginate(25);
        
        return view('admin.reports.profit-loss.snapshots', compact(
            'stores',
            'storeId',
            'snapshots'
        ));
    }

    public function showSnapshot(PlSnapshot $snapshot)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        if ($snapshot->store_id) {
            if (! $user->hasStoreAccess((int) $snapshot->store_id)) {
                abort(403, 'Access denied to this store');
            }
        } elseif (! $user->isAdmin() && (int) $snapshot->created_by !== (int) $user->id) {
            abort(403, 'Access denied to this snapshot');
        }

        $stores = Store::whereIn('id', $accessibleStoreIds)->get();
        $startDate = optional($snapshot->start_date)->format('Y-m-d') ?? (string) $snapshot->start_date;
        $endDate = optional($snapshot->end_date)->format('Y-m-d') ?? (string) $snapshot->end_date;
        $storeId = $snapshot->store_id;
        $comparisonPeriod = null;
        $data = [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'comparison_period' => null,
            'pl' => $snapshot->pl_data,
        ];
        $snapshotMode = true;

        return view('admin.reports.profit-loss.index', compact(
            'stores',
            'startDate',
            'endDate',
            'storeId',
            'comparisonPeriod',
            'data',
            'snapshot',
            'snapshotMode'
        ));
    }

    /**
     * Export P&L to CSV
     * Only Admin and Owner can export (Managers cannot export)
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        
        // Managers cannot export P&L
        if ($user->isManager()) {
            abort(403, 'Managers cannot export P&L reports');
        }
        
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        // Ensure owner can only export for their stores
        if ($user->isOwner() && $request->store_id && !$user->hasStoreAccess($request->store_id)) {
            abort(403, 'Access denied to this store');
        }

        ['pl' => $pl, 'storeName' => $storeName, 'filenameBase' => $filenameBase] = $this->buildExportPayload($request);

        $callback = function () use ($pl, $storeName, $request) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['PROFIT & LOSS STATEMENT']);
            fputcsv($file, ['Store:', $storeName]);
            fputcsv($file, ['Period:', $request->start_date . ' to ' . $request->end_date]);
            fputcsv($file, ['Generated:', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []); // Empty row

            // REVENUE SECTION
            fputcsv($file, ['REVENUE']);
            if (!empty($pl['revenue']['items'])) {
                foreach ($pl['revenue']['items'] as $item) {
                    fputcsv($file, [
                        $item['name'],
                        '$' . number_format($item['amount'], 2)
                    ]);
                }
            }
            fputcsv($file, ['Total Revenue', '$' . number_format($pl['revenue']['total'] ?? 0, 2)]);
            fputcsv($file, []); // Empty row

            // COGS SECTION
            fputcsv($file, ['COST OF GOODS SOLD (COGS)']);
            if (!empty($pl['cogs']['items'])) {
                foreach ($pl['cogs']['items'] as $item) {
                    fputcsv($file, [
                        $item['name'],
                        '$' . number_format($item['amount'], 2)
                    ]);
                }
            }
            fputcsv($file, ['Total COGS', '$' . number_format($pl['cogs']['total'] ?? 0, 2)]);
            fputcsv($file, []); // Empty row

            // GROSS PROFIT
            fputcsv($file, ['Gross Profit', '$' . number_format($pl['gross_profit'] ?? 0, 2)]);
            fputcsv($file, ['Gross Margin', number_format($pl['gross_margin'] ?? 0, 2) . '%']);
            fputcsv($file, []); // Empty row

            // OPERATING EXPENSES SECTION
            fputcsv($file, ['OPERATING EXPENSES']);
            if (!empty($pl['operating_expenses']['items'])) {
                foreach ($pl['operating_expenses']['items'] as $item) {
                    if (isset($item['items'])) {
                        // Parent category with sub-items
                        fputcsv($file, [$item['name'] . ' (Total)', '$' . number_format($item['total'] ?? 0, 2)]);
                        foreach ($item['items'] ?? [] as $subItem) {
                            fputcsv($file, ['  ' . $subItem['name'], '$' . number_format($subItem['amount'], 2)]);
                        }
                    } else {
                        fputcsv($file, [
                            $item['name'],
                            '$' . number_format($item['amount'], 2)
                        ]);
                    }
                }
            }
            fputcsv($file, ['Total Operating Expenses', '$' . number_format($pl['operating_expenses']['total'] ?? 0, 2)]);
            fputcsv($file, []); // Empty row

            // NET PROFIT/LOSS
            $netProfit = $pl['net_profit'] ?? 0;
            fputcsv($file, ['NET PROFIT / (LOSS)', '$' . number_format($netProfit, 2)]);
            fputcsv($file, ['Net Margin', number_format($pl['net_margin'] ?? 0, 2) . '%']);

            fclose($file);
        };

        return response()->streamDownload($callback, "{$filenameBase}.csv", [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Export P&L to PDF
     * Only Admin and Owner can export (Managers cannot export)
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        
        // Managers cannot export P&L
        if ($user->isManager()) {
            abort(403, 'Managers cannot export P&L reports');
        }
        
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        // Ensure owner can only export for their stores
        if ($user->isOwner() && $request->store_id && !$user->hasStoreAccess($request->store_id)) {
            abort(403, 'Access denied to this store');
        }

        ['pl' => $pl, 'storeName' => $storeName, 'filenameBase' => $filenameBase] = $this->buildExportPayload($request);

        // Use Blade view for PDF generation
        $html = view('admin.reports.profit-loss.pdf', [
            'pl' => $pl,
            'storeName' => $storeName,
            'startDate' => $request->start_date,
            'endDate' => $request->end_date,
            'generatedAt' => now(),
        ])->render();

        abort_unless(class_exists(\Dompdf\Dompdf::class), 500, 'PDF export is not available.');

        $dompdf = new \Dompdf\Dompdf();
        $options = $dompdf->getOptions();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $dompdf->setOptions($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filenameBase . '.pdf"',
        ]);
    }

    private function buildExportPayload(Request $request): array
    {
        $request->merge([
            'store_id' => $request->input('store_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ]);

        $response = $this->plController->index($request);
        $data = json_decode($response->getContent(), true);
        $pl = $data['pl'] ?? [];

        $store = $request->store_id ? Store::find($request->store_id) : null;
        $storeName = $store ? $store->store_info : 'All Stores';
        $safeStoreName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $storeName);
        $safeStartDate = preg_replace('/[^0-9-]+/', '_', (string) $request->start_date);
        $safeEndDate = preg_replace('/[^0-9-]+/', '_', (string) $request->end_date);

        return [
            'pl' => $pl,
            'storeName' => $storeName,
            'filenameBase' => "profit_loss_{$safeStoreName}_{$safeStartDate}_to_{$safeEndDate}",
        ];
    }
}
