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
     */
    protected function processCreditCardDeposit(DailyReport $dailyReport): void
    {
        // Skip if no credit card sales
        if (!$dailyReport->credit_cards || $dailyReport->credit_cards <= 0) {
            return;
        }

        try {
            // Calculate merchant fees (2.45% of gross)
            $grossAmount = $dailyReport->credit_cards;
            $merchantFeeRate = 0.0245;
            $merchantFee = $grossAmount * $merchantFeeRate;
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

            // Get or find Merchant Processing Fees COA
            $merchantCoa = ChartOfAccount::where('account_name', 'Merchant Processing Fees')
                ->orWhere('account_code', '5200') // Assuming this code
                ->first();

            if (!$merchantCoa) {
                Log::warning('Merchant Processing Fees COA not found. Please create it manually.');
                return;
            }

            // Create merchant fee expense transaction
            $existingFee = ExpenseTransaction::where('daily_report_id', $dailyReport->id)
                ->where('coa_id', $merchantCoa->id)
                ->first();

            if ($existingFee) {
                // Update existing fee
                $existingFee->update([
                    'amount' => $merchantFee,
                    'description' => "Merchant fee for {$dailyReport->report_date->format('M d, Y')}",
                ]);
                $feeTransaction = $existingFee;
            } else {
                // Create new fee
                $feeTransaction = ExpenseTransaction::create([
                    'transaction_type' => 'credit_card',
                    'transaction_date' => $dailyReport->report_date,
                    'store_id' => $dailyReport->store_id,
                    'vendor_id' => $squareVendor->id,
                    'coa_id' => $merchantCoa->id,
                    'amount' => $merchantFee,
                    'description' => "Merchant fee for {$dailyReport->report_date->format('M d, Y')}",
                    'payment_method' => 'credit_card',
                    'daily_report_id' => $dailyReport->id,
                    'created_by' => auth()->id() ?? $dailyReport->created_by,
                    'duplicate_check_hash' => md5($dailyReport->id . 'merchant-fee'),
                ]);
            }

            // Create expected bank deposit transaction if a bank account exists
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
                    // Update existing deposit
                    $existingDeposit->update([
                        'amount' => $netDeposit,
                    ]);
                } else {
                    // Create new expected deposit
                    BankTransaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'transaction_date' => $dailyReport->report_date,
                        'transaction_type' => 'credit',
                        'amount' => $netDeposit,
                        'description' => "Expected CC deposit for {$dailyReport->report_date->format('M d, Y')}",
                        'reference_number' => "CC-{$dailyReport->id}",
                        'reconciliation_status' => 'unmatched',
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
