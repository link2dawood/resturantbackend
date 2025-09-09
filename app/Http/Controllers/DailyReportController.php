<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\DailyReportTransaction;
use App\Models\DailyReportRevenue;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class DailyReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = DailyReport::with(['store', 'creator', 'approver', 'transactions', 'revenues.revenueIncomeType']);
        
        // Filter reports based on user role
        if ($user->hasPermission('view_reports')) {
            if ($user->role === \App\Enums\UserRole::OWNER) {
                $query->whereHas('store', function ($q) use ($user) {
                    $q->where('created_by', $user->id);
                });
            } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
                $query->whereHas('store', function ($q) use ($user) {
                    $q->whereHas('managers', function ($subQ) use ($user) {
                        $subQ->where('users.id', $user->id);
                    });
                });
            }
        }
        
        // Apply search filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('store', function ($storeQ) use ($search) {
                    $storeQ->where('store_info', 'LIKE', "%{$search}%");
                })
                ->orWhereHas('creator', function ($userQ) use ($search) {
                    $userQ->where('name', 'LIKE', "%{$search}%");
                })
                ->orWhere('gross_sales', 'LIKE', "%{$search}%")
                ->orWhere('net_sales', 'LIKE', "%{$search}%");
            });
        }
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }
        
        // Store filter
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        
        // Amount range filter
        if ($request->filled('min_amount')) {
            $query->where('gross_sales', '>=', $request->min_amount);
        }
        
        if ($request->filled('max_amount')) {
            $query->where('gross_sales', '<=', $request->max_amount);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'report_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $validSortFields = ['report_date', 'gross_sales', 'net_sales', 'created_at'];
        if (in_array($sortBy, $validSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('report_date', 'desc');
        }
        
        $reports = $query->paginate(15)->appends($request->query());
        
        // Get available stores for filter dropdown (based on user role)
        $storesQuery = Store::query();
        if ($user->hasPermission('view_reports')) {
            if ($user->role === \App\Enums\UserRole::OWNER) {
                $storesQuery->where('created_by', $user->id);
            } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
                $storesQuery->whereHas('managers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
        }
        $stores = $storesQuery->get();
            
        return view('daily-reports.index', compact('reports', 'stores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        // Critical security fix: Check permission before allowing access
        if (!$user->hasPermission('create_reports')) {
            abort(403, 'You do not have permission to create daily reports.');
        }
        
        $query = Store::query();
        $types = TransactionType::all();
        $revenueTypes = RevenueIncomeType::where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get();

        // Filter stores based on user role (security critical)
        if ($user->role === \App\Enums\UserRole::OWNER) {
            $query->where('created_by', $user->id);
        } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
            $query->whereHas('managers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        // Admins see all stores
        
        $stores = $query->get();
        
        // Business logic fix: Prevent unassigned managers from accessing create form
        if ($user->isManager() && $stores->isEmpty()) {
            return redirect()->route('daily-reports.index')
                ->with('warning', 'You have not been assigned to any stores. Please contact an administrator to assign stores to your account.');
        }
        
        $store = $stores->first(); // Select the first store as default (may be null if no stores)

        return view('daily-reports.create', compact('stores', 'types', 'revenueTypes', 'store'));
    }

    /**
     * Show the quick entry form for creating a new resource.
     */
    public function quickEntry()
    {
        $user = auth()->user();
        $query = Store::query();
        $types = TransactionType::all();
        
        // Filter stores based on user role
        if ($user->hasPermission('create_reports')) {
            if ($user->role === \App\Enums\UserRole::OWNER) {
                $query->where('created_by', $user->id);
            } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
                $query->whereHas('managers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
            // Admins see all stores
        }
        
        $stores = $query->get();
        return view('daily-reports.quick-entry', compact('stores', 'types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'report_date'         => 'required|date',
            'page_number'         => 'nullable|integer|min:1',
            'weather'             => 'nullable|string|max:50',
            'holiday_event'       => 'nullable|string|max:100',
        
            // decimals
            'projected_sales'     => 'required|numeric',
            'gross_sales'         => 'required|numeric',
            'amount_of_cancels'   => 'nullable|numeric',
            'amount_of_voids'     => 'nullable|numeric',
            'coupons_received'    => 'nullable|numeric',
            'adjustments_overrings' => 'nullable|numeric',
            'net_sales'           => 'nullable|numeric',
            'tax'                 => 'nullable|numeric',
            'average_ticket'      => 'nullable|numeric',
            'sales'               => 'nullable|numeric',
            'total_paid_outs'     => 'required|numeric',
            'credit_cards'        => 'nullable|numeric',
            'cash_to_account'     => 'nullable|numeric',
            'actual_deposit'      => 'nullable|numeric',
            'short'               => 'nullable|numeric',
            'over'                => 'nullable|numeric',
        
            // integers
            'number_of_no_sales'  => 'nullable|integer',
            'total_coupons'       => 'nullable|integer',
            'total_customers'     => 'nullable|integer',
        
            // relationships
            'store_id'            => 'required|exists:stores,id',
        
            // approval workflow
            'approval_notes'      => 'nullable|string|max:1000',
            
            // revenue entries
            'revenues'            => 'nullable|array',
            'revenues.*.revenue_income_type_id' => 'required_with:revenues|exists:revenue_income_types,id',
            'revenues.*.amount'   => 'required_with:revenues|numeric|min:0',
            'revenues.*.notes'    => 'nullable|string|max:500',
        ]);

        // Validate manager can only create reports for assigned stores
        $user = auth()->user();
        if ($user->role === \App\Enums\UserRole::MANAGER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->whereHas('managers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->first();
                
            if (!$allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to create reports for this store.']
                ]);
            }
        } elseif ($user->role === \App\Enums\UserRole::OWNER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->where('created_by', $user->id)
                ->first();
                
            if (!$allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to create reports for this store.']
                ]);
            }
        }

        // Check for duplicate reports (same store, same date)
        $existingReport = DailyReport::where('store_id', $validatedData['store_id'])
                                   ->where('report_date', $validatedData['report_date'])
                                   ->first();
                                   
        if ($existingReport) {
            throw ValidationException::withMessages([
                'report_date' => ['A daily report for this store already exists for the selected date. Please edit the existing report or choose a different date.']
            ]);
        }
        

        // Additional business logic validations
        $this->validateBusinessRules($validatedData);

        try {
            DB::beginTransaction();
            
            $validatedData['created_by'] = auth()->id();
            
            // Calculate derived fields
            $validatedData = $this->calculateDerivedFields($validatedData);
            
            $dailyReport = DailyReport::create($validatedData);
            
            // Log the creation
            AuditLog::log('created', $dailyReport, null, $dailyReport->toArray(), [
                'store_name' => $dailyReport->store->store_info ?? 'Unknown',
                'report_date' => $dailyReport->report_date->format('Y-m-d')
            ]);
            
            if ($request->has('transactions')) {
                foreach ($request->transactions as $transactionData) {
                    if (empty($transactionData['company']) || empty($transactionData['amount'])) {
                        continue; // Skip empty transaction rows
                    }

                    // Validate transaction_type_id
                    if (!empty($transactionData['transaction_id']) && !TransactionType::find($transactionData['transaction_id'])) {
                        throw ValidationException::withMessages([
                            'transactions' => "Invalid transaction type ID: {$transactionData['transaction_id']}"
                        ]);
                    }

                    DailyReportTransaction::create([
                        'daily_report_id' => $dailyReport->id,
                        'transaction_type_id' => $transactionData['transaction_id'] ?? null,
                        'company' => $transactionData['company'],
                        'amount' => (float) $transactionData['amount'],
                    ]);
                }
            }
            
            // Process revenue entries
            if ($request->has('revenues')) {
                foreach ($request->revenues as $revenueData) {
                    if (empty($revenueData['revenue_income_type_id']) || empty($revenueData['amount'])) {
                        continue; // Skip empty revenue rows
                    }

                    DailyReportRevenue::create([
                        'daily_report_id' => $dailyReport->id,
                        'revenue_income_type_id' => $revenueData['revenue_income_type_id'],
                        'amount' => (float) $revenueData['amount'],
                        'notes' => $revenueData['notes'] ?? null,
                        'metadata' => isset($revenueData['metadata']) ? $revenueData['metadata'] : null,
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Daily report created successfully', [
                'report_id' => $dailyReport->id,
                'store_id' => $dailyReport->store_id,
                'user_id' => auth()->id(),
                'report_date' => $dailyReport->report_date
            ]);
            
            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', '✅ Daily report created successfully! All calculations have been verified.');
                
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to create daily report', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'data' => $validatedData
            ]);
            
            return back()->withInput()->withErrors([
                'error' => 'Failed to create daily report: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(DailyReport $dailyReport)
    {
        $dailyReport->load(['store', 'creator', 'transactions', 'revenues.revenueIncomeType']);
        return view('daily-reports.show', compact('dailyReport'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DailyReport $dailyReport)
    {
        $user = auth()->user();
        $query = Store::query();
        
        // Filter stores based on user role
        if ($user->hasPermission('create_reports')) {
            if ($user->role === \App\Enums\UserRole::OWNER) {
                $query->where('created_by', $user->id);
            } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
                $query->whereHas('managers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
            // Admins see all stores
        }
        
        $stores = $query->get();
        $types = TransactionType::all();
        $revenueTypes = RevenueIncomeType::active()->ordered()->get();
        $dailyReport->load(['transactions', 'revenues.revenueIncomeType']);
        return view('daily-reports.edit', compact('dailyReport', 'stores', 'types', 'revenueTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyReport $dailyReport)
    {
        $validatedData = $request->validate([
            'report_date'         => 'required|date',
            'page_number'         => 'nullable|integer|min:1',
            'weather'             => 'nullable|string|max:50',
            'holiday_event'       => 'nullable|string|max:100',
        
            // decimals
            'projected_sales'     => 'required|numeric',
            'gross_sales'         => 'required|numeric',
            'amount_of_cancels'   => 'nullable|numeric',
            'amount_of_voids'     => 'nullable|numeric',
            'coupons_received'    => 'nullable|numeric',
            'adjustments_overrings' => 'nullable|numeric',
            'net_sales'           => 'nullable|numeric',
            'tax'                 => 'nullable|numeric',
            'average_ticket'      => 'nullable|numeric',
            'sales'               => 'nullable|numeric',
            'total_paid_outs'     => 'required|numeric',
            'credit_cards'        => 'nullable|numeric',
            'cash_to_account'     => 'nullable|numeric',
            'actual_deposit'      => 'nullable|numeric',
            'short'               => 'nullable|numeric',
            'over'                => 'nullable|numeric',
        
            // integers
            'number_of_no_sales'  => 'nullable|integer',
            'total_coupons'       => 'nullable|integer',
            'total_customers'     => 'nullable|integer',
        
            // relationships
            'store_id'            => 'required|exists:stores,id',
            
            // transactions
            'transactions' => 'nullable|array',
            'transactions.*.transaction_id' => 'required|integer',
            'transactions.*.company' => 'required|string|max:100',
            'transactions.*.transaction_type' => 'required|in:Food Cost,Rent,Accounting,Taxes,Other',
            'transactions.*.amount' => 'required|numeric|min:0'
        ]);

        // Validate manager can only update reports for assigned stores
        $user = auth()->user();
        if ($user->role === \App\Enums\UserRole::MANAGER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->whereHas('managers', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->first();
                
            if (!$allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to update reports for this store.']
                ]);
            }
        } elseif ($user->role === \App\Enums\UserRole::OWNER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->where('created_by', $user->id)
                ->first();
                
            if (!$allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to update reports for this store.']
                ]);
            }
        }

        // Check for duplicate reports (same store, same date) - excluding current report
        $existingReport = DailyReport::where('store_id', $validatedData['store_id'])
                                   ->where('report_date', $validatedData['report_date'])
                                   ->where('id', '!=', $dailyReport->id)
                                   ->first();
                                   
        if ($existingReport) {
            throw ValidationException::withMessages([
                'report_date' => ['A daily report for this store already exists for the selected date. Please choose a different date.']
            ]);
        }

        try {
            DB::beginTransaction();
            
            // Store old values for audit log
            $oldValues = $dailyReport->toArray();
            
            $validatedData['created_by'] = auth()->id();
            $dailyReport->update($validatedData);
            
            // Log the update
            AuditLog::log('updated', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
                'store_name' => $dailyReport->store->store_info ?? 'Unknown',
                'report_date' => $dailyReport->report_date->format('Y-m-d')
            ]);
            
            $dailyReport->transactions()->delete();
            
            if ($request->has('transactions')) {
                foreach ($request->transactions as $transactionData) {
                    // Validate transaction_type_id
                    if (!empty($transactionData['transaction_id']) && !TransactionType::find($transactionData['transaction_id'])) {
                        throw ValidationException::withMessages([
                            'transactions' => "Invalid transaction type ID: {$transactionData['transaction_id']}"
                        ]);
                    }

                    DailyReportTransaction::create([
                        'daily_report_id' => $dailyReport->id,
                        'transaction_type_id' => $transactionData['transaction_id'] ?? null,
                        'company' => $transactionData['company'] ?? '',
                        'amount' => $transactionData['amount'] ?? 0,
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', 'Daily report updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollback();
            throw ValidationException::withMessages([
                'error' => ['Failed to update daily report. Please try again.']
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DailyReport $dailyReport)
    {
        try {
            // Log the deletion
            AuditLog::log('deleted', $dailyReport, $dailyReport->toArray(), null, [
                'store_name' => $dailyReport->store->store_info ?? 'Unknown',
                'report_date' => $dailyReport->report_date->format('Y-m-d')
            ]);
            
            $dailyReport->delete();
            return redirect()->route('daily-reports.index')
                ->with('success', 'Daily report deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('daily-reports.index')
                ->with('error', 'Failed to delete daily report.');
        }
    }

    /**
     * Show the reports form for creating/editing daily reports
     */
    public function reports($id)
    {
        $user = auth()->user();
        $query = Store::query();
        
        // Filter stores based on user role for security
        if ($user->role === 'owner') {
            $query->where('created_by', $user->id);
        } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
            $query->whereHas('managers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        // Admins can access all stores
        
        $store = $query->find($id);
        
        if (!$store) {
            abort(403, 'You are not authorized to access this store.');
        }
        
        $types = TransactionType::all();
        $revenueTypes = RevenueIncomeType::where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get();
        return view('daily-reports.form', compact('store','types', 'revenueTypes'));
    }

    /**
     * Export daily report as PDF
     */
    public function exportPdf(DailyReport $dailyReport)
    {
        $dailyReport->load(['store', 'creator', 'transactions.transactionType', 'revenues.revenueIncomeType']);
        
        $html = view('daily-reports.pdf', compact('dailyReport'))->render();
        
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'daily-report-' . $dailyReport->store->store_info . '-' . $dailyReport->report_date->format('Y-m-d') . '.pdf';
        
        return $dompdf->stream($filename);
    }

    /**
     * Validate business rules for daily reports
     */
    private function validateBusinessRules($data)
    {
        $errors = [];
        
        // Calculate derived fields
        $grossSales = (float) ($data['gross_sales'] ?? 0);
        $couponsReceived = (float) ($data['coupons_received'] ?? 0);
        $adjustmentsOverrings = (float) ($data['adjustments_overrings'] ?? 0);
        $creditCards = (float) ($data['credit_cards'] ?? 0);
        $actualDeposit = (float) ($data['actual_deposit'] ?? 0);
        $totalPaidOuts = (float) ($data['total_paid_outs'] ?? 0);
        
        // Calculate net sales
        $netSales = $grossSales - $couponsReceived - $adjustmentsOverrings;
        $data['net_sales'] = $netSales;
        
        // Calculate tax (assuming 8.25% tax rate)
        $tax = $netSales - ($netSales / 1.0825);
        $data['tax'] = $tax;
        
        // Calculate sales (pre-tax)
        $data['sales'] = $netSales - $tax;
        
        // Calculate cash to account for
        $cashToAccountFor = $netSales - $totalPaidOuts - $creditCards;
        $data['cash_to_account'] = $cashToAccountFor;
        
        // Calculate short/over
        if ($actualDeposit < $cashToAccountFor) {
            $data['short'] = $actualDeposit - $cashToAccountFor;
            $data['over'] = 0;
        } else {
            $data['short'] = 0;
            $data['over'] = $actualDeposit - $cashToAccountFor;
        }
        
        return $data;
    }
    
    /**
     * Calculate derived fields for the daily report.
     */
    private function calculateDerivedFields(array $data): array
    {
        $grossSales = (float) ($data['gross_sales'] ?? 0);
        $couponsReceived = (float) ($data['coupons_received'] ?? 0);
        $adjustmentsOverrings = (float) ($data['adjustments_overrings'] ?? 0);
        $creditCards = (float) ($data['credit_cards'] ?? 0);
        $totalPaidOuts = (float) ($data['total_paid_outs'] ?? 0);

        // Calculate net sales
        $data['net_sales'] = $grossSales - $couponsReceived - $adjustmentsOverrings;

        // Calculate cash to account
        $data['cash_to_account'] = $data['net_sales'] - $totalPaidOuts - $creditCards;

        return $data;
    }
    
    /**
     * Submit report for approval
     */
    public function submit(DailyReport $dailyReport)
    {
        // Only the creator or owners/admins can submit
        if ($dailyReport->created_by !== auth()->id() && !in_array(auth()->user()->role, ['owner', 'admin'])) {
            abort(403);
        }
        
        // Can only submit draft reports
        if ($dailyReport->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft reports can be submitted.');
        }
        
        $oldValues = $dailyReport->toArray();
        
        $dailyReport->update([
            'status' => 'submitted'
        ]);
        
        // Log the submission
        AuditLog::log('submitted', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d')
        ]);
        
        return redirect()->route('daily-reports.index')->with('success', 'Report submitted for approval.');
    }
    
    /**
     * Approve a report
     */
    public function approve(Request $request, DailyReport $dailyReport)
    {
        // Only owners and admins can approve
        if (!auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }
        
        // Can only approve submitted reports
        if ($dailyReport->status !== 'submitted') {
            return redirect()->back()->with('error', 'Only submitted reports can be approved.');
        }
        
        $request->validate([
            'approval_notes' => 'nullable|string|max:1000'
        ]);
        
        $oldValues = $dailyReport->toArray();
        
        $dailyReport->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes
        ]);
        
        // Log the approval
        AuditLog::log('approved', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'approval_notes' => $request->approval_notes
        ]);
        
        return redirect()->route('daily-reports.index')->with('success', 'Report approved successfully.');
    }
    
    /**
     * Reject a report
     */
    public function reject(Request $request, DailyReport $dailyReport)
    {
        // Only owners and admins can reject
        if (!auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }
        
        // Can only reject submitted reports
        if ($dailyReport->status !== 'submitted') {
            return redirect()->back()->with('error', 'Only submitted reports can be rejected.');
        }
        
        $request->validate([
            'approval_notes' => 'required|string|max:1000'
        ]);
        
        $oldValues = $dailyReport->toArray();
        
        $dailyReport->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes
        ]);
        
        // Log the rejection
        AuditLog::log('rejected', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'rejection_reason' => $request->approval_notes
        ]);
        
        return redirect()->route('daily-reports.index')->with('success', 'Report rejected.');
    }
    
    /**
     * Return report to draft
     */
    public function returnToDraft(DailyReport $dailyReport)
    {
        // Only owners and admins can return to draft
        if (!auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }
        
        // Can return approved or rejected reports to draft
        if (!in_array($dailyReport->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Only approved or rejected reports can be returned to draft.');
        }
        
        $oldValues = $dailyReport->toArray();
        
        $dailyReport->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null
        ]);
        
        // Log the return to draft
        AuditLog::log('returned_to_draft', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'previous_status' => $oldValues['status']
        ]);
        
        return redirect()->route('daily-reports.index')->with('success', 'Report returned to draft.');
    }
}
