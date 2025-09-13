<?php

namespace App\Exceptions\Business;

use Exception;

class StoreException extends Exception
{
    public static function notFound(int $storeId): self
    {
        return new self("Store with ID {$storeId} not found.", 404);
    }

    public static function accessDenied(int $storeId, int $userId): self
    {
        return new self("User {$userId} does not have access to store {$storeId}.", 403);
    }

    public static function alreadyDeleted(int $storeId): self
    {
        return new self("Store with ID {$storeId} has already been deleted.", 409);
    }

    public static function cannotDeleteWithReports(int $storeId, int $reportCount): self
    {
        return new self(
            "Cannot delete store {$storeId} because it has {$reportCount} associated daily reports. Please archive or transfer reports first.",
            409
        );
    }

    public static function invalidTaxRate(float $rate, string $type = 'tax'): self
    {
        return new self("Invalid {$type} rate: {$rate}. Rate must be between 0 and 100.", 422);
    }
}