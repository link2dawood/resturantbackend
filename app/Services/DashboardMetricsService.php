<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\KpiTarget;
use Illuminate\Support\Carbon;

/**
 * Phase 4 — Dashboard circular metrics.
 *
 * Computes the four headline ring metrics (Sales, Food Cost %, Payroll Cost %,
 * Rent Cost %) for a date range. Queries run through the tenant-scoped
 * DailyReport / ExpenseTransaction models, so each user only sees their own
 * stores' numbers automatically.
 *
 * Each metric returns a normalized shape consumed by the Blade ring partial:
 *   has_data   bool    — false → render greyed-out
 *   percent    float   — 0..100, the ring fill
 *   variance   float   — signed; for Sales it's $, for cost % it's percentage points
 *   ahead      bool    — true → ahead of / better than projection (green)
 */
class DashboardMetricsService
{
    public function forUser(Carbon $start, Carbon $end): array
    {
        $netSales = (float) DailyReport::whereBetween('report_date', [$start, $end])->sum('net_sales');
        $projectedSales = (float) DailyReport::whereBetween('report_date', [$start, $end])->sum('projected_sales');
        $hasReports = DailyReport::whereBetween('report_date', [$start, $end])->exists();

        return [
            'sales' => $this->salesMetric($netSales, $projectedSales, $hasReports),
            'food' => $this->costMetric('Food Cost', 'food', $netSales, $hasReports, $start, $end),
            'payroll' => $this->costMetric('Payroll Cost', 'payroll', $netSales, $hasReports, $start, $end),
            'rent' => $this->costMetric('Rental Cost', 'rent', $netSales, $hasReports, $start, $end),
        ];
    }

    private function salesMetric(float $actual, float $projected, bool $hasReports): array
    {
        $hasData = $hasReports && $projected > 0;
        $variance = $actual - $projected;

        return [
            'key' => 'sales',
            'label' => 'Sales',
            'format' => 'currency',
            'has_data' => $hasData,
            'actual' => $actual,
            'projected' => $projected,
            // Ring fill = how much of the projection was achieved (capped at 100%).
            'percent' => $hasData ? min(100, round(($actual / $projected) * 100, 1)) : 0.0,
            'display' => $this->money($actual),
            'sub' => $hasData ? 'of '.$this->money($projected).' projected' : null,
            'variance' => round($variance, 2),
            'variance_label' => $hasData
                ? $this->money(abs($variance)).' '.($variance >= 0 ? 'ahead' : 'behind')
                : null,
            'ahead' => $variance >= 0,
        ];
    }

    private function costMetric(string $label, string $key, float $netSales, bool $hasReports, Carbon $start, Carbon $end): array
    {
        $codes = config("dashboard.coa.{$key}", []);
        $target = $this->resolveTarget($key);

        $spend = (float) ExpenseTransaction::whereBetween('transaction_date', [$start, $end])
            ->whereHas('coa', fn ($q) => $q->whereIn('account_code', $codes))
            ->sum('amount');

        $hasData = $hasReports && $netSales > 0;
        $pct = $hasData ? round(($spend / $netSales) * 100, 1) : 0.0;
        // For costs, lower than target is good ("ahead"); over target is "behind".
        $variance = round($pct - $target, 1);

        return [
            'key' => $key,
            'label' => $label,
            'format' => 'percent',
            'has_data' => $hasData,
            'amount' => $spend,
            'target' => $target,
            'percent' => $hasData ? min(100, $pct) : 0.0,
            'display' => $hasData ? $pct.'%' : '—',
            'sub' => $hasData ? $this->money($spend).' of sales' : null,
            'variance' => $variance,
            'variance_label' => $hasData
                ? abs($variance).' pts '.($variance <= 0 ? 'under' : 'over').' target ('.rtrim(rtrim((string) $target, '0'), '.').'%)'
                : null,
            'ahead' => $variance <= 0,
        ];
    }

    /**
     * The target % for a cost metric: the user-defined per-store KPI target
     * (averaged across the user's accessible stores via the tenant-scoped
     * KpiTarget model), falling back to the config default when none is set.
     */
    private function resolveTarget(string $key): float
    {
        $column = KpiTarget::COLUMN_FOR[$key] ?? null;

        if ($column) {
            // AVG ignores NULLs; returns null when the user has no targets at all.
            $avg = KpiTarget::avg($column);

            if ($avg !== null) {
                return round((float) $avg, 2);
            }
        }

        return (float) config("dashboard.targets.{$key}", 0);
    }

    private function money(float $value): string
    {
        return '$'.number_format($value, 2);
    }
}
