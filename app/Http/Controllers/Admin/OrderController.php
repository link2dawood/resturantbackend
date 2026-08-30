<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\Inventory\StockUpService;
use App\Notifications\OrderStatusChangedNotification;
use App\Support\VendorOrderText;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
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

        // A separate flag, not a magic value in week_start_date, so the date
        // input and the toggle cannot collide on the same query parameter.
        $allWeeks = $request->boolean('all_weeks');

        $query = Order::with(['vendor', 'items'])
            ->where('store_id', $store->id)
            ->orderByDesc('week_start_date')
            ->orderBy('order_sequence')->orderBy('vendor_id');

        if (! $allWeeks) {
            $query->forWeek($week->toDateString());
        }

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
            'allWeeks' => $allWeeks,
            'orders' => $query->get(),
            'vendors' => Vendor::where('is_active', true)->orderBy('vendor_name')->get(),
            'statuses' => Order::STATUSES,
            'history' => $history,
            'selectedVendorId' => (int) $request->input('vendor_id'),
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeStore($order);

        return view('admin.orders.show', [
            'order' => $order->load(['vendor', 'store', 'items.inventoryItem']),
            'vendors' => Vendor::where('is_active', true)->orderBy('vendor_name')->get(),
        ]);
    }

    /**
     * The vendor-facing report: what actually gets printed, pasted into
     * WhatsApp, or emailed. Readable at any status, because a placed order is
     * exactly the thing you most want to re-send.
     */
    public function report(Order $order)
    {
        $this->authorizeStore($order);

        $order->load(['vendor', 'store', 'items.inventoryItem']);

        return view('admin.orders.report', [
            'order' => $order,
            'plainText' => VendorOrderText::build($order),
            'subject' => VendorOrderText::subject($order),
        ]);
    }

    /** The same report as a PDF, using the DomPDF setup the other exports use. */
    public function reportPdf(Order $order)
    {
        $this->authorizeStore($order);

        $order->load(['vendor', 'store', 'items.inventoryItem']);

        $html = view('admin.orders.report-pdf', ['order' => $order])->render();

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $filename = sprintf(
            'order-%s-%s-%d.pdf',
            str($order->vendor->vendor_name ?? 'vendor')->slug(),
            $order->week_start_date->toDateString(),
            $order->order_sequence
        );

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$filename}\"",
        ]);
    }

    public function markPlaced(Order $order)
    {
        return $this->transition($order, Order::STATUS_PLACED, [
            'placed_at' => now(),
        ], 'Order marked as placed. The lines are now locked.', OrderStatusChangedNotification::EVENT_PLACED);
    }

    /** The check-in screen: what was ordered against what turned up. */
    public function receiveForm(Order $order)
    {
        $this->authorizeStore($order);

        if (! $order->canTransitionTo(Order::STATUS_RECEIVED) && $order->status !== Order::STATUS_RECEIVED) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'An order that is '.$order->status.' cannot be checked in.');
        }

        return view('admin.orders.receive', [
            'order' => $order->load(['vendor', 'store', 'items.inventoryItem']),
        ]);
    }

    /**
     * Record the delivery line by line.
     *
     * The client had a vendor ship more than was ordered and nobody caught it,
     * so what arrived is stored against what was asked for and any difference is
     * reported rather than quietly accepted.
     */
    public function markReceived(Request $request, Order $order)
    {
        $this->authorizeStore($order);

        if (! $order->canTransitionTo(Order::STATUS_RECEIVED)) {
            return back()->with('error', "An order that is {$order->status} cannot be marked received.");
        }

        $data = $request->validate([
            // present, not required: an order with no lines can still be closed
            // off, but a caller must send the field consciously rather than
            // falling back to the old one-click "all arrived" behaviour.
            'received' => ['present', 'array'],
            'received.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'received_notes' => ['nullable', 'array'],
            'received_notes.*' => ['nullable', 'string', 'max:255'],
        ]);

        $unchecked = 0;

        DB::transaction(function () use ($order, $data, &$unchecked) {
            foreach ($order->items()->get() as $line) {
                $entered = $data['received'][$line->id] ?? null;

                // Blank means nobody checked this line, which is not the same as
                // nothing arriving. Leave it null and say so.
                if (! filled($entered)) {
                    $unchecked++;
                    $line->received_notes = $data['received_notes'][$line->id] ?? null;
                    $line->save();

                    continue;
                }

                $line->quantity_received = (float) $entered;
                $line->received_notes = $data['received_notes'][$line->id] ?? null;
                $line->save();
            }

            $order->update([
                'status' => Order::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by' => auth()->id(),
            ]);
        });

        $this->notifyManagement($order, OrderStatusChangedNotification::EVENT_RECEIVED);

        $order->refresh()->load('items.inventoryItem');
        $discrepancies = $order->discrepancies;

        $message = 'Delivery checked in.';

        if ($discrepancies->isNotEmpty()) {
            $message .= ' '.$discrepancies->count().' line(s) did not match the order';

            if (abs($order->discrepancy_value) >= 0.01) {
                $message .= ' ('.($order->discrepancy_value > 0 ? '+' : '')
                    .'$'.number_format($order->discrepancy_value, 2).')';
            }

            $message .= '.';
        }

        if ($unchecked > 0) {
            $message .= " {$unchecked} line(s) were left unchecked.";
        }

        return redirect()->route('admin.orders.show', $order)
            ->with($discrepancies->isNotEmpty() ? 'error' : 'success', $message);
    }

    public function cancel(Order $order)
    {
        return $this->transition($order, Order::STATUS_CANCELLED, [], 'Order cancelled.');
    }

    /**
     * Move an order along its lifecycle, refusing any jump the state machine
     * does not allow (a received order going back to draft, for instance).
     */
    private function transition(Order $order, string $status, array $extra, string $message, ?string $notifyEvent = null)
    {
        $this->authorizeStore($order);

        if (! $order->canTransitionTo($status)) {
            return back()->with(
                'error',
                "An order that is {$order->status} cannot be marked {$status}."
            );
        }

        $order->update(['status' => $status] + $extra);

        if ($notifyEvent !== null) {
            $this->notifyManagement($order, $notifyEvent);
        }

        return back()->with('success', $message);
    }

    /**
     * Tell admins (and the franchisor) that an order moved. Notifying fails
     * soft: a mail outage must not roll back a status the manager already
     * committed to with the vendor.
     */
    private function notifyManagement(Order $order, string $event): void
    {
        $recipients = User::where('role', 'admin')
            ->orWhere(fn ($q) => $q->where('role', 'owner')->whereRaw('LOWER(name) = ?', ['franchisor']))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new OrderStatusChangedNotification(
                $order->load(['vendor', 'store', 'items']),
                $event,
                auth()->user()?->name
            ));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Edit a draft order's lines: quantity, price, note, or move a line to a
     * different vendor. Moving a line means moving it to that vendor's order for
     * the same week and sequence, creating it if there is not one yet, because an
     * order belongs to exactly one vendor.
     */
    public function updateItems(Request $request, Order $order)
    {
        $this->authorizeStore($order);

        if ($order->isLocked()) {
            return back()->with('error', 'This order is '.$order->status.' and can no longer be edited.');
        }

        $data = $request->validate([
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'unit_price' => ['nullable', 'array'],
            'unit_price.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'line_notes' => ['nullable', 'array'],
            'line_notes.*' => ['nullable', 'string', 'max:255'],
            'move_to_vendor' => ['nullable', 'array'],
            'move_to_vendor.*' => ['nullable', 'integer', 'exists:vendors,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $moved = 0;
        $removed = 0;

        DB::transaction(function () use ($order, $data, &$moved, &$removed) {
            $order->update(['notes' => $data['notes'] ?? null]);

            foreach ($order->items()->get() as $line) {
                $quantity = $data['quantity'][$line->id] ?? null;

                // A quantity cleared to zero means the line is off the order.
                if (! filled($quantity) || (float) $quantity <= 0) {
                    $line->delete();
                    $removed++;

                    continue;
                }

                $line->fill([
                    'quantity' => (float) $quantity,
                    'unit_price' => filled($data['unit_price'][$line->id] ?? null)
                        ? (float) $data['unit_price'][$line->id]
                        : null,
                    'notes' => $data['line_notes'][$line->id] ?? null,
                ]);

                if ($line->suggested_quantity !== null) {
                    $line->is_manual_override =
                        abs((float) $line->quantity - (float) $line->suggested_quantity) >= 0.0001;
                }

                $targetVendorId = (int) ($data['move_to_vendor'][$line->id] ?? 0);

                if ($targetVendorId && $targetVendorId !== (int) $order->vendor_id) {
                    $line->order_id = $this->draftOrderFor($order, $targetVendorId)->id;
                    $moved++;
                }

                $line->save();
            }

            // An order with nothing left on it is not an order.
            if ($order->items()->count() === 0) {
                $order->delete();
            }
        });

        $message = 'Order updated.';
        if ($moved > 0) {
            $message .= " {$moved} line(s) moved to another vendor.";
        }
        if ($removed > 0) {
            $message .= " {$removed} line(s) removed.";
        }

        return $order->exists && Order::find($order->id)
            ? back()->with('success', $message)
            : redirect()->route('admin.orders.index', ['store_id' => $order->store_id])
                ->with('success', $message.' The order is now empty and was removed.');
    }

    /**
     * "Duplicate for Order 2" — a second delivery in the same week. Copies the
     * lines across at the same quantities so the manager edits down rather than
     * rebuilding from scratch.
     */
    public function duplicateForSecondOrder(Order $order)
    {
        $this->authorizeStore($order);

        if ((int) $order->order_sequence !== 1) {
            return back()->with('error', 'Only the first order of a week can be duplicated into Order 2.');
        }

        $existing = Order::where('store_id', $order->store_id)
            ->forWeek($order->week_start_date->toDateString())
            ->where('vendor_id', $order->vendor_id)
            ->where('order_sequence', 2)
            ->first();

        if ($existing) {
            return redirect()->route('admin.orders.show', $existing)
                ->with('error', 'An Order 2 already exists for this vendor and week.');
        }

        $copy = DB::transaction(function () use ($order) {
            $copy = Order::create([
                'store_id' => $order->store_id,
                'vendor_id' => $order->vendor_id,
                'week_start_date' => $order->week_start_date->toDateString(),
                'order_sequence' => 2,
                'status' => Order::STATUS_DRAFT,
                'notes' => $order->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($order->items()->get() as $line) {
                $copy->items()->create([
                    'inventory_item_id' => $line->inventory_item_id,
                    'quantity' => $line->quantity,
                    // The suggestion belonged to Order 1's count, so it does not
                    // carry over; this copy is a manual decision from the start.
                    'suggested_quantity' => null,
                    'is_manual_override' => false,
                    'unit' => $line->unit,
                    'unit_price' => $line->unit_price,
                    'notes' => $line->notes,
                ]);
            }

            return $copy;
        });

        return redirect()->route('admin.orders.show', $copy)
            ->with('success', 'Order 2 created as a draft. Adjust the quantities before placing it.');
    }

    /**
     * The draft order for a vendor in this order's week and sequence, created if
     * needed.
     *
     * The week lookup uses whereDate, not an exact match: week_start_date is a
     * date-cast column, so it is stored with a time component and an exact
     * string comparison silently misses, which would open a duplicate order for
     * a vendor that already has one.
     */
    private function draftOrderFor(Order $order, int $vendorId): Order
    {
        $existing = Order::where('store_id', $order->store_id)
            ->where('vendor_id', $vendorId)
            ->forWeek($order->week_start_date->toDateString())
            ->where('order_sequence', $order->order_sequence)
            ->where('status', Order::STATUS_DRAFT)
            ->first();

        return $existing ?? Order::create([
            'store_id' => $order->store_id,
            'vendor_id' => $vendorId,
            'week_start_date' => $order->week_start_date->toDateString(),
            'order_sequence' => $order->order_sequence,
            'status' => Order::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);
    }

    public function destroy(Order $order)
    {
        $this->authorizeStore($order);

        if ($order->status === Order::STATUS_PLACED || $order->status === Order::STATUS_RECEIVED) {
            return back()->with('error', 'A '.$order->status.' order is history. Cancel it instead of deleting it.');
        }

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
