<?php

namespace App\Support;

use App\Models\ImportBatch;
use App\Models\OwnerCcStatementImport;
use App\Models\ThirdPartyStatement;

/**
 * Enforce at most one import per original client file name (case-insensitive, trimmed),
 * separately for each import channel (CC, bank statement, third-party).
 */
final class ImportUniqueFileName
{
    public static function normalize(string $originalClientName): string
    {
        return mb_strtolower(trim($originalClientName));
    }

    public static function ownerCcStatementImportExists(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        return OwnerCcStatementImport::query()
            ->whereRaw('LOWER(TRIM(file_name)) = ?', [$normalized])
            ->exists();
    }

    public static function bankStatementImportExists(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        return ImportBatch::query()
            ->where('import_type', 'bank_statement')
            ->whereRaw('LOWER(TRIM(file_name)) = ?', [$normalized])
            ->exists();
    }

    public static function thirdPartyStatementExists(string $normalized): bool
    {
        if ($normalized === '') {
            return false;
        }

        return ThirdPartyStatement::query()
            ->whereNotNull('file_name')
            ->where('file_name', '!=', '')
            ->whereRaw('LOWER(TRIM(file_name)) = ?', [$normalized])
            ->exists();
    }
}
