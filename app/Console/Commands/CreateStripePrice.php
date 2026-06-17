<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;

/**
 * Phase 4 — Payments helper.
 *
 * Creates a recurring monthly Stripe Price (and its Product) for the
 * subscription plan, using STRIPE_SECRET + the values in config/subscription.php,
 * and prints the price id to drop into STRIPE_PRICE_ID. Saves a trip to the
 * Stripe dashboard.
 */
class CreateStripePrice extends Command
{
    protected $signature = 'stripe:create-price {--amount= : Amount in cents (default: SUBSCRIPTION_MONTHLY_AMOUNT)} {--name= : Plan name (default: SUBSCRIPTION_PLAN_NAME)}';

    protected $description = 'Create a recurring monthly Stripe price for the subscription plan and print its ID';

    public function handle(): int
    {
        $secret = config('cashier.secret');

        if (! $secret) {
            $this->error('STRIPE_SECRET is not set in .env. Set it (and run config:clear) first.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: config('subscription.plan_name', 'Pro');
        $amount = (int) ($this->option('amount') ?: config('subscription.monthly_amount', 9900));
        $currency = strtolower(config('cashier.currency', 'usd'));
        $mode = str_starts_with($secret, 'sk_live_') ? 'LIVE' : 'TEST';

        $this->info("Creating a {$mode}-mode price: \"{$name}\" — ".number_format($amount / 100, 2)." {$currency}/month");

        try {
            $price = (new StripeClient($secret))->prices->create([
                'unit_amount' => $amount,
                'currency' => $currency,
                'recurring' => ['interval' => 'month'],
                'product_data' => ['name' => $name],
            ]);
        } catch (\Throwable $e) {
            $this->error('Stripe error: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✓ Created price: '.$price->id);
        $this->newLine();
        $this->line('Add this to your .env and run: php artisan config:clear');
        $this->line('  STRIPE_PRICE_ID='.$price->id);
        $this->newLine();
        $this->warn("Created in {$mode} mode (matches your STRIPE_SECRET). Running this again makes a NEW price — use the latest id.");

        return self::SUCCESS;
    }
}
