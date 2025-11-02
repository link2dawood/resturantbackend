<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Store;
use Illuminate\Http\Request;

class ChartOfAccountViewController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartOfAccount::with(['stores']);

        // Apply filters
        if ($request->has('account_type') && $request->account_type) {
            $query->where('account_type', $request->account_type);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('account_code', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%");
            });
        }

        if ($request->has('store_id') && $request->store_id) {
            $query->whereHas('stores', function($q) use ($request) {
                $q->where('stores.id', $request->store_id);
            });
        }

        $coas = $query->orderBy('account_code', 'asc')->paginate(25);
        $stores = Store::all();
        $parentAccounts = ChartOfAccount::whereNull('parent_account_id')->where('is_active', true)->orderBy('account_name')->get();

        return view('admin.coa.index', compact('coas', 'stores', 'parentAccounts'));
    }
}
