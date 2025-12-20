<?php

use App\Http\Controllers\Admin\BankAccountViewController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\ExpenseViewController;
use App\Http\Controllers\Admin\MerchantFeeViewController;
use App\Http\Controllers\Admin\ProfitLossViewController;
use App\Http\Controllers\Admin\ReviewQueueViewController;
use App\Http\Controllers\Admin\VendorViewController;
use App\Http\Controllers\Api\BankAccountController;
use App\Http\Controllers\Api\BankImportController;
use App\Http\Controllers\Api\BankReconciliationController;
use App\Http\Controllers\Api\ChartOfAccountController as ApiChartOfAccountController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MerchantFeeController;
use App\Http\Controllers\Api\ProfitLossController;
use App\Http\Controllers\Api\ThirdPartyImportController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RevenueIncomeTypeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransactionTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('index');

Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Dashboard Analytics Routes
Route::middleware('auth')->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportData'])->name('dashboard.export');
});

// Google OAuth Routes
Route::get('google-signin', [GoogleController::class, 'redirectToGoogle'])->name('google.signin');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');

    // Owner management - Admin only with date conversion
    Route::middleware(['role:admin', 'convert_date_format'])->group(function () {
        Route::get('/owners', [OwnerController::class, 'index'])->name('owners.index');
        Route::get('owners/create', [OwnerController::class, 'create'])->name('owners.create');
        Route::post('owners/create', [OwnerController::class, 'create'])->name('owners.store');
        Route::get('owners/{owner}', [OwnerController::class, 'show'])->name('owners.show');
        Route::get('owners/{owner}/edit', [OwnerController::class, 'edit'])->name('owners.edit');
        Route::put('owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
        Route::delete('owners/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');
        Route::get('owners/{owner}/assign-stores', [OwnerController::class, 'assignStoresForm'])->name('owners.assign-stores.form');
        Route::post('owners/{owner}/assign-stores', [OwnerController::class, 'assignStores'])->name('owners.assign-stores');
    });

    // Store management - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}', [StoreController::class, 'show'])->name('stores.show');
        Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
        Route::get('/stores/{store}/assign-owner', [StoreController::class, 'assignOwnerForm'])->name('stores.assign-owner.form');
        Route::post('/stores/{store}/assign-owner', [StoreController::class, 'assignOwner'])->name('stores.assign-owner');
    });

    // Daily Reports Routes - with access control and date conversion
    Route::middleware(['daily_report_access', 'convert_date_format'])->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');

        // Multi-step creation process
        Route::get('/daily-reports/create', [DailyReportController::class, 'selectStore'])->name('daily-reports.create');
        Route::get('/daily-reports/create/step-2', [DailyReportController::class, 'selectDate'])->name('daily-reports.select-date');
        Route::get('/daily-reports/create/form', [DailyReportController::class, 'createForm'])->name('daily-reports.create-form');

        Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
        Route::get('/daily-reports/{dailyReport}', [DailyReportController::class, 'show'])->name('daily-reports.show');
        Route::get('/daily-reports/{dailyReport}/edit', [DailyReportController::class, 'edit'])->name('daily-reports.edit');
        Route::put('/daily-reports/{dailyReport}', [DailyReportController::class, 'update'])->name('daily-reports.update');
        Route::delete('/daily-reports/{dailyReport}', [DailyReportController::class, 'destroy'])->name('daily-reports.destroy');
        Route::get('stores/{store}/daily-reports', [DailyReportController::class, 'reports'])->name('stores.daily-reports.index');

        // Export routes
        Route::get('/daily-reports/{dailyReport}/export-pdf', [DailyReportController::class, 'exportPdf'])->name('daily-reports.export-pdf');
        Route::get('/daily-reports/export-csv', [DailyReportController::class, 'exportCsv'])->name('daily-reports.export-csv');

        // Approval workflow routes
        Route::post('/daily-reports/{dailyReport}/submit', [DailyReportController::class, 'submit'])->name('daily-reports.submit');
        Route::post('/daily-reports/{dailyReport}/approve', [DailyReportController::class, 'approve'])->name('daily-reports.approve');
        Route::post('/daily-reports/{dailyReport}/reject', [DailyReportController::class, 'reject'])->name('daily-reports.reject');
        Route::post('/daily-reports/{dailyReport}/return-to-draft', [DailyReportController::class, 'returnToDraft'])->name('daily-reports.return-to-draft');
    });

    // Manager management - Admin and Owner access
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/managers', [ManagerController::class, 'index'])->name('managers.index');
        Route::get('/managers/create', [ManagerController::class, 'create'])->name('managers.create');
        Route::post('/managers', [ManagerController::class, 'store'])->name('managers.store');
        Route::get('/managers/{manager}', [ManagerController::class, 'show'])->name('managers.show');
        Route::get('/managers/{manager}/edit', [ManagerController::class, 'edit'])->name('managers.edit');
        Route::put('/managers/{manager}', [ManagerController::class, 'update'])->name('managers.update');
        Route::delete('/managers/{manager}', [ManagerController::class, 'destroy'])->name('managers.destroy');
        Route::get('managers/{manager}/assign-stores', [ManagerController::class, 'assignStoresForm'])->name('managers.assign-stores.form');
        Route::post('managers/{manager}/assign-stores', [ManagerController::class, 'assignStores'])->name('managers.assign-stores');
    });

    // Transaction Types - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/transaction-types', [TransactionTypeController::class, 'index'])->name('transaction-types.index');
        Route::get('/transaction-types/create', [TransactionTypeController::class, 'create'])->name('transaction-types.create');
        Route::post('/transaction-types', [TransactionTypeController::class, 'store'])->name('transaction-types.store');
        Route::get('/transaction-types/{transactionType}', [TransactionTypeController::class, 'show'])->name('transaction-types.show');
        Route::get('/transaction-types/{transactionType}/edit', [TransactionTypeController::class, 'edit'])->name('transaction-types.edit');
        Route::put('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
        Route::delete('/transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
        Route::post('transaction-types/{transactionType}/assign-stores', [TransactionTypeController::class, 'assignStores'])->name('transaction-types.assign.stores');
    });

    // Chart of Accounts - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::resource('chart-of-accounts', ChartOfAccountController::class)
            ->parameters(['chart-of-accounts' => 'chartOfAccount'])
            ->names('coa');
        // API endpoint for stores (for COA form and other admin tools)
        Route::get('/api/stores', function () {
            return response()->json(App\Models\Store::select('id', 'store_info as name')->get());
        })->name('api.stores');
    });

    // Vendors - Admin and Owner can manage
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/vendors', [VendorViewController::class, 'index'])->name('admin.vendors.index');
    });

    // Expenses - Admin, Owner, Manager can view
    Route::middleware('role:admin,owner,manager')->group(function () {
        Route::get('/expenses', [ExpenseViewController::class, 'index'])->name('admin.expenses.index');
        Route::get('/expenses/review', [ReviewQueueViewController::class, 'index'])->name('admin.expenses.review');
    });
    
    // Merchant Fees - Admin, Owner
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/merchant-fees', [MerchantFeeViewController::class, 'index'])->name('admin.merchant-fees.index');
        Route::get('/merchant-fees/third-party', [MerchantFeeViewController::class, 'thirdParty'])->name('admin.merchant-fees.third-party');
    });

    // P&L Reports - Admin, Owner (full access), Manager (view only)
    Route::middleware('role:admin,owner,manager')->group(function () {
        // Managers can view but not export
        Route::get('/reports/profit-loss', [ProfitLossViewController::class, 'index'])->name('admin.reports.profit-loss.index');
        Route::get('/reports/profit-loss/drill-down', [ProfitLossViewController::class, 'drillDown'])->name('admin.reports.profit-loss.drill-down');
    });
    
    // P&L Export and Advanced Features - Admin and Owner only
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/reports/profit-loss/comparison', [ProfitLossViewController::class, 'comparison'])->name('admin.reports.profit-loss.comparison');
        Route::get('/reports/profit-loss/snapshots', [ProfitLossViewController::class, 'snapshots'])->name('admin.reports.profit-loss.snapshots');
        Route::get('/reports/profit-loss/export/csv', [ProfitLossViewController::class, 'exportCsv'])->name('admin.reports.profit-loss.export.csv');
        Route::get('/reports/profit-loss/export/pdf', [ProfitLossViewController::class, 'exportPdf'])->name('admin.reports.profit-loss.export.pdf');
    });

    // Bank Accounts - Admin, Owner
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/bank-accounts', [BankAccountViewController::class, 'index'])->name('admin.bank.accounts.index');
        Route::get('/bank-accounts/{id}', [BankAccountViewController::class, 'show'])->name('admin.bank.accounts.show');
        Route::get('/bank-accounts/{accountId}/reconciliation', [BankAccountViewController::class, 'reconciliation'])->name('admin.bank.reconciliation.index');
    });

    // Revenue Income Types Routes - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/revenue-income-types', [RevenueIncomeTypeController::class, 'index'])->name('revenue-income-types.index');
        Route::get('/revenue-income-types/create', [RevenueIncomeTypeController::class, 'create'])->name('revenue-income-types.create');
        Route::post('/revenue-income-types', [RevenueIncomeTypeController::class, 'store'])->name('revenue-income-types.store');
        Route::get('/revenue-income-types/{revenueIncomeType}', [RevenueIncomeTypeController::class, 'show'])->name('revenue-income-types.show');
        Route::get('/revenue-income-types/{revenueIncomeType}/edit', [RevenueIncomeTypeController::class, 'edit'])->name('revenue-income-types.edit');
        Route::put('/revenue-income-types/{revenueIncomeType}', [RevenueIncomeTypeController::class, 'update'])->name('revenue-income-types.update');
        Route::delete('/revenue-income-types/{revenueIncomeType}', [RevenueIncomeTypeController::class, 'destroy'])->name('revenue-income-types.destroy');

        Route::get('/api/users-for-impersonation', [DashboardController::class, 'getUsersForImpersonation'])->name('api.users-for-impersonation');
    });

    // Impersonation Routes - Admin only (controller handles role checking)
    // Note: These routes must be accessible even during impersonation
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    Route::match(['get', 'post'], '/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::get('/debug-user', [ImpersonationController::class, 'debug'])->name('debug.user');

    // Audit Log Routes (Admin and Owner only) with date conversion
    Route::middleware(['admin_or_owner', 'convert_date_format'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    // Reports Routes
    Route::middleware('auth')->group(function () {
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    });

    // API Routes - JSON endpoints for AJAX calls (using web session auth)
    Route::prefix('api')->group(function () {
        // Chart of Accounts API routes are defined in routes/api.php to avoid duplication
        
        // Vendor API
        // Vendors - Admin and Owner can manage
        Route::middleware('role:admin,owner')->group(function () {
            Route::get('vendors/match', [VendorController::class, 'match']);
            Route::apiResource('vendors', VendorController::class);
        });
        
        // Vendor aliases - Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('vendors/{id}/aliases', [VendorController::class, 'addAlias']);
        });
        
        // Expense API
        Route::post('expenses/sync-cash-expenses', [ExpenseController::class, 'syncCashExpenses'])->middleware('role:admin');
        Route::get('expenses', [ExpenseController::class, 'index']);
        Route::post('expenses', [ExpenseController::class, 'store'])->middleware('role:admin,owner,manager');
        Route::get('expenses/{id}', [ExpenseController::class, 'show']);
        Route::put('expenses/{id}', [ExpenseController::class, 'update'])->middleware('role:admin,owner');
        
        // Review Queue API
        Route::get('expenses/review-queue', [ExpenseController::class, 'reviewQueue'])->middleware('role:admin,owner');
        Route::get('expenses/review-stats', [ExpenseController::class, 'reviewStats']);
        Route::post('expenses/{id}/resolve', [ExpenseController::class, 'resolve'])->middleware('role:admin,owner');
        Route::post('expenses/bulk-resolve', [ExpenseController::class, 'bulkResolve'])->middleware('role:admin,owner');
        
        // Bank Account API
        Route::middleware('role:admin,owner')->group(function () {
            Route::get('bank-accounts', [BankAccountController::class, 'index']);
            Route::post('bank-accounts', [BankAccountController::class, 'store']);
            Route::get('bank-accounts/{id}', [BankAccountController::class, 'show']);
            Route::put('bank-accounts/{id}', [BankAccountController::class, 'update']);
        });
        
        // Bank Import API
        Route::middleware('role:admin,owner')->group(function () {
            Route::post('bank/import/preview', [BankImportController::class, 'preview']);
            Route::post('bank/import/upload', [BankImportController::class, 'import']);
            Route::get('bank/import/history', [BankImportController::class, 'history']);
        });
        
        // Bank Reconciliation API
        Route::middleware('role:admin,owner')->group(function () {
            Route::get('bank/reconciliation', [BankReconciliationController::class, 'index']);
            Route::get('bank/reconciliation/{id}/matches', [BankReconciliationController::class, 'getMatches']);
            Route::post('bank/reconciliation/{id}/match', [BankReconciliationController::class, 'matchTransaction']);
            Route::post('bank/reconciliation/{id}/mark-reviewed', [BankReconciliationController::class, 'markReviewed']);
        });
        
        // Third-Party Platform Import API
        Route::middleware('role:admin,owner')->group(function () {
            Route::post('third-party/import', [ThirdPartyImportController::class, 'import']);
            Route::get('third-party/statements', [ThirdPartyImportController::class, 'history']);
            Route::get('third-party/statements/{id}', [ThirdPartyImportController::class, 'show']);
        });
        
        // Merchant Fee Analytics API
        Route::middleware('role:admin,owner')->group(function () {
            Route::get('merchant-fees/summary', [MerchantFeeController::class, 'summary']);
            Route::get('merchant-fees/by-processor', [MerchantFeeController::class, 'byProcessor']);
            Route::get('merchant-fees/trends', [MerchantFeeController::class, 'trends']);
            Route::get('merchant-fees/third-party-breakdown', [MerchantFeeController::class, 'thirdPartyBreakdown']);
            Route::get('merchant-fees/transactions', [MerchantFeeController::class, 'transactions']);
        });
        
        // P&L Report API
        Route::get('reports/pl/summary', [ProfitLossController::class, 'summary'])->middleware('auth');
        Route::middleware('role:admin,owner')->group(function () {
            Route::get('reports/pl', [ProfitLossController::class, 'index']);
            Route::post('reports/pl/snapshot', [ProfitLossController::class, 'snapshot']);
            Route::get('reports/pl/snapshots', [ProfitLossController::class, 'snapshots']);
            Route::get('reports/pl/drill-down', [ProfitLossController::class, 'drillDown']);
            Route::get('reports/pl/consolidated', [ProfitLossController::class, 'consolidated']);
            Route::get('reports/pl/store-comparison', [ProfitLossController::class, 'storeComparison']);
        });
    });
});
