<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChartOfAccountController;

/*
 * Chart of Accounts is the ONLY API resource that lives here (web.php delegates
 * to this file for it — see the note in routes/web.php). It is business-wide
 * reference data shared across all stores, so any authenticated business role
 * may read it; the controller enforces write authorization inline (create =
 * admin/owner, update/destroy = admin).
 *
 * Vendor and Expense API routes used to be duplicated here under `auth:web`
 * only, which shadowed the role-guarded definitions in routes/web.php. They
 * have been removed — routes/web.php is now the single, guarded source.
 */
Route::middleware(['auth:web', 'role:admin,owner,manager'])->group(function () {
    Route::apiResource('coa', ChartOfAccountController::class)->names('api.coa');
});
