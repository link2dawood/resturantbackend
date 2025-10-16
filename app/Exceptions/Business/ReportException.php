<?php

namespace App\Exceptions\Business;

use Exception;

class ReportException extends Exception
{
    public static function duplicateDate(string $date, int $storeId): self
    {
        return new self("A daily report for {$date} already exists for store {$storeId}.", 409);
    }

    public static function invalidDateRange(string $startDate, string $endDate): self
    {
        return new self("Invalid date range: {$startDate} to {$endDate}. End date must be after start date.", 422);
    }

    public static function futureDate(string $date): self
    {
        return new self("Cannot create reports for future dates: {$date}.", 422);
    }

    public static function alreadyApproved(int $reportId): self
    {
        return new self("Report {$reportId} has already been approved and cannot be modified.", 409);
    }

    public static function cannotApproveOwnReport(int $reportId, int $userId): self
    {
        return new self("User {$userId} cannot approve their own report {$reportId}.", 409);
    }

    public static function missingRequiredData(array $missingFields): self
    {
        $fields = implode(', ', $missingFields);

        return new self("Missing required report data: {$fields}.", 422);
    }

    public static function inconsistentTotals(float $calculated, float $provided): self
    {
        return new self(
            "Total amount inconsistency: calculated {$calculated}, provided {$provided}.",
            422
        );
    }
}
