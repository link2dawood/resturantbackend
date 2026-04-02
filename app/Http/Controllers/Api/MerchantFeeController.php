<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseTransaction;
use App\Models\DailyReport;
use App\Models\ThirdPartyStatement;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantFeeController extends Controller
{
    /**
     * Get merchant fee summary statistics
     */
    public function summary(Request $request)
    {
        $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        $merchantFeeCoa = ChartOfAccount::merchantProcessingFeesAccount();
        
        if (!$merchantFeeCoa) {
            return response()->json([
                'message' => 'Merchant Processing Fees COA not found'
            ], 404);
        }

        // Base query for merchant processing fees
        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);

        $query->whereIn('store_id', $accessibleStoreIds);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        // Get total fees
        $totalFees = $query->sum('amount');

        // Get credit card sales (in-store) from daily reports
        $dailyReportsQuery = DailyReport::whereNotNull('credit_cards')
            ->where('credit_cards', '>', 0);

        $dailyReportsQuery->whereIn('store_id', $accessibleStoreIds);

        if ($storeId) {
            $dailyReportsQuery->where('store_id', $storeId);
        }

        if ($startDate) {
            $dailyReportsQuery->where('report_date', '>=', $startDate);
        }

        if ($endDate) {
            $dailyReportsQuery->where('report_date', '<=', $endDate);
        }

        $totalCreditCardSales = (float) $dailyReportsQuery->sum('credit_cards');

        // Fall back to configured rate when no fee transactions exist yet for this period
        $merchantFeeRate = config('services.square.fee_rate', 0.0245);
        if ((float) $totalFees == 0 && $totalCreditCardSales > 0) {
            $totalFees = round($totalCreditCardSales * $merchantFeeRate, 2);
        }

        // Calculate average fee percentage based on credit card sales
        $averageFeePercentage = $totalCreditCardSales > 0
            ? ((float) $totalFees / $totalCreditCardSales) * 100
            : 0;

        // Get third-party fees
        $thirdPartyQuery = ThirdPartyStatement::query();

        $thirdPartyQuery->whereIn('store_id', $accessibleStoreIds);
        
        if ($storeId) {
            $thirdPartyQuery->where('store_id', $storeId);
        }

        if ($startDate) {
            $thirdPartyQuery->where('statement_date', '>=', $startDate);
        }

        if ($endDate) {
            $thirdPartyQuery->where('statement_date', '<=', $endDate);
        }

        $thirdPartyStats = $thirdPartyQuery->select(
            DB::raw('COALESCE(SUM(marketing_fees + delivery_fees + processing_fees + CASE WHEN platform = \'grubhub\' THEN 0 ELSE COALESCE(adjustments, 0) END), 0) as total_fees'),
            DB::raw('COALESCE(SUM(gross_sales), 0) as total_sales')
        )->first();

        $thirdPartyAverageFeePercentage = (float) $thirdPartyStats->total_sales > 0
            ? ((float) $thirdPartyStats->total_fees / (float) $thirdPartyStats->total_sales) * 100
            : 0;

        return response()->json([
            'merchant_processing' => [
                'total_fees' => (float) $totalFees,
                'total_sales' => (float) $totalCreditCardSales,
                'average_fee_percentage' => round($averageFeePercentage, 2),
            ],
            'third_party_platforms' => [
                'total_fees' => (float) $thirdPartyStats->total_fees,
                'total_sales' => (float) $thirdPartyStats->total_sales,
                'average_fee_percentage' => round($thirdPartyAverageFeePercentage, 2),
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Get merchant fees by processor
     */
    public function byProcessor(Request $request)
    {
        $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        $merchantFeeCoa = ChartOfAccount::merchantProcessingFeesAccount();
        
        if (!$merchantFeeCoa) {
            return response()->json([
                'message' => 'Merchant Processing Fees COA not found'
            ], 404);
        }

        $query = ExpenseTransaction::select(
                'vendors.vendor_name as processor',
                DB::raw('SUM(expense_transactions.amount) as total_fees'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->join('vendors', 'expense_transactions.vendor_id', '=', 'vendors.id')
            ->where('expense_transactions.coa_id', $merchantFeeCoa->id);

        $query->whereIn('expense_transactions.store_id', $accessibleStoreIds);

        if ($storeId) {
            $query->where('expense_transactions.store_id', $storeId);
        }

        if ($startDate) {
            $query->where('expense_transactions.transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('expense_transactions.transaction_date', '<=', $endDate);
        }

        $byProcessor = $query->groupBy('vendors.vendor_name')
            ->orderByDesc('total_fees')
            ->get();

        return response()->json($byProcessor);
    }

    /**
     * Get merchant fees over time (for chart)
     */
    public function trends(Request $request)
    {
        $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date', now()->subDays(30));
        $endDate = $request->input('end_date', now());
        $groupBy = $request->input('group_by', 'day'); // day, week, month

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        $merchantFeeCoa = ChartOfAccount::merchantProcessingFeesAccount();
        
        if (!$merchantFeeCoa) {
            return response()->json([
                'message' => 'Merchant Processing Fees COA not found'
            ], 404);
        }

        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);

        $query->whereIn('store_id', $accessibleStoreIds);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        // Group by date based on parameter
        switch ($groupBy) {
            case 'day':
                $query->select(
                    DB::raw('DATE(transaction_date) as period'),
                    DB::raw('SUM(amount) as total_fees'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('period')
                ->orderBy('period');
                break;

            case 'week':
                $query->select(
                    DB::raw('YEAR(transaction_date) as year'),
                    DB::raw('WEEK(transaction_date) as week'),
                    DB::raw('DATE(transaction_date) as period'),
                    DB::raw('SUM(amount) as total_fees'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('year', 'week')
                ->orderBy('year')
                ->orderBy('week');
                break;

            case 'month':
                $query->select(
                    DB::raw('YEAR(transaction_date) as year'),
                    DB::raw('MONTH(transaction_date) as month'),
                    DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as period"),
                    DB::raw('SUM(amount) as total_fees'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month');
                break;
        }

        $trends = $query->get();

        return response()->json([
            'trends' => $trends,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'group_by' => $groupBy,
            ],
        ]);
    }

    /**
     * Get third-party platform breakdown
     */
    public function thirdPartyBreakdown(Request $request)
    {
        $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        $query = ThirdPartyStatement::select(
                'platform',
                DB::raw('SUM(gross_sales) as total_gross_sales'),
                DB::raw('SUM(marketing_fees) as total_marketing_fees'),
                DB::raw('SUM(delivery_fees) as total_delivery_fees'),
                DB::raw('SUM(processing_fees) as total_processing_fees'),
                DB::raw('SUM(marketing_fees + delivery_fees + processing_fees) as total_fees'),
                DB::raw('SUM(net_deposit) as total_net_deposit'),
                DB::raw('COUNT(*) as statement_count')
            );

        $query->whereIn('store_id', $accessibleStoreIds);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($startDate) {
            $query->where('statement_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('statement_date', '<=', $endDate);
        }

        $breakdown = $query->groupBy('platform')
            ->orderByDesc('total_fees')
            ->get();

        return response()->json($breakdown);
    }

    /**
     * Get detailed merchant fee transactions
     */
    public function transactions(Request $request)
    {
        $accessibleStoreIds = auth()->user()->getAccessibleStoreIds();
        $storeId = $request->input('store_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $processor = $request->input('processor');

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        $merchantFeeCoa = ChartOfAccount::merchantProcessingFeesAccount();
        
        if (!$merchantFeeCoa) {
            return response()->json([
                'message' => 'Merchant Processing Fees COA not found'
            ], 404);
        }

        $query = ExpenseTransaction::with(['store', 'vendor', 'dailyReport'])
            ->where('coa_id', $merchantFeeCoa->id);

        $query->whereIn('store_id', $accessibleStoreIds);

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($startDate) {
            $query->where('transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('transaction_date', '<=', $endDate);
        }

        if ($processor) {
            $query->whereHas('vendor', function($q) use ($processor) {
                $q->where('vendor_name', 'like', "%{$processor}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json($transactions);
    }
}
