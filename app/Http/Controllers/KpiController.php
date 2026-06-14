<?php

namespace App\Http\Controllers;

use App\Models\KpiTarget;
use Illuminate\Http\Request;

/**
 * Phase 4 — KPI configuration.
 *
 * Lets an owner (or admin) set per-store targets for the dashboard cost rings
 * (Food Cost %, Payroll %, Rent %). Targets are stored per tenant (store) and
 * read back by DashboardMetricsService; anything left blank falls back to the
 * config defaults.
 */
class KpiController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function edit(Request $request)
    {
        $stores = $request->user()->accessibleStores()->orderBy('store_info')->get();

        // Existing targets keyed by store id (TenantScoped → only accessible stores).
        $targets = KpiTarget::whereIn('store_id', $stores->pluck('id'))->get()->keyBy('store_id');

        return view('kpi.edit', [
            'stores' => $stores,
            'targets' => $targets,
            'defaults' => config('dashboard.targets'),
        ]);
    }

    public function update(Request $request)
    {
        $accessibleIds = $request->user()->getAccessibleStoreIds();

        $validated = $request->validate([
            'targets' => ['required', 'array'],
            'targets.*.food_cost_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'targets.*.payroll_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'targets.*.rent_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated['targets'] as $storeId => $values) {
            // Only allow writing targets for stores the user can actually access.
            if (! in_array((int) $storeId, $accessibleIds, true)) {
                continue;
            }

            KpiTarget::updateOrCreate(
                ['store_id' => (int) $storeId],
                [
                    'food_cost_pct' => $values['food_cost_pct'] ?? null,
                    'payroll_pct' => $values['payroll_pct'] ?? null,
                    'rent_pct' => $values['rent_pct'] ?? null,
                    'updated_by' => $request->user()->id,
                ]
            );
        }

        return redirect()->route('kpi.edit')->with('success', 'KPI targets saved.');
    }
}
