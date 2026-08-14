<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Inventory\StockUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 5.5 — weekly stock-up worksheet. Enter the projected weekly sales dollars
 * and see suggested order quantities per item (from historical usage), with an
 * editable override per row. Admin/owner/manager, store-scoped. Turning the
 * suggestions into vendor orders happens in module 5.6.
 */
class StockUpController extends Controller
{
    public function __construct(private StockUpService $stockUp)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);

        $week = $request->filled('week_start_date')
            ? Carbon::parse($request->week_start_date)->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $projectedDollars = max(0.0, (float) $request->input('projected_dollars', 0));
        $historyWeeks = min(12, max(1, (int) $request->input('history_weeks', 4)));

        $suggestions = $this->stockUp->suggest($store->id, $week, $projectedDollars, $historyWeeks);

        return view('admin.stock-up.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'projectedDollars' => $projectedDollars,
            'historyWeeks' => $historyWeeks,
            'suggestions' => $suggestions,
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
