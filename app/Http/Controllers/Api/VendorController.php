<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\Vendor;
use App\Models\VendorAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    /**
     * Restrict requested store IDs to those the current user may access, so an
     * owner cannot assign a vendor to a store they do not control. Admins (and
     * the franchisor, via getAccessibleStoreIds) keep the full requested list.
     */
    private function restrictToAccessibleStores($requested): array
    {
        $requested = array_map('intval', (array) ($requested ?? []));
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $requested;
        }

        return array_values(array_intersect($requested, $user->getAccessibleStoreIds()));
    }

    /**
     * Display a listing of vendors
     */
    public function index(Request $request)
    {
        $query = Vendor::with(['defaultCoa', 'stores', 'creator'])
            ->withCount(['inventoryItems', 'preferredForItems']);

        // Hidden (soft-deleted) vendors are excluded unless explicitly asked for.
        if ($request->boolean('only_trashed')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('with_trashed')) {
            $query->withTrashed();
        }

        // Filters
        if ($request->has('store_id')) {
            $query->whereHas('stores', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->has('vendor_type')) {
            $query->where('vendor_type', $request->vendor_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('has_coa')) {
            if ($request->boolean('has_coa')) {
                $query->hasCoa();
            } else {
                $query->doesntHave('defaultCoa');
            }
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $vendors = $query->paginate($request->per_page ?? 25);

        return response()->json($vendors);
    }

    /**
     * Store a newly created vendor
     */
    public function store(StoreVendorRequest $request)
    {
        $vendorName = trim($request->vendor_name);

        // A hidden vendor still owns its name and its aliases. Restoring it beats
        // creating a second row that would collide on the unique alias index.
        $trashed = Vendor::onlyTrashed()
            ->whereRaw('LOWER(vendor_name) = ?', [mb_strtolower($vendorName)])
            ->first();

        if ($trashed) {
            $trashed->restore();
            $trashed->update($this->vendorAttributes($request) + ['is_active' => true]);

            $storeIds = $this->restrictToAccessibleStores($request->store_ids);
            if (! empty($storeIds)) {
                $trashed->stores()->syncWithoutDetaching($storeIds);
            }

            return response()->json([
                'message' => 'This vendor was hidden and has been restored.',
                'data' => $trashed->load(['defaultCoa', 'stores', 'aliases']),
            ], 200);
        }

        // Idempotent: if a vendor with this name (or a matching alias) already
        // exists, reuse it instead of creating a duplicate. Vendor aliases are
        // globally unique on (alias, source), so re-creating an existing name
        // would otherwise crash on the alias insert (1062 duplicate) and leave an
        // orphaned vendor row behind.
        $existing = Vendor::whereRaw('LOWER(vendor_name) = ?', [mb_strtolower($vendorName)])->first();
        if (! $existing) {
            $existing = optional(
                VendorAlias::whereRaw('LOWER(alias) = ?', [mb_strtolower($vendorName)])->first()
            )->vendor;
        }

        if ($existing) {
            // Backfill a default COA / store link if the caller supplied one.
            if ($request->filled('default_coa_id') && ! $existing->default_coa_id) {
                $existing->update(['default_coa_id' => $request->default_coa_id]);
            }
            $storeIds = $this->restrictToAccessibleStores($request->store_ids);
            if (! empty($storeIds)) {
                $existing->stores()->syncWithoutDetaching($storeIds);
            }

            return response()->json([
                'message' => 'Vendor already existed and was selected.',
                'data' => $existing->load(['defaultCoa', 'stores', 'aliases']),
            ], 200);
        }

        // Create the vendor and its aliases atomically so a failed alias never
        // leaves a half-created vendor behind. firstOrCreate guards against a
        // stray alias left by an earlier partial failure.
        $storeIds = $this->restrictToAccessibleStores($request->store_ids);
        $vendor = DB::transaction(function () use ($request, $vendorName, $storeIds) {
            $vendor = Vendor::create([
                'vendor_name' => $vendorName,
                'vendor_identifier' => $request->vendor_identifier,
                'vendor_type' => $request->vendor_type,
                'default_coa_id' => $request->default_coa_id,
                'default_transaction_type_id' => $request->default_transaction_type_id,
                'contact_name' => $request->contact_name,
                'contact_email' => $request->contact_email,
                'contact_phone' => $request->contact_phone,
                'website' => $request->website,
                'address' => $request->address,
                'notes' => $request->notes,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            if (! empty($storeIds)) {
                $vendor->stores()->sync($storeIds);
            }

            VendorAlias::firstOrCreate(
                ['alias' => $vendor->vendor_name, 'source' => 'manual'],
                ['vendor_id' => $vendor->id]
            );

            if ($request->vendor_identifier && strtoupper($request->vendor_identifier) !== strtoupper($vendor->vendor_name)) {
                VendorAlias::firstOrCreate(
                    ['alias' => $request->vendor_identifier, 'source' => 'manual'],
                    ['vendor_id' => $vendor->id]
                );
            }

            return $vendor;
        });

        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => $vendor->load(['defaultCoa', 'stores', 'aliases'])
        ], 201);
    }

    /**
     * Display the specified vendor
     */
    public function show($id)
    {
        $vendor = Vendor::with(['defaultCoa', 'stores', 'aliases', 'creator'])->findOrFail($id);

        // Items this vendor supplies, limited to stores the viewer can see, so
        // the vendor panel does not leak another owner's item list.
        $vendor->setRelation('inventoryItems', $vendor->inventoryItems()
            ->whereIn('inventory_items.store_id', auth()->user()->getAccessibleStoreIds())
            ->with('store:id,store_info')
            ->orderBy('inventory_items.name')
            ->get());

        return response()->json($vendor);
    }

    /**
     * Update the specified vendor
     */
    public function update(UpdateVendorRequest $request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update($request->only([
            'vendor_name',
            'vendor_identifier',
            'vendor_type',
            'default_coa_id',
            'default_transaction_type_id',
            'contact_name',
            'contact_email',
            'contact_phone',
            'website',
            'address',
            'notes',
            'is_active',
        ]));

        // Update store assignments (restricted to stores the user can access).
        if ($request->has('store_ids')) {
            $vendor->stores()->sync($this->restrictToAccessibleStores($request->store_ids));
        }

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => $vendor->load(['defaultCoa', 'stores', 'aliases'])
        ]);
    }

    /**
     * Hide (soft-delete) the specified vendor.
     *
     * Refused while inventory items are still assigned, because an order built
     * from those items would silently lose its supplier. The blocking items are
     * returned so the UI can name them instead of showing a bare failure.
     */
    public function destroy($id)
    {
        // Authorization check - only admin can delete
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::findOrFail($id);
        $blocking = $this->assignedInventoryItems($vendor);

        if ($blocking->isNotEmpty()) {
            return response()->json([
                'error' => 'This vendor still supplies '.$blocking->count().' inventory '
                    .Str::plural('item', $blocking->count())
                    .'. Reassign them to another vendor first, or set the vendor inactive instead.',
                'items' => $blocking->values(),
            ], 422);
        }

        // Soft delete: expense history, aliases and store assignments all survive,
        // so the vendor can be restored later.
        $vendor->delete();

        return response()->json([
            'message' => 'Vendor hidden successfully. Expense history is unchanged.',
        ]);
    }

    /**
     * Restore a hidden vendor.
     */
    public function restore($id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::onlyTrashed()->findOrFail($id);
        $vendor->restore();

        return response()->json([
            'message' => 'Vendor restored successfully',
            'data' => $vendor->fresh()->load(['defaultCoa', 'stores', 'aliases']),
        ]);
    }

    /**
     * Flip a vendor between active and inactive without deleting it. Inactive
     * vendors stay attached to their items and history, they just drop out of
     * the ordering dropdowns.
     */
    public function toggleActive($id)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::findOrFail($id);
        $vendor->update(['is_active' => ! $vendor->is_active]);

        return response()->json([
            'message' => $vendor->is_active ? 'Vendor activated' : 'Vendor deactivated',
            'data' => ['id' => $vendor->id, 'is_active' => $vendor->is_active],
        ]);
    }

    /**
     * Distinct inventory items tied to this vendor, whether through the
     * item/vendor pivot or by being the item's preferred vendor.
     */
    private function assignedInventoryItems(Vendor $vendor)
    {
        return $vendor->inventoryItems()
            ->select('inventory_items.id', 'inventory_items.name', 'inventory_items.store_id')
            ->get()
            ->concat(
                $vendor->preferredForItems()
                    ->select('inventory_items.id', 'inventory_items.name', 'inventory_items.store_id')
                    ->get()
            )
            ->unique('id')
            ->map(fn ($item) => ['id' => $item->id, 'name' => $item->name]);
    }

    /**
     * The editable vendor columns, pulled off a validated request.
     */
    private function vendorAttributes($request): array
    {
        return $request->only([
            'vendor_identifier',
            'vendor_type',
            'default_coa_id',
            'default_transaction_type_id',
            'contact_name',
            'contact_email',
            'contact_phone',
            'website',
            'address',
            'notes',
        ]);
    }

    /**
     * Add alias to vendor
     */
    public function addAlias(Request $request, $id)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'alias' => 'required|string|max:100',
            'source' => 'required|in:bank,credit_card,manual',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check for duplicate alias with same source
        $existing = VendorAlias::where('alias', $request->alias)
            ->where('source', $request->source)
            ->where('vendor_id', '!=', $vendor->id)
            ->exists();

        if ($existing) {
            return response()->json(['error' => 'Alias already exists for another vendor'], 422);
        }

        $alias = VendorAlias::create([
            'vendor_id' => $vendor->id,
            'alias' => $request->alias,
            'source' => $request->source
        ]);

        return response()->json([
            'message' => 'Alias added successfully',
            'data' => $alias
        ], 201);
    }

    /**
     * Fuzzy match vendor by description (for CSV imports)
     */
    public function match(Request $request)
    {
        $description = $request->get('description');
        
        if (!$description) {
            return response()->json(['error' => 'Description parameter required'], 422);
        }

        // Clean the description
        $searchTerm = strtoupper(trim($description));
        
        // Get all vendors with their aliases
        $vendors = Vendor::with('aliases')->active()->get();
        
        $bestMatch = null;
        $bestScore = 0;
        $matchType = null;

        foreach ($vendors as $vendor) {
            // Check vendor name
            $nameSimilarity = $this->calculateSimilarity($searchTerm, strtoupper($vendor->vendor_name));
            
            // Check vendor identifier
            $identifierSimilarity = 0;
            if ($vendor->vendor_identifier) {
                $identifierSimilarity = $this->calculateSimilarity($searchTerm, strtoupper($vendor->vendor_identifier));
            }
            
            // Check aliases
            $aliasSimilarity = 0;
            foreach ($vendor->aliases as $alias) {
                $similarity = $this->calculateSimilarity($searchTerm, strtoupper($alias->alias));
                if ($similarity > $aliasSimilarity) {
                    $aliasSimilarity = $similarity;
                }
            }
            
            // Get the best match for this vendor
            $maxSimilarity = max($nameSimilarity, $identifierSimilarity, $aliasSimilarity);
            
            if ($maxSimilarity > $bestScore) {
                $bestScore = $maxSimilarity;
                $bestMatch = $vendor;
                
                // Determine match type
                if ($maxSimilarity === $nameSimilarity) {
                    $matchType = 'name';
                } elseif ($maxSimilarity === $identifierSimilarity) {
                    $matchType = 'identifier';
                } else {
                    $matchType = 'alias';
                }
            }
        }

        // Only return if similarity is above 60% threshold
        if ($bestMatch && $bestScore >= 60) {
            return response()->json([
                'match' => true,
                'confidence' => round($bestScore, 2),
                'vendor' => $bestMatch->load('defaultCoa'),
                'match_type' => $matchType
            ]);
        }

        return response()->json([
            'match' => false,
            'confidence' => 0,
            'suggestion' => null
        ]);
    }

    /**
     * Calculate similarity percentage between two strings
     * Using Levenshtein distance algorithm
     */
    private function calculateSimilarity($string1, $string2)
    {
        // Remove common noise from strings
        $string1 = $this->normalizeString($string1);
        $string2 = $this->normalizeString($string2);
        
        // Exact match
        if ($string1 === $string2) {
            return 100;
        }
        
        // Check for substring match
        if (strpos($string1, $string2) !== false || strpos($string2, $string1) !== false) {
            $minLen = min(strlen($string1), strlen($string2));
            $maxLen = max(strlen($string1), strlen($string2));
            return ($minLen / $maxLen) * 90; // Max 90% for substring match
        }
        
        // Use Levenshtein distance
        $distance = levenshtein($string1, $string2);
        $maxLen = max(strlen($string1), strlen($string2));
        
        if ($maxLen === 0) {
            return 0;
        }
        
        $similarity = (1 - ($distance / $maxLen)) * 100;
        return max(0, $similarity);
    }

    /**
     * Normalize string for better matching
     */
    private function normalizeString($string)
    {
        // Remove common prefixes and suffixes
        $string = preg_replace('/\b(SQ\s*\*\s*|[*#])\s*/i', '', $string);
        $string = preg_replace('/\s+/', ' ', $string); // Multiple spaces to single space
        $string = trim($string);
        return strtoupper($string);
    }
}
