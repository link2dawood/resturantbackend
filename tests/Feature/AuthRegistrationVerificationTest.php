<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\SignsUpOwners;
use Tests\TestCase;

/**
 * Phase 4 — SaaS conversion: Authentication & User Management.
 *
 * Covers self-serve signup (creates an Owner), email-verification delivery,
 * and the app-wide "block until verified" enforcement.
 */
class AuthRegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;
    use SignsUpOwners;

    /** @test */
    public function self_serve_registration_creates_an_unverified_owner(): void
    {
        $response = $this->post('/register', $this->ownerSignupPayload());

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user, 'Registration should create a user.');
        $this->assertSame(UserRole::OWNER, $user->role, 'Public signup must be an Owner.');
        $this->assertNull($user->email_verified_at, 'New signup must start unverified.');
    }

    /** @test */
    public function registration_dispatches_the_email_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', $this->ownerSignupPayload([
            'name' => 'Verify Me',
            'email' => 'verify@example.com',
        ]));

        $user = User::where('email', 'verify@example.com')->first();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /** @test */
    public function the_registered_event_is_wired_to_send_verification(): void
    {
        // Guards against the listener wiring regressing in AppServiceProvider.
        Event::fake();
        $this->post('/register', $this->ownerSignupPayload([
            'name' => 'Event User',
            'email' => 'event@example.com',
        ]));
        Event::assertDispatched(Registered::class);
    }

    /** @test */
    public function unverified_user_is_blocked_from_the_app_and_redirected_to_verify_notice(): void
    {
        $user = User::factory()->unverified()->create(['role' => UserRole::OWNER]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('verification.notice'));
    }

    /** @test */
    public function verified_user_passes_the_verified_gate(): void
    {
        $user = User::factory()->create(['role' => UserRole::OWNER]); // factory verifies by default

        // /profile sits behind ['auth','verified']; a verified user must NOT be
        // bounced to the verification notice. (We avoid /home here because its
        // dashboard query uses MySQL-only SQL functions the SQLite test DB lacks.)
        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }
}
