<?php

namespace App\Services;

use App\Models\User;
use App\Models\Store;
use App\Models\DailyReport;
use App\Models\AuditLog;
use App\Exceptions\Business\ReportException;
use App\Exceptions\Business\PermissionException;
use App\Exceptions\Business\StoreException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DailyReportService
{
    /**
     * Validate user permissions and store access for daily report creation
     */
    public function validateReportCreation(User $user, int $storeId, string $reportDate): array
    {
        $validationErrors = [];

        // 1. Validate user authentication
        if (!$user) {
            throw PermissionException::sessionExpired();
        }

        // 2. Check if user account is active (not soft deleted)
        if ($user->trashed()) {
            throw PermissionException::accountSuspended($user->id, 'Account has been deactivated');
        }

        // 3. Validate store exists and is active
        $store = Store::find($storeId);
        if (!$store) {
            throw StoreException::notFound($storeId);
        }

        if ($store->trashed()) {
            throw StoreException::alreadyDeleted($storeId);
        }

        // 4. Check user has access to this store based on role
        if (!$this->userHasStoreAccess($user, $store)) {
            throw StoreException::accessDenied($storeId, $user->id);
        }

        // 5. Validate report date
        $this->validateReportDate($reportDate);

        // 6. Check for duplicate reports
        $this->checkDuplicateReport($storeId, $reportDate);

        return [
            'user' => $user,
            'store' => $store,
            'report_date' => Carbon::parse($reportDate),
        ];
    }

    /**
     * Check if user has access to create reports for the given store
     */
    public function userHasStoreAccess(User $user, Store $store): bool
    {
        // Admins can access all stores
        if ($user->isAdmin()) {
            return true;
        }

        // Owners can access stores they created
        if ($user->isOwner()) {
            return $store->created_by === $user->id;
        }

        // Managers can access their assigned store
        if ($user->isManager()) {
            return $user->store_id == $store->id;
        }

        return false;
    }

    /**
     * Validate report date business rules
     */
    public function validateReportDate(string $reportDate): void
    {
        $date = Carbon::parse($reportDate);
        $today = Carbon::today();

        // Cannot create reports for future dates
        if ($date->isFuture()) {
            throw ReportException::futureDate($reportDate);
        }

        // Optional: Cannot create reports older than 30 days (business rule)
        if ($date->diffInDays($today) > 30) {
            Log::warning('Attempt to create report for date older than 30 days', [
                'report_date' => $reportDate,
                'days_old' => $date->diffInDays($today),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Check for duplicate reports (same store + date)
     */
    public function checkDuplicateReport(int $storeId, string $reportDate): void
    {
        $exists = DailyReport::where('store_id', $storeId)
            ->whereDate('report_date', $reportDate)
            ->exists();

        if ($exists) {
            throw ReportException::duplicateDate($reportDate, $storeId);
        }
    }

    /**
     * Create daily report with comprehensive validation and audit logging
     */
    public function createDailyReport(User $user, array $reportData): DailyReport
    {
        // Validate creation permissions
        $validation = $this->validateReportCreation(
            $user,
            $reportData['store_id'],
            $reportData['report_date']
        );

        return DB::transaction(function () use ($user, $reportData, $validation) {
            // Create the report
            $reportData['created_by'] = $user->id;
            $report = DailyReport::create($reportData);

            // Create audit log entry
            $this->logReportCreation($user, $report, $validation['store']);

            // Log successful creation
            Log::info('Daily report created successfully', [
                'report_id' => $report->id,
                'store_id' => $report->store_id,
                'report_date' => $report->report_date,
                'created_by' => $user->id,
                'user_role' => $user->role->value,
            ]);

            return $report;
        });
    }

    /**
     * Validate report data consistency and business rules
     */
    public function validateReportData(array $reportData): void
    {
        $errors = [];

        // Required fields validation
        $requiredFields = [
            'store_id', 'report_date', 'gross_sales', 'total_customers',
            'credit_cards', 'actual_deposit'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($reportData[$field]) || $reportData[$field] === null || $reportData[$field] === '') {
                $errors[] = $field;
            }
        }

        if (!empty($errors)) {
            throw ReportException::missingRequiredData($errors);
        }

        // Business rule validations
        $this->validateFinancialData($reportData);
    }

    /**
     * Validate financial data consistency
     */
    private function validateFinancialData(array $reportData): void
    {
        $grossSales = (float) $reportData['gross_sales'];
        $couponsReceived = (float) ($reportData['coupons_received'] ?? 0);
        $adjustments = (float) ($reportData['adjustments_overrings'] ?? 0);
        $creditCards = (float) $reportData['credit_cards'];
        $actualDeposit = (float) $reportData['actual_deposit'];

        // Net sales should be positive
        $netSales = $grossSales - $couponsReceived - $adjustments;
        if ($netSales < 0) {
            throw ReportException::inconsistentTotals($netSales, $grossSales);
        }

        // Credit card amount should not exceed gross sales
        if ($creditCards > $grossSales) {
            Log::warning('Credit card amount exceeds gross sales', [
                'gross_sales' => $grossSales,
                'credit_cards' => $creditCards,
                'store_id' => $reportData['store_id'],
                'report_date' => $reportData['report_date'],
            ]);
        }

        // Actual deposit should be reasonable compared to net sales
        $expectedCash = $netSales - $creditCards;
        $variance = abs($actualDeposit - $expectedCash);
        
        if ($variance > ($grossSales * 0.1)) { // 10% variance threshold
            Log::warning('High cash deposit variance detected', [
                'expected_cash' => $expectedCash,
                'actual_deposit' => $actualDeposit,
                'variance' => $variance,
                'variance_percentage' => ($variance / $grossSales) * 100,
                'store_id' => $reportData['store_id'],
                'report_date' => $reportData['report_date'],
            ]);
        }
    }

    /**
     * Create audit log entry for report creation
     */
    private function logReportCreation(User $user, DailyReport $report, Store $store): void
    {
        // Additional safety check to prevent null store_info access
        if (!$store) {
            throw new \InvalidArgumentException('Store cannot be null when creating audit log');
        }

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'created',
            'auditable_type' => DailyReport::class,
            'auditable_id' => $report->id,
            'old_values' => null,
            'new_values' => [
                'store_name' => $store->store_info,
                'report_date' => $report->report_date->format('Y-m-d'),
                'gross_sales' => $report->gross_sales,
                'total_customers' => $report->total_customers,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get user's accessible stores for report creation
     */
    public function getUserAccessibleStores(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->accessibleStores()->whereNull('deleted_at')->get();
    }

    /**
     * Check if user can modify existing report
     */
    public function canModifyReport(User $user, DailyReport $report): bool
    {
        // Admins can modify any report
        if ($user->isAdmin()) {
            return true;
        }

        // Users can only modify reports they created
        if ($report->created_by === $user->id) {
            // Only if report is not approved
            return $report->approved_at === null;
        }

        return false;
    }
}