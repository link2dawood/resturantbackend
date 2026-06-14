<?php

namespace Tests\Feature;

use App\Mail\BillingMail;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * Phase 4 — Payments / Stripe.
 *
 * Covers the logic that doesn't require a live Stripe API: billing-cycle anchor
 * + proration setup, billing access control, the webhook listener (receipts,
 * dunning, cancellation → lockout), and the admin MRR/churn dashboard.
 */
class PaymentSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $overrides = []): User
    {
        return User::factory()->create(array_merge(['role' => 'owner'], $overrides));
    }

    private function seedSubscription(User $user, string $status, array $overrides = []): Subscription
    {
        return tap(new Subscription)->forceFill(array_merge([
            'user_id' => $user->id,
            'type' => config('subscription.type', 'default'),
            'stripe_id' => 'sub_'.uniqid(),
            'stripe_status' => $status,
            'stripe_price' => 'price_test',
            'quantity' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides))->save() ? Subscription::where('user_id', $user->id)->latest('id')->first() : null;
    }

    /** @test */
    public function billing_cycle_anchors_to_the_first_of_next_month(): void
    {
        $anchor = app(SubscriptionService::class)->nextBillingAnchor(now()->setDate(2026, 6, 12));

        $this->assertSame(1, $anchor->day);
        $this->assertSame(7, $anchor->month); // July
        $this->assertSame(2026, $anchor->year);
    }

    /** @test */
    public function managers_cannot_manage_billing(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        // No Stripe call happens — access is rejected before that.
        $this->actingAs($manager)->get('/billing')->assertRedirect(route('home'));
    }

    /** @test */
    public function payment_succeeded_webhook_sends_receipt_and_keeps_access(): void
    {
        Mail::fake();
        $owner = $this->owner([
            'stripe_id' => 'cus_paid',
            'subscription_status' => User::SUBSCRIPTION_EXPIRED,
            'email' => 'paid@example.com',
        ]);

        event(new WebhookHandled([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => [
                'customer' => 'cus_paid',
                'amount_paid' => 9900,
                'hosted_invoice_url' => 'https://invoice.test/abc',
            ]],
        ]));

        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $owner->fresh()->subscription_status);
        Mail::assertSent(BillingMail::class, fn ($m) => $m->markdownView === 'emails.billing.receipt' && $m->hasTo('paid@example.com'));
    }

    /** @test */
    public function payment_failed_webhook_emails_dunning_without_locking_out(): void
    {
        Mail::fake();
        $owner = $this->owner([
            'stripe_id' => 'cus_fail',
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
            'email' => 'fail@example.com',
        ]);

        event(new WebhookHandled([
            'type' => 'invoice.payment_failed',
            'data' => ['object' => ['customer' => 'cus_fail', 'next_payment_attempt' => now()->addDays(3)->timestamp]],
        ]));

        // Dunning email sent, but access is NOT revoked (Stripe is still retrying).
        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $owner->fresh()->subscription_status);
        Mail::assertSent(BillingMail::class, fn ($m) => $m->markdownView === 'emails.billing.payment-failed' && $m->hasTo('fail@example.com'));
    }

    /** @test */
    public function subscription_deleted_webhook_locks_out_and_emails_cancellation(): void
    {
        Mail::fake();
        $owner = $this->owner([
            'stripe_id' => 'cus_gone',
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
            'email' => 'gone@example.com',
        ]);

        event(new WebhookHandled([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_gone', 'status' => 'canceled']],
        ]));

        $this->assertSame(User::SUBSCRIPTION_EXPIRED, $owner->fresh()->subscription_status);
        Mail::assertSent(BillingMail::class, fn ($m) => $m->markdownView === 'emails.billing.cancelled' && $m->hasTo('gone@example.com'));
    }

    /** @test */
    public function admin_dashboard_computes_mrr_and_churn(): void
    {
        config(['subscription.monthly_amount' => 9900]); // $99

        $admin = User::factory()->create(['role' => 'admin']);

        // 3 active subscriptions, 1 cancelled in the last 30 days.
        $this->seedSubscription($this->owner(), 'active');
        $this->seedSubscription($this->owner(), 'active');
        $this->seedSubscription($this->owner(), 'active');
        // A cancelled subscription in Cashier has ends_at set (in the past once
        // the period lapses), which is how active() excludes it.
        $this->seedSubscription($this->owner(), 'canceled', [
            'ends_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.subscriptions.index'));

        $response->assertOk()
            ->assertViewHas('activeCount', 3)
            ->assertViewHas('mrr', 297.0)       // 3 × $99
            ->assertViewHas('cancelledLast30', 1)
            ->assertViewHas('churnRate', 25.0); // 1 / (3 + 1)
    }
}
