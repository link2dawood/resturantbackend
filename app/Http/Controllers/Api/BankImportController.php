<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ImportBatch;
use App\Models\DailyReport;
use App\Models\ExpenseTransaction;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
                    BankTransaction::create([
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
                'status' => 'completed',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Bank statement imported successfully',
                'imported' => $importedCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount,
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

        // Chase format detection
        if (strpos($firstLine, 'chase') !== false || strpos($firstLine, 'card') !== false) {
            return 'chase';
        }

        // Generic format with common headers
        if (strpos($firstLine, 'date') !== false && strpos($firstLine, 'amount') !== false) {
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

            return [
                'transaction_date' => $date,
                'post_date' => $date,
                'description' => $row[1] ?? '',
                'transaction_type' => $amount < 0 ? 'debit' : 'credit',
                'amount' => abs($amount),
                'balance' => isset($row[3]) ? $this->parseAmount($row[3]) : null,
                'reference_number' => $row[4] ?? null,
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

            return [
                'transaction_date' => $this->parseDate($row[1]) ?? $date,
                'post_date' => $date,
                'description' => $row[3] ?? '',
                'transaction_type' => strtolower($row[6]) === 'debit' ? 'debit' : 'credit',
                'amount' => abs($amount),
                'reference_number' => $row[0] ?? null, // Card number
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
}
