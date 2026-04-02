<?php

namespace App\Http\Controllers\Admin;

use App\Constants\BankStatementSupportedBank;
use App\Http\Controllers\Api\BankImportController;
use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ChartOfAccount;
use App\Models\ExpenseTransaction;
use App\Models\ImportBatch;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankStatementImportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $accessibleIds = $user->getAccessibleStoreIds();

        $query = ImportBatch::query()
            ->with(['store', 'importer'])
            ->where('import_type', 'bank_statement')
            ->whereIn('store_id', $accessibleIds)
            ->orderByDesc('imported_at');

        if ($request->filled('store_id')) {
            $sid = (int) $request->store_id;
            if (in_array($sid, $accessibleIds, true)) {
                $query->where('store_id', $sid);
            }
        }

        $batches = $query->paginate(20);
        $stores = Store::whereIn('id', $accessibleIds)->orderBy('store_info')->get();

        return view('admin.bank-statement-imports.index', [
            'batches' => $batches,
            'stores' => $stores,
        ]);
    }

    public function create()
    {
        $user = auth()->user();
        $accessibleIds = $user->getAccessibleStoreIds();
        $stores = Store::whereIn('id', $accessibleIds)->orderBy('store_info')->get();

        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->whereIn('store_id', $accessibleIds)
            ->orderBy('bank_name')
            ->orderBy('id')
            ->get(['id', 'store_id', 'bank_name', 'account_number_last_four', 'account_type']);

        $bankAccountsPayload = $bankAccounts->map(function (BankAccount $ba) {
            $tail = $ba->account_number_last_four ? ' · …'.$ba->account_number_last_four : '';

            return [
                'id' => $ba->id,
                'store_id' => $ba->store_id,
                'label' => $ba->bank_name.$tail,
                'bow' => BankStatementSupportedBank::isLikelyBankOfTheWestName($ba->bank_name),
            ];
        })->values();

        return view('admin.bank-statement-imports.create', [
            'stores' => $stores,
            'bankLabel' => BankStatementSupportedBank::BANK_OF_THE_WEST_LABEL,
            'bankAccountsPayload' => $bankAccountsPayload,
            'hasAnyBankAccount' => $bankAccounts->isNotEmpty(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $accessibleIds = $user->getAccessibleStoreIds();

        $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'bank_account_id' => 'required|integer|exists:bank_accounts,id',
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $storeId = (int) $request->store_id;
        if (! in_array($storeId, $accessibleIds, true)) {
            abort(403);
        }

        $account = BankAccount::query()
            ->whereKey((int) $request->bank_account_id)
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->first();

        if (! $account) {
            return back()
                ->with('error', 'The selected bank account is inactive, missing, or does not belong to the chosen store.')
                ->withInput();
        }

        try {
            $batch = app(BankImportController::class)->runBankStatementImportForAdmin(
                $request->file('file'),
                $account,
                'bank_west',
                (int) auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            Log::error('Bank statement admin import failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Import failed: '.$e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.bank-statement-imports.show', $batch)
            ->with('success', 'Bank statement imported. Assign Chart of Account for debits that created expenses below.');
    }

    public function show(ImportBatch $importBatch)
    {
        if ($importBatch->import_type !== 'bank_statement') {
            abort(404);
        }

        $user = auth()->user();
        if (! $user->hasStoreAccess((int) $importBatch->store_id)) {
            abort(403);
        }

        $importBatch->load(['store', 'importer']);

        $transactions = $importBatch->bankTransactions()
            ->with(['matchedExpense.chartOfAccount'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $chartOfAccounts = ChartOfAccount::active()
            ->whereIn('account_type', ['Expense', 'COGS'])
            ->where(function ($q) {
                $q->whereRaw('CAST(account_code AS UNSIGNED) BETWEEN 5001 AND 5999')
                    ->orWhereRaw('CAST(account_code AS UNSIGNED) BETWEEN 6001 AND 6999');
            })
            ->whereNotIn('account_code', ChartOfAccount::totalRollupAccountCodes())
            ->orderBy('account_code')
            ->get();

        return view('admin.bank-statement-imports.show', [
            'batch' => $importBatch,
            'transactions' => $transactions,
            'chartOfAccounts' => $chartOfAccounts,
        ]);
    }

    public function bulkUpdateCoa(Request $request, ImportBatch $importBatch): RedirectResponse
    {
        if ($importBatch->import_type !== 'bank_statement') {
            abort(404);
        }

        $user = auth()->user();
        if (! $user->hasStoreAccess((int) $importBatch->store_id)) {
            abort(403);
        }

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:bank_transactions,id'],
            'lines.*.coa_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
        ]);

        DB::transaction(function () use ($data, $importBatch) {
            foreach ($data['lines'] as $row) {
                $bankTxnId = (int) $row['id'];
                $bankTxn = $importBatch->bankTransactions()->whereKey($bankTxnId)->first();
                if (! $bankTxn || ! $bankTxn->matched_expense_id) {
                    continue;
                }

                $coaId = ! empty($row['coa_id']) ? (int) $row['coa_id'] : null;

                ExpenseTransaction::whereKey($bankTxn->matched_expense_id)->update([
                    'coa_id' => $coaId,
                    'needs_review' => $coaId === null,
                    'review_reason' => $coaId === null ? 'COA not assigned' : null,
                ]);
            }
        });

        return back()->with('success', 'Chart of Account assignments saved.');
    }

    public function destroy(ImportBatch $importBatch): RedirectResponse
    {
        if ($importBatch->import_type !== 'bank_statement') {
            abort(404);
        }

        if (! auth()->user()->hasStoreAccess((int) $importBatch->store_id)) {
            abort(403);
        }

        DB::transaction(function () use ($importBatch) {
            ExpenseTransaction::query()->where('import_batch_id', $importBatch->id)->delete();
            BankTransaction::query()->where('import_batch_id', $importBatch->id)->delete();
            $importBatch->delete();
        });

        return redirect()
            ->route('admin.bank-statement-imports.index')
            ->with('success', 'Bank statement import, bank lines, and expenses created from this import have been deleted.');
    }
}
