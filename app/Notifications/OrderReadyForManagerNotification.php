<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\OrderPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Phase 5 Part 1.5 — the owner approves the order, the manager places it.
 *
 * The client asked for the order to reach the manager as a PDF rather than as a
 * block of text to copy, and for it to say how this particular vendor takes
 * orders, because Lisanti, Restaurant Depot and Coca-Cola are each different.
 */
class OrderReadyForManagerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Order $order,
        private ?string $approvedBy = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;

        $mail = (new MailMessage)
            ->subject($this->subject())
            ->greeting('Hi '.($notifiable->name ?? 'there').',')
            ->line($this->sentence())
            ->line('**How to place it:** '.($order->vendor->order_instruction ?? 'Contact the vendor.'))
            ->line('Week of '.$order->week_start_date->format('M j, Y').', '
                .$order->items->count().' item(s)'
                .($order->total > 0 ? ', $'.number_format($order->total, 2) : '').'.')
            ->action('Open the order', url(route('admin.orders.show', $order)))
            ->line('The order sheet is attached. Check the delivery in against it when it arrives.');

        // Attaching can fail on a bad template or a memory ceiling. The manager
        // still needs the email, so a missing attachment must not lose it.
        try {
            $mail->attachData(OrderPdf::render($order), OrderPdf::filename($order), [
                'mime' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            report($e);
            $mail->line('The PDF could not be attached. Open the order and use Print.');
        }

        return $mail;
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'body' => $this->sentence().' '.($this->order->vendor->order_instruction ?? ''),
            'url' => route('admin.orders.report.pdf', $this->order),
            'icon' => 'file-text',
            'order_id' => $this->order->id,
            'store_id' => $this->order->store_id,
        ];
    }

    private function subject(): string
    {
        $vendorName = $this->order->vendor->vendor_name ?? 'a vendor';

        return "Place this order with {$vendorName}";
    }

    private function sentence(): string
    {
        $who = $this->approvedBy ?? 'The owner';
        $vendorName = $this->order->vendor->vendor_name ?? 'a vendor';
        $storeName = $this->order->store->store_info ?? 'your store';

        return "{$who} approved a {$vendorName} order for {$storeName}.";
    }
}
