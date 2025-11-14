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
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
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
}
