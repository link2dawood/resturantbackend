<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\MenuItem;
use App\Models\Store;
use App\Services\Inventory\RecipeService;
use App\Services\Inventory\UnitMismatchException;
use Illuminate\Http\Request;

/**
 * Phase 5.3 — admin interface for menu items and their recipes (portion maps).
 * Recipes are per size variant and versioned (see RecipeService). Supports CSV
 * bulk import. Admin/owner only; scoped to accessible stores.
 */
class MenuItemController extends Controller
{
    public const SIZES = ['mini', 'regular', 'large'];

    public function __construct(private RecipeService $recipes)
    {
    }

    public function index(Request $request)
    {
        $store = $this->resolveStore($request);
        $menuItems = MenuItem::where('store_id', $store->id)
            ->withCount('recipes')
            ->orderBy('name')
            ->paginate(30);
        $stores = $this->storeOptions();

        return view('admin.menu-items.index', compact('store', 'menuItems', 'stores'));
    }

    public function create(Request $request)
    {
        $store = $this->resolveStore($request);

        return view('admin.menu-items.form', ['store' => $store, 'menuItem' => new MenuItem()]);
    }

    public function store(Request $request)
    {
        $store = $this->resolveStore($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'square_name' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $menuItem = MenuItem::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'square_name' => $data['square_name'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.menu-items.show', $menuItem)->with('success', 'Menu item created.');
    }

    public function show(MenuItem $menuItem)
    {
        $this->authorizeStore($menuItem);

        $inventoryItems = InventoryItem::where('store_id', $menuItem->store_id)
            ->where('is_active', true)->orderBy('category')->orderBy('name')->get();

        // Current recipe + full version history per size.
        $recipesBySize = [];
        $historyBySize = [];
        foreach (self::SIZES as $size) {
            $recipesBySize[$size] = $menuItem->recipes()
                ->where('size_variant', $size)->where('is_current', true)
                ->with('ingredients.inventoryItem')->first();
            $historyBySize[$size] = $menuItem->recipes()
                ->where('size_variant', $size)
                ->with('ingredients.inventoryItem')
                ->orderByDesc('version')->get();
        }

        return view('admin.menu-items.show', compact('menuItem', 'inventoryItems', 'recipesBySize', 'historyBySize'));
    }

    public function edit(MenuItem $menuItem)
    {
        $this->authorizeStore($menuItem);

        return view('admin.menu-items.form', ['store' => $menuItem->store, 'menuItem' => $menuItem]);
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $this->authorizeStore($menuItem);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:50'],
            'square_name' => ['nullable', 'string', 'max:191'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $menuItem->update([
            'name' => $data['name'],
            'category' => $data['category'] ?? null,
            'square_name' => $data['square_name'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.menu-items.show', $menuItem)->with('success', 'Menu item updated.');
    }

    public function destroy(MenuItem $menuItem)
    {
        $this->authorizeStore($menuItem);
        $menuItem->delete();

        return redirect()->route('admin.menu-items.index', ['store_id' => $menuItem->store_id])
            ->with('success', 'Menu item deleted.');
    }

    /** Save a new recipe version for one size variant. */
    public function updateRecipe(Request $request, MenuItem $menuItem, string $size)
    {
        $this->authorizeStore($menuItem);
        abort_unless(in_array($size, self::SIZES, true), 404);

        $data = $request->validate([
            'ingredient_item_id' => ['required', 'array', 'min:1'],
            'ingredient_item_id.*' => ['nullable', 'integer'],
            'quantity' => ['required', 'array'],
            'quantity.*' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['required', 'array'],
            'unit.*' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $lines = [];
        $accessibleItemIds = InventoryItem::where('store_id', $menuItem->store_id)->pluck('id')->all();
        foreach ($data['ingredient_item_id'] as $i => $itemId) {
            $qty = $data['quantity'][$i] ?? null;
            if (! $itemId || $qty === null || $qty === '') {
                continue; // skip blank rows
            }
            if (! in_array((int) $itemId, $accessibleItemIds, true)) {
                return back()->withInput()->with('error', 'An ingredient does not belong to this store.');
            }
            $lines[] = [
                'inventory_item_id' => (int) $itemId,
                'quantity' => (float) $qty,
                'unit' => $data['unit'][$i] ?? null,
            ];
        }

        if (empty($lines)) {
            return back()->withInput()->with('error', 'Add at least one ingredient.');
        }

        try {
            $this->recipes->saveVersion($menuItem, $size, $lines, auth()->id(), $data['notes'] ?? null);
        } catch (UnitMismatchException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.menu-items.show', $menuItem)
            ->with('success', ucfirst($size).' recipe saved (new version).');
    }

    public function importForm(Request $request)
    {
        $store = $this->resolveStore($request);

        return view('admin.menu-items.import', ['store' => $store, 'stores' => $this->storeOptions()]);
    }

    public function import(Request $request)
    {
        $store = $this->resolveStore($request);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);

        $lines = file($request->file('file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($lines); // header: menu_item,size_variant,ingredient,quantity,unit

        // Group ingredient rows by (menu item name, size).
        $groups = [];
        $errors = [];
        foreach ($lines as $n => $line) {
            $row = str_getcsv($line);
            if (count($row) < 5) {
                continue;
            }
            [$menuName, $size, $ingredientName, $qty, $unit] = array_map('trim', $row);
            $size = strtolower($size);
            if (! in_array($size, self::SIZES, true)) {
                $errors[] = "Row ".($n + 2).": unknown size '{$size}'.";
                continue;
            }
            $groups[$menuName.'|'.$size][] = compact('ingredientName', 'qty', 'unit');
        }

        $created = 0;
        $itemsByName = InventoryItem::where('store_id', $store->id)->get()
            ->keyBy(fn ($i) => strtolower($i->name));

        foreach ($groups as $key => $rows) {
            [$menuName, $size] = explode('|', $key, 2);
            $menuItem = MenuItem::firstOrCreate(
                ['store_id' => $store->id, 'name' => $menuName],
                ['is_active' => true]
            );

            $lines = [];
            $groupOk = true;
            foreach ($rows as $r) {
                $item = $itemsByName->get(strtolower($r['ingredientName']));
                if (! $item) {
                    $errors[] = "'{$menuName}' ({$size}): ingredient '{$r['ingredientName']}' not found in store inventory.";
                    $groupOk = false;
                    continue;
                }
                $lines[] = ['item' => $item, 'quantity' => (float) $r['qty'], 'unit' => $r['unit'] ?: $item->base_unit];
            }

            if ($groupOk && ! empty($lines)) {
                try {
                    $this->recipes->saveVersion($menuItem, $size, $lines, auth()->id(), 'Bulk CSV import');
                    $created++;
                } catch (UnitMismatchException $e) {
                    $errors[] = "'{$menuName}' ({$size}): {$e->getMessage()}";
                }
            }
        }

        return redirect()->route('admin.menu-items.index', ['store_id' => $store->id])
            ->with('success', "{$created} recipe version(s) imported.")
            ->with('import_errors', $errors);
    }

    // ── store scoping ────────────────────────────────────────────────────────

    private function resolveStore(Request $request): Store
    {
        $accessible = auth()->user()->getAccessibleStoreIds();
        abort_if(empty($accessible), 403, 'No accessible store.');
        $requested = (int) $request->input('store_id');
        $storeId = in_array($requested, $accessible, true) ? $requested : $accessible[0];

        return Store::findOrFail($storeId);
    }

    private function storeOptions()
    {
        return Store::whereIn('id', auth()->user()->getAccessibleStoreIds())->get();
    }

    private function authorizeStore(MenuItem $menuItem): void
    {
        abort_unless(in_array($menuItem->store_id, auth()->user()->getAccessibleStoreIds(), true), 403);
    }
}
