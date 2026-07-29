<?php

namespace Tests\Feature;

use App\Models\ExpenseTransaction;
use App\Models\Vendor;
use App\Models\VendorAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DedupeVendorsCommandTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_merges_exact_duplicate_vendors_and_keeps_the_alias_owner(): void
    {
        // Three "Sam's Club" rows; only #2 owns the manual alias (the rest are
        // orphans left by the old crash).
        $a = Vendor::create(['vendor_name' => "Sam's Club", 'vendor_type' => 'Supplies', 'is_active' => true]);
        $keeper = Vendor::create(['vendor_name' => "Sam's Club", 'vendor_type' => 'Supplies', 'is_active' => true]);
        $c = Vendor::create(['vendor_name' => "sam's club", 'vendor_type' => 'Supplies', 'is_active' => true]);
        VendorAlias::create(['vendor_id' => $keeper->id, 'alias' => "Sam's Club", 'source' => 'manual']);

        // A distinct spelling must be left alone.
        $other = Vendor::create(['vendor_name' => 'Sams Club', 'vendor_type' => 'Supplies', 'is_active' => true]);

        $this->artisan('vendors:dedupe', ['--apply' => true])->assertSuccessful();

        // The orphans are gone, the alias owner remains, the distinct name stays.
        $this->assertNull(Vendor::find($a->id));
        $this->assertNull(Vendor::find($c->id));
        $this->assertNotNull(Vendor::find($keeper->id));
        $this->assertNotNull(Vendor::find($other->id));
        $this->assertSame(1, Vendor::whereRaw("LOWER(vendor_name) = ?", ["sam's club"])->count());
    }

    /** @test */
    public function it_repoints_transactions_from_the_dupes_to_the_keeper(): void
    {
        $keeper = Vendor::create(['vendor_name' => 'Dordash', 'vendor_type' => 'Services', 'is_active' => true]);
        $dupe = Vendor::create(['vendor_name' => 'Dordash', 'vendor_type' => 'Services', 'is_active' => true]);

        $txn = ExpenseTransaction::factory()->create(['vendor_id' => $dupe->id]);

        $this->artisan('vendors:dedupe', ['--apply' => true])->assertSuccessful();

        // The transaction must now point at the keeper, not be nulled/orphaned.
        $this->assertSame($keeper->id, $txn->fresh()->vendor_id);
        $this->assertNull(Vendor::find($dupe->id));
    }

    /** @test */
    public function dry_run_changes_nothing(): void
    {
        Vendor::create(['vendor_name' => 'Uber', 'vendor_type' => 'Services', 'is_active' => true]);
        Vendor::create(['vendor_name' => 'Uber', 'vendor_type' => 'Services', 'is_active' => true]);

        $this->artisan('vendors:dedupe')->assertSuccessful();

        $this->assertSame(2, Vendor::where('vendor_name', 'Uber')->count());
    }
}
