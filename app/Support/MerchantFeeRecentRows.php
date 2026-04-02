<?php

namespace App\Support;

use App\Models\ExpenseTransaction;
use App\Models\ThirdPartyStatement;
use Illuminate\Support\Collection;

/**
 * Normalized rows for "Recent Merchant Fee Transactions" (in-store processing + platform imports).
 */
final class MerchantFeeRecentRows
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function fetch(
        array $coaIds,
        ?int $storeId,
        string $startDate,
        string $endDate,
        array $accessibleStoreIds,
        int $limit = 25
    ): Collection {
        if ($accessibleStoreIds === []) {
            return collect();
        }

        $expenseQuery = ExpenseTransaction::with(['store', 'vendor', 'dailyReport'])
            ->whereIn('store_id', $accessibleStoreIds)
            ->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($storeId) {
            $expenseQuery->where('store_id', $storeId);
        }

        if ($coaIds === []) {
            $expenseQuery->whereNotNull('third_party_statement_id');
        } else {
            $expenseQuery->where(function ($q) use ($coaIds) {
                $q->whereIn('coa_id', $coaIds)
                    ->orWhereNotNull('third_party_statement_id');
            });
        }

        $expenses = $expenseQuery->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $expenseRows = $expenses->map(fn (ExpenseTransaction $e) => self::expenseToRow($e));

        $statementQuery = ThirdPartyStatement::with('store')
            ->whereIn('store_id', $accessibleStoreIds)
            ->whereBetween('statement_date', [$startDate, $endDate]);

        if ($storeId) {
            $statementQuery->where('store_id', $storeId);
        }

        $statements = $statementQuery->orderByDesc('statement_date')
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        $statementIds = $statements->pluck('id')->all();
        $statementIdsWithExpenses = $statementIds === []
            ? collect()
            : ExpenseTransaction::query()
                ->whereIn('third_party_statement_id', $statementIds)
                ->whereNotNull('third_party_statement_id')
                ->distinct()
                ->pluck('third_party_statement_id')
                ->flip();

        $syntheticRows = collect();
        foreach ($statements as $statement) {
            if ($statementIdsWithExpenses->has((int) $statement->id)) {
                continue;
            }
            $feeTotal = $statement->analyticsTotalFees();
            if (abs($feeTotal) < 0.005) {
                continue;
            }
            $syntheticRows->push(self::statementToRow($statement, $feeTotal));
        }

        return $expenseRows->concat($syntheticRows)
            ->sortByDesc('sort_ts')
            ->take($limit)
            ->values()
            ->map(fn (array $row) => self::stripSortKey($row));
    }

    /**
     * @return array<string, mixed>
     */
    private static function expenseToRow(ExpenseTransaction $e): array
    {
        $dr = $e->dailyReport;
        $sortTs = $e->created_at?->getTimestamp()
            ?? $e->transaction_date->getTimestamp();

        return [
            'sort_ts' => $sortTs,
            'transaction_date' => $e->transaction_date->format('Y-m-d'),
            'store_name' => $e->store->store_info ?? 'N/A',
            'processor' => $e->vendor->vendor_name ?? 'Unknown',
            'amount' => (float) $e->amount,
            'credit_cards' => $dr !== null ? (float) ($dr->credit_cards ?? 0) : null,
            'statement_gross_sales' => null,
            'daily_report_id' => $e->daily_report_id,
            'third_party_statement_id' => $e->third_party_statement_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function statementToRow(ThirdPartyStatement $statement, float $feeTotal): array
    {
        $sortTs = $statement->created_at?->getTimestamp()
            ?? $statement->statement_date->getTimestamp();

        return [
            'sort_ts' => $sortTs,
            'transaction_date' => $statement->statement_date->format('Y-m-d'),
            'store_name' => $statement->store->store_info ?? 'N/A',
            'processor' => ucfirst($statement->platform),
            'amount' => $feeTotal,
            'credit_cards' => null,
            'statement_gross_sales' => (float) $statement->gross_sales,
            'daily_report_id' => null,
            'third_party_statement_id' => $statement->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private static function stripSortKey(array $row): array
    {
        unset($row['sort_ts']);

        return $row;
    }
}
