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
     * Whether the stored bank name looks like Bank of the West (for CSV template hints / default ordering).
     * Matching is case-insensitive and tolerates common variations (missing “the”, BMO branding, etc.).
     */
    public static function isLikelyBankOfTheWestName(?string $bankName): bool
    {
        if ($bankName === null || trim($bankName) === '') {
            return false;
        }

        $n = mb_strtolower(trim($bankName));

        if (str_contains($n, 'bank of the west')) {
            return true;
        }

        if (str_contains($n, 'bank of west')) {
            return true;
        }

        if (str_contains($n, 'bmo') && str_contains($n, 'west')) {
            return true;
        }

        return (bool) preg_match('/\bof\s+the\s+west\b/i', $bankName);
    }

    /**
     * Best-effort auto-pick when the bank name looks like Bank of the West; UIs should prefer an explicit account.
     */
    public static function resolveBankOfTheWestAccount(int $storeId): ?BankAccount
    {
        return BankAccount::query()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->first(fn (BankAccount $a) => self::isLikelyBankOfTheWestName($a->bank_name));
    }
}
