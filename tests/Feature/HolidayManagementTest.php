<?php

namespace Tests\Feature;

use App\Models\Holiday;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // The create-table migration seeds the default list; start clean so each
        // test controls its own holidays.
        Holiday::query()->delete();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function an_admin_can_add_edit_deactivate_and_delete_a_holiday(): void
    {
        $admin = $this->admin();

        // Add
        $this->actingAs($admin)->post(route('admin.holidays.store'), ['name' => 'Super Bowl Sunday'])
            ->assertRedirect();
        $holiday = Holiday::where('name', 'Super Bowl Sunday')->first();
        $this->assertNotNull($holiday);
        $this->assertTrue($holiday->is_active);

        // Edit + deactivate (unchecked is_active)
        $this->actingAs($admin)->put(route('admin.holidays.update', $holiday), [
            'name' => 'Big Game Sunday',
            'sort_order' => 5,
        ])->assertRedirect();
        $holiday->refresh();
        $this->assertSame('Big Game Sunday', $holiday->name);
        $this->assertFalse($holiday->is_active);

        // Delete
        $this->actingAs($admin)->delete(route('admin.holidays.destroy', $holiday))->assertRedirect();
        $this->assertNull(Holiday::find($holiday->id));
    }

    /** @test */
    public function duplicate_names_are_rejected(): void
    {
        $admin = $this->admin();
        Holiday::create(['name' => 'Labor Day', 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.holidays.store'), ['name' => 'Labor Day'])
            ->assertSessionHasErrors('name');
    }

    /** @test */
    public function option_list_returns_only_active_names_in_order(): void
    {
        Holiday::create(['name' => 'Zeta Day', 'sort_order' => 2, 'is_active' => true]);
        Holiday::create(['name' => 'Alpha Day', 'sort_order' => 1, 'is_active' => true]);
        Holiday::create(['name' => 'Hidden Day', 'sort_order' => 3, 'is_active' => false]);

        $this->assertSame(['Alpha Day', 'Zeta Day'], Holiday::optionList());
    }

    /** @test */
    public function the_daily_report_form_lists_managed_holidays(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        Holiday::create(['name' => 'Founders Day', 'is_active' => true]);

        $this->actingAs($owner)->get(route('daily-reports.create-form', [
            'store_id' => $store->id,
            'report_date' => now()->format('Y-m-d'),
        ]))->assertStatus(200)->assertSee('Founders Day');
    }

    /** @test */
    public function managers_cannot_manage_holidays(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager)->get(route('admin.holidays.index'))->assertStatus(403);
    }
}
