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

/**
 * Third-party platform statement import (Grubhub, UberEats, DoorDash).
 * PDF extraction uses Smalot PDF Parser (text-based PDFs): parseFile → getText(),
 * then regex/string matching to extract totals (Sales, Commission, Net total, etc.).
 */
class ThirdPartyImportController extends Controller
{
    /**
     * Fields we expect to reliably extract for a third-party statement.
     * (statement_id and sales_tax_collected are optional depending on platform/PDF.)
     */
    protected const REQUIRED_STATEMENT_FIELDS = [
        'statement_date',
        'gross_sales',
        'net_deposit',
        'marketing_fees',
        'delivery_fees',
        'processing_fees',
    ];

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

        $storeId = (int) $request->input('store_id');
        $user = auth()->user();
        if (! $user->hasStoreAccess($storeId)) {
            return response()->json(['error' => 'You do not have access to the selected store.'], 403);
        }

        try {
            $file = $request->file('file');
            $platform = $request->input('platform');

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
                $statementData = $this->parseMonthlyStatementPDF($file, $platform);
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

            $this->assertStatementDataIsUsable($statementData);

            // Begin transaction
            DB::beginTransaction();

            // Create third-party statement (file_path set after we store the file)
            $statement = ThirdPartyStatement::create([
                'platform' => $platform,
                'store_id' => $storeId,
                'statement_date' => $statementData['statement_date'] ?? now(),
                'statement_id' => $statementData['statement_id'] ?? null,
                'gross_sales' => $statementData['gross_sales'] ?? 0,
                'marketing_fees' => $statementData['marketing_fees'] ?? 0,
                'delivery_fees' => $statementData['delivery_fees'] ?? 0,
                'processing_fees' => $statementData['processing_fees'] ?? 0,
                'adjustments' => $statementData['adjustments'] ?? 0,
                'net_deposit' => $statementData['net_deposit'] ?? 0,
                'sales_tax_collected' => $statementData['sales_tax_collected'] ?? 0,
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'imported_by' => auth()->id(),
            ]);

            // Store the uploaded file on disk and save path in table
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $storedPath = $file->storeAs(
                'third_party_statements',
                $statement->id . '_' . $safeName,
                'local'
            );
            if ($storedPath) {
                $statement->update(['file_path' => $storedPath]);
            }

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

        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            Log::warning('Third-party import parse failed: ' . $e->getMessage(), [
                'platform' => $request->input('platform'),
                'file' => $request->file('file')?->getClientOriginalName(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
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
    protected function parseMonthlyStatementPDF($file, string $platform)
    {
        $filename = $file->getClientOriginalName();
        $extracted = $this->extractPdfText($file);
        $text = $this->normalizeExtractedText($extracted['text'] ?? '');

        Log::info('Monthly statement PDF text extracted', [
            'platform' => $platform,
            'file' => $filename,
            'length' => strlen($text),
        ]);

        // If extraction is empty, fail fast with a clear error
        if (trim($text) === '') {
            throw new \InvalidArgumentException('Failed to parse statement: PDF text extraction returned empty text.');
        }

        // Platform-specific extraction (monthly PDFs vary significantly by platform)
        $data = match ($platform) {
            'doordash' => $this->extractDoorDashMonthlyStatementDataFromText($text, $filename),
            'grubhub' => $this->extractGrubhubMonthlyStatementDataFromText($text, $filename),
            'ubereats' => $this->extractUberMonthlyStatementDataFromText($text, $filename),
            default => $this->extractMonthlyStatementDataFromText($text, $filename),
        };
        $data['platform'] = $platform;

        $this->assertStatementDataIsUsable($data, $text);
        return $data;
    }

    /**
     * Extract statement data from normalized monthly statement text.
     */
    protected function extractMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
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
            'statement_id' => $this->extractStatementIdFromText($text),
            'gross_sales' => round($grossSales, 2),
            'marketing_fees' => round(abs($marketingFees), 2),
            'delivery_fees' => round(abs($deliveryFees), 2),
            'processing_fees' => round(abs($processingFees), 2),
            'net_deposit' => round(max(0, $netDeposit), 2),
            'sales_tax_collected' => round($salesTax, 2),
        ];
    }

    /**
     * DoorDash monthly statement PDF (text) extractor.
     * Example labels in PDFs: "Subtotal $X", "Tax (subtotal) $X", "Commission -$X",
     * "Marketing fees -$X", "Merchant fees $X", "Error charges -$X", "Net total $X".
     */
    protected function extractDoorDashMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
        $statementDate = $this->extractDateFromMonthlyStatement($text, $filename);

        // DoorDash PDFs include a consolidated summary on Page 1, then payout rows and appendix.
        // To avoid duplicates and keyword matches in the wrong sections, restrict parsing to the summary block.
        // Use "Page 1 of" as the end marker: in Smalot's text extraction, the page 2 payout table data
        // appears BEFORE "Page 2 of" (the body is encoded before the page header in DoorDash PDFs),
        // so "Page 2 of" was letting through per-payout marketing fees lines. "Page 1 of" appears
        // after the page 1 financial summary but before any page 2 content.
        $summaryBlock = $this->extractTextBetweenMarkers($text, 'Sales (', 'Page 1 of');
        if (trim($summaryBlock) === '') {
            $summaryBlock = $text;
        }

        $subtotal = $this->extractAmountFromTextWithCents($summaryBlock, ['subtotal'], true, false);
        $taxSubtotal = $this->extractAmountFromTextWithCents($summaryBlock, ['tax (subtotal)'], true, false);

        // Fees
        // DoorDash has multiple commission rows. We want "Commission & fees" (e.g. -$126.12)
        // to avoid double-counting "Commission -$124.50".
        $commissionAndFees = $this->extractAmountFromTextWithCents($summaryBlock, ['commission & fees'], false, false);

        // Using only "marketing fees" avoids duplicate captures.
        $marketingFees = $this->extractAmountFromTextWithCents($summaryBlock, ['marketing fees'], false, false);

        // Your PDF definitions:
        // - "Amendments" = total error charges and other adjustments.
        // Map DoorDash "adjustments" in our DB/UI to that total amendments amount.
        $amendments = $this->extractAmountFromTextWithCents($summaryBlock, ['amendments'], false, false);
        if ($amendments === 0.0) {
            // Fallback if a standalone "Amendments" line isn't present:
            // total error charges + any separate one-time adjustments line.
            $errorCharges = $this->extractAmountFromTextWithCents($summaryBlock, ['error charges'], false, false);
            $otherAdjustments = $this->extractAmountFromTextWithCents($summaryBlock, ['adjustments'], false, false);
            $amendments = $errorCharges + $otherAdjustments;
        }

        // Net total is the best net-deposit proxy for DoorDash monthly statements.
        $netTotal = $this->extractAmountFromTextWithCents($summaryBlock, ['net total'], true, false);

        return [
            'statement_date' => $statementDate ?: now(),
            'statement_id' => $this->extractStatementIdFromText($text),
            // Treat subtotal + tax as gross sales (closest comparable to other platforms)
            'gross_sales' => round(max(0, $subtotal + $taxSubtotal), 2),
            'marketing_fees' => round(abs($marketingFees), 2),
            // DoorDash monthly statement doesn't reliably separate delivery fees in the summary
            'delivery_fees' => 0,
            // Use commission & fees as "processing_fees"
            'processing_fees' => round(abs($commissionAndFees), 2),
            // Amendments roll into Adjustments
            'adjustments' => round(abs($amendments), 2),
            'net_deposit' => round(max(0, $netTotal), 2),
            'sales_tax_collected' => round(max(0, $taxSubtotal), 2),
        ];
    }

    /**
     * Grubhub statement PDF (text) extractor.
     * Uses "Total payments to you" as net deposit and "Restaurant sales" as gross.
     * Marketing / Deliveries by Grubhub / Order processing appear as (X.XX).
     */
    protected function extractGrubhubMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
        $statementDate = $this->extractDateFromMonthlyStatement($text, $filename) ?: $this->extractDateFromGrubhub($text);

        $read = function (string $pattern, bool $abs = true) use ($text) {
            if (! preg_match($pattern, $text, $m)) {
                return 0.0;
            }
            $val = $this->parseAmount($m[1] ?? null);
            if ($abs) {
                $val = abs($val);
            }
            return (float) $val;
        };

        // Your sample Grubhub PDF text looks like:
        // Total payments to you $ 37.75
        // Restaurant sales for 2 orders $ 22.18
        // Grubhub order services $ (6.10)
        // 1 Marketing (3.07)
        // 3 Deliveries by Grubhub (2.05)
        // 1 Order processing (0.98)
        // Includes $1.69 in taxes...
        // Account adjustments $ 21.67
        $gross = $read('/Restaurant\s+sales\s+for\s+\d+\s+orders\s*\$?\s*([0-9\.,]+)\b/i');
        $net = $read('/Total\s+payments\s+to\s+you\s*\$?\s*([0-9\.,]+)\b/i');

        $marketing = $read('/\bMarketing\s*\(\s*([0-9\.,]+)\s*\)/i');
        $delivery = $read('/\bDeliveries\s+by\s+Grubhub\s*\(\s*([0-9\.,]+)\s*\)/i');
        $processing = $read('/\bOrder\s+processing\s*\(\s*([0-9\.,]+)\s*\)/i');

        $tax = $read('/Includes\s*\$?\s*([0-9\.,]+)\s*in\s+taxes/i', true);
        $adjustments = $read('/Account\s+adjustments\s*\$?\s*([0-9\.,]+)\b/i');

        return [
            'statement_date' => $statementDate ?: now(),
            'statement_id' => $this->extractStatementIdFromText($text),
            'gross_sales' => round(max(0, $gross), 2),
            'marketing_fees' => round(max(0, $marketing), 2),
            'delivery_fees' => round(max(0, $delivery), 2),
            'processing_fees' => round(max(0, $processing), 2),
            'adjustments' => round(max(0, $adjustments), 2),
            'net_deposit' => round(max(0, $net), 2),
            'sales_tax_collected' => round(max(0, $tax), 2),
        ];
    }

    /**
     * Uber Eats monthly statement PDF (text) extractor.
     * Uses "Consolidated Monthly Summary" totals:
     * - gross_sales: Total Earnings
     * - processing_fees: Total Uber Fees
     * - marketing_fees: Total Marketing Spends
     * - adjustments: Total Amendments
     * - net_deposit: Net Total
     * Taxes: Tax on Sales + Tax on Container Fees + Tax on Other Earnings
     */
    protected function extractUberMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
        $statementDate = $this->extractDateFromMonthlyStatement($text, $filename) ?: now();

        // Prefer extracting from the consolidated summary block to avoid duplications in payouts tables.
        $summaryBlock = $this->extractTextBetweenMarkers(
            $text,
            'Consolidated Monthly Summary',
            'Payouts received in the month'
        );

        $gross = $this->extractAmountFromTextFlexible($summaryBlock, ['total earnings'], true);
        $net = $this->extractAmountFromTextFlexible($summaryBlock, ['net total'], true);

        $processing = $this->extractAmountFromTextFlexible($summaryBlock, ['total uber fees'], false);
        $marketing = $this->extractAmountFromTextFlexible($summaryBlock, ['total marketing spends'], false);
        $adjustments = $this->extractAmountFromTextFlexible($summaryBlock, ['total amendments'], false);

        $taxSales = $this->extractAmountFromTextFlexible($summaryBlock, ['tax on sales'], true);
        $taxContainer = $this->extractAmountFromTextFlexible($summaryBlock, ['tax on container fees'], true);
        $taxOtherEarnings = $this->extractAmountFromTextFlexible($summaryBlock, ['tax on other earnings'], true);
        $tax = $taxSales + $taxContainer + $taxOtherEarnings;

        return [
            'statement_date' => $statementDate,
            'statement_id' => $this->extractStatementIdFromText($text),
            'gross_sales' => round(max(0, $gross), 2),
            'marketing_fees' => round(abs($marketing), 2),
            'delivery_fees' => 0,
            'processing_fees' => round(abs($processing), 2),
            'adjustments' => round(abs($adjustments), 2),
            'net_deposit' => round(max(0, $net), 2),
            'sales_tax_collected' => round(max(0, $tax), 2),
        ];
    }

    /**
     * Extract a substring between two markers (case-insensitive).
     * If either marker is missing, falls back to the full text.
     */
    protected function extractTextBetweenMarkers(string $text, string $startMarker, string $endMarker): string
    {
        $startPos = mb_stripos($text, $startMarker);
        if ($startPos === false) {
            return $text;
        }

        $endPos = mb_stripos($text, $endMarker, $startPos + mb_strlen($startMarker));
        if ($endPos === false) {
            return mb_substr($text, $startPos);
        }

        return mb_substr($text, $startPos, $endPos - $startPos);
    }

    /**
     * Extract a platform-agnostic statement identifier from PDF text.
     * Avoids false positives like "January 2026 Statement" -> "Jan".
     */
    protected function extractStatementIdFromText(string $text): ?string
    {
        $patterns = [
            '/statement\\s*(number)?\\s*#\\s*([A-Z0-9\\-]{6,})/i',
            '/statement\\s*#\\s*([A-Z0-9\\-]{6,})/i',
            '/invoice\\s*#\\s*([A-Z0-9\\-]{6,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $id = trim($m[count($m) - 1]);
                return $id !== '' ? $id : null;
            }
        }
        return null;
    }

    /**
     * Extract raw text from a PDF using Smalot PDF Parser (text-based PDFs).
     * Pattern: Parser::parseFile() → getText() → then parse with regex/string matching.
     */
    protected function extractPdfText($file): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getRealPath());
        $text = $pdf->getText();

        $pages = [];
        try {
            foreach ($pdf->getPages() as $page) {
                $pages[] = $page->getText();
            }
        } catch (\Throwable $e) {
            // Some PDFs may not enumerate pages cleanly; ignore and use full text.
        }

        return [
            'text' => $text ?? '',
            'pages' => $pages,
        ];
    }

    /**
     * Normalize extracted text so regex rules are more stable across PDFs.
     */
    protected function normalizeExtractedText(string $text): string
    {
        if ($text === '') {
            return '';
        }
        // Normalize whitespace and remove weird non-breaking spaces
        $text = str_replace(["\u{00A0}", "\u{2007}", "\u{202F}"], ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }

    /**
     * Ensure parsed data has the minimum fields needed for DB + downstream records.
     * If parsing looks suspicious, throw InvalidArgumentException (returns 400 to UI).
     */
    protected function assertStatementDataIsUsable(?array $data, ?string $debugText = null): void
    {
        if (! is_array($data)) {
            throw new \InvalidArgumentException('Failed to parse statement: no data was extracted.');
        }

        foreach (self::REQUIRED_STATEMENT_FIELDS as $field) {
            if (! array_key_exists($field, $data)) {
                throw new \InvalidArgumentException("Failed to parse statement: missing field {$field}.");
            }
        }

        // Validate key numeric fields (gross/net must be > 0 for a statement)
        $gross = (float) ($data['gross_sales'] ?? 0);
        $net = (float) ($data['net_deposit'] ?? 0);
        $fees = (float) ($data['marketing_fees'] ?? 0) + (float) ($data['delivery_fees'] ?? 0) + (float) ($data['processing_fees'] ?? 0);

        // Hard sanity bounds to catch mis-parses like "2026" or "Store ID" being treated as money
        $maxReasonable = 1000000.0;
        foreach (['gross_sales' => $gross, 'net_deposit' => $net, 'total_fees' => $fees] as $k => $v) {
            if (abs($v) > $maxReasonable) {
                if ($debugText !== null) {
                    Log::info('Third-party parse debug (first 1200 chars)', [
                        'snippet' => mb_substr($debugText, 0, 1200),
                    ]);
                }
                throw new \InvalidArgumentException("Failed to parse statement: extracted {$k} looks invalid ({$v}).");
            }
        }
        if ($gross <= 0 && $net <= 0) {
            if ($debugText !== null) {
                Log::info('Third-party parse debug (first 1200 chars)', [
                    'snippet' => mb_substr($debugText, 0, 1200),
                ]);
            }
            throw new \InvalidArgumentException('Failed to parse statement: could not find gross sales or net deposit.');
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
     * Extract money values that always include cents (e.g. $240.38).
     * This helps avoid capturing integers like "2026" (year) or "Store ID" values.
     */
    protected function extractAmountFromTextWithCents(string $text, array $keywords, bool $preferPositive, bool $allowNextLineFallback = true): float
    {
        // IMPORTANT:
        // Extract amounts per-line so a "keyword match" cannot accidentally
        // grab an amount from a different line (this was causing wrong DoorDash taxes).
        $amounts = [];
        $lines = preg_split('/\r\n|\r|\n/', $text);

        // Match amounts with cents, including styles:
        //   -$126.12
        //   $-126.12
        //   (123.45)
        $amountRegex = '/(?:\(?-?\$?[\d,]+\.\d{2}\)?|\(?\$\-?[\d,]+\.\d{2}\)?)/';

        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            if ($keyword === '') {
                continue;
            }

            foreach ($lines as $i => $line) {
                if (stripos($line, $keyword) === false) {
                    continue;
                }

                // Prefer the closest cents-amount on the same line.
                preg_match_all($amountRegex, $line, $matches, PREG_OFFSET_CAPTURE);
                if (! empty($matches[0])) {
                    $keywordPos = stripos($line, $keyword);
                    $bestRaw = null;
                    $bestDist = PHP_INT_MAX;

                    foreach ($matches[0] as $m) {
                        [$raw, $offset] = $m;
                        if ($raw === '' || $offset === null) {
                            continue;
                        }
                        $dist = abs($offset - $keywordPos);
                        if ($dist < $bestDist) {
                            $bestDist = $dist;
                            $bestRaw = $raw;
                        }
                    }

                    if ($bestRaw !== null) {
                        $amount = $this->parseAmount(trim($bestRaw));
                        if ($amount != 0.0) {
                            $amounts[] = $amount;
                        }
                    }

                    continue;
                }

                // Optional fallback: keyword appears on this line, amount may be next line.
                if ($allowNextLineFallback && isset($lines[$i + 1])) {
                    preg_match_all($amountRegex, $lines[$i + 1], $nextMatches, PREG_OFFSET_CAPTURE);
                    if (! empty($nextMatches[0])) {
                        $raw = $nextMatches[0][0][0];
                        $amount = $this->parseAmount(trim($raw));
                        if ($amount != 0.0) {
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
            // For values like gross sales or taxes, PDFs sometimes show negatives;
            // take the maximum positive when present.
            $positive = array_filter($amounts, fn($a) => $a > 0);
            return ! empty($positive) ? max($positive) : abs(array_sum($amounts));
        }

        // For fee/expense rows, DoorDash typically uses negative numbers; return abs(total).
        return abs(array_sum($amounts));
    }

    /**
     * Parse Grubhub PDF statement using PDF parser
     */
    protected function parseGrubhubPDF($file)
    {
        $extracted = $this->extractPdfText($file);
        $text = $this->normalizeExtractedText($extracted['text'] ?? '');

        Log::info('Grubhub PDF text extracted', ['length' => strlen($text)]);

        if (trim($text) === '') {
            throw new \InvalidArgumentException('Failed to parse statement: PDF text extraction returned empty text.');
        }

        // Extract data using regex patterns
        $statementDate = $this->extractDateFromGrubhub($text);
        $grossSales = $this->extractAmountFromText($text, ['gross sales', 'total sales', 'restaurant sales']);
        $marketingFees = $this->extractAmountFromText($text, ['marketing fee', 'commission', 'platform fee']);
        $deliveryFees = $this->extractAmountFromText($text, ['delivery fee', 'delivery']);
        $processingFees = $this->extractAmountFromText($text, ['processing fee', 'payment processing']);
        $netDeposit = $this->extractAmountFromText($text, ['net deposit', 'total payment', 'payout']);
        $salesTax = $this->extractAmountFromText($text, ['sales tax', 'tax collected']);

        $data = [
            'statement_date' => $statementDate ?: now(),
            'statement_id' => $this->extractStatementIdFromGrubhub($text),
            'gross_sales' => $grossSales,
            'marketing_fees' => abs($marketingFees),
            'delivery_fees' => abs($deliveryFees),
            'processing_fees' => abs($processingFees),
            'net_deposit' => max(0, $netDeposit),
            'sales_tax_collected' => $salesTax,
        ];

        $this->assertStatementDataIsUsable($data, $text);
        return $data;
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
        $adjustmentsCoa = ChartOfAccount::where('account_name', 'Adjustments - Overrings/Returns')->first();

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

        // Create adjustments expense if exists (DoorDash amendments -> adjustments)
        if (! empty($data['adjustments']) && (float) $data['adjustments'] > 0 && $adjustmentsCoa) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $adjustmentsCoa->id,
                'amount' => (float) $data['adjustments'],
                'description' => "{$platformName} adjustments - {$statement->statement_date->format('M d, Y')}",
                'payment_method' => 'credit_card',
                'third_party_statement_id' => $statement->id,
                'created_by' => auth()->id(),
                'duplicate_check_hash' => md5($statement->id . 'adjustments'),
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
