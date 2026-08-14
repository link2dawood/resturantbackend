<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\StockUpService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5.6 — multi-vendor weekly ordering. Build a combined order list (one row
 * per item, with its vendor), then generate one order per vendor for the week.
 * Two orders per week are supported (order_sequence 1|2). Orders move
 * draft → placed → received. Admin/owner/manager, store-scoped.
 */
class OrderController extends Controller
{
    public function __construct(private StockUpService $stockUp)
    {
    }

    /** Combined order-build worksheet. */
    public function build(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->week($request);
        $sequence = in_array((int) $request->input('order_sequence'), [1, 2], true) ? (int) $request->input('order_sequence') : 1;
        $projectedDollars = max(0.0, (float) $request->input('projected_dollars', 0));

        $items = InventoryItem::with('preferredVendor')
            ->where('store_id', $store->id)->where('is_active', true)
            ->orderBy('category')->orderBy('name')->get();

        // Suggested quantities from the stock-up engine (keyed by item id).
        $suggested = collect($this->stockUp->suggest($store->id, $week, $projectedDollars))
            ->keyBy(fn ($s) => $s['item']->id);

        return view('admin.orders.build', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'sequence' => $sequence,
            'projectedDollars' => $projectedDollars,
            'items' => $items,
            'vendors' => Vendor::where('is_active', true)->orderBy('vendor_name')->get(),
            'suggested' => $suggested,
        ]);
    }

    /** Create one order per vendor from the build form. */
    public function generate(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'week_start_date' => ['required', 'date'],
            'order_sequence' => ['required', 'in:1,2'],
            'item_id' => ['required', 'array'],
            'item_id.*' => ['nullable', 'integer'],
            'vendor_id' => ['required', 'array'],
            'vendor_id.*' => ['nullable', 'integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'array'],
            'unit.*' => ['nullable', 'string', 'max:20'],
        ]);

        $week = Carbon::parse($data['week_start_date'])->startOfWeek(Carbon::MONDAY);
        $sequence = (int) $data['order_sequence'];
        $storeItemIds = InventoryItem::where('store_id', $store->id)->pluck('id')->all();
        $activeVendorIds = Vendor::where('is_active', true)->pluck('id')->all();

        // Group order lines by vendor.
        $byVendor = [];
        foreach ($data['item_id'] as $i => $itemId) {
            $qty = (float) ($data['quantity'][$i] ?? 0);
            $vendorId = (int) ($data['vendor_id'][$i] ?? 0);
            if ($qty <= 0 || ! in_array((int) $itemId, $storeItemIds, true) || ! in_array($vendorId, $activeVendorIds, true)) {
                continue;
            }
            $byVendor[$vendorId][] = [
                'inventory_item_id' => (int) $itemId,
                'quantity' => $qty,
                'unit' => $data['unit'][$i] ?? null,
            ];
        }

        if (empty($byVendor)) {
            return back()->withInput()->with('error', 'Set a quantity and vendor on at least one item.');
        }

        $orderCount = 0;
        DB::transaction(function () use ($store, $week, $sequence, $byVendor, &$orderCount) {
            // Rebuild: drop existing DRAFT orders for this store/week/sequence (placed/received kept).
            Order::where('store_id', $store->id)
                ->whereDate('week_start_date', $week->toDateString())
                ->where('order_sequence', $sequence)
                ->where('status', 'draft')
                ->delete(); // order_items cascade

            foreach ($byVendor as $vendorId => $lines) {
                $order = Order::create([
                    'store_id' => $store->id,
                    'vendor_id' => $vendorId,
                    'week_start_date' => $week->toDateString(),
                    'order_sequence' => $sequence,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
                foreach ($lines as $line) {
                    $order->items()->create($line);
                }
                $orderCount++;
            }
        });

        return redirect()->route('admin.orders.index', ['store_id' => $store->id, 'week_start_date' => $week->toDateString()])
            ->with('success', "Generated {$orderCount} order(s) for week of ".$week->format('M j, Y').'.');
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $week = $this->week($request);

        $query = Order::with(['vendor', 'items'])
            ->where('store_id', $store->id)
            ->whereDate('week_start_date', $week->toDateString())
            ->orderBy('order_sequence')->orderBy('vendor_id');

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->input('vendor_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Per-vendor history (across weeks) when a vendor is selected.
        $history = collect();
        if ($request->filled('vendor_id')) {
            $history = Order::with('vendor')->where('store_id', $store->id)
                ->where('vendor_id', (int) $request->input('vendor_id'))
                ->orderByDesc('week_start_date')->orderBy('order_sequence')->limit(20)->get();
        }

        return view('admin.orders.index', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'week' => $week,
            'orders' => $query->get(),
            'vendors' => Vendor::where('is_active', true)->orderBy('vendor_name')->get(),
            'history' => $history,
            'selectedVendorId' => (int) $request->input('vendor_id'),
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeStore($order);

        return view('admin.orders.show', ['order' => $order->load(['vendor', 'store', 'items.inventoryItem'])]);
    }

    public function markPlaced(Order $order)
    {
        $this->authorizeStore($order);
        $order->update(['status' => 'placed', 'placed_at' => now()]);

        return back()->with('success', 'Order marked as placed.');
    }

    public function markReceived(Order $order)
    {
        $this->authorizeStore($order);
        $order->update(['status' => 'received', 'received_at' => now()]);

        return back()->with('success', 'Order marked as received.');
    }

    public function destroy(Order $order)
    {
        $this->authorizeStore($order);
        $order->delete();

        return back()->with('success', 'Order deleted.');
    }

    // ── helpers ──────────────────────────────────────────────────────────────

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

    private function authorizeStore(Order $order): void
    {
        abort_unless(in_array($order->store_id, auth()->user()->getAccessibleStoreIds(), true), 403);
    }
}
