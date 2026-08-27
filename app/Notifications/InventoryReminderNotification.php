<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Phase 5.9 — Monday-morning nudge to complete the weekly inventory count.
 */
class InventoryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Store $store, private Carbon $week)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Time to enter this week\'s inventory',
            'body' => ($this->store->store_info ?? 'Your store').' — week of '.$this->week->format('M j, Y'),
            'url' => route('inventory.weekly-count.index', [
                'store_id' => $this->store->id,
                'week' => $this->week->toDateString(),
            ]),
            'icon' => 'clipboard',
            'store_id' => $this->store->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Time to enter this week\'s inventory — '.($this->store->store_info ?? 'your store'))
            ->greeting('Good morning!')
            ->line('It\'s inventory day for '.($this->store->store_info ?? 'your store').' (week of '.$this->week->format('M j, Y').').')
            ->line('Count what is on hand for each item, then submit. The page saves a draft as you go, so you can stop and come back.')
            ->action('Enter this week\'s count', url(route('inventory.weekly-count.index', [
                'store_id' => $this->store->id,
                'week' => $this->week->toDateString(),
            ])));
    }
}
