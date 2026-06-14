<?php

namespace Tests\Feature;

use App\Mail\TrialMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Phase 4 — SaaS Trial System.
 *
 * Activation on signup, real-time lockout when expired, the Trial Expired
 * screen + Request to Continue, and the scheduled expiry notifications.
 */
class TrialSystemTest extends TestCase
{
    use RefreshDatabase;

    private function owner(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'owner',
        ], $overrides));
    }

    /** @test */
    public function signup_activates_a_30_day_trial_and_emails_client_and_sales(): void
    {
        Mail::fake();
        config(['trial.days' => 30, 'trial.sales_email' => 'sales@test.com']);

        $this->post('/register', [
            'name' => 'Trial Owner',
            'email' => 'trial@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $owner = User::where('email', 'trial@example.com')->first();

        $this->assertSame(User::SUBSCRIPTION_TRIALING, $owner->subscription_status);
        $this->assertNotNull($owner->trial_ends_at);
        // ~30 days out (allow slack for test runtime + day rounding).
        $this->assertTrue(
            $owner->trial_ends_at->betweenIncluded(now()->addDays(29), now()->addDays(31)),
            'Trial should end roughly 30 days from signup.'
        );

        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.welcome' && $m->hasTo('trial@example.com'));
        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.signup-sales' && $m->hasTo('sales@test.com'));
    }

    /** @test */
    public function owner_within_trial_can_use_the_app(): void
    {
        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_TRIALING,
            'trial_started_at' => now()->subDays(5),
            'trial_ends_at' => now()->addDays(25),
        ]);

        $this->actingAs($owner)->get('/profile')->assertOk();
    }

    /** @test */
    public function owner_with_expired_trial_is_locked_out_and_redirected(): void
    {
        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_TRIALING,
            'trial_started_at' => now()->subDays(31),
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)->get('/profile')->assertRedirect(route('trial.expired'));
    }

    /** @test */
    public function expired_owner_can_reach_the_trial_expired_screen(): void
    {
        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)->get(route('trial.expired'))->assertOk()->assertSee('free trial has ended');
    }

    /** @test */
    public function active_subscription_is_never_trial_locked(): void
    {
        $owner = $this->owner(['subscription_status' => User::SUBSCRIPTION_ACTIVE]);

        $this->actingAs($owner)->get('/profile')->assertOk();
    }

    /** @test */
    public function admin_is_exempt_from_the_trial_gate(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'subscription_status' => User::SUBSCRIPTION_EXPIRED]);

        $this->actingAs($admin)->get('/profile')->assertOk();
    }

    /** @test */
    public function request_to_continue_records_timestamp_and_notifies_sales_and_client(): void
    {
        Mail::fake();
        config(['trial.sales_email' => 'sales@test.com']);

        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_EXPIRED,
            'trial_ends_at' => now()->subDay(),
            'email' => 'expired@example.com',
        ]);

        $this->actingAs($owner)->post(route('trial.request-continue'))
            ->assertRedirect(route('trial.expired'))
            ->assertSessionHas('success');

        $this->assertNotNull($owner->fresh()->trial_extension_requested_at);

        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.continue-request' && $m->hasTo('sales@test.com'));
        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.continue-confirm' && $m->hasTo('expired@example.com'));
    }

    /** @test */
    public function trials_check_command_notifies_and_expires_lapsed_trials(): void
    {
        Mail::fake();
        config(['trial.sales_email' => 'sales@test.com']);

        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->subDay(),
            'email' => 'lapsed@example.com',
        ]);

        $this->artisan('trials:check')->assertSuccessful();

        $owner->refresh();
        $this->assertSame(User::SUBSCRIPTION_EXPIRED, $owner->subscription_status);
        $this->assertNotNull($owner->trial_expired_notified_at);

        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.expired-client' && $m->hasTo('lapsed@example.com'));
        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.expired-sales' && $m->hasTo('sales@test.com'));
    }

    /** @test */
    public function trials_check_command_sends_expiring_soon_reminder(): void
    {
        Mail::fake();
        config(['trial.expiring_soon_days' => 3]);

        $owner = $this->owner([
            'subscription_status' => User::SUBSCRIPTION_TRIALING,
            'trial_ends_at' => now()->addDays(2),
            'email' => 'soon@example.com',
        ]);

        $this->artisan('trials:check')->assertSuccessful();

        $this->assertNotNull($owner->fresh()->trial_expiring_notified_at);
        Mail::assertSent(TrialMail::class, fn ($m) => $m->markdownView === 'emails.trial.expiring-soon' && $m->hasTo('soon@example.com'));
    }
}
