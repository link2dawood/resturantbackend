<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Store;
use App\Services\Inventory\VarianceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Phase 5.8 — the variance report (the spec's most-important module surfaced).
 * Filters (store, week, item), color-coded lines, drill-down to the contributing
 * menu items, and PDF / CSV export. Manager-and-up only (employees excluded).
 */
class VarianceReportController extends Controller
{
    public function __construct(private VarianceReportService $reports)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->week($request);
        $itemId = $request->filled('item_id') ? (int) $request->input('item_id') : null;

        $rows = $this->reports->compute($store->id, $week, $itemId);

        return view('admin.variance.index', array_merge(
            ['store' => $store, 'week' => $week, 'itemId' => $itemId],
            $this->viewData($store, $rows),
        ));
    }

    public function drillDown(Request $request, InventoryItem $inventoryItem)
    {
        $this->authorizeStore($inventoryItem->store_id);
        $week = $this->week($request);

        $line = app(\App\Services\Inventory\VarianceCalculationService::class)
            ->calculate($inventoryItem->store_id, $inventoryItem->id, $week);
        $contributors = $this->reports->contributors($inventoryItem->store_id, $inventoryItem->id, $week);

        return view('admin.variance.drill-down', [
            'item' => $inventoryItem,
            'week' => $week,
            'line' => $line,
            'contributors' => $contributors,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->week($request);
        $rows = $this->reports->compute($store->id, $week);

        $html = view('admin.variance.pdf', array_merge(['store' => $store, 'week' => $week], $this->viewData($store, $rows)))->render();

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'variance-'.($store->store_info ?? $store->id).'-'.$week->toDateString().'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function exportCsv(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->week($request);
        $rows = $this->reports->compute($store->id, $week);

        $filename = 'variance-'.($store->store_info ?? $store->id).'-'.$week->toDateString().'.csv';
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Item', 'Unit', 'Starting', 'Ordered', 'Available', 'Theoretical usage', 'Theoretical ending', 'Actual ending', 'Variance', 'Variance %', 'Severity']);
            foreach ($rows as $r) {
                $l = $r['line'];
                fputcsv($out, [
                    $r['item']->name, $l->baseUnit,
                    $l->startingStock, $l->orderedQty, $l->totalAvailable,
                    $l->theoreticalUsage, $l->theoreticalEnding,
                    $l->isIncomplete ? '' : $l->actualEnding,
                    $l->variance ?? '', $l->variancePct ?? '', $l->severity ?? 'incomplete',
                ]);
            }
            fclose($out);
        }, $filename, $headers);
    }

    /** Shared view data: rows + severity tallies. */
    private function viewData(Store $store, array $rows): array
    {
        $tally = ['green' => 0, 'yellow' => 0, 'red' => 0, 'incomplete' => 0];
        foreach ($rows as $r) {
            $tally[$r['line']->isIncomplete ? 'incomplete' : ($r['line']->severity ?? 'incomplete')]++;
        }

        return [
            'rows' => $rows,
            'tally' => $tally,
            'items' => InventoryItem::where('store_id', $store->id)->where('is_active', true)->orderBy('name')->get(),
            'stores' => $this->storeOptions(),
        ];
    }

    private function week(Request $request): Carbon
    {
        return $request->filled('week_start_date')
            ? Carbon::parse($request->input('week_start_date'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
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

    private function authorizeStore(int $storeId): void
    {
        abort_unless(in_array($storeId, auth()->user()->getAccessibleStoreIds(), true), 403);
    }
}
