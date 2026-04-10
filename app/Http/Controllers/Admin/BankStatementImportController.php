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
use App\Models\OwnerCcDescriptionMapping;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
            ->where(function ($q) use ($accessibleIds, $user) {
                $q->whereIn('store_id', $accessibleIds);
                if ($user->isAdmin() || $user->isFranchisor()) {
                    $q->orWhereNull('store_id');
                } elseif ($user->isOwner()) {
                    $q->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('store_id')->where('imported_by', $user->id);
                    });
                }
            })
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

        // Store-scoped accounts plus corporate (null store_id); plain whereIn(store_id, …) never matches NULL.
        $bankAccounts = BankAccount::query()
            ->where('is_active', true)
            ->where(function ($q) use ($accessibleIds) {
                $q->whereIn('store_id', $accessibleIds)
                    ->orWhereNull('store_id');
            })
            ->with('store')
            ->orderBy('bank_name')
            ->orderBy('id')
            ->get();

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
            'bankAccounts' => $bankAccounts,
            'bankAccountsPayload' => $bankAccountsPayload,
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
            ->where('is_active', true)
            ->where(function ($q) use ($storeId, $accessibleIds) {
                $q->where('store_id', $storeId)->orWhereNull('store_id');
            })
            ->first();

        if (! $account) {
            return back()
                ->with('error', 'The selected bank account is inactive, missing, or is not valid for the chosen store.')
                ->withInput();
        }

        if ($account->store_id !== null && ! in_array((int) $account->store_id, $accessibleIds, true)) {
            abort(403);
        }

        try {
            $batch = app(BankImportController::class)->runBankStatementImportForAdmin(
                $request->file('file'),
                $account,
                'bank_west',
                (int) auth()->id(),
                $storeId
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
        $this->authorizeBankStatementBatch($importBatch);

        $importBatch->load(['store', 'importer']);

        $transactionTypeFilter = request()->query('transaction_type', 'debit');
        if (! in_array($transactionTypeFilter, ['all', 'debit', 'credit'], true)) {
            $transactionTypeFilter = 'debit';
        }

        $transactionsQuery = $importBatch->bankTransactions()
            ->with(['matchedExpense.coa', 'coa'])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($transactionTypeFilter !== 'all') {
            $transactionsQuery->where('transaction_type', $transactionTypeFilter);
        }

        $transactions = $transactionsQuery->get();
        $transactionCounts = [
            'all' => (int) $importBatch->bankTransactions()->count(),
            'debit' => (int) $importBatch->bankTransactions()->where('transaction_type', 'debit')->count(),
            'credit' => (int) $importBatch->bankTransactions()->where('transaction_type', 'credit')->count(),
        ];

        // Same COA picker list as Owner CC statements (Expense/COGS detail accounts in 5001–5999 / 6001–6999).
        $chartOfAccounts = ChartOfAccount::active()
            ->whereIn('account_type', ['Expense', 'COGS'])
            ->where(function ($q) {
                $q->whereRaw('CAST(account_code AS UNSIGNED) BETWEEN 5001 AND 5999')
                    ->orWhereRaw('CAST(account_code AS UNSIGNED) BETWEEN 6001 AND 6999');
            })
            ->whereNotIn('account_code', ChartOfAccount::totalRollupAccountCodes())
            ->orderBy('account_code')
            ->get();

        $patterns = $transactions
            ->filter(fn (BankTransaction $t) => $t->matched_expense_id || $t->transaction_type === 'credit')
            ->map(fn (BankTransaction $t) => OwnerCcDescriptionMapping::normalizeDescription($t->description))
            ->filter()
            ->unique()
            ->values();

        $learnedCoaByPattern = $patterns->isEmpty()
            ? collect()
            : OwnerCcDescriptionMapping::query()
                ->whereIn('description_pattern', $patterns->all())
                ->whereNotNull('coa_id')
                ->pluck('coa_id', 'description_pattern');

        return view('admin.bank-statement-imports.show', [
            'batch' => $importBatch,
            'transactions' => $transactions,
            'transactionTypeFilter' => $transactionTypeFilter,
            'transactionCounts' => $transactionCounts,
            'chartOfAccounts' => $chartOfAccounts,
            'learnedCoaByPattern' => $learnedCoaByPattern,
        ]);
    }

    public function updateTransactionCoa(Request $request, ImportBatch $importBatch, BankTransaction $bankTransaction): JsonResponse
    {
        $this->authorizeBankStatementBatch($importBatch);

        if ((int) $bankTransaction->import_batch_id !== (int) $importBatch->id) {
            abort(404);
        }

        $data = $request->validate([
            'coa_id' => ['nullable', 'integer', 'exists:chart_of_accounts,id'],
        ]);

        $coaId = isset($data['coa_id']) && $data['coa_id'] !== '' ? (int) $data['coa_id'] : null;

        if ($bankTransaction->matched_expense_id) {
            DB::transaction(function () use ($bankTransaction, $coaId) {
                $this->applyCoaToMatchedBankExpense($bankTransaction, $coaId);
            });
        } elseif ($bankTransaction->transaction_type === 'credit') {
            DB::transaction(function () use ($bankTransaction, $coaId) {
                $this->applyCoaToCreditBankTransaction($bankTransaction, $coaId);
            });
        } else {
            return response()->json(['message' => 'Chart of account applies to credits or to debits that have a linked expense.'], 422);
        }

        return response()->json([
            'message' => 'Chart of account saved.',
            'coa_id' => $coaId,
        ]);
    }

    protected function applyCoaToMatchedBankExpense(BankTransaction $bankTxn, ?int $coaId): void
    {
        if (! $bankTxn->matched_expense_id) {
            return;
        }

        ExpenseTransaction::whereKey($bankTxn->matched_expense_id)->update([
            'coa_id' => $coaId,
            'needs_review' => $coaId === null,
            'review_reason' => $coaId === null ? 'COA not assigned' : null,
        ]);

        $this->saveLearnedCoaMappingFromBankDescription($bankTxn->description, $coaId);

        $bankTxn->update(['coa_id' => $coaId]);
    }

    protected function applyCoaToCreditBankTransaction(BankTransaction $bankTxn, ?int $coaId): void
    {
        if ($bankTxn->transaction_type !== 'credit') {
            return;
        }

        $bankTxn->update(['coa_id' => $coaId]);
        $this->saveLearnedCoaMappingFromBankDescription($bankTxn->description, $coaId);
    }

    public function destroy(ImportBatch $importBatch): RedirectResponse
    {
        $this->authorizeBankStatementBatch($importBatch);

        DB::transaction(function () use ($importBatch) {
            ExpenseTransaction::query()->where('import_batch_id', $importBatch->id)->delete();
            BankTransaction::query()->where('import_batch_id', $importBatch->id)->delete();
            $importBatch->delete();
        });

        return redirect()
            ->route('admin.bank-statement-imports.index')
            ->with('success', 'Bank statement import, bank lines, and expenses created from this import have been deleted.');
    }

    /**
     * Web authorization for bank_statement batches. Never cast null store_id to 0 (that always fails hasStoreAccess).
     */
    protected function authorizeBankStatementBatch(ImportBatch $importBatch): void
    {
        if ($importBatch->import_type !== 'bank_statement') {
            abort(404);
        }

        /** @var User $user */
        $user = auth()->user();

        if ($importBatch->store_id !== null) {
            if (! $user->hasStoreAccess((int) $importBatch->store_id)) {
                abort(403);
            }

            return;
        }

        if ($user->isAdmin() || $user->isFranchisor()) {
            return;
        }

        if ($user->isOwner() && (int) $importBatch->imported_by === (int) $user->id) {
            return;
        }

        abort(403);
    }

    /**
     * Persist description → COA for auto-suggest (same table as Owner CC statement imports).
     */
    protected function saveLearnedCoaMappingFromBankDescription(?string $description, $coaId): void
    {
        $pattern = OwnerCcDescriptionMapping::normalizeDescription($description);
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
}
