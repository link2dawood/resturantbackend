<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseTransaction;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewQueueViewController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get review queue transactions grouped by reason
        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator'])
            ->where('needs_review', true);

        // Filter by accessible stores
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $query->whereIn('store_id', $accessibleStoreIds);

        // Apply filters
        if ($request->has('store_id') && $request->store_id) {
            if ($user->hasStoreAccess($request->store_id)) {
                $query->where('store_id', $request->store_id);
            }
        }

        if ($request->has('review_reason') && $request->review_reason) {
            $query->where('review_reason', $request->review_reason);
        }

        // Group by reason for display
        $groupedTransactions = $query->orderBy('transaction_date', 'desc')
            ->get()
            ->groupBy('review_reason');

        // Calculate stats
        $stats = [
            'total_pending' => $query->count(),
            'by_reason' => ExpenseTransaction::where('needs_review', true)
                ->whereIn('store_id', $accessibleStoreIds)
                ->select('review_reason', DB::raw('count(*) as count'))
                ->groupBy('review_reason')
                ->pluck('count', 'review_reason'),
            'by_store' => ExpenseTransaction::where('needs_review', true)
                ->whereIn('store_id', $accessibleStoreIds)
                ->select('store_id', DB::raw('count(*) as count'))
                ->groupBy('store_id')
                ->with('store:id,store_info')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->store->store_info ?? 'Unknown' => $item->count];
                })
        ];

        $stores = Store::whereIn('id', $accessibleStoreIds)->get();
        $vendors = Vendor::where('is_active', true)->orderBy('vendor_name')->get();
        $coas = ChartOfAccount::where('is_active', true)->orderBy('account_name')->get();

        return view('admin.review-queue.index', compact('groupedTransactions', 'stats', 'stores', 'vendors', 'coas'));
    }
}
