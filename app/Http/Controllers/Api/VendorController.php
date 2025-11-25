<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorAlias;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors
     */
    public function index(Request $request)
    {
        $query = Vendor::with(['defaultCoa', 'stores', 'creator']);

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
    public function store(Request $request)
    {
        // Authorization check - only admin/owner can create
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'required|string|max:100',
            'vendor_identifier' => 'nullable|string|max:100|unique:vendors',
            'vendor_type' => 'required|in:Food,Beverage,Supplies,Utilities,Services,Other',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
            'default_transaction_type_id' => 'nullable|exists:transaction_types,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vendor = Vendor::create([
            'vendor_name' => $request->vendor_name,
            'vendor_identifier' => $request->vendor_identifier,
            'vendor_type' => $request->vendor_type,
            'default_coa_id' => $request->default_coa_id,
            'default_transaction_type_id' => $request->default_transaction_type_id,
            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'address' => $request->address,
            'notes' => $request->notes,
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        // Attach stores if provided
        if ($request->has('store_ids') && is_array($request->store_ids) && count($request->store_ids) > 0) {
            $vendor->stores()->sync($request->store_ids);
        }

        // Create initial alias from vendor name
        VendorAlias::create([
            'vendor_id' => $vendor->id,
            'alias' => $vendor->vendor_name,
            'source' => 'manual'
        ]);

        // Create alias from vendor_identifier if different
        if ($request->vendor_identifier && strtoupper($request->vendor_identifier) !== strtoupper($vendor->vendor_name)) {
            VendorAlias::create([
                'vendor_id' => $vendor->id,
                'alias' => $request->vendor_identifier,
                'source' => 'manual'
            ]);
        }

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
        return response()->json($vendor);
    }

    /**
     * Update the specified vendor
     */
    public function update(Request $request, $id)
    {
        // Authorization check - only admin/owner can update
        $user = auth()->user();
        if (!$user->isAdmin() && !$user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor_name' => 'required|string|max:100',
            'vendor_identifier' => 'nullable|string|max:100|unique:vendors,vendor_identifier,' . $id,
            'vendor_type' => 'required|in:Food,Beverage,Supplies,Utilities,Services,Other',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'contact_name' => 'nullable|string|max:100',
            'contact_email' => 'nullable|email|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vendor->update($request->only([
            'vendor_name',
            'vendor_identifier',
            'vendor_type',
            'default_coa_id',
            'contact_name',
            'contact_email',
            'contact_phone',
            'address',
            'notes',
            'is_active'
        ]));

        // Update store assignments
        if ($request->has('store_ids')) {
            $vendor->stores()->sync($request->store_ids ?? []);
        }

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => $vendor->load(['defaultCoa', 'stores', 'aliases'])
        ]);
    }

    /**
     * Soft delete (deactivate) the specified vendor
     */
    public function destroy($id)
    {
        // Authorization check - only admin can delete
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $vendor = Vendor::findOrFail($id);

        // Check if vendor has transactions (we'll add this relationship later)
        // if ($vendor->expenses()->exists()) {
        //     return response()->json(['error' => 'Cannot delete vendor with linked transactions'], 403);
        // }

        // Soft delete (deactivate)
        $vendor->update(['is_active' => false]);

        return response()->json([
            'message' => 'Vendor deactivated successfully'
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
