<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BankReconciliationController extends Controller
{
    /**
     * Get reconciliation list with filters
     */
    public function index(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = BankTransaction::with(['bankAccount', 'matchedExpense', 'matchedRevenue']);

        // Apply filters
        if ($request->has('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }

        if ($request->has('status')) {
            $query->where('reconciliation_status', $request->status);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Show unmatched first
        $transactions = $query->orderByRaw("CASE WHEN reconciliation_status = 'unmatched' THEN 1 ELSE 2 END")
                             ->orderBy('transaction_date', 'desc')
                             ->paginate(50);

        return response()->json($transactions);
    }

    /**
     * Get potential matches for a bank transaction
     */
    public function getMatches(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $bankTransaction = BankTransaction::findOrFail($id);

        $matches = [
            'expense_matches' => [],
            'revenue_matches' => [],
        ];

        // For debits: match to expenses
        if ($bankTransaction->transaction_type === 'debit') {
            $matches['expense_matches'] = $this->findExpenseMatches($bankTransaction);
        }

        // For credits: match to revenue (daily reports)
        if ($bankTransaction->transaction_type === 'credit') {
            $matches['revenue_matches'] = $this->findRevenueMatches($bankTransaction);
        }

        return response()->json($matches);
    }

    /**
     * Match bank transaction to expense or revenue
     */
    public function matchTransaction(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'expense_id' => 'nullable|exists:expense_transactions,id',
            'revenue_id' => 'nullable|exists:daily_reports,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bankTransaction = BankTransaction::findOrFail($id);

            DB::beginTransaction();

            if ($request->has('expense_id')) {
                $bankTransaction->matched_expense_id = $request->expense_id;
                $bankTransaction->matched_revenue_id = null;
            }

            if ($request->has('revenue_id')) {
                $bankTransaction->matched_revenue_id = $request->revenue_id;
                $bankTransaction->matched_expense_id = null;
            }

            $bankTransaction->reconciliation_status = 'matched';
            if ($request->filled('notes')) {
                $bankTransaction->reconciliation_notes = $request->notes;
            }
            $bankTransaction->save();

            DB::commit();

            return response()->json([
                'message' => 'Transaction matched successfully',
                'data' => $bankTransaction->load(['matchedExpense', 'matchedRevenue']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error matching bank transaction: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to match transaction'], 500);
        }
    }

    /**
     * Mark transaction as reviewed
     */
    public function markReviewed(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'notes' => 'required|string',
            'create_expense' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $bankTransaction = BankTransaction::findOrFail($id);

            DB::beginTransaction();

            $bankTransaction->reconciliation_status = 'reviewed';
            $bankTransaction->reconciliation_notes = $request->notes;
            $bankTransaction->save();

            // Optionally create expense transaction for fees, etc.
            if ($request->boolean('create_expense')) {
                // TODO: Create expense transaction based on notes
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction marked as reviewed',
                'data' => $bankTransaction,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking transaction as reviewed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to mark as reviewed'], 500);
        }
    }

    /**
     * Find potential expense matches for a debit
     */
    protected function findExpenseMatches(BankTransaction $bankTransaction): array
    {
        // Date range: ±3 days
        $dateFrom = $bankTransaction->transaction_date->copy()->subDays(3);
        $dateTo = $bankTransaction->transaction_date->copy()->addDays(3);

        // Amount range: ±$0.50
        $amountMin = $bankTransaction->amount - 0.50;
        $amountMax = $bankTransaction->amount + 0.50;

        // Get already matched expense IDs
        $matchedExpenseIds = BankTransaction::where('transaction_type', 'debit')
            ->whereNotNull('matched_expense_id')
            ->pluck('matched_expense_id')
            ->toArray();

        $matches = ExpenseTransaction::where('store_id', $bankTransaction->bankAccount->store_id)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->whereBetween('amount', [$amountMin, $amountMax])
            ->whereNotIn('id', $matchedExpenseIds) // Not already matched
            ->with(['vendor', 'coa', 'store'])
            ->limit(10)
            ->get()
            ->map(function ($expense) use ($bankTransaction) {
                $confidence = $this->calculateMatchConfidence($bankTransaction, $expense);
                return [
                    'expense' => $expense,
                    'confidence' => $confidence,
                    'date_diff' => $bankTransaction->transaction_date->diffInDays($expense->transaction_date),
                    'amount_diff' => abs($bankTransaction->amount - $expense->amount),
                ];
            })
            ->sortByDesc('confidence')
            ->values()
            ->toArray();

        return $matches;
    }

    /**
     * Find potential revenue matches for a credit (deposits)
     */
    protected function findRevenueMatches(BankTransaction $bankTransaction): array
    {
        // Date range: ±2 days for deposits
        $dateFrom = $bankTransaction->transaction_date->copy()->subDays(2);
        $dateTo = $bankTransaction->transaction_date->copy()->addDays(2);

        // Amount range: ±$1 for deposits
        $amountMin = $bankTransaction->amount - 1.00;
        $amountMax = $bankTransaction->amount + 1.00;

        // Match to credit card deposits (net after fee deduction expected by observer)
        $matches = DailyReport::where('store_id', $bankTransaction->bankAccount->store_id)
            ->whereBetween('report_date', [$dateFrom, $dateTo])
            ->whereNotNull('credit_cards')
            ->where('credit_cards', '>', 0)
            ->with(['store'])
            ->limit(10)
            ->get()
            ->filter(function ($report) use ($amountMin, $amountMax) {
                // Calculate net deposit (credit_cards - 2.45% fee)
                $netDeposit = $report->credit_cards * 0.9755; // 97.55% of gross
                return $netDeposit >= $amountMin && $netDeposit <= $amountMax;
            })
            ->map(function ($report) use ($bankTransaction) {
                $netDeposit = $report->credit_cards * 0.9755;
                $confidence = $this->calculateRevenueConfidence($bankTransaction, $netDeposit);
                return [
                    'report' => $report,
                    'gross_amount' => $report->credit_cards,
                    'net_amount' => $netDeposit,
                    'confidence' => $confidence,
                    'date_diff' => $bankTransaction->transaction_date->diffInDays($report->report_date),
                    'amount_diff' => abs($bankTransaction->amount - $netDeposit),
                ];
            })
            ->sortByDesc('confidence')
            ->values()
            ->toArray();

        return $matches;
    }

    /**
     * Calculate match confidence for expenses
     */
    protected function calculateMatchConfidence(BankTransaction $bank, ExpenseTransaction $expense): float
    {
        $confidence = 1.0;

        // Date difference penalty
        $dateDiff = $bank->transaction_date->diffInDays($expense->transaction_date);
        if ($dateDiff > 3) {
            $confidence *= 0.5;
        } else {
            $confidence *= (1 - ($dateDiff * 0.1));
        }

        // Amount difference penalty
        $amountDiff = abs($bank->amount - $expense->amount);
        if ($amountDiff > 0.50) {
            $confidence *= 0.3;
        } else {
            $confidence *= (1 - ($amountDiff * 0.3));
        }

        return min(1.0, max(0, $confidence));
    }

    /**
     * Calculate match confidence for revenue
     */
    protected function calculateRevenueConfidence(BankTransaction $bank, float $revenue): float
    {
        $confidence = 1.0;

        // Amount difference penalty
        $amountDiff = abs($bank->amount - $revenue);
        if ($amountDiff > 1.00) {
            $confidence *= 0.5;
        } else {
            $confidence *= (1 - ($amountDiff * 0.2));
        }

        return min(1.0, max(0, $confidence));
    }
}
