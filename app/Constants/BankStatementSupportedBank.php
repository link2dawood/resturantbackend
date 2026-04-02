<?php

namespace App\Constants;

use App\Models\BankAccount;

/**
 * Banks supported by the admin “Bank statement import” UI (CSV templates).
 */
final class BankStatementSupportedBank
{
    public const BANK_OF_THE_WEST = 'bank_of_the_west';

    public const BANK_OF_THE_WEST_LABEL = 'Bank of the West';

    /**
     * Resolve the active checking account for this store (single supported bank for now).
     */
    public static function resolveBankOfTheWestAccount(int $storeId): ?BankAccount
    {
        return BankAccount::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('bank_name', 'like', '%Bank of the West%')
                    ->orWhere('bank_name', 'like', '%of the West%');
            })
            ->orderBy('id')
            ->first();
    }
}
