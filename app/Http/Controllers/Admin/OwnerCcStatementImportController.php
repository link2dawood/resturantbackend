<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\OwnerCcStatementRowsImport;
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
        $ownerCcStatementImport->load(['importer', 'store', 'lines.transactionType']);
        $transactionTypes = \App\Models\TransactionType::orderBy('name')->get();

        return view('admin.owner-cc-statements.show', [
            'import' => $ownerCcStatementImport,
            'transactionTypes' => $transactionTypes,
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
     * Assign transaction type to a statement line and save as learned mapping for future imports.
     */
    public function updateLineTransactionType(Request $request, OwnerCcStatementLine $ownerCcStatementLine)
    {
        $request->validate([
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
        ]);

        $ownerCcStatementLine->update([
            'transaction_type_id' => $request->input('transaction_type_id') ?: null,
        ]);

        $typeId = $request->input('transaction_type_id');
        if ($typeId) {
            $pattern = OwnerCcDescriptionMapping::normalizeDescription($ownerCcStatementLine->description);
            if ($pattern !== '') {
                OwnerCcDescriptionMapping::updateOrCreate(
                    ['description_pattern' => $pattern],
                    [
                        'transaction_type_id' => $typeId,
                        'created_by' => auth()->id(),
                    ]
                );
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaction type saved. Future imports will use this for similar descriptions.',
                'line' => $ownerCcStatementLine->fresh('transactionType'),
            ]);
        }

        return back()->with('success', 'Transaction type saved. Future similar transactions will be assigned automatically.');
    }

    /**
     * Download this statement's records as CSV.
     */
    public function download(OwnerCcStatementImport $ownerCcStatementImport): StreamedResponse
    {
        $import = $ownerCcStatementImport->load('lines');
        $filename = 'cc-statement-' . pathinfo($import->file_name, PATHINFO_FILENAME) . '-' . $import->created_at->format('Y-m-d') . '.csv';

        $import->load('lines.transactionType');

        return response()->streamDownload(function () use ($import) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Status', 'Date', 'Description', 'Debit', 'Credit', 'Member Name', 'Transaction Type']);

            foreach ($import->lines as $line) {
                fputcsv($handle, [
                    $line->status ?? '',
                    $line->transaction_date->format('m/d/Y'),
                    $line->description ?? '',
                    $line->debit > 0 ? (string) $line->debit : '',
                    $line->credit > 0 ? (string) $line->credit : '',
                    $line->member_name ?? '',
                    $line->transactionType?->name ?? '',
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
     * Apply learned transaction type from past owner assignments (auto-learn).
     */
    protected function applyLearnedTransactionType(OwnerCcStatementLine $line): void
    {
        $pattern = OwnerCcDescriptionMapping::normalizeDescription($line->description);
        if ($pattern === '') {
            return;
        }

        $mapping = OwnerCcDescriptionMapping::where('description_pattern', $pattern)->first();
        if ($mapping) {
            $line->update(['transaction_type_id' => $mapping->transaction_type_id]);
            $mapping->increment('times_matched');
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
     * Build map of column name (lowercase) => index.
     */
    protected function buildHeaderMap(array $header): array
    {
        $expected = ['status', 'date', 'description', 'debit', 'credit', 'member name'];
        $map = [];
        foreach ($header as $index => $col) {
            $normalized = strtolower(trim((string) $col));
            if (in_array($normalized, $expected, true)) {
                $map[$normalized] = $index;
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
            return $val === null || $val === '' ? null : trim((string) $val);
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
            return $val === null || $val === '' ? null : trim((string) $val);
        };

        $dateStr = $get('date');
        if (! $dateStr) {
            return null;
        }
        $transactionDate = $this->parseDate($dateStr);
        if (! $transactionDate) {
            return null;
        }

        $debit = $this->parseAmount($get('debit') ?? '0');
        $credit = $this->parseAmount($get('credit') ?? '0');

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
