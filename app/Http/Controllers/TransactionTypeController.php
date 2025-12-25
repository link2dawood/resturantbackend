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
        $transactionTypes = TransactionType::with('defaultCoa', 'parent', 'children')->get();
        $parentTransactionTypes = TransactionType::whereNull('p_id')->get();

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

        return redirect()->route('transaction-types.index')->with('success', 'Transaction Type updated successfully.');
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
        $parentCategories = TransactionType::whereNull('p_id')->get()->keyBy(function($item) {
            return strtolower($item->name);
        });

        // Get transaction types that don't have a parent (children, not categories themselves)
        $transactionTypes = TransactionType::whereNull('p_id')
            ->whereDoesntHave('children') // Not a category (has no children)
            ->get();

        $assigned = 0;
        $skipped = 0;

        foreach ($transactionTypes as $type) {
            // Skip if already has a category
            if ($type->p_id !== null) {
                $skipped++;
                continue;
            }

            $matchedCategory = $this->findMatchingCategory($type->name, $parentCategories);
            
            if ($matchedCategory && $matchedCategory->id != $type->id) {
                $type->update(['p_id' => $matchedCategory->id]);
                $assigned++;
            } else {
                $skipped++;
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
        $descLower = strtolower(trim($descriptionName));

        // Exact match (case-insensitive)
        if (isset($parentCategories[$descLower])) {
            return $parentCategories[$descLower];
        }

        // Partial match
        foreach ($parentCategories as $category) {
            $catLower = strtolower($category->name);
            if (strpos($descLower, $catLower) !== false || strpos($catLower, $descLower) !== false) {
                return $category;
            }
        }

        // Word-by-word matching
        $descWords = explode(' ', $descLower);
        foreach ($parentCategories as $category) {
            $catWords = explode(' ', strtolower($category->name));
            foreach ($descWords as $descWord) {
                foreach ($catWords as $catWord) {
                    if (strlen($descWord) >= 3 && strlen($catWord) >= 3) {
                        if (strpos($descWord, $catWord) !== false || strpos($catWord, $descWord) !== false) {
                            return $category;
                        }
                    }
                }
            }
        }

        return null;
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
