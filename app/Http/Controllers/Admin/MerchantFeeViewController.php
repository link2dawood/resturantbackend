<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Store;
use App\Models\ExpenseTransaction;
use App\Models\DailyReport;
use App\Models\ThirdPartyStatement;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MerchantFeeViewController extends Controller
{
    /**
     * Display the merchant fees dashboard
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleStoreIds)->orderBy('store_info')->get();

        // Set default date range
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $storeId = $request->input('store_id');

        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        // Get merchant fee COA
        $merchantFeeCoa = ChartOfAccount::merchantProcessingFeesAccount();
        
        // Calculate merchant processing stats
        $merchantProcessing = $this->getMerchantProcessingStats($merchantFeeCoa, $storeId, $startDate, $endDate, $accessibleStoreIds);
        
        // Get third-party stats
        $thirdPartyPlatforms = $this->getThirdPartyStats($storeId, $startDate, $endDate, $accessibleStoreIds);
        
        // Get fees by processor
        $byProcessor = $this->getFeesByProcessor($merchantFeeCoa, $storeId, $startDate, $endDate, $accessibleStoreIds);
        
        // Get trends data
        $trends = $this->getFeeTrends($merchantFeeCoa, $storeId, $startDate, $endDate, 'day', $accessibleStoreIds);
        
        // Get recent transactions
        $recentTransactions = $this->getRecentFeeTransactions($merchantFeeCoa, $storeId, $startDate, $endDate, $accessibleStoreIds);
        
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
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleStoreIds)->orderBy('store_info')->get();

        // No default date range — show all data unless user explicitly filters
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $storeId = $request->input('store_id');
        $platform = $request->input('platform');

        // Restrict store filter to accessible stores only
        if ($storeId && ! in_array((int) $storeId, $accessibleStoreIds, true)) {
            $storeId = null;
        }

        // Get summary stats (scoped to accessible stores)
        $summary = $this->getThirdPartySummary($storeId, $startDate, $endDate, $accessibleStoreIds, $platform);

        // Get platform breakdown (scoped to accessible stores)
        $platformBreakdown = $this->getThirdPartyBreakdown($storeId, $startDate, $endDate, $accessibleStoreIds, $platform);

        // Get import history (only statements for accessible stores)
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
     * Show a single third-party statement (detail page with proper UI)
     */
    public function thirdPartyStatementShow(ThirdPartyStatement $statement)
    {
        $user = auth()->user();
        if (!$user->hasStoreAccess($statement->store_id)) {
            abort(403, 'You do not have access to this statement.');
        }

        $this->syncThirdPartyExpenseCoas($statement);
        $statement->load(['store', 'importer', 'expenses.vendor', 'expenses.coa']);

        return view('admin.merchant-fees.third-party-show', compact('statement'));
    }

    /**
     * Delete a third-party statement and all related records (expense transactions, expected deposit).
     * No file is stored on disk; only DB records are removed.
     */
    public function thirdPartyStatementDestroy(ThirdPartyStatement $statement)
    {
        $user = auth()->user();
        if (!$user->hasStoreAccess($statement->store_id)) {
            abort(403, 'You do not have access to this statement.');
        }

        DB::transaction(function () use ($statement) {
            // Delete expense transactions created by this import
            $statement->expenses()->delete();

            // Delete expected deposit bank transaction (created at import)
            BankTransaction::where('reference_number', $statement->platform . '-' . $statement->id)->delete();

            // Delete the stored file from disk (path stored in file_path)
            if ($statement->file_path && Storage::disk('local')->exists($statement->file_path)) {
                Storage::disk('local')->delete($statement->file_path);
            }

            // Delete the statement
            $statement->delete();
        });

        return redirect()
            ->route('admin.merchant-fees.third-party')
            ->with('success', 'Statement and all related records have been deleted.');
    }

    protected function syncThirdPartyExpenseCoas(ThirdPartyStatement $statement): void
    {
        $platformExpenseCoa = ChartOfAccount::thirdPartyPlatformExpenseAccount($statement->platform);

        if (! $platformExpenseCoa) {
            return;
        }

        $statement->expenses()
            ->where(function ($query) use ($platformExpenseCoa) {
                $query->whereNull('coa_id')
                    ->orWhere('coa_id', '!=', $platformExpenseCoa->id);
            })
            ->update([
                'coa_id' => $platformExpenseCoa->id,
            ]);
    }
    
    /**
     * Get merchant processing statistics
     */
    protected function getMerchantProcessingStats($merchantFeeCoa, $storeId, $startDate, $endDate, array $accessibleStoreIds = [])
    {
        if (!$merchantFeeCoa) {
            return [
                'total_fees' => 0,
                'total_sales' => 0,
                'third_party_sales' => 0,
                'average_fee_percentage' => 0,
            ];
        }

        // Get total merchant fees
        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        $totalFees = $query->sum('amount');

        // Get credit card sales (in-store)
        $dailyReportsQuery = DailyReport::whereNotNull('credit_cards')->where('credit_cards', '>', 0);

        if (! empty($accessibleStoreIds)) {
            $dailyReportsQuery->whereIn('store_id', $accessibleStoreIds);
        }

        if ($storeId) {
            $dailyReportsQuery->where('store_id', $storeId);
        }

        $dailyReportsQuery->whereBetween('report_date', [$startDate, $endDate]);
        $creditCardSales = $dailyReportsQuery->sum('credit_cards');

        // Get third-party platform net deposits (online platform payouts)
        $thirdPartyQuery = ThirdPartyStatement::query();

        if (! empty($accessibleStoreIds)) {
            $thirdPartyQuery->whereIn('store_id', $accessibleStoreIds);
        }

        if ($storeId) {
            $thirdPartyQuery->where('store_id', $storeId);
        }

        $thirdPartyQuery->whereBetween('statement_date', [$startDate, $endDate]);
        $thirdPartySales = (float) $thirdPartyQuery->sum('net_deposit');

        $totalSales = $creditCardSales + $thirdPartySales;
        $averageFeePercentage = $totalSales > 0 ? ($totalFees / $totalSales) * 100 : 0;

        return [
            'total_fees' => $totalFees,
            'total_sales' => $totalSales,
            'credit_card_sales' => $creditCardSales,
            'third_party_sales' => $thirdPartySales,
            'average_fee_percentage' => round($averageFeePercentage, 2),
        ];
    }
    
    /**
     * Get third-party platform statistics
     */
    protected function getThirdPartyStats($storeId, $startDate, $endDate, array $accessibleStoreIds = [])
    {
        $query = ThirdPartyStatement::query();

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }
        
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        
        $query->whereBetween('statement_date', [$startDate, $endDate]);
        
        $stats = $query->select(
            DB::raw('COALESCE(SUM(marketing_fees + delivery_fees + processing_fees + COALESCE(adjustments, 0)), 0) as total_fees'),
            DB::raw('COALESCE(SUM(gross_sales), 0) as total_sales')
        )->first();

        $totalFees = (float) ($stats->total_fees ?? 0);
        $totalSales = (float) ($stats->total_sales ?? 0);
        $averageFeePercentage = $totalSales > 0 ? ($totalFees / $totalSales) * 100 : 0;
        
        return [
            'total_fees' => $totalFees,
            'total_sales' => $totalSales,
            'average_fee_percentage' => round($averageFeePercentage, 2),
        ];
    }
    
    /**
     * Get fees grouped by processor
     */
    protected function getFeesByProcessor($merchantFeeCoa, $storeId, $startDate, $endDate, array $accessibleStoreIds = [])
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

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('expense_transactions.store_id', $accessibleStoreIds);
        }
        
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
    protected function getFeeTrends($merchantFeeCoa, $storeId, $startDate, $endDate, $groupBy = 'day', array $accessibleStoreIds = [])
    {
        if (!$merchantFeeCoa) {
            return collect([]);
        }
        
        $query = ExpenseTransaction::where('coa_id', $merchantFeeCoa->id);

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }
        
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
    protected function getRecentFeeTransactions($merchantFeeCoa, $storeId, $startDate, $endDate, array $accessibleStoreIds = [])
    {
        if (!$merchantFeeCoa) {
            return collect([]);
        }
        
        $query = ExpenseTransaction::with(['store', 'vendor', 'dailyReport'])
            ->where('coa_id', $merchantFeeCoa->id);

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }
        
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
     * Get third-party platform summary (optionally scoped to accessible store IDs).
     */
    protected function getThirdPartySummary($storeId, $startDate, $endDate, array $accessibleStoreIds = [], ?string $platform = null)
    {
        $query = ThirdPartyStatement::query();

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('statement_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('statement_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('statement_date', '<=', $endDate);
        }

        $stats = $query->select(
            DB::raw('COALESCE(SUM(gross_sales), 0) as total_gross_sales'),
            DB::raw('COALESCE(SUM(marketing_fees + delivery_fees + processing_fees + adjustments), 0) as total_fees'),
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
     * Get third-party platform breakdown (optionally scoped to accessible store IDs).
     */
    protected function getThirdPartyBreakdown($storeId, $startDate, $endDate, array $accessibleStoreIds = [], ?string $platform = null)
    {
        $query = ThirdPartyStatement::select(
            'platform',
            DB::raw('SUM(gross_sales) as total_gross_sales'),
            DB::raw('SUM(marketing_fees) as total_marketing_fees'),
            DB::raw('SUM(delivery_fees) as total_delivery_fees'),
            DB::raw('SUM(processing_fees) as total_processing_fees'),
            DB::raw('SUM(adjustments) as total_adjustments'),
            DB::raw('SUM(marketing_fees + delivery_fees + processing_fees + adjustments) as total_fees'),
            DB::raw('SUM(net_deposit) as total_net_deposit'),
            DB::raw('COUNT(*) as statement_count')
        );

        if (! empty($accessibleStoreIds)) {
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($platform) {
            $query->where('platform', $platform);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('statement_date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('statement_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('statement_date', '<=', $endDate);
        }

        return $query->groupBy('platform')
            ->orderByDesc('total_fees')
            ->get();
    }
    
    /**
     * Get third-party import history (only statements for stores the current user can access).
     */
    protected function getThirdPartyImportHistory($storeId, $platform)
    {
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        $query = ThirdPartyStatement::with(['store', 'importer'])
            ->whereIn('store_id', $accessibleStoreIds);

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
