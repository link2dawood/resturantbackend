<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\TransactionTypeController;
use App\Http\Controllers\DailyReportController;


Route::get('/', [App\Http\Controllers\WelcomeController::class, 'index'])->name('index');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

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

    Route::get('/owners', [OwnerController::class, 'index'])->name('owners.index');
    Route::get('owners/create', [OwnerController::class, 'create'])->name('owners.create');
    Route::post('owners/create', [OwnerController::class, 'create'])->name('owners.store');
    Route::get('owners/{owner}/edit', [OwnerController::class, 'edit'])->name('owners.edit');
    Route::put('owners/{owner}', [OwnerController::class, 'update'])->name('owners.update');
    Route::delete('owners/{owner}', [OwnerController::class, 'destroy'])->name('owners.destroy');

    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index'); // Display all stores
    Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create'); // Show form to create a store
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store'); // Save a new store
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit'); // Show form to edit a store
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update'); // Update a store
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy'); // Delete a store
    
    // Daily Reports Routes
    Route::get('/daily-reports', [DailyReportController::class, 'index'])->name('daily-reports.index');
    Route::get('/daily-reports/create', [DailyReportController::class, 'create'])->name('daily-reports.create');
    Route::post('/daily-reports', [DailyReportController::class, 'store'])->name('daily-reports.store');
    Route::get('/daily-reports/{dailyReport}', [DailyReportController::class, 'show'])->name('daily-reports.show');
    Route::get('/daily-reports/{dailyReport}/edit', [DailyReportController::class, 'edit'])->name('daily-reports.edit');
    Route::put('/daily-reports/{dailyReport}', [DailyReportController::class, 'update'])->name('daily-reports.update');
    Route::delete('/daily-reports/{dailyReport}', [DailyReportController::class, 'destroy'])->name('daily-reports.destroy');
    Route::get('/daily/reports', [DailyReportController::class, 'reports'])->name('reports.index'); 

    Route::get('/managers', [ManagerController::class, 'index'])->name('managers.index');
    Route::get('/managers/create', [ManagerController::class, 'create'])->name('managers.create');
    Route::post('/managers', [ManagerController::class, 'store'])->name('managers.store');
    Route::get('/managers/{manager}/edit', [ManagerController::class, 'edit'])->name('managers.edit');
    Route::put('/managers/{manager}', [ManagerController::class, 'update'])->name('managers.update');
    Route::delete('/managers/{manager}', [ManagerController::class, 'destroy'])->name('managers.destroy');
    Route::get('managers/{manager}/assign-stores', [ManagerController::class, 'assignStoresForm'])->name('managers.assign-stores.form');
    Route::post('managers/{manager}/assign-stores', [ManagerController::class, 'assignStores'])->name('managers.assign-stores');

    Route::get('/transaction-types', [TransactionTypeController::class, 'index'])->name('transaction-types.index');
    Route::get('/transaction-types/create', [TransactionTypeController::class, 'create'])->name('transaction-types.create');
    Route::post('/transaction-types', [TransactionTypeController::class, 'store'])->name('transaction-types.store');
    Route::get('/transaction-types/{transactionType}/edit', [TransactionTypeController::class, 'edit'])->name('transaction-types.edit');
    Route::put('/transaction-types/{transactionType}', [TransactionTypeController::class, 'update'])->name('transaction-types.update');
    Route::delete('/transaction-types/{transactionType}', [TransactionTypeController::class, 'destroy'])->name('transaction-types.destroy');
    Route::post('transaction-types/{transactionType}/assign-stores', [TransactionTypeController::class, 'assignStores'])->name('transaction-types.assign.stores');
});
