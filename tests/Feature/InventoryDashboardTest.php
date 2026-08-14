<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use App\Notifications\InventoryReminderNotification;
use App\Notifications\LargeVarianceAlertNotification;
use App\Services\Inventory\RecipeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryDashboardTest extends TestCase
{
    use RefreshDatabase;

    /** A RED-variance item (100 oz short = 6.25%) for the given week. */
    private function redItem(Store $store, string $week): InventoryItem
    {
        $steak = InventoryItem::factory()->create([
            'store_id' => $store->id, 'name' => 'Ribeye Steak',
            'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640,
        ]);
        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id, 'store_id' => $store->id,
            'week_start_date' => $week, 'starting_stock' => 320, 'actual_ending_stock' => 600,
        ]);
        $order = Order::factory()->received()->create(['store_id' => $store->id, 'week_start_date' => $week]);
        OrderItem::factory()->create(['order_id' => $order->id, 'inventory_item_id' => $steak->id, 'quantity' => 2, 'unit' => 'case']);

        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        app(RecipeService::class)->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 4.5, 'unit' => 'oz']]);
        MenuItemSold::factory()->create([
            'store_id' => $store->id, 'week_start_date' => $week,
            'menu_item_id' => $menu->id, 'size_variant' => 'regular', 'quantity_sold' => 200, 'is_matched' => true,
        ]);

        return $steak;
    }

    /** @test */
    public function the_dashboard_renders_with_alerts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $priorWeek = now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->subWeek()->toDateString();
        $this->redItem($store, $priorWeek);

        $this->actingAs($admin)->get(route('admin.inventory-dashboard.index', ['store_id' => $store->id]))
            ->assertOk()
            ->assertSee('Inventory Dashboard')
            ->assertSee('Items to investigate')   // alerts table shows because of the red item
            ->assertSee('Ribeye Steak');
    }

    /** @test */
    public function employees_cannot_reach_the_dashboard(): void
    {
        $store = Store::factory()->create();
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);

        $this->actingAs($employee)->get(route('admin.inventory-dashboard.index'))->assertStatus(403);
    }

    /** @test */
    public function the_monday_reminder_notifies_employees_and_managers(): void
    {
        Notification::fake();
        $store = Store::factory()->create();
        InventoryItem::factory()->create(['store_id' => $store->id, 'is_active' => true]);
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $store->id]);
        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $store->id]);

        $this->artisan('inventory:remind')->assertExitCode(0);

        Notification::assertSentTo($employee, InventoryReminderNotification::class);
        Notification::assertSentTo($manager, InventoryReminderNotification::class);
    }

    /** @test */
    public function the_weekly_job_persists_a_snapshot_and_alerts_on_red(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $store->id]);
        $week = '2026-08-17';
        $this->redItem($store, $week);

        $this->artisan('inventory:generate-variance', ['--week' => $week])->assertExitCode(0);

        $this->assertDatabaseHas('variance_reports', ['store_id' => $store->id]);
        Notification::assertSentTo($manager, LargeVarianceAlertNotification::class);
        Notification::assertSentTo($owner, LargeVarianceAlertNotification::class);
    }

    /** @test */
    public function the_weekly_job_sends_no_alert_when_nothing_is_red(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'owner']);
        $store = Store::factory()->create(['created_by' => $owner->id]);
        $item = InventoryItem::factory()->create(['store_id' => $store->id]);
        $week = '2026-08-17';
        // On-hand matches theoretical exactly → green, no alert.
        InventoryStock::factory()->create([
            'inventory_item_id' => $item->id, 'store_id' => $store->id,
            'week_start_date' => $week, 'starting_stock' => 100, 'actual_ending_stock' => 100,
        ]);

        $this->artisan('inventory:generate-variance', ['--week' => $week])->assertExitCode(0);

        $this->assertDatabaseHas('variance_reports', ['store_id' => $store->id]);
        Notification::assertNothingSent();
    }
}
