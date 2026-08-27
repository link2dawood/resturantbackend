<?php

namespace App\Services\Inventory;

use App\Models\DailyReport;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Phase 5.5 — weekly stock-up suggestions.
 *
 * Algorithm (per item):
 *   usage_per_dollar = Σ historical usage / Σ historical sales$ (daily-report net sales)
 *   projected_usage  = projected weekly sales$ × usage_per_dollar
 *   required         = max(projected_usage × (1 + safety_buffer%), min_stock_level)
 *   suggested_order  = max(0, required − current on-hand)
 *
 * Historical usage reuses VarianceCalculationService::usage() (sold × recipe
 * portions), so usage is defined in one place. Window = last N weeks (default 4).
 * When there is no sales-dollar history, it falls back to the average weekly
 * usage (the dollar projection is ignored) so the tool still works from day one.
 */
class StockUpService
{
    public function __construct(private VarianceCalculationService $variance)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function suggest(int $storeId, string|CarbonInterface $projectionWeek, float $projectedDollars, int $historyWeeks = 4): array
    {
        $projMonday = Carbon::parse($projectionWeek)->startOfWeek(Carbon::MONDAY);

        $weeks = [];
        for ($i = 1; $i <= max(1, $historyWeeks); $i++) {
            $weeks[] = $projMonday->copy()->subWeeks($i);
        }

        // Historical sales dollars = sum of daily-report net sales across the weeks.
        $totalSales = 0.0;
        foreach ($weeks as $w) {
            $reports = DailyReport::where('store_id', $storeId)
                ->whereBetween('report_date', [$w->toDateString(), $w->copy()->addDays(6)->toDateString()])
                ->get();
            foreach ($reports as $r) {
                $totalSales += (float) $r->net_sales;
            }
        }

        $items = InventoryItem::where('store_id', $storeId)->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        $suggestions = [];
        foreach ($items as $item) {
            $usages = array_map(fn ($w) => $this->variance->usage($storeId, $item->id, $w), $weeks);
            $totalUsage = array_sum($usages);
            $weeksWithData = count(array_filter($usages, fn ($u) => $u > 0));
            $avgWeekly = $weeksWithData > 0 ? $totalUsage / $weeksWithData : 0.0;

            if ($totalSales > 0.00001) {
                $projectedUsage = $projectedDollars * ($totalUsage / $totalSales);
                $basis = 'sales';
            } else {
                $projectedUsage = $avgWeekly; // no dollar history → use average usage
                $basis = 'average';
            }

            $buffer = (float) $item->safety_buffer_pct / 100.0;
            $required = max($projectedUsage * (1 + $buffer), (float) $item->min_stock_level);

            $currentOnHand = (float) (InventoryStock::where('inventory_item_id', $item->id)
                ->whereDate('week_start_date', $projMonday->toDateString())
                ->value('starting_stock') ?? 0);

            $suggestions[] = [
                'item' => $item,
                'basis' => $basis,
                'avg_weekly_usage' => round($avgWeekly, 4),
                'projected_usage' => round($projectedUsage, 4),
                'current_on_hand' => round($currentOnHand, 4),
                'required' => round($required, 4),
                'suggested_order' => round(max(0.0, $required - $currentOnHand), 4),
                'reorder_flag' => $currentOnHand <= (float) $item->reorder_threshold,
            ];
        }

        return $suggestions;
    }
}
