<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ImportUniqueFileName;
use App\Models\ThirdPartyStatement;
use App\Models\ExpenseTransaction;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

            $normalizedName = ImportUniqueFileName::normalize($file->getClientOriginalName());
            if (ImportUniqueFileName::thirdPartyStatementExists($normalizedName)) {
                return response()->json([
                    'message' => 'A file with this name has already been imported.',
                ], 409);
            }

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
     * DoorDash monthly statement PDF (text) extractor (e.g. monthly-statement-generator PDF).
     * Mapping (per merchant definitions for this layout):
     * - Commission (line item, not "Commission & fees" rollup) → delivery_fees
     * - Merchant fees → processing_fees
     * - Marketing fees → marketing_fees
     * - Tax (commission) → sales_tax_collected
     * - Subtotal + Tax (subtotal) → gross_sales (marketplace sales total)
     * - Amendments / error charges → adjustments as negative values (payout reductions)
     */
    protected function extractDoorDashMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
        $statementDate = $this->extractDateFromMonthlyStatement($text, $filename);

        // Restrict to page-1 consolidated summary (see marker notes in history).
        $summaryBlock = $this->extractTextBetweenMarkers($text, 'Sales (', 'Page 1 of');
        if (trim($summaryBlock) === '') {
            $summaryBlock = $text;
        }

        $subtotal = $this->extractAmountFromTextWithCents($summaryBlock, ['subtotal'], true, false);
        $taxSubtotal = $this->extractAmountFromTextWithCents($summaryBlock, ['tax (subtotal)'], true, false);

        // Standalone "Commission -$X" line only (exclude "Commission & fees" header line).
        $commissionLine = $this->extractDoorDashLineStartingWith($summaryBlock, 'Commission');
        $merchantFees = $this->extractDoorDashLineStartingWith($summaryBlock, 'Merchant fees');
        $taxOnCommission = $this->extractAmountFromTextWithCents($summaryBlock, ['tax (commission)'], false, false);

        $marketingFees = $this->extractAmountFromTextWithCents($summaryBlock, ['marketing fees'], false, false);

        // Amendments: error charges (and optional "Adjustments ($)" line) as magnitude; store signed negative.
        $errorCharges = $this->extractAmountFromTextWithCents($summaryBlock, ['error charges'], false, false);
        $lineAdjustments = $this->extractDoorDashAdjustmentsParentheticalLine($summaryBlock);
        $amendmentsMagnitude = $errorCharges + $lineAdjustments;
        if ($amendmentsMagnitude <= 0 && $this->extractAmountFromTextWithCents($summaryBlock, ['adjustments'], false, false) > 0) {
            $amendmentsMagnitude = $this->extractAmountFromTextWithCents($summaryBlock, ['adjustments'], false, false);
        }
        $adjustments = $amendmentsMagnitude > 0 ? -round($amendmentsMagnitude, 2) : 0.0;

        $netTotal = $this->extractAmountFromTextWithCents($summaryBlock, ['net total'], true, false);

        return [
            'statement_date' => $statementDate ?: now(),
            'statement_id' => $this->extractStatementIdFromText($text),
            'gross_sales' => round(max(0, $subtotal + $taxSubtotal), 2),
            'marketing_fees' => round(abs($marketingFees), 2),
            'delivery_fees' => round(abs($commissionLine), 2),
            'processing_fees' => round(abs($merchantFees), 2),
            'adjustments' => $adjustments,
            'net_deposit' => round(max(0, $netTotal), 2),
            'sales_tax_collected' => round(abs($taxOnCommission), 2),
        ];
    }

    /**
     * First line in $text that starts with $prefix (case-insensitive), excluding "Commission &".
     */
    protected function extractDoorDashLineStartingWith(string $text, string $prefix): float
    {
        $prefixLower = strtolower($prefix);
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $trim = trim($line);
            if ($trim === '') {
                continue;
            }
            if (stripos($trim, $prefixLower) !== 0) {
                continue;
            }
            if ($prefixLower === 'commission' && str_contains(strtolower($trim), 'commission &')) {
                continue;
            }
            if (preg_match('/(\(?-?\$?[\d,]+\.\d{2}\)?)/', $trim, $m)) {
                return abs((float) $this->parseAmount(trim($m[1])));
            }
        }

        return 0.0;
    }

    /**
     * "Adjustments (n) $X.XX" line under Amendments (positive = extra withholding → negative adjustment overall).
     */
    protected function extractDoorDashAdjustmentsParentheticalLine(string $text): float
    {
        foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
            $trim = trim($line);
            if (preg_match('/^Adjustments\s*\(\s*\d+\s*\)\s*(\(?-?\$?[\d,]+\.\d{2}\)?)/i', $trim, $m)) {
                return abs((float) $this->parseAmount(trim($m[1])));
            }
        }

        return 0.0;
    }

    /**
     * Grubhub statement PDF (text) extractor.
     * Uses "Total payments to you" as net deposit and "Restaurant sales" as gross.
     * Marketing (N), Deliveries by Grubhub (N), Order processing (N) are the fee breakdown;
     * "Grubhub order services" is the authoritative sum of those three (scaled if PDF lines disagree).
     * "Account adjustments" may be a payout credit (positive) or debit (negative, e.g. parentheses).
     * Stored signed on the statement; positive credits are not posted as expenses, negative debits are.
     */
    protected function extractGrubhubMonthlyStatementDataFromText(string $text, string $filename = ''): array
    {
        $statementDate = $this->extractDateFromMonthlyStatement($text, $filename) ?: $this->extractDateFromGrubhub($text);

        // Page 2 deposit grids repeat Marketing / Deliveries / Processing and can confuse whole-document regexes.
        // Narrative totals: prefer text between "Total payments" and "Distribution ID", but some PDF extractors
        // place "Distribution ID" before the fee lines → Marketing/Delivery/Processing must use the block from
        // "Grubhub order services" through "Account adjustments" (matches real statement layout).
        $summaryBlock = $this->extractTextBetweenMarkers($text, 'Total payments to you', 'Distribution ID');
        if (trim($summaryBlock) === '') {
            $summaryBlock = $this->extractTextBetweenMarkers($text, 'Restaurant sales for', 'Distribution ID');
        }
        if (trim($summaryBlock) === '') {
            $summaryBlock = $text;
        }

        $feeNarrativeBlock = $this->extractGrubhubOrderServicesDetailBlock($text);
        $feeContext = (trim($feeNarrativeBlock) !== '') ? $feeNarrativeBlock : $summaryBlock;

        $read = function (string $pattern, bool $abs = true) use ($summaryBlock) {
            if (! preg_match($pattern, $summaryBlock, $m)) {
                return 0.0;
            }
            $val = $this->parseAmount($m[1] ?? null);
            if ($abs) {
                $val = abs($val);
            }
            return (float) $val;
        };

        $readFees = function (string $pattern, bool $abs = true) use ($feeContext) {
            if (! preg_match($pattern, $feeContext, $m)) {
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
        // Grubhub order services $ (6.10)  ← authoritative total for Marketing + Deliveries + Processing
        // 1 Marketing (3.07)
        // (description may continue on following lines: "Services such as customer acquisition…")
        // 3 Deliveries by Grubhub (2.05)
        // 1 Order processing (0.98)
        // Account adjustments $ 21.67
        $gross = $read('/Restaurant\s+sales\s+for\s+\d+\s+(?:orders|order)\s*\$?\s*([0-9\.,]+)\b/i');
        $net = $read('/Total\s+payments\s+to\s+you\s*\$?\s*([0-9\.,]+)\b/i');

        // Fee lines: parse from feeContext (order-services narrative block), not Distribution ID–trimmed summary.
        $marketing = $this->extractGrubhubMarketingFeeFromSummary($feeContext);
        if ($marketing <= 0) {
            $marketing = $this->extractGrubhubMarketingFeeLoose($text);
        }
        $delivery = $readFees('/\bDeliveries\s+by\s+Grubhub\s*\(\s*([0-9\.,]+)\s*\)/iu');
        $processing = $readFees('/\bOrder\s+processing\s*\(\s*([0-9\.,]+)\s*\)/iu');

        $orderServicesTotal = $this->extractGrubhubOrderServicesTotal($feeContext);

        // Deposit table row lists exact fee splits; use when narrative failed entirely or doesn't sum to
        // "Grubhub order services" (e.g. only processing parsed, marketing/delivery stayed 0).
        $tableFees = $this->extractGrubhubFeesFromMarketplaceOrderTableRow($text);
        if ($tableFees !== null && $orderServicesTotal > 0.005) {
            $sumParsed = (float) $marketing + (float) $delivery + (float) $processing;
            if ($sumParsed < 0.005 || abs($sumParsed - $orderServicesTotal) > 0.05) {
                [$marketing, $delivery, $processing] = $tableFees;
            }
        }

        $tax = $read('/Includes\s*\$?\s*([0-9\.,]+)\s*in\s+taxes/i', true);

        $adjustments = 0.0;
        if (preg_match('/Account\s+adjustments\s*\$?\s*(\([^)]+\)|-?\$?[\d,]+\.\d{2})\b/i', $summaryBlock, $adjMatch)) {
            $adjustments = round((float) ($this->parseAmount(trim($adjMatch[1])) ?? 0), 2);
        } elseif (preg_match('/Account\s+adjustments\s*\$?\s*([0-9\.,]+)\b/i', $summaryBlock, $adjMatch)) {
            $adjustments = round((float) ($this->parseAmount($adjMatch[1] ?? null) ?? 0), 2);
        }

        // If "Restaurant sales" appears above "Total payments" in extracted text, recover gross from the sales block.
        if ($gross <= 0) {
            $altBlock = $this->extractTextBetweenMarkers($text, 'Restaurant sales for', 'Distribution ID');
            if (trim($altBlock) !== '' && preg_match('/Restaurant\s+sales\s+for\s+\d+\s+(?:orders|order)\s*\$?\s*([0-9\.,]+)\b/i', $altBlock, $gm)) {
                $gross = abs((float) $this->parseAmount($gm[1] ?? null));
            }
        }

        [$marketing, $delivery, $processing] = $this->normalizeGrubhubFeeLinesToOrderServicesTotal(
            $marketing,
            $delivery,
            $processing,
            $orderServicesTotal
        );

        return [
            'statement_date' => $statementDate ?: now(),
            'statement_id' => $this->extractGrubhubAccountStatementId($text) ?? $this->extractStatementIdFromText($text),
            'gross_sales' => round(max(0, $gross), 2),
            'marketing_fees' => round(max(0, $marketing), 2),
            'delivery_fees' => round(max(0, $delivery), 2),
            'processing_fees' => round(max(0, $processing), 2),
            'adjustments' => $adjustments,
            'net_deposit' => round(max(0, $net), 2),
            'sales_tax_collected' => round(max(0, $tax), 2),
        ];
    }

    /**
     * Narrative block from "Grubhub order services" through "Account adjustments".
     * Uses regex markers so PDF line breaks inside labels (e.g. "Grubhub order\nservices") still match;
     * literal mb_stripos fails on those exports and left marketing_fees at 0.
     */
    protected function extractGrubhubOrderServicesDetailBlock(string $text): string
    {
        $startRe = '/Grubhub\s+order\s+services/is';
        $ends = [
            '/Account\s+adjustments\b/is',
            '/Adjustments\s+to\s+your\s+account\b/is',
        ];

        if (! preg_match($startRe, $text, $sm, PREG_OFFSET_CAPTURE)) {
            return '';
        }

        $blockStart = $sm[0][1];
        $afterStartLabel = $blockStart + strlen($sm[0][0]);
        $tail = substr($text, $afterStartLabel);

        foreach ($ends as $endRe) {
            if (preg_match($endRe, $tail, $em, PREG_OFFSET_CAPTURE)) {
                $blockEndExclusive = $afterStartLabel + $em[0][1];

                return substr($text, $blockStart, $blockEndExclusive - $blockStart);
            }
        }

        return substr($text, $blockStart);
    }

    /**
     * Grubhub marketing fee on the numbered narrative line ("1 Marketing …").
     * Leading count avoids matching the "Marketing services" rates section.
     * Tolerates PDF line breaks between the count, "Marketing", and "(3.07)".
     */
    protected function extractGrubhubMarketingFeeFromSummary(string $block): float
    {
        $patternAmountIndex = [
            ['/^\s*\d+\s+Marketing(?:\s+Services\b[\s\S]*?)?\s*\(\s*([0-9\.,]+)\s*\)/imu', 1],
            ['/(\d+)\s+Marketing(?:\s+Services\b[\s\S]*?)?\s*\(\s*([0-9\.,]+)\s*\)/isu', 2],
            ['/(\d+)[\s\x{00A0}]*[\r\n]+[\s\x{00A0}]*Marketing(?:\s+Services\b[\s\S]*?)?\s*\(\s*([0-9\.,]+)\s*\)/iu', 2],
            ['/(\d+)[\s\x{00A0}]+Marketing[\s\x{00A0}]*[\r\n]+[\s\x{00A0}]*Services\b[\s\S]*?\(\s*([0-9\.,]+)\s*\)/iu', 2],
        ];

        foreach ($patternAmountIndex as [$pattern, $idx]) {
            if (preg_match($pattern, $block, $m)) {
                return abs((float) $this->parseAmount($m[$idx] ?? null));
            }
        }

        return 0.0;
    }

    /**
     * Last-resort: find "1 Marketing (amount)" in a window after flexible "Grubhub order services".
     */
    protected function extractGrubhubMarketingFeeLoose(string $text): float
    {
        if (! preg_match(
            '/Grubhub\s+order\s+services[\s\S]{0,3000}?(?<![0-9])\d+\s+Marketing(?:\s+Services\b[\s\S]*?)?\s*\(\s*([0-9\.,]+)\s*\)/isu',
            $text,
            $m
        )) {
            return 0.0;
        }

        return abs((float) $this->parseAmount($m[1] ?? null));
    }

    /**
     * Deposit grid row: "... $22.18 ($3.07) ($2.05) $0.00 ($0.98)" → marketing, delivery, processing.
     * Grubhub PDFs use dollar inside parens: ($3.07) not (3.07). Smalot may split the row across lines,
     * so we scan a byte slice after "Marketplace order N", not a single \n-terminated line.
     */
    protected function extractGrubhubFeesFromMarketplaceOrderTableRow(string $text): ?array
    {
        if (! preg_match('/Marketplace\s+order\s+\d+/iu', $text, $mm, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $start = $mm[0][1];
        $len = strlen($text);
        $chunk = substr($text, $start, min(1500, max(0, $len - $start)));
        if ($chunk === '') {
            return null;
        }

        // Optional $ inside each parenthesis; trailing: (mkt) (deliv) $withheld (processing)
        $feeTail = '/\(\s*\$?\s*([0-9\.,]+)\s*\)\s+\(\s*\$?\s*([0-9\.,]+)\s*\)\s+\$[0-9\.,]+\s+\(\s*\$?\s*([0-9\.,]+)\s*\)/u';
        if (! preg_match($feeTail, $chunk, $m)) {
            return null;
        }

        return [
            round(abs((float) $this->parseAmount($m[1] ?? null)), 2),
            round(abs((float) $this->parseAmount($m[2] ?? null)), 2),
            round(abs((float) $this->parseAmount($m[3] ?? null)), 2),
        ];
    }

    /**
     * Grubhub account reference e.g. "(#7595744)" in the statement header block.
     */
    protected function extractGrubhubAccountStatementId(string $text): ?string
    {
        if (preg_match('/\(#\s*([0-9]{6,})\s*\)/', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * "Grubhub order services" line total (e.g. "$ (6.10)" or "$ (6.10)" after label).
     */
    protected function extractGrubhubOrderServicesTotal(string $summaryBlock): float
    {
        // Allow line breaks between the label and "$ (6.10)" (common in Smalot output).
        if (preg_match('/Grubhub\s+order\s+services[\s\r\n]*\$?\s*(\([^)]+\))/is', $summaryBlock, $m)) {
            return abs((float) $this->parseAmount(trim($m[1])));
        }
        if (preg_match('/Grubhub\s+order\s+services[\s\r\n]*\$?\s*(-?\$?[\d,]+\.\d{2})/is', $summaryBlock, $m)) {
            return abs((float) $this->parseAmount(trim($m[1])));
        }

        return 0.0;
    }

    /**
     * Scale Marketing / Deliveries / Processing so they sum to "Grubhub order services" when that total is present.
     */
    protected function normalizeGrubhubFeeLinesToOrderServicesTotal(
        float $marketing,
        float $delivery,
        float $processing,
        float $orderServicesTotal
    ): array {
        if ($orderServicesTotal <= 0) {
            return [$marketing, $delivery, $processing];
        }

        $sum = $marketing + $delivery + $processing;
        if ($sum <= 0) {
            return [0.0, 0.0, $orderServicesTotal];
        }

        if (abs($sum - $orderServicesTotal) < 0.02) {
            return [$marketing, $delivery, $processing];
        }

        $scale = $orderServicesTotal / $sum;
        $m = round($marketing * $scale, 2);
        $d = round($delivery * $scale, 2);
        $p = round($orderServicesTotal - $m - $d, 2);

        return [$m, $d, max(0, $p)];
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

        // Use the stricter cents-based extractor here so a zero-value line like
        // "Total Amendments $0.00" cannot drift down and capture the later
        // "Net Total $61.23*" amount from the PDF text.
        $gross = $this->extractAmountFromTextWithCents($summaryBlock, ['total earnings'], true);
        $net = $this->extractAmountFromTextWithCents($summaryBlock, ['net total'], true);

        $processing = $this->extractAmountFromTextWithCents($summaryBlock, ['total uber fees'], false);
        $marketing = $this->extractAmountFromTextWithCents($summaryBlock, ['total marketing spends'], false);
        $adjustments = $this->extractAmountFromTextWithCents($summaryBlock, ['total amendments'], false);

        $taxSales = $this->extractAmountFromTextWithCents($summaryBlock, ['tax on sales'], true);
        $taxContainer = $this->extractAmountFromTextWithCents($summaryBlock, ['tax on container fees'], true);
        $taxOtherEarnings = $this->extractAmountFromTextWithCents($summaryBlock, ['tax on other earnings'], true);
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

        // Grubhub monthly PDFs: "For 1/2/2026 to 1/23/2026" (uses "to", not a hyphen range)
        if (preg_match('/\bFor\s+(\d{1,2}\/\d{1,2}\/\d{2,4})\s+to\s+(\d{1,2}\/\d{1,2}\/\d{2,4})\b/i', $combined, $m)) {
            $end = $this->parseDate($m[2]);
            if ($end) {
                return Carbon::parse($end)->startOfMonth()->format('Y-m-d');
            }
        }

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
        return ChartOfAccount::thirdPartyPlatformExpenseAccount($platform);
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
                $normalized = $this->normalizeCsvHeader($column);
                $headerMap[$normalized] = $index;
            }

            $dateColumn = $this->findCsvHeaderIndex($headerMap, [
                'date',
                'transaction date',
                'statement date',
                'payout date',
            ]);

            $grossColumn = $this->findCsvHeaderIndex($headerMap, [
                'gross sales',
                'subtotal',
                'total sales',
                'total earnings',
                'order subtotal',
                'sales',
            ]);

            $marketingColumn = $this->findCsvHeaderIndex($headerMap, [
                'marketing fees',
                'marketing fee',
                'total marketing spends',
                'marketing spend',
                'promotion fees',
                'promotions',
                'promo spend',
                'ads',
            ]);

            $deliveryColumn = $this->findCsvHeaderIndex($headerMap, [
                'delivery fees',
                'delivery fee',
                'delivery',
                'delivery charges',
                'courier payment',
                'courier payments',
            ]);

            $processingColumn = $this->findCsvHeaderIndex($headerMap, [
                'processing fees',
                'processing fee',
                'payment processing',
                'service fee',
                'service fees',
                'total uber fees',
                'uber fees',
                'commission',
            ]);

            $netDepositColumn = $this->findCsvHeaderIndex($headerMap, [
                'net deposit',
                'payout',
                'net payment',
                'net payout',
                'net total',
                'deposit',
                'total payout',
            ]);
            
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
                if ($dateColumn !== null && isset($row[$dateColumn])) {
                    $date = $this->parseDate($row[$dateColumn]);
                }
                
                if (!$firstDate) $firstDate = $date;
                $lastDate = $date ?: $lastDate;
                
                // Parse amounts based on column headers
                if ($grossColumn !== null) {
                    $grossSales += $this->parseAmount($row[$grossColumn] ?? '0');
                }
                
                if ($marketingColumn !== null) {
                    $marketingFees += $this->parseAmount($row[$marketingColumn] ?? '0');
                }
                
                if ($deliveryColumn !== null) {
                    $deliveryFees += $this->parseAmount($row[$deliveryColumn] ?? '0');
                }
                
                if ($processingColumn !== null) {
                    $processingFees += $this->parseAmount($row[$processingColumn] ?? '0');
                }
                
                if ($netDepositColumn !== null) {
                    $netDeposit += $this->parseAmount($row[$netDepositColumn] ?? '0');
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
                $normalized = $this->normalizeCsvHeader($column);
                $headerMap[$normalized] = $index;
            }

            $dateColumn = $this->findCsvHeaderIndex($headerMap, [
                'date',
                'transaction date',
                'statement date',
                'payout date',
            ]);

            $grossColumn = $this->findCsvHeaderIndex($headerMap, [
                'gross sales',
                'subtotal',
                'total sales',
                'sales',
            ]);

            $marketingColumn = $this->findCsvHeaderIndex($headerMap, [
                'marketing fees',
                'marketing fee',
                'platform fee',
            ]);

            $deliveryColumn = $this->findCsvHeaderIndex($headerMap, [
                'delivery fees',
                'delivery fee',
                'delivery',
            ]);

            $processingColumn = $this->findCsvHeaderIndex($headerMap, [
                'processing fees',
                'processing fee',
                'payment processing',
                'service fee',
                'service fees',
            ]);

            $netDepositColumn = $this->findCsvHeaderIndex($headerMap, [
                'net deposit',
                'payout',
                'net payment',
                'net payout',
                'net total',
            ]);
            
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
                if ($dateColumn !== null && isset($row[$dateColumn])) {
                    $date = $this->parseDate($row[$dateColumn]);
                }
                
                if (!$firstDate) $firstDate = $date;
                $lastDate = $date ?: $lastDate;
                
                // Parse amounts based on column headers
                if ($grossColumn !== null) {
                    $grossSales += $this->parseAmount($row[$grossColumn] ?? '0');
                }
                
                if ($marketingColumn !== null) {
                    $marketingFees += $this->parseAmount($row[$marketingColumn] ?? '0');
                }
                
                if ($deliveryColumn !== null) {
                    $deliveryFees += $this->parseAmount($row[$deliveryColumn] ?? '0');
                }
                
                if ($processingColumn !== null) {
                    $processingFees += $this->parseAmount($row[$processingColumn] ?? '0');
                }
                
                if ($netDepositColumn !== null) {
                    $netDeposit += $this->parseAmount($row[$netDepositColumn] ?? '0');
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
        $platformExpenseCoa = ChartOfAccount::thirdPartyPlatformExpenseAccount($statement->platform);
        
        // Get or create vendor for platform
        $vendor = Vendor::where('vendor_name', $platformName)->first();
        if (!$vendor) {
            $vendor = Vendor::create([
                'vendor_name' => $platformName,
                'vendor_identifier' => $platformName,
                'vendor_type' => 'Services',
                'default_coa_id' => $platformExpenseCoa?->id,
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);
        } elseif ($platformExpenseCoa && $vendor->default_coa_id !== $platformExpenseCoa->id) {
            $vendor->update([
                'default_coa_id' => $platformExpenseCoa->id,
            ]);
        }

        // Third-party platform costs should post to the platform-specific expense COA
        // rather than the generic merchant processing account.
        $marketingCoa = $platformExpenseCoa;
        $deliveryCoa = $platformExpenseCoa;
        $processingCoa = $platformExpenseCoa;
        $adjustmentsCoa = $platformExpenseCoa;

        // Create marketing fee expense if exists
        if ($data['marketing_fees'] > 0 && $marketingCoa) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $marketingCoa->id,
                'amount' => $data['marketing_fees'],
                'description' => "{$platformName} marketing fee - {$statement->statement_date->format(config('dates.display'))}",
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
                'description' => "{$platformName} delivery fee - {$statement->statement_date->format(config('dates.display'))}",
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
                'description' => "{$platformName} processing fee - {$statement->statement_date->format(config('dates.display'))}",
                'payment_method' => 'credit_card',
                'third_party_statement_id' => $statement->id,
                'created_by' => auth()->id(),
                'duplicate_check_hash' => md5($statement->id . 'processing'),
            ]);
        }

        // Adjustments: DoorDash may store negative amendments (post expense as abs).
        // Grubhub: post expense only for negative adjustments (payout debits); skip positive credits.
        // Other platforms: post when adjustments > 0 (fee-style).
        $adj = (float) ($data['adjustments'] ?? 0);
        $postAdjustmentExpense = false;
        if ($adjustmentsCoa && abs($adj) >= 0.005) {
            if ($statement->platform === 'doordash') {
                $postAdjustmentExpense = true;
            } elseif ($statement->platform === 'grubhub') {
                $postAdjustmentExpense = $adj < 0;
            } else {
                $postAdjustmentExpense = $adj > 0;
            }
        }

        if ($postAdjustmentExpense) {
            ExpenseTransaction::create([
                'transaction_type' => 'credit_card',
                'transaction_date' => $statement->statement_date,
                'store_id' => $statement->store_id,
                'vendor_id' => $vendor->id,
                'coa_id' => $adjustmentsCoa->id,
                'amount' => abs($adj), // statement may store DoorDash amendments as negative; book positive expense
                'description' => "{$platformName} amendments/adjustments - {$statement->statement_date->format(config('dates.display'))}",
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
                'description' => "Expected {$statement->platform} deposit for {$statement->statement_date->format(config('dates.display'))}",
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

    protected function normalizeCsvHeader(?string $header): string
    {
        $header = strtolower(trim((string) $header));
        $header = preg_replace('/[\s_\-\/]+/', ' ', $header);
        $header = preg_replace('/[^a-z0-9 ]+/', '', $header);

        return trim($header);
    }

    protected function findCsvHeaderIndex(array $headerMap, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $normalizedAlias = $this->normalizeCsvHeader($alias);
            if (array_key_exists($normalizedAlias, $headerMap)) {
                return $headerMap[$normalizedAlias];
            }
        }

        return null;
    }
}
