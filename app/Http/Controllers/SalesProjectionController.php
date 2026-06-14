<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\SalesProjection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 4 — Sales Projection Calendar.
 *
 * A monthly calendar to enter a daily sales projection per store and compare it
 * against the actual net sales from the filed DailyReport. Projections are
 * tenant-scoped (per store); quick-entry upserts one day at a time via AJAX.
 */
class SalesProjectionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $stores = $user->accessibleStores()->orderBy('store_info')->get();

        if ($stores->isEmpty()) {
            return view('sales-projections.index', ['stores' => $stores, 'store' => null]);
        }

        $storeId = (int) $request->query('store_id', $stores->first()->id);
        abort_unless($user->hasStoreAccess($storeId), 403);
        $store = $stores->firstWhere('id', $storeId);

        $month = $this->parseMonth($request->query('month'));
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        // Projections (calendar) and actuals (filed reports) for the month.
        $projections = SalesProjection::where('store_id', $storeId)
            ->whereBetween('projection_date', [$start, $end])
            ->get()
            ->keyBy(fn ($p) => $p->projection_date->format('Y-m-d'));

        // Use the raw net_sales COLUMN (DailyReport has a computed net_sales
        // accessor that would otherwise recompute from relations).
        $actuals = [];
        foreach (DailyReport::where('store_id', $storeId)->whereBetween('report_date', [$start, $end])->get(['report_date', 'net_sales']) as $r) {
            $actuals[Carbon::parse($r->report_date)->format('Y-m-d')] = (float) $r->getRawOriginal('net_sales');
        }

        [$weeks, $totals] = $this->buildCalendar($start, $end, $projections, $actuals);

        return view('sales-projections.index', [
            'stores' => $stores,
            'store' => $store,
            'storeId' => $storeId,
            'month' => $start,
            'prevMonth' => $start->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextMonth' => $start->copy()->addMonthNoOverflow()->format('Y-m'),
            'weeks' => $weeks,
            'totals' => $totals,
        ]);
    }

    /**
     * Quick-entry: upsert a single day's projection (AJAX).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless($request->user()->hasStoreAccess($validated['store_id']), 403);

        $date = Carbon::parse($validated['date'])->toDateString();

        // Match on the date part (the `date` cast persists a time component, so an
        // exact-string updateOrCreate lookup would miss and re-insert → unique clash).
        $projection = SalesProjection::where('store_id', $validated['store_id'])
            ->whereDate('projection_date', $date)
            ->first();

        if ($projection) {
            $projection->update(['amount' => $validated['amount'], 'updated_by' => $request->user()->id]);
        } else {
            $projection = SalesProjection::create([
                'store_id' => $validated['store_id'],
                'projection_date' => $date,
                'amount' => $validated['amount'],
                'updated_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'ok' => true,
            'date' => $validated['date'],
            'amount' => (float) $projection->amount,
        ]);
    }

    private function parseMonth(?string $month): Carbon
    {
        try {
            return $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : Carbon::now()->startOfMonth();
        } catch (\Throwable) {
            return Carbon::now()->startOfMonth();
        }
    }

    /**
     * @return array{0: array<int, array<int, array>>, 1: array{projected: float, actual: float, variance: float}}
     */
    private function buildCalendar(Carbon $start, Carbon $end, $projections, $actuals): array
    {
        $gridStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $end->copy()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $totalProjected = 0.0;
        $totalActual = 0.0;
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->format('Y-m-d');
                $inMonth = $cursor->month === $start->month;
                $projection = isset($projections[$key]) ? (float) $projections[$key]->amount : null;
                $actual = $actuals[$key] ?? null;

                if ($inMonth) {
                    $totalProjected += $projection ?? 0;
                    $totalActual += $actual ?? 0;
                }

                $week[] = [
                    'date' => $cursor->copy(),
                    'key' => $key,
                    'day' => $cursor->day,
                    'in_month' => $inMonth,
                    'projection' => $projection,
                    'actual' => $actual,
                    'variance' => ($projection !== null && $actual !== null) ? round($actual - $projection, 2) : null,
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return [$weeks, [
            'projected' => round($totalProjected, 2),
            'actual' => round($totalActual, 2),
            'variance' => round($totalActual - $totalProjected, 2),
        ]];
    }
}
