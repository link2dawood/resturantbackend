<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Models\User;
use App\Services\CoaTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4 — Tenant migration utilities.
 */
class TenantMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function coa_template_apply_is_idempotent_and_seeds_the_standard_chart(): void
    {
        $service = app(CoaTemplateService::class);

        $first = $service->apply();
        $this->assertGreaterThan(0, $first['created']);
        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '5100']); // Food COGS
        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '6500']); // Rent

        // Running again creates nothing new.
        $second = $service->apply();
        $this->assertSame(0, $second['created']);
        $this->assertSame(count($service->defaultAccounts()), ChartOfAccount::count());
    }

    /** @test */
    public function migrate_existing_assigns_unowned_stores_to_the_franchisor(): void
    {
        // A legacy store with no explicit owner (no owner_store pivot row).
        $orphan = Store::factory()->create();
        $this->assertFalse($orphan->owners()->exists());

        $this->artisan('tenant:migrate-existing')->assertSuccessful();

        $franchisor = User::where('email', 'franchisor@system.local')->first();
        $this->assertNotNull($franchisor, 'Franchisor should be provisioned.');
        $this->assertTrue($orphan->fresh()->owners()->where('users.id', $franchisor->id)->exists());

        // CoA seeded as part of migration.
        $this->assertDatabaseHas('chart_of_accounts', ['account_code' => '6600']); // Payroll
    }

    /** @test */
    public function migrate_existing_dry_run_makes_no_changes(): void
    {
        $orphan = Store::factory()->create();
        $coaBefore = ChartOfAccount::count(); // system accounts seeded by migrations

        $this->artisan('tenant:migrate-existing', ['--dry-run' => true])->assertSuccessful();

        $this->assertFalse($orphan->fresh()->owners()->exists());
        $this->assertSame($coaBefore, ChartOfAccount::count()); // dry-run seeded nothing
    }

    /** @test */
    public function coa_export_writes_the_template_snapshot(): void
    {
        app(CoaTemplateService::class)->apply();
        $path = storage_path('framework/testing/coa-template-test.json');
        @unlink($path);

        $count = app(CoaTemplateService::class)->export($path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, $count);
        $decoded = json_decode(file_get_contents($path), true);
        $this->assertContains('5100', array_column($decoded, 'account_code'));

        @unlink($path);
    }
}
