<?php

namespace App\Console\Commands;

use App\Models\InventoryItem;
use App\Models\Store;
use App\Models\User;
use App\Notifications\LargeVarianceAlertNotification;
use App\Services\Inventory\VarianceReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Phase 5.9 — persist each store's variance snapshot for the just-completed week
 * and alert management about large (red) variances. Scheduled Tuesdays 02:00
 * (routes/console.php).
 */
class GenerateWeeklyVariance extends Command
{
    protected $signature = 'inventory:generate-variance {--week= : Monday date (Y-m-d); defaults to last week}';

    protected $description = 'Persist the weekly variance snapshot and alert on large variances';

    public function handle(VarianceReportService $service): int
    {
        $week = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY)->subWeek();

        $alertOn = (array) config('inventory.variance.alert_on', ['red']);
        $storeIds = InventoryItem::where('is_active', true)->distinct()->pluck('store_id');

        $reports = 0;
        $alerts = 0;
        foreach (Store::whereIn('id', $storeIds)->get() as $store) {
            $report = $service->persist($store->id, $week);
            $reports++;

            $flagged = $report->lines()->whereIn('severity', $alertOn)->with('inventoryItem')->get();
            if ($flagged->isEmpty()) {
                continue;
            }

            $recipients = $this->management($store);
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new LargeVarianceAlertNotification($store, $week, $flagged));
                $alerts++;
            }
        }

        $this->info("Generated {$reports} variance report(s); {$alerts} alert(s) sent.");

        return self::SUCCESS;
    }

    /** Managers of the store plus its controlling owner. */
    private function management(Store $store): Collection
    {
        $recipients = User::where('role', 'manager')->where('store_id', $store->id)->get();
        $owner = $store->controllingOwner();

        return $owner ? $recipients->push($owner)->unique('id')->values() : $recipients;
    }
}
