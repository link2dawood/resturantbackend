<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\MenuItemSold;
use App\Models\Recipe;
use App\Models\VarianceReport;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5.8 — assembles the variance report from the engine (VarianceCalculationService),
 * plus the per-item drill-down (which menu items drove the usage) and an optional
 * persisted snapshot (variance_reports + lines) reused by the weekly job (5.9).
 */
class VarianceReportService
{
    public function __construct(private VarianceCalculationService $engine)
    {
    }

    /**
     * One variance line per active item (optionally a single item).
     *
     * @return array<int, array{item: InventoryItem, line: \App\Services\Inventory\VarianceLine}>
     */
    public function compute(int $storeId, string|CarbonInterface $week, ?int $itemId = null): array
    {
        $items = InventoryItem::where('store_id', $storeId)->where('is_active', true)
            ->when($itemId, fn ($q) => $q->where('id', $itemId))
            ->orderBy('category')->orderBy('name')->get();

        return $items->map(fn ($item) => [
            'item' => $item,
            'line' => $this->engine->calculate($storeId, $item->id, $week),
        ])->all();
    }

    /**
     * Drill-down: the menu items (and quantities) that contributed to one item's
     * theoretical usage for the week, largest first.
     *
     * @return list<array{menu_item: string, size: string, qty_sold: float, portion: float, usage: float}>
     */
    public function contributors(int $storeId, int $itemId, string|CarbonInterface $week): array
    {
        $weekStr = Carbon::parse($week)->toDateString();

        $sold = MenuItemSold::with('menuItem')
            ->where('store_id', $storeId)
            ->whereDate('week_start_date', $weekStr)
            ->where('is_matched', true)
            ->whereNotNull('menu_item_id')
            ->get();

        $rows = [];
        foreach ($sold as $s) {
            $recipe = Recipe::where('menu_item_id', $s->menu_item_id)
                ->where('size_variant', $s->size_variant)
                ->where('is_current', true)
                ->first();
            if (! $recipe) {
                continue;
            }
            $ing = $recipe->ingredients()->where('inventory_item_id', $itemId)->first();
            if (! $ing) {
                continue;
            }

            $rows[] = [
                'menu_item' => $s->menuItem->name ?? $s->square_raw_name,
                'size' => (string) $s->size_variant,
                'qty_sold' => (float) $s->quantity_sold,
                'portion' => (float) $ing->quantity_base,
                'usage' => (float) $s->quantity_sold * (float) $ing->quantity_base,
            ];
        }

        usort($rows, fn ($a, $b) => $b['usage'] <=> $a['usage']);

        return $rows;
    }

    /** Persist a snapshot for the week (replacing any prior one). */
    public function persist(int $storeId, string|CarbonInterface $week, ?int $userId = null): VarianceReport
    {
        $weekStr = Carbon::parse($week)->startOfWeek(Carbon::MONDAY)->toDateString();

        return DB::transaction(function () use ($storeId, $weekStr, $userId) {
            VarianceReport::where('store_id', $storeId)->whereDate('week_start_date', $weekStr)->delete();

            $report = VarianceReport::create([
                'store_id' => $storeId,
                'week_start_date' => $weekStr,
                'status' => 'generated',
                'generated_by' => $userId,
                'generated_at' => now(),
            ]);

            foreach ($this->compute($storeId, $weekStr) as $row) {
                $report->lines()->create($row['line']->toArray());
            }

            return $report;
        });
    }
}
