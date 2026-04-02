<?php

namespace App\Constants;

/**
 * Card issuers supported for owner CC statement import (create form options).
 */
final class OwnerCcStatementCardPlatform
{
    public const CITY_BANK = 'city_bank';

    public const AMERICAN_EXPRESS = 'american_express';

    public const CHASE_BANK = 'chase_bank';

    /**
     * Machine value => display label.
     */
    public const LABELS = [
        self::CITY_BANK => 'City Bank',
        self::AMERICAN_EXPRESS => 'American Express',
        self::CHASE_BANK => 'Chase Bank',
    ];

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_keys(self::LABELS);
    }

    public static function label(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::LABELS[$value] ?? null;
    }

    /**
     * Human-readable expected headers (for UI and import error messages).
     */
    public static function expectedColumnsDescription(string $platform): string
    {
        return match ($platform) {
            self::CITY_BANK => 'Status, Date, Description, Debit, Credit, Member Name',
            self::CHASE_BANK => 'Card, Transaction Date, Post Date, Description, Category, Type, Amount (positive = charge/debit, negative = payment/credit), Memo',
            self::AMERICAN_EXPRESS => 'Account, ChkRef, Debit, Credit, Date, Description (a second Credit column is supported if present)',
            default => 'Status, Date, Description, Debit, Credit, Member Name',
        };
    }
}
