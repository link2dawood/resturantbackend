<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\RevenueIncomeTypeController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;


Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('index');

Auth::routes();

// Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/states', [App\Http\Controllers\HomeController::class, 'states'])->name('states.index');

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
        Route::get('owners/{owner}/edit', [OwnerController::class, 'edit'])->name('owners.edit');
        Route::put('owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
        Route::delete('owners/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');
        Route::get('owners/{owner}/assign-stores', [OwnerController::class, 'assignStoresForm'])->name('owners.assign-stores.form');
        Route::post('owners/{owner}/assign-stores', [OwnerController::class, 'assignStores'])->name('owners.assign-stores');
    });

    // Store management - Admin and Owner access
    Route::middleware('role:admin,owner')->group(function () {
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    });
    
    // Daily Reports Routes - with access control and date conversion
    Route::middleware(['daily_report_access', 'convert_date_format'])->group(function () {
        Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');

        // Multi-step creation process
        Route::get('/daily-reports/create', [DailyReportController::class, 'selectStore'])->name('daily-reports.create');
        Route::get('/daily-reports/create/step-2', [DailyReportController::class, 'selectDate'])->name('daily-reports.select-date');
        Route::get('/daily-reports/create/form', [DailyReportController::class, 'createForm'])->name('daily-reports.create-form');

        Route::get('/daily-reports/quick-entry', [DailyReportController::class, 'quickEntry'])->name('daily-reports.quick-entry');
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
        Route::get('/transaction-types/{transactionType}/edit', [TransactionTypeController::class, 'edit'])->name('transaction-types.edit');
        Route::put('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
        Route::delete('/transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
        Route::post('transaction-types/{transactionType}/assign-stores', [TransactionTypeController::class, 'assignStores'])->name('transaction-types.assign.stores');
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
    });


    // Audit Log Routes (Admin and Owner only) with date conversion
    Route::middleware(['admin_or_owner', 'convert_date_format'])->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    });
});
