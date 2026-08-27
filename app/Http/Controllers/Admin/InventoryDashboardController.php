<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Store;
use App\Services\Inventory\ManagerDashboardService;
use App\Services\Inventory\VarianceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 5.9 — inventory operations dashboard: this week's count progress, pending
 * orders, and last week's variance alerts. Admin/owner/manager, store-scoped.
 */
class InventoryDashboardController extends Controller
{
    public function __construct(
        private VarianceReportService $variance,
        private ManagerDashboardService $widgets,
    ) {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $priorWeek = $week->copy()->subWeek();

        $stock = InventoryStock::where('store_id', $store->id)->whereDate('week_start_date', $week->toDateString());
        $totalItems = (clone $stock)->count();
        $submittedItems = (clone $stock)->where('status', 'submitted')->count();

        // Task 13 widgets.
        $countStatus = $this->widgets->countStatus($store, $week);
        $pendingOrders = $this->widgets->pendingOrders($store, $week);
        $lowStock = $this->widgets->lowStock($store, $week);
        $recentActivity = $this->widgets->recentActivity($store);

        // Last completed week's variance — surface red/yellow lines as alerts.
        $alerts = collect($this->variance->compute($store->id, $priorWeek))
            ->filter(fn ($r) => in_array($r['line']->severity, ['red', 'yellow'], true))
            ->sortByDesc(fn ($r) => abs((float) ($r['line']->variancePct ?? 0)))
            ->values();

        return view('admin.inventory-dashboard.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'priorWeek' => $priorWeek,
            'totalItems' => $totalItems,
            'submittedItems' => $submittedItems,
            'pendingOrders' => $pendingOrders,
            'alerts' => $alerts,
            'countStatus' => $countStatus,
            'lowStock' => $lowStock,
            'recentActivity' => $recentActivity,
        ]);
    }

    private function resolveStore(Request $request): Store
    {
        $accessible = auth()->user()->getAccessibleStoreIds();
        abort_if(empty($accessible), 403, 'No accessible store.');

        if (auth()->user()->isManager()) {
            $storeId = $accessible[0];
        } else {
            $requested = (int) $request->input('store_id');
            $storeId = in_array($requested, $accessible, true) ? $requested : $accessible[0];
        }

        return Store::findOrFail($storeId);
    }

    private function storeOptions()
    {
        if (auth()->user()->isManager()) {
            return collect();
        }

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())->get();
    }
}
