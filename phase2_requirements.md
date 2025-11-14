# Fann's RMS - Phase 2: Development Tasks Breakdown

## Project Overview
**Phase 2 Goal:** Develop an Expense Management and P&L Module that consolidates all cash, credit card, and bank activity into a unified accounting summary.

**Tech Stack:**
- **Backend:** Laravel 12 (PHP 8.2+)
- **Frontend:** Bootstrap 5.3+ (No Material UI or other CSS frameworks)
- **JavaScript:** Vanilla JS / jQuery (Alpine.js optional for reactivity)
- **Database:** MySQL 8.0+
- **Template Engine:** Blade
- **Authentication:** Laravel Breeze/Sanctum (from Phase 1)

---

## PREREQUISITE SETUP (Before Starting Phase 2)

### Environment Setup

**1. Verify Laravel Installation:**
```bash
php artisan --version  # Should show Laravel Framework 12.x.x
php --version          # Should show PHP 8.2 or higher
```

**2. Install Required Packages:**
```bash
composer require league/csv
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
composer require smalot/pdfparser
composer require spatie/laravel-permission

# Dev dependencies
composer require --dev laravel/pint
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
```

**3. Configure Database:**
```env
# .env file
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fanns_rms
DB_USERNAME=root
DB_PASSWORD=your_password
```

**4. Setup Permissions Package:**
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

**5. Frontend Assets:**

**File: `resources/views/layouts/app.blade.php`** (Update header)
```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-token" content="{{ auth()->user()?->createToken('api-token')->plainTextToken }}">
    <title>@yield('title', 'Fann\'s RMS')</title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    @include('layouts.partials.navbar')
    
    <div class="container-fluid">
        <div class="row">
            @include('layouts.partials.sidebar')
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                @yield('content')
            </main>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (optional) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
```

**6. Create Helper JavaScript File:**

**File: `public/js/app.js`**
```javascript
// CSRF Token Setup
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

// API Token Setup
function getAuthToken() {
    return document.querySelector('meta[name="api-token"]')?.content || '';
}

// Toast Notification Function
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();
    
    const bgColor = type === 'success' ? 'bg-success' : 
                    type === 'error' || type === 'danger' ? 'bg-danger' : 
                    type === 'warning' ? 'bg-warning' : 'bg-info';
    
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();
    
    // Remove from DOM after hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Loading Spinner Functions
function showLoader() {
    if (!document.getElementById('globalLoader')) {
        const loader = `
            <div id="globalLoader" class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.3); z-index: 9999;">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', loader);
    }
}

function hideLoader() {
    const loader = document.getElementById('globalLoader');
    if (loader) {
        loader.remove();
    }
}

// Set Button Loading State
function setButtonLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    
    if (loading) {
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    }
}

// Format Currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format Date
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Confirm Action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}
```

**7. Add Custom Styles:**

**File: `public/css/custom.css`**
```css
/* Custom styles for Fann's RMS */

:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    --success-color: #198754;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
    --info-color: #0dcaf0;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

/* Table improvements */
.table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

/* Badge improvements */
.badge {
    font-weight: 500;
}

/* Loading states */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Card shadows */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Form improvements */
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Button improvements */
.btn {
    font-weight: 500;
}

/* Modal improvements */
.modal-header {
    border-bottom: 2px solid #dee2e6;
}

.modal-footer {
    border-top: 2px solid #dee2e6;
}

/* Toast positioning */
.toast-container {
    z-index: 9999;
}

/* Responsive table */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
}
```

---

## MILESTONE 1: Chart of Accounts (COA) Management
**Estimated Time: 1-2 weeks**

### Task 1.1: Database Schema - COA Table
**Priority: High | Estimated Time: 4 hours**

**Description:**
Create the database table structure for Chart of Accounts using Laravel migrations.

**Requirements:**

**Create Migration:**
```bash
php artisan make:migration create_chart_of_accounts_table
php artisan make:migration create_coa_store_assignments_table
```

**Migration Code:**

**File: `database/migrations/xxxx_create_chart_of_accounts_table.php`**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code', 10)->unique();
            $table->string('account_name', 100);
            $table->enum('account_type', ['Revenue', 'COGS', 'Expense', 'Other Income']);
            $table->foreignId('parent_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_account')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('account_code');
            $table->index('account_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
```

**File: `database/migrations/xxxx_create_coa_store_assignments_table.php`**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coa_store_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coa_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->boolean('is_global')->default(false);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['coa_id', 'store_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coa_store_assignments');
    }
};
```

**Create Seeder for System Accounts:**
```bash
php artisan make:seeder ChartOfAccountsSeeder
```

**File: `database/seeders/ChartOfAccountsSeeder.php`**
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // Revenue Accounts
            ['account_code' => '4000', 'account_name' => 'Revenue - Food Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4100', 'account_name' => 'Revenue - Beverage Sales', 'account_type' => 'Revenue', 'is_system_account' => true],
            ['account_code' => '4200', 'account_name' => 'Revenue - Third Party (Grubhub/Uber)', 'account_type' => 'Revenue', 'is_system_account' => true],
            
            // COGS Accounts
            ['account_code' => '5000', 'account_name' => 'COGS - Food Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5100', 'account_name' => 'COGS - Beverage Purchases', 'account_type' => 'COGS', 'is_system_account' => true],
            ['account_code' => '5200', 'account_name' => 'COGS - Packaging Supplies', 'account_type' => 'COGS', 'is_system_account' => true],
            
            // Expense Accounts
            ['account_code' => '6000', 'account_name' => 'Merchant Processing Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6100', 'account_name' => 'Marketing Fees (Grubhub)', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6200', 'account_name' => 'Delivery Service Fees', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6300', 'account_name' => 'Utilities - Electric', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6310', 'account_name' => 'Utilities - Water', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6320', 'account_name' => 'Utilities - Gas', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6330', 'account_name' => 'Utilities - Internet', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6400', 'account_name' => 'Rent', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6500', 'account_name' => 'Payroll', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6600', 'account_name' => 'Supplies - Paper Goods', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6700', 'account_name' => 'Maintenance & Repairs', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6800', 'account_name' => 'Insurance', 'account_type' => 'Expense', 'is_system_account' => true],
            ['account_code' => '6900', 'account_name' => 'Professional Services', 'account_type' => 'Expense', 'is_system_account' => true],
        ];

        foreach ($accounts as $account) {
            DB::table('chart_of_accounts')->insert(array_merge($account, [
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
```

**Run Migrations:**
```bash
php artisan migrate
php artisan db:seed --class=ChartOfAccountsSeeder
```

**Acceptance Criteria:**
- [ ] Migrations created and can run successfully
- [ ] Foreign key constraints implemented
- [ ] Indexes created for performance
- [ ] Seeder populates system accounts
- [ ] Rollback works correctly (`php artisan migrate:rollback`)
- [ ] No errors in migration

---

### Task 1.2: Backend Controllers - COA CRUD Operations
**Priority: High | Estimated Time: 8 hours**

**Description:**
Create Laravel controllers for Chart of Accounts management with direct data handling (no API/AJAX).

**Step 1: Create Model**
```bash
php artisan make:model ChartOfAccount
```

**File: `app/Models/ChartOfAccount.php`**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChartOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_code',
        'account_name',
        'account_type',
        'parent_account_id',
        'is_active',
        'is_system_account',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_account' => 'boolean',
    ];

    // Relationships
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_account_id');
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'coa_store_assignments', 'coa_id', 'store_id')
                    ->withPivot('is_global')
                    ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expenseTransactions(): HasMany
    {
        return $this->hasMany(ExpenseTransaction::class, 'coa_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('account_name', 'like', "%{$search}%")
              ->orWhere('account_code', 'like', "%{$search}%");
        });
    }
}
```

**Step 2: Create Form Request Validation**
```bash
php artisan make:request ChartOfAccountRequest
```

**File: `app/Http/Requests/ChartOfAccountRequest.php`**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        $coaId = $this->route('chart_of_account');

        return [
            'account_code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('chart_of_accounts', 'account_code')->ignore($coaId)
            ],
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|in:Revenue,COGS,Expense,Other Income',
            'parent_account_id' => 'nullable|exists:chart_of_accounts,id',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'is_global' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'account_code.required' => 'Account code is required',
            'account_code.unique' => 'This account code already exists',
            'account_name.required' => 'Account name is required',
            'account_type.required' => 'Please select an account type',
            'account_type.in' => 'Invalid account type selected',
        ];
    }
}
```

**Step 3: Create Controller**
```bash
php artisan make:controller ChartOfAccountController --resource
```

**File: `app/Http/Controllers/ChartOfAccountController.php`**
```php
<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Store;
use App\Http\Requests\ChartOfAccountRequest;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * Display a listing of COA entries
     */
    public function index(Request $request)
    {
        $query = ChartOfAccount::with(['stores', 'parent', 'creator']);

        // Apply filters
        if ($request->filled('account_type')) {
            $query->byType($request->account_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('store_id')) {
            $query->whereHas('stores', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        $coas = $query->orderBy('account_code')->paginate(25)->withQueryString();
        
        $stores = Store::all();
        $accountTypes = ['Revenue', 'COGS', 'Expense', 'Other Income'];

        return view('admin.coa.index', compact('coas', 'stores', 'accountTypes'));
    }

    /**
     * Show the form for creating a new COA entry
     */
    public function create()
    {
        $stores = Store::all();
        $parentAccounts = ChartOfAccount::active()
            ->orderBy('account_code')
            ->get();
        $accountTypes = ['Revenue', 'COGS', 'Expense', 'Other Income'];

        return view('admin.coa.create', compact('stores', 'parentAccounts', 'accountTypes'));
    }

    /**
     * Store a newly created COA entry
     */
    public function store(ChartOfAccountRequest $request)
    {
        $coa = ChartOfAccount::create([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_account_id' => $request->parent_account_id,
            'is_active' => $request->boolean('is_active', true),
            'is_system_account' => false,
            'created_by' => auth()->id(),
        ]);

        // Attach stores
        if ($request->filled('store_ids') && !$request->boolean('is_global')) {
            $syncData = [];
            foreach ($request->store_ids as $storeId) {
                $syncData[$storeId] = ['is_global' => false];
            }
            $coa->stores()->sync($syncData);
        }

        return redirect()->route('coa.index')
            ->with('success', 'Chart of Account created successfully');
    }

    /**
     * Display the specified COA entry
     */
    public function show(ChartOfAccount $chartOfAccount)
    {
        $chartOfAccount->load(['stores', 'parent', 'children', 'creator', 'expenseTransactions']);
        
        return view('admin.coa.show', compact('chartOfAccount'));
    }

    /**
     * Show the form for editing the specified COA entry
     */
    public function edit(ChartOfAccount $chartOfAccount)
    {
        // Prevent editing system accounts
        if ($chartOfAccount->is_system_account) {
            return redirect()->route('coa.index')
                ->with('error', 'System accounts cannot be edited');
        }

        $stores = Store::all();
        $parentAccounts = ChartOfAccount::active()
            ->where('id', '!=', $chartOfAccount->id)
            ->orderBy('account_code')
            ->get();
        $accountTypes = ['Revenue', 'COGS', 'Expense', 'Other Income'];

        return view('admin.coa.edit', compact('chartOfAccount', 'stores', 'parentAccounts', 'accountTypes'));
    }

    /**
     * Update the specified COA entry
     */
    public function update(ChartOfAccountRequest $request, ChartOfAccount $chartOfAccount)
    {
        // Prevent editing system accounts
        if ($chartOfAccount->is_system_account) {
            return redirect()->route('coa.index')
                ->with('error', 'System accounts cannot be edited');
        }

        $chartOfAccount->update([
            'account_code' => $request->account_code,
            'account_name' => $request->account_name,
            'account_type' => $request->account_type,
            'parent_account_id' => $request->parent_account_id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Update store assignments
        if ($request->filled('store_ids') && !$request->boolean('is_global')) {
            $syncData = [];
            foreach ($request->store_ids as $storeId) {
                $syncData[$storeId] = ['is_global' => $request->boolean('is_global', false)];
            }
            $chartOfAccount->stores()->sync($syncData);
        } else {
            $chartOfAccount->stores()->detach();
        }

        return redirect()->route('coa.index')
            ->with('success', 'Chart of Account updated successfully');
    }

    /**
     * Remove (deactivate) the specified COA entry
     */
    public function destroy(ChartOfAccount $chartOfAccount)
    {
        // Prevent deleting system accounts
        if ($chartOfAccount->is_system_account) {
            return redirect()->route('coa.index')
                ->with('error', 'System accounts cannot be deleted');
        }

        // Check if has transactions
        if ($chartOfAccount->expenseTransactions()->exists()) {
            return redirect()->route('coa.index')
                ->with('error', 'Cannot delete account with linked transactions. Deactivate it instead.');
        }

        // Soft delete (deactivate)
        $chartOfAccount->update(['is_active' => false]);

        return redirect()->route('coa.index')
            ->with('success', 'Chart of Account deactivated successfully');
    }
}
```

**Step 4: Create Routes**

**File: `routes/web.php`**
```php
use App\Http\Controllers\ChartOfAccountController;

Route::middleware(['auth', 'role:super_admin,admin,owner'])->group(function () {
    
    // Chart of Accounts Routes
    Route::resource('coa', ChartOfAccountController::class)->names([
        'index' => 'coa.index',
        'create' => 'coa.create',
        'store' => 'coa.store',
        'show' => 'coa.show',
        'edit' => 'coa.edit',
        'update' => 'coa.update',
        'destroy' => 'coa.destroy',
    ]);
    
});
```

**Step 5: Create Middleware for Permission Check**
```bash
php artisan make:middleware CheckRole
```

**File: `app/Http/Middleware/CheckRole.php`**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $userRole = auth()->user()->role;

        if (!in_array($userRole, $roles)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

**Register Middleware in `bootstrap/app.php` (Laravel 12):**
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

**Step 6: Add Helper Method to User Model**

**File: `app/Models/User.php`** (Add this method)
```php
public function hasRole($roles)
{
    if (is_string($roles)) {
        return $this->role === $roles;
    }
    
    if (is_array($roles)) {
        return in_array($this->role, $roles);
    }
    
    return false;
}
```

**Acceptance Criteria:**
- [ ] All CRUD methods working with direct data handling
- [ ] No AJAX/API calls - all form submissions use traditional POST/PUT/DELETE
- [ ] Input validation using Form Requests
- [ ] Permission middleware implemented
- [ ] Relationships loading correctly
- [ ] Flash messages for success/error feedback
- [ ] Redirects working properly
- [ ] Unit tests passing

---

### Task 1.3: Frontend - COA Management Interface
**Priority: High | Estimated Time: 12 hours**

**Description:**
Create admin interface for managing Chart of Accounts using Bootstrap 5, Blade templates, and traditional form submissions (no AJAX).

**Step 1: Create Blade Views**

**File: `resources/views/admin/coa/index.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">Chart of Accounts</h2>
            <p class="text-muted">Manage your accounting categories</p>
        </div>
        <div class="col-md-6 text-end">
            @if(auth()->user()->hasRole(['super_admin', 'admin']))
            <a href="{{ route('coa.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Account
            </a>
            @endif
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('coa.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Account Type</label>
                    <select class="form-select" name="account_type">
                        <option value="">All Types</option>
                        @foreach($accountTypes as $type)
                        <option value="{{ $type }}" {{ request('account_type') == $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" value="{{ request('search') }}" 
                           placeholder="Search by name or code...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-secondary flex-fill">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- COA Table -->
    <div class="card">
        <div class="card-body">
            @if($coas->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Stores</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($coas as $coa)
                        <tr>
                            <td><strong>{{ $coa->account_code }}</strong></td>
                            <td>
                                {{ $coa->account_name }}
                                @if($coa->is_system_account)
                                <span class="badge bg-info ms-2">System</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $coa->account_type }}</span></td>
                            <td>
                                @if($coa->stores->count() === 0)
                                <span class="text-muted">All Stores</span>
                                @else
                                <span class="badge bg-light text-dark">{{ $coa->stores->count() }} store(s)</span>
                                @endif
                            </td>
                            <td>
                                @if($coa->is_active)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('coa.show', $coa) }}" class="btn btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->hasRole(['super_admin', 'admin']) && !$coa->is_system_account)
                                    <a href="{{ route('coa.edit', $coa) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal{{ $coa->id }}" 
                                            title="Deactivate">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </div>

                                <!-- Delete Confirmation Modal -->
                                @if(auth()->user()->hasRole(['super_admin', 'admin']) && !$coa->is_system_account)
                                <div class="modal fade" id="deleteModal{{ $coa->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Confirm Deactivation</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to deactivate <strong>{{ $coa->account_name }}</strong>?</p>
                                                <p class="text-muted mb-0">This action can be reversed by editing the account later.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="{{ route('coa.destroy', $coa) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger">Deactivate</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                    Showing {{ $coas->firstItem() }} to {{ $coas->lastItem() }} of {{ $coas->total() }} entries
                </div>
                <div>
                    {{ $coas->links() }}
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <p class="text-muted mt-3">No accounts found</p>
                @if(auth()->user()->hasRole(['super_admin', 'admin']))
                <a href="{{ route('coa.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add First Account
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
```

**File: `resources/views/admin/coa/create.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'Create Chart of Account')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Add Chart of Account</h2>
                    <p class="text-muted">Create a new accounting category</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>

            <!-- Form Card -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_code') is-invalid @enderror" 
                                       id="account_code" name="account_code" value="{{ old('account_code') }}" 
                                       maxlength="10" required>
                                @error('account_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">E.g., 4000, 5100, 6300</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('account_type') is-invalid @enderror" 
                                        id="account_type" name="account_type" required>
                                    <option value="">Select Type</option>
                                    @foreach($accountTypes as $type)
                                    <option value="{{ $type }}" {{ old('account_type') == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                                   id="account_name" name="account_name" value="{{ old('account_name') }}" 
                                   maxlength="100" required>
                            @error('account_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parent_account_id" class="form-label">Parent Account (Optional)</label>
                            <select class="form-select" id="parent_account_id" name="parent_account_id">
                                <option value="">None (Top Level)</option>
                                @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}" {{ old('parent_account_id') == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->account_code }} - {{ $parent->account_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" 
                                       value="1" {{ old('is_global') ? 'checked' : '' }}
                                       onchange="toggleStoreSelection()">
                                <label class="form-check-label" for="is_global">
                                    Available to All Stores
                                </label>
                            </div>
                            <div id="storeSelection" class="border rounded p-3 {{ old('is_global') ? 'd-none' : '' }}">
                                @foreach($stores as $store)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="store_ids[]" 
                                           value="{{ $store->id }}" id="store{{ $store->id }}"
                                           {{ in_array($store->id, old('store_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="store{{ $store->id }}">
                                        {{ $store->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Account
                            </button>
                            <a href="{{ route('coa.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleStoreSelection() {
    const isGlobal = document.getElementById('is_global').checked;
    const storeSelection = document.getElementById('storeSelection');
    
    if (isGlobal) {
        storeSelection.classList.add('d-none');
        // Uncheck all stores
        document.querySelectorAll('input[name="store_ids[]"]').forEach(cb => cb.checked = false);
    } else {
        storeSelection.classList.remove('d-none');
    }
}
</script>
@endpush
@endsection
```

**File: `resources/views/admin/coa/edit.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'Edit Chart of Account')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">Edit Chart of Account</h2>
                    <p class="text-muted">Update account: {{ $chartOfAccount->account_name }}</p>
                </div>
                <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to List
                </a>
            </div>

            <!-- Form Card -->
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('coa.update', $chartOfAccount) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="account_code" class="form-label">Account Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_code') is-invalid @enderror" 
                                       id="account_code" name="account_code" 
                                       value="{{ old('account_code', $chartOfAccount->account_code) }}" 
                                       maxlength="10" required>
                                @error('account_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
                                <select class="form-select @error('account_type') is-invalid @enderror" 
                                        id="account_type" name="account_type" required>
                                    <option value="">Select Type</option>
                                    @foreach($accountTypes as $type)
                                    <option value="{{ $type }}" 
                                        {{ old('account_type', $chartOfAccount->account_type) == $type ? 'selected' : '' }}>
                                        {{ $type }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('account_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="account_name" class="form-label">Account Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('account_name') is-invalid @enderror" 
                                   id="account_name" name="account_name" 
                                   value="{{ old('account_name', $chartOfAccount->account_name) }}" 
                                   maxlength="100" required>
                            @error('account_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="parent_account_id" class="form-label">Parent Account (Optional)</label>
                            <select class="form-select" id="parent_account_id" name="parent_account_id">
                                <option value="">None (Top Level)</option>
                                @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}" 
                                    {{ old('parent_account_id', $chartOfAccount->parent_account_id) == $parent->id ? 'selected' : '' }}>
                                    {{ $parent->account_code }} - {{ $parent->account_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Store Assignment</label>
                            @php
                                $assignedStoreIds = $chartOfAccount->stores->pluck('id')->toArray();
                                $isGlobal = empty($assignedStoreIds);
                            @endphp
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="is_global" name="is_global" 
                                       value="1" {{ old('is_global', $isGlobal) ? 'checked' : '' }}
                                       onchange="toggleStoreSelection()">
                                <label class="form-check-label" for="is_global">
                                    Available to All Stores
                                </label>
                            </div>
                            <div id="storeSelection" class="border rounded p-3 {{ old('is_global', $isGlobal) ? 'd-none' : '' }}">
                                @foreach($stores as $store)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="store_ids[]" 
                                           value="{{ $store->id }}" id="store{{ $store->id }}"
                                           {{ in_array($store->id, old('store_ids', $assignedStoreIds)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="store{{ $store->id }}">
                                        {{ $store->name }}
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       value="1" {{ old('is_active', $chartOfAccount->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Account
                            </button>
                            <a href="{{ route('coa.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleStoreSelection() {
    const isGlobal = document.getElementById('is_global').checked;
    const storeSelection = document.getElementById('storeSelection');
    
    if (isGlobal) {
        storeSelection.classList.add('d-none');
        document.querySelectorAll('input[name="store_ids[]"]').forEach(cb => cb.checked = false);
    } else {
        storeSelection.classList.remove('d-none');
    }
}
</script>
@endpush
@endsection
```

**File: `resources/views/admin/coa/show.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'View Chart of Account')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="mb-0">{{ $chartOfAccount->account_name }}</h2>
                    <p class="text-muted">Account Code: {{ $chartOfAccount->account_code }}</p>
                </div>
                <div class="d-flex gap-2">
                    @if(auth()->user()->hasRole(['super_admin', 'admin']) && !$chartOfAccount->is_system_account)
                    <a href="{{ route('coa.edit', $chartOfAccount) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    @endif
                    <a href="{{ route('coa.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Account Details -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Account Details</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Account Code:</th>
                                    <td><strong>{{ $chartOfAccount->account_code }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Account Name:</th>
                                    <td>{{ $chartOfAccount->account_name }}</td>
                                </tr>
                                <tr>
                                    <th>Account Type:</th>
                                    <td><span class="badge bg-secondary">{{ $chartOfAccount->account_type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Parent Account:</th>
                                    <td>
                                        @if($chartOfAccount->parent)
                                        <a href="{{ route('coa.show', $chartOfAccount->parent) }}">
                                            {{ $chartOfAccount->parent->account_code }} - {{ $chartOfAccount->parent->account_name }}
                                        </a>
                                        @else
                                        <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($chartOfAccount->is_active)
                                        <span class="badge bg-success">Active</span>
                                        @else
                                        <span class="badge bg-danger">Inactive</span>
                                        @endif
                                        @if($chartOfAccount->is_system_account)
                                        <span class="badge bg-info ms-2">System Account</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>{{ $chartOfAccount->creator->name ?? 'System' }}</td>
                                </tr>
                                <tr>
                                    <th>Created At:</th>
                                    <td>{{ $chartOfAccount->created_at->format('M d, Y h:i A') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Store Assignments -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Store Assignments</h5>
                        </div>
                        <div class="card-body">
                            @if($chartOfAccount->stores->count() > 0)
                            <ul class="list-group">
                                @foreach($chartOfAccount->stores as $store)
                                <li class="list-group-item">
                                    <i class="bi bi-shop me-2"></i>{{ $store->name }}
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted mb-0">
                                <i class="bi bi-globe me-2"></i>Available to all stores
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub Accounts -->
            @if($chartOfAccount->children->count() > 0)
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Sub-Accounts ({{ $chartOfAccount->children->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($chartOfAccount->children as $child)
                                <tr>
                                    <td><strong>{{ $child->account_code }}</strong></td>
                                    <td>{{ $child->account_name }}</td>
                                    <td><span class="badge bg-secondary">{{ $child->account_type }}</span></td>
                                    <td>
                                        @if($child->is_active)
                                        <span class="badge bg-success">Active</span>
                                        @else
                                        <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('coa.show', $child) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Transaction History -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Transaction Summary</h5>
                </div>
                <div class="card-body">
                    @if($chartOfAccount->expenseTransactions->count() > 0)
                    <p class="mb-0">
                        <i class="bi bi-receipt me-2"></i>
                        This account has <strong>{{ $chartOfAccount->expenseTransactions->count() }}</strong> linked transactions
                    </p>
                    @else
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        No transactions linked to this account yet
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

**Step 2: Update Bootstrap Pagination**

**File: `app/Providers/AppServiceProvider.php`**
```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Use Bootstrap 5 for pagination
        Paginator::useBootstrapFive();
    }
}
```

**Acceptance Criteria:**
- [ ] List page displaying all COA entries with filters
- [ ] Create page with form submission
- [ ] Edit page loading existing data
- [ ] Show page displaying full account details
- [ ] Delete confirmation using Bootstrap modal
- [ ] Form validation displaying Laravel errors
- [ ] Flash messages showing success/error
- [ ] Pagination using Bootstrap 5 styling
- [ ] No AJAX/API calls - all traditional form submissions
- [ ] Responsive design (mobile-friendly)
- [ ] Permission-based buttons visibility
**Priority: High | Estimated Time: 12 hours**

**Description:**
Create admin interface for managing Chart of Accounts using Bootstrap 5 and Blade templates.

**Step 1: Create Controller for Views**
```bash
php artisan make:controller Admin/ChartOfAccountViewController
```

**File: `app/Http/Controllers/Admin/ChartOfAccountViewController.php`**
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountViewController extends Controller
{
    public function index()
    {
        return view('admin.coa.index');
    }
}
```

**Step 2: Create Routes**

**File: `routes/web.php`**
```php
use App\Http\Controllers\Admin\ChartOfAccountViewController;

Route::middleware(['auth', 'role:super_admin,admin,owner'])->prefix('admin')->group(function () {
    Route::get('/chart-of-accounts', [ChartOfAccountViewController::class, 'index'])->name('admin.coa.index');
});
```

**Step 3: Create Blade Templates**

**File: `resources/views/admin/coa/index.blade.php`**
```blade
@extends('layouts.app')

@section('title', 'Chart of Accounts')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">Chart of Accounts</h2>
            <p class="text-muted">Manage your accounting categories</p>
        </div>
        <div class="col-md-6 text-end">
            @can('create', App\Models\ChartOfAccount::class)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#coaModal" onclick="openCreateModal()">
                <i class="bi bi-plus-circle"></i> Add Account
            </button>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Account Type</label>
                    <select class="form-select" id="filterAccountType" name="account_type">
                        <option value="">All Types</option>
                        <option value="Revenue">Revenue</option>
                        <option value="COGS">COGS</option>
                        <option value="Expense">Expense</option>
                        <option value="Other Income">Other Income</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="filterStatus" name="is_active">
                        <option value="">All Status</option>
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="filterSearch" name="search" placeholder="Search by name or code...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- COA Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="coaTable">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th>Stores</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="coaTableBody">
                        <!-- Data will be loaded via JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-end mb-0" id="pagination">
                    <!-- Pagination will be loaded via JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="coaModal" tabindex="-1" aria-labelledby="coaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="coaModalLabel">Add Chart of Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="coaForm">
                <div class="modal-body">
                    <input type="hidden" id="coaId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="accountCode" class="form-label">Account Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="accountCode" name="account_code" required maxlength="10">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="accountType" class="form-label">Account Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="accountType" name="account_type" required>
                                <option value="">Select Type</option>
                                <option value="Revenue">Revenue</option>
                                <option value="COGS">COGS</option>
                                <option value="Expense">Expense</option>
                                <option value="Other Income">Other Income</option>
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="accountName" class="form-label">Account Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="accountName" name="account_name" required maxlength="100">
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="parentAccount" class="form-label">Parent Account (Optional)</label>
                        <select class="form-select" id="parentAccount" name="parent_account_id">
                            <option value="">None (Top Level)</option>
                            <!-- Options loaded via JavaScript -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Store Assignment</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="isGlobal" name="is_global">
                            <label class="form-check-label" for="isGlobal">
                                Available to All Stores
                            </label>
                        </div>
                        <div id="storeSelection" class="border rounded p-3">
                            <!-- Store checkboxes loaded via JavaScript -->
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                            <label class="form-check-label" for="isActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Save Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deactivation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate this account?</p>
                <p class="text-muted mb-0">This action can be reversed by editing the account later.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Deactivate</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Global variables
let currentPage = 1;
let deleteCoaId = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCOAs();
    loadStores();
    loadParentAccounts();
    
    // Filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadCOAs();
    });
    
    // COA form submission
    document.getElementById('coaForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveCOA();
    });
    
    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        deleteCOA(deleteCoaId);
    });
    
    // Toggle store selection based on global checkbox
    document.getElementById('isGlobal').addEventListener('change', function() {
        document.getElementById('storeSelection').style.display = this.checked ? 'none' : 'block';
    });
});

// Load COA data
function loadCOAs() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData);
    params.append('page', currentPage);
    
    showLoader();
    
    fetch(`/api/coa?${params.toString()}`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        renderTable(data.data);
        renderPagination(data);
        hideLoader();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading data', 'error');
        hideLoader();
    });
}

// Render table rows
function renderTable(coas) {
    const tbody = document.getElementById('coaTableBody');
    tbody.innerHTML = '';
    
    if (coas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No accounts found</td></tr>';
        return;
    }
    
    coas.forEach(coa => {
        const row = `
            <tr>
                <td><strong>${coa.account_code}</strong></td>
                <td>
                    ${coa.account_name}
                    ${coa.is_system_account ? '<span class="badge bg-info ms-2">System</span>' : ''}
                </td>
                <td><span class="badge bg-secondary">${coa.account_type}</span></td>
                <td>
                    ${coa.stores.length === 0 ? '<span class="text-muted">All Stores</span>' : 
                      `<span class="badge bg-light text-dark">${coa.stores.length} store(s)</span>`}
                </td>
                <td>
                    ${coa.is_active ? 
                      '<span class="badge bg-success">Active</span>' : 
                      '<span class="badge bg-danger">Inactive</span>'}
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary" onclick="editCOA(${coa.id})" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        ${!coa.is_system_account ? `
                        <button class="btn btn-outline-danger" onclick="openDeleteModal(${coa.id})" title="Deactivate">
                            <i class="bi bi-trash"></i>
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Render pagination
function renderPagination(data) {
    const pagination = document.getElementById('pagination');
    pagination.innerHTML = '';
    
    if (data.last_page <= 1) return;
    
    // Previous button
    pagination.innerHTML += `
        <li class="page-item ${data.current_page === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${data.current_page - 1}); return false;">Previous</a>
        </li>
    `;
    
    // Page numbers
    for (let i = 1; i <= data.last_page; i++) {
        if (i === 1 || i === data.last_page || (i >= data.current_page - 2 && i <= data.current_page + 2)) {
            pagination.innerHTML += `
                <li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (i === data.current_page - 3 || i === data.current_page + 3) {
            pagination.innerHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    pagination.innerHTML += `
        <li class="page-item ${data.current_page === data.last_page ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="changePage(${data.current_page + 1}); return false;">Next</a>
        </li>
    `;
}

function changePage(page) {
    currentPage = page;
    loadCOAs();
}

// Open create modal
function openCreateModal() {
    document.getElementById('coaModalLabel').textContent = 'Add Chart of Account';
    document.getElementById('coaForm').reset();
    document.getElementById('coaId').value = '';
    clearValidationErrors();
}

// Edit COA
function editCOA(id) {
    fetch(`/api/coa/${id}`, {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(coa => {
        document.getElementById('coaModalLabel').textContent = 'Edit Chart of Account';
        document.getElementById('coaId').value = coa.id;
        document.getElementById('accountCode').value = coa.account_code;
        document.getElementById('accountName').value = coa.account_name;
        document.getElementById('accountType').value = coa.account_type;
        document.getElementById('parentAccount').value = coa.parent_account_id || '';
        document.getElementById('isActive').checked = coa.is_active;
        
        // Handle store assignments
        const isGlobal = coa.stores.length === 0;
        document.getElementById('isGlobal').checked = isGlobal;
        document.getElementById('storeSelection').style.display = isGlobal ? 'none' : 'block';
        
        if (!isGlobal) {
            const storeIds = coa.stores.map(s => s.id);
            document.querySelectorAll('input[name="store_ids[]"]').forEach(checkbox => {
                checkbox.checked = storeIds.includes(parseInt(checkbox.value));
            });
        }
        
        new bootstrap.Modal(document.getElementById('coaModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error loading account details', 'error');
    });
}

// Save COA
function saveCOA() {
    const form = document.getElementById('coaForm');
    const formData = new FormData(form);
    const id = document.getElementById('coaId').value;
    
    const data = {
        account_code: formData.get('account_code'),
        account_name: formData.get('account_name'),
        account_type: formData.get('account_type'),
        parent_account_id: formData.get('parent_account_id') || null,
        is_global: formData.get('is_global') === 'on',
        is_active: formData.get('is_active') === 'on',
        store_ids: formData.getAll('store_ids[]').map(id => parseInt(id))
    };
    
    const url = id ? `/api/coa/${id}` : '/api/coa';
    const method = id ? 'PUT' : 'POST';
    
    setButtonLoading('saveBtn', true);
    
    fetch(url, {
        method: method,
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.errors) {
            displayValidationErrors(result.errors);
        } else {
            showToast(result.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('coaModal')).hide();
            loadCOAs();
            form.reset();
        }
        setButtonLoading('saveBtn', false);
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error saving account', 'error');
        setButtonLoading('saveBtn', false);
    });
}

// Delete modal
function openDeleteModal(id) {
    deleteCoaId = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

// Delete COA
function deleteCOA(id) {
    fetch(`/api/coa/${id}`, {
        method: 'DELETE',
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(result => {
        showToast(result.message, result.error ? 'error' : 'success');
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        if (!result.error) {
            loadCOAs();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error deleting account', 'error');
    });
}

// Load stores for assignment
function loadStores() {
    fetch('/api/stores', {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(stores => {
        const container = document.getElementById('storeSelection');
        container.innerHTML = '';
        stores.forEach(store => {
            container.innerHTML += `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="store_ids[]" value="${store.id}" id="store${store.id}">
                    <label class="form-check-label" for="store${store.id}">${store.name}</label>
                </div>
            `;
        });
    });
}

// Load parent accounts
function loadParentAccounts() {
    fetch('/api/coa?per_page=1000', {
        headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById('parentAccount');
        data.data.forEach(coa => {
            const option = document.createElement('option');
            option.value = coa.id;
            option.textContent = `${coa.account_code} - ${coa.account_name}`;
            select.appendChild(option);
        });
    });
}

// Utility functions
function getAuthToken() {
    return document.querySelector('meta[name="api-token"]')?.content || '';
}

function showLoader() {
    // Implement your loader logic
}

function hideLoader() {
    // Implement your loader logic
}

function showToast(message, type = 'success') {
    // Implement Bootstrap toast notification
    const toast = `
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    // Add to toast container and show
}

function setButtonLoading(btnId, loading) {
    const btn = document.getElementById(btnId);
    const spinner = btn.querySelector('.spinner-border');
    if (loading) {
        btn.disabled = true;
        spinner?.classList.remove('d-none');
    } else {
        btn.disabled = false;
        spinner?.classList.add('d-none');
    }
}

function displayValidationErrors(errors) {
    clearValidationErrors();
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = input.nextElementSibling;
            if (feedback && feedback.classList.contains('invalid-feedback')) {
                feedback.textContent = errors[field][0];
            }
        }
    });
}

function clearValidationErrors() {
    document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}
</script>
@endpush

@push('styles')
<style>
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        cursor: pointer;
    }
</style>
@endpush
```

**Acceptance Criteria:**
- [ ] List page displaying all COA entries
- [ ] Filters working (type, status, search)
- [ ] Pagination working
- [ ] Create modal opens and saves data
- [ ] Edit modal loads existing data
- [ ] Delete/deactivate confirmation working
- [ ] Form validation displays errors
- [ ] Store assignment functional
- [ ] Responsive design (mobile-friendly)
- [ ] Loading states implemented
- [ ] Toast notifications working
- [ ] Permission-based buttons (only super admin sees add/edit/delete)
- [ ] Bootstrap 5 styling consistent

---

## MILESTONE 2: Vendor Management System
**Estimated Time: 1-1.5 weeks**

### Task 2.1: Database Schema - Vendors
**Priority: High | Estimated Time: 3 hours**

**Description:**
Create vendor management tables with COA linking.

**Tables to Create:**

1. **`vendors` table:**
   - `id` (Primary Key)
   - `vendor_name` (VARCHAR, required)
   - `vendor_identifier` (VARCHAR, nullable - for matching CSV imports)
   - `default_coa_id` (Foreign Key to chart_of_accounts, nullable)
   - `vendor_type` (ENUM: 'Food', 'Beverage', 'Supplies', 'Utilities', 'Services', 'Other')
   - `contact_name` (VARCHAR, nullable)
   - `contact_email` (VARCHAR, nullable)
   - `contact_phone` (VARCHAR, nullable)
   - `address` (TEXT, nullable)
   - `notes` (TEXT, nullable)
   - `is_active` (Boolean, default true)
   - `created_at` (Timestamp)
   - `updated_at` (Timestamp)
   - `created_by` (Foreign Key to users)

2. **`vendor_store_assignments` table:**
   - `id` (Primary Key)
   - `vendor_id` (Foreign Key to vendors)
   - `store_id` (Foreign Key to stores)
   - `is_global` (Boolean)
   - Unique constraint on (vendor_id, store_id)

3. **`vendor_aliases` table:** (for CSV matching)
   - `id` (Primary Key)
   - `vendor_id` (Foreign Key to vendors)
   - `alias` (VARCHAR - variations of vendor name)
   - `source` (ENUM: 'bank', 'credit_card', 'manual')
   - Unique constraint on (alias, source)

**Indexes:**
- vendor_name (for search)
- vendor_identifier (for CSV matching)
- alias (for fast lookup)

**Acceptance Criteria:**
- [ ] All tables created with proper relationships
- [ ] Indexes created
- [ ] Migration and rollback scripts
- [ ] Sample data inserted (10-15 common vendors)

**Sample Vendors Data:**
```sql
INSERT INTO vendors (vendor_name, vendor_identifier, vendor_type, default_coa_id) VALUES
('Sam''s Club', 'SAMSCLUB', 'Food', 5000),
('Sysco Foods', 'SYSCO', 'Food', 5000),
('Coca-Cola', 'COCA-COLA', 'Beverage', 5100),
('Spectrum', 'SPECTRUM', 'Utilities', 6300),
('AT&T', 'ATT', 'Utilities', 6300),
('Grubhub', 'GRUBHUB', 'Services', 6100),
('Square', 'SQ *SQUARE', 'Services', 6000),
('Travelers Insurance', 'TRAVELERS', 'Services', 6800);
```

---

### Task 2.2: Backend API - Vendor Management
**Priority: High | Estimated Time: 8 hours**

**Description:**
Create API endpoints for vendor CRUD and COA linking.

**Endpoints to Create:**

1. **GET /api/vendors**
   - Query params: `store_id`, `vendor_type`, `is_active`, `search`, `has_coa`
   - Response: List with default COA details
   - Permission: All authenticated users

2. **GET /api/vendors/:id**
   - Response: Full vendor details with COA and store assignments
   - Permission: All authenticated users

3. **POST /api/vendors**
   - Body: `{vendor_name, vendor_identifier, vendor_type, default_coa_id, store_ids[], contact_info, notes}`
   - Validation:
     - vendor_name required, max 100 chars
     - default_coa_id must exist if provided
     - vendor_identifier unique if provided
   - Auto-create initial alias from vendor_name
   - Permission: Owner, Admin, Super Admin

4. **PUT /api/vendors/:id**
   - Body: Same as POST
   - Permission: Owner, Admin, Super Admin

5. **DELETE /api/vendors/:id**
   - Soft delete (set is_active = false)
   - Check if vendor has transactions
   - Permission: Admin, Super Admin

6. **POST /api/vendors/:id/aliases**
   - Body: `{alias, source}`
   - Add new alias for CSV matching
   - Permission: Owner, Admin, Super Admin

7. **GET /api/vendors/match**
   - Query params: `description` (from CSV)
   - Fuzzy match vendor by name or alias
   - Return best match with confidence score
   - Permission: All authenticated users

**Acceptance Criteria:**
- [ ] All endpoints implemented
- [ ] Fuzzy matching algorithm for vendor identification
- [ ] Unit tests (80%+ coverage)
- [ ] API documentation

---

### Task 2.3: Frontend - Vendor Management Interface
**Priority: High | Estimated Time: 10 hours**

**Description:**
Create vendor management UI with COA linking.

**Pages/Components:**

1. **Vendor List Page** (`/vendors`)
   - Table: Name, Type, Default COA, Stores, Status, Actions
   - Filters: Type, Status, Has COA Assigned
   - Search: By name or identifier
   - Actions: Edit, View Details, Deactivate

2. **Vendor Create/Edit Form**
   - Form fields:
     - Vendor Name* (text)
     - Vendor Identifier (text, for CSV matching)
     - Vendor Type* (dropdown)
     - Default COA Category (searchable dropdown)
     - Store Assignment (multi-select or "All Stores")
     - Contact Information (collapsible section)
       - Contact Name
       - Email
       - Phone
     - Notes (textarea)
     - Status (toggle)
   - Save button with validation

3. **Vendor Details Modal**
   - Display all vendor info
   - Show linked transactions (if any)
   - Aliases section with Add/Remove
   - Edit button

4. **COA Assignment Helper**
   - When creating vendor, suggest COA based on vendor_type
   - Visual indicator if no COA assigned

**Acceptance Criteria:**
- [ ] All pages/components built
- [ ] COA dropdown with search functionality
- [ ] Store assignment working
- [ ] Responsive design
- [ ] Permission-based UI
- [ ] Integration with API complete

---

## MILESTONE 3: Cash Expense Integration
**Estimated Time: 1 week**

### Task 3.1: Database Schema - Expense Transactions
**Priority: High | Estimated Time: 4 hours**

**Description:**
Create unified expense ledger table for all transaction types.

**Table to Create:**

**`expense_transactions` table:**
- `id` (Primary Key, UUID)
- `transaction_type` (ENUM: 'cash', 'credit_card', 'bank_transfer', 'check')
- `transaction_date` (DATE, indexed)
- `post_date` (DATE, nullable - for credit cards)
- `store_id` (Foreign Key to stores)
- `vendor_id` (Foreign Key to vendors, nullable)
- `vendor_name_raw` (VARCHAR - original from import)
- `coa_id` (Foreign Key to chart_of_accounts)
- `amount` (DECIMAL(10,2))
- `description` (TEXT)
- `reference_number` (VARCHAR - check number, transaction ID, etc.)
- `payment_method` (ENUM: 'cash', 'credit_card', 'debit_card', 'check', 'eft', 'other')
- `card_last_four` (VARCHAR(4), nullable)
- `receipt_url` (VARCHAR, nullable)
- `notes` (TEXT, nullable)
- `is_reconciled` (Boolean, default false)
- `reconciled_date` (Timestamp, nullable)
- `reconciled_by` (Foreign Key to users, nullable)
- `needs_review` (Boolean, default false)
- `review_reason` (VARCHAR, nullable)
- `duplicate_check_hash` (VARCHAR, indexed - for duplicate detection)
- `import_batch_id` (Foreign Key, nullable - for CSV imports)
- `daily_report_id` (Foreign Key, nullable - link to cash expenses)
- `created_at` (Timestamp)
- `created_by` (Foreign Key to users)

**Indexes:**
- transaction_date, store_id (composite)
- duplicate_check_hash
- needs_review, store_id
- is_reconciled

**Acceptance Criteria:**
- [ ] Table created with all constraints
- [ ] Indexes applied
- [ ] Migration script with sample data
- [ ] Rollback script

---

### Task 3.2: Backend API - Cash Expense Import
**Priority: High | Estimated Time: 6 hours**

**Description:**
Create automated sync from daily reports to expense ledger.

**Endpoints to Create:**

1. **POST /api/expenses/sync-cash-expenses**
   - Query params: `start_date`, `end_date`, `store_id`
   - Process:
     - Fetch all cash expenses from daily_reports table
     - Check if already imported (by daily_report_id)
     - Create expense_transactions records
     - Link vendor and COA if available
     - Flag for review if vendor/COA missing
   - Response: Summary of imported records
   - Permission: Admin, Super Admin

2. **GET /api/expenses**
   - Query params: `store_id`, `start_date`, `end_date`, `transaction_type`, `needs_review`, `vendor_id`, `coa_id`
   - Response: Paginated expense list with vendor and COA details
   - Permission: Based on store access

3. **GET /api/expenses/:id**
   - Response: Full expense details
   - Permission: Based on store access

4. **PUT /api/expenses/:id**
   - Body: `{vendor_id, coa_id, notes}`
   - Update expense categorization
   - Remove from review queue if fixed
   - Permission: Owner, Admin, Super Admin

5. **POST /api/expenses/manual**
   - Body: `{transaction_date, store_id, vendor_id, coa_id, amount, payment_method, description, receipt_url, notes}`
   - Create manual expense entry
   - Permission: Manager, Owner, Admin

**Business Logic:**
- Generate `duplicate_check_hash` = MD5(store_id + date + vendor + amount)
- Auto-match vendor by name
- If vendor matched, use vendor's default_coa_id
- If no match, set needs_review = true

**Acceptance Criteria:**
- [ ] All endpoints working
- [ ] Duplicate detection logic
- [ ] Auto-categorization logic
- [ ] Unit tests
- [ ] Integration tests with daily reports

---

### Task 3.3: Frontend - Cash Expense View
**Priority: Medium | Estimated Time: 6 hours**

**Description:**
Display cash expenses from daily reports in expense ledger.

**Pages/Components:**

1. **Expense Ledger Page** (`/expenses`)
   - Table columns:
     - Date
     - Store
     - Vendor
     - Category (COA)
     - Amount
     - Type (Cash/CC/Bank)
     - Status (Reconciled/Pending/Review)
     - Actions
   - Filters:
     - Date range picker
     - Store (multi-select)
     - Transaction type
     - Status
     - Category
   - Total row at bottom
   - Export to CSV button

2. **Sync Cash Expenses Button**
   - In admin section
   - Date range selector
   - Progress indicator
   - Success summary modal

3. **Manual Expense Entry Modal**
   - Form for creating manual expenses
   - Receipt upload functionality
   - Save button

**Acceptance Criteria:**
- [ ] Table with sorting and filtering
- [ ] Sync functionality working
- [ ] Manual entry form complete
- [ ] Responsive design
- [ ] Permission-based UI

---

## MILESTONE 4: Credit Card Transaction Upload
**Estimated Time: 2 weeks**

### Task 4.1: Database Schema - CSV Import Tracking
**Priority: High | Estimated Time: 2 hours**

**Description:**
Create tables to track CSV imports and prevent duplicates.

**Tables to Create:**

1. **`import_batches` table:**
   - `id` (Primary Key, UUID)
   - `import_type` (ENUM: 'credit_card', 'bank_statement')
   - `file_name` (VARCHAR)
   - `file_hash` (VARCHAR - MD5 of file content)
   - `store_id` (Foreign Key, nullable)
   - `transaction_count` (INT)
   - `imported_count` (INT)
   - `duplicate_count` (INT)
   - `error_count` (INT)
   - `needs_review_count` (INT)
   - `date_range_start` (DATE)
   - `date_range_end` (DATE)
   - `status` (ENUM: 'processing', 'completed', 'failed')
   - `error_log` (TEXT, nullable)
   - `imported_at` (Timestamp)
   - `imported_by` (Foreign Key to users)

2. **`transaction_mapping_rules` table:** (Machine Learning component)
   - `id` (Primary Key)
   - `description_pattern` (VARCHAR - regex or keywords)
   - `vendor_id` (Foreign Key to vendors)
   - `coa_id` (Foreign Key to chart_of_accounts)
   - `confidence_score` (DECIMAL(3,2) - 0.00 to 1.00)
   - `times_used` (INT)
   - `times_correct` (INT - when user confirms)
   - `times_incorrect` (INT - when user corrects)
   - `last_used` (Timestamp)
   - `created_at` (Timestamp)

**Acceptance Criteria:**
- [ ] Tables created
- [ ] Indexes on file_hash and description_pattern
- [ ] Migration scripts

---

### Task 4.2: Backend - CSV Parser & Import Logic
**Priority: Critical | Estimated Time: 12 hours**

**Description:**
Build flexible CSV parser that handles multiple bank formats.

**Requirements:**

**CSV Format Detection:**
Support multiple formats found in uploaded files:
1. **Chase Format:** Card, Transaction Date, Post Date, Description, Category, Type, Amount, Memo
2. **Generic Format:** Date, Description, Amount, Category, Store
3. **Custom Format:** Allow admin to define column mapping

**Endpoints to Create:**

1. **POST /api/imports/preview**
   - Upload: Multipart file (CSV)
   - Response:
     - Detected format
     - Column mapping preview
     - First 10 rows parsed
     - Detected issues/warnings
   - Permission: Owner, Admin

2. **POST /api/imports/upload**
   - Upload: Multipart file + `{store_id, import_type, column_mapping}`
   - Process:
     - Validate CSV structure
     - Check file_hash for duplicates
     - Parse all rows
     - For each transaction:
       - Check duplicate_check_hash
       - Match vendor (fuzzy match + aliases)
       - Get COA from vendor default or mapping rules
       - Calculate confidence score
       - Create expense_transaction record
       - Update mapping rules statistics
     - Create import_batch record
   - Response: Import summary with review queue URL
   - Permission: Owner, Admin

3. **GET /api/imports/history**
   - Query params: `store_id`, `import_type`
   - Response: List of past imports with summaries
   - Permission: Owner, Admin

4. **GET /api/imports/:batch_id/transactions**
   - Response: All transactions from this batch
   - Permission: Owner, Admin

**Duplicate Detection Logic:**
- Generate hash: MD5(transaction_date + vendor_name + amount)
- Check if hash exists in expense_transactions
- If exists and from same import, skip
- If exists from different source, flag for review

**Vendor Matching Logic:**
1. Exact match on vendor_identifier
2. Exact match on vendor_aliases
3. Fuzzy match on vendor_name (> 85% similarity)
4. If no match, create "needs_review" flag

**Auto-Categorization Logic:**
1. If vendor matched and has default_coa_id → use it
2. Check transaction_mapping_rules for description pattern → use highest confidence
3. If no match → needs_review = true

**Acceptance Criteria:**
- [ ] CSV parser handles multiple formats
- [ ] Duplicate detection working
- [ ] Vendor matching with fuzzy logic
- [ ] Auto-categorization with confidence scoring
- [ ] Import batch tracking
- [ ] Error handling and logging
- [ ] Unit tests (85%+ coverage)
- [ ] Test with provided sample files

---

### Task 4.3: Backend - Machine Learning for Categorization
**Priority: Medium | Estimated Time: 8 hours**

**Description:**
Implement learning system that improves categorization over time.

**Endpoints to Create:**

1. **POST /api/expenses/:id/confirm-categorization**
   - Body: `{is_correct: boolean}`
   - Process:
     - If correct: Increment times_correct on matching rule
     - If incorrect: Increment times_incorrect, reduce confidence
     - If user changed COA: Create/update mapping rule
   - Update confidence scores
   - Permission: Owner, Admin

2. **POST /api/mapping-rules/train**
   - Analyze all confirmed transactions
   - Create/update mapping rules based on patterns
   - Calculate confidence scores
   - Permission: Super Admin only (run as cron job)

**Learning Algorithm:**
```
For each unique (description_pattern, vendor_id, coa_id) combination:
  confidence_score = times_correct / (times_correct + times_incorrect)
  
  If confidence_score > 0.80: Auto-apply
  If confidence_score 0.50-0.80: Suggest with review
  If confidence_score < 0.50: Require manual categorization
```

**Pattern Extraction:**
- Extract keywords from transaction descriptions
- Group similar descriptions
- Identify common patterns (e.g., "SQ *SQUARE" → Square vendor)

**Acceptance Criteria:**
- [ ] Confirmation endpoint working
- [ ] Training algorithm implemented
- [ ] Confidence scoring accurate
- [ ] Pattern extraction working
- [ ] Unit tests

---

### Task 4.4: Frontend - CSV Upload Interface
**Priority: High | Estimated Time: 12 hours**

**Description:**
Build user-friendly CSV upload wizard with preview.

**Components to Build:**

1. **CSV Upload Wizard** (`/imports/upload`)
   
   **Step 1: File Selection**
   - Drag-and-drop area or file picker
   - File type validation (.csv only)
   - File size check (max 10MB)
   - Store selection dropdown
   - Import type selection (Credit Card / Bank)

   **Step 2: Preview & Mapping**
   - Display parsed columns
   - Show first 10 rows
   - Column mapping interface:
     - Auto-detected mappings (highlighted in green)
     - Manual mapping dropdowns for unrecognized columns
     - Required fields marked with *
   - Data validation summary:
     - Date format check
     - Amount format check
     - Missing required fields
   - Warning messages for issues

   **Step 3: Review Matches**
   - Show vendor matching results:
     - Auto-matched (green badge)
     - Needs review (yellow badge)
     - Not matched (red badge)
   - Show COA categorization:
     - Auto-categorized (green)
     - Suggested (yellow with confidence %)
     - Needs manual selection (red)
   - Allow inline editing before import

   **Step 4: Confirm & Import**
   - Summary statistics:
     - Total transactions
     - Auto-categorized
     - Need review
     - Duplicates detected
   - Progress bar during import
   - Success screen with:
     - Import summary
     - Link to review queue
     - Link to expense ledger

2. **Import History Page** (`/imports/history`)
   - Table: Date, File Name, Store, Type, Total, Auto-categorized, Needs Review, Status
   - Click row to see details
   - Re-process failed imports button

**UX Requirements:**
- Multi-step progress indicator
- Clear error messages with solutions
- Ability to go back and fix issues
- Cancel import option
- Loading states for all steps

**Acceptance Criteria:**
- [ ] All wizard steps working
- [ ] Preview showing correctly
- [ ] Column mapping functional
- [ ] Import process with progress tracking
- [ ] Error handling throughout
- [ ] Tested with sample CSV files
- [ ] Mobile responsive
- [ ] Accessible

---

## MILESTONE 5: Review & Exception Handling
**Estimated Time: 1 week**

### Task 5.1: Backend - Review Queue API
**Priority: High | Estimated Time: 6 hours**

**Description:**
Create endpoints for managing transactions that need review.

**Endpoints to Create:**

1. **GET /api/expenses/review-queue**
   - Query params: `store_id`, `review_reason`
   - Response: All transactions with needs_review = true
   - Group by: reason (No vendor, No category, Duplicate, Other)
   - Permission: Owner, Admin

2. **POST /api/expenses/:id/resolve**
   - Body: `{vendor_id, coa_id, create_mapping_rule: boolean, notes}`
   - Process:
     - Update transaction with correct vendor/COA
     - Set needs_review = false
     - If create_mapping_rule = true:
       - Create new mapping rule from this transaction
       - Set initial confidence to 0.75
   - Permission: Owner, Admin

3. **POST /api/expenses/bulk-resolve**
   - Body: `{expense_ids[], vendor_id, coa_id, create_mapping_rule}`
   - Apply same categorization to multiple transactions
   - Permission: Owner, Admin

4. **GET /api/expenses/review-stats**
   - Response: Count of items needing review by store and reason
   - Display in dashboard
   - Permission: All authenticated users (based on store access)

**Acceptance Criteria:**
- [ ] All endpoints implemented
- [ ] Bulk operations working
- [ ] Mapping rule creation on resolve
- [ ] Unit tests

---

### Task 5.2: Frontend - Review Queue Interface
**Priority: High | Estimated Time: 10 hours**

**Description:**
Build interface for reviewing and categorizing unmatched transactions.

**Pages/Components:**

1. **Review Queue Dashboard** (`/expenses/review`)
   
   **Summary Cards:**
   - Total Pending Review
   - No Vendor Assigned (count)
   - No Category Assigned (count)
   - Possible Duplicates (count)
   - By Store breakdown
   
   **Grouped Lists:**
   Group transactions by issue type with collapsible sections

2. **Transaction Review Card** (individual item)
   - Display:
     - Date
     - Raw description from CSV
     - Amount
     - Current store
     - Issue badges (no vendor, no category)
   - Actions:
     - Vendor selector (searchable dropdown with "Create New" option)
     - COA category selector (searchable dropdown)
     - "Create mapping rule" checkbox
     - Notes field
     - Save button
     - Skip button (leave for later)
   - Smart Suggestions:
     - Show similar past transactions
     - Suggest vendor based on fuzzy match (with confidence %)
     - Suggest category based on mapping rules

3. **Bulk Categorization Tool**
   - Select multiple similar transactions
   - Apply vendor + category to all selected
   - Preview changes before saving
   - Save all button

4. **Duplicate Resolution Modal**
   - Show both transactions side by side
   - Mark which one to keep
   - Option to merge or delete duplicate

**UX Features:**
- Keyboard shortcuts for faster review (↑/↓ arrows, Tab, Enter)
- Auto-save on categorization
- Undo last action button
- Progress indicator (X of Y reviewed)
- Celebrate when queue is empty! 🎉

**Acceptance Criteria:**
- [ ] All components built
- [ ] Suggestions working
- [ ] Bulk actions functional
- [ ] Keyboard shortcuts
- [ ] Responsive design
- [ ] Integration complete

---

## MILESTONE 6: Bank Reconciliation
**Estimated Time: 1.5 weeks**

### Task 6.1: Database Schema - Bank Accounts
**Priority: High | Estimated Time: 2 hours**

**Description:**
Create tables for bank account tracking and reconciliation.

**Tables to Create:**

1. **`bank_accounts` table:**
   - `id` (Primary Key)
   - `bank_name` (VARCHAR)
   - `account_number_last_four` (VARCHAR(4))
   - `account_type` (ENUM: 'checking', 'savings', 'credit_card')
   - `store_id` (Foreign Key, nullable - null = corporate account)
   - `opening_balance` (DECIMAL(10,2))
   - `current_balance` (DECIMAL(10,2))
   - `last_reconciled_date` (DATE, nullable)
   - `is_active` (Boolean)
   - `created_at` (Timestamp)

2. **`bank_transactions` table:**
   - `id` (Primary Key, UUID)
   - `bank_account_id` (Foreign Key to bank_accounts)
   - `transaction_date` (DATE)
   - `post_date` (DATE, nullable)
   - `description` (TEXT)
   - `transaction_type` (ENUM: 'debit', 'credit')
   - `amount` (DECIMAL(10,2))
   - `balance` (DECIMAL(10,2), nullable)
   - `reference_number` (VARCHAR, nullable)
   - `matched_expense_id` (Foreign Key to expense_transactions, nullable)
   - `matched_revenue_id` (Foreign Key to daily_reports, nullable)
   - `reconciliation_status` (ENUM: 'unmatched', 'matched', 'reviewed', 'exception')
   - `reconciliation_notes` (TEXT, nullable)
   - `import_batch_id` (Foreign Key to import_batches)
   - `duplicate_check_hash` (VARCHAR, indexed)
   - `created_at` (Timestamp)

**Acceptance Criteria:**
- [ ] Tables created with relationships
- [ ] Indexes on reconciliation_status and transaction_date
- [ ] Migration scripts

---

### Task 6.2: Backend - Bank Statement Import
**Priority: High | Estimated Time: 10 hours**

**Description:**
Import bank CSV and match with existing transactions.

**Endpoints to Create:**

1. **POST /api/bank/accounts**
   - Body: `{bank_name, account_number_last_four, account_type, store_id, opening_balance}`
   - Create new bank account
   - Permission: Admin, Super Admin

2. **GET /api/bank/accounts**
   - Query params: `store_id`
   - Response: List of bank accounts
   - Permission: Based on store access

3. **POST /api/bank/import**
   - Upload: CSV file + `{bank_account_id}`
   - Process:
     - Parse bank statement CSV
     - Detect duplicates by hash
     - For each transaction:
       - **Credits (deposits):**
         - Match to daily_report net deposits by date + amount (±$1 tolerance)
         - Check for credit card deposits (net after fees)
         - Flag if no match found
       - **Debits (withdrawals):**
         - Match to expense_transactions by date + amount + vendor
         - Use fuzzy matching on description
         - Flag if no match found
     - Create bank_transactions records
     - Update reconciliation_status
   - Response: Import summary with unmatched count
   - Permission: Owner, Admin

4. **GET /api/bank/reconciliation**
   - Query params: `bank_account_id`, `start_date`, `end_date`, `status`
   - Response: List of bank transactions with match status
   - Show unmatched items first
   - Permission: Owner, Admin

5. **POST /api/bank/reconciliation/:id/match**
   - Body: `{expense_id}` or `{revenue_id}`
   - Manually link bank transaction to expense or revenue
   - Update reconciliation_status to 'matched'
   - Permission: Owner, Admin

6. **POST /api/bank/reconciliation/:id/mark-reviewed**
   - Body: `{notes}`
   - Mark transaction as reviewed (e.g., bank fee, interest)
   - Create expense_transaction if needed
   - Permission: Owner, Admin

**Matching Algorithm:**
```python
def match_deposit(bank_transaction):
    date_range = bank_transaction.date ± 2 days
    amount_range = bank_transaction.amount ± $1
    
    # Try exact match first
    match = daily_reports.find(
        date IN date_range,
        net_deposit IN amount_range
    )
    
    if match:
        return match
    
    # Try credit card deposit (gross - fees)
    match = daily_reports.find(
        date IN date_range,
        (credit_card_sales * 0.9755) IN amount_range  # 2.45% fee
    )
    
    return match or null

def match_withdrawal(bank_transaction):
    date_range = bank_transaction.date ± 3 days
    amount_range = bank_transaction.amount ± $0.50
    
    # Try vendor match
    vendor = fuzzy_match_vendor(bank_transaction.description)
    
    match = expense_transactions.find(
        date IN date_range,
        amount IN amount_range,
        vendor_id = vendor.id if vendor else any
    )
    
    return match or null
```

**Acceptance Criteria:**
- [ ] Bank account management working
- [ ] CSV import with format detection
- [ ] Matching algorithm implemented
- [ ] Unmatched transaction handling
- [ ] Unit tests for matching logic
- [ ] Integration tests with sample files

---

### Task 6.3: Frontend - Bank Reconciliation Interface
**Priority: High | Estimated Time: 12 hours**

**Description:**
Build reconciliation interface for matching bank transactions.

**Pages/Components:**

1. **Bank Accounts Page** (`/bank/accounts`)
   - List of bank accounts
   - Current balance
   - Last reconciled date
   - Actions: View Transactions, Upload Statement, Reconcile

2. **Bank Statement Upload** (similar to CSV upload wizard)
   - Step 1: Select bank account, upload file
   - Step 2: Preview transactions
   - Step 3: Auto-matching summary
   - Step 4: Import confirmation

3. **Reconciliation Dashboard** (`/bank/reconciliation/:account_id`)
   
   **Layout:**
   - **Left Panel: Bank Transactions**
     - List of unmatched bank transactions
     - Color coded:
       - Green: Auto-matched
       - Yellow: Suggested match
       - Red: No match
     - Click to expand details
   
   - **Right Panel: Potential Matches**
     - When bank transaction selected:
       - Show potential expense/revenue matches
       - Display confidence score
       - Show details side-by-side
       - Match button
   
   - **Bottom: Reconciliation Summary**
     - Total bank transactions
     - Matched count
     - Unmatched count
     - Balance discrepancy

4. **Match Confirmation Modal**
   - Show both records side by side
   - Highlight matching fields
   - Note differences
   - Confirm/Cancel buttons

5. **Manual Resolution Modal**
   - For unrecognized bank fees, interest, etc.
   - Form to create expense entry
   - Link to bank transaction
   - Save button

**Features:**
- Drag-and-drop matching
- Bulk match button for obvious matches
- Filter: Show only unmatched
- Date range selector
- Export reconciliation report

**Acceptance Criteria:**
- [ ] All components built
- [ ] Matching interface intuitive
- [ ] Drag-and-drop working
- [ ] Auto-matching suggestions
- [ ] Manual resolution working
- [ ] Responsive design
- [ ] Integration complete

---

## MILESTONE 7: Credit Card Deposit Handling
**Estimated Time: 1 week**

### Task 7.1: Backend - Merchant Fee Calculation
**Priority: High | Estimated Time: 6 hours**

**Description:**
Automatically calculate and record merchant fees from credit card sales.

**Requirements:**

**Daily Report Enhancement:**
When daily report is saved with credit card sales:
1. Calculate merchant fee (2.45% of gross)
2. Calculate net deposit (gross - fee)
3. Create expense_transaction for merchant fee
4. Create bank_transaction for expected deposit

**Endpoints to Create:**

1. **POST /api/daily-reports/:id/process-cc-deposit**
   - Triggered automatically on daily report save/update
   - Process:
     - Get credit_card_sales from daily report
     - Calculate:
       - gross_amount = credit_card_sales
       - merchant_fee = gross_amount * 0.0245
       - net_deposit = gross_amount - merchant_fee
     - Create expense_transaction:
       - coa_id = "Merchant Processing Fees"
       - amount = merchant_fee
       - vendor_id = "Square" (or detected processor)
       - transaction_type = 'credit_card'
       - description = "Merchant fee for [date]"
     - Create expected bank_transaction:
       - amount = net_deposit
       - transaction_type = 'credit'
       - reconciliation_status = 'unmatched'
       - description = "Expected CC deposit for [date]"
   - Response: Fee breakdown
   - Permission: Automatic (system)

2. **GET /api/reports/merchant-fees**
   - Query params: `store_id`, `start_date`, `end_date`
   - Response: Summary of all merchant fees
   - Group by: Month, Store
   - Permission: Owner, Admin

3. **PUT /api/settings/merchant-fee-rate**
   - Body: `{rate, processor_name}`
   - Update merchant fee percentage
   - Apply to future transactions only
   - Permission: Super Admin

**Grubhub/Third-Party Fee Handling:**
Based on the Grubhub statement PDF, also handle:
- Marketing fees (15% or variable)
- Delivery fees (10% or variable)
- Processing fees

Create separate expense transactions for each fee type.

**Acceptance Criteria:**
- [ ] Auto-calculation on daily report save
- [ ] Multiple fee types supported
- [ ] Configurable fee rates
- [ ] Expense transactions created correctly
- [ ] Expected deposits logged
- [ ] Unit tests

---

### Task 7.2: Backend - Third-Party Platform Integration
**Priority: Medium | Estimated Time: 8 hours**

**Description:**
Parse and import Grubhub/UberEats/DoorDash statements.

**Requirements:**

Support multiple platform formats:
1. **Grubhub:** PDF statement (as provided)
2. **UberEats:** CSV export
3. **DoorDash:** CSV export

**Endpoints to Create:**

1. **POST /api/third-party/import**
   - Upload: File (PDF or CSV) + `{platform, store_id}`
   - Parse based on platform:
     - **Grubhub PDF:**
       - Extract sales amount
       - Extract marketing fees
       - Extract delivery fees
       - Extract processing fees
       - Extract net deposit
       - Extract sales tax collected
     - **CSV platforms:** Standard parsing
   - Create multiple expense_transactions:
     - One for each fee type
     - Link to appropriate COA categories
   - Create revenue transaction for gross sales
   - Create expected deposit transaction
   - Response: Import summary
   - Permission: Owner, Admin

2. **GET /api/third-party/statements**
   - Query params: `store_id`, `platform`
   - Response: List of imported statements
   - Permission: Owner, Admin

**PDF Parsing for Grubhub:**
Use library like PyPDF2 or pdfplumber to extract:
- Statement date range
- Total payments (net deposit)
- Restaurant sales
- Marketing fees
- Delivery fees
- Processing fees
- Sales tax

**Acceptance Criteria:**
- [ ] Grubhub PDF parsing working
- [ ] Multiple fee transactions created
- [ ] CSV platforms supported
- [ ] Sales tax handling
- [ ] Unit tests with sample files

---

### Task 7.3: Frontend - Merchant Fee Dashboard
**Priority: Medium | Estimated Time: 6 hours**

**Description:**
Display merchant fee analytics and third-party platform costs.

**Pages/Components:**

1. **Merchant Fees Dashboard** (`/reports/merchant-fees`)
   
   **Summary Cards:**
   - Total fees this month
   - Average fee percentage
   - Total by processor (Square, Stripe, etc.)
   - Total third-party fees (Grubhub, etc.)
   
   **Charts:**
   - Line chart: Fees over time
   - Pie chart: Fee breakdown by type
   - Bar chart: Fees by store
   
   **Detailed Table:**
   - Date, Store, Gross Sales, Fee Amount, Fee %, Net Deposit, Processor
   - Export to CSV

2. **Third-Party Platform Costs** (`/reports/third-party`)
   - By platform: Grubhub, UberEats, DoorDash
   - Breakdown: Marketing, Delivery, Processing
   - Gross vs Net comparison
   - ROI analysis (sales - fees)

3. **Fee Settings Page** (`/settings/merchant-fees`)
   - Configure fee rates per processor
   - Set default processor per store
   - Historical rate changes log

**Acceptance Criteria:**
- [ ] All components built
- [ ] Charts displaying correctly
- [ ] Export functionality
- [ ] Fee settings editable
- [ ] Responsive design

---

## MILESTONE 8: Profit & Loss (P&L) Statement
**Estimated Time: 2 weeks**

### Task 8.1: Backend - P&L Calculation Engine
**Priority: Critical | Estimated Time: 12 hours**

**Description:**
Build automated P&L generation from all transaction data.

**Requirements:**

**P&L Structure:**
```
REVENUE
  Food Sales
  Beverage Sales
  Third-Party Sales (Grubhub, UberEats)
  Other Income
TOTAL REVENUE

COST OF GOODS SOLD (COGS)
  Food Purchases
  Beverage Purchases
  Packaging Supplies
TOTAL COGS

GROSS PROFIT (Revenue - COGS)

OPERATING EXPENSES
  Payroll
  Rent
  Utilities
    - Electric
    - Water
    - Gas
  Marketing
    - Third-party platform fees
    - Advertising
  Credit Card Processing Fees
  Insurance
  Maintenance & Repairs
  Supplies
  Professional Services
  Other Expenses
TOTAL OPERATING EXPENSES

NET PROFIT (Gross Profit - Operating Expenses)
```

**Endpoints to Create:**

1. **GET /api/reports/pl**
   - Query params: `store_id`, `start_date`, `end_date`, `comparison_period`
   - Process:
     - Fetch all revenue (from daily_reports)
     - Fetch all expenses (from expense_transactions) grouped by COA
     - Calculate totals and subtotals
     - If comparison_period: Calculate variance ($  and %)
   - Response: Complete P&L structure with all line items
   - Permission: Based on store access and role

2. **GET /api/reports/pl/summary**
   - Query params: Same as above
   - Response: High-level summary only (Revenue, COGS, Expenses, Profit)
   - Permission: All authenticated users

3. **POST /api/reports/pl/snapshot**
   - Body: `{store_id, start_date, end_date, name}`
   - Save P&L snapshot for historical comparison
   - Response: Snapshot ID
   - Permission: Owner, Admin

4. **GET /api/reports/pl/snapshots**
   - Query params: `store_id`
   - Response: List of saved P&L snapshots
   - Permission: Owner, Admin

5. **GET /api/reports/pl/drill-down**
   - Query params: `store_id`, `coa_id`, `start_date`, `end_date`
   - Response: All transactions for specific line item
   - Allows drilling down into details
   - Permission: Owner, Admin

**Calculation Logic:**
```sql
-- Revenue
SELECT SUM(cash_sales + credit_card_sales + third_party_sales)
FROM daily_reports
WHERE store_id = ? AND report_date BETWEEN ? AND ?

-- COGS
SELECT coa.account_name, SUM(et.amount)
FROM expense_transactions et
JOIN chart_of_accounts coa ON et.coa_id = coa.id
WHERE coa.account_type = 'COGS'
  AND et.store_id = ? AND et.transaction_date BETWEEN ? AND ?
GROUP BY coa.id

-- Expenses
SELECT coa.account_name, SUM(et.amount)
FROM expense_transactions et
JOIN chart_of_accounts coa ON et.coa_id = coa.id
WHERE coa.account_type = 'Expense'
  AND et.store_id = ? AND et.transaction_date BETWEEN ? AND ?
GROUP BY coa.id
```

**Acceptance Criteria:**
- [ ] All endpoints implemented
- [ ] P&L calculations accurate
- [ ] Comparison period logic working
- [ ] Drill-down functionality
- [ ] Snapshot feature
- [ ] Unit tests
- [ ] Verified against sample P&L Excel file

---

### Task 8.2: Backend - Consolidated Multi-Store P&L
**Priority: High | Estimated Time: 6 hours**

**Description:**
Generate consolidated P&L across all stores.

**Endpoints to Create:**

1. **GET /api/reports/pl/consolidated**
   - Query params: `store_ids[]`, `start_date`, `end_date`
   - Response: Combined P&L with:
     - Consolidated totals
     - Per-store breakdown
     - Store comparison
   - Permission: Owner, Super Admin

2. **GET /api/reports/pl/store-comparison**
   - Query params: `store_ids[]`, `metric` (revenue, profit, margin), `start_date`, `end_date`
   - Response: Side-by-side comparison
   - Permission: Owner, Super Admin

**Acceptance Criteria:**
- [ ] Consolidated calculations working
- [ ] Per-store breakdown accurate
- [ ] Comparison metrics correct
- [ ] Unit tests

---

### Task 8.3: Frontend - P&L Report Display
**Priority: Critical | Estimated Time: 16 hours**

**Description:**
Build comprehensive P&L report interface with drill-down.

**Pages/Components:**

1. **P&L Report Page** (`/reports/profit-loss`)
   
   **Header Controls:**
   - Store selector (single or "All Stores")
   - Date range picker with presets:
     - This Month
     - Last Month
     - This Quarter
     - This Year
     - Custom Range
   - Comparison toggle:
     - None
     - Previous Period
     - Previous Year
   - Export buttons: PDF, Excel, CSV
   - Print button
   
   **Report Display:**
   
   **Format:**
   ```
   REVENUE                              Current     Comparison    Variance    Variance %
   ├── Food Sales                       $25,000     $23,000       $2,000      8.7%
   ├── Beverage Sales                   $8,000      $7,500        $500        6.7%
   ├── Third-Party Sales                $12,000     $10,000       $2,000      20.0%
   └── Other Income                     $500        $300          $200        66.7%
   TOTAL REVENUE                        $45,500     $40,800       $4,700      11.5%
   
   COST OF GOODS SOLD
   ├── Food Purchases                   ($8,750)    ($8,050)      ($700)      8.7%
   ├── Beverage Purchases               ($2,400)    ($2,250)      ($150)      6.7%
   └── Packaging                        ($1,200)    ($1,100)      ($100)      9.1%
   TOTAL COGS                           ($12,350)   ($11,400)     ($950)      8.3%
   
   GROSS PROFIT                         $33,150     $29,400       $3,750      12.8%
   Gross Margin                         72.9%       72.1%         0.8%
   
   OPERATING EXPENSES
   ├── Payroll                          ($15,000)   ($14,000)     ($1,000)    7.1%
   ├── Rent                             ($4,000)    ($4,000)      $0          0.0%
   ├── Utilities
   │   ├── Electric                     ($800)      ($750)        ($50)       6.7%
   │   ├── Water                        ($200)      ($180)        ($20)       11.1%
   │   └── Gas                          ($150)      ($140)        ($10)       7.1%
   ├── Marketing                        ($2,500)    ($2,000)      ($500)      25.0%
   ├── Credit Card Fees                 ($1,115)    ($1,000)      ($115)      11.5%
   ├── Insurance                        ($500)      ($500)        $0          0.0%
   └── Other                            ($1,500)    ($1,200)      ($300)      25.0%
   TOTAL OPERATING EXPENSES             ($25,765)   ($23,770)     ($1,995)    8.4%
   
   NET PROFIT                           $7,385      $5,630        $1,755      31.2%
   Net Margin                           16.2%       13.8%         2.4%
   ```
   
   **Features:**
   - Expandable/collapsible sections (accordion)
   - Click line item to drill down to transactions
   - Color coding:
     - Positive variance: Green
     - Negative variance: Red
     - No change: Gray
   - Tooltip with calculation details
   - Highlight important metrics
   
2. **Drill-Down Modal**
   - Triggered by clicking line item
   - Shows all transactions for that category
   - Table: Date, Vendor, Description, Amount, Store
   - Ability to edit transaction from here
   - Export transaction list

3. **Multi-Store Comparison View**
   - Side-by-side P&L for selected stores
   - Visual comparison with bar charts
   - Highlight best/worst performers
   - Store ranking table

4. **P&L Dashboard Widgets** (for main dashboard)
   - Current Month Profit widget
   - Revenue trend chart (last 6 months)
   - Top expenses chart
   - Margin indicator gauge

5. **Historical Snapshots** (`/reports/pl/snapshots`)
   - List of saved P&L snapshots
   - Compare any two snapshots
   - View snapshot details

**Export Formats:**

**PDF:**
- Professional formatting
- Company header
- Date range
- All sections
- Summary at top
- Page numbers

**Excel:**
- Multiple sheets:
   - Summary
   - Detailed P&L
   - Transaction breakdown
   - Charts
- Formulas intact for further analysis

**CSV:**
- Flat structure with all data
- One row per line item

**Acceptance Criteria:**
- [ ] All components built and styled
- [ ] Drill-down working
- [ ] Comparison view functional
- [ ] All export formats working
- [ ] Responsive design
- [ ] Loading states
- [ ] Error handling
- [ ] Tested with real data
- [ ] Matches format of provided P&L Excel

---

## MILESTONE 9: Permissions & Security
**Estimated Time: 3-4 days**

### Task 9.1: Backend - Role-Based Access Control
**Priority: Critical | Estimated Time: 8 hours**

**Description:**
Implement granular permissions for all Phase 2 features.

**Permission Matrix:**

| Feature | Manager | Owner/Admin | Super Admin |
|---------|---------|-------------|-------------|
| View COA | ✅ | ✅ | ✅ |
| Manage COA | ❌ | ❌ | ✅ |
| View Vendors | ✅ | ✅ | ✅ |
| Manage Vendors | ❌ | ✅ | ✅ |
| Enter Cash Expenses | ✅ | ✅ | ✅ |
| Upload CSV | ❌ | ✅ | ✅ |
| View Expenses (Own Store) | ✅ | ✅ | ✅ |
| View Expenses (All Stores) | ❌ | ✅ | ✅ |
| Review/Categorize | ❌ | ✅ | ✅ |
| Bank Reconciliation | ❌ | ✅ | ✅ |
| View P&L (Own Store) | ✅ | ✅ | ✅ |
| View P&L (All Stores) | ❌ | ✅ | ✅ |
| Export Reports | ✅ | ✅ | ✅ |
| Manage Settings | ❌ | ❌ | ✅ |

**Implementation:**

1. **Middleware Function:** `checkPermission(resource, action)`
   ```javascript
   function checkPermission(req, res, next) {
     const user = req.user;
     const resource = req.resource; // 'coa', 'vendors', 'expenses', etc.
     const action = req.action; // 'view', 'create', 'update', 'delete'
     const storeId = req.params.store_id || req.body.store_id;
     
     // Super Admin: Full access
     if (user.role === 'super_admin') {
       return next();
     }
     
     // Owner/Admin: Access to assigned stores + specific permissions
     if (user.role === 'owner' || user.role === 'admin') {
       if (resource === 'coa' && action !== 'view') {
         return res.status(403).json({error: 'Permission denied'});
       }
       if (storeId && !user.assignedStores.includes(storeId)) {
         return res.status(403).json({error: 'Store access denied'});
       }
       return next();
     }
     
     // Manager: Limited access
     if (user.role === 'manager') {
       if (action !== 'view' && resource !== 'expenses') {
         return res.status(403).json({error: 'Permission denied'});
       }
       if (storeId && storeId !== user.assignedStore) {
         return res.status(403).json({error: 'Store access denied'});
       }
       return next();
     }
     
     return res.status(403).json({error: 'Permission denied'});
   }
   ```

2. **Apply to All Routes:**
   - Add middleware to all expense, vendor, COA, and report routes
   - Check permissions before processing

3. **Data Filtering:**
   - Automatically filter data by store access
   - Prevent data leakage between stores

**Acceptance Criteria:**
- [ ] Middleware implemented
- [ ] All routes protected
- [ ] Data filtering working
- [ ] Permission tests (unit + integration)
- [ ] Audit log for sensitive actions

---

### Task 9.2: Frontend - Permission-Based UI
**Priority: High | Estimated Time: 6 hours**

**Description:**
Hide/show UI elements based on user permissions.

**Implementation:**

1. **Permission Check Hook:** `usePermission(resource, action)`
   ```javascript
   function usePermission(resource, action) {
     const user = useAuth();
     
     if (user.role === 'super_admin') return true;
     
     const permissions = {
       manager: {
         expenses: ['view', 'create'],
         reports: ['view'],
       },
       owner: {
         expenses: ['view', 'create', 'update'],
         vendors: ['view', 'create', 'update'],
         reports: ['view', 'export'],
         imports: ['upload'],
       },
       // ... more roles
     };
     
     return permissions[user.role]?.[resource]?.includes(action) || false;
   }
   ```

2. **Conditional Rendering:**
   ```jsx
   {hasPermission('coa', 'create') && (
     <Button onClick={openCreateModal}>Add Account</Button>
   )}
   ```

3. **Route Guards:**
   - Redirect unauthorized users
   - Show 403 page if trying to access forbidden page

**Acceptance Criteria:**
- [ ] Permission hook implemented
- [ ] All buttons/links conditionally rendered
- [ ] Route guards working
- [ ] Graceful error handling
- [ ] User-friendly messaging

---

## MILESTONE 10: Testing & Documentation
**Estimated Time: 1 week**

### Task 10.1: Comprehensive Testing
**Priority: Critical | Estimated Time: 16 hours**

**Test Categories:**

1. **Unit Tests:**
   - All API endpoints
   - Business logic functions (matching, categorization, P&L calc)
   - Utility functions
   - Target: 85%+ code coverage

2. **Integration Tests:**
   - Complete CSV import flow
   - Bank reconciliation flow
   - P&L generation
   - Permission checks across modules

3. **End-to-End Tests:**
   - User journey: Upload CSV → Review → Reconcile → Generate P&L
   - Multi-store scenarios
   - Role-based access testing

4. **Performance Tests:**
   - Large CSV import (1000+ rows)
   - P&L generation with 1 year of data
   - Concurrent user access

5. **Security Tests:**
   - SQL injection attempts
   - XSS prevention
   - CSRF protection
   - Permission bypass attempts

**Testing Tools:**
- Backend: Jest, Mocha, or PyTest
- Frontend: React Testing Library, Cypress
- API: Postman/Newman
- Load Testing: Apache JMeter

**Acceptance Criteria:**
- [ ] All test suites passing
- [ ] 85%+ code coverage
- [ ] No critical security vulnerabilities
- [ ] Performance benchmarks met
- [ ] Test documentation written

---

### Task 10.2: User Documentation
**Priority: High | Estimated Time: 8 hours**

**Documents to Create:**

1. **User Guide:**
   - Getting Started with Phase 2
   - Chart of Accounts Setup
   - Vendor Management
   - Uploading Credit Card Statements
   - Bank Reconciliation Process
   - Understanding the Review Queue
   - Generating P&L Reports
   - Role-Based Features Guide

2. **Admin Guide:**
   - System Configuration
   - Managing COA
   - User Permissions Setup
   - Import Troubleshooting
   - Best Practices

3. **Video Tutorials:**
   - CSV Upload Walkthrough (5 min)
   - Bank Reconciliation Demo (7 min)
   - P&L Report Guide (5 min)

4. **FAQ:**
   - Common import errors
   - Matching logic explanation
   - Duplicate detection
   - Fee calculations

**Acceptance Criteria:**
- [ ] All documents written and reviewed
- [ ] Screenshots included
- [ ] Videos recorded and edited
- [ ] Published in help center

---

### Task 10.3: Technical Documentation
**Priority: High | Estimated Time: 6 hours**

**Documents to Create:**

1. **API Documentation:**
   - Complete endpoint reference
   - Request/response examples
   - Error codes
   - Authentication
   - Rate limits

2. **Database Schema Documentation:**
   - ER diagrams
   - Table descriptions
   - Relationships
   - Indexes

3. **Architecture Documentation:**
   - System overview
   - Data flow diagrams
   - Integration points
   - Deployment architecture

4. **Developer Guide:**
   - Local setup
   - Running tests
   - Code standards
   - Contributing guidelines

**Acceptance Criteria:**
- [ ] All technical docs complete
- [ ] Diagrams created
- [ ] Code comments reviewed
- [ ] Published in developer portal

---

## MILESTONE 11: Deployment & Training
**Estimated Time: 3-4 days**

### Task 11.1: Staging Deployment
**Priority: Critical | Estimated Time: 4 hours**

**Checklist:**
- [ ] Deploy to staging environment
- [ ] Run database migrations
- [ ] Seed test data
- [ ] Configure environment variables
- [ ] Test all features in staging
- [ ] Performance monitoring setup
- [ ] Error logging configured
- [ ] Backup procedures tested

---

### Task 11.2: User Acceptance Testing (UAT)
**Priority: Critical | Estimated Time: 16 hours (over 3-5 days)**

**Process:**
1. Select UAT testers (2-3 users from each role)
2. Provide test scenarios and scripts
3. Gather feedback via survey/interviews
4. Log all bugs and issues
5. Prioritize and fix critical issues
6. Re-test after fixes

**Test Scenarios:**
- Import last month's credit card statement
- Reconcile bank account
- Review and categorize 20 expenses
- Generate P&L for Q4
- Compare two stores' performance

**Acceptance Criteria:**
- [ ] All critical bugs fixed
- [ ] User feedback incorporated
- [ ] 90%+ user satisfaction score
- [ ] Sign-off from stakeholders

---

### Task 11.3: Production Deployment
**Priority: Critical | Estimated Time: 4 hours**

**Deployment Steps:**
1. Final code review
2. Merge to production branch
3. Run production database migrations (with backup)
4. Deploy application
5. Smoke tests
6. Monitor for errors (24 hours)
7. Rollback plan ready

**Post-Deployment:**
- [ ] All systems operational
- [ ] No critical errors in logs
- [ ] Performance metrics normal
- [ ] User notifications sent

---

### Task 11.4: User Training
**Priority: High | Estimated Time: 8 hours (sessions)**

**Training Sessions:**

1. **Super Admin Training** (2 hours)
   - Complete system overview
   - COA management
   - User permission setup
   - Troubleshooting

2. **Owner/Admin Training** (2 hours)
   - CSV upload process
   - Review queue management
   - Bank reconciliation
   - P&L generation and interpretation

3. **Manager Training** (1 hour)
   - Daily expense entry
   - Viewing reports
   - Understanding P&L

4. **Refresher Training** (optional, 1 hour)
   - Advanced features
   - Tips and tricks
   - Q&A

**Training Materials:**
- PowerPoint slides
- Hands-on exercises
- Quick reference cards
- Recorded sessions

**Acceptance Criteria:**
- [ ] All training sessions completed
- [ ] Materials distributed
- [ ] User feedback collected
- [ ] Support channel established

---

## PROJECT TIMELINE SUMMARY

| Milestone | Estimated Time | Dependencies |
|-----------|----------------|--------------|
| 1. Chart of Accounts | 1-2 weeks | Phase 1 complete |
| 2. Vendor Management | 1-1.5 weeks | Milestone 1 |
| 3. Cash Expense Integration | 1 week | Milestones 1, 2 |
| 4. Credit Card Upload | 2 weeks | Milestones 1, 2, 3 |
| 5. Review & Exceptions | 1 week | Milestone 4 |
| 6. Bank Reconciliation | 1.5 weeks | Milestones 3, 4 |
| 7. CC Deposit Handling | 1 week | Milestones 3, 6 |
| 8. P&L Statement | 2 weeks | All previous |
| 9. Permissions | 3-4 days | All previous |
| 10. Testing & Docs | 1 week | All previous |
| 11. Deployment & Training | 3-4 days | Milestone 10 |

**Total Estimated Time: 10-12 weeks**

---

## DAILY TASK RECOMMENDATIONS

For optimal progress, I recommend the following daily task assignments:

### Week 1-2: Foundation
- Days 1-2: Database schemas (COA, vendors, expenses)
- Days 3-5: COA backend API
- Days 6-8: COA frontend UI
- Days 9-10: Vendor backend API

### Week 3-4: Vendor & Cash
- Days 11-13: Vendor frontend UI
- Days 14-15: Expense transaction schema
- Days 16-18: Cash expense integration
- Days 19-20: Testing & bug fixes

### Week 5-6: CSV Import
- Days 21-23: CSV parser backend
- Days 24-25: Duplicate detection
- Days 26-28: CSV upload UI
- Days 29-30: Machine learning logic

### Week 7: Review Queue
- Days 31-33: Review queue backend
- Days 34-37: Review queue UI

### Week 8-9: Bank Reconciliation
- Days 38-40: Bank schema & import
- Days 41-43: Matching algorithm
- Days 44-47: Reconciliation UI

### Week 10: Merchant Fees & Third-Party
- Days 48-50: Merchant fee calculation
- Days 51-52: Grubhub/PDF parsing
- Days 53-54: Merchant fee dashboard

### Week 11-12: P&L
- Days 55-58: P&L calculation engine
- Days 59-64: P&L frontend (complex)
- Days 65-66: Multi-store consolidation

### Week 13: Polish & Test
- Days 67-69: Permissions & security
- Days 70-73: Comprehensive testing
- Days 74-76: Documentation

### Week 14: Deploy
- Days 77-78: Staging & UAT
- Days 79-80: Production deployment
- Days 81-82: Training sessions

---

## TECHNICAL SPECIFICATIONS

### Technology Stack

**Backend:**
- **Framework:** Laravel 12.x (PHP 8.2+)
- **Database:** MySQL 8.0+
- **Authentication:** Laravel Sanctum (API tokens)
- **File Storage:** Laravel Storage (local/S3)
- **Queue System:** Laravel Queues (for CSV processing)
- **Testing:** PHPUnit, Pest (optional)

**Frontend:**
- **CSS Framework:** Bootstrap 5.3+
- **Icons:** Bootstrap Icons
- **JavaScript:** Vanilla JS / jQuery 3.7+
- **Template Engine:** Blade
- **Charts:** Chart.js 4.x
- **Date Picker:** Flatpickr or Bootstrap Datepicker
- **File Upload:** Dropzone.js

**Development Tools:**
- **API Testing:** Postman / Thunder Client
- **Version Control:** Git
- **Code Style:** Laravel Pint (PSR-12)
- **Local Environment:** Laravel Sail / Herd / Valet

### Required Laravel Packages

**Add to `composer.json`:**
```json
{
    "require": {
        "laravel/framework": "^12.0",
        "laravel/sanctum": "^4.0",
        "league/csv": "^9.0",
        "maatwebsite/excel": "^3.1",
        "barryvdh/laravel-dompdf": "^3.0",
        "smalot/pdfparser": "^2.0",
        "spatie/laravel-permission": "^6.0"
    },
    "require-dev": {
        "laravel/pint": "^1.0",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-laravel": "^2.0"
    }
}
```

**Package Usage:**
- `league/csv` - CSV parsing and export
- `maatwebsite/excel` - Excel import/export
- `barryvdh/laravel-dompdf` - PDF generation for reports
- `smalot/pdfparser` - Parse Grubhub PDF statements
- `spatie/laravel-permission` - Role and permission management

### Frontend Libraries (via CDN or npm)

**Bootstrap & Core:**
```html
<!-- Bootstrap 5.3 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<!-- jQuery (if needed) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
```

**Charts & Data Visualization:**
```html
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

**Date Picker:**
```html
<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
```

**File Upload:**
```html
<!-- Dropzone.js -->
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css">
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
```

### Database Configuration

**MySQL Configuration (`config/database.php`):**
```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'fanns_rms'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### Laravel Directory Structure for Phase 2

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ChartOfAccountController.php
│   │   │   ├── VendorController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── ImportController.php
│   │   │   ├── BankReconciliationController.php
│   │   │   └── ReportController.php
│   │   └── Admin/
│   │       ├── ChartOfAccountViewController.php
│   │       ├── VendorViewController.php
│   │       ├── ExpenseViewController.php
│   │       └── ReportViewController.php
│   ├── Middleware/
│   │   └── CheckRole.php
│   ├── Requests/
│   │   ├── StoreChartOfAccountRequest.php
│   │   ├── UpdateChartOfAccountRequest.php
│   │   ├── StoreVendorRequest.php
│   │   └── ImportTransactionRequest.php
│   └── Resources/
│       ├── ChartOfAccountResource.php
│       ├── VendorResource.php
│       └── ExpenseTransactionResource.php
├── Models/
│   ├── ChartOfAccount.php
│   ├── Vendor.php
│   ├── VendorAlias.php
│   ├── ExpenseTransaction.php
│   ├── BankAccount.php
│   ├── BankTransaction.php
│   ├── ImportBatch.php
│   └── TransactionMappingRule.php
├── Services/
│   ├── CsvParserService.php
│   ├── VendorMatchingService.php
│   ├── BankReconciliationService.php
│   ├── PLReportService.php
│   └── MerchantFeeService.php
└── Jobs/
    ├── ProcessCsvImport.php
    └── ReconcileBankTransactions.php

database/
├── migrations/
│   ├── xxxx_create_chart_of_accounts_table.php
│   ├── xxxx_create_vendors_table.php
│   ├── xxxx_create_expense_transactions_table.php
│   ├── xxxx_create_bank_accounts_table.php
│   └── ...
└── seeders/
    ├── ChartOfAccountsSeeder.php
    └── VendorsSeeder.php

resources/
├── views/
│   ├── layouts/
│   │   ├── app.blade.php
│   │   └── partials/
│   │       ├── navbar.blade.php
│   │       └── sidebar.blade.php
│   └── admin/
│       ├── coa/
│       │   └── index.blade.php
│       ├── vendors/
│       │   ├── index.blade.php
│       │   └── show.blade.php
│       ├── expenses/
│       │   ├── index.blade.php
│       │   └── review.blade.php
│       ├── imports/
│       │   ├── upload.blade.php
│       │   └── history.blade.php
│       ├── bank/
│       │   ├── accounts.blade.php
│       │   └── reconciliation.blade.php
│       └── reports/
│           ├── pl.blade.php
│           └── merchant-fees.blade.php
└── js/
    └── app.js

public/
├── css/
│   └── custom.css
└── js/
    └── custom.js

routes/
├── web.php      # Blade view routes
└── api.php      # API endpoints

tests/
├── Feature/
│   ├── ChartOfAccountTest.php
│   ├── VendorTest.php
│   ├── ExpenseImportTest.php
│   └── PLReportTest.php
└── Unit/
    ├── VendorMatchingServiceTest.php
    └── PLCalculationTest.php
```

---

## RISK MITIGATION

| Risk | Impact | Mitigation |
|------|--------|----------|
| CSV format variations | High | Support multiple formats, user mapping |
| Large file imports | Medium | Implement batch processing, progress tracking |
| Matching accuracy | High | Machine learning + manual review queue |
| Performance with large data | Medium | Database indexing, pagination, caching |
| User adoption | High | Training, intuitive UI, good documentation |
| Data security | Critical | Encryption, permissions, audit logs |

---

## SUCCESS CRITERIA

Phase 2 is considered successful when:

1. ✅ All milestones completed and tested
2. ✅ 95%+ of expenses auto-categorized after 1 month
3. ✅ Bank reconciliation completed within 15 minutes
4. ✅ P&L generation takes < 5 seconds
5. ✅ User satisfaction score > 85%
6. ✅ No critical bugs in production
7. ✅ All stores using the system daily
8. ✅ Review queue averages < 10 items per week

---

## NOTES FOR DEVELOPER

1. **Start with Sample Data:**
   - Use the provided CSV and Excel files for testing
   - Create realistic test data for development

2. **Incremental Development:**
   - Complete backend API first, then frontend
   - Test each endpoint before moving to next
   - Use Postman collections for API testing

3. **Code Quality:**
   - Follow existing Phase 1 patterns
   - Write clean, commented code
   - Use meaningful variable names
   - Keep functions small and focused

4. **Git Workflow:**
   - Create feature branches for each milestone
   - Commit frequently with clear messages
   - Pull request review before merging

5. **Communication:**
   - Daily standup updates
   - Flag blockers immediately
   - Ask questions early
   - Demo completed milestones

6. **Testing:**
   - Write tests as you develop (TDD)
   - Don't skip edge cases
   - Test with real data early

---

## SUPPORT & RESOURCES

- **Phase 1 Documentation:** [Link to Phase 1 docs]
- **Database Schema:** [Link to ER diagrams]
- **API Documentation:** [Link to Swagger/Postman]
- **Design Mockups:** [Link to Figma/designs]
- **Slack Channel:** #rms-phase2-dev
- **Project Manager:** [Name & contact]
- **Technical Lead:** [Name & contact]

---

**Document Version:** 1.0  
**Last Updated:** October 30, 2025  
**Created By:** Development Team  
**Next Review:** Start of each milestone
