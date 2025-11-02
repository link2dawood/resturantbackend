<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseTransaction;
use App\Models\DailyReportTransaction;
use App\Models\Vendor;
use App\Models\TransactionMappingRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator', 'dailyReport']);

        // Filters
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('transaction_type')) {
            $query->byType($request->transaction_type);
        }

        if ($request->has('needs_review')) {
            if ($request->boolean('needs_review')) {
                $query->needsReview();
            } else {
                $query->where('needs_review', false);
            }
        }

        if ($request->has('vendor_id')) {
            $query->forVendor($request->vendor_id);
        }

        if ($request->has('coa_id')) {
            $query->forCoa($request->coa_id);
        }

        $expenses = $query->orderBy('transaction_date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate($request->per_page ?? 50);

        return response()->json($expenses);
    }

    /**
     * Display the specified expense
     */
    public function show($id)
    {
        $expense = ExpenseTransaction::with([
            'store', 
            'vendor', 
            'coa', 
            'creator', 
            'dailyReport',
            'reconciledByUser'
        ])->findOrFail($id);

        return response()->json($expense);
    }

    /**
     * Store a newly created manual expense
     */
    public function store(Request $request)
    {
        // Authorization check - managers, owners, and admins can create
        if (!auth()->user() || (auth()->user()->isAdmin() || auth()->user()->isOwner() || auth()->user()->isManager())) {
            // Allow
        } else {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'store_id' => 'required|exists:stores,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'coa_id' => 'required|exists:chart_of_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,credit_card,debit_card,check,eft,other',
            'description' => 'nullable|string',
            'reference_number' => 'nullable|string|max:100',
            'receipt_url' => 'nullable|url',
            'notes' => 'nullable|string',
            'transaction_type' => 'nullable|in:cash,credit_card,bank_transfer,check',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Determine transaction type based on payment method
        $transactionType = $request->transaction_type ?? $this->mapPaymentMethodToTransactionType($request->payment_method);

        // Generate duplicate check hash
        $duplicateHash = $this->generateDuplicateHash(
            $request->store_id,
            $request->transaction_date,
            $request->vendor_id,
            $request->amount
        );

        // Check for duplicates
        $existing = ExpenseTransaction::where('duplicate_check_hash', $duplicateHash)->first();
        if ($existing) {
            return response()->json([
                'error' => 'Potential duplicate transaction detected',
                'duplicate_id' => $existing->id
            ], 422);
        }

        $expense = ExpenseTransaction::create([
            'transaction_type' => $transactionType,
            'transaction_date' => $request->transaction_date,
            'store_id' => $request->store_id,
            'vendor_id' => $request->vendor_id,
            'coa_id' => $request->coa_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'reference_number' => $request->reference_number,
            'receipt_url' => $request->receipt_url,
            'notes' => $request->notes,
            'duplicate_check_hash' => $duplicateHash,
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'data' => $expense->load(['vendor', 'coa', 'store'])
        ], 201);
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $expense = ExpenseTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'nullable|exists:vendors,id',
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
            'notes' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = [];
        if ($request->has('vendor_id')) {
            $updateData['vendor_id'] = $request->vendor_id;
        }
        if ($request->has('coa_id')) {
            $updateData['coa_id'] = $request->coa_id;
        }
        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }
        if ($request->has('description')) {
            $updateData['description'] = $request->description;
        }

        // If vendor and COA are both assigned, remove from review queue
        if (!empty($updateData['vendor_id']) && !empty($updateData['coa_id'])) {
            $updateData['needs_review'] = false;
            $updateData['review_reason'] = null;
        }

        $expense->update($updateData);

        return response()->json([
            'message' => 'Expense updated successfully',
            'data' => $expense->load(['vendor', 'coa', 'store'])
        ]);
    }

    /**
     * Sync cash expenses from daily reports
     */
    public function syncCashExpenses(Request $request)
    {
        // Authorization check - only admins
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get all cash transactions from daily reports
        $query = DailyReportTransaction::with(['dailyReport', 'transactionType'])
            ->whereHas('dailyReport', function($q) use ($request) {
                if ($request->has('start_date')) {
                    $q->whereDate('report_date', '>=', $request->start_date);
                }
                if ($request->has('end_date')) {
                    $q->whereDate('report_date', '<=', $request->end_date);
                }
                if ($request->has('store_id')) {
                    $q->where('store_id', $request->store_id);
                }
            });

        $cashTransactions = $query->get();

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($cashTransactions as $cashTrans) {
                // Check if already imported
                $existing = ExpenseTransaction::where('daily_report_id', $cashTrans->daily_report_id)
                    ->whereNotNull('daily_report_id')
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Try to match vendor
                $vendor = null;
                if ($cashTrans->company) {
                    $vendor = $this->matchVendor($cashTrans->company);
                }

                // Determine COA
                $coaId = null;
                if ($vendor && $vendor->default_coa_id) {
                    $coaId = $vendor->default_coa_id;
                }

                // Generate duplicate hash
                $duplicateHash = $this->generateDuplicateHash(
                    $cashTrans->dailyReport->store_id,
                    $cashTrans->dailyReport->report_date,
                    $vendor?->id,
                    $cashTrans->amount
                );

                // Create expense transaction
                $expense = ExpenseTransaction::create([
                    'transaction_type' => 'cash',
                    'transaction_date' => $cashTrans->dailyReport->report_date,
                    'store_id' => $cashTrans->dailyReport->store_id,
                    'vendor_id' => $vendor?->id,
                    'vendor_name_raw' => $cashTrans->company,
                    'coa_id' => $coaId,
                    'amount' => $cashTrans->amount,
                    'description' => $cashTrans->transactionType?->name ?? 'Cash Expense',
                    'payment_method' => 'cash',
                    'needs_review' => !$vendor || !$coaId,
                    'review_reason' => !$vendor ? 'Vendor not found' : (!$coaId ? 'COA not assigned' : null),
                    'duplicate_check_hash' => $duplicateHash,
                    'daily_report_id' => $cashTrans->daily_report_id,
                    'created_by' => $cashTrans->dailyReport->created_by,
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Cash expenses synced successfully',
                'imported' => $imported,
                'skipped' => $skipped,
                'total_processed' => $cashTransactions->count()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error syncing expenses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Match vendor by company name
     */
    private function matchVendor($companyName)
    {
        // First, try exact match with aliases
        $vendor = Vendor::whereHas('aliases', function($q) use ($companyName) {
            $q->where('alias', $companyName);
        })->first();

        if ($vendor) {
            return $vendor;
        }

        // Try fuzzy matching
        $matchResult = app(\App\Http\Controllers\Api\VendorController::class)->match(new Request(['description' => $companyName]));
        $matchData = json_decode($matchResult->getContent(), true);

        if ($matchData && $matchData['match'] && $matchData['confidence'] >= 60) {
            return Vendor::find($matchData['vendor']['id']);
        }

        return null;
    }

    /**
     * Generate duplicate check hash
     */
    private function generateDuplicateHash($storeId, $date, $vendorId, $amount)
    {
        $data = $storeId . '|' . $date . '|' . ($vendorId ?? 'NULL') . '|' . $amount;
        return Hash::make($data);
    }

    /**
     * Map payment method to transaction type
     */
    private function mapPaymentMethodToTransactionType($paymentMethod)
    {
        return match($paymentMethod) {
            'cash' => 'cash',
            'credit_card', 'debit_card' => 'credit_card',
            'check' => 'check',
            'eft' => 'bank_transfer',
            default => 'cash',
        };
    }

    /**
     * Get review queue - all transactions needing review
     */
    public function reviewQueue(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = ExpenseTransaction::with(['store', 'vendor', 'coa', 'creator'])
            ->where('needs_review', true);

        // Filters
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('review_reason')) {
            $query->where('review_reason', $request->review_reason);
        }

        $expenses = $query->orderBy('transaction_date', 'desc')->get();

        // Group by review_reason
        $grouped = $expenses->groupBy('review_reason');

        return response()->json([
            'total' => $expenses->count(),
            'by_reason' => $grouped->map(function($items) {
                return [
                    'count' => $items->count(),
                    'transactions' => $items
                ];
            })
        ]);
    }

    /**
     * Resolve a single transaction
     */
    public function resolve(Request $request, $id)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $expense = ExpenseTransaction::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'vendor_id' => 'nullable|exists:vendors,id',
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
            'create_mapping_rule' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Update transaction
            $updateData = [];
            if ($request->has('vendor_id')) {
                $updateData['vendor_id'] = $request->vendor_id;
            }
            if ($request->has('coa_id')) {
                $updateData['coa_id'] = $request->coa_id;
            }
            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }
            
            // Only remove from review if both vendor and COA are set
            if (!empty($updateData['vendor_id']) && !empty($updateData['coa_id'])) {
                $updateData['needs_review'] = false;
                $updateData['review_reason'] = null;
            }

            $expense->update($updateData);

            // Create mapping rule if requested
            if ($request->boolean('create_mapping_rule') && $request->has('coa_id')) {
                TransactionMappingRule::create([
                    'description_pattern' => $expense->description ?? $expense->vendor_name_raw ?? 'Unknown',
                    'vendor_id' => $request->vendor_id ?? null,
                    'coa_id' => $request->coa_id,
                    'confidence_score' => 0.75,
                    'times_used' => 0,
                    'times_correct' => 0,
                    'times_incorrect' => 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Transaction resolved successfully',
                'data' => $expense->fresh(['vendor', 'coa', 'store'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error resolving transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk resolve multiple transactions
     */
    public function bulkResolve(Request $request)
    {
        // Authorization check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'expense_ids' => 'required|array|min:1',
            'expense_ids.*' => 'exists:expense_transactions,id',
            'vendor_id' => 'nullable|exists:vendors,id',
            'coa_id' => 'nullable|exists:chart_of_accounts,id',
            'create_mapping_rule' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $updated = 0;
            
            foreach ($request->expense_ids as $expenseId) {
                $expense = ExpenseTransaction::findOrFail($expenseId);

                $updateData = [];
                if ($request->has('vendor_id')) {
                    $updateData['vendor_id'] = $request->vendor_id;
                }
                if ($request->has('coa_id')) {
                    $updateData['coa_id'] = $request->coa_id;
                }
                if ($request->has('notes')) {
                    $updateData['notes'] = $request->notes;
                }

                // Remove from review if both vendor and COA are set
                if (!empty($updateData['vendor_id']) && !empty($updateData['coa_id'])) {
                    $updateData['needs_review'] = false;
                    $updateData['review_reason'] = null;
                }

                $expense->update($updateData);
                $updated++;

                // Create mapping rule if requested (only for first transaction to avoid duplicates)
                if ($updated === 1 && $request->boolean('create_mapping_rule') && $request->has('coa_id')) {
                    TransactionMappingRule::create([
                        'description_pattern' => $expense->description ?? $expense->vendor_name_raw ?? 'Unknown',
                        'vendor_id' => $request->vendor_id ?? null,
                        'coa_id' => $request->coa_id,
                        'confidence_score' => 0.75,
                        'times_used' => 0,
                        'times_correct' => 0,
                        'times_incorrect' => 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully resolved {$updated} transactions",
                'updated_count' => $updated
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error resolving transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get review statistics
     */
    public function reviewStats(Request $request)
    {
        // Authorization - all authenticated users
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $query = ExpenseTransaction::where('needs_review', true);

        // Filter by store if user is not admin
        if (!auth()->user()->isAdmin()) {
            if (auth()->user()->isManager()) {
                $query->where('store_id', auth()->user()->store_id);
            } elseif (auth()->user()->isOwner()) {
                // Owners can see their own stores
                $ownedStoreIds = DB::table('stores')->where('created_by', auth()->id())->pluck('id');
                $query->whereIn('store_id', $ownedStoreIds);
            }
        }

        $stats = [
            'total_pending' => $query->count(),
            'by_reason' => $query->select('review_reason', DB::raw('count(*) as count'))
                ->groupBy('review_reason')
                ->pluck('count', 'review_reason'),
            'by_store' => $query->select('store_id', DB::raw('count(*) as count'))
                ->groupBy('store_id')
                ->with('store:id,store_info')
                ->get()
                ->mapWithKeys(function($item) {
                    return [$item->store->store_info ?? 'Unknown' => $item->count];
                })
        ];

        return response()->json($stats);
    }
}
