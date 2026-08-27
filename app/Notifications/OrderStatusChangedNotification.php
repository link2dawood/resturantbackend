<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 5 Part 1 Task 14 — tells management when a manager places or receives an
 * order. One class for both events rather than two near-identical ones; the
 * event is a constructor argument.
 */
class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EVENT_PLACED = 'placed';

    public const EVENT_RECEIVED = 'received';

    public function __construct(
        private Order $order,
        private string $event,
        private ?string $actorName = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->sentence())
            ->line('Week of '.$this->order->week_start_date->format('M j, Y').', Order '.$this->order->order_sequence.'.')
            ->action('View the order', url(route('admin.orders.show', $this->order)));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'body' => $this->sentence(),
            'url' => route('admin.orders.show', $this->order),
            'icon' => $this->event === self::EVENT_PLACED ? 'cart' : 'truck',
            'order_id' => $this->order->id,
            'store_id' => $this->order->store_id,
        ];
    }

    private function subject(): string
    {
        $storeName = $this->order->store->store_info ?? 'A store';
        $vendorName = $this->order->vendor->vendor_name ?? 'a vendor';

        return $this->event === self::EVENT_PLACED
            ? "Order placed with {$vendorName} — {$storeName}"
            : "Order received from {$vendorName} — {$storeName}";
    }

    private function sentence(): string
    {
        $who = $this->actorName ?? 'A manager';
        $storeName = $this->order->store->store_info ?? 'a store';
        $vendorName = $this->order->vendor->vendor_name ?? 'a vendor';
        $total = $this->order->total > 0 ? ' for $'.number_format($this->order->total, 2) : '';

        return $this->event === self::EVENT_PLACED
            ? "{$who} placed an order with {$vendorName}{$total} at {$storeName}."
            : "{$who} marked the {$vendorName} order{$total} received at {$storeName}.";
    }
}
