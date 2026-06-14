<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Phase 4 — Tenant-scoped file storage.
 *
 * Uploaded statements/receipts are written under a per-tenant (per-store) path
 * prefix instead of a flat shared namespace, so one tenant's files are
 * filesystem-separated from another's:
 *
 *   tenant/{storeId}/{category}/{filename}
 *   tenant/shared/{category}/{filename}   (corporate / no specific store)
 *
 * Access is additionally protected at the model layer: the records that point
 * to these files (OwnerCcStatementImport, ThirdPartyStatement, ImportBatch) are
 * TenantScoped, so a user can't even load another tenant's record to reach its
 * file. Existing files keep their previously-stored path (the path lives in the
 * DB per record), so this only affects new uploads — no data migration needed.
 */
class TenantStorage
{
    public static function prefix(?int $storeId): string
    {
        return 'tenant/'.($storeId ?: 'shared');
    }

    /**
     * Store an uploaded file under the tenant-scoped path. Returns the stored
     * path (relative to the disk) or false on failure.
     */
    public static function storeUpload(
        UploadedFile $file,
        string $category,
        ?int $storeId,
        string $filename,
        string $disk = 'local'
    ): string|false {
        $directory = self::prefix($storeId).'/'.$category;

        return $file->storeAs($directory, $filename, $disk);
    }
}
