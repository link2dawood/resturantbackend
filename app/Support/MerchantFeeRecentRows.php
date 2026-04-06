<?php

namespace App\Support;

use App\Models\ExpenseTransaction;
use App\Models\OwnerCcStatementLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Normalized rows for "Recent Merchant Fee Transactions" (credit card processing fees:
 * ExpenseTransaction on processing COAs + Owner CC lines assigned to those COAs).
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

        $expenseRows = collect();
        if ($coaIds !== []) {
            $expenseQuery = ExpenseTransaction::with(['store', 'vendor', 'dailyReport'])
                ->whereIn('store_id', $accessibleStoreIds)
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->whereIn('coa_id', $coaIds)
                ->whereNull('third_party_statement_id');

            if ($storeId) {
                $expenseQuery->where('store_id', $storeId);
            }

            $expenses = $expenseQuery->orderByDesc('transaction_date')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(200)
                ->get();

            $expenseRows = $expenses->map(fn (ExpenseTransaction $e) => self::expenseToRow($e));
        }

        $ccRows = collect();
        if ($coaIds !== []) {
            $ccQuery = OwnerCcStatementLine::query()
                ->select('owner_cc_statement_lines.*')
                ->join(
                    'owner_cc_statement_imports as occi',
                    'occi.id',
                    '=',
                    'owner_cc_statement_lines.owner_cc_statement_import_id'
                )
                ->whereIn('owner_cc_statement_lines.coa_id', $coaIds)
                ->whereBetween('owner_cc_statement_lines.transaction_date', [$startDate, $endDate])
                ->whereIn(DB::raw('COALESCE(owner_cc_statement_lines.store_id, occi.store_id)'), $accessibleStoreIds);

            if ($storeId) {
                $ccQuery->whereRaw(
                    'COALESCE(owner_cc_statement_lines.store_id, occi.store_id) = ?',
                    [$storeId]
                );
            }

            $ccLines = $ccQuery->with(['import.store', 'store'])
                ->orderByDesc('owner_cc_statement_lines.transaction_date')
                ->orderByDesc('owner_cc_statement_lines.id')
                ->limit(200)
                ->get();

            $ccRows = $ccLines->map(fn (OwnerCcStatementLine $line) => self::ccLineToRow($line));
        }

        return $expenseRows->concat($ccRows)
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
            'third_party_statement_id' => null,
            'owner_cc_statement_import_id' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function ccLineToRow(OwnerCcStatementLine $line): array
    {
        $import = $line->import;
        $sortTs = $line->created_at?->getTimestamp()
            ?? $line->transaction_date->getTimestamp();

        $amount = $line->debit > 0
            ? (float) $line->debit
            : (($line->credit > 0) ? (float) $line->credit : 0.0);

        $storeName = $line->store?->store_info ?? $import?->store?->store_info ?? 'N/A';
        $last4 = $line->card_last4 ?? $import?->card_last4 ?? null;

        $processor = 'CC statement';
        if ($last4) {
            $processor .= ' (····'.$last4.')';
        }
        if ($line->description) {
            $processor .= ': '.mb_substr((string) $line->description, 0, 40);
        }

        return [
            'sort_ts' => $sortTs,
            'transaction_date' => $line->transaction_date->format('Y-m-d'),
            'store_name' => $storeName,
            'processor' => $processor,
            'amount' => $amount,
            'credit_cards' => null,
            'statement_gross_sales' => null,
            'daily_report_id' => null,
            'third_party_statement_id' => null,
            'owner_cc_statement_import_id' => $import?->id,
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
