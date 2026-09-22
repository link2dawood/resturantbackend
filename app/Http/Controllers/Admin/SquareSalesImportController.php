<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Store;
use App\Services\Inventory\SquareItemsSoldParser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 5.4 — weekly Square "Items Sold" import wizard: upload → preview (with
 * auto-match to menu items + manual mapping of unmatched rows) → commit. Never
 * auto-commits; committing replaces that week's rows so re-imports are safe.
 * CSV only (no Square API). Admin/owner/manager, store-scoped.
 */
class SquareSalesImportController extends Controller
{
    private const SIZES = ['mini', 'regular', 'large'];

    public function __construct(private SquareItemsSoldParser $parser)
    {
    }

    public function form(Request $request)
    {
        $store = $this->resolveStore($request);

        return view('admin.square-import.form', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => Carbon::now()->startOfWeek(Carbon::MONDAY),
        ]);
    }

    public function preview(Request $request)
    {
        $store = $this->resolveStore($request);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'week_start_date' => ['required', 'date'],
        ]);

        $week = Carbon::parse($request->week_start_date)->startOfWeek(Carbon::MONDAY);
        $parsed = $this->parser->parse($request->file('file')->getRealPath());

        // Aggregate per (item, variation) — Square repeats items across days.
        $agg = [];
        foreach ($parsed as $r) {
            $key = mb_strtolower($r['item']).'|'.mb_strtolower($r['variation']);
            $agg[$key] ??= ['item' => $r['item'], 'variation' => $r['variation'], 'quantity' => 0.0];
            $agg[$key]['quantity'] += $r['quantity'];
        }

        $menuItems = MenuItem::where('store_id', $store->id)->orderBy('name')->get();
        $bySquare = $menuItems->filter(fn ($m) => filled($m->square_name))->keyBy(fn ($m) => mb_strtolower($m->square_name));
        $byName = $menuItems->keyBy(fn ($m) => mb_strtolower($m->name));

        $rows = [];
        foreach ($agg as $a) {
            $match = $bySquare->get(mb_strtolower($a['item'])) ?? $byName->get(mb_strtolower($a['item']));
            $rows[] = [
                'square_raw_name' => $a['item'],
                'variation' => $a['variation'],
                'quantity' => $a['quantity'],
                'menu_item_id' => $match?->id,
                'size_variant' => $this->normalizeSize($a['variation']),
                'matched' => (bool) $match,
            ];
        }

        if (empty($rows)) {
            return back()->with('error', 'No sellable rows found in that CSV.');
        }

        return view('admin.square-import.preview', [
            'store' => $store,
            'week' => $week,
            'rows' => $rows,
            'menuItems' => $menuItems,
            'sizes' => self::SIZES,
            'matchedCount' => collect($rows)->where('matched', true)->count(),
            'unmatchedCount' => collect($rows)->where('matched', false)->count(),
        ]);
    }

    /**
     * Manual entry, the backup the client asked for when the Square export is
     * not to hand: one row per menu item and size, type what sold.
     *
     * It writes the same `menu_items_sold` rows the CSV wizard does, so the
     * variance engine neither knows nor cares which route the numbers took.
     */
    public function manualForm(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->weekFrom($request);

        $existing = MenuItemSold::where('store_id', $store->id)
            ->whereDate('week_start_date', $week)
            ->get()
            ->keyBy(fn ($row) => $row->menu_item_id.'-'.$row->size_variant);

        return view('admin.square-import.manual', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'sizes' => self::SIZES,
            'menuItems' => MenuItem::where('store_id', $store->id)->orderBy('name')->get(),
            'existing' => $existing,
            'importedFromCsv' => $existing->contains(fn ($row) => filled($row->import_batch_id) && filled($row->square_raw_name)),
        ]);
    }

    public function manualStore(Request $request)
    {
        $store = $this->resolveStore($request);

        $data = $request->validate([
            'week_start_date' => ['required', 'date'],
            'sold' => ['present', 'array'],
            'sold.*' => ['nullable', 'numeric', 'min:0', 'max:999999'],
        ]);

        $week = Carbon::parse($data['week_start_date'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $batch = (string) Str::uuid();
        $menuItems = MenuItem::where('store_id', $store->id)->get()->keyBy('id');

        $written = 0;
        DB::transaction(function () use ($data, $store, $week, $batch, $menuItems, &$written) {
            // Same rule as the CSV wizard: a save replaces that week entirely,
            // so entering it twice cannot double the sales.
            MenuItemSold::where('store_id', $store->id)->whereDate('week_start_date', $week)->delete();

            foreach ($data['sold'] as $key => $qty) {
                if ($qty === null || $qty === '' || (float) $qty <= 0) {
                    continue;
                }

                // Keys arrive as "<menu item id>-<size>".
                [$menuItemId, $size] = array_pad(explode('-', (string) $key, 2), 2, 'regular');
                $menuItem = $menuItems->get((int) $menuItemId);

                if (! $menuItem || ! in_array($size, self::SIZES, true)) {
                    continue;
                }

                MenuItemSold::create([
                    'store_id' => $store->id,
                    'week_start_date' => $week,
                    'menu_item_id' => $menuItem->id,
                    'size_variant' => $size,
                    'square_raw_name' => $menuItem->name.' ('.$size.', entered by hand)',
                    'quantity_sold' => (float) $qty,
                    'import_batch_id' => $batch,
                    'is_matched' => true,
                ]);
                $written++;
            }
        });

        return redirect()
            ->route('admin.square-import.manual', ['store_id' => $store->id, 'week_start_date' => $week])
            ->with('success', $written > 0
                ? "Saved {$written} line(s) of sales for the week of {$week}."
                : "Nothing saved: every quantity was blank or zero, so the week of {$week} now has no sales recorded.");
    }

    /** The Monday of the week being worked on. */
    private function weekFrom(Request $request): string
    {
        return Carbon::parse($request->input('week_start_date', now()))
            ->startOfWeek(Carbon::MONDAY)
            ->toDateString();
    }

    public function commit(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'week_start_date' => ['required', 'date'],
            'square_raw_name' => ['required', 'array', 'min:1'],
            'square_raw_name.*' => ['required', 'string', 'max:191'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric'],
            'menu_item_id' => ['required', 'array'],
            'menu_item_id.*' => ['nullable', 'integer'],
            'size_variant' => ['required', 'array'],
            'size_variant.*' => ['nullable', 'string', 'max:20'],
        ]);

        $week = Carbon::parse($data['week_start_date'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $batch = (string) Str::uuid();
        $storeMenuIds = MenuItem::where('store_id', $store->id)->pluck('id')->all();

        $written = 0;
        DB::transaction(function () use ($data, $store, $week, $batch, $storeMenuIds, &$written) {
            // Replace any prior import for this store/week.
            MenuItemSold::where('store_id', $store->id)->whereDate('week_start_date', $week)->delete();

            foreach ($data['square_raw_name'] as $i => $name) {
                $qty = (float) ($data['quantity'][$i] ?? 0);
                // A menu item only counts if it actually belongs to this store.
                $menuItemId = (int) ($data['menu_item_id'][$i] ?? 0);
                $menuItemId = in_array($menuItemId, $storeMenuIds, true) ? $menuItemId : null;
                $size = in_array($data['size_variant'][$i] ?? '', self::SIZES, true) ? $data['size_variant'][$i] : 'regular';

                MenuItemSold::create([
                    'store_id' => $store->id,
                    'week_start_date' => $week,
                    'menu_item_id' => $menuItemId,
                    'size_variant' => $size,
                    'square_raw_name' => $name,
                    'quantity_sold' => $qty,
                    'import_batch_id' => $batch,
                    'is_matched' => $menuItemId !== null,
                ]);
                $written++;
            }
        });

        return redirect()->route('admin.square-import.form', ['store_id' => $store->id])
            ->with('success', "Imported {$written} line(s) for the week of ".Carbon::parse($week)->format('M j, Y').'.');
    }

    private function normalizeSize(?string $variation): string
    {
        $v = strtolower(trim((string) $variation));
        if ($v === '') {
            return 'regular';
        }
        if (str_contains($v, 'mini') || str_contains($v, 'small') || $v === 'sm' || $v === 's') {
            return 'mini';
        }
        if (str_contains($v, 'large') || str_contains($v, 'lg') || $v === 'l' || str_contains($v, 'xl')) {
            return 'large';
        }

        return 'regular';
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
