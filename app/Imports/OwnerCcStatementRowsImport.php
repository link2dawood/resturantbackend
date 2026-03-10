<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Reads the first sheet of an Excel file and returns all rows as array.
 * Used for owner CC statement CSV/XLSX import.
 */
class OwnerCcStatementRowsImport implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}
