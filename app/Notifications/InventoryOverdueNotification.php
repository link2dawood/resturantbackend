<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Phase 5 Part 1 Task 14 — the Wednesday chase, sent only when the week's count
 * still has not been submitted. Separate class from the Monday nudge so the
 * wording can be firmer and the two can be told apart in the bell.
 */
class InventoryOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Store $store,
        private Carbon $week,
        private int $countedItems,
        private int $totalItems,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $storeName = $this->store->store_info ?? 'your store';

        $mail = (new MailMessage)
            ->subject('Still waiting on this week\'s inventory — '.$storeName)
            ->greeting('Hi,')
            ->line('The inventory count for '.$storeName.' (week of '.$this->week->format('M j, Y').') has not been submitted yet.');

        $mail = $this->countedItems > 0
            ? $mail->line("You have counted {$this->countedItems} of {$this->totalItems} items. Your draft is saved, so you can pick up where you left off.")
            : $mail->line("None of the {$this->totalItems} items have been counted yet.");

        return $mail
            ->line('Orders for the week are built from this count, so the sooner it is in, the sooner the order can go out.')
            ->action('Finish the count', url(route('inventory.weekly-count.index', [
                'store_id' => $this->store->id,
                'week' => $this->week->toDateString(),
            ])));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'This week\'s inventory is still outstanding',
            'body' => $this->countedItems > 0
                ? "{$this->countedItems} of {$this->totalItems} items counted for the week of ".$this->week->format('M j')
                : "None of the {$this->totalItems} items counted for the week of ".$this->week->format('M j'),
            'url' => route('inventory.weekly-count.index', [
                'store_id' => $this->store->id,
                'week' => $this->week->toDateString(),
            ]),
            'icon' => 'alert',
            'store_id' => $this->store->id,
        ];
    }
}
