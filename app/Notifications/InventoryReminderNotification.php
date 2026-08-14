<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Phase 5.9 — Monday-morning nudge to complete the weekly inventory count.
 */
class InventoryReminderNotification extends Notification
{
    use Queueable;

    public function __construct(private Store $store, private Carbon $week)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Weekly inventory count — '.($this->store->store_info ?? 'your store'))
            ->greeting('Good morning!')
            ->line('It\'s inventory day for '.($this->store->store_info ?? 'your store').' (week of '.$this->week->format('M j, Y').').')
            ->line('Please count and submit before the Monday end-of-day cutoff.')
            ->action('Enter inventory', url(route('inventory.entry.index')));
    }
}
