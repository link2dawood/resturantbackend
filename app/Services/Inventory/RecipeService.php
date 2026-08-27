<?php

namespace App\Services\Inventory;

use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Support\Facades\DB;

/**
 * Phase 5.3 — recipe versioning. Saving a recipe for a (menu item, size variant)
 * creates a NEW version and flips the previous current one off, so every edit is
 * preserved as history. Ingredient quantities are converted to the item's base
 * unit via UnitConverter (never mixed silently).
 */
class RecipeService
{
    /**
     * @param  array<int, array{inventory_item_id?: int, item?: InventoryItem, quantity: float|string, unit?: string}>  $lines
     */
    public function saveVersion(MenuItem $menuItem, string $sizeVariant, array $lines, ?int $userId = null, ?string $notes = null): Recipe
    {
        return DB::transaction(function () use ($menuItem, $sizeVariant, $lines, $userId, $notes) {
            $nextVersion = (int) Recipe::where('menu_item_id', $menuItem->id)
                ->where('size_variant', $sizeVariant)
                ->max('version') + 1;

            // Retire the previous current recipe(s) for this size.
            Recipe::where('menu_item_id', $menuItem->id)
                ->where('size_variant', $sizeVariant)
                ->update(['is_current' => false]);

            $recipe = Recipe::create([
                'menu_item_id' => $menuItem->id,
                'size_variant' => $sizeVariant,
                'version' => $nextVersion,
                'is_current' => true,
                'created_by' => $userId,
                'notes' => $notes,
            ]);

            foreach ($lines as $line) {
                $item = $line['item'] ?? InventoryItem::findOrFail($line['inventory_item_id']);
                $qty = (float) $line['quantity'];
                $unit = $line['unit'] ?? $item->base_unit;
                $base = UnitConverter::toBase($item, $qty, $unit);

                RecipeIngredient::create([
                    'recipe_id' => $recipe->id,
                    'inventory_item_id' => $item->id,
                    'quantity_base' => $base,
                    'entered_quantity' => $qty,
                    'entered_unit' => $unit,
                ]);
            }

            return $recipe->load('ingredients');
        });
    }
}
