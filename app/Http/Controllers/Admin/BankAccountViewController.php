<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Store;
use Illuminate\Http\Request;

class BankAccountViewController extends Controller
{
    public function index(Request $request)
    {
        $query = BankAccount::with('store');

        // Filters
        if ($request->has('store_id') && $request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('account_type') && $request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        $accounts = $query->orderBy('bank_name')->paginate(25);
        $stores = Store::all();

        return view('admin.bank.accounts.index', compact('accounts', 'stores'));
    }

    public function show($id)
    {
        $account = BankAccount::with(['store', 'transactions'])->findOrFail($id);
        
        return view('admin.bank.accounts.show', compact('account'));
    }

    public function reconciliation(Request $request, $accountId)
    {
        $account = BankAccount::with('store')->findOrFail($accountId);
        
        // Get filter parameters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $status = $request->input('status');
        
        // Query bank transactions
        $query = $account->transactions()->orderBy('transaction_date', 'desc');
        
        // Apply filters
        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }
        
        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }
        
        if ($status) {
            $query->where('reconciliation_status', $status);
        }
        
        // Get counts for summary
        $unmatchedCount = $account->transactions()->where('reconciliation_status', 'unmatched')->count();
        $matchedCount = $account->transactions()->where('reconciliation_status', 'matched')->count();
        $reviewedCount = $account->transactions()->where('reconciliation_status', 'reviewed')->count();
        
        // Paginate results
        $transactions = $query->paginate(25);
        
        return view('admin.bank.reconciliation.index', compact(
            'account', 
            'transactions', 
            'unmatchedCount', 
            'matchedCount', 
            'reviewedCount',
            'startDate',
            'endDate',
            'status'
        ));
    }
}
