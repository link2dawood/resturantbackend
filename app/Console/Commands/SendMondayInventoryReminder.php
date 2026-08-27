<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Notifications\InventoryReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Phase 5.9 — Monday reminder to complete the weekly inventory count. Scheduled
 * Mondays 07:00 (routes/console.php). Notifies each store's employees + managers.
 */
class SendMondayInventoryReminder extends Command
{
    protected $signature = 'inventory:remind {--week= : Monday date (Y-m-d); defaults to this week}';

    protected $description = 'Email employees/managers to complete the weekly inventory count';

    public function handle(): int
    {
        $week = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $storeIds = InventoryItem::where('is_active', true)->distinct()->pluck('store_id');

        $sent = 0;
        foreach (Store::whereIn('id', $storeIds)->get() as $store) {
            $recipients = User::whereIn('role', ['employee', 'manager'])
                ->where('store_id', $store->id)->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            Notification::send($recipients, new InventoryReminderNotification($store, $week));
            $sent += $recipients->count();
        }

        $this->info("Sent {$sent} inventory reminder(s).");

        return self::SUCCESS;
    }
}
