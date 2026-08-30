<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\InventoryOverdueNotification;
use App\Notifications\InventoryReminderNotification;
use App\Notifications\OrderStatusChangedNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private User $admin;

    private Vendor $lisanti;

    private InventoryCategory $meats;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-24')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'The Admin']);
        $this->store = Store::factory()->create(['created_by' => $this->admin->id, 'store_info' => 'Round Rock']);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id, 'name' => 'Dana Reed']);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->lisanti = Vendor::factory()->create(['vendor_name' => 'Lisanti', 'vendor_type' => 'Food']);
    }

    private function item(string $name = 'Steak'): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ], $overrides));

        $order->items()->create([
            'inventory_item_id' => $this->item()->id,
            'quantity' => 4, 'unit' => 'box', 'unit_price' => 145.00,
        ]);

        return $order->fresh();
    }

    // ---- 1. Monday reminder -------------------------------------------------

    /** @test */
    public function the_monday_reminder_reaches_the_manager_by_mail_and_in_app(): void
    {
        $this->item();

        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);

        $this->assertSame(1, $this->manager->fresh()->unreadNotifications()->count());

        $notification = $this->manager->fresh()->notifications()->first();
        $this->assertSame(InventoryReminderNotification::class, $notification->type);
        $this->assertSame("Time to enter this week's inventory", $notification->data['title']);
        $this->assertStringContainsString('/inventory/weekly-count', $notification->data['url']);
    }


    // ---- 2. Wednesday chase -------------------------------------------------

    /** @test */
    public function the_wednesday_chase_goes_out_when_the_week_is_not_submitted(): void
    {
        Notification::fake();
        $item = $this->item();
        InventoryStock::create([
            'inventory_item_id' => $item->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 0, 'status' => InventoryStock::STATUS_DRAFT,
        ]);

        $this->artisan('inventory:remind-overdue', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);

        Notification::assertSentTo($this->manager, InventoryOverdueNotification::class);
    }

    /** @test */
    public function the_wednesday_chase_skips_a_store_that_already_submitted(): void
    {
        Notification::fake();
        $item = $this->item();
        InventoryStock::create([
            'inventory_item_id' => $item->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 10, 'status' => InventoryStock::STATUS_SUBMITTED,
            'counted_at' => now(),
        ]);

        // Nagging someone who already complied is how alerts get ignored.
        $this->artisan('inventory:remind-overdue', ['--week' => $this->monday()->toDateString()])
            ->expectsOutputToContain('1 store(s) had already submitted')
            ->assertExitCode(0);

        Notification::assertNothingSent();
    }

    /** @test */
    public function the_wednesday_chase_reports_partial_progress(): void
    {
        $a = $this->item('Steak');
        $b = $this->item('Chicken');

        InventoryStock::create([
            'inventory_item_id' => $a->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 10, 'status' => 'draft', 'counted_at' => now(),
        ]);
        InventoryStock::create([
            'inventory_item_id' => $b->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->monday()->toDateString(),
            'starting_stock' => 0, 'status' => 'draft',
        ]);

        $this->artisan('inventory:remind-overdue', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);

        $data = $this->manager->fresh()->notifications()->first()->data;
        $this->assertStringContainsString('1 of 2 items counted', $data['body']);
    }


    /** @test */
    public function neither_reminder_is_scheduled_but_both_still_run_by_hand(): void
    {
        // The client asked for no automated nagging: they count every Monday
        // because orders go out by Wednesday.
        $scheduled = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command ?? '', 'inventory:remind'));

        $this->assertCount(0, $scheduled);

        // Run by hand, they still work. That is what the tests above cover.
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()])->assertExitCode(0);
        $this->assertSame(1, $this->manager->fresh()->unreadNotifications()->count());
    }

    // ---- 3 & 4. Order status ------------------------------------------------

    /** @test */
    public function placing_an_order_notifies_the_admin_with_the_vendor_and_amount(): void
    {
        $order = $this->order();

        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $order))->assertRedirect();

        $notification = $this->admin->fresh()->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(OrderStatusChangedNotification::class, $notification->type);
        $this->assertStringContainsString('Order placed with Lisanti', $notification->data['title']);
        $this->assertStringContainsString('Dana Reed placed an order with Lisanti', $notification->data['body']);
        $this->assertStringContainsString('$580.00', $notification->data['body']);
    }

    /** @test */
    public function receiving_an_order_notifies_the_admin(): void
    {
        $order = $this->order(['status' => Order::STATUS_PLACED, 'placed_at' => now()]);

        $this->actingAs($this->manager)->patch(route('admin.orders.received', $order))->assertRedirect();

        $notification = $this->admin->fresh()->notifications()->first();

        $this->assertStringContainsString('Order received from Lisanti', $notification->data['title']);
        $this->assertStringContainsString('marked the Lisanti order', $notification->data['body']);
    }

    /** @test */
    public function the_franchisor_is_notified_alongside_admins(): void
    {
        $franchisor = User::factory()->create(['role' => 'owner', 'name' => 'Franchisor', 'state' => 'PA']);
        $plainOwner = User::factory()->create(['role' => 'owner', 'name' => 'Some Owner', 'state' => 'PA']);
        $order = $this->order();

        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $order))->assertRedirect();

        $this->assertSame(1, $franchisor->fresh()->notifications()->count());
        // A regular owner is not management for this purpose.
        $this->assertSame(0, $plainOwner->fresh()->notifications()->count());
    }

    /** @test */
    public function a_refused_transition_sends_nothing(): void
    {
        Notification::fake();
        $order = $this->order(['status' => Order::STATUS_RECEIVED]);

        $this->actingAs($this->manager)->patch(route('admin.orders.placed', $order))->assertSessionHas('error');

        Notification::assertNothingSent();
    }

    // ---- In-app bell --------------------------------------------------------

    /** @test */
    public function the_bell_shows_an_unread_count_in_the_navbar(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);

        $this->actingAs($this->manager)->get(route('admin.inventory-dashboard.index'))
            ->assertOk()
            ->assertSee('id="notificationBadge"', false)
            ->assertSee('aria-label="Notifications"', false);
    }

    /** @test */
    public function the_recent_endpoint_returns_the_users_notifications(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);

        $this->actingAs($this->manager)->getJson(route('notifications.recent'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', "Time to enter this week's inventory")
            ->assertJsonPath('notifications.0.read', false);
    }

    /** @test */
    public function one_notification_can_be_marked_read(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);
        $notification = $this->manager->fresh()->notifications()->first();

        $this->actingAs($this->manager)
            ->postJson(route('notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function all_notifications_can_be_marked_read_at_once(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);
        $this->artisan('inventory:remind-overdue', ['--week' => $this->monday()->toDateString()]);

        $this->assertSame(2, $this->manager->fresh()->unreadNotifications()->count());

        $this->actingAs($this->manager)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $this->manager->fresh()->unreadNotifications()->count());
    }

    /** @test */
    public function the_notifications_page_lists_them(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);

        $this->actingAs($this->manager)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee("Time to enter this week's inventory")
            ->assertSee('1 unread');
    }

    /** @test */
    public function a_user_cannot_mark_someone_elses_notification_read(): void
    {
        $this->item();
        $this->artisan('inventory:remind', ['--week' => $this->monday()->toDateString()]);
        $managersNotification = $this->manager->fresh()->notifications()->first();

        $this->actingAs($this->admin)
            ->postJson(route('notifications.read', $managersNotification->id))
            ->assertNotFound();

        $this->assertNull($managersNotification->fresh()->read_at);
    }

    /** @test */
    public function a_guest_cannot_reach_the_notification_routes(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
    }
}
