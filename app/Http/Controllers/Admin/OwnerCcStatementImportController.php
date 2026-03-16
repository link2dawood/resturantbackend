<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\OwnerCcStatementRowsImport;
use App\Models\ChartOfAccount;
use App\Models\OwnerCcDescriptionMapping;
use App\Models\OwnerCcStatementImport;
use App\Models\OwnerCcStatementLine;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OwnerCcStatementImportController extends Controller
{
    /**
     * List owner CC statement imports (for owners / admin).
     */
    public function index(Request $request)
    {
        $query = OwnerCcStatementImport::with(['importer', 'store'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $imports = $query->paginate(20);
        $stores = Store::orderBy('store_info')->get();

        return view('admin.owner-cc-statements.index', [
            'imports' => $imports,
            'stores' => $stores,
        ]);
    }

    /**
     * Show upload form.
     */
    public function create()
    {
        $stores = Store::orderBy('store_info')->get();

        return view('admin.owner-cc-statements.create', [
            'stores' => $stores,
        ]);
    }

    /**
     * Import CSV or XLSX file and insert records.
     * Expected columns: Status, Date, Description, Debit, Credit, Member Name
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:20480',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileHash = md5_file($file->getRealPath());
        $storeId = $request->input('store_id');

        // Only block if the same file was already imported for this same store (allow same file for different stores)
        $existing = OwnerCcStatementImport::where('file_hash', $fileHash)
            ->where(function ($q) use ($storeId) {
                if ($storeId) {
                    $q->where('store_id', $storeId);
                } else {
                    $q->whereNull('store_id');
                }
            })
            ->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'This file has already been imported for this store.',
                    'import_id' => $existing->id,
                ], 409);
            }
            return redirect()
                ->route('admin.owner-cc-statements.index')
                ->with('error', 'This file has already been imported for this store.');
        }

        try {
            $rows = $this->readRowsFromFile($file, $extension);
            if (empty($rows)) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'No data rows found in file.'], 400);
                }
                return back()->with('error', 'No data rows found in file.')->withInput();
            }

            $header = array_shift($rows);
            $headerMap = $this->buildHeaderMap($header);
            if (empty($headerMap)) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'Could not find required columns. Expected: Status, Date, Description, Debit, Credit, Member Name',
                    ], 400);
                }
                return back()->with('error', 'Invalid file format. Expected columns: Status, Date, Description, Debit, Credit, Member Name')->withInput();
            }

            DB::beginTransaction();

            $import = OwnerCcStatementImport::create([
                'imported_by' => auth()->id(),
                'store_id' => $request->input('store_id'),
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'rows_imported' => 0,
                'rows_skipped' => 0,
            ]);

            // Store original file on disk and save path
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $storedPath = $file->storeAs(
                'owner_cc_statements',
                $import->id . '_' . $safeName,
                'local'
            );
            if ($storedPath) {
                $import->update(['file_path' => $storedPath]);
            }

            $inserted = 0;
            $exceptions = [];
            foreach ($rows as $index => $row) {
                $line = $this->mapRowToLine($row, $headerMap, $import->id);
                if ($line) {
                    $created = OwnerCcStatementLine::create($line);
                    $this->applyLearnedTransactionType($created);
                    $inserted++;
                } else {
                    $reason = $this->getSkipReason($row, $headerMap);
                    $exceptions[] = [
                        'row' => $index + 2, // 1-based + header row
                        'reason' => $reason,
                        'data' => array_slice($row, 0, 6), // first 6 columns for report
                    ];
                }
            }

            $import->update([
                'rows_imported' => $inserted,
                'rows_skipped' => count($exceptions),
                'import_exceptions' => $exceptions ?: null,
            ]);
            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Import successful.',
                    'import_id' => $import->id,
                    'rows_imported' => $inserted,
                    'rows_skipped' => count($exceptions),
                    'import_exceptions' => $exceptions,
                ], 201);
            }

            $message = "Imported {$inserted} transactions.";
            if (count($exceptions) > 0) {
                $message .= ' ' . count($exceptions) . ' row(s) skipped (see exception report).';
            }
            return redirect()
                ->route('admin.owner-cc-statements.show', $import)
                ->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Owner CC statement import failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Import failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Show a single import with its lines (separate page per statement, e.g. January CC statement).
     */
    public function show(OwnerCcStatementImport $ownerCcStatementImport)
    {
        $ownerCcStatementImport->load(['importer', 'store', 'lines.transactionType', 'lines.chartOfAccount']);
        // Expenses and COGS only: account codes 5001–5999 and 6001–6999 (exclude 5000 and 6000),
        // and exclude “total” rollup accounts that are sums of the detailed rows below.
        $chartOfAccounts = ChartOfAccount::active()
            ->whereIn('account_type', ['Expense', 'COGS'])
            ->where(function ($q) {
                $q->whereRaw('CAST(account_code AS UNSIGNED) BETWEEN 5001 AND 5999')
                    ->orWhereRaw('CAST(account_code AS UNSIGNED) BETWEEN 6001 AND 6999');
            })
            ->whereNotIn('account_code', ChartOfAccount::totalRollupAccountCodes())
            ->orderBy('account_code')
            ->get();

        return view('admin.owner-cc-statements.show', [
            'import' => $ownerCcStatementImport,
            'chartOfAccounts' => $chartOfAccounts,
        ]);
    }

    /**
     * Delete an import, its stored file, and all related lines.
     */
    public function destroy(OwnerCcStatementImport $ownerCcStatementImport): RedirectResponse
    {
        DB::transaction(function () use ($ownerCcStatementImport) {
            // Delete lines (also handled by FK cascade, but explicit for clarity)
            $ownerCcStatementImport->lines()->delete();

            // Delete stored file from disk if present
            if ($ownerCcStatementImport->file_path && Storage::disk('local')->exists($ownerCcStatementImport->file_path)) {
                Storage::disk('local')->delete($ownerCcStatementImport->file_path);
            }

            // Delete import record
            $ownerCcStatementImport->delete();
        });

        return redirect()
            ->route('admin.owner-cc-statements.index')
            ->with('success', 'Statement import, its file, and all related records have been deleted.');
    }

    /**
     * Assign Chart of Account to a single statement line (legacy per-row update).
     * Kept for compatibility but bulkUpdateLines is preferred by the UI.
     */
    public function updateLineTransactionType(Request $request, OwnerCcStatementLine $ownerCcStatementLine)
    {
        $request->validate([
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
        ]);

        $ownerCcStatementLine->update([
            'coa_id' => $request->input('coa_id') ?: null,
        ]);

        $this->saveLearnedMapping($ownerCcStatementLine, $request->input('coa_id'));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Chart of account saved. Future imports will use this for similar descriptions.',
                'line' => $ownerCcStatementLine->fresh('chartOfAccount'),
            ]);
        }

        return back()->with('success', 'Chart of account saved. Future similar transactions will be assigned automatically.');
    }

    /**
     * Bulk update Chart of Account for many lines in one request.
     */
    public function bulkUpdateLines(Request $request, OwnerCcStatementImport $ownerCcStatementImport): RedirectResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:owner_cc_statement_lines,id'],
            'lines.*.coa_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
        ]);

        $linesData = $data['lines'] ?? [];

        DB::transaction(function () use ($linesData, $ownerCcStatementImport) {
            foreach ($linesData as $lineData) {
                /** @var \App\Models\OwnerCcStatementLine|null $line */
                $line = $ownerCcStatementImport->lines()->whereKey($lineData['id'])->first();
                if (! $line) {
                    continue;
                }

                $coaId = $lineData['coa_id'] ?? null;
                $line->update(['coa_id' => $coaId ?: null]);
                $this->saveLearnedMapping($line, $coaId);
            }
        });

        return back()->with('success', 'All changes have been saved for this statement.');
    }

    /**
     * Save/update learned description → COA mapping for future imports.
     */
    protected function saveLearnedMapping(OwnerCcStatementLine $line, $coaId): void
    {
        $pattern = OwnerCcDescriptionMapping::normalizeDescription($line->description);
        if ($pattern === '') {
            return;
        }

        OwnerCcDescriptionMapping::updateOrCreate(
            ['description_pattern' => $pattern],
            [
                'coa_id' => $coaId ?: null,
                'transaction_type_id' => null,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * Download this statement's records as CSV.
     */
    public function download(OwnerCcStatementImport $ownerCcStatementImport): StreamedResponse
    {
        $import = $ownerCcStatementImport->load('lines');
        $filename = 'cc-statement-' . pathinfo($import->file_name, PATHINFO_FILENAME) . '-' . $import->created_at->format('Y-m-d') . '.csv';

        $import->load('lines.transactionType', 'lines.chartOfAccount');

        return response()->streamDownload(function () use ($import) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Status', 'Date', 'Description', 'Debit', 'Credit', 'Member Name', 'Chart of Account']);

            foreach ($import->lines as $line) {
                $coaLabel = $line->chartOfAccount
                    ? ($line->chartOfAccount->account_code . ' - ' . $line->chartOfAccount->account_name)
                    : '';
                fputcsv($handle, [
                    $line->status ?? '',
                    $line->transaction_date->format('m/d/Y'),
                    $line->description ?? '',
                    $line->debit > 0 ? (string) $line->debit : '',
                    $line->credit > 0 ? (string) $line->credit : '',
                    $line->member_name ?? '',
                    $coaLabel,
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Download exception report (skipped rows) for this import as CSV.
     */
    public function downloadExceptionReport(OwnerCcStatementImport $ownerCcStatementImport): StreamedResponse|RedirectResponse
    {
        $import = $ownerCcStatementImport;
        $exceptions = $import->import_exceptions ?? [];
        if (empty($exceptions)) {
            return redirect()
                ->route('admin.owner-cc-statements.show', $import)
                ->with('info', 'No exception report for this import (no rows were skipped).');
        }

        $filename = 'cc-statement-exceptions-' . pathinfo($import->file_name, PATHINFO_FILENAME) . '-' . $import->created_at->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($exceptions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Row', 'Reason', 'Status', 'Date', 'Description', 'Debit', 'Credit', 'Member Name']);
            foreach ($exceptions as $ex) {
                $data = $ex['data'] ?? [];
                fputcsv($handle, [
                    $ex['row'] ?? '',
                    $ex['reason'] ?? 'Unknown',
                    $data[0] ?? '',
                    $data[1] ?? '',
                    $data[2] ?? '',
                    $data[3] ?? '',
                    $data[4] ?? '',
                    $data[5] ?? '',
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Apply learned Chart of Account (and legacy transaction type) from past owner assignments (auto-learn).
     */
    protected function applyLearnedTransactionType(OwnerCcStatementLine $line): void
    {
        $pattern = OwnerCcDescriptionMapping::normalizeDescription($line->description);
        if ($pattern === '') {
            return;
        }

        $mapping = OwnerCcDescriptionMapping::where('description_pattern', $pattern)->first();
        if ($mapping) {
            $updates = [];
            if ($mapping->coa_id !== null) {
                $updates['coa_id'] = $mapping->coa_id;
            }
            if ($mapping->transaction_type_id !== null) {
                $updates['transaction_type_id'] = $mapping->transaction_type_id;
            }
            if ($updates !== []) {
                $line->update($updates);
                $mapping->increment('times_matched');
            }
        }
    }

    /**
     * Read all rows from file (CSV or XLSX).
     */
    protected function readRowsFromFile($file, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->readCsvRows($file);
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            if (! class_exists('Maatwebsite\Excel\Facades\Excel')) {
                throw new \RuntimeException('XLSX support requires maatwebsite/excel. Run: composer require maatwebsite/excel');
            }
            $data = \Maatwebsite\Excel\Facades\Excel::toArray(new OwnerCcStatementRowsImport(), $file);
            $sheet = $data[0] ?? [];

            return array_values($sheet);
        }

        return [];
    }

    protected function readCsvRows($file): array
    {
        $path = $file->getRealPath();
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            return [];
        }
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = str_getcsv($line);
        }
        return $rows;
    }

    /**
     * Remove UTF-8 BOM from string (common in Excel/CSV exports).
     */
    protected function stripBom(string $value): string
    {
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($value, $bom)) {
            return substr($value, strlen($bom));
        }
        return $value;
    }

    /**
     * Build map of column name (lowercase) => index.
     * Expects: Status, Date, Description, Debit, Credit, Member Name (with or without BOM).
     */
    protected function buildHeaderMap(array $header): array
    {
        $map = [];
        foreach ($header as $index => $col) {
            $raw = $this->stripBom(trim((string) $col));
            $normalized = strtolower($raw);
            if ($normalized === '') {
                continue;
            }
            // Exact matches
            if (in_array($normalized, ['status', 'date', 'description', 'debit', 'credit', 'member name'], true)) {
                $map[$normalized] = $index;
                continue;
            }
            // Debit: column name contains "debit" but not "credit" (avoid "credit" matching debit)
            if (str_contains($normalized, 'debit') && ! str_contains($normalized, 'credit')) {
                $map['debit'] = $index;
            }
            // Credit: column name contains "credit", or "deposit"/"payment" (bank wording for money in)
            if (str_contains($normalized, 'credit')) {
                $map['credit'] = $index;
            } elseif (! isset($map['credit']) && in_array($normalized, ['deposit', 'deposits', 'deposit amount', 'payment', 'payments', 'payment amount'], true)) {
                $map['credit'] = $index;
            }
            // Member: "member name", "member", "member name (optional)"
            if (str_contains($normalized, 'member')) {
                $map['member name'] = $index;
            }
            // Date: "date", "transaction date", "posting date"
            if (in_array($normalized, ['date', 'transaction date', 'posting date', 'trans date', 'statement date'], true)
                || (str_contains($normalized, 'date') && ! isset($map['date']))) {
                $map['date'] = $index;
            }
            // Description
            if (str_contains($normalized, 'description') || $normalized === 'desc') {
                $map['description'] = $index;
            }
            // Status
            if (str_contains($normalized, 'status')) {
                $map['status'] = $index;
            }
            // Single "Amount" column (signed: positive=debit, negative=credit)
            if (in_array($normalized, ['amount', 'transaction amount', 'amt', 'sum'], true)
                || (str_contains($normalized, 'amount') && ! str_contains($normalized, 'debit') && ! str_contains($normalized, 'credit'))) {
                $map['amount'] = $index;
            }
        }
        if (! isset($map['date']) || ! isset($map['description'])) {
            return [];
        }
        return $map;
    }

    /**
     * Reason a row was skipped (for exception report).
     */
    protected function getSkipReason(array $row, array $headerMap): string
    {
        $get = function (string $key) use ($row, $headerMap) {
            $idx = $headerMap[$key] ?? null;
            if ($idx === null) {
                return null;
            }
            $val = $row[$idx] ?? null;
            if ($val === null || $val === '') {
                return null;
            }
            if (is_numeric($val)) {
                return (string) $val;
            }
            return trim((string) $val);
        };
        $dateStr = $get('date');
        if (! $dateStr) {
            return 'Missing date';
        }
        if (! $this->parseDate($dateStr)) {
            return 'Invalid date format';
        }
        return 'Unknown';
    }

    /**
     * Map one data row to OwnerCcStatementLine attributes.
     */
    protected function mapRowToLine(array $row, array $headerMap, int $importId): ?array
    {
        $get = function (string $key) use ($row, $headerMap) {
            $idx = $headerMap[$key] ?? null;
            if ($idx === null) {
                return null;
            }
            $val = $row[$idx] ?? null;
            if ($val === null || $val === '') {
                return null;
            }
            // Excel may return numeric cells as int/float; ensure we pass a string to parseAmount/trim
            if (is_numeric($val)) {
                return (string) $val;
            }
            return trim((string) $val);
        };

        $dateStr = $get('date');
        if (! $dateStr) {
            return null;
        }
        $transactionDate = $this->parseDate($dateStr);
        if (! $transactionDate) {
            return null;
        }

        $debitRaw = $get('debit');
        $creditRaw = $get('credit');
        $amountRaw = $get('amount'); // single signed amount column (optional)

        $debit = 0.0;
        $credit = 0.0;

        if ($amountRaw !== null && $amountRaw !== '') {
            // Single "Amount" column: positive = debit, negative = credit (common bank export)
            $amount = $this->parseAmount($amountRaw);
            if ($amount >= 0) {
                $debit = $amount;
            } else {
                $credit = abs($amount);
            }
        } else {
            $debit = $this->parseAmount($debitRaw ?? '0');
            $credit = $this->parseAmount($creditRaw ?? '0');
            // If file has only one amount column (e.g. "Debit") and bank puts credits as negative: treat negative as credit
            if ($credit === 0.0 && $debit < 0) {
                $credit = abs($debit);
                $debit = 0.0;
            }
        }

        return [
            'owner_cc_statement_import_id' => $importId,
            'status' => $get('status'),
            'transaction_date' => $transactionDate,
            'description' => $get('description'),
            'debit' => $debit,
            'credit' => $credit,
            'member_name' => $get('member name'),
        ];
    }

    protected function parseDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $value = trim($value);
        $formats = ['m/d/Y', 'm-d-Y', 'Y-m-d', 'Y/m/d', 'd/m/Y', 'M d, Y'];
        foreach ($formats as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }

    protected function parseAmount(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }
        $value = str_replace(['$', ',', ' '], '', trim($value));
        if (preg_match('/\(([\d.]+)\)/', $value, $m)) {
            return (float) (-1 * $m[1]);
        }
        return (float) $value;
    }
}
