<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Phase 5.9 — alert to management when the weekly variance run finds large
 * (red) variances.
 */
class LargeVarianceAlertNotification extends Notification
{
    use Queueable;

    /** @param Collection<int, \App\Models\VarianceReportLine> $lines */
    public function __construct(private Store $store, private Carbon $week, private Collection $lines)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->lines->count().' large variance(s) — '.($this->store->store_info ?? 'store'))
            ->line(($this->store->store_info ?? 'A store').' has '.$this->lines->count().' item(s) with a large variance for the week of '.$this->week->format('M j, Y').':');

        foreach ($this->lines->take(15) as $line) {
            $mail->line('• '.($line->inventoryItem->name ?? 'Item').': '.rtrim(rtrim(number_format((float) $line->variance, 4, '.', ''), '0'), '.').' '.$line->base_unit.' ('.number_format((float) $line->variance_pct, 1).'%)');
        }

        return $mail->action('Open variance report', url(route('admin.variance.index', ['store_id' => $this->store->id, 'week_start_date' => $this->week->toDateString()])));
    }
}
