<?php

namespace App\Http\Controllers;

use App\Models\TransactionType;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactionTypes = TransactionType::with('defaultCoa', 'parent', 'children')
            ->orderBy('name')
            ->get();
        $parentTransactionTypes = TransactionType::whereNull('p_id')
            ->orderBy('name')
            ->get();

        return view('transaction_types.index', compact('transactionTypes', 'parentTransactionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->get();
        $chartOfAccounts = ChartOfAccount::where('account_type', 'Expense')
            ->orWhere('account_type', 'COGS')
            ->active()
            ->orderBy('account_code')
            ->get();

        return view('transaction_types.create', compact('parentTransactionTypes', 'chartOfAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        TransactionType::create($request->only(['name', 'p_id', 'default_coa_id']));

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TransactionType $transactionType)
    {
        $transactionType->load('parent', 'children', 'defaultCoa');

        return view('transaction_types.show', compact('transactionType'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransactionType $transactionType)
    {
        $parentTransactionTypes = TransactionType::whereNull('p_id')->where('id', '!=', $transactionType->id)->get();
        $chartOfAccounts = ChartOfAccount::where('account_type', 'Expense')
            ->orWhere('account_type', 'COGS')
            ->active()
            ->orderBy('account_code')
            ->get();

        return view('transaction_types.edit', compact('transactionType', 'parentTransactionTypes', 'chartOfAccounts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'p_id' => 'nullable|exists:transaction_types,id',
            'default_coa_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $transactionType->update($request->only(['name', 'p_id', 'default_coa_id']));

        return redirect()->route('transaction-types.edit', $transactionType)->with('success', 'Transaction Type updated successfully.');
    }

    /**
     * Update only the category of a transaction type.
     */
    public function updateCategory(Request $request, TransactionType $transactionType)
    {
        $request->validate([
            'p_id' => 'nullable|exists:transaction_types,id',
        ]);

        // Prevent assigning itself as parent
        if ($request->p_id == $transactionType->id) {
            return redirect()->route('transaction-types.index')
                ->withErrors(['error' => 'A transaction type cannot be its own category.']);
        }

        $transactionType->update(['p_id' => $request->p_id]);

        return redirect()->route('transaction-types.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Auto-assign categories to all transaction types without categories.
     */
    public function autoAssignCategories()
    {
        // Get all potential parent categories (all items where p_id is null)
        // These can be categories (have children) or potential categories (no children yet)
        $allParentCategories = TransactionType::whereNull('p_id')->get();
        
        // Build a lookup by lowercase name, excluding items that will be assigned
        $parentCategories = $allParentCategories->keyBy(function($item) {
            return strtolower($item->name);
        });

        // Get all transaction types that need categories assigned:
        // 1. Don't have a parent assigned (p_id is null)
        // 2. Are NOT categories themselves (don't have children)
        $transactionTypes = TransactionType::whereNull('p_id')
            ->whereDoesntHave('children')
            ->get();

        $assigned = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach ($transactionTypes as $type) {
            // Skip if this type is itself a category (has children) - shouldn't happen due to query but safety check
            if ($type->children()->count() > 0) {
                $skipped++;
                $skippedReasons[] = "{$type->name}: Is a category (has children)";
                continue;
            }

            // Find matching category, excluding self
            $availableCategories = $parentCategories->filter(function($cat) use ($type) {
                return $cat->id != $type->id;
            });
            
            $matchedCategory = $this->findMatchingCategory($type->name, $availableCategories);
            
            if ($matchedCategory && $matchedCategory->id != $type->id) {
                $type->update(['p_id' => $matchedCategory->id]);
                $assigned++;
            } else {
                $skipped++;
                // Log for debugging
                \Log::info("Could not match category for: {$type->name}", [
                    'available_categories' => $availableCategories->pluck('name')->toArray()
                ]);
            }
        }

        $message = "Auto-assignment completed. {$assigned} categories assigned, {$skipped} skipped.";
        
        return redirect()->route('transaction-types.index')->with('success', $message);
    }

    /**
     * Find matching category based on description name.
     */
    private function findMatchingCategory(string $descriptionName, $parentCategories)
    {
        if (empty($descriptionName) || $parentCategories->isEmpty()) {
            return null;
        }

        $descLower = strtolower(trim($descriptionName));
        $descWords = array_filter(explode(' ', $descLower), function($word) {
            return strlen($word) >= 2; // Filter out very short words
        });

        // Exact match (case-insensitive)
        if (isset($parentCategories[$descLower])) {
            return $parentCategories[$descLower];
        }

        // Try to find best match
        $bestMatch = null;
        $bestScore = 0;

        foreach ($parentCategories as $category) {
            $catLower = strtolower(trim($category->name));
            
            // Skip if trying to match to itself
            if ($catLower === $descLower) {
                continue;
            }

            $score = 0;

            // Exact match gets highest score
            if ($catLower === $descLower) {
                $score = 100;
            }
            // Full word match
            elseif (strpos($descLower, $catLower) !== false || strpos($catLower, $descLower) !== false) {
                $score = 80;
            }
            // Word-by-word matching
            else {
                $catWords = array_filter(explode(' ', $catLower), function($word) {
                    return strlen($word) >= 2;
                });
                
                $matchedWords = 0;
                foreach ($descWords as $descWord) {
                    foreach ($catWords as $catWord) {
                        if (strlen($descWord) >= 3 && strlen($catWord) >= 3) {
                            // Check if words are similar
                            if ($descWord === $catWord) {
                                $matchedWords++;
                                $score += 30;
                            } elseif (strpos($descWord, $catWord) !== false || strpos($catWord, $descWord) !== false) {
                                $matchedWords++;
                                $score += 20;
                            }
                        }
                    }
                }
                
                // Bonus for multiple word matches
                if ($matchedWords > 1) {
                    $score += 10;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $category;
            }
        }

        // Only return match if score is above threshold
        return $bestScore >= 20 ? $bestMatch : null;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransactionType $transactionType)
    {
        $transactionType->delete();

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type deleted successfully.');
    }
}
