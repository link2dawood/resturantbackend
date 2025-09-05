<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\DailyReport;
use App\Models\Store;

class CheckDailyReportAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Admins have full access
        if ($user->isAdmin()) {
            return $next($request);
        }
        
        // Get the daily report from route parameter
        $dailyReportId = $request->route('dailyReport');
        if ($dailyReportId) {
            $dailyReport = is_object($dailyReportId) ? $dailyReportId : DailyReport::find($dailyReportId);
            
            if ($dailyReport) {
                // Check if user has access to this report's store
                if (!$this->hasStoreAccess($user, $dailyReport->store_id)) {
                    abort(403, 'You do not have access to reports for this store.');
                }
            }
        }
        
        // For store-specific routes, check store access
        $storeId = $request->route('store') ?? $request->input('store_id');
        if ($storeId) {
            $storeId = is_object($storeId) ? $storeId->id : $storeId;
            
            if (!$this->hasStoreAccess($user, $storeId)) {
                abort(403, 'You do not have access to this store.');
            }
        }
        
        return $next($request);
    }
    
    /**
     * Check if user has access to a specific store
     */
    private function hasStoreAccess($user, $storeId)
    {
        return $user->hasStoreAccess($storeId);
    }
}
