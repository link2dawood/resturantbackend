<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Store;
use App\Models\User;
use App\Notifications\InventoryOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

/**
 * Phase 5 Part 1 Task 14 — the Wednesday chase. Scheduled Wednesdays 08:00
 * (routes/console.php).
 *
 * Only stores whose week is NOT yet submitted are contacted, so a manager who
 * did their job on Monday hears nothing. Nagging people who already complied is
 * the fastest way to get an alert ignored.
 */
class SendOverdueInventoryReminder extends Command
{
    protected $signature = 'inventory:remind-overdue {--week= : Monday date (Y-m-d); defaults to this week}';

    protected $description = 'Chase stores whose weekly inventory count is still not submitted';

    public function handle(): int
    {
        $week = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);

        $storeIds = InventoryItem::where('is_active', true)->distinct()->pluck('store_id');
        $sent = 0;
        $skipped = 0;

        foreach (Store::whereIn('id', $storeIds)->get() as $store) {
            $rows = InventoryStock::where('store_id', $store->id)
                ->forWeek($week->toDateString())
                ->get();

            if ($rows->contains(fn ($row) => $row->status === InventoryStock::STATUS_SUBMITTED)) {
                $skipped++;

                continue;
            }

            $recipients = User::whereIn('role', ['employee', 'manager'])
                ->where('store_id', $store->id)
                ->get();

            if ($recipients->isEmpty()) {
                continue;
            }

            $total = $rows->count() ?: InventoryItem::where('store_id', $store->id)->where('is_active', true)->count();
            $counted = $rows->filter(fn ($row) => $row->counted_at !== null)->count();

            Notification::send($recipients, new InventoryOverdueNotification($store, $week, $counted, $total));
            $sent += $recipients->count();
        }

        $this->info("Sent {$sent} overdue reminder(s); {$skipped} store(s) had already submitted.");

        return self::SUCCESS;
    }
}
