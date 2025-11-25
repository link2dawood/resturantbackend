<?php

namespace App\Observers;

use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\BankTransaction;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;

class DailyReportObserver
{
    /**
     * Handle the DailyReport "created" event.
     */
    public function created(DailyReport $dailyReport): void
    {
        $this->processCreditCardDeposit($dailyReport);
    }

    /**
     * Handle the DailyReport "updated" event.
     */
    public function updated(DailyReport $dailyReport): void
    {
        // Only process if credit card amount changed
        if ($dailyReport->wasChanged('credit_cards')) {
            $this->processCreditCardDeposit($dailyReport);
        }
    }

    /**
     * Process credit card deposit and merchant fees
     * 
     * This method handles the complete credit card transaction flow:
     * 1. Gross Sales (credit_cards) → Already recorded in daily_reports, used as Revenue in P&L
     * 2. Merchant Fee (2.45% of gross) → Posted as Expense under 'Merchant Processing Fees' COA
     * 3. Net Deposit (gross - fee) → Logged to Bank Ledger as expected deposit
     */
    protected function processCreditCardDeposit(DailyReport $dailyReport): void
    {
        // Skip if no credit card sales
        if (!$dailyReport->credit_cards || $dailyReport->credit_cards <= 0) {
            return;
        }

        try {
            // GROSS SALES: credit_cards field in daily_reports
            // This is already recorded as Revenue in the P&L (via daily_reports.gross_sales)
            $grossAmount = $dailyReport->credit_cards;
            
            // MERCHANT FEE: Calculate 2.45% of gross
            $merchantFeeRate = 0.0245; // 2.45%
            $merchantFee = $grossAmount * $merchantFeeRate;
            
            // NET DEPOSIT: Gross minus merchant fee
            $netDeposit = $grossAmount - $merchantFee;

            // Get or create Square vendor
            $squareVendor = Vendor::where('vendor_identifier', 'Square')
                ->orWhere('vendor_name', 'like', '%Square%')
                ->first();

            if (!$squareVendor) {
                // Create Square vendor if it doesn't exist
                $squareVendor = Vendor::create([
                    'vendor_name' => 'Square',
                    'vendor_identifier' => 'Square',
                    'vendor_type' => 'Services',
                    'is_active' => true,
                    'created_by' => auth()->id() ?? 1,
                ]);
            }

            // Get or find Merchant Processing Fees COA (account_code: 6000)
            $merchantCoa = ChartOfAccount::where('account_name', 'Merchant Processing Fees')
                ->orWhere('account_code', '6000')
                ->first();

            if (!$merchantCoa) {
                Log::error('Merchant Processing Fees COA not found. Please run ChartOfAccountsSeeder.');
                return;
            }

            // MERCHANT FEE → Expense Transaction under 'Merchant Processing Fees' COA
            // This automatically posts to P&L as an expense
            $existingFee = ExpenseTransaction::where('daily_report_id', $dailyReport->id)
                ->where('coa_id', $merchantCoa->id)
                ->first();

            if ($existingFee) {
                // Update existing fee if daily report was modified
                $existingFee->update([
                    'amount' => $merchantFee,
                    'description' => "Merchant processing fee (2.45%) for {$dailyReport->report_date->format('M d, Y')} - Gross: $" . number_format($grossAmount, 2),
                ]);
                $feeTransaction = $existingFee;
            } else {
                // Create new merchant fee expense transaction
                $feeTransaction = ExpenseTransaction::create([
                    'transaction_type' => 'credit_card',
                    'transaction_date' => $dailyReport->report_date,
                    'post_date' => $dailyReport->report_date,
                    'store_id' => $dailyReport->store_id,
                    'vendor_id' => $squareVendor->id,
                    'coa_id' => $merchantCoa->id, // 'Merchant Processing Fees' COA
                    'amount' => $merchantFee,
                    'description' => "Merchant processing fee (2.45%) for {$dailyReport->report_date->format('M d, Y')} - Gross: $" . number_format($grossAmount, 2) . ", Net: $" . number_format($netDeposit, 2),
                    'payment_method' => 'credit_card',
                    'daily_report_id' => $dailyReport->id,
                    'created_by' => auth()->id() ?? $dailyReport->created_by,
                    'duplicate_check_hash' => md5($dailyReport->id . 'merchant-fee'),
                    'needs_review' => false, // Auto-calculated, no review needed
                ]);
            }

            // NET DEPOSIT → Bank Ledger (Expected deposit transaction)
            // This creates an expected deposit that will be matched when actual bank CSV is imported
            $bankAccount = BankAccount::where('account_type', 'checking')
                ->where(function($q) use ($dailyReport) {
                    $q->where('store_id', $dailyReport->store_id)
                      ->orWhereNull('store_id'); // Corporate account
                })
                ->active()
                ->first();

            if ($bankAccount) {
                $existingDeposit = BankTransaction::where('reference_number', "CC-{$dailyReport->id}")
                    ->first();

                if ($existingDeposit) {
                    // Update existing deposit if daily report was modified
                    $existingDeposit->update([
                        'amount' => $netDeposit,
                        'description' => "Expected CC deposit for {$dailyReport->report_date->format('M d, Y')} (Gross: $" . number_format($grossAmount, 2) . ", Fee: $" . number_format($merchantFee, 2) . ")",
                    ]);
                } else {
                    // Create new expected deposit in bank ledger
                    BankTransaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'transaction_date' => $dailyReport->report_date,
                        'post_date' => $dailyReport->report_date,
                        'transaction_type' => 'credit',
                        'amount' => $netDeposit,
                        'description' => "Expected CC deposit for {$dailyReport->report_date->format('M d, Y')} (Gross: $" . number_format($grossAmount, 2) . ", Fee: $" . number_format($merchantFee, 2) . ")",
                        'reference_number' => "CC-{$dailyReport->id}",
                        'reconciliation_status' => 'unmatched', // Will be matched when bank CSV is imported
                        'import_batch_id' => null, // System-generated, not from import
                        'duplicate_check_hash' => md5("CC-{$dailyReport->id}"),
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error processing credit card deposit: ' . $e->getMessage(), [
                'daily_report_id' => $dailyReport->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
