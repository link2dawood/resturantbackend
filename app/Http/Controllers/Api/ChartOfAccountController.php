<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of COA entries
     */
    public function index(Request $request)
    {
        // If browser request (wants HTML), redirect to web UI
        if (!$request->wantsJson() && $request->accepts(['text/html', 'application/xhtml+xml'])) {
            return redirect('/chart-of-accounts');
        }

        $query = ChartOfAccount::with(['stores', 'parent', 'creator']);

        // Filters
        if ($request->has('store_id')) {
            $query->whereHas('stores', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->has('account_type')) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        $coas = $query->paginate($request->per_page ?? 25);

        return response()->json($coas);
    }

    /**
     * Store a newly created COA entry
     */
    public function store(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'account_code' => 'required|string|max:10|unique:chart_of_accounts',
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|in:Assets,Liability,Equity,Taxes,Revenue,COGS,Expense,Adjustments',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'is_global' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coa = ChartOfAccount::create([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_account_id' => $request->parent_account_id,
            'is_active' => true,
            'is_system_account' => false,
            'created_by' => auth()->id(),
        ]);

        // Attach stores
        if ($request->has('store_ids') && !$request->is_global) {
            foreach ($request->store_ids as $storeId) {
                $coa->stores()->attach($storeId, ['is_global' => false]);
            }
        }

        return response()->json([
            'message' => 'Chart of Account created successfully',
            'data' => $coa->load('stores')
        ], 201);
    }

    /**
     * Display the specified COA entry
     */
    public function show(Request $request, $id)
    {
        // If browser request (wants HTML), redirect to web UI
        if (!$request->wantsJson() && $request->accepts(['text/html', 'application/xhtml+xml'])) {
            return redirect()->route('coa.show', $id);
        }

        $coa = ChartOfAccount::with(['stores', 'parent', 'children', 'creator'])->findOrFail($id);
        
        return response()->json([
            'data' => $coa
        ]);
    }

    /**
     * Update the specified COA entry
     */
    public function update(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $coa = ChartOfAccount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'account_code' => 'required|string|max:10|unique:chart_of_accounts,account_code,' . $id,
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|in:Assets,Liability,Equity,Taxes,Revenue,COGS,Expense,Adjustments',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $coa->update($request->only([
            'account_code',
            'account_name',
            'account_type',
            'parent_account_id',
            'is_active'
        ]));

        // Update store assignments
        if ($request->has('store_ids')) {
            $coa->stores()->sync([]);
            foreach ($request->store_ids as $storeId) {
                $coa->stores()->attach($storeId, ['is_global' => $request->boolean('is_global', false)]);
            }
        }

        return response()->json([
            'message' => 'Chart of Account updated successfully',
            'data' => $coa->load('stores')
        ]);
    }

    /**
     * Delete the specified COA entry
     */
    public function destroy($id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $coa = ChartOfAccount::findOrFail($id);

        // Check if has transactions (we'll add this relationship later)
        // if ($coa->expenseTransactions()->exists()) {
        //     return response()->json(['error' => 'Cannot delete COA with linked transactions'], 403);
        // }

        // Hard delete
        $coa->delete();

        return response()->json([
            'message' => 'Chart of Account deleted successfully'
        ]);
    }
}
