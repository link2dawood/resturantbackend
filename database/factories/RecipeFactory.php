<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'menu_item_id' => MenuItem::factory(),
            'size_variant' => 'regular',
            'version' => 1,
            'is_current' => true,
            'created_by' => null,
            'notes' => null,
        ];
    }
}
