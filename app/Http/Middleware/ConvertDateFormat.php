<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ConvertDateFormat
{
    /**
     * Handle an incoming request.
     * Convert MM-DD-YYYY format dates to YYYY-MM-DD for Laravel
     */
    public function handle(Request $request, Closure $next)
    {
        $dateFields = [
            'report_date',
            'corporate_creation_date', 
            'date_from',
            'date_to'
        ];
        
        foreach ($dateFields as $field) {
            if ($request->has($field) && $request->get($field)) {
                $dateValue = $request->get($field);
                
                // Check if it's in MM-DD-YYYY format
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $dateValue)) {
                    try {
                        // Convert MM-DD-YYYY to YYYY-MM-DD
                        $parts = explode('-', $dateValue);
                        $convertedDate = $parts[2] . '-' . $parts[0] . '-' . $parts[1];
                        
                        // Validate the date
                        if (Carbon::createFromFormat('Y-m-d', $convertedDate)) {
                            $request->merge([$field => $convertedDate]);
                        }
                    } catch (\Exception $e) {
                        // If conversion fails, let Laravel's validation handle it
                        continue;
                    }
                }
            }
        }
        
        return $next($request);
    }
}