# Permissions Implementation - Complete

## Overview

The system implements a comprehensive role-based access control (RBAC) system with three main roles: Super Admin (Admin), Owner/Admin, and Manager.

---

## Role Permissions Matrix

### 🔴 Super Admin (Admin Role)
**Full System Access - Can do everything**

#### Chart of Accounts (COA)
- ✅ **Full CRUD**: Create, Read, Update, Delete
- ✅ Setup and manage all COA categories
- ✅ Assign COA to stores (global or per-store)
- ✅ Manage system accounts

#### Vendor Management
- ✅ **Full CRUD**: Create, Read, Update, Delete
- ✅ Manage all vendors across all stores
- ✅ Link vendors to COA categories
- ✅ Manage vendor aliases

#### File Uploads
- ✅ Upload bank statement CSVs
- ✅ Upload credit card statement CSVs
- ✅ Upload third-party platform statements
- ✅ Import history access

#### Reports
- ✅ **All Reports**: View all stores
- ✅ Generate P&L for any store
- ✅ Export P&L to PDF/CSV
- ✅ Store comparison reports
- ✅ Consolidated multi-store reports
- ✅ Create and manage P&L snapshots

#### Other Permissions
- ✅ User management (Owners, Managers)
- ✅ Store management (all stores)
- ✅ Transaction type management
- ✅ Revenue income type management
- ✅ Audit logs access
- ✅ System configuration

---

### 🟠 Owner/Admin
**Upload files, edit vendors, generate P&L for their stores**

#### Chart of Accounts (COA)
- ✅ **View Only**: Can view COA for reference
- ❌ Cannot create, update, or delete COA

#### Vendor Management
- ✅ **View and Edit**: Can view and edit vendors
- ✅ Create new vendors
- ✅ Update vendor information
- ✅ Link vendors to COA categories
- ❌ Cannot delete vendors (Admin only)

#### File Uploads
- ✅ Upload bank statement CSVs
- ✅ Upload credit card statement CSVs
- ✅ Upload third-party platform statements
- ✅ Import history access (for their stores)

#### Reports
- ✅ **Generate P&L**: For their stores only
- ✅ View P&L for their stores
- ✅ Export P&L to PDF/CSV (for their stores)
- ✅ Store comparison (for their stores)
- ✅ Create P&L snapshots (for their stores)
- ❌ Cannot view other owners' stores

#### Other Permissions
- ✅ Store management (their stores only)
- ✅ Manager management (assign to their stores)
- ✅ Daily reports (create, view, edit, approve)
- ✅ Expense management (view, create, update)
- ✅ Bank reconciliation (for their stores)
- ✅ Review queue (categorize transactions)

---

### 🟢 Manager
**Enter daily reports, view store-level P&L only**

#### Chart of Accounts (COA)
- ✅ **View Only**: Can view COA for reference when entering expenses
- ❌ Cannot create, update, or delete COA

#### Vendor Management
- ✅ **View Only**: Can view vendors for reference
- ❌ Cannot create, update, or delete vendors

#### File Uploads
- ❌ **No Access**: Cannot upload any CSV files
- ❌ Cannot access import history

#### Reports
- ✅ **View P&L Only**: For their assigned stores only
- ✅ View drill-down transactions
- ❌ Cannot export P&L (no CSV/PDF export)
- ❌ Cannot generate new P&L reports
- ❌ Cannot access store comparison
- ❌ Cannot access snapshots

#### Daily Reports
- ✅ **Create**: Daily reports for assigned stores
- ✅ **View**: Daily reports for assigned stores
- ✅ **Edit**: Their own reports (before approval)
- ❌ Cannot approve reports
- ❌ Cannot delete reports

#### Other Permissions
- ✅ View assigned stores only
- ✅ Create expenses for assigned stores
- ✅ View expenses for assigned stores
- ❌ Cannot access bank reconciliation
- ❌ Cannot access review queue

---

## Permission Enforcement

### Route Protection

#### Admin-Only Routes
```php
Route::middleware('role:admin')->group(function () {
    // Chart of Accounts - Full CRUD
    Route::resource('chart-of-accounts', ChartOfAccountController::class);
    
    // Vendor Aliases - Admin only
    Route::post('vendors/{id}/aliases', ...);
    
    // System Configuration
    Route::resource('transaction-types', ...);
    Route::resource('revenue-income-types', ...);
});
```

#### Admin + Owner Routes
```php
Route::middleware('role:admin,owner')->group(function () {
    // Vendor Management - View and Edit
    Route::get('/vendors', ...);
    Route::apiResource('vendors', VendorController::class);
    
    // File Uploads
    Route::post('bank/import/upload', ...);
    Route::post('third-party/import', ...);
    
    // P&L Export
    Route::get('/reports/profit-loss/export/csv', ...);
    Route::get('/reports/profit-loss/export/pdf', ...);
    
    // Bank Reconciliation
    Route::get('/bank-accounts', ...);
    Route::get('/bank-accounts/{id}/reconciliation', ...);
});
```

#### Admin + Owner + Manager Routes
```php
Route::middleware('role:admin,owner,manager')->group(function () {
    // P&L View (Managers can only view)
    Route::get('/reports/profit-loss', ...);
    Route::get('/reports/profit-loss/drill-down', ...);
    
    // Daily Reports
    Route::resource('daily-reports', ...);
    
    // Expenses (View and Create)
    Route::get('/expenses', ...);
    Route::post('expenses', ...);
});
```

### Controller-Level Protection

#### P&L Export Protection
```php
public function exportCsv(Request $request)
{
    $user = auth()->user();
    
    // Managers cannot export P&L
    if ($user->isManager()) {
        abort(403, 'Managers cannot export P&L reports');
    }
    
    // Owner can only export for their stores
    if ($user->isOwner() && $request->store_id && !$user->hasStoreAccess($request->store_id)) {
        abort(403, 'Access denied to this store');
    }
}
```

#### Store Access Validation
```php
// In ProfitLossViewController
if ($storeId && !$user->hasStoreAccess($storeId)) {
    abort(403, 'Access denied to this store');
}

// Managers can only view their assigned stores
if ($user->isManager() && $storeId && !$user->hasStoreAccess($storeId)) {
    abort(403, 'Access denied to this store');
}
```

### Blade Directive Protection

```blade
@can('reports', 'export')
    <a href="{{ route('admin.reports.profit-loss.export.csv') }}">Export CSV</a>
    <a href="{{ route('admin.reports.profit-loss.export.pdf') }}">Export PDF</a>
@endcan

@can('vendors', 'update')
    <button>Edit Vendor</button>
@endcan
```

---

## Permission Matrix Summary

| Feature | Super Admin | Owner/Admin | Manager |
|---------|-------------|------------|---------|
| **COA Setup** | ✅ Full CRUD | ❌ View Only | ❌ View Only |
| **Vendor Management** | ✅ Full CRUD | ✅ View/Edit | ❌ View Only |
| **File Uploads** | ✅ All | ✅ All | ❌ None |
| **P&L Generation** | ✅ All Stores | ✅ Their Stores | ❌ None |
| **P&L View** | ✅ All Stores | ✅ Their Stores | ✅ Assigned Stores |
| **P&L Export** | ✅ PDF/CSV | ✅ PDF/CSV | ❌ None |
| **Daily Reports** | ✅ All | ✅ Their Stores | ✅ Assigned Stores |
| **Bank Reconciliation** | ✅ All | ✅ Their Stores | ❌ None |
| **Review Queue** | ✅ All | ✅ Their Stores | ❌ None |

---

## Database Permissions

### Permission Seeder
The `PermissionSeeder` creates granular permissions:
- `manage_coa`, `view_coa`
- `manage_vendors`, `view_vendors`, `edit_vendors`
- `upload_files`
- `generate_pl`, `view_pl`, `export_pl`

### Role-Permission Mapping
- **Admin**: All permissions
- **Owner**: View COA, Edit Vendors, Upload Files, Generate/View/Export P&L
- **Manager**: View COA, View Vendors, View P&L (no export)

---

## Security Features

1. **Route Middleware**: All routes protected by role middleware
2. **Controller Checks**: Additional validation in controllers
3. **Store Access Validation**: Users can only access their assigned stores
4. **Blade Directives**: UI elements hidden based on permissions
5. **API Authorization**: All API endpoints check permissions
6. **Audit Logging**: Permission denials are logged

---

## Implementation Status: ✅ **COMPLETE**

All permission requirements have been implemented:
- ✅ Super Admin: Full system access (COA setup, vendor management, all reports)
- ✅ Owner/Admin: Upload files, edit vendors, generate P&L for their stores
- ✅ Manager: Enter daily reports, view store-level P&L only

