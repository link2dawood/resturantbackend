<?php

namespace App\Services;

use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\KpiTarget;
// Base Carbon so callers can pass either Carbon\Carbon or Illuminate\Support\Carbon
// (the latter extends the former); DashboardController passes Carbon\Carbon.
use Carbon\Carbon;

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
        // net_sales / gross_sales are COMPUTED from the revenue line items (the
        // cached columns are often 0 for imported data), so sum the accessor.
        // withSum preloads the revenue total so the accessor needs no extra query.
        $reports = DailyReport::withSum('revenues', 'amount')
            ->whereBetween('report_date', [$start, $end])
            ->get();

        $netSales = round((float) $reports->sum(fn ($r) => $this->reportNetSales($r)), 2);
        $projectedSales = round((float) $reports->sum(fn ($r) => (float) $r->projected_sales), 2);
        $hasReports = $reports->isNotEmpty();

        return [
            'sales' => $this->salesMetric($netSales, $projectedSales, $hasReports),
            'food' => $this->costMetric('Food Cost', 'food', $netSales, $hasReports, $start, $end),
            'payroll' => $this->costMetric('Payroll Cost', 'payroll', $netSales, $hasReports, $start, $end),
            'rent' => $this->costMetric('Rental Cost', 'rent', $netSales, $hasReports, $start, $end),
        ];
    }

    private function salesMetric(float $actual, float $projected, bool $hasReports): array
    {
        // Show actual sales whenever there are reports with sales — a projection is
        // optional (imported reports often have none).
        $hasData = $hasReports && ($actual > 0 || $projected > 0);
        $hasProjection = $projected > 0;
        $variance = $actual - $projected;

        return [
            'key' => 'sales',
            'label' => 'Sales',
            'format' => 'currency',
            'has_data' => $hasData,
            'actual' => $actual,
            'projected' => $projected,
            // Ring fill = % of projection achieved (capped); full ring when there's
            // no projection to measure against.
            'percent' => $hasProjection ? min(100, round(($actual / $projected) * 100, 1)) : ($hasData ? 100.0 : 0.0),
            'display' => $this->money($actual),
            'sub' => $hasData
                ? ($hasProjection ? 'of '.$this->money($projected).' projected' : 'actual sales')
                : null,
            'variance' => round($variance, 2),
            'variance_label' => $hasProjection
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

    /**
     * Net sales for a single report. Prefers the value computed from revenue
     * line items; falls back to the stored net_sales / gross_sales columns for
     * imported or legacy reports that recorded totals on the row instead.
     */
    private function reportNetSales(DailyReport $report): float
    {
        $fromRevenues = (float) $report->net_sales; // accessor (uses revenues)
        if (abs($fromRevenues) > 0.001) {
            return $fromRevenues;
        }

        $netColumn = (float) $report->getRawOriginal('net_sales');
        if (abs($netColumn) > 0.001) {
            return $netColumn;
        }

        return (float) $report->getRawOriginal('gross_sales');
    }

    private function money(float $value): string
    {
        return '$'.number_format($value, 2);
    }
}
