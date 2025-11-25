<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ImportBatch;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\Vendor;
use App\Models\TransactionMappingRule;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class BankImportController extends Controller
{
    /**
     * Preview CSV file before importing
     */
    public function preview(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            // Get first 10 lines for preview
            $previewLines = array_slice($lines, 0, 10);
            
            // Detect format
            $format = $this->detectCsvFormat($previewLines);
            
            return response()->json([
                'detected_format' => $format,
                'preview' => $previewLines,
                'total_lines' => count($lines),
            ]);

        } catch (\Exception $e) {
            Log::error('Error previewing bank CSV: ' . $e->getMessage());
            return response()->json(['error' => 'Error reading file: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Import bank statement CSV
     */
    public function import(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'format' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            $bankAccount = BankAccount::findOrFail($request->bank_account_id);
            
            // Get file hash to check for duplicates
            $fileHash = md5_file($file->getRealPath());
            
            // Check if file already imported
            $existingBatch = ImportBatch::where('file_hash', $fileHash)
                ->where('import_type', 'bank_statement')
                ->first();
            
            if ($existingBatch) {
                return response()->json([
                    'error' => 'This file has already been imported',
                    'existing_batch_id' => $existingBatch->id
                ], 422);
            }

            // Parse CSV
            $transactions = $this->parseCsvFile($file->getRealPath(), $request->format);
            
            // Create import batch
            $importBatch = ImportBatch::create([
                'import_type' => 'bank_statement',
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'store_id' => $bankAccount->store_id,
                'transaction_count' => count($transactions),
                'imported_count' => 0,
                'duplicate_count' => 0,
                'error_count' => 0,
                'needs_review_count' => 0,
                'date_range_start' => collect($transactions)->min('transaction_date'),
                'date_range_end' => collect($transactions)->max('transaction_date'),
                'status' => 'processing',
                'imported_by' => auth()->id(),
                'imported_at' => now(),
            ]);

            $importedCount = 0;
            $duplicateCount = 0;
            $errorCount = 0;
            $needsReviewCount = 0;
            $expenseTransactionsCreated = 0;

            foreach ($transactions as $row) {
                try {
                    // Generate duplicate check hash
                    $duplicateHash = md5(
                        $bankAccount->id .
                        $row['transaction_date'] .
                        $row['amount'] .
                        $row['description']
                    );

                    // Check for duplicate
                    $existingTransaction = BankTransaction::where('duplicate_check_hash', $duplicateHash)->first();
                    if ($existingTransaction) {
                        $duplicateCount++;
                        continue;
                    }

                    // Create bank transaction
                    $bankTransaction = BankTransaction::create([
                        'bank_account_id' => $bankAccount->id,
                        'transaction_date' => $row['transaction_date'],
                        'post_date' => $row['post_date'] ?? $row['transaction_date'],
                        'description' => $row['description'],
                        'transaction_type' => $row['transaction_type'],
                        'amount' => abs($row['amount']),
                        'balance' => $row['balance'] ?? null,
                        'reference_number' => $row['reference_number'] ?? null,
                        'reconciliation_status' => 'unmatched',
                        'import_batch_id' => $importBatch->id,
                        'duplicate_check_hash' => $duplicateHash,
                    ]);

                    $importedCount++;

                    // For debit transactions, automatically create expense transaction with vendor matching
                    if ($row['transaction_type'] === 'debit' && abs($row['amount']) > 0) {
                        $expenseCreated = $this->createExpenseFromBankTransaction(
                            $bankTransaction,
                            $bankAccount,
                            $importBatch,
                            $row['card_last_four'] ?? null,
                            $row['card_type'] ?? null
                        );

                        if ($expenseCreated) {
                            $expenseTransactionsCreated++;
                            if ($expenseCreated->needs_review) {
                                $needsReviewCount++;
                            }
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Error importing bank transaction: ' . $e->getMessage(), ['row' => $row]);
                    $errorCount++;
                }
            }

            // Update import batch
            $importBatch->update([
                'imported_count' => $importedCount,
                'duplicate_count' => $duplicateCount,
                'error_count' => $errorCount,
                'needs_review_count' => $needsReviewCount,
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bank statement imported successfully',
                'imported' => $importedCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount,
                'expense_transactions_created' => $expenseTransactionsCreated,
                'needs_review' => $needsReviewCount,
                'batch_id' => $importBatch->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing bank statement: ' . $e->getMessage());
            return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Detect CSV format from preview lines
     */
    protected function detectCsvFormat(array $lines): string
    {
        if (empty($lines)) {
            return 'unknown';
        }

        $firstLine = strtolower($lines[0]);

        // Chase credit card format detection
        // Format: Card, Transaction Date, Post Date, Description, Category, Type, Amount, Memo
        if (strpos($firstLine, 'chase') !== false || 
            (strpos($firstLine, 'card') !== false && strpos($firstLine, 'transaction date') !== false)) {
            return 'chase';
        }

        // Generic credit card format with common headers
        // Look for: Date, Description, Amount, or Date, Vendor, Description, Amount
        if ((strpos($firstLine, 'date') !== false && strpos($firstLine, 'amount') !== false) ||
            (strpos($firstLine, 'date') !== false && strpos($firstLine, 'vendor') !== false && strpos($firstLine, 'amount') !== false)) {
            return 'generic';
        }

        return 'unknown';
    }

    /**
     * Parse CSV file into transaction array
     */
    protected function parseCsvFile(string $filePath, ?string $format = null): array
    {
        $transactions = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Skip header row
        array_shift($lines);

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            
            if (count($row) < 3) {
                continue;
            }

            $transaction = $this->parseCsvRow($row, $format);
            if ($transaction) {
                $transactions[] = $transaction;
            }
        }

        return $transactions;
    }

    /**
     * Parse a single CSV row based on format
     */
    protected function parseCsvRow(array $row, ?string $format = null): ?array
    {
        // Generic format: Date, Description, Amount
        if (!$format || $format === 'generic') {
            if (count($row) < 3) {
                return null;
            }

            // Try to parse date
            $date = $this->parseDate($row[0]);
            if (!$date) {
                return null;
            }

            // Parse amount
            $amount = $this->parseAmount($row[2]);
            if ($amount === null) {
                return null;
            }

            // Try to extract card info from description or reference number
            $cardInfo = null;
            $cardSource = $row[4] ?? $row[1] ?? '';
            if ($cardSource) {
                $cardInfo = $this->extractCardInfo($cardSource);
            }

            return [
                'transaction_date' => $date,
                'post_date' => $date,
                'description' => $row[1] ?? '',
                'transaction_type' => $amount < 0 ? 'debit' : 'credit',
                'amount' => abs($amount),
                'balance' => isset($row[3]) ? $this->parseAmount($row[3]) : null,
                'reference_number' => $row[4] ?? null,
                'card_last_four' => $cardInfo['last_four'] ?? null,
                'card_type' => $cardInfo['type'] ?? null,
            ];
        }

        // Chase format: Card, Transaction Date, Post Date, Description, Category, Type, Amount, Memo
        if ($format === 'chase') {
            if (count($row) < 8) {
                return null;
            }

            $date = $this->parseDate($row[2]); // Post Date
            if (!$date) {
                return null;
            }

            $amount = $this->parseAmount($row[7]); // Amount
            if ($amount === null) {
                return null;
            }

            // Extract card type and last 4 digits from card number
            $cardInfo = $this->extractCardInfo($row[0] ?? '');

            return [
                'transaction_date' => $this->parseDate($row[1]) ?? $date,
                'post_date' => $date,
                'description' => $row[3] ?? '',
                'transaction_type' => strtolower($row[6]) === 'debit' ? 'debit' : 'credit',
                'amount' => abs($amount),
                'reference_number' => $row[0] ?? null, // Card number
                'card_last_four' => $cardInfo['last_four'] ?? null,
                'card_type' => $cardInfo['type'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate(string $dateString): ?string
    {
        // Remove any whitespace
        $dateString = trim($dateString);

        // Try common date formats
        $formats = [
            'Y-m-d',           // 2024-01-15
            'm/d/Y',           // 01/15/2024
            'm-d-Y',           // 01-15-2024
            'Y/m/d',           // 2024/01/15
            'M d, Y',          // Jan 15, 2024
        ];

        foreach ($formats as $format) {
            $date = \Carbon\Carbon::createFromFormat($format, $dateString);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Parse amount from various formats
     */
    protected function parseAmount(string $amountString): ?float
    {
        // Remove currency symbols and whitespace
        $amountString = str_replace(['$', ',', ' '], '', trim($amountString));

        // Handle negative amounts in parentheses
        if (preg_match('/\(([\d.]+)\)/', $amountString, $matches)) {
            return -floatval($matches[1]);
        }

        // Try to parse as float
        $amount = floatval($amountString);
        return is_numeric($amount) ? $amount : null;
    }

    /**
     * Get import history
     */
    public function history(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ImportBatch::with(['store', 'importer'])
            ->where('import_type', 'bank_statement')
            ->orderBy('imported_at', 'desc');

        if ($request->has('bank_account_id')) {
            $bankAccount = BankAccount::find($request->bank_account_id);
            if ($bankAccount && $bankAccount->store_id) {
                $query->where('store_id', $bankAccount->store_id);
            }
        }

        $batches = $query->paginate(20);

        return response()->json($batches);
    }

    /**
     * Create expense transaction from bank transaction with automatic vendor matching and COA assignment
     */
    protected function createExpenseFromBankTransaction(
        BankTransaction $bankTransaction,
        BankAccount $bankAccount,
        ImportBatch $importBatch,
        ?string $cardLastFour = null,
        ?string $cardType = null
    ): ?ExpenseTransaction {
        try {
            // Try to match vendor from description
            $vendor = $this->matchVendor($bankTransaction->description);
            
            // Try to get COA from mapping rules first
            $coaId = $this->getCoaFromMappingRule($bankTransaction->description, $vendor);
            
            // If no mapping rule, use vendor's default COA
            if (!$coaId && $vendor && $vendor->default_coa_id) {
                $coaId = $vendor->default_coa_id;
            }

            // Determine payment method based on transaction type
            $paymentMethod = $this->mapTransactionTypeToPaymentMethod($bankTransaction->transaction_type);

            // Generate duplicate check hash for expense (Date + Vendor + Amount)
            // Use vendor ID if matched, otherwise use normalized vendor name from description
            $vendorIdentifier = $vendor?->id ?? $this->extractVendorNameFromDescription($bankTransaction->description);
            $expenseDuplicateHash = $this->generateExpenseDuplicateHash(
                $bankAccount->store_id,
                $bankTransaction->transaction_date,
                $vendorIdentifier,
                $bankTransaction->amount
            );

            // Check if expense already exists
            $existingExpense = ExpenseTransaction::where('duplicate_check_hash', $expenseDuplicateHash)->first();
            if ($existingExpense) {
                return null; // Skip duplicate
            }

            // Determine if needs review
            $needsReview = !$vendor || !$coaId;
            $reviewReason = null;
            if (!$vendor) {
                $reviewReason = 'Vendor not found';
            } elseif (!$coaId) {
                $reviewReason = 'COA not assigned';
            }

            // If card info not provided, try to extract from description or reference number
            if (!$cardLastFour) {
                $cardInfo = $this->extractCardInfo(
                    $bankTransaction->description . ' ' . ($bankTransaction->reference_number ?? '')
                );
                $cardLastFour = $cardInfo['last_four'] ?? null;
                $cardType = $cardInfo['type'] ?? null;
            }

            // Create expense transaction
            $expense = ExpenseTransaction::create([
                'transaction_type' => $this->mapPaymentMethodToTransactionType($paymentMethod),
                'transaction_date' => $bankTransaction->transaction_date,
                'post_date' => $bankTransaction->post_date,
                'store_id' => $bankAccount->store_id,
                'vendor_id' => $vendor?->id,
                'vendor_name_raw' => $this->extractVendorNameFromDescription($bankTransaction->description),
                'coa_id' => $coaId,
                'amount' => $bankTransaction->amount,
                'description' => $bankTransaction->description,
                'reference_number' => $bankTransaction->reference_number,
                'payment_method' => $paymentMethod,
                'card_last_four' => $cardLastFour,
                'needs_review' => $needsReview,
                'review_reason' => $reviewReason,
                'duplicate_check_hash' => $expenseDuplicateHash,
                'import_batch_id' => $importBatch->id,
                'created_by' => auth()->id(),
            ]);

            return $expense;

        } catch (\Exception $e) {
            Log::error('Error creating expense from bank transaction: ' . $e->getMessage(), [
                'bank_transaction_id' => $bankTransaction->id,
            ]);
            return null;
        }
    }

    /**
     * Match vendor by description using aliases and fuzzy matching
     */
    protected function matchVendor(string $description): ?Vendor
    {
        // Extract potential vendor name from description (first few words)
        $words = explode(' ', trim($description));
        $potentialVendorName = implode(' ', array_slice($words, 0, 3)); // First 3 words

        // First, try exact match with aliases
        $vendor = Vendor::whereHas('aliases', function($q) use ($potentialVendorName) {
            $q->where('alias', 'like', '%' . $potentialVendorName . '%');
        })->first();

        if ($vendor) {
            return $vendor;
        }

        // Try fuzzy matching using VendorController
        try {
            $matchResult = app(\App\Http\Controllers\Api\VendorController::class)
                ->match(new Request(['description' => $description]));
            $matchData = json_decode($matchResult->getContent(), true);

            if ($matchData && isset($matchData['match']) && $matchData['match'] && 
                isset($matchData['confidence']) && $matchData['confidence'] >= 60) {
                return Vendor::find($matchData['vendor']['id'] ?? null);
            }
        } catch (\Exception $e) {
            Log::debug('Fuzzy matching failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Get COA from mapping rules based on description pattern
     */
    protected function getCoaFromMappingRule(string $description, ?Vendor $vendor = null): ?int
    {
        // Try to find matching mapping rule
        $mappingRule = TransactionMappingRule::where('description_pattern', 'like', '%' . substr($description, 0, 20) . '%')
            ->orderBy('confidence_score', 'desc')
            ->orderBy('times_used', 'desc')
            ->first();

        if ($mappingRule && $mappingRule->coa_id) {
            // Update usage statistics
            $mappingRule->markAsUsed();
            return $mappingRule->coa_id;
        }

        // If vendor provided, try vendor-specific mapping
        if ($vendor) {
            $vendorMapping = TransactionMappingRule::where('vendor_id', $vendor->id)
                ->where('description_pattern', 'like', '%' . substr($description, 0, 20) . '%')
                ->orderBy('confidence_score', 'desc')
                ->first();

            if ($vendorMapping && $vendorMapping->coa_id) {
                $vendorMapping->markAsUsed();
                return $vendorMapping->coa_id;
            }
        }

        return null;
    }

    /**
     * Map transaction type to payment method
     */
    protected function mapTransactionTypeToPaymentMethod(string $transactionType): string
    {
        return match($transactionType) {
            'debit' => 'credit_card',
            'credit' => 'credit_card',
            default => 'credit_card',
        };
    }

    /**
     * Map payment method to transaction type
     */
    protected function mapPaymentMethodToTransactionType(string $paymentMethod): string
    {
        return match($paymentMethod) {
            'cash' => 'cash',
            'credit_card', 'debit_card' => 'credit_card',
            'check' => 'check',
            'eft' => 'bank_transfer',
            default => 'credit_card',
        };
    }

    /**
     * Generate duplicate check hash for expense transaction
     * Uses Date + Vendor + Amount for duplicate detection
     */
    protected function generateExpenseDuplicateHash($storeId, $date, $vendorIdentifier, $amount): string
    {
        // Normalize amount to 2 decimal places
        $normalizedAmount = number_format((float)$amount, 2, '.', '');
        // Use consistent hash (MD5) for duplicate detection
        $data = $storeId . '|' . $date . '|' . ($vendorIdentifier ?? 'NULL') . '|' . $normalizedAmount;
        return md5($data);
    }

    /**
     * Extract card information (type and last 4 digits) from card number or description
     */
    protected function extractCardInfo($cardString): array
    {
        $result = ['last_four' => null, 'type' => null];
        
        if (!$cardString) {
            return $result;
        }

        // Extract last 4 digits
        if (preg_match('/(\d{4})\s*$/', $cardString, $matches)) {
            $result['last_four'] = $matches[1];
        }

        // Detect card type from card number patterns
        $cardString = preg_replace('/\s+/', '', $cardString);
        
        // Visa: starts with 4
        if (preg_match('/^4/', $cardString)) {
            $result['type'] = 'Visa';
        }
        // Mastercard: starts with 5
        elseif (preg_match('/^5[1-5]/', $cardString)) {
            $result['type'] = 'Mastercard';
        }
        // American Express: starts with 34 or 37
        elseif (preg_match('/^3[47]/', $cardString)) {
            $result['type'] = 'American Express';
        }
        // Discover: starts with 6
        elseif (preg_match('/^6/', $cardString)) {
            $result['type'] = 'Discover';
        }

        return $result;
    }

    /**
     * Extract vendor name from transaction description
     * Removes common prefixes/suffixes and card info
     */
    protected function extractVendorNameFromDescription($description): string
    {
        if (!$description) {
            return '';
        }

        // Remove common card-related prefixes
        $description = preg_replace('/^(POS|DEBIT|CREDIT|PURCHASE|AUTH)\s*/i', '', $description);
        
        // Remove card number patterns (e.g., "****1234")
        $description = preg_replace('/\*+\d{4}/', '', $description);
        
        // Remove date patterns
        $description = preg_replace('/\d{1,2}\/\d{1,2}\/\d{2,4}/', '', $description);
        
        // Clean up extra spaces
        $description = trim(preg_replace('/\s+/', ' ', $description));
        
        // Take first part (usually vendor name)
        $parts = explode(' ', $description);
        return implode(' ', array_slice($parts, 0, min(5, count($parts))));
    }
}
