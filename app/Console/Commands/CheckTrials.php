<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\TrialMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckTrials extends Command
{
    protected $signature = 'trials:check';

    protected $description = 'Convert lapsed trials with a card on file, and send "expiring soon"/"expired" notifications';

    public function __construct(private SubscriptionService $subscriptions)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expiringSoon = $this->notifyExpiringSoon();
        [$converted, $expired] = $this->processLapsedTrials();

        $this->info("Trial check complete: {$expiringSoon} expiring-soon reminder(s), {$converted} auto-converted to paid, {$expired} expiry notification(s).");

        return self::SUCCESS;
    }

    /**
     * Owners within the reminder window whose client hasn't been reminded yet.
     */
    protected function notifyExpiringSoon(): int
    {
        $windowDays = (int) config('trial.expiring_soon_days', 3);
        $count = 0;

        User::query()
            ->where('subscription_status', User::SUBSCRIPTION_TRIALING)
            ->whereNull('trial_expiring_notified_at')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($windowDays)])
            ->each(function (User $owner) use (&$count) {
                TrialMailer::expiringSoon($owner);
                $owner->trial_expiring_notified_at = now();
                $owner->save();
                $count++;
            });

        return $count;
    }

    /**
     * Owners whose trial has lapsed. Trial-to-paid conversion: if they have a
     * card on file, convert them to a paid subscription (prorated, anchored to
     * the 1st). Otherwise flip them to 'expired' and send the expiry emails.
     *
     * @return array{0:int,1:int} [convertedCount, expiredCount]
     */
    protected function processLapsedTrials(): array
    {
        $converted = 0;
        $expired = 0;

        User::query()
            ->where('subscription_status', User::SUBSCRIPTION_TRIALING)
            ->whereNull('trial_expired_notified_at')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->each(function (User $owner) use (&$converted, &$expired) {
                // Trial-to-paid: convert if a card is on file.
                if ($this->subscriptions->canAutoConvert($owner)) {
                    try {
                        $this->subscriptions->convert($owner);
                        $converted++;

                        return; // billing webhooks (payment_succeeded) take over from here
                    } catch (\Throwable $e) {
                        Log::error('Trial auto-conversion failed; falling back to expiry', [
                            'owner_id' => $owner->id,
                            'error' => $e->getMessage(),
                        ]);
                        // fall through to expiry so they aren't stuck in limbo
                    }
                }

                TrialMailer::expired($owner);
                $owner->subscription_status = User::SUBSCRIPTION_EXPIRED;
                $owner->trial_expired_notified_at = now();
                $owner->save();
                $expired++;
            });

        return [$converted, $expired];
    }
}
