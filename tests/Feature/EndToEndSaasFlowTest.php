<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\Concerns\SignsUpOwners;
use Tests\TestCase;

/**
 * Phase 4 — QA: end-to-end SaaS lifecycle.
 *
 * Walks the full state machine without a live Stripe call:
 *   sign up → (unverified) → verify → trial active → trial expires → locked out
 *   → paid (subscription webhook) → access restored → renewal payment receipt.
 */
class EndToEndSaasFlowTest extends TestCase
{
    use RefreshDatabase;
    use SignsUpOwners;

    /** @test */
    public function full_signup_trial_paid_renewal_lifecycle(): void
    {
        Mail::fake();

        // 1. Sign up (self-serve) → Owner, unverified, on trial.
        $this->post('/register', $this->ownerSignupPayload([
            'name' => 'Lifecycle Owner',
            'email' => 'life@example.com',
        ]));

        $owner = User::where('email', 'life@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertTrue($owner->isOwner());
        $this->assertSame(User::SUBSCRIPTION_TRIALING, $owner->subscription_status);
        $this->assertNull($owner->email_verified_at);

        // 2. Unverified → blocked from the app.
        $this->actingAs($owner)->get('/profile')->assertRedirect(route('verification.notice'));

        // 3. Verify email → trial access granted.
        $owner->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($owner->fresh())->get('/profile')->assertOk();

        // 4. Trial expires → locked out to the Trial Expired screen.
        $owner->forceFill(['trial_ends_at' => now()->subDay()])->save();
        $this->actingAs($owner->fresh())->get('/profile')->assertRedirect(route('trial.expired'));

        // 5. Pays → Stripe sends a subscription/payment webhook → access restored.
        $owner->forceFill(['stripe_id' => 'cus_life'])->save();
        event(new WebhookHandled([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['customer' => 'cus_life', 'amount_paid' => 9900]],
        ]));

        $owner->refresh();
        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $owner->subscription_status);
        $this->actingAs($owner)->get('/profile')->assertOk();

        // 6. Monthly renewal payment → another succeeded webhook keeps access.
        event(new WebhookHandled([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['customer' => 'cus_life', 'amount_paid' => 9900]],
        ]));
        $this->assertSame(User::SUBSCRIPTION_ACTIVE, $owner->fresh()->subscription_status);

        // 7. Cancellation (final dunning failure / cancel) → locked out again.
        event(new WebhookHandled([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_life', 'status' => 'canceled']],
        ]));
        $this->assertSame(User::SUBSCRIPTION_EXPIRED, $owner->fresh()->subscription_status);
        $this->actingAs($owner->fresh())->get('/profile')->assertRedirect(route('trial.expired'));
    }
}
