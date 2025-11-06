<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\ExpenseTransaction;
use App\Models\DailyReport;
use App\Models\ThirdPartyStatement;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantFeeViewController extends Controller
{
    /**
     * Display the merchant fees dashboard
     */
    public function index(Request $request)
    {
        $stores = Store::all();
        
        // Set default date range
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->input('store_id');
        
        // Get merchant fee COA
        $merchantFeeCoa = ChartOfAccount::where('account_name', 'Merchant Processing Fees')->first();
        
        // Calculate merchant processing stats
        $merchantProcessing = $this->getMerchantProcessingStats($merchantFeeCoa, $storeId, $startDate, $endDate);
        
        // Get third-party stats
        $thirdPartyPlatforms = $this->getThirdPartyStats($storeId, $startDate, $endDate);
        
        // Get fees by processor
        $byProcessor = $this->getFeesByProcessor($merchantFeeCoa, $storeId, $startDate, $endDate);
        
        // Get trends data
        $trends = $this->getFeeTrends($merchantFeeCoa, $storeId, $startDate, $endDate, 'day');
        
        // Get recent transactions
        $recentTransactions = $this->getRecentFeeTransactions($merchantFeeCoa, $storeId, $startDate, $endDate);
        
        return view('admin.merchant-fees.index', compact(
            'stores', 
            'merchantProcessing', 
            'thirdPartyPlatforms', 
            'byProcessor', 
            'trends', 
            'recentTransactions',
            'startDate',
            'endDate',
            'storeId'
        ));
    }

    /**
     * Display third-party platform costs
     */
    public function thirdParty(Request $request)
    {
        $stores = Store::all();
        
        // Set default date range
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->input('store_id');
        $platform = $request->input('platform');
        
        // Get summary stats
        $summary = $this->getThirdPartySummary($storeId, $startDate, $endDate);
        
        // Get platform breakdown
        $platformBreakdown = $this->getThirdPartyBreakdown($storeId, $startDate, $endDate);
        
        // Get import history
        $importHistory = $this->getThirdPartyImportHistory($storeId, $platform);
        
        return view('admin.merchant-fees.third-party', compact(
            'stores', 
            'summary', 
            'platformBreakdown', 
            'importHistory',
            'startDate',
            'endDate',
            'storeId',
            'platform'
        ));
    }
    
    /**
     * Get merchant processing statistics
     */
    protected function getMerchantProcessingStats($merchantFeeCoa, $storeId, $startDate, $endDate)
    {
        if (!$merchantFeeCoa) {
            return [
                'total_fees' => 0,
                'total_sales' => 0,
                'average_fee_percentage' => 0,
            ];
        }
        
        // Get total merchant fees
        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('transaction_date', [$startDate, $endDate]);
        
        $totalFees = $query->sum('amount');
        
        // Get credit card sales
        $dailyReportsQuery = DailyReport::whereNotNull('credit_cards')->where('credit_cards', '>', 0);
        
        if ($storeId) {
            $dailyReportsQuery->where('store_id', $storeId);
        }
        
        $dailyReportsQuery->whereBetween('report_date', [$startDate, $endDate]);
        $totalSales = $dailyReportsQuery->sum('credit_cards');
        
        $averageFeePercentage = $totalSales > 0 ? ($totalFees / $totalSales) * 100 : 0;
        
        return [
            'total_fees' => $totalFees,
            'total_sales' => $totalSales,
            'average_fee_percentage' => round($averageFeePercentage, 2),
        ];
    }
    
    /**
     * Get third-party platform statistics
     */
    protected function getThirdPartyStats($storeId, $startDate, $endDate)
    {
        $query = ThirdPartyStatement::query();
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('statement_date', [$startDate, $endDate]);
        
        $stats = $query->select(
            DB::raw('COALESCE(SUM(marketing_fees + delivery_fees + processing_fees), 0) as total_fees')
        )->first();
        
        return [
            'total_fees' => $stats->total_fees,
        ];
    }
    
    /**
     * Get fees grouped by processor
     */
    protected function getFeesByProcessor($merchantFeeCoa, $storeId, $startDate, $endDate)
    {
        if (!$merchantFeeCoa) {
            return collect([]);
        }
        
        $query = ExpenseTransaction::select(
                'vendors.vendor_name as processor',
                DB::raw('SUM(expense_transactions.amount) as total_fees'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->join('vendors', 'expense_transactions.vendor_id', '=', 'vendors.id')
            ->where('expense_transactions.coa_id', $merchantFeeCoa->id);
        
        if ($storeId) {
            $query->where('expense_transactions.store_id', $storeId);
        }
        
        $query->whereBetween('expense_transactions.transaction_date', [$startDate, $endDate]);
        
        return $query->groupBy('vendors.vendor_name')
            ->orderByDesc('total_fees')
            ->get();
    }
    
    /**
     * Get fee trends over time
     */
    protected function getFeeTrends($merchantFeeCoa, $storeId, $startDate, $endDate, $groupBy = 'day')
    {
        if (!$merchantFeeCoa) {
            return collect([]);
        }
        
        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);
        
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
                )->groupBy('period')->orderBy('period');
                break;
                
            case 'week':
                $query->select(
                    DB::raw('YEAR(transaction_date) as year'),
                    DB::raw('WEEK(transaction_date) as week'),
                    DB::raw('DATE(transaction_date) as period'),
                    DB::raw('SUM(amount) as total_fees'),
                    DB::raw('COUNT(*) as transaction_count')
                )->groupBy('year', 'week')->orderBy('year')->orderBy('week');
                break;
                
            case 'month':
                $query->select(
                    DB::raw('YEAR(transaction_date) as year'),
                    DB::raw('MONTH(transaction_date) as month'),
                    DB::raw("DATE_FORMAT(transaction_date, '%Y-%m') as period"),
                    DB::raw('SUM(amount) as total_fees'),
                    DB::raw('COUNT(*) as transaction_count')
                )->groupBy('year', 'month')->orderBy('year')->orderBy('month');
                break;
        }
        
        return $query->get();
    }
    
    /**
     * Get recent merchant fee transactions
     */
    protected function getRecentFeeTransactions($merchantFeeCoa, $storeId, $startDate, $endDate)
    {
        if (!$merchantFeeCoa) {
            return collect([]);
        }
        
        $query = ExpenseTransaction::with(['store', 'vendor', 'dailyReport'])
            ->where('coa_id', $merchantFeeCoa->id);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('transaction_date', [$startDate, $endDate]);
        
        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    /**
     * Get third-party platform summary
     */
    protected function getThirdPartySummary($storeId, $startDate, $endDate)
    {
        $query = ThirdPartyStatement::query();
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('statement_date', [$startDate, $endDate]);
        
        $stats = $query->select(
            DB::raw('COALESCE(SUM(gross_sales), 0) as total_gross_sales'),
            DB::raw('COALESCE(SUM(marketing_fees + delivery_fees + processing_fees), 0) as total_fees'),
            DB::raw('COALESCE(SUM(net_deposit), 0) as total_net_deposit')
        )->first();
        
        $avgFeePercentage = $stats->total_gross_sales > 0 
            ? ($stats->total_fees / $stats->total_gross_sales) * 100 
            : 0;
        
        return [
            'total_gross_sales' => $stats->total_gross_sales,
            'total_fees' => $stats->total_fees,
            'total_net_deposit' => $stats->total_net_deposit,
            'average_fee_percentage' => round($avgFeePercentage, 2),
        ];
    }
    
    /**
     * Get third-party platform breakdown
     */
    protected function getThirdPartyBreakdown($storeId, $startDate, $endDate)
    {
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
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('statement_date', [$startDate, $endDate]);
        
        return $query->groupBy('platform')
            ->orderByDesc('total_fees')
            ->get();
    }
    
    /**
     * Get third-party import history
     */
    protected function getThirdPartyImportHistory($storeId, $platform)
    {
        $query = ThirdPartyStatement::with(['store', 'importer']);
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        if ($platform) {
            $query->where('platform', $platform);
        }
        
        return $query->orderBy('statement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }
}
