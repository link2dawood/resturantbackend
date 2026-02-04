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

        // Paginate expenses
        $expenses = $query->orderBy('transaction_date', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->paginate(50);

        // Get accessible stores
        $stores = Store::whereIn('id', $accessibleStoreIds)->get();

        // Get statistics for summary cards
        $statsQuery = ExpenseTransaction::whereIn('store_id', $accessibleStoreIds);
        
        // Apply same filters for stats
        if ($request->has('store_id') && $request->store_id && $user->hasStoreAccess($request->store_id)) {
            $statsQuery->where('store_id', $request->store_id);
        }
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $statsQuery->byDateRange($request->start_date, $request->end_date);
        }
        if ($request->has('transaction_type') && $request->transaction_type) {
            $statsQuery->byType($request->transaction_type);
        }
        if ($request->has('coa_id') && $request->coa_id) {
            $statsQuery->forCoa($request->coa_id);
        }

        // Calculate detailed statistics
        $statsQueryClone = clone $statsQuery;
        $stats = [
            'total_count' => $statsQuery->count(),
            'total_amount' => $statsQuery->sum('amount'),
            'cash_count' => (clone $statsQuery)->where('transaction_type', 'cash')->count(),
            'cash_amount' => (clone $statsQuery)->where('transaction_type', 'cash')->sum('amount'),
            'credit_card_count' => (clone $statsQuery)->where('transaction_type', 'credit_card')->count(),
            'credit_card_amount' => (clone $statsQuery)->where('transaction_type', 'credit_card')->sum('amount'),
            'bank_transfer_count' => (clone $statsQuery)->where('transaction_type', 'bank_transfer')->count(),
            'bank_transfer_amount' => (clone $statsQuery)->where('transaction_type', 'bank_transfer')->sum('amount'),
            'check_count' => (clone $statsQuery)->where('transaction_type', 'check')->count(),
            'check_amount' => (clone $statsQuery)->where('transaction_type', 'check')->sum('amount'),
            'with_coa_count' => (clone $statsQuery)->whereNotNull('coa_id')->count(),
            'without_coa_count' => (clone $statsQuery)->whereNull('coa_id')->count(),
        ];

        // Get summary by COA
        $summaryByCoa = ExpenseTransaction::selectRaw('
                coa_id,
                chart_of_accounts.account_code,
                chart_of_accounts.account_name,
                chart_of_accounts.account_type,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount
            ')
            ->leftJoin('chart_of_accounts', 'expense_transactions.coa_id', '=', 'chart_of_accounts.id')
            ->whereIn('expense_transactions.store_id', $accessibleStoreIds);
        
        // Apply same filters for COA summary
        if ($request->has('store_id') && $request->store_id && $user->hasStoreAccess($request->store_id)) {
            $summaryByCoa->where('expense_transactions.store_id', $request->store_id);
        }
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $summaryByCoa->whereBetween('expense_transactions.transaction_date', [$request->start_date, $request->end_date]);
        }
        if ($request->has('transaction_type') && $request->transaction_type) {
            $summaryByCoa->where('expense_transactions.transaction_type', $request->transaction_type);
        }
        if ($request->has('coa_id') && $request->coa_id) {
            $summaryByCoa->where('expense_transactions.coa_id', $request->coa_id);
        }
        
        $summaryByCoa = $summaryByCoa->groupBy('coa_id', 'chart_of_accounts.account_code', 'chart_of_accounts.account_name', 'chart_of_accounts.account_type')
            ->orderBy('total_amount', 'desc')
            ->limit(10)
            ->get();

        // Get summary by store
        $summaryByStore = ExpenseTransaction::selectRaw('
                store_id,
                stores.store_info as store_name,
                COUNT(*) as transaction_count,
                SUM(amount) as total_amount
            ')
            ->join('stores', 'expense_transactions.store_id', '=', 'stores.id')
            ->whereIn('expense_transactions.store_id', $accessibleStoreIds);
        
        // Apply same filters for store summary
        if ($request->has('store_id') && $request->store_id && $user->hasStoreAccess($request->store_id)) {
            $summaryByStore->where('expense_transactions.store_id', $request->store_id);
        }
        if ($request->has('start_date') && $request->has('end_date') && $request->start_date && $request->end_date) {
            $summaryByStore->whereBetween('expense_transactions.transaction_date', [$request->start_date, $request->end_date]);
        }
        if ($request->has('transaction_type') && $request->transaction_type) {
            $summaryByStore->where('expense_transactions.transaction_type', $request->transaction_type);
        }
        if ($request->has('coa_id') && $request->coa_id) {
            $summaryByStore->where('expense_transactions.coa_id', $request->coa_id);
        }
        
        $summaryByStore = $summaryByStore->groupBy('store_id', 'stores.store_info')
            ->orderBy('total_amount', 'desc')
            ->get();

        return view('admin.expenses.index', compact('expenses', 'total', 'stores', 'stats', 'summaryByCoa', 'summaryByStore'));
    }
}
