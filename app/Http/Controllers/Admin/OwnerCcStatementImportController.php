<?php

namespace App\Http\Controllers\Admin;

use App\Constants\OwnerCcStatementCardPlatform;
use App\Support\ImportUniqueFileName;
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
use Illuminate\Validation\Rule;
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
            'cardPlatforms' => OwnerCcStatementCardPlatform::LABELS,
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
            'card_platform' => ['required', 'string', Rule::in(OwnerCcStatementCardPlatform::values())],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $fileHash = md5_file($file->getRealPath());

        $normalizedName = ImportUniqueFileName::normalize($file->getClientOriginalName());
        if (ImportUniqueFileName::ownerCcStatementImportExists($normalizedName)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'A file with this name has already been imported.',
                ], 409);
            }

            return redirect()
                ->route('admin.owner-cc-statements.index')
                ->with('error', 'A file with this name has already been imported.');
        }

        try {
            $rows = $this->readRowsFromFile($file, $extension);
            if (empty($rows)) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'No data rows found in file.'], 400);
                }
                return back()->with('error', 'No data rows found in file.')->withInput();
            }

            $cardPlatform = (string) $request->input('card_platform');

            $header = array_shift($rows);
            $headerMap = $this->buildHeaderMap($header, $cardPlatform);
            if (empty($headerMap)) {
                $expected = OwnerCcStatementCardPlatform::expectedColumnsDescription($cardPlatform);
                if ($request->wantsJson()) {
                    return response()->json([
                        'error' => 'Could not find required columns for this card issuer. Expected: ' . $expected,
                    ], 400);
                }

                return back()->with('error', 'Invalid file format for the selected issuer. Expected columns: ' . $expected)->withInput();
            }

            DB::beginTransaction();

            $import = OwnerCcStatementImport::create([
                'imported_by' => auth()->id(),
                'store_id' => $request->input('store_id'),
                'card_platform' => $request->input('card_platform'),
                'file_name' => $file->getClientOriginalName(),
                'file_hash' => $fileHash,
                'rows_imported' => 0,
                'rows_skipped' => 0,
            ]);

            // Store original file on disk (tenant-scoped path) and save path
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
            $storedPath = \App\Support\TenantStorage::storeUpload(
                $file,
                'owner_cc_statements',
                $import->store_id,
                $import->id . '_' . $safeName,
                'local'
            );
            if ($storedPath) {
                $import->update(['file_path' => $storedPath]);
            }

            $inserted = 0;
            $exceptions = [];
            foreach ($rows as $index => $row) {
                $line = $this->mapRowToLine($row, $headerMap, $import->id, $cardPlatform);
                if ($line) {
                    $created = OwnerCcStatementLine::create($line);
                    $this->applyLearnedTransactionType($created);
                    $inserted++;
                } else {
                    $reason = $this->getSkipReason($row, $headerMap, $cardPlatform);
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
                    'card_platform' => $import->card_platform,
                    'card_platform_label' => $import->cardPlatformLabel(),
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
        $ownerCcStatementImport->load(['importer', 'store', 'lines.transactionType', 'lines.chartOfAccount', 'lines.store']);
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

        $user = auth()->user();
        $stores = Store::whereIn('id', $user->getAccessibleStoreIds())->orderBy('store_info')->get();

        return view('admin.owner-cc-statements.show', [
            'import' => $ownerCcStatementImport,
            'chartOfAccounts' => $chartOfAccounts,
            'stores' => $stores,
        ]);
    }

    /**
     * Set card last 4 for the statement; apply to import and all lines that don't have it.
     */
    public function updateCardLast4(Request $request, OwnerCcStatementImport $ownerCcStatementImport): RedirectResponse
    {
        $request->validate([
            'card_last4' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
        ]);

        $last4 = $request->input('card_last4');

        DB::transaction(function () use ($ownerCcStatementImport, $last4) {
            $ownerCcStatementImport->update(['card_last4' => $last4]);
            $ownerCcStatementImport->lines()->whereNull('card_last4')->update(['card_last4' => $last4]);
        });

        return back()->with('success', 'Card last 4 set to ' . $last4 . ' for this statement and all transactions.');
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
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:owner_cc_statement_lines,id'],
            'lines.*.coa_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
            'lines.*.store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ]);

        $linesData = $data['lines'] ?? [];

        DB::transaction(function () use ($linesData, $ownerCcStatementImport, $accessibleStoreIds) {
            foreach ($linesData as $lineData) {
                /** @var \App\Models\OwnerCcStatementLine|null $line */
                $line = $ownerCcStatementImport->lines()->whereKey($lineData['id'])->first();
                if (! $line) {
                    continue;
                }

                $coaId = $lineData['coa_id'] ?? null;
                $storeId = isset($lineData['store_id']) && in_array((int) $lineData['store_id'], $accessibleStoreIds, true)
                    ? (int) $lineData['store_id']
                    : null;

                $line->update([
                    'coa_id' => $coaId ?: null,
                    'store_id' => $storeId,
                ]);
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
    public function download(Request $request, OwnerCcStatementImport $ownerCcStatementImport): StreamedResponse
    {
        $import = $ownerCcStatementImport->load('lines');
        $filename = 'cc-statement-' . pathinfo($import->file_name, PATHINFO_FILENAME) . '-' . $import->created_at->format('Y-m-d') . '.csv';

        $import->load('lines.transactionType', 'lines.chartOfAccount', 'lines.store');
        $user = auth()->user();
        $accessibleStoreIds = $user->getAccessibleStoreIds();

        $lineSelections = collect($request->input('lines', []))
            ->filter(fn ($line) => is_array($line) && ! empty($line['id']))
            ->mapWithKeys(function (array $line) use ($accessibleStoreIds) {
                $requestedStoreId = isset($line['store_id']) && in_array((int) $line['store_id'], $accessibleStoreIds, true)
                    ? (int) $line['store_id']
                    : null;

                $requestedCoaId = ! empty($line['coa_id']) ? (int) $line['coa_id'] : null;

                return [
                    (int) $line['id'] => [
                        'store_id' => $requestedStoreId,
                        'coa_id' => $requestedCoaId,
                    ],
                ];
            });

        $selectedStoreIds = $lineSelections->pluck('store_id')->filter()->unique()->values();
        $selectedCoaIds = $lineSelections->pluck('coa_id')->filter()->unique()->values();

        $selectedStores = $selectedStoreIds->isNotEmpty()
            ? Store::whereIn('id', $selectedStoreIds)->get()->keyBy('id')
            : collect();

        $selectedCoas = $selectedCoaIds->isNotEmpty()
            ? ChartOfAccount::whereIn('id', $selectedCoaIds)->get()->keyBy('id')
            : collect();

        return response()->streamDownload(function () use ($import, $lineSelections, $selectedStores, $selectedCoas) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Last 4 CC', 'Date', 'Description', 'Debit', 'Credit', 'Member Name', 'Store', 'Chart of Account']);

            foreach ($import->lines as $line) {
                $selectedLine = $lineSelections->get((int) $line->id, []);
                $selectedStore = ! empty($selectedLine['store_id']) ? $selectedStores->get((int) $selectedLine['store_id']) : null;
                $selectedCoa = ! empty($selectedLine['coa_id']) ? $selectedCoas->get((int) $selectedLine['coa_id']) : null;

                $last4 = $line->card_last4 ?? $import->card_last4 ?? '';
                $effectiveStore = $selectedStore ?? $line->store ?? $import->store;
                $effectiveCoa = $selectedCoa ?? $line->chartOfAccount;

                $storeLabel = $effectiveStore ? $effectiveStore->store_info : '';
                $coaLabel = $effectiveCoa
                    ? ($effectiveCoa->account_code . ' - ' . $effectiveCoa->account_name)
                    : '';

                fputcsv($handle, [
                    $last4,
                    $line->transaction_date->format(config('dates.display')),
                    $line->description ?? '',
                    $line->debit > 0 ? (string) $line->debit : '',
                    $line->credit > 0 ? (string) $line->credit : '',
                    $line->member_name ?? '',
                    $storeLabel,
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
     * @return \Closure(string): ?string
     */
    protected function headerCellGetter(array $row, array $headerMap): \Closure
    {
        return function (string $key) use ($row, $headerMap) {
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
    }

    protected function buildHeaderMap(array $header, string $cardPlatform): array
    {
        return match ($cardPlatform) {
            OwnerCcStatementCardPlatform::CHASE_BANK => $this->buildHeaderMapChase($header),
            OwnerCcStatementCardPlatform::AMERICAN_EXPRESS => $this->buildHeaderMapAmex($header),
            default => $this->buildHeaderMapCityBank($header),
        };
    }

    /**
     * City Bank: Status, Date, Description, Debit, Credit, Member Name.
     */
    protected function buildHeaderMapCityBank(array $header): array
    {
        $map = [];
        foreach ($header as $index => $col) {
            $raw = $this->stripBom(trim((string) $col));
            $normalized = strtolower($raw);
            if ($normalized === '') {
                continue;
            }
            if (in_array($normalized, ['status', 'date', 'description', 'debit', 'credit', 'member name'], true)) {
                $map[$normalized] = $index;
                continue;
            }
            if (str_contains($normalized, 'debit') && ! str_contains($normalized, 'credit')) {
                $map['debit'] = $index;
            }
            if (str_contains($normalized, 'credit')) {
                $map['credit'] = $index;
            } elseif (! isset($map['credit']) && in_array($normalized, ['deposit', 'deposits', 'deposit amount', 'payment', 'payments', 'payment amount'], true)) {
                $map['credit'] = $index;
            }
            if (str_contains($normalized, 'member')) {
                $map['member name'] = $index;
            }
            if (in_array($normalized, ['date', 'transaction date', 'posting date', 'trans date', 'statement date'], true)
                || (str_contains($normalized, 'date') && ! isset($map['date']))) {
                $map['date'] = $index;
            }
            if (str_contains($normalized, 'description') || $normalized === 'desc') {
                $map['description'] = $index;
            }
            if (str_contains($normalized, 'status')) {
                $map['status'] = $index;
            }
            if (in_array($normalized, ['card last4', 'card last 4', 'last 4', 'last4', 'last four'], true)
                || (str_contains($normalized, 'last') && str_contains($normalized, '4'))) {
                $map['card last4'] = $index;
            }
            if (in_array($normalized, ['amount', 'transaction amount', 'amt', 'sum'], true)
                || (str_contains($normalized, 'amount') && ! str_contains($normalized, 'debit') && ! str_contains($normalized, 'credit'))) {
                $map['amount'] = $index;
            }
        }
        if (! isset($map['date']) || ! isset($map['description'])) {
            return [];
        }
        if (! isset($map['debit']) && ! isset($map['credit']) && ! isset($map['amount'])) {
            return [];
        }

        return $map;
    }

    /**
     * Chase: Card, Transaction Date, Post Date, Description, Category, Type, Amount, Memo.
     */
    protected function buildHeaderMapChase(array $header): array
    {
        $map = [];
        foreach ($header as $index => $col) {
            $raw = $this->stripBom(trim((string) $col));
            $n = strtolower($raw);
            if ($n === '') {
                continue;
            }

            if ($n === 'card' || str_starts_with($n, 'card ') || str_contains($n, 'card number') || $n === 'card #') {
                if (! isset($map['card'])) {
                    $map['card'] = $index;
                }
                continue;
            }
            if ($n === 'transaction date' || $n === 'trans date' || $n === 'trans. date') {
                $map['date'] = $index;
                continue;
            }
            if ($n === 'post date' || $n === 'postdate' || $n === 'posting date') {
                $map['post_date'] = $index;
                continue;
            }
            if ($n === 'description' || $n === 'desc') {
                $map['description'] = $index;
                continue;
            }
            if ($n === 'category') {
                $map['category'] = $index;
                continue;
            }
            if ($n === 'type' || $n === 'transaction type' || $n === 'trans type') {
                $map['chase_type'] = $index;
                continue;
            }
            if ($n === 'memo' || $n === 'memos' || $n === 'notes') {
                $map['memo'] = $index;
                continue;
            }
            if ($n === 'amount' || (str_contains($n, 'amount') && ! str_contains($n, 'debit') && ! str_contains($n, 'credit'))) {
                if (! isset($map['amount'])) {
                    $map['amount'] = $index;
                }
            }
        }

        if (! isset($map['date']) && isset($map['post_date'])) {
            $map['date'] = $map['post_date'];
        }

        if (! isset($map['date']) || ! isset($map['description']) || ! isset($map['amount'])) {
            return [];
        }

        return $map;
    }

    /**
     * American Express: Date, Receipt, Description, Amount (signed).
     */
    protected function buildHeaderMapAmex(array $header): array
    {
        $map = [];
        foreach ($header as $index => $col) {
            $raw = $this->stripBom(trim((string) $col));
            $n = strtolower($raw);
            if ($n === '') {
                continue;
            }

            if ($n === 'date' || $n === 'transaction date' || $n === 'trans date') {
                $map['date'] = $index;
                continue;
            }
            if ($n === 'receipt' || str_starts_with($n, 'receipt ') || $n === 'receipt #') {
                if (! isset($map['receipt'])) {
                    $map['receipt'] = $index;
                }
                continue;
            }
            if ($n === 'description' || $n === 'desc' || str_contains($n, 'description')) {
                if (! isset($map['description'])) {
                    $map['description'] = $index;
                }
                continue;
            }
            if ($n === 'amount' || (str_contains($n, 'amount') && ! str_contains($n, 'debit') && ! str_contains($n, 'credit'))) {
                if (! isset($map['amount'])) {
                    $map['amount'] = $index;
                }
            }
        }

        if (! isset($map['date']) || ! isset($map['description']) || ! isset($map['amount'])) {
            return [];
        }

        return $map;
    }

    /**
     * Reason a row was skipped (for exception report).
     */
    protected function getSkipReason(array $row, array $headerMap, string $cardPlatform = OwnerCcStatementCardPlatform::CITY_BANK): string
    {
        $get = $this->headerCellGetter($row, $headerMap);
        $dateStr = $get('date');
        if (! $dateStr) {
            return 'Missing date';
        }
        if (! $this->parseDate($dateStr)) {
            return 'Invalid date format';
        }
        if ($cardPlatform === OwnerCcStatementCardPlatform::CHASE_BANK
            || $cardPlatform === OwnerCcStatementCardPlatform::AMERICAN_EXPRESS) {
            $amountRaw = $get('amount');
            if ($amountRaw === null || $amountRaw === '' || abs($this->parseAmount($amountRaw)) < 0.00001) {
                return 'Missing or zero amount';
            }
        }

        return 'Unknown';
    }

    /**
     * Map one data row to OwnerCcStatementLine attributes.
     */
    protected function mapRowToLine(array $row, array $headerMap, int $importId, string $cardPlatform): ?array
    {
        return match ($cardPlatform) {
            OwnerCcStatementCardPlatform::CHASE_BANK => $this->mapRowToLineChase($row, $headerMap, $importId),
            OwnerCcStatementCardPlatform::AMERICAN_EXPRESS => $this->mapRowToLineAmex($row, $headerMap, $importId),
            default => $this->mapRowToLineCityBank($row, $headerMap, $importId),
        };
    }

    protected function mapRowToLineCityBank(array $row, array $headerMap, int $importId): ?array
    {
        $get = $this->headerCellGetter($row, $headerMap);

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
        $amountRaw = $get('amount');

        $debit = 0.0;
        $credit = 0.0;

        if ($amountRaw !== null && $amountRaw !== '') {
            $amount = $this->parseAmount($amountRaw);
            if ($amount >= 0) {
                $debit = $amount;
            } else {
                $credit = abs($amount);
            }
        } else {
            $debit = $this->parseAmount($debitRaw ?? '0');
            $credit = $this->parseAmount($creditRaw ?? '0');
            if ($credit === 0.0 && $debit < 0) {
                $credit = abs($debit);
                $debit = 0.0;
            }
        }

        $cardLast4Raw = $get('card last4');
        $cardLast4 = $this->extractCardLast4($cardLast4Raw);

        return [
            'owner_cc_statement_import_id' => $importId,
            'status' => $get('status'),
            'transaction_date' => $transactionDate,
            'description' => $get('description'),
            'debit' => $debit,
            'credit' => $credit,
            'member_name' => $get('member name'),
            'card_last4' => $cardLast4,
        ];
    }

    protected function mapRowToLineChase(array $row, array $headerMap, int $importId): ?array
    {
        $get = $this->headerCellGetter($row, $headerMap);

        $dateStr = $get('date') ?? $get('post_date');
        if (! $dateStr) {
            return null;
        }
        $transactionDate = $this->parseDate($dateStr);
        if (! $transactionDate) {
            return null;
        }

        $amountRaw = $get('amount');
        if ($amountRaw === null || $amountRaw === '') {
            return null;
        }
        $amount = $this->parseAmount($amountRaw);
        if (abs($amount) < 0.00001) {
            return null;
        }

        $debit = 0.0;
        $credit = 0.0;
        if ($amount >= 0) {
            $debit = $amount;
        } else {
            $credit = abs($amount);
        }

        $descParts = array_filter([
            $get('description'),
            $get('category') ? 'Category: ' . $get('category') : null,
            $get('chase_type') ? 'Type: ' . $get('chase_type') : null,
            $get('memo') ? 'Memo: ' . $get('memo') : null,
        ]);
        $description = implode(' | ', $descParts);

        $cardLast4 = $this->extractCardLast4($get('card'));

        return [
            'owner_cc_statement_import_id' => $importId,
            'status' => null,
            'transaction_date' => $transactionDate,
            'description' => $description !== '' ? $description : null,
            'debit' => $debit,
            'credit' => $credit,
            'member_name' => null,
            'card_last4' => $cardLast4,
        ];
    }

    protected function mapRowToLineAmex(array $row, array $headerMap, int $importId): ?array
    {
        $get = $this->headerCellGetter($row, $headerMap);

        $dateStr = $get('date');
        if (! $dateStr) {
            return null;
        }
        $transactionDate = $this->parseDate($dateStr);
        if (! $transactionDate) {
            return null;
        }

        $amountRaw = $get('amount');
        if ($amountRaw === null || $amountRaw === '') {
            return null;
        }
        $amount = $this->parseAmount($amountRaw);
        if (abs($amount) < 0.00001) {
            return null;
        }

        $debit = 0.0;
        $credit = 0.0;
        if ($amount >= 0) {
            $debit = $amount;
        } else {
            $credit = abs($amount);
        }

        $baseDesc = trim((string) ($get('description') ?? ''));
        $receipt = $get('receipt');
        $description = $receipt
            ? ('Receipt ' . trim($receipt) . ($baseDesc !== '' ? ' · ' . $baseDesc : ''))
            : $baseDesc;

        return [
            'owner_cc_statement_import_id' => $importId,
            'status' => null,
            'transaction_date' => $transactionDate,
            'description' => $description !== '' ? $description : null,
            'debit' => $debit,
            'credit' => $credit,
            'member_name' => null,
            'card_last4' => null,
        ];
    }

    protected function extractCardLast4(?string $cardLast4Raw): ?string
    {
        if ($cardLast4Raw === null || $cardLast4Raw === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $cardLast4Raw);
        if (strlen($digits) >= 4) {
            return substr($digits, -4);
        }

        return null;
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
