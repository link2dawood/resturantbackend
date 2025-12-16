<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChartOfAccountController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\ExpenseController;

// Chart of Accounts Routes - using web auth for now
Route::middleware(['auth:web'])->group(function () {
    Route::apiResource('coa', ChartOfAccountController::class)->names([
        'index' => 'api.coa.index',
        'store' => 'api.coa.store',
        'show' => 'api.coa.show',
        'update' => 'api.coa.update',
        'destroy' => 'api.coa.destroy',
    ]);
});

// Vendor Routes - using web auth for now
Route::middleware(['auth:web'])->group(function () {
    // Custom routes must come before resource routes
    Route::post('vendors/{id}/aliases', [VendorController::class, 'addAlias']);
    Route::get('vendors/match', [VendorController::class, 'match']);
    Route::apiResource('vendors', VendorController::class);
});

// Expense Routes - using web auth for now
Route::middleware(['auth:web'])->group(function () {
    // Custom routes must come before standard routes
    Route::post('expenses/sync-cash-expenses', [ExpenseController::class, 'syncCashExpenses']);
    Route::get('expenses', [ExpenseController::class, 'index']);
    Route::post('expenses', [ExpenseController::class, 'store']);
    Route::get('expenses/{id}', [ExpenseController::class, 'show']);
    Route::put('expenses/{id}', [ExpenseController::class, 'update']);
});

