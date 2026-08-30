<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * None of the client's vendors takes an emailed order, so the order sheet has
 * to say what to do instead: phone Lisanti, open the Coca-Cola site, carry a
 * printed sheet into HEB.
 */
class VendorOrderMethodTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->store = Store::factory()->create(['created_by' => $admin->id]);
        $this->manager = User::factory()->create(['role' => 'manager', 'store_id' => $this->store->id]);
        $this->manager->assignedStoresPivot()->attach($this->store->id);
    }

    private function orderFor(Vendor $vendor): Order
    {
        $order = Order::create([
            'store_id' => $this->store->id,
            'vendor_id' => $vendor->id,
            'week_start_date' => Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'order_sequence' => 1,
            'status' => Order::STATUS_DRAFT,
        ]);

        $item = InventoryItem::factory()->create([
            'store_id' => $this->store->id,
            'inventory_category_id' => InventoryCategory::where('name', 'Meats')->value('id'),
            'name' => 'Steak',
        ]);
        $order->items()->create(['inventory_item_id' => $item->id, 'quantity' => 4, 'unit' => 'box']);

        return $order->fresh();
    }

    /** @test */
    public function the_seeded_vendors_get_the_method_the_client_described(): void
    {
        $this->seed(\Database\Seeders\VendorsSeeder::class);
        $this->artisan('migrate', ['--force' => true]);

        $expected = [
            'Coca-Cola' => 'online',
            'Lisanti' => 'phone',
            'Restaurant Depot' => 'online',
            "Sam's Club" => 'online',
            'HEB' => 'in_person',
            'Walmart' => 'in_person',
        ];

        foreach ($expected as $name => $method) {
            $vendor = Vendor::where('vendor_name', $name)->first();

            if (! $vendor) {
                continue;
            }

            $this->assertSame($method, $vendor->order_method, "{$name} should be ordered {$method}.");
        }
    }

    /** @test */
    public function a_phone_vendor_tells_you_to_ring_them_and_gives_the_number(): void
    {
        $lisanti = Vendor::factory()->create([
            'vendor_name' => 'Lisanti', 'order_method' => 'phone', 'contact_phone' => '215-555-0142',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($lisanti)))
            ->assertOk()
            ->assertSee('Phone the vendor')
            ->assertSee('215-555-0142');
    }

    /** @test */
    public function an_online_vendor_offers_a_link_to_its_site(): void
    {
        $coke = Vendor::factory()->create([
            'vendor_name' => 'Coca-Cola', 'order_method' => 'online', 'website' => 'https://coke.test',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($coke)))
            ->assertOk()
            ->assertSee('Order online')
            ->assertSee('Open Coca-Cola site')
            ->assertSee('https://coke.test');
    }

    /** @test */
    public function an_in_person_vendor_says_to_take_the_sheet(): void
    {
        $heb = Vendor::factory()->create([
            'vendor_name' => 'HEB', 'order_method' => 'in_person', 'address' => '1 Market St',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($heb)))
            ->assertOk()
            ->assertSee('Order in person')
            ->assertSee('1 Market St');
    }

    /** @test */
    public function the_email_button_only_appears_for_a_vendor_that_takes_email(): void
    {
        $phone = Vendor::factory()->create([
            'vendor_name' => 'Lisanti', 'order_method' => 'phone', 'contact_email' => 'orders@lisanti.test',
        ]);

        // It has an email address, but that is not how they take orders.
        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($phone)))
            ->assertOk()
            ->assertDontSee('Email Lisanti');

        $byEmail = Vendor::factory()->create([
            'vendor_name' => 'Some Supplier', 'order_method' => 'email', 'contact_email' => 'sales@supplier.test',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($byEmail)))
            ->assertOk()
            ->assertSee('Email Some Supplier');
    }

    /** @test */
    public function a_vendor_with_no_method_set_is_flagged_on_the_order(): void
    {
        $vendor = Vendor::factory()->create(['vendor_name' => 'Mystery Co', 'order_method' => null]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($vendor)))
            ->assertOk()
            ->assertSee('No ordering method is set');
    }

    /** @test */
    public function a_missing_detail_is_reported_rather_than_left_blank(): void
    {
        $vendor = Vendor::factory()->create([
            'vendor_name' => 'Lisanti', 'order_method' => 'phone', 'contact_phone' => null,
        ]);

        $this->assertSame('Phone the vendor: no phone number on file', $vendor->order_instruction);
    }

    /** @test */
    public function ordering_notes_show_on_the_order(): void
    {
        $vendor = Vendor::factory()->create([
            'vendor_name' => 'Lisanti', 'order_method' => 'phone', 'contact_phone' => '215-555-0142',
            'order_notes' => 'Ask for Marco, orders before 2pm ship same day',
        ]);

        $this->actingAs($this->manager)->get(route('admin.orders.report', $this->orderFor($vendor)))
            ->assertOk()
            ->assertSee('Ask for Marco');
    }

    /** @test */
    public function the_method_can_be_set_from_the_vendor_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/vendors', [
            'vendor_name' => 'New Supplier',
            'vendor_type' => 'Food',
            'order_method' => 'in_person',
            'order_notes' => 'Cash and carry only',
        ])->assertCreated();

        $vendor = Vendor::where('vendor_name', 'New Supplier')->firstOrFail();
        $this->assertSame('in_person', $vendor->order_method);
        $this->assertSame('Cash and carry only', $vendor->order_notes);
    }

    /** @test */
    public function an_unknown_method_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/vendors', [
            'vendor_name' => 'Bad Method', 'vendor_type' => 'Food', 'order_method' => 'carrier_pigeon',
        ])->assertStatus(422)->assertJsonValidationErrors('order_method');
    }
}
