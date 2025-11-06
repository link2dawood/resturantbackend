<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseTransaction;
use App\Models\Store;
use Illuminate\Http\Request;

class ExpenseViewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Filter by accessible stores
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        
        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator', 'dailyReport'])
            ->whereIn('store_id', $accessibleStoreIds);

        // Apply filters from request
        if ($request->has('store_id') && $request->store_id) {
            // Ensure user has access to the requested store
            if ($user->hasStoreAccess($request->store_id)) {
                $query->where('store_id', $request->store_id);
            }
        }

        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('transaction_type') && $request->transaction_type) {
            $query->byType($request->transaction_type);
        }

        if ($request->has('needs_review')) {
            if ($request->boolean('needs_review')) {
                $query->needsReview();
            } else {
                $query->where('needs_review', false);
            }
        }

        if ($request->has('vendor_id') && $request->vendor_id) {
            $query->forVendor($request->vendor_id);
        }

        if ($request->has('coa_id') && $request->coa_id) {
            $query->forCoa($request->coa_id);
        }

        // Get total sum before pagination
        $total = $query->sum('amount');

        // Now paginate
        $expenses = $query->orderBy('transaction_date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(50);

        // Get accessible stores
        $stores = Store::whereIn('id', $accessibleStoreIds)->get();

        return view('admin.expenses.index', compact('expenses', 'total', 'stores'));
    }
}
