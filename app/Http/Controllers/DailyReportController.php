<?php

namespace App\Http\Controllers;

use App\Exceptions\Business\PermissionException;
use App\Exceptions\Business\ReportException;
use App\Exceptions\Business\StoreException;
use App\Http\Middleware\CheckDailyReportAccess;
use App\Models\AuditLog;
use App\Models\DailyReport;
use App\Models\DailyReportRevenue;
use App\Models\DailyReportTransaction;
use App\Models\RevenueIncomeType;
use App\Models\Store;
use App\Models\TransactionType;
use App\Models\Vendor;
use App\Services\DailyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DailyReportController extends Controller
{
    protected DailyReportService $reportService;

    public function __construct(DailyReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth');
        $this->middleware('permission:view_daily_reports')->only(['index', 'show']);
        $this->middleware('permission:create_reports')->only(['create', 'store']);
        $this->middleware('permission:manage_reports')->only(['edit', 'update', 'destroy', 'approve']);
        $this->middleware(CheckDailyReportAccess::class)->except(['index', 'create']);
    }

    /**
     * Display a listing of the resource.
     * Hierarchical selection: Year -> Month -> Reports
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get selected year and month from request
        $selectedYear = $request->get('year');
        $selectedMonth = $request->get('month');
        
        // Generate years list (2000 to current year)
        $currentYear = (int) date('Y');
        $years = range(2000, $currentYear);
        rsort($years); // Most recent first
        
        // Months list
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        // Initialize reports collection
        $reports = collect();
        
        // If year and month are selected, fetch reports
        if ($selectedYear && $selectedMonth) {
            $query = DailyReport::with(['store', 'creator', 'approver', 'transactions', 'revenues.revenueIncomeType'])
                ->withSum('transactions', 'amount')
                ->withSum('revenues', 'amount');

            // Filter reports based on user accessible stores
            if (! $user->isAdmin()) {
                $accessibleStoreIds = $user->accessibleStores()->pluck('id');
                $query->whereIn('store_id', $accessibleStoreIds);
            }

            // Filter by year and month
            $query->whereYear('report_date', $selectedYear)
                  ->whereMonth('report_date', $selectedMonth);

            // Store filter
            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            // Sorting
            $query->orderBy('report_date', 'desc');

            $reports = $query->paginate(50)->appends($request->query());
        }
        
        // Get available stores for filter dropdown (based on user role)
        $storesQuery = Store::query();
        if ($user->hasPermission('view_daily_reports')) {
            if ($user->role === \App\Enums\UserRole::OWNER) {
                $storesQuery->where('created_by', $user->id);
            } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
                $storesQuery->where('id', $user->store_id);
            }
        }
        $stores = $storesQuery->get();

        return view('daily-reports.index', compact('reports', 'stores', 'years', 'months', 'selectedYear', 'selectedMonth'));
    }

    /**
     * Step 1: Show store selection for creating a new daily report
     */
    public function selectStore()
    {
        $user = auth()->user();

        try {
            // Use service to get accessible stores with proper validation
            $stores = $this->reportService->getUserAccessibleStores($user);

            // Business logic fix: Prevent unassigned managers from accessing create form
            if ($user->isManager() && $stores->isEmpty()) {
                return redirect()->route('daily-reports.index')
                    ->with('warning', 'You have not been assigned to any stores. Please contact an administrator to assign stores to your account.');
            }

            return view('daily-reports.select-store', compact('stores'));

        } catch (PermissionException $e) {
            return redirect()->route('daily-reports.index')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error loading store selection for daily report', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('daily-reports.index')
                ->with('error', 'Unable to load store selection. Please try again.');
        }
    }

    /**
     * Step 2: Show date selection after store is selected
     */
    public function selectDate(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        $user = auth()->user();
        $storeId = $request->get('store_id');

        // Verify user has access to this store
        if (! $user->hasStoreAccess($storeId)) {
            return redirect()->route('daily-reports.create')
                ->with('error', 'You do not have access to the selected store.');
        }

        $store = Store::find($storeId);

        // Get existing report dates for this store to prevent duplicates
        $existingDates = DailyReport::where('store_id', $storeId)
            ->pluck('report_date')
            ->map(function ($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        return view('daily-reports.select-date', compact('store', 'existingDates'));
    }

    /**
     * Step 3: Show the actual report creation form with pre-selected store and date
     */
    public function createForm(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'report_date' => 'required|date',
        ]);

        $user = auth()->user();
        $storeId = $request->get('store_id');
        $reportDate = $request->get('report_date');

        // Verify user has access to this store
        if (! $user->hasStoreAccess($storeId)) {
            return redirect()->route('daily-reports.create')
                ->with('error', 'You do not have access to the selected store.');
        }

        // Check if report already exists for this store and date
        $existingReport = DailyReport::where('store_id', $storeId)
            ->whereDate('report_date', $reportDate)
            ->first();

        if ($existingReport) {
            return redirect()->route('daily-reports.select-date', ['store_id' => $storeId])
                ->with('error', 'A daily report for this store already exists for the selected date. Please choose a different date.');
        }

        $store = Store::find($storeId);
        $types = TransactionType::all();
        $revenueTypes = RevenueIncomeType::where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        
        // Get vendors for the company dropdown
        $vendors = Vendor::active()
            ->with(['defaultTransactionType', 'defaultCoa'])
            ->orderBy('vendor_name')
            ->get();

        return view('daily-reports.create', compact('store', 'types', 'revenueTypes', 'reportDate', 'vendors'));
    }

    /**
     * Legacy method for backward compatibility (if needed)
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('daily-reports.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        try {
            // Validate request data
            $validatedData = $request->validate([
                'report_date' => 'required|date',
                'page_number' => 'nullable|integer|min:1',
                'weather' => 'nullable|string|max:50',
                'holiday_event' => 'nullable|string|max:100',

                // decimals
                'projected_sales' => 'required|numeric',
                'gross_sales' => 'required|numeric',
                'amount_of_cancels' => 'nullable|numeric',
                'amount_of_voids' => 'nullable|numeric',
                'coupons_received' => 'nullable|numeric',
                'adjustments_overrings' => 'nullable|numeric',
                'net_sales' => 'nullable|numeric',
                'tax' => 'nullable|numeric',
                'average_ticket' => 'nullable|numeric',
                'sales' => 'nullable|numeric',
                'total_paid_outs' => 'required|numeric',
                'credit_cards' => 'nullable|numeric',
                'cash_to_account' => 'nullable|numeric',
                'actual_deposit' => 'nullable|numeric',
                'short' => 'nullable|numeric',
                'over' => 'nullable|numeric',

                // integers
                'number_of_no_sales' => 'nullable|integer',
                'total_coupons' => 'nullable|integer',
                'total_customers' => 'nullable',

                // relationships
                'store_id' => 'required|exists:stores,id',

                // approval workflow
                'approval_notes' => 'nullable|string|max:1000',

                // revenue entries
                'revenues' => 'nullable|array',
                'revenues.*.revenue_income_type_id' => 'nullable|exists:revenue_income_types,id',
                'revenues.*.amount' => 'nullable|numeric|min:0',
                'revenues.*.notes' => 'nullable|string|max:500',
            ]);

            // Use service for comprehensive validation and business rule checking
            $this->reportService->validateReportData($validatedData);

            // Validate revenue entries
            $this->validateRevenueEntries($request);

            // Calculate derived fields
            $validatedData = $this->calculateDerivedFields($validatedData);

            // Use service to create report with proper validation and audit logging
            $dailyReport = $this->reportService->createDailyReport($user, $validatedData);

            // Process transactions if provided
            if ($request->has('transactions')) {
                $this->processTransactions($request, $dailyReport);
            }

            // Process revenue entries if provided
            if ($request->has('revenues')) {
                $this->processRevenueEntries($request, $dailyReport);
            }

            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', '✅ Daily report created successfully! All calculations have been verified.');

        } catch (ReportException|StoreException|PermissionException $e) {
            Log::warning('Business rule validation failed for daily report creation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'store_id' => $request->input('store_id'),
                'report_date' => $request->input('report_date'),
            ]);

            return back()->withInput()->withErrors([
                'business_error' => $e->getMessage(),
            ]);

        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());

        } catch (\Exception $e) {
            Log::error('Failed to create daily report', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'store_id' => $request->input('store_id'),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors([
                'error' => 'An unexpected error occurred while creating the daily report. Please try again.',
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
                $query->where('store_id', $user->store_id);
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
            'report_date' => 'required|date',
            'page_number' => 'nullable|integer|min:1',
            'weather' => 'nullable|string|max:50',
            'holiday_event' => 'nullable|string|max:100',

            // decimals
            'projected_sales' => 'required|numeric',
            'gross_sales' => 'required|numeric',
            'amount_of_cancels' => 'nullable|numeric',
            'amount_of_voids' => 'nullable|numeric',
            'coupons_received' => 'nullable|numeric',
            'adjustments_overrings' => 'nullable|numeric',
            'net_sales' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'average_ticket' => 'nullable|numeric',
            'sales' => 'nullable|numeric',
            'total_paid_outs' => 'required|numeric',
            'credit_cards' => 'nullable|numeric',
            'cash_to_account' => 'nullable|numeric',
            'actual_deposit' => 'nullable|numeric',
            'short' => 'nullable|numeric',
            'over' => 'nullable|numeric',

            // integers
            'number_of_no_sales' => 'nullable|integer',
            'total_coupons' => 'nullable|integer',
            'total_customers' => 'nullable|integer',

            // relationships
            'store_id' => 'required|exists:stores,id',

            // transactions
            'transactions' => 'nullable|array',
            'transactions.*.transaction_id' => 'nullable|integer',
            'transactions.*.company' => 'nullable|string|max:100',
            'transactions.*.vendor_id' => 'nullable|exists:vendors,id',
            'transactions.*.transaction_type' => 'nullable|exists:transaction_types,id',
            'transactions.*.amount' => 'required|numeric|min:0',

            // revenue entries
            'revenues' => 'nullable|array',
            'revenues.*.revenue_income_type_id' => 'nullable|exists:revenue_income_types,id',
            'revenues.*.amount' => 'nullable|numeric|min:0',
            'revenues.*.notes' => 'nullable|string|max:500',
        ]);

        // Validate manager can only update reports for assigned stores
        $user = auth()->user();
        if ($user->role === \App\Enums\UserRole::MANAGER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->where('id', $user->store_id)
                ->first();

            if (! $allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to update reports for this store.'],
                ]);
            }
        } elseif ($user->role === \App\Enums\UserRole::OWNER) {
            $allowedStore = Store::where('id', $validatedData['store_id'])
                ->where('created_by', $user->id)
                ->first();

            if (! $allowedStore) {
                throw ValidationException::withMessages([
                    'store_id' => ['You are not authorized to update reports for this store.'],
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
                'report_date' => ['A daily report for this store already exists for the selected date. Please choose a different date.'],
            ]);
        }

        try {
            DB::beginTransaction();

            // Store old values for audit log
            $oldValues = $dailyReport->toArray();

            // Validate revenue entries if provided
            if ($request->has('revenues')) {
                $this->validateRevenueEntries($request);
            }

            // Calculate derived fields
            $validatedData = $this->calculateDerivedFields($validatedData);

            $validatedData['created_by'] = auth()->id();
            $dailyReport->update($validatedData);

            // Log the update
            AuditLog::log('updated', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
                'store_name' => $dailyReport->store->store_info ?? 'Unknown',
                'report_date' => $dailyReport->report_date->format('Y-m-d'),
            ]);

            // Delete existing transactions and revenues
            $dailyReport->transactions()->delete();
            $dailyReport->revenues()->delete();

            // Process transactions if provided
            if ($request->has('transactions')) {
                $this->processTransactions($request, $dailyReport);
            }

            // Process revenue entries if provided
            if ($request->has('revenues')) {
                $this->processRevenueEntries($request, $dailyReport);
            }

            DB::commit();

            return redirect()->route('daily-reports.show', $dailyReport)
                ->with('success', 'Daily report updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            throw ValidationException::withMessages([
                'error' => ['Failed to update daily report. Please try again.'],
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
                'report_date' => $dailyReport->report_date->format('Y-m-d'),
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
        if ($user->role === \App\Enums\UserRole::OWNER) {
            $query->where('created_by', $user->id);
        } elseif ($user->role === \App\Enums\UserRole::MANAGER) {
            $query->where('store_id', $user->store_id);
        }
        // Admins can access all stores

        $store = $query->find($id);

        if (! $store) {
            abort(403, 'You are not authorized to access this store.');
        }

        $types = TransactionType::all();
        $revenueTypes = RevenueIncomeType::where('is_active', 1)->orderBy('sort_order')->orderBy('name')->get();

        return view('daily-reports.form', compact('store', 'types', 'revenueTypes'));
    }

    /**
     * Export all daily reports as CSV
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        $query = DailyReport::with(['store', 'creator']);

        // Filter reports based on user accessible stores
        if (! $user->isAdmin()) {
            $accessibleStoreIds = $user->accessibleStores()->pluck('stores.id');
            $query->whereIn('store_id', $accessibleStoreIds);
        }

        // Apply same filters as index method
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

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $reports = $query->orderBy('report_date', 'desc')->get();

        $filename = 'daily_reports_'.date('Y-m-d_H-i-s').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($reports) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Date',
                'Store',
                'Status',
                'Gross Sales',
                'Net Sales',
                'Projected Sales',
                'Total Customers',
                'Average Ticket',
                'Credit Cards',
                'Actual Deposit',
                'Created By',
                'Created At',
            ]);

            // Add data rows
            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->report_date->format('Y-m-d'),
                    $report->store->store_info ?? 'N/A',
                    ucfirst($report->status),
                    number_format($report->gross_sales, 2),
                    number_format($report->net_sales, 2),
                    number_format($report->projected_sales, 2),
                    $report->total_customers,
                    number_format($report->average_ticket, 2),
                    number_format($report->credit_cards, 2),
                    number_format($report->actual_deposit, 2),
                    $report->creator->name ?? 'N/A',
                    $report->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export daily report as PDF
     */
    public function exportPdf(DailyReport $dailyReport)
    {
        $dailyReport->load(['store', 'creator', 'transactions.transactionType', 'revenues.revenueIncomeType']);

        $html = view('daily-reports.pdf', compact('dailyReport'))->render();

        $dompdf = new \Dompdf\Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $storeName = $dailyReport->store?->store_info ?? 'unknown';
        $filename = 'daily-report-'.$storeName.'-'.$dailyReport->report_date->format('Y-m-d').'.pdf';

        return $dompdf->stream($filename);
    }

    /**
     * Calculate derived fields for the daily report.
     */
    private function calculateDerivedFields(array $data): array
    {
        $creditCards = (float) ($data['credit_cards'] ?? 0);
        $totalPaidOuts = (float) ($data['total_paid_outs'] ?? 0);
        $actualDeposit = (float) ($data['actual_deposit'] ?? 0);

        // Calculate net sales as sum of revenues
        $netSales = 0;
        if (isset($data['revenues']) && is_array($data['revenues'])) {
            foreach ($data['revenues'] as $revenue) {
                if (isset($revenue['amount']) && is_numeric($revenue['amount'])) {
                    $netSales += (float) $revenue['amount'];
                }
            }
        }
        $data['net_sales'] = $netSales;

        // Calculate tax (8.25% sales tax)
        // If net sales includes tax: tax = netSales * 0.0825 / 1.0825
        $tax = $netSales * 0.0825 / 1.0825;
        $data['tax'] = $tax;

        // Calculate sales (pre-tax)
        $data['sales'] = $netSales - $tax;

        // Calculate cash to account for = Net Sales - Transaction Expenses - Online Platform Revenue - Credit Cards
        // Formula: Net Sales - transaction expenses - online platforms - credit card
        // Calculate online platform revenue
        $onlinePlatformRevenue = 0;
        if (isset($data['revenues']) && is_array($data['revenues'])) {
            foreach ($data['revenues'] as $revenue) {
                if (isset($revenue['amount']) && is_numeric($revenue['amount']) && isset($revenue['revenue_income_type_id'])) {
                    $revenueType = \App\Models\RevenueIncomeType::find($revenue['revenue_income_type_id']);
                    if ($revenueType && $revenueType->category === 'online') {
                        $onlinePlatformRevenue += (float) $revenue['amount'];
                    }
                }
            }
        }
        
        // Calculate: Net Sales - Transaction Expenses - Online Platform Revenue - Credit Cards
        $cashToAccountFor = $netSales - $totalPaidOuts - $onlinePlatformRevenue - $creditCards;
        
        // Ensure result is not negative (numbers cannot go negative)
        $cashToAccountFor = max(0, round($cashToAccountFor, 2));
        
        $data['cash_to_account'] = $cashToAccountFor;

        // Calculate short/over
        if ($actualDeposit < $cashToAccountFor) {
            $data['short'] = $actualDeposit - $cashToAccountFor;
            $data['over'] = 0;
        } else {
            $data['short'] = 0;
            $data['over'] = $actualDeposit - $cashToAccountFor;
        }

        // Calculate average ticket if total_customers is provided
        $totalCustomers = (int) ($data['total_customers'] ?? 0);
        if ($totalCustomers > 0) {
            $data['average_ticket'] = $netSales / $totalCustomers;
        } else {
            $data['average_ticket'] = 0;
        }

        return $data;
    }

    /**
     * Submit report for approval
     */
    public function submit(DailyReport $dailyReport)
    {
        // Only the creator or owners/admins can submit
        if (
            $dailyReport->created_by !== auth()->id()
            && ! in_array(
                auth()->user()->role,
                [\App\Enums\UserRole::OWNER, \App\Enums\UserRole::ADMIN]
            )
        ) {
            abort(403);
        }

        // Can only submit draft reports
        if ($dailyReport->status !== 'draft') {
            return redirect()->back()->with('error', 'Only draft reports can be submitted.');
        }

        $oldValues = $dailyReport->toArray();

        $dailyReport->update([
            'status' => 'submitted',
        ]);

        // Log the submission
        AuditLog::log('submitted', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Report submitted for approval.');
    }

    /**
     * Approve a report
     */
    public function approve(Request $request, DailyReport $dailyReport)
    {
        // Only owners and admins can approve
        if (! auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }

        // Can only approve submitted reports
        if ($dailyReport->status !== 'submitted') {
            return redirect()->back()->with('error', 'Only submitted reports can be approved.');
        }

        $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $oldValues = $dailyReport->toArray();

        $dailyReport->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Log the approval
        AuditLog::log('approved', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'approval_notes' => $request->approval_notes,
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Report approved successfully.');
    }

    /**
     * Reject a report
     */
    public function reject(Request $request, DailyReport $dailyReport)
    {
        // Only owners and admins can reject
        if (! auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }

        // Can only reject submitted reports
        if ($dailyReport->status !== 'submitted') {
            return redirect()->back()->with('error', 'Only submitted reports can be rejected.');
        }

        $request->validate([
            'approval_notes' => 'required|string|max:1000',
        ]);

        $oldValues = $dailyReport->toArray();

        $dailyReport->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->approval_notes,
        ]);

        // Log the rejection
        AuditLog::log('rejected', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'rejection_reason' => $request->approval_notes,
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Report rejected.');
    }

    /**
     * Return report to draft
     */
    public function returnToDraft(DailyReport $dailyReport)
    {
        // Only owners and admins can return to draft
        if (! auth()->user()->hasPermission('approve_reports')) {
            abort(403);
        }

        // Can return approved or rejected reports to draft
        if (! in_array($dailyReport->status, ['approved', 'rejected'])) {
            return redirect()->back()->with('error', 'Only approved or rejected reports can be returned to draft.');
        }

        $oldValues = $dailyReport->toArray();

        $dailyReport->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);

        // Log the return to draft
        AuditLog::log('returned_to_draft', $dailyReport, $oldValues, $dailyReport->fresh()->toArray(), [
            'store_name' => $dailyReport->store->store_info ?? 'Unknown',
            'report_date' => $dailyReport->report_date->format('Y-m-d'),
            'previous_status' => $oldValues['status'],
        ]);

        return redirect()->route('daily-reports.index')->with('success', 'Report returned to draft.');
    }

    /**
     * Validate revenue entries - if amount is provided, revenue_income_type_id is required
     */
    private function validateRevenueEntries(Request $request): void
    {
        if ($request->has('revenues')) {
            foreach ($request->revenues as $index => $revenueData) {
                $amount = $revenueData['amount'] ?? null;
                $typeId = $revenueData['revenue_income_type_id'] ?? null;

                // If amount is provided but no type ID, throw validation error
                if (! empty($amount) && $amount > 0 && empty($typeId)) {
                    throw ValidationException::withMessages([
                        "revenues.{$index}.revenue_income_type_id" => ['The revenue income type is required when amount is provided.'],
                    ]);
                }
            }
        }
    }

    /**
     * Process transaction entries for the daily report
     */
    private function processTransactions(Request $request, DailyReport $dailyReport): void
    {
        foreach ($request->transactions as $transactionData) {
            if (empty($transactionData['amount'])) {
                continue; // Skip empty transaction rows
            }

            // Get company name from vendor if vendor_id is provided
            $companyName = $transactionData['company'] ?? '';
            if (!empty($transactionData['vendor_id'])) {
                $vendor = \App\Models\Vendor::find($transactionData['vendor_id']);
                if ($vendor) {
                    $companyName = $vendor->vendor_name;
                }
            }

            // Validate transaction_type_id
            $transactionTypeId = $transactionData['transaction_type'] ?? null;
            if ($transactionTypeId && ! TransactionType::find($transactionTypeId)) {
                throw ValidationException::withMessages([
                    'transactions' => "Invalid transaction type ID: {$transactionTypeId}",
                ]);
            }

            DailyReportTransaction::create([
                'daily_report_id' => $dailyReport->id,
                'transaction_type_id' => $transactionTypeId,
                'company' => $companyName,
                'amount' => (float) $transactionData['amount'],
            ]);
        }
    }

    /**
     * Process revenue entries for the daily report
     */
    private function processRevenueEntries(Request $request, DailyReport $dailyReport): void
    {
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
}
