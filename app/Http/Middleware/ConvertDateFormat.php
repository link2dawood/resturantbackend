<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class ConvertDateFormat
{
    /**
     * Convert US-style dates (MM/DD/YYYY or MM-DD-YYYY, with 1–2 digit month/day) to Y-m-d for Laravel.
     */
    public function handle(Request $request, Closure $next)
    {
        $dateFields = [
            'report_date',
            'corporate_creation_date',
            'date_from',
            'date_to',
            'start_date',
            'end_date',
            'from_date',
            'to_date',
            'transaction_date',
        ];

        foreach ($dateFields as $field) {
            if (! $request->has($field)) {
                continue;
            }
            $dateValue = $request->get($field);
            if ($dateValue === '' || $dateValue === null) {
                continue;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                continue;
            }

            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateValue, $m)) {
                try {
                    $convertedDate = sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[1], (int) $m[2]);
                    $parsed = Carbon::createFromFormat('Y-m-d', $convertedDate);
                    if ($parsed->format('Y-m-d') !== $convertedDate) {
                        continue;
                    }
                    $request->merge([$field => $convertedDate]);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return $next($request);
    }
}
