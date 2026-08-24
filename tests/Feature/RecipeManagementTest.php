<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Store;
use App\Models\User;
use App\Services\Inventory\RecipeService;
use App\Services\Inventory\UnitMismatchException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RecipeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function steak(Store $store): InventoryItem
    {
        return InventoryItem::factory()->create([
            'store_id' => $store->id, 'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640,
        ]);
    }

    /** @test */
    public function saving_a_recipe_creates_a_new_version_and_retires_the_old_one(): void
    {
        $store = Store::factory()->create();
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        $steak = $this->steak($store);
        $svc = app(RecipeService::class);

        $v1 = $svc->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 4.5, 'unit' => 'oz']]);
        $v2 = $svc->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 5.0, 'unit' => 'oz']]);

        $this->assertSame(1, $v1->version);
        $this->assertSame(2, $v2->version);
        $this->assertFalse($v1->fresh()->is_current);
        $this->assertTrue($v2->fresh()->is_current);
        // History preserved: v1 still has its 4.5 oz ingredient.
        $this->assertEqualsWithDelta(4.5, (float) $v1->ingredients()->first()->quantity_base, 1e-4);
    }

    /** @test */
    public function ingredient_quantities_are_converted_to_the_base_unit(): void
    {
        $store = Store::factory()->create();
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        $steak = $this->steak($store);
        $svc = app(RecipeService::class);

        // 2 cases → 1280 oz; 4.5 oz stays 4.5.
        $case = $svc->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 2, 'unit' => 'case']]);
        $this->assertEqualsWithDelta(1280, (float) $case->ingredients()->first()->quantity_base, 1e-4);

        $oz = $svc->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 4.5, 'unit' => 'oz']]);
        $this->assertEqualsWithDelta(4.5, (float) $oz->ingredients()->first()->quantity_base, 1e-4);
    }

    /** @test */
    public function size_variants_have_independent_current_recipes(): void
    {
        $store = Store::factory()->create();
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        $steak = $this->steak($store);
        $svc = app(RecipeService::class);

        $svc->saveVersion($menu, 'mini', [['inventory_item_id' => $steak->id, 'quantity' => 3, 'unit' => 'oz']]);
        $svc->saveVersion($menu, 'regular', [['inventory_item_id' => $steak->id, 'quantity' => 4.5, 'unit' => 'oz']]);

        $this->assertEqualsWithDelta(3, (float) $menu->currentRecipe('mini')->ingredients->first()->quantity_base, 1e-4);
        $this->assertEqualsWithDelta(4.5, (float) $menu->currentRecipe('regular')->ingredients->first()->quantity_base, 1e-4);
    }

    /** @test */
    public function an_unknown_unit_is_rejected(): void
    {
        $store = Store::factory()->create();
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        $steak = $this->steak($store);

        $this->expectException(UnitMismatchException::class);
        app(RecipeService::class)->saveVersion($menu, 'regular', [
            ['inventory_item_id' => $steak->id, 'quantity' => 1, 'unit' => 'gallon'],
        ]);
    }

    /** @test */
    public function an_admin_can_save_a_recipe_through_the_controller(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $menu = MenuItem::factory()->create(['store_id' => $store->id]);
        $steak = $this->steak($store);

        $this->actingAs($admin)->put(route('admin.menu-items.recipe.update', [$menu, 'regular']), [
            'ingredient_item_id' => [$steak->id],
            'quantity' => [4.5],
            'unit' => ['oz'],
        ])->assertRedirect();

        $this->assertDatabaseHas('recipes', [
            'menu_item_id' => $menu->id, 'size_variant' => 'regular', 'is_current' => true,
        ]);
    }

    /** @test */
    public function the_recipe_editor_and_index_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $menu = MenuItem::factory()->create(['store_id' => $store->id, 'name' => 'Standard Steak']);
        $this->steak($store);

        $this->actingAs($admin)->get(route('admin.menu-items.show', $menu))
            ->assertOk()->assertSee('Recipe by size')->assertSee('Standard Steak');
        $this->actingAs($admin)->get(route('admin.menu-items.index', ['store_id' => $store->id]))
            ->assertOk()->assertSee('Standard Steak');
    }

    /** @test */
    public function managers_cannot_reach_the_recipe_admin(): void
    {
        $store = Store::factory()->create();
        $manager = User::factory()->create(['role' => 'manager', 'store_id' => $store->id]);

        $this->actingAs($manager)->get(route('admin.menu-items.index'))->assertStatus(403);
    }

    /** @test */
    public function csv_import_builds_recipes_and_maps_ingredients_by_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        $steak = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak', 'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640]);
        $roll = InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Hoagie Roll', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 24]);

        $csv = "menu_item,size_variant,ingredient,quantity,unit\n"
            ."Big Steak,regular,Ribeye Steak,4.5,oz\n"
            ."Big Steak,regular,Hoagie Roll,1,each\n"
            ."Big Steak,regular,Unknown Cheese,2,oz\n"; // unmatched → warned

        $file = UploadedFile::fake()->createWithContent('recipes.csv', $csv);

        $this->actingAs($admin)->post(route('admin.menu-items.import'), [
            'store_id' => $store->id, 'file' => $file,
        ])->assertRedirect();

        $menu = MenuItem::where('store_id', $store->id)->where('name', 'Big Steak')->first();
        $this->assertNotNull($menu);
        $recipe = $menu->currentRecipe('regular');
        // Unmatched ingredient aborts that group, so no recipe is created for it.
        $this->assertNull($recipe, 'A group with an unmatched ingredient should be skipped');
    }

    /** @test */
    public function csv_import_creates_a_recipe_when_all_ingredients_match(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $store = Store::factory()->create(['created_by' => $admin->id]);
        InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Ribeye Steak', 'base_unit' => 'oz', 'purchase_unit' => 'case', 'units_per_purchase' => 640]);
        InventoryItem::factory()->create(['store_id' => $store->id, 'name' => 'Hoagie Roll', 'base_unit' => 'each', 'purchase_unit' => 'case', 'units_per_purchase' => 24]);

        $csv = "menu_item,size_variant,ingredient,quantity,unit\n"
            ."Big Steak,regular,Ribeye Steak,4.5,oz\n"
            ."Big Steak,regular,Hoagie Roll,1,each\n";

        $file = UploadedFile::fake()->createWithContent('recipes.csv', $csv);
        $this->actingAs($admin)->post(route('admin.menu-items.import'), ['store_id' => $store->id, 'file' => $file]);

        $menu = MenuItem::where('store_id', $store->id)->where('name', 'Big Steak')->firstOrFail();
        $recipe = $menu->currentRecipe('regular');
        $this->assertNotNull($recipe);
        $this->assertSame(2, $recipe->ingredients->count());
        $steakLine = $recipe->ingredients->firstWhere('entered_unit', 'oz');
        $this->assertEqualsWithDelta(4.5, (float) $steakLine->quantity_base, 1e-4);
    }
}
