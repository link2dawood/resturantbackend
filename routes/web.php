<?php

use App\Http\Controllers\Admin\BankAccountViewController;
use App\Http\Controllers\Admin\BankStatementImportController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\ExpenseViewController;
use App\Http\Controllers\Admin\ImportLogController;
use App\Http\Controllers\Admin\MerchantFeeViewController;
use App\Http\Controllers\Admin\OwnerCcStatementImportController;
use App\Http\Controllers\Admin\ProfitLossViewController;
use App\Http\Controllers\Admin\ReviewQueueViewController;
use App\Http\Controllers\Admin\VendorViewController;
use App\Http\Controllers\Api\BankAccountController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BankImportController;
use App\Http\Controllers\Api\BankReconciliationController;
use App\Http\Controllers\Api\ChartOfAccountController as ApiChartOfAccountController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\MerchantFeeController;
use App\Http\Controllers\Api\ProfitLossController;
use App\Http\Controllers\Api\ThirdPartyImportController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Admin\SubscriptionAdminController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RevenueIncomeTypeController;
use App\Http\Controllers\SalesProjectionController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\TrialController;
use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('index');

Auth::routes(['verify' => true]);

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Trial lifecycle routes — auth + verified, but intentionally NOT behind the
// 'trial' gate so an expired user can still reach the "Trial Expired" screen
// and submit a request to continue.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trial/expired', [TrialController::class, 'expired'])->name('trial.expired');
    Route::post('/trial/request-continue', [TrialController::class, 'requestContinue'])->name('trial.request-continue');

    // Billing — reachable during the trial AND after expiry (so an expired owner
    // can pay to unlock), hence NOT behind the 'trial' gate.
    Route::get('/billing', [BillingController::class, 'show'])->name('billing.show');
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe'])->name('billing.subscribe');
    Route::get('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
});

// Dashboard Analytics Routes
Route::middleware(['auth', 'verified', 'trial'])->group(function () {
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // Task 14 — in-app notification bell. Every route reads only the signed-in
    // user's own notifications, so there is nothing to scope beyond auth.
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');
    Route::get('/dashboard/export', [DashboardController::class, 'exportData'])->name('dashboard.export');
});

// Google OAuth Routes
Route::get('google-signin', [GoogleController::class, 'redirectToGoogle'])->name('google.signin');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// Profile Routes
// NOTE: 'verified' is enforced app-wide here — unverified users are bounced to the
// email-verification notice. The verification routes themselves come from
// Auth::routes(['verify' => true]) above and are intentionally NOT inside this group.
// 'trial' locks out expired-trial workspaces (redirects to the Trial Expired screen).
Route::middleware(['auth', 'verified', 'trial'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    // Business logo (owner/admin) — shown in the dashboard navbar
    Route::post('/profile/logo', [ProfileController::class, 'updateLogo'])->name('profile.logo.update');
    Route::delete('/profile/logo', [ProfileController::class, 'removeLogo'])->name('profile.logo.remove');

    // Owner management - Owners/Franchisor only (business control)
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

    // Store management - Admin and Franchisor only (business control)
    Route::middleware('role:admin,owner')->group(function () {
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
        // Admin-only summary route
        Route::middleware('role:admin')->group(function () {
            Route::get('/daily-reports/summary', [DailyReportController::class, 'summary'])->name('daily-reports.summary');
        });
        
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');

        // Multi-step creation process
        Route::get('/daily-reports/create', [DailyReportController::class, 'selectStore'])->name('daily-reports.create');
        Route::get('/daily-reports/create/step-2', [DailyReportController::class, 'selectDate'])->name('daily-reports.select-date');
        Route::get('/daily-reports/create/form', [DailyReportController::class, 'createForm'])->name('daily-reports.create-form');

        Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
        
        // Export routes - must be before {dailyReport} route to avoid route conflicts
        Route::get('/daily-reports/export-pdf-range', [DailyReportController::class, 'exportPdfRange'])->name('daily-reports.export-pdf-range');
        Route::get('/daily-reports/export-csv', [DailyReportController::class, 'exportCsv'])->name('daily-reports.export-csv');
        
        Route::get('/daily-reports/{dailyReport}', [DailyReportController::class, 'show'])->name('daily-reports.show');
        Route::get('/daily-reports/{dailyReport}/edit', [DailyReportController::class, 'edit'])->name('daily-reports.edit');
        Route::put('/daily-reports/{dailyReport}', [DailyReportController::class, 'update'])->name('daily-reports.update');
        Route::delete('/daily-reports/{dailyReport}', [DailyReportController::class, 'destroy'])->middleware('role:admin,owner')->name('daily-reports.destroy');
        Route::get('/daily-reports/{dailyReport}/export-pdf', [DailyReportController::class, 'exportPdf'])->name('daily-reports.export-pdf');
        Route::get('stores/{store}/daily-reports', [DailyReportController::class, 'reports'])->name('stores.daily-reports.index');

        // Approval workflow routes
        Route::post('/daily-reports/{dailyReport}/submit', [DailyReportController::class, 'submit'])->name('daily-reports.submit');
        Route::post('/daily-reports/{dailyReport}/approve', [DailyReportController::class, 'approve'])->name('daily-reports.approve');
        Route::post('/daily-reports/{dailyReport}/reject', [DailyReportController::class, 'reject'])->name('daily-reports.reject');
        Route::post('/daily-reports/{dailyReport}/return-to-draft', [DailyReportController::class, 'returnToDraft'])->name('daily-reports.return-to-draft');
    });

    // Inventory entry (Phase 5) — weekly Monday count. Employees are restricted
    // to this workflow; managers/owners/admins can also access it.
    Route::middleware('role:admin,owner,manager,employee')->group(function () {
        Route::get('/inventory', [\App\Http\Controllers\InventoryEntryController::class, 'index'])->name('inventory.entry.index');
        Route::post('/inventory/draft', [\App\Http\Controllers\InventoryEntryController::class, 'saveDraft'])->name('inventory.entry.draft');
        Route::post('/inventory/submit', [\App\Http\Controllers\InventoryEntryController::class, 'submit'])->name('inventory.entry.submit');

        // Phase 5 Part 1 Task 8 — the Monday weekly count. Literal sub-paths
        // only, so ordering against a wildcard is not a concern here.
        Route::get('/inventory/weekly-count', [\App\Http\Controllers\WeeklyCountController::class, 'index'])->name('inventory.weekly-count.index');
        Route::post('/inventory/weekly-count/autosave', [\App\Http\Controllers\WeeklyCountController::class, 'autosave'])->name('inventory.weekly-count.autosave');
        Route::post('/inventory/weekly-count/submit', [\App\Http\Controllers\WeeklyCountController::class, 'submit'])->name('inventory.weekly-count.submit');
        Route::post('/inventory/weekly-count/unlock', [\App\Http\Controllers\WeeklyCountController::class, 'unlock'])->name('inventory.weekly-count.unlock');
        Route::get('/inventory/weekly-count/suggestions', [\App\Http\Controllers\WeeklyCountController::class, 'suggestions'])->name('inventory.weekly-count.suggestions');
        Route::post('/inventory/weekly-count/generate-order', [\App\Http\Controllers\WeeklyCountController::class, 'generateOrder'])->name('inventory.weekly-count.generate-order');
    });

    // Square sales import (Phase 5.4) — weekly Items Sold CSV wizard.
    Route::middleware('role:admin,owner,manager')->group(function () {
        Route::get('/square-import', [\App\Http\Controllers\Admin\SquareSalesImportController::class, 'form'])->name('admin.square-import.form');
        Route::post('/square-import/preview', [\App\Http\Controllers\Admin\SquareSalesImportController::class, 'preview'])->name('admin.square-import.preview');
        Route::post('/square-import/commit', [\App\Http\Controllers\Admin\SquareSalesImportController::class, 'commit'])->name('admin.square-import.commit');

        // Stock-up worksheet (Phase 5.5) — projection → suggested order quantities.
        Route::get('/stock-up', [\App\Http\Controllers\Admin\StockUpController::class, 'index'])->name('admin.stock-up.index');

        // Multi-vendor orders (Phase 5.6). build/generate MUST precede {order}.
        Route::get('/orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])->name('admin.orders.index');
        Route::get('/orders/build', [\App\Http\Controllers\Admin\OrderController::class, 'build'])->name('admin.orders.build');
        Route::post('/orders/generate', [\App\Http\Controllers\Admin\OrderController::class, 'generate'])->name('admin.orders.generate');
        // /orders/history MUST precede /orders/{order} or the wildcard eats it.
        Route::get('/orders/history', [\App\Http\Controllers\Admin\OrderHistoryController::class, 'index'])->name('admin.orders.history');
        Route::get('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show'])->name('admin.orders.show');
        Route::get('/orders/{order}/report', [\App\Http\Controllers\Admin\OrderController::class, 'report'])->name('admin.orders.report');
        Route::get('/orders/{order}/report/pdf', [\App\Http\Controllers\Admin\OrderController::class, 'reportPdf'])->name('admin.orders.report.pdf');
        Route::patch('/orders/{order}/placed', [\App\Http\Controllers\Admin\OrderController::class, 'markPlaced'])->name('admin.orders.placed');
        Route::patch('/orders/{order}/received', [\App\Http\Controllers\Admin\OrderController::class, 'markReceived'])->name('admin.orders.received');
        Route::patch('/orders/{order}/cancel', [\App\Http\Controllers\Admin\OrderController::class, 'cancel'])->name('admin.orders.cancel');
        Route::put('/orders/{order}/items', [\App\Http\Controllers\Admin\OrderController::class, 'updateItems'])->name('admin.orders.items.update');
        Route::post('/orders/{order}/duplicate', [\App\Http\Controllers\Admin\OrderController::class, 'duplicateForSecondOrder'])->name('admin.orders.duplicate');
        Route::post('/orders/{order}/reorder', [\App\Http\Controllers\Admin\OrderHistoryController::class, 'reorder'])->name('admin.orders.reorder');
        Route::delete('/orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'destroy'])->name('admin.orders.destroy');

        // Vendor prices & comparison (Phase 5.7, extended in Phase 5 Part 1 Task 6).
        // Paths moved from /vendor-prices to /pricing/*; the route NAMES are kept
        // so every existing route() call and test keeps resolving.
        // /compare/export must precede nothing here, but keep literal paths above
        // any wildcard if one is ever added.
        Route::get('/pricing/update', [\App\Http\Controllers\Admin\VendorPriceController::class, 'index'])->name('admin.vendor-prices.index');
        Route::post('/pricing/update', [\App\Http\Controllers\Admin\VendorPriceController::class, 'bulkUpdate'])->name('admin.vendor-prices.bulk');
        Route::get('/pricing/compare', [\App\Http\Controllers\Admin\VendorPriceController::class, 'compare'])->name('admin.vendor-prices.compare');
        Route::get('/pricing/compare/export', [\App\Http\Controllers\Admin\VendorPriceController::class, 'exportCompare'])->name('admin.vendor-prices.compare.export');
        Route::post('/pricing/apply-cheapest', [\App\Http\Controllers\Admin\VendorPriceController::class, 'applyCheapest'])->name('admin.vendor-prices.apply-cheapest');
        Route::get('/pricing/history/{inventoryItem}', [\App\Http\Controllers\Admin\VendorPriceController::class, 'history'])->name('admin.vendor-prices.history');

        // Variance report (Phase 5.8) — the headline module.
        Route::get('/variance', [\App\Http\Controllers\Admin\VarianceReportController::class, 'index'])->name('admin.variance.index');
        Route::get('/variance/export/pdf', [\App\Http\Controllers\Admin\VarianceReportController::class, 'exportPdf'])->name('admin.variance.export.pdf');
        Route::get('/variance/export/csv', [\App\Http\Controllers\Admin\VarianceReportController::class, 'exportCsv'])->name('admin.variance.export.csv');
        Route::get('/variance/drill-down/{inventoryItem}', [\App\Http\Controllers\Admin\VarianceReportController::class, 'drillDown'])->name('admin.variance.drill-down');

        // Per-store stock targets (Phase 5). Literal sub-paths precede nothing
        // here, but keep them above any future wildcard.
        Route::get('/stores/{store}/inventory-targets', [\App\Http\Controllers\Admin\InventoryTargetController::class, 'index'])->name('admin.inventory-targets.index');
        Route::post('/stores/{store}/inventory-targets', [\App\Http\Controllers\Admin\InventoryTargetController::class, 'update'])->name('admin.inventory-targets.update');
        Route::post('/stores/{store}/inventory-targets/bulk-default', [\App\Http\Controllers\Admin\InventoryTargetController::class, 'bulkDefault'])->name('admin.inventory-targets.bulk-default');
        Route::post('/stores/{store}/inventory-targets/copy-from', [\App\Http\Controllers\Admin\InventoryTargetController::class, 'copyFrom'])->name('admin.inventory-targets.copy-from');

        // Inventory operations dashboard (Phase 5.9).
        Route::get('/inventory-dashboard', [\App\Http\Controllers\Admin\InventoryDashboardController::class, 'index'])->name('admin.inventory-dashboard.index');

        // Inventory item master (Phase 5). The literal /import* paths MUST come
        // before {inventoryItem} so the wildcard does not swallow them.
        Route::get('/inventory-items', [\App\Http\Controllers\Admin\InventoryItemController::class, 'index'])->name('admin.inventory-items.index');
        Route::get('/inventory-items/import', [\App\Http\Controllers\Admin\InventoryItemController::class, 'importForm'])->name('admin.inventory-items.import');
        Route::post('/inventory-items/import/preview', [\App\Http\Controllers\Admin\InventoryItemController::class, 'importPreview'])->name('admin.inventory-items.import.preview');
        Route::post('/inventory-items/import/commit', [\App\Http\Controllers\Admin\InventoryItemController::class, 'importCommit'])->name('admin.inventory-items.import.commit');
        Route::post('/inventory-items/bulk-assign-vendor', [\App\Http\Controllers\Admin\InventoryItemController::class, 'bulkAssignVendor'])->name('admin.inventory-items.bulk-assign-vendor');
        Route::post('/inventory-items', [\App\Http\Controllers\Admin\InventoryItemController::class, 'store'])->name('admin.inventory-items.store');
        Route::get('/inventory-items/{inventoryItem}', [\App\Http\Controllers\Admin\InventoryItemController::class, 'show'])->name('admin.inventory-items.show');
        Route::put('/inventory-items/{inventoryItem}', [\App\Http\Controllers\Admin\InventoryItemController::class, 'update'])->name('admin.inventory-items.update');
        Route::delete('/inventory-items/{inventoryItem}', [\App\Http\Controllers\Admin\InventoryItemController::class, 'destroy'])->name('admin.inventory-items.destroy');
    });

    // Inventory categories (Phase 5) — shared order-guide grouping. Admin only:
    // a rename changes every store's count sheet. "reorder" MUST precede
    // {inventoryCategory} so the drag-save is not swallowed by the wildcard.
    Route::middleware('role:admin')->group(function () {
        Route::get('/inventory-categories', [\App\Http\Controllers\Admin\InventoryCategoryController::class, 'index'])->name('admin.inventory-categories.index');
        Route::post('/inventory-categories/reorder', [\App\Http\Controllers\Admin\InventoryCategoryController::class, 'reorder'])->name('admin.inventory-categories.reorder');
        Route::post('/inventory-categories', [\App\Http\Controllers\Admin\InventoryCategoryController::class, 'store'])->name('admin.inventory-categories.store');
        Route::put('/inventory-categories/{inventoryCategory}', [\App\Http\Controllers\Admin\InventoryCategoryController::class, 'update'])->name('admin.inventory-categories.update');
        Route::delete('/inventory-categories/{inventoryCategory}', [\App\Http\Controllers\Admin\InventoryCategoryController::class, 'destroy'])->name('admin.inventory-categories.destroy');
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

    // Transaction Types - Admin and Owners/Franchisor (business configuration)
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/transaction-types', [TransactionTypeController::class, 'index'])->name('transaction-types.index');
        Route::get('/transaction-types/create', [TransactionTypeController::class, 'create'])->name('transaction-types.create');
        Route::post('/transaction-types', [TransactionTypeController::class, 'store'])->name('transaction-types.store');
        Route::post('/transaction-types/auto-assign-categories', [TransactionTypeController::class, 'autoAssignCategories'])->name('transaction-types.auto-assign-categories');
        Route::get('/transaction-types/{transactionType}', [TransactionTypeController::class, 'show'])->name('transaction-types.show');
        Route::get('/transaction-types/{transactionType}/edit', [TransactionTypeController::class, 'edit'])->name('transaction-types.edit');
        Route::put('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
        Route::patch('/transaction-types/{transactionType}/update-category', [TransactionTypeController::class, 'updateCategory'])->name('transaction-types.update-category');
        Route::delete('/transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
        Route::post('transaction-types/{transactionType}/assign-stores', [TransactionTypeController::class, 'assignStores'])->name('transaction-types.assign.stores');

        // Holidays - self-service management of the daily-report holiday list.
        Route::get('/holidays', [\App\Http\Controllers\Admin\HolidayController::class, 'index'])->name('admin.holidays.index');
        Route::post('/holidays', [\App\Http\Controllers\Admin\HolidayController::class, 'store'])->name('admin.holidays.store');
        Route::put('/holidays/{holiday}', [\App\Http\Controllers\Admin\HolidayController::class, 'update'])->name('admin.holidays.update');
        Route::delete('/holidays/{holiday}', [\App\Http\Controllers\Admin\HolidayController::class, 'destroy'])->name('admin.holidays.destroy');

        // Categorization Rules (Phase 4) - learned import auto-categorization rules + decision log.
        Route::get('/mapping-rules', [\App\Http\Controllers\Admin\MappingRuleController::class, 'index'])->name('admin.mapping-rules.index');
        Route::put('/mapping-rules/{rule}', [\App\Http\Controllers\Admin\MappingRuleController::class, 'update'])->name('admin.mapping-rules.update');
        Route::patch('/mapping-rules/{rule}/toggle', [\App\Http\Controllers\Admin\MappingRuleController::class, 'toggle'])->name('admin.mapping-rules.toggle');
        Route::delete('/mapping-rules/{rule}', [\App\Http\Controllers\Admin\MappingRuleController::class, 'destroy'])->name('admin.mapping-rules.destroy');

        // Menu items & recipes (Phase 5.3). Import routes MUST precede {menuItem}.
        Route::get('/menu-items', [\App\Http\Controllers\Admin\MenuItemController::class, 'index'])->name('admin.menu-items.index');
        Route::get('/menu-items/create', [\App\Http\Controllers\Admin\MenuItemController::class, 'create'])->name('admin.menu-items.create');
        Route::post('/menu-items', [\App\Http\Controllers\Admin\MenuItemController::class, 'store'])->name('admin.menu-items.store');
        Route::get('/menu-items/import', [\App\Http\Controllers\Admin\MenuItemController::class, 'importForm'])->name('admin.menu-items.import.form');
        Route::post('/menu-items/import', [\App\Http\Controllers\Admin\MenuItemController::class, 'import'])->name('admin.menu-items.import');
        Route::get('/menu-items/{menuItem}', [\App\Http\Controllers\Admin\MenuItemController::class, 'show'])->name('admin.menu-items.show');
        Route::get('/menu-items/{menuItem}/edit', [\App\Http\Controllers\Admin\MenuItemController::class, 'edit'])->name('admin.menu-items.edit');
        Route::put('/menu-items/{menuItem}', [\App\Http\Controllers\Admin\MenuItemController::class, 'update'])->name('admin.menu-items.update');
        Route::delete('/menu-items/{menuItem}', [\App\Http\Controllers\Admin\MenuItemController::class, 'destroy'])->name('admin.menu-items.destroy');
        Route::put('/menu-items/{menuItem}/recipe/{size}', [\App\Http\Controllers\Admin\MenuItemController::class, 'updateRecipe'])->name('admin.menu-items.recipe.update');
    });

    // Chart of Accounts - Admin and Owners/Franchisor (business configuration)
    // Owners may VIEW and ADD accounts but may NOT modify existing ones;
    // edit/update/destroy are admin-only (registered separately below).
    Route::middleware('role:admin,owner')->group(function () {
        // Hierarchy report + exports. MUST be registered before the resource route
        // so "report"/"export" are not captured as a {chartOfAccount} parameter.
        Route::get('chart-of-accounts/report', [ChartOfAccountController::class, 'report'])->name('coa.report');
        Route::get('chart-of-accounts/export/csv', [ChartOfAccountController::class, 'exportCsv'])->name('coa.export.csv');
        Route::get('chart-of-accounts/export/pdf', [ChartOfAccountController::class, 'exportPdf'])->name('coa.export.pdf');

        Route::resource('chart-of-accounts', ChartOfAccountController::class)
            ->parameters(['chart-of-accounts' => 'chartOfAccount'])
            ->only(['index', 'create', 'store', 'show'])
            ->names('coa');
        // API endpoint for stores (for COA form and other admin tools)
        Route::get('/api/stores', function () {
            return response()->json(App\Models\Store::select('id', 'store_info as name')->get());
        })->name('api.stores');
        // API endpoint for COAs (for vendor form and other admin tools)
        Route::get('/api/coa-list', function (Request $request) {
            $query = \App\Models\ChartOfAccount::select('id', 'account_code', 'account_name', 'is_active');
            
            // Optional filters
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('account_name', 'like', "%{$search}%")
                      ->orWhere('account_code', 'like', "%{$search}%");
                });
            }
            
            $perPage = $request->per_page ?? 10000;
            $coas = $query->orderByRaw('CAST(account_code AS UNSIGNED) ASC')
                          ->orderBy('account_name')
                          ->paginate($perPage);
            
            return response()->json($coas);
        })->name('api.coa.list');

        // Child accounts that roll up under a parent (+ allowed sub-code range)
        Route::get('/api/coa/{chartOfAccount}/children', [ChartOfAccountController::class, 'children'])
            ->name('api.coa.children');
    });

    // Chart of Accounts — owners may edit/delete the accounts THEY created;
    // admin/franchisor may manage any. Per-account ownership is enforced in the
    // controller (edit/destroy) and UpdateChartOfAccountRequest (update).
    Route::middleware('role:admin,owner')->group(function () {
        Route::resource('chart-of-accounts', ChartOfAccountController::class)
            ->parameters(['chart-of-accounts' => 'chartOfAccount'])
            ->only(['edit', 'update', 'destroy'])
            ->names('coa');
    });

    // Vendors - Admin and Owner can manage
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/vendors', [VendorViewController::class, 'index'])->name('admin.vendors.index');
    });

    // Expenses - Admin, Owner, Manager can view
    Route::middleware(['role:admin,owner,manager', 'convert_date_format'])->group(function () {
        Route::get('/expenses', [ExpenseViewController::class, 'index'])->name('admin.expenses.index');
        Route::get('/expenses/review', [ReviewQueueViewController::class, 'index'])->name('admin.expenses.review');
    });
    
    // Merchant Fees - Admin, Owner
    Route::middleware(['role:admin,owner', 'convert_date_format'])->group(function () {
        Route::get('/merchant-fees', [MerchantFeeViewController::class, 'index'])->name('admin.merchant-fees.index');
        Route::get('/merchant-fees/third-party', [MerchantFeeViewController::class, 'thirdParty'])->name('admin.merchant-fees.third-party');
        Route::get('/merchant-fees/third-party/statements/{statement}', [MerchantFeeViewController::class, 'thirdPartyStatementShow'])->name('admin.merchant-fees.third-party.show');
        Route::delete('/merchant-fees/third-party/statements/{statement}', [MerchantFeeViewController::class, 'thirdPartyStatementDestroy'])->name('admin.merchant-fees.third-party.destroy');
        Route::get('/merchant-fees/exceptions', [ReviewQueueViewController::class, 'exceptionsReport'])->name('admin.exceptions-report.index');
        Route::get('/merchant-fees/import-log', [ImportLogController::class, 'index'])->name('admin.import-log.index');
    });

    // Owner CC Statements - Admin, Owner (import CSV/XLSX statements)
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/owner-cc-statements', [OwnerCcStatementImportController::class, 'index'])->name('admin.owner-cc-statements.index');
        Route::get('/owner-cc-statements/create', [OwnerCcStatementImportController::class, 'create'])->name('admin.owner-cc-statements.create');
        Route::post('/owner-cc-statements', [OwnerCcStatementImportController::class, 'store'])->name('admin.owner-cc-statements.store');
        Route::get('/owner-cc-statements/{ownerCcStatementImport}', [OwnerCcStatementImportController::class, 'show'])->name('admin.owner-cc-statements.show');
        Route::post('/owner-cc-statements/{ownerCcStatementImport}/card-last4', [OwnerCcStatementImportController::class, 'updateCardLast4'])->name('admin.owner-cc-statements.card-last4');
        Route::match(['get', 'post'], '/owner-cc-statements/{ownerCcStatementImport}/download', [OwnerCcStatementImportController::class, 'download'])->name('admin.owner-cc-statements.download');
        Route::post('/owner-cc-statements/{ownerCcStatementImport}/lines/bulk-update', [OwnerCcStatementImportController::class, 'bulkUpdateLines'])->name('admin.owner-cc-statements.lines.bulk-update');
        Route::put('/owner-cc-statements/lines/{ownerCcStatementLine}/transaction-type', [OwnerCcStatementImportController::class, 'updateLineTransactionType'])->name('admin.owner-cc-statements.lines.update-type');
        Route::get('/owner-cc-statements/{ownerCcStatementImport}/exceptions', [OwnerCcStatementImportController::class, 'downloadExceptionReport'])->name('admin.owner-cc-statements.exceptions');
        Route::delete('/owner-cc-statements/{ownerCcStatementImport}', [OwnerCcStatementImportController::class, 'destroy'])->name('admin.owner-cc-statements.destroy');

        Route::get('/bank-statement-imports', [BankStatementImportController::class, 'index'])->name('admin.bank-statement-imports.index');
        Route::get('/bank-statement-imports/create', [BankStatementImportController::class, 'create'])->name('admin.bank-statement-imports.create');
        Route::post('/bank-statement-imports', [BankStatementImportController::class, 'store'])->name('admin.bank-statement-imports.store');
        Route::get('/bank-statement-imports/batches/{importBatch}', [BankStatementImportController::class, 'show'])->name('admin.bank-statement-imports.show');
        Route::post('/bank-statement-imports/batches/{importBatch}/bank-transactions/{bankTransaction}/coa', [BankStatementImportController::class, 'updateTransactionCoa'])->name('admin.bank-statement-imports.transaction-coa');
        Route::delete('/bank-statement-imports/batches/{importBatch}', [BankStatementImportController::class, 'destroy'])->name('admin.bank-statement-imports.destroy');
    });

    // P&L Reports - Admin, Owner (full access), Manager (view only)
    Route::middleware(['role:admin,owner,manager', 'convert_date_format'])->group(function () {
        // Managers can view but not export
        Route::get('/reports/profit-loss', [ProfitLossViewController::class, 'index'])->name('admin.reports.profit-loss.index');
        Route::get('/reports/profit-loss/annual', [ProfitLossViewController::class, 'annual'])->name('admin.reports.profit-loss.annual');
        Route::get('/reports/profit-loss/drill-down', [ProfitLossViewController::class, 'drillDown'])->name('admin.reports.profit-loss.drill-down');
    });
    
    // P&L Export and Advanced Features - Admin and Owner only
    Route::middleware(['role:admin,owner', 'convert_date_format'])->group(function () {
        Route::get('/reports/profit-loss/comparison', [ProfitLossViewController::class, 'comparison'])->name('admin.reports.profit-loss.comparison');
        Route::get('/reports/profit-loss/snapshots', [ProfitLossViewController::class, 'snapshots'])->name('admin.reports.profit-loss.snapshots');
        Route::get('/reports/profit-loss/snapshots/{snapshot}', [ProfitLossViewController::class, 'showSnapshot'])->name('admin.reports.profit-loss.snapshots.show');
        Route::get('/reports/profit-loss/export/csv', [ProfitLossViewController::class, 'exportCsv'])->name('admin.reports.profit-loss.export.csv');
        Route::get('/reports/profit-loss/export/pdf', [ProfitLossViewController::class, 'exportPdf'])->name('admin.reports.profit-loss.export.pdf');
    });

    // Bank Accounts - Admin, Owner
    Route::middleware(['role:admin,owner', 'convert_date_format'])->group(function () {
        Route::get('/bank-accounts', [BankAccountViewController::class, 'index'])->name('admin.bank.accounts.index');
        Route::get('/bank-accounts/{id}', [BankAccountViewController::class, 'show'])->name('admin.bank.accounts.show');
        Route::get('/bank-accounts/{accountId}/reconciliation', [BankAccountViewController::class, 'reconciliation'])->name('admin.bank.reconciliation.index');
    });

    // Revenue Income Types Routes - Admin and Owners/Franchisor (business configuration)
    Route::middleware('role:admin,owner')->group(function () {
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
    // IMPORTANT: /impersonate/stop must come BEFORE /impersonate/{user} to avoid route conflicts
    Route::match(['get', 'post'], '/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    Route::get('/debug-user', [ImpersonationController::class, 'debug'])->name('debug.user');

    // Admin Reports (audit logs) - Admin and Owner
    Route::middleware(['admin_or_owner', 'convert_date_format'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });

    // Subscriptions dashboard (active subs, MRR, churn) - Admin only (Phase 4)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/subscriptions', [SubscriptionAdminController::class, 'index'])->name('admin.subscriptions.index');
    });

    // KPI configuration (per-store dashboard targets) - Admin and Owner (Phase 4)
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/kpi-settings', [KpiController::class, 'edit'])->name('kpi.edit');
        Route::put('/kpi-settings', [KpiController::class, 'update'])->name('kpi.update');
    });

    // Sales Projection Calendar (per-store daily projections vs actuals) - Phase 4
    Route::middleware('role:admin,owner,manager')->group(function () {
        Route::get('/sales-projections', [SalesProjectionController::class, 'index'])->name('sales-projections.index');
        Route::post('/sales-projections', [SalesProjectionController::class, 'store'])->name('sales-projections.store');
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
            // Active toggle must precede apiResource so "toggle-active" is not
            // swallowed by the {vendor} wildcard.
            Route::patch('vendors/{id}/toggle-active', [VendorController::class, 'toggleActive']);
            Route::apiResource('vendors', VendorController::class);
        });
        
        // Vendor aliases + restore - Admin only
        Route::middleware('role:admin')->group(function () {
            Route::post('vendors/{id}/aliases', [VendorController::class, 'addAlias']);
            Route::post('vendors/{id}/restore', [VendorController::class, 'restore']);
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
        Route::middleware('role:admin,owner,manager')->group(function () {
            Route::get('reports/pl', [ProfitLossController::class, 'index']);
            Route::get('reports/pl/annual', [ProfitLossController::class, 'annual']);
            Route::get('reports/pl/drill-down', [ProfitLossController::class, 'drillDown']);
        });
        Route::middleware('role:admin,owner')->group(function () {
            Route::post('reports/pl/snapshot', [ProfitLossController::class, 'snapshot']);
            Route::get('reports/pl/snapshots', [ProfitLossController::class, 'snapshots']);
            Route::get('reports/pl/consolidated', [ProfitLossController::class, 'consolidated']);
            Route::get('reports/pl/store-comparison', [ProfitLossController::class, 'storeComparison']);
        });
    });
});
