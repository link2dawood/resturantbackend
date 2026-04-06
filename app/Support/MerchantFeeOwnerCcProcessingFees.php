<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Owner CC statement lines assigned to merchant processing fee COAs (credit card fees),
 * scoped the same way as ExpenseTransaction merchant fee queries.
 */
final class MerchantFeeOwnerCcProcessingFees
{
    /**
     * SQL expression for line amount (debit or credit column, whichever is non-zero).
     */
    public static function lineAmountExpression(string $occlAlias = 'occl'): string
    {
        return "CASE WHEN {$occlAlias}.debit > 0 THEN {$occlAlias}.debit WHEN {$occlAlias}.credit > 0 THEN {$occlAlias}.credit ELSE 0 END";
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    public static function baseJoinedQuery(
        array $coaIds,
        ?int $storeId,
        string $startDate,
        string $endDate,
        array $accessibleStoreIds
    ) {
        $q = DB::table('owner_cc_statement_lines as occl')
            ->join('owner_cc_statement_imports as occi', 'occi.id', '=', 'occl.owner_cc_statement_import_id')
            ->whereIn('occl.coa_id', $coaIds)
            ->whereBetween('occl.transaction_date', [$startDate, $endDate])
            ->whereIn(DB::raw('COALESCE(occl.store_id, occi.store_id)'), $accessibleStoreIds);

        if ($storeId) {
            $q->whereRaw('COALESCE(occl.store_id, occi.store_id) = ?', [$storeId]);
        }

        return $q;
    }

    /**
     * Single row for "Credit card statement" bucket in By Processor.
     *
     * @return object{processor: string, total_fees: float|int, transaction_count: int}|null
     */
    public static function byProcessorRow(
        array $coaIds,
        ?int $storeId,
        string $startDate,
        string $endDate,
        array $accessibleStoreIds
    ): ?object {
        if ($coaIds === [] || $accessibleStoreIds === []) {
            return null;
        }

        $amt = self::lineAmountExpression('occl');

        $row = self::baseJoinedQuery($coaIds, $storeId, $startDate, $endDate, $accessibleStoreIds)
            ->selectRaw("SUM({$amt}) as total_fees, COUNT(*) as transaction_count")
            ->first();

        if (! $row) {
            return null;
        }

        $totalFees = (float) ($row->total_fees ?? 0);
        $count = (int) ($row->transaction_count ?? 0);
        if ($count === 0 && abs($totalFees) < 0.005) {
            return null;
        }

        return (object) [
            'processor' => 'Credit card statement (imported)',
            'total_fees' => $totalFees,
            'transaction_count' => $count,
        ];
    }

    /**
     * Trends rows matching MerchantFeeController / ViewController expense trend shape.
     *
     * @return Collection<int, object>
     */
    public static function trends(
        array $coaIds,
        ?int $storeId,
        string $startDate,
        string $endDate,
        string $groupBy,
        array $accessibleStoreIds
    ): Collection {
        if ($coaIds === [] || $accessibleStoreIds === []) {
            return collect();
        }

        $amt = self::lineAmountExpression('occl');
        $q = self::baseJoinedQuery($coaIds, $storeId, $startDate, $endDate, $accessibleStoreIds);

        switch ($groupBy) {
            case 'week':
                $q->select(
                    DB::raw('YEAR(occl.transaction_date) as year'),
                    DB::raw('WEEK(occl.transaction_date) as week'),
                    DB::raw('DATE(occl.transaction_date) as period'),
                    DB::raw("SUM({$amt}) as total_fees"),
                    DB::raw('COUNT(*) as transaction_count')
                )->groupBy('year', 'week')->orderBy('year')->orderBy('week');
                break;

            case 'month':
                $q->select(
                    DB::raw('YEAR(occl.transaction_date) as year'),
                    DB::raw('MONTH(occl.transaction_date) as month'),
                    DB::raw("DATE_FORMAT(occl.transaction_date, '%Y-%m') as period"),
                    DB::raw("SUM({$amt}) as total_fees"),
                    DB::raw('COUNT(*) as transaction_count')
                )->groupBy('year', 'month')->orderBy('year')->orderBy('month');
                break;

            case 'day':
            default:
                $q->select(
                    DB::raw('DATE(occl.transaction_date) as period'),
                    DB::raw("SUM({$amt}) as total_fees"),
                    DB::raw('COUNT(*) as transaction_count')
                )->groupBy('period')->orderBy('period');
                break;
        }

        return collect($q->get());
    }

    /**
     * @param  Collection<int, object>  $expenseTrends
     * @return Collection<int, object>
     */
    public static function mergeTrends(Collection $expenseTrends, Collection $ccTrends, string $groupBy): Collection
    {
        if ($ccTrends->isEmpty()) {
            return $expenseTrends;
        }

        $ccKeyed = $ccTrends->keyBy('period');
        $merged = $expenseTrends->map(function ($row) use ($ccKeyed) {
            $add = $ccKeyed->get($row->period ?? null);
            if ($add) {
                $row->total_fees = (float) ($row->total_fees ?? 0) + (float) ($add->total_fees ?? 0);
                $row->transaction_count = (int) ($row->transaction_count ?? 0) + (int) ($add->transaction_count ?? 0);
            }

            return $row;
        });

        $expensePeriods = $merged->pluck('period')->flip();
        foreach ($ccTrends as $ccRow) {
            $p = $ccRow->period ?? null;
            if ($p === null || $expensePeriods->has($p)) {
                continue;
            }
            $merged->push((object) [
                'period' => $p,
                'total_fees' => (float) ($ccRow->total_fees ?? 0),
                'transaction_count' => (int) ($ccRow->transaction_count ?? 0),
                'year' => $ccRow->year ?? null,
                'week' => $ccRow->week ?? null,
                'month' => $ccRow->month ?? null,
            ]);
        }

        return match ($groupBy) {
            'month' => $merged->sortBy('period')->values(),
            'week' => $merged->sortBy(['year', 'week'])->values(),
            default => $merged->sortBy('period')->values(),
        };
    }

    /**
     * @param  Collection<int, object|\Illuminate\Database\Eloquent\Model>  $expenseByProcessor
     * @return Collection<int, object>
     */
    public static function mergeByProcessor(Collection $expenseByProcessor, ?object $ccRow): Collection
    {
        $out = $expenseByProcessor->values();
        if ($ccRow !== null
            && ((float) ($ccRow->total_fees ?? 0) > 0.005 || (int) ($ccRow->transaction_count ?? 0) > 0)) {
            $out->push($ccRow);
        }

        return $out->sortByDesc(fn ($r) => (float) ($r->total_fees ?? 0))->values();
    }
}
