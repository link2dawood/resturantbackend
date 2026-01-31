<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorViewController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with(['stores', 'defaultCoa']);

        // Apply filters
        if ($request->has('store_id') && $request->store_id) {
            $query->whereHas('stores', function($q) use ($request) {
                $q->where('stores.id', $request->store_id);
            });
        }

        if ($request->has('vendor_type') && $request->vendor_type) {
            $query->where('vendor_type', $request->vendor_type);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                  ->orWhere('vendor_identifier', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('has_coa') && $request->has_coa !== '') {
            if ($request->boolean('has_coa')) {
                $query->whereNotNull('default_coa_id');
            } else {
                $query->whereNull('default_coa_id');
            }
        }

        $vendors = $query->orderBy('vendor_name', 'asc')->paginate(25);
        $stores = Store::all();
        $coas = ChartOfAccount::where('account_code', '>', 5000)
        ->where('account_code', '<', 7000)->where('is_active', true)->orderBy('account_name')->get();

        return view('admin.vendors.index', compact('vendors', 'stores', 'coas'));
    }
}
