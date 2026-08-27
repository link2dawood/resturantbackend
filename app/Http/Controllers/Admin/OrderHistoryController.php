<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\OrderTrendService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5 Part 1 Task 12 — order history, trends and quick reorder.
 *
 * Separate from OrderController, which is about the current week's workflow.
 * This one looks backwards: what did we order, how much of it, how often, and
 * can we send that again.
 */
class OrderHistoryController extends Controller
{
    /** Default window when no date range is given. */
    private const DEFAULT_WEEKS = 4;

    public function __construct(private OrderTrendService $trends)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        [$from, $to] = $this->dateRange($request);

        $vendorIds = array_values(array_filter(array_map('intval', (array) $request->input('vendor_ids', []))));
        $statuses = array_values(array_intersect((array) $request->input('statuses', []), Order::STATUSES));

        $query = Order::with(['vendor', 'items.inventoryItem'])
            ->where('store_id', $store->id)
            ->whereDate('week_start_date', '>=', $from->toDateString())
            ->whereDate('week_start_date', '<=', $to->toDateString())
            ->orderByDesc('week_start_date')
            ->orderBy('vendor_id')
            ->orderBy('order_sequence');

        if (! empty($vendorIds)) {
            $query->whereIn('vendor_id', $vendorIds);
        }

        if (! empty($statuses)) {
            $query->whereIn('status', $statuses);
        }

        $orders = $query->get();

        // "Last 4 weeks of Lisanti orders": one block per vendor, weeks inside.
        $byVendor = $orders
            ->groupBy(fn (Order $order) => $order->vendor->vendor_name ?? 'No vendor')
            ->sortKeys();

        return view('admin.orders.history', [
            'store' => $store,
            'stores' => $this->storeOptions(),
            'orders' => $orders,
            'byVendor' => $byVendor,
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'statuses' => Order::STATUSES,
            'selectedVendorIds' => $vendorIds,
            'selectedStatuses' => $statuses,
            'from' => $from,
            'to' => $to,
            'trends' => $this->trends->perItem($orders),
            'summary' => $this->trends->summary($orders),
            'currentWeek' => Carbon::now()->startOfWeek(Carbon::MONDAY),
        ]);
    }

    /**
     * Send a past order again: copy its lines into a fresh draft for the chosen
     * week. Slots into Order 1 if that is free, otherwise Order 2; if both are
     * taken the manager is pointed at the existing draft rather than getting a
     * third order the rest of the system does not expect.
     */
    public function reorder(Request $request, Order $order)
    {
        $this->authorizeStore($order);

        $data = $request->validate([
            'week' => ['nullable', 'date'],
        ]);

        $week = isset($data['week'])
            ? Carbon::parse($data['week'])->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        if ($week->greaterThan(Carbon::now()->startOfWeek(Carbon::MONDAY))) {
            return back()->with('error', 'You cannot build an order for a week that has not started.');
        }

        if ($order->items()->count() === 0) {
            return back()->with('error', 'That order has no lines to copy.');
        }

        $taken = Order::where('store_id', $order->store_id)
            ->where('vendor_id', $order->vendor_id)
            ->forWeek($week->toDateString())
            ->pluck('order_sequence')
            ->map(fn ($n) => (int) $n)
            ->all();

        $sequence = collect([1, 2])->first(fn (int $n) => ! in_array($n, $taken, true));

        if ($sequence === null) {
            return back()->with(
                'error',
                'That vendor already has both orders for the week of '.$week->format('M j, Y').'. Edit one of those instead.'
            );
        }

        $copy = DB::transaction(function () use ($order, $week, $sequence) {
            $copy = Order::create([
                'store_id' => $order->store_id,
                'vendor_id' => $order->vendor_id,
                'week_start_date' => $week->toDateString(),
                'order_sequence' => $sequence,
                'status' => Order::STATUS_DRAFT,
                'notes' => $order->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($order->items()->get() as $line) {
                $copy->items()->create([
                    'inventory_item_id' => $line->inventory_item_id,
                    'quantity' => $line->quantity,
                    // Copied from history, not proposed by the calculator, so
                    // there is no suggestion to have overridden.
                    'suggested_quantity' => null,
                    'is_manual_override' => false,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'notes' => $line->notes,
                ]);
            }

            return $copy;
        });

        return redirect()->route('admin.orders.show', $copy)->with('success', sprintf(
            'Reordered %d line(s) from the week of %s into a new draft (Order %d, week of %s).',
            $copy->items()->count(),
            $order->week_start_date->format('M j, Y'),
            $sequence,
            $week->format('M j, Y')
        ));
    }

    // ---- helpers -----------------------------------------------------------

    /** @return array{0: Carbon, 1: Carbon} */
    private function dateRange(Request $request): array
    {
        $currentWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);

        $to = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->startOfWeek(Carbon::MONDAY)
            : $currentWeek;

        $from = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->startOfWeek(Carbon::MONDAY)
            : $to->copy()->subWeeks(self::DEFAULT_WEEKS - 1);

        // A backwards range returns nothing and looks like a bug, so swap it.
        return $from->greaterThan($to) ? [$to, $from] : [$from, $to];
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

        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())
            ->orderBy('store_info')
            ->get();
    }

    private function authorizeStore(Order $order): void
    {
        abort_unless(in_array($order->store_id, auth()->user()->getAccessibleStoreIds(), true), 403);
    }
}
