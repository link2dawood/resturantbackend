<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SignsUpOwners;
use Tests\TestCase;

/**
 * Phase 4 — multi-step owner signup wizard.
 *
 * Signup collects the owner's business profile and their first restaurant
 * (all steps required), then activates the trial.
 */
class RegistrationWizardTest extends TestCase
{
    use RefreshDatabase;
    use SignsUpOwners;

    /** @test */
    public function signup_creates_owner_business_profile_first_store_and_trial(): void
    {
        Mail::fake();

        $this->post('/register', $this->ownerSignupPayload([
            'email' => 'biz@example.com',
            'corporate_address' => '999 Corporate Blvd',
            'state' => 'TX',
            'store_info' => 'Test Cheesesteaks',
            'store_state' => 'TX',
        ]))->assertRedirect('/home');

        $owner = User::where('email', 'biz@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertTrue($owner->isOwner());
        // Business profile persisted on the owner.
        $this->assertSame('Acme Restaurants LLC', $owner->corporate_name);
        $this->assertSame('999 Corporate Blvd', $owner->corporate_address);
        $this->assertSame('TX', $owner->state);
        $this->assertSame('office@example.com', $owner->corporate_email);
        // Trial activated.
        $this->assertSame(User::SUBSCRIPTION_TRIALING, $owner->subscription_status);
        $this->assertNotNull($owner->trial_ends_at);

        // First restaurant created and owned by the new owner.
        $store = Store::where('created_by', $owner->id)->first();
        $this->assertNotNull($store);
        $this->assertSame('Test Cheesesteaks', $store->store_info);
        $this->assertSame('TX', $store->state);

        // …and immediately visible through the owner's access scope.
        $this->assertTrue($owner->accessibleStores()->where('stores.id', $store->id)->exists());
    }

    /** @test */
    public function uploaded_logo_is_stored_and_drives_the_brand_logo(): void
    {
        Mail::fake();
        Storage::fake('public');

        $this->post('/register', $this->ownerSignupPayload([
            'email' => 'logo@example.com',
            'logo' => UploadedFile::fake()->image('brand.png', 200, 200),
        ]))->assertRedirect('/home');

        $owner = User::where('email', 'logo@example.com')->first();
        $this->assertNotNull($owner->logo, 'The logo filename should be saved on the owner.');
        Storage::disk('public')->assertExists('logos/'.$owner->logo);

        // The owner's own brand resolves to the uploaded logo.
        $this->assertStringContainsString('storage/logos/'.$owner->logo, $owner->brandLogoUrl());
    }

    /** @test */
    public function without_a_logo_the_brand_falls_back_to_the_business_name(): void
    {
        Mail::fake();

        $this->post('/register', $this->ownerSignupPayload([
            'email' => 'nologo@example.com',
            'store_info' => 'The Corner Grill',
            // no 'logo' uploaded
        ]))->assertRedirect('/home');

        $owner = User::where('email', 'nologo@example.com')->first();
        $this->assertNull($owner->logo);
        $this->assertNull($owner->brandLogoUrl());
        // Falls back to the corporate / business name they entered.
        $this->assertSame('Acme Restaurants LLC', $owner->brandName());
    }

    /** @test */
    public function signup_is_blocked_until_the_terms_are_accepted(): void
    {
        $response = $this->from('/register')->post('/register', $this->ownerSignupPayload([
            'email' => 'noterms@example.com',
            'terms' => '', // not accepted
        ]));

        $response->assertSessionHasErrors('terms');
        $this->assertDatabaseMissing('users', ['email' => 'noterms@example.com']);
    }

    /** @test */
    public function business_and_restaurant_fields_are_required_and_block_signup(): void
    {
        $response = $this->from('/register')->post('/register', $this->ownerSignupPayload([
            'email' => 'incomplete@example.com',
            'corporate_address' => '',
            'corporate_email' => '',
            'store_info' => '',
            'store_zip' => '',
        ]));

        $response->assertSessionHasErrors(['corporate_address', 'corporate_email', 'store_info', 'store_zip']);
        $this->assertDatabaseMissing('users', ['email' => 'incomplete@example.com']);
        $this->assertSame(0, Store::query()->count());
    }
}
