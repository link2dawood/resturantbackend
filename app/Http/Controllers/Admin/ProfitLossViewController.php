<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\PlSnapshot;
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
            'data'
        ));
    }

    /**
     * Display drill-down for a specific COA
     */
    public function drillDown(Request $request)
    {
        $request->validate([
            'store_id' => 'nullable|exists:stores,id',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        
        $stores = Store::all();
        
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
        $stores = Store::all();
        
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeIds = $request->input('store_ids', []);
        $metric = $request->input('metric', 'profit');
        
        if (empty($storeIds)) {
            $storeIds = Store::pluck('id')->toArray();
        }
        
        $request->merge([
            'store_ids' => $storeIds,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'metric' => $metric,
        ]);
        
        $response = $this->plController->storeComparison($request);
        $comparisonData = json_decode($response->getContent(), true);
        
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
     * Display P&L snapshots
     */
    public function snapshots(Request $request)
    {
        $stores = Store::all();
        $storeId = $request->input('store_id');
        
        // Query snapshots directly to get a paginator object instead of JSON array
        $query = PlSnapshot::with(['store', 'creator']);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $snapshots = $query->orderBy('created_at', 'desc')->paginate(25);
        
        return view('admin.reports.profit-loss.snapshots', compact(
            'stores',
            'storeId',
            'snapshots'
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

        // Calculate P&L
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
        $filename = "profit_loss_{$storeName}_" . str_replace([' ', '/'], '_', $request->start_date) . '_to_' . str_replace([' ', '/'], '_', $request->end_date) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

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

        return response()->stream($callback, 200, $headers);
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

        // Calculate P&L
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

        // Use Blade view for PDF generation
        $html = view('admin.reports.profit-loss.pdf', [
            'pl' => $pl,
            'storeName' => $storeName,
            'startDate' => $request->start_date,
            'endDate' => $request->end_date,
            'generatedAt' => now(),
        ])->render();

        // Check if DOMPDF is available
        if (class_exists(\DomPDF\DomPDF::class)) {
            $dompdf = new \DomPDF\DomPDF();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $filename = "profit_loss_{$storeName}_" . str_replace([' ', '/'], '_', $request->start_date) . '_to_' . str_replace([' ', '/'], '_', $request->end_date) . '.pdf';

            return $dompdf->stream($filename);
        } else {
            // Fallback: return HTML view
            return view('admin.reports.profit-loss.pdf', [
                'pl' => $pl,
                'storeName' => $storeName,
                'startDate' => $request->start_date,
                'endDate' => $request->end_date,
                'generatedAt' => now(),
            ]);
        }
    }
}
