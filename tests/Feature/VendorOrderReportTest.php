<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Support\VendorOrderText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VendorOrderReportTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    private Vendor $lisanti;

    private InventoryCategory $meats;

    private function monday(): Carbon
    {
        return Carbon::parse('2026-08-03')->startOfWeek(Carbon::MONDAY);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo($this->monday()->copy()->setTime(9, 0));

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create([
            'created_by' => $admin->id,
            'store_info' => 'Round Rock',
            'address' => '1200 Sam Bass Rd',
            'city' => 'Round Rock',
            'state' => 'TX',
            'zip' => '78681',
            'phone' => '512-555-0100',
            'contact_name' => 'Dana Reed',
        ]);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
        $this->meats = InventoryCategory::where('name', 'Meats')->firstOrFail();
        $this->lisanti = Vendor::factory()->create([
            'vendor_name' => 'Lisanti',
            'vendor_type' => 'Food',
            'contact_name' => 'Marco Rossi',
            'contact_email' => 'orders@lisanti.test',
            'contact_phone' => '215-555-0142',
        ]);
    }

    private function item(string $name = 'Ribeye Steak'): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => $this->meats->id,
            'name' => $name,
            'base_unit' => 'portion',
            'purchase_unit' => 'box',
            'units_per_purchase' => 53,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'store_id' => $this->store->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
            'created_by' => $this->manager->id,
        ], $overrides));
    }

    private function orderWithLines(): Order
    {
        $order = $this->order(['notes' => 'Deliver before 10am']);

        $order->items()->create([
            'inventory_item_id' => $this->item('Ribeye Steak')->id,
            'quantity' => 4, 'unit' => 'box', 'unit_price' => 145.00,
            'notes' => 'Ask for the fresh pallet',
        ]);
        $order->items()->create([
            'inventory_item_id' => $this->item('Provolone')->id,
            'quantity' => 2, 'unit' => 'case', 'unit_price' => 89.00,
        ]);

        return $order->fresh()->load(['vendor', 'store', 'items.inventoryItem']);
    }

    // ---- The report page ---------------------------------------------------

    /** @test */
    public function the_report_shows_vendor_store_order_details_and_the_total(): void
    {
        $order = $this->orderWithLines();

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertSee('Purchase Order')
            // Vendor block
            ->assertSee('Lisanti')
            ->assertSee('Marco Rossi')
            ->assertSee('orders@lisanti.test')
            ->assertSee('215-555-0142')
            // Store block
            ->assertSee('Round Rock')
            ->assertSee('1200 Sam Bass Rd')
            ->assertSee('512-555-0100')
            // Order details
            ->assertSee('Aug 3, 2026')
            ->assertSee('Ribeye Steak')
            ->assertSee('Provolone')
            ->assertSee('Ask for the fresh pallet')
            ->assertSee('Deliver before 10am')
            // 4 x 145 + 2 x 89 = 758
            ->assertSee('$758.00');
    }

    /** @test */
    public function the_order_detail_page_links_to_the_report(): void
    {
        $order = $this->orderWithLines();

        $this->actingAs($this->manager)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('View Vendor Report')
            ->assertSee(route('admin.orders.report', $order), false);
    }

    /** @test */
    public function the_report_is_readable_for_a_placed_order(): void
    {
        // Re-sending a placed order is the common case, so this must not 403.
        $order = $this->orderWithLines();
        $order->update(['status' => Order::STATUS_PLACED, 'placed_at' => now()]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertSee('Ribeye Steak');
    }

    /** @test */
    public function an_order_with_no_prices_still_renders(): void
    {
        $order = $this->order();
        $order->items()->create([
            'inventory_item_id' => $this->item()->id, 'quantity' => 4, 'unit' => 'box',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertSee('Ribeye Steak')
            ->assertDontSee('Total</th>', false);
    }

    // ---- Plain text --------------------------------------------------------

    /** @test */
    public function the_plain_text_version_carries_everything_a_vendor_needs(): void
    {
        $text = VendorOrderText::build($this->orderWithLines());

        $this->assertStringContainsString('ORDER — Round Rock', $text);
        $this->assertStringContainsString('Vendor:   Lisanti', $text);
        $this->assertStringContainsString('Week of:  Aug 3, 2026', $text);
        $this->assertStringContainsString('Order:    #1', $text);
        $this->assertStringContainsString('1200 Sam Bass Rd', $text);
        $this->assertStringContainsString('Round Rock, TX 78681', $text);
        $this->assertStringContainsString('Ribeye Steak', $text);
        $this->assertStringContainsString('4 box', $text);
        $this->assertStringContainsString('@ $145.00 = $580.00', $text);
        $this->assertStringContainsString('note: Ask for the fresh pallet', $text);
        $this->assertStringContainsString('TOTAL', $text);
        $this->assertStringContainsString('$758.00', $text);
        $this->assertStringContainsString('Deliver before 10am', $text);
        $this->assertStringContainsString('512-555-0100', $text);
    }

    /** @test */
    public function quantities_in_the_plain_text_drop_trailing_zeros(): void
    {
        $order = $this->order();
        $order->items()->create([
            'inventory_item_id' => $this->item()->id, 'quantity' => 2.5000, 'unit' => 'box',
        ]);

        $text = VendorOrderText::build($order->fresh()->load(['vendor', 'store', 'items.inventoryItem']));

        $this->assertStringContainsString('2.5 box', $text);
        $this->assertStringNotContainsString('2.5000', $text);
    }

    /** @test */
    public function the_copy_as_text_option_is_gone(): void
    {
        // None of the client's vendors takes an order as pasted text: Coca-Cola
        // and Amazon online, Lisanti by phone, HEB and Walmart in person. Print
        // and PDF are what they actually use.
        $order = $this->orderWithLines();

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertDontSee('Copy as text')
            ->assertDontSee('Plain text version')
            ->assertDontSee('id="orderPlainText"', false);
    }

    /** @test */
    public function the_email_subject_names_the_store_week_and_order_number(): void
    {
        $subject = VendorOrderText::subject($this->orderWithLines());

        $this->assertSame('Order for Round Rock — week of Aug 3, 2026 (Order 1)', $subject);
    }

    // ---- Email button ------------------------------------------------------

    /** @test */
    public function the_email_button_is_offered_only_when_email_is_how_they_take_orders(): void
    {
        // Having an address is not the same as accepting orders there. Lisanti
        // takes orders by phone.
        $this->lisanti->update(['order_method' => 'email']);
        $order = $this->orderWithLines();

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertSee('Email Lisanti')
            ->assertSee('orders@lisanti.test');
    }

    /** @test */
    public function a_phone_vendor_gets_no_email_button_even_with_an_address_on_file(): void
    {
        $this->lisanti->update(['order_method' => 'phone', 'contact_phone' => '215-555-0142']);
        $order = $this->orderWithLines();

        $this->actingAs($this->manager)->get(route('admin.orders.report', $order))
            ->assertOk()
            ->assertSee('Phone the vendor')
            ->assertDontSee('Email Lisanti');
    }

    // ---- PDF ---------------------------------------------------------------

    /** @test */
    public function the_report_downloads_as_a_pdf(): void
    {
        $order = $this->orderWithLines();

        $response = $this->actingAs($this->manager)
            ->get(route('admin.orders.report.pdf', $order))
            ->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('order-lisanti-2026-08-03-1.pdf', $response->headers->get('Content-Disposition'));
        // A plain response, not a streamed download, so read the body directly.
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /** @test */
    public function the_pdf_renders_for_an_order_with_no_lines(): void
    {
        $order = $this->order();

        $this->actingAs($this->manager)
            ->get(route('admin.orders.report.pdf', $order))
            ->assertOk();
    }

    // ---- Access ------------------------------------------------------------

    /** @test */
    public function a_manager_cannot_open_another_stores_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStore = Store::factory()->create(['created_by' => $admin->id]);
        $foreign = Order::create([
            'store_id' => $otherStore->id,
            'vendor_id' => $this->lisanti->id,
            'week_start_date' => $this->monday()->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $foreign))->assertForbidden();
        $this->actingAs($this->manager)->get(route('admin.orders.report.pdf', $foreign))->assertForbidden();
    }

    /** @test */
    public function employees_cannot_reach_the_report(): void
    {
        $order = $this->orderWithLines();
        $employee = User::factory()->create(['role' => 'employee', 'store_id' => $this->store->id]);

        $this->actingAs($employee)->get(route('admin.orders.report', $order))->assertStatus(403);
        $this->actingAs($employee)->get(route('admin.orders.report.pdf', $order))->assertStatus(403);
    }

    /** @test */
    public function a_guest_is_redirected_to_login(): void
    {
        $order = $this->orderWithLines();

        $this->get(route('admin.orders.report', $order))->assertRedirect(route('login'));
    }
}
