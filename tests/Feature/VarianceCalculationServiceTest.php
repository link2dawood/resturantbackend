<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\MenuItem;
use App\Models\MenuItemSold;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Store;
use App\Services\Inventory\UnitMismatchException;
use App\Services\Inventory\VarianceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Executable specification for the variance engine — see
 * docs/features/inventory-variance/variance-formula.md. Written test-first.
 */
class VarianceCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private VarianceCalculationService $svc;
    private Store $store;
    private string $week = '2026-08-03'; // a Monday

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new VarianceCalculationService(); // default thresholds 2% / 5%
        $this->store = Store::factory()->create();
    }

    private function item(array $overrides = []): InventoryItem
    {
        return InventoryItem::factory()->create(array_merge([
            'store_id' => $this->store->id,
            'base_unit' => 'oz',
            'purchase_unit' => 'case',
            'units_per_purchase' => 640,
        ], $overrides));
    }

    private function stock(InventoryItem $item, $starting, $ending): void
    {
        InventoryStock::factory()->create([
            'inventory_item_id' => $item->id,
            'store_id' => $this->store->id,
            'week_start_date' => $this->week,
            'starting_stock' => $starting,
            'actual_ending_stock' => $ending,
            'status' => 'submitted',
        ]);
    }

    private function receivedOrder(InventoryItem $item, $qty, string $unit): void
    {
        $order = Order::factory()->received()->create([
            'store_id' => $this->store->id,
            'week_start_date' => $this->week,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'inventory_item_id' => $item->id,
            'quantity' => $qty,
            'unit' => $unit,
        ]);
    }

    /** Sell $qtySold of a menu item whose $size recipe uses $portionBase of $item. */
    private function sell(InventoryItem $item, float $qtySold, float $portionBase, string $size = 'regular', bool $matched = true): void
    {
        $menu = MenuItem::factory()->create(['store_id' => $this->store->id]);
        $recipe = Recipe::factory()->create([
            'menu_item_id' => $menu->id, 'size_variant' => $size, 'is_current' => true,
        ]);
        RecipeIngredient::factory()->create([
            'recipe_id' => $recipe->id, 'inventory_item_id' => $item->id, 'quantity_base' => $portionBase,
        ]);
        MenuItemSold::factory()->create([
            'store_id' => $this->store->id, 'week_start_date' => $this->week,
            'menu_item_id' => $matched ? $menu->id : null, 'size_variant' => $size,
            'quantity_sold' => $qtySold, 'is_matched' => $matched,
        ]);
    }

    /** @test */
    public function it_computes_the_worked_example(): void
    {
        // Ribeye: 320 oz start + 2 cases (2×640=1280) = 1600 available.
        // 200 Standard sandwiches × 4.5 oz = 900 usage. Theoretical ending 700.
        // Counted 680 → variance 20 oz short = 1.25% → green.
        $steak = $this->item();
        $this->stock($steak, 320, 680);
        $this->receivedOrder($steak, 2, 'case');
        $this->sell($steak, 200, 4.5);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(320, $line->startingStock, 1e-4);
        $this->assertEqualsWithDelta(1280, $line->orderedQty, 1e-4);
        $this->assertEqualsWithDelta(1600, $line->totalAvailable, 1e-4);
        $this->assertEqualsWithDelta(900, $line->theoreticalUsage, 1e-4);
        $this->assertEqualsWithDelta(700, $line->theoreticalEnding, 1e-4);
        $this->assertEqualsWithDelta(680, $line->actualEnding, 1e-4);
        $this->assertEqualsWithDelta(20, $line->variance, 1e-4);
        $this->assertEqualsWithDelta(1.25, $line->variancePct, 1e-4);
        $this->assertSame('green', $line->severity);
        $this->assertFalse($line->isIncomplete);
        $this->assertSame('oz', $line->baseUnit);
    }

    /** @test */
    public function a_large_short_is_flagged_red(): void
    {
        $steak = $this->item();
        $this->stock($steak, 320, 600); // counted 100 short
        $this->receivedOrder($steak, 2, 'case');
        $this->sell($steak, 200, 4.5);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(100, $line->variance, 1e-4);
        $this->assertEqualsWithDelta(6.25, $line->variancePct, 1e-4);
        $this->assertSame('red', $line->severity);
    }

    /** @test */
    public function a_surplus_gives_a_negative_variance(): void
    {
        $steak = $this->item();
        $this->stock($steak, 320, 720); // more than the 700 theoretical
        $this->receivedOrder($steak, 2, 'case');
        $this->sell($steak, 200, 4.5);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(-20, $line->variance, 1e-4);
        $this->assertEqualsWithDelta(-1.25, $line->variancePct, 1e-4);
        $this->assertSame('green', $line->severity); // |1.25%| ≤ 2%
    }

    /** @test */
    public function usage_sums_across_multiple_menu_items_and_size_variants(): void
    {
        $steak = $this->item();
        $this->stock($steak, 0, 0);
        // 100 regular × 4.5 + 50 mini × 3.0 = 450 + 150 = 600 usage.
        $this->sell($steak, 100, 4.5, 'regular');
        $this->sell($steak, 50, 3.0, 'mini');

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(600, $line->theoreticalUsage, 1e-4);
    }

    /** @test */
    public function an_order_in_the_base_unit_needs_no_conversion(): void
    {
        $steak = $this->item(['units_per_purchase' => 640]);
        $this->stock($steak, 100, 100);
        $this->receivedOrder($steak, 250, 'oz'); // already base unit

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(250, $line->orderedQty, 1e-4);
        $this->assertEqualsWithDelta(350, $line->totalAvailable, 1e-4);
    }

    /** @test */
    public function an_unknown_order_unit_throws(): void
    {
        $steak = $this->item();
        $this->stock($steak, 0, 0);
        $this->receivedOrder($steak, 5, 'pallet'); // no conversion defined

        $this->expectException(UnitMismatchException::class);
        $this->svc->calculate($this->store->id, $steak->id, $this->week);
    }

    /** @test */
    public function unmatched_sold_items_are_excluded_and_warned(): void
    {
        $steak = $this->item();
        $this->stock($steak, 1000, 1000);
        $this->sell($steak, 200, 4.5, 'regular', matched: false);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        // The unmatched sale contributes no usage.
        $this->assertEqualsWithDelta(0, $line->theoreticalUsage, 1e-4);
        $this->assertNotEmpty($line->warnings);
    }

    /** @test */
    public function only_received_orders_count_toward_available(): void
    {
        $steak = $this->item();
        $this->stock($steak, 100, 100);
        // A draft (not received) order must NOT count.
        $draft = Order::factory()->create([
            'store_id' => $this->store->id, 'week_start_date' => $this->week, 'status' => 'draft',
        ]);
        OrderItem::factory()->create([
            'order_id' => $draft->id, 'inventory_item_id' => $steak->id, 'quantity' => 3, 'unit' => 'case',
        ]);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(0, $line->orderedQty, 1e-4);
    }

    /** @test */
    public function a_missing_ending_count_makes_the_line_incomplete(): void
    {
        $steak = $this->item();
        InventoryStock::factory()->create([
            'inventory_item_id' => $steak->id, 'store_id' => $this->store->id,
            'week_start_date' => $this->week, 'starting_stock' => 320, 'actual_ending_stock' => null,
        ]);
        $this->sell($steak, 100, 4.5);

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertTrue($line->isIncomplete);
        $this->assertNull($line->variance);
        $this->assertNull($line->variancePct);
        $this->assertNull($line->severity);
    }

    /** @test */
    public function zero_available_yields_zero_percent(): void
    {
        $steak = $this->item();
        $this->stock($steak, 0, 0); // no stock, no orders → available 0
        // no sales

        $line = $this->svc->calculate($this->store->id, $steak->id, $this->week);

        $this->assertEqualsWithDelta(0, $line->totalAvailable, 1e-4);
        $this->assertEqualsWithDelta(0, $line->variancePct, 1e-4);
        $this->assertSame('green', $line->severity);
    }

    /** @test */
    public function severity_thresholds_are_applied_at_the_boundaries(): void
    {
        $this->assertSame('green', $this->svc->severityFor(2.0));
        $this->assertSame('green', $this->svc->severityFor(-2.0));
        $this->assertSame('yellow', $this->svc->severityFor(2.01));
        $this->assertSame('yellow', $this->svc->severityFor(5.0));
        $this->assertSame('red', $this->svc->severityFor(5.01));
        $this->assertSame('red', $this->svc->severityFor(-9.9));
    }

    /** @test */
    public function convert_to_base_uses_explicit_factors(): void
    {
        $item = $this->item(['units_per_purchase' => 640]);

        $this->assertEqualsWithDelta(1280, $this->svc->convertToBase(2, 'case', $item), 1e-4);
        $this->assertEqualsWithDelta(50, $this->svc->convertToBase(50, 'oz', $item), 1e-4);

        $this->expectException(UnitMismatchException::class);
        $this->svc->convertToBase(1, 'gallon', $item);
    }
}
