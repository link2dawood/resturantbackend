<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryCategoryRequest;
use App\Http\Requests\UpdateInventoryCategoryRequest;
use App\Models\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Phase 5 — order-guide category management. The list is server-rendered; add,
 * inline rename, delete and drag-to-reorder all post back as JSON so the page
 * never has to reload mid-drag. Admin only: renaming a category changes what
 * every store sees on its weekly count sheet.
 */
class InventoryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = InventoryCategory::withCount([
            'items',
            'activeItems as active_items_count',
        ])->ordered()->get();

        return view('admin.inventory-categories.index', compact('categories'));
    }

    public function store(StoreInventoryCategoryRequest $request)
    {
        $category = InventoryCategory::create([
            'name' => trim($request->name),
            'display_order' => $request->filled('display_order')
                ? (int) $request->display_order
                : InventoryCategory::nextDisplayOrder(),
        ]);

        return response()->json([
            'message' => 'Category added.',
            'data' => $category,
        ], 201);
    }

    public function update(UpdateInventoryCategoryRequest $request, InventoryCategory $inventoryCategory)
    {
        $inventoryCategory->update(array_filter([
            'name' => trim($request->name),
            'display_order' => $request->filled('display_order') ? (int) $request->display_order : null,
        ], fn ($value) => $value !== null));

        return response()->json([
            'message' => 'Category updated.',
            'data' => $inventoryCategory->fresh(),
        ]);
    }

    /**
     * Delete a category.
     *
     * Refused while active items are filed under it, because the FK is
     * nullOnDelete: the items would survive with no category and quietly drop
     * off the grouped order guide. The blocking items are named in the response.
     */
    public function destroy(InventoryCategory $inventoryCategory)
    {
        $blocking = $inventoryCategory->activeItems()
            ->orderBy('name')
            ->get(['id', 'name', 'store_id']);

        if ($blocking->isNotEmpty()) {
            return response()->json([
                'error' => '"'.$inventoryCategory->name.'" still has '.$blocking->count().' active '
                    .Str::plural('item', $blocking->count())
                    .'. Move them to another category first.',
                'items' => $blocking->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values(),
            ], 422);
        }

        // Only inactive items can still point here; say how many lost their
        // category rather than nulling them out silently.
        $unassigned = $inventoryCategory->items()->count();
        $inventoryCategory->delete();

        return response()->json([
            'message' => $unassigned > 0
                ? 'Category deleted. '.$unassigned.' inactive '.Str::plural('item', $unassigned).' no longer has a category.'
                : 'Category deleted.',
        ]);
    }

    /**
     * Persist a drag-and-drop reorder. Positions are rewritten in tens so a
     * later manual insert has room between two neighbours.
     */
    public function reorder(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'exists:inventory_categories,id'],
        ]);

        DB::transaction(function () use ($data) {
            foreach (array_values($data['order']) as $index => $id) {
                InventoryCategory::where('id', $id)->update(['display_order' => ($index + 1) * 10]);
            }
        });

        return response()->json([
            'message' => 'Order saved.',
            'data' => InventoryCategory::ordered()->get(['id', 'name', 'display_order']),
        ]);
    }
}
