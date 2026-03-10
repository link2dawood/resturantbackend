<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ThirdPartyStatement;
use App\Models\ExpenseTransaction;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;

class ThirdPartyImportController extends Controller
{
    /**
     * Import third-party platform statement
     * Supports: Grubhub (PDF), UberEats (CSV), DoorDash (CSV)
     */
    public function import(Request $request)
    {
        $request->validate([
            'platform' => 'required|in:grubhub,ubereats,doordash',
            'store_id' => 'required|exists:stores,id',
            'file' => 'required|file|mimes:pdf,csv,xlsx,xls|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $platform = $request->input('platform');
            $storeId = $request->input('store_id');

            // Generate file hash for duplicate detection
            $fileHash = md5_file($file->getRealPath());
            
            // Check for duplicate imports
            $existing = ThirdPartyStatement::where('file_hash', $fileHash)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'This file has already been imported',
                    'existing_statement' => $existing
                ], 409);
            }

            // Parse based on file type and platform
            $statementData = null;
            $extension = strtolower($file->getClientOriginalExtension());
            $isPdf = $extension === 'pdf';

            if ($isPdf) {
                // Monthly statement PDF (Grubhub, Uber, DoorDash – e.g. "Jan 2026_Fann's Philly Grill (Round Rock).pdf")
                $statementData = $this->parseMonthlyStatementPDF($file);
            } else {
                switch ($platform) {
                    case 'grubhub':
                        $statementData = $this->parseGrubhubPDF($file);
                        break;
                    case 'ubereats':
                        $statementData = $this->parseUberEatsCSV($file);
                        break;
                    case 'doordash':
                        $statementData = $this->parseDoorDashCSV($file);
                        break;
                }
            }

            if (!$statementData) {
                return response()->json([
                    'error' => 'Failed to parse statement'
                ], 400);
            }

            // Begin transaction
            DB::beginTransaction();

            // Create third-party statement
            $statement = ThirdPartyStatement::create([
                'platform' => $platform,
                'store_id' => $storeId,
                'statement_date' => $statementData['statement_date'] ?? now(),
                'statement_id' => $statementData['statement_id'] ?? null,
                'gross_sales' => $statementData['gross_sales'] ?? 0,
                'marketing_fees' => $statementData['marketing_fees'] ?? 0,
                'delivery_fees' => $statementData['delivery_fees'] ?? 0,
                'processing_fees' => $statementData['processing_fees'] ?? 0,
                'net_deposit' => $statementData['net_deposit'] ?? 0,
                'sales_tax_collected' => $statementData['sales_tax_collected'] ?? 0,
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'imported_by' => auth()->id(),
            ]);

            // Create expense transactions for fees
            $this->createFeeExpenses($statement, $statementData);

            // Create revenue transaction for gross sales
            $this->createRevenueTransaction($statement);

            // Create expected bank deposit
            $this->createExpectedDeposit($statement, $storeId);

            DB::commit();

            return response()->json([
                'message' => 'Statement imported successfully',
                'statement' => $statement,
                'expenses_created' => 3, // Marketing, Delivery, Processing
                'revenue_created' => 1,
                'expected_deposit_created' => 1,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Third-party import error: ' . $e->getMessage(), [
                'platform' => $request->input('platform'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse monthly statement PDF (works for Grubhub, Uber Eats, DoorDash monthly statements).
     * Handles formats like "Jan 2026_Fann's Philly Grill (Round Rock).pdf" and similar layouts.
     */
    protected function parseMonthlyStatementPDF($file)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();
            $filename = $file->getClientOriginalName();

            Log::info('Monthly statement PDF text extracted', ['length' => strlen($text), 'file' => $filename]);

            $statementDate = $this->extractDateFromMonthlyStatement($text, $filename);
            $grossSales = $this->extractAmountFromTextFlexible($text, [
                'gross sales', 'total sales', 'restaurant sales', 'sales', 'subtotal', 'marketplace sales',
                'total orders', 'order value',
            ], true);
            $marketingFees = $this->extractAmountFromTextFlexible($text, [
                'marketing fee', 'commission', 'platform fee', 'marketplace fee', 'marketplace commission',
                'partner fee', 'service fee', 'grubhub fee', 'ubereats fee', 'doordash fee',
            ], false);
            $deliveryFees = $this->extractAmountFromTextFlexible($text, [
                'delivery fee', 'delivery', 'delivery charges', 'delivery commission',
            ], false);
            $processingFees = $this->extractAmountFromTextFlexible($text, [
                'processing fee', 'payment processing', 'card processing', 'transaction fee',
            ], false);
            $netDeposit = $this->extractAmountFromTextFlexible($text, [
                'net deposit', 'total payment', 'payout', 'net payout', 'transfer', 'total transfer',
                'deposit', 'amount due', 'you will receive', 'payment to you',
            ], true);
            $salesTax = $this->extractAmountFromTextFlexible($text, ['sales tax', 'tax collected', 'tax'], true);

            return [
                'statement_date' => $statementDate ?: now(),
                'statement_id' => $this->extractStatementIdFromGrubhub($text),
                'gross_sales' => round($grossSales, 2),
                'marketing_fees' => round(abs($marketingFees), 2),
                'delivery_fees' => round(abs($deliveryFees), 2),
                'processing_fees' => round(abs($processingFees), 2),
                'net_deposit' => round(max(0, $netDeposit), 2),
                'sales_tax_collected' => round($salesTax, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Monthly statement PDF parse error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'statement_date' => now(),
                'statement_id' => null,
                'gross_sales' => 0,
                'marketing_fees' => 0,
                'delivery_fees' => 0,
                'processing_fees' => 0,
                'net_deposit' => 0,
                'sales_tax_collected' => 0,
            ];
        }
    }

    /**
     * Extract date from monthly statement text or filename (e.g. "Jan 2026", "January 2026").
     */
    protected function extractDateFromMonthlyStatement(string $text, string $filename = ''): ?string
    {
        $combined = $text . "\n" . $filename;

        $patterns = [
            '/\b(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+(\d{4})\b/i',
            '/\b(January|February|March|April|May|June|July|August|September|October|November|December)\s+(\d{4})\b/i',
            '/statement\s+date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/for\s+the\s+period[:\s]+.*?(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/ending[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/through[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\s*-\s*\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/',
        ];

        $monthNames = [
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04', 'may' => '05', 'jun' => '06',
            'jul' => '07', 'aug' => '08', 'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $combined, $matches)) {
                if (isset($matches[2]) && isset($monthNames[strtolower(substr($matches[1], 0, 3))])) {
                    $month = $monthNames[strtolower(substr($matches[1], 0, 3))];
                    $year = $matches[2];
                    return $year . '-' . $month . '-01';
                }
                if (isset($matches[1]) && (strpos($matches[1], '/') !== false || strpos($matches[1], '-') !== false)) {
                    $parsed = $this->parseDate($matches[1]);
                    if ($parsed) {
                        return $parsed;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Extract amount from text with flexible patterns: same line, next line, parentheses for negatives.
     * $preferPositive: for gross/net use positive; for fees we take absolute value.
     */
    protected function extractAmountFromTextFlexible(string $text, array $keywords, bool $preferPositive): float
    {
        $amounts = [];
        $lines = preg_split('/\r\n|\r|\n/', $text);

        foreach ($keywords as $keyword) {
            $quoted = preg_quote($keyword, '/');
            $patternSameLine = '/' . $quoted . '\s*[:\$]?\s*\$?\s*(\([\d,]+\.?\d*\)|[\d,]+\.?\d*)/i';
            if (preg_match_all($patternSameLine, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $amount = $this->parseAmount(trim($match[1]));
                    if ($amount != 0) {
                        $amounts[] = $amount;
                    }
                }
            }
            $patternDollar = '/' . $quoted . '[^\d\$]*(\$?\s*\([\d,]+\.?\d*\)|\$?\s*[\d,]+\.?\d*)/i';
            if (preg_match_all($patternDollar, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $amount = $this->parseAmount(trim($match[1]));
                    if ($amount != 0) {
                        $amounts[] = $amount;
                    }
                }
            }
            for ($i = 0; $i < count($lines) - 1; $i++) {
                if (preg_match('/' . $quoted . '/i', $lines[$i])) {
                    if (preg_match('/(\$?\s*\([\d,]+\.?\d*\)|\$?\s*[\d,]+\.?\d*)/', $lines[$i + 1], $m)) {
                        $amount = $this->parseAmount(trim($m[1]));
                        if ($amount != 0) {
                            $amounts[] = $amount;
                        }
                    }
                }
            }
        }

        if (empty($amounts)) {
            return 0.0;
        }
        if ($preferPositive) {
            $positive = array_filter($amounts, fn($a) => $a > 0);
            return !empty($positive) ? max($positive) : abs(array_sum($amounts));
        }
        return abs(array_sum($amounts));
    }

    /**
     * Parse Grubhub PDF statement using PDF parser
     */
    protected function parseGrubhubPDF($file)
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($file->getRealPath());
            $text = $pdf->getText();
            
            // Log extracted text for debugging
            Log::info('Grubhub PDF text extracted', ['length' => strlen($text)]);
            
            // Extract data using regex patterns
            $statementDate = $this->extractDateFromGrubhub($text);
            $grossSales = $this->extractAmountFromText($text, ['gross sales', 'total sales', 'restaurant sales']);
            $marketingFees = $this->extractAmountFromText($text, ['marketing fee', 'commission', 'platform fee']);
            $deliveryFees = $this->extractAmountFromText($text, ['delivery fee', 'delivery']);
            $processingFees = $this->extractAmountFromText($text, ['processing fee', 'payment processing']);
            $netDeposit = $this->extractAmountFromText($text, ['net deposit', 'total payment', 'payout']);
            $salesTax = $this->extractAmountFromText($text, ['sales tax', 'tax collected']);
            
            return [
                'statement_date' => $statementDate ?: now(),
                'statement_id' => $this->extractStatementIdFromGrubhub($text),
                'gross_sales' => $grossSales,
                'marketing_fees' => $marketingFees,
                'delivery_fees' => $deliveryFees,
                'processing_fees' => $processingFees,
                'net_deposit' => $netDeposit,
                'sales_tax_collected' => $salesTax,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error parsing Grubhub PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty structure on error
            return [
                'statement_date' => now(),
                'statement_id' => null,
                'gross_sales' => 0,
                'marketing_fees' => 0,
                'delivery_fees' => 0,
                'processing_fees' => 0,
                'net_deposit' => 0,
                'sales_tax_collected' => 0,
            ];
        }
    }
    
    /**
     * Extract date from Grubhub PDF text
     */
    protected function extractDateFromGrubhub(string $text): ?string
    {
        // Look for date patterns like "Statement Date: 01/15/2024" or "Period: Jan 15 - Jan 31, 2024"
        $patterns = [
            '/statement\s+date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/period[:\s]+\w+\s+\d{1,2}[,\s]+\d{4}/i',
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $date = $this->parseDate($matches[1] ?? $matches[0]);
                if ($date) {
                    return $date;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract statement ID from Grubhub PDF
     */
    protected function extractStatementIdFromGrubhub(string $text): ?string
    {
        // Look for patterns like "Statement #: 12345" or "Invoice #: 12345"
        $patterns = [
            '/statement\s*#?\s*[:\s]+([A-Z0-9\-]+)/i',
            '/invoice\s*#?\s*[:\s]+([A-Z0-9\-]+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return null;
    }
    
    /**
     * Extract amount from text using multiple patterns
     */
    protected function extractAmountFromText(string $text, array $keywords): float
    {
        $amounts = [];
        
        // Search for each keyword
        foreach ($keywords as $keyword) {
            // Create pattern to match amount after keyword
            $pattern = '/' . preg_quote($keyword, '/') . '\s*[:\$]?\s*\$?\s*([\d,]+\.?\d*)/i';
            
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $amount = $this->parseAmount($match[1]);
                    if ($amount > 0) {
                        $amounts[] = $amount;
                    }
                }
            }
        }
        
        // Return the most common amount or average
        if (!empty($amounts)) {
            return array_sum($amounts);
        }
        
        return 0;
    }
    
    /**
     * Get default COA for a platform
     */
    protected function getDefaultCoaForPlatform(string $platform): ?ChartOfAccount
    {
        // Map platforms to their default COA
        $coaMapping = [
            'grubhub' => 'Marketing Fees (Grubhub)',
            'ubereats' => 'Marketing Fees (Grubhub)', // Reuse Grubhub category
            'doordash' => 'Marketing Fees (Grubhub)', // Reuse Grubhub category
        ];
        
        $coaName = $coaMapping[$platform] ?? null;
        if ($coaName) {
            return ChartOfAccount::where('account_name', $coaName)->first();
        }
        
        return null;
    }

    /**
     * Parse UberEats CSV statement
     * Expected format: Date, Gross Sales, Marketing Fees, Delivery Fees, Processing Fees, Net Deposit
     */
    protected function parseUberEatsCSV($file)
    {
        try {
            $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (count($lines) < 2) {
                throw new \Exception('Invalid UberEats CSV format - insufficient data');
            }
            
            // Parse header to find column indices
            $header = str_getcsv(array_shift($lines));
            $headerMap = [];
            
            foreach ($header as $index => $column) {
                $normalized = strtolower(trim($column));
                $headerMap[$normalized] = $index;
            }
            
            // Aggregate totals from all rows
            $grossSales = 0;
            $marketingFees = 0;
            $deliveryFees = 0;
            $processingFees = 0;
            $netDeposit = 0;
            $firstDate = null;
            $lastDate = null;
            
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                
                // Try to find date column
                $date = null;
                if (isset($headerMap['date'])) {
                    $date = $this->parseDate($row[$headerMap['date']]);
                } elseif (isset($headerMap['transaction date'])) {
                    $date = $this->parseDate($row[$headerMap['transaction date']]);
                } elseif (isset($headerMap['statement date'])) {
                    $date = $this->parseDate($row[$headerMap['statement date']]);
                }
                
                if (!$firstDate) $firstDate = $date;
                $lastDate = $date ?: $lastDate;
                
                // Parse amounts based on column headers
                if (isset($headerMap['gross sales']) || isset($headerMap['subtotal'])) {
                    $col = $headerMap['gross sales'] ?? $headerMap['subtotal'];
                    $grossSales += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['marketing fees']) || isset($headerMap['commission'])) {
                    $col = $headerMap['marketing fees'] ?? $headerMap['commission'];
                    $marketingFees += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['delivery fees'])) {
                    $deliveryFees += $this->parseAmount($row[$headerMap['delivery fees']] ?? '0');
                }
                
                if (isset($headerMap['processing fees']) || isset($headerMap['payment processing'])) {
                    $col = $headerMap['processing fees'] ?? $headerMap['payment processing'];
                    $processingFees += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['net deposit']) || isset($headerMap['payout'])) {
                    $col = $headerMap['net deposit'] ?? $headerMap['payout'];
                    $netDeposit += $this->parseAmount($row[$col] ?? '0');
                }
            }
            
            return [
                'statement_date' => $lastDate ?: now(),
                'statement_id' => null,
                'gross_sales' => max(0, $grossSales),
                'marketing_fees' => abs($marketingFees),
                'delivery_fees' => abs($deliveryFees),
                'processing_fees' => abs($processingFees),
                'net_deposit' => max(0, $netDeposit),
                'sales_tax_collected' => 0,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error parsing UberEats CSV: ' . $e->getMessage());
            return [
                'statement_date' => now(),
                'statement_id' => null,
                'gross_sales' => 0,
                'marketing_fees' => 0,
                'delivery_fees' => 0,
                'processing_fees' => 0,
                'net_deposit' => 0,
                'sales_tax_collected' => 0,
            ];
        }
    }

    /**
     * Parse DoorDash CSV statement
     * Expected format: Date, Gross Sales, Marketing Fees, Delivery Fees, Processing Fees, Net Deposit
     */
    protected function parseDoorDashCSV($file)
    {
        try {
            $lines = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if (count($lines) < 2) {
                throw new \Exception('Invalid DoorDash CSV format - insufficient data');
            }
            
            // Parse header to find column indices
            $header = str_getcsv(array_shift($lines));
            $headerMap = [];
            
            foreach ($header as $index => $column) {
                $normalized = strtolower(trim($column));
                $headerMap[$normalized] = $index;
            }
            
            // Aggregate totals from all rows
            $grossSales = 0;
            $marketingFees = 0;
            $deliveryFees = 0;
            $processingFees = 0;
            $netDeposit = 0;
            $firstDate = null;
            $lastDate = null;
            
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                
                // Try to find date column
                $date = null;
                if (isset($headerMap['date'])) {
                    $date = $this->parseDate($row[$headerMap['date']]);
                } elseif (isset($headerMap['transaction date'])) {
                    $date = $this->parseDate($row[$headerMap['transaction date']]);
                } elseif (isset($headerMap['statement date'])) {
                    $date = $this->parseDate($row[$headerMap['statement date']]);
                }
                
                if (!$firstDate) $firstDate = $date;
                $lastDate = $date ?: $lastDate;
                
                // Parse amounts based on column headers
                if (isset($headerMap['gross sales']) || isset($headerMap['subtotal']) || isset($headerMap['total sales'])) {
                    $col = $headerMap['gross sales'] ?? ($headerMap['subtotal'] ?? $headerMap['total sales']);
                    $grossSales += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['marketing fees']) || isset($headerMap['platform fee'])) {
                    $col = $headerMap['marketing fees'] ?? $headerMap['platform fee'];
                    $marketingFees += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['delivery fees']) || isset($headerMap['delivery'])) {
                    $col = $headerMap['delivery fees'] ?? $headerMap['delivery'];
                    $deliveryFees += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['processing fees']) || isset($headerMap['payment processing'])) {
                    $col = $headerMap['processing fees'] ?? $headerMap['payment processing'];
                    $processingFees += $this->parseAmount($row[$col] ?? '0');
                }
                
                if (isset($headerMap['net deposit']) || isset($headerMap['payout']) || isset($headerMap['net payment'])) {
                    $col = $headerMap['net deposit'] ?? ($headerMap['payout'] ?? $headerMap['net payment']);
                    $netDeposit += $this->parseAmount($row[$col] ?? '0');
                }
            }
            
            return [
                'statement_date' => $lastDate ?: now(),
                'statement_id' => null,
                'gross_sales' => max(0, $grossSales),
                'marketing_fees' => abs($marketingFees),
                'delivery_fees' => abs($deliveryFees),
                'processing_fees' => abs($processingFees),
                'net_deposit' => max(0, $netDeposit),
                'sales_tax_collected' => 0,
            ];
            
        } catch (\Exception $e) {
            Log::error('Error parsing DoorDash CSV: ' . $e->getMessage());
            return [
                'statement_date' => now(),
                'statement_id' => null,
                'gross_sales' => 0,
                'marketing_fees' => 0,
                'delivery_fees' => 0,
                'processing_fees' => 0,
                'net_deposit' => 0,
                'sales_tax_collected' => 0,
            ];
        }
    }

    /**
     * Create expense transactions for platform fees
     */
    protected function createFeeExpenses(ThirdPartyStatement $statement, array $data)
    {
        $platformName = ucfirst($statement->platform);
        
        // Get or create vendor for platform
        $vendor = Vendor::where('vendor_name', $platformName)->first();
        if (!$vendor) {
            // Get appropriate default COA based on platform
            $defaultCoa = $this->getDefaultCoaForPlatform($statement->platform);
            
            $vendor = Vendor::create([
                'vendor_name' => $platformName,
                'vendor_identifier' => $platformName,
                'vendor_type' => 'Services',
                'default_coa_id' => $defaultCoa?->id,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        }

        // Get COA categories for fees
        $marketingCoa = ChartOfAccount::where('account_name', 'Marketing Fees (Grubhub)')->first();
        $deliveryCoa = ChartOfAccount::where('account_name', 'Delivery Service Fees')->first();
        $processingCoa = ChartOfAccount::where('account_name', 'Merchant Processing Fees')->first();

        // Create marketing fee expense if exists
        if ($data['marketing_fees'] > 0 && $marketingCoa) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $marketingCoa->id,
                'amount' => $data['marketing_fees'],
                'description' => "{$platformName} marketing fee - {$statement->statement_date->format('M d, Y')}",
                'payment_method' => 'credit_card',
                'third_party_statement_id' => $statement->id,
                'created_by' => auth()->id(),
                'duplicate_check_hash' => md5($statement->id . 'marketing'),
            ]);
        }

        // Create delivery fee expense if exists
        if ($data['delivery_fees'] > 0 && $deliveryCoa) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $deliveryCoa->id,
                'amount' => $data['delivery_fees'],
                'description' => "{$platformName} delivery fee - {$statement->statement_date->format('M d, Y')}",
                'payment_method' => 'credit_card',
                'third_party_statement_id' => $statement->id,
                'created_by' => auth()->id(),
                'duplicate_check_hash' => md5($statement->id . 'delivery'),
            ]);
        }

        // Create processing fee expense if exists
        if ($data['processing_fees'] > 0 && $processingCoa) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $processingCoa->id,
                'amount' => $data['processing_fees'],
                'description' => "{$platformName} processing fee - {$statement->statement_date->format('M d, Y')}",
                'payment_method' => 'credit_card',
                'third_party_statement_id' => $statement->id,
                'created_by' => auth()->id(),
                'duplicate_check_hash' => md5($statement->id . 'processing'),
            ]);
        }
    }

    /**
     * Create revenue transaction for gross sales
     */
    protected function createRevenueTransaction(ThirdPartyStatement $statement)
    {
        // TODO: Create revenue transaction in daily_reports or revenue tracking
        // This would require integration with revenue tracking system
        Log::info('Revenue transaction creation for third-party platform not yet implemented');
    }

    /**
     * Create expected bank deposit transaction
     */
    protected function createExpectedDeposit(ThirdPartyStatement $statement, int $storeId)
    {
        if ($statement->net_deposit <= 0) {
            return;
        }

        // Find bank account for store
        $bankAccount = \App\Models\BankAccount::where('account_type', 'checking')
            ->where(function($q) use ($storeId) {
                $q->where('store_id', $storeId)
                  ->orWhereNull('store_id');
            })
            ->where('is_active', true)
            ->first();

        if ($bankAccount) {
            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'transaction_date' => $statement->statement_date,
                'transaction_type' => 'credit',
                'amount' => $statement->net_deposit,
                'description' => "Expected {$statement->platform} deposit for {$statement->statement_date->format('M d, Y')}",
                'reference_number' => "{$statement->platform}-{$statement->id}",
                'reconciliation_status' => 'unmatched',
                'import_batch_id' => null,
                'duplicate_check_hash' => md5("{$statement->platform}-{$statement->id}"),
            ]);
        }
    }

    /**
     * List imported third-party statements
     */
    public function history(Request $request)
    {
        $query = ThirdPartyStatement::with(['store', 'importer']);

        if ($request->has('store_id')) {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        $statements = $query->orderBy('statement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($statements);
    }

    /**
     * Get details of a specific statement
     */
    public function show($id)
    {
        $statement = ThirdPartyStatement::with([
            'store', 
            'importer', 
            'expenses.vendor', 
            'expenses.coa'
        ])->findOrFail($id);

        return response()->json($statement);
    }

    /**
     * Parse date string into Y-m-d format
     */
    protected function parseDate(string $dateString): ?string
    {
        $dateString = trim($dateString);
        
        if (empty($dateString)) {
            return null;
        }

        $formats = [
            'Y-m-d',           // 2024-01-15
            'm/d/Y',           // 01/15/2024
            'm-d-Y',           // 01-15-2024
            'Y/m/d',           // 2024/01/15
            'M d, Y',          // Jan 15, 2024
        ];

        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $dateString);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Parse amount string into float
     */
    protected function parseAmount(string $amountString): ?float
    {
        $amountString = str_replace(['$', ',', ' '], '', trim($amountString));

        if (empty($amountString)) {
            return 0;
        }

        if (preg_match('/\(([\d.]+)\)/', $amountString, $matches)) {
            return -floatval($matches[1]);
        }

        $amount = floatval($amountString);
        return is_numeric($amount) ? $amount : 0;
    }
}
