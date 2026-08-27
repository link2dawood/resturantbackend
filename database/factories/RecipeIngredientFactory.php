<?php

namespace Database\Factories;

use App\Models\InventoryItem;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeIngredientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'inventory_item_id' => InventoryItem::factory(),
            'quantity_base' => 1,
            'entered_quantity' => null,
            'entered_unit' => null,
        ];
    }
}
