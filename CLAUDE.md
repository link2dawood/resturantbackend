# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Single Source of Truth

**CURSOR.md** is the living reference for this project. After any core change (new routes, nav items, major features, renames, new modules) **update CURSOR.md's Changelog section**. Full docs index is at `docs/README.md`.

## Stack

Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade + Bootstrap 5 (Tabler theme), jQuery, Laravel Mix. Auth via Laravel Auth + Google OAuth (Socialite). Roles: `admin`, `owner`, `manager` (enum-based, DB permissions). Key packages: `barryvdh/laravel-dompdf`, `maatwebsite/excel`, `intervention/image`, `smalot/pdfparser`.

## Commands

```bash
# Local development (server + queue + logs + vite concurrently)
composer dev

# Individual
php artisan serve
php artisan queue:listen --tries=1
npm run dev

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Tests
php artisan test
php artisan test --filter=TestName

# Cache clear (required after adding routes)
php artisan route:clear && php artisan config:clear && php artisan cache:clear
```

### Docker (production-like)

```bash
make up          # Start containers (apache, mysql, redis)
make build       # Build and start
make shell       # Shell into app container
make migrate     # Run migrations in container
make clear-cache # Clear route/config/cache in container
make artisan CMD="cache:clear"  # Run any artisan command
```

## Architecture

### Two controller layers

- **`app/Http/Controllers/Admin/`** — Blade UI controllers. Each renders a view with pre-computed server-side data. Views are in `resources/views/admin/`.
- **`app/Http/Controllers/Api/`** — JSON API endpoints consumed by the same pages via AJAX. Registered under `Route::prefix('api')` inside `routes/web.php` (session-authenticated, not `routes/api.php`).

Both layers often perform the same data computations (view controller for initial page load; API controller for dynamic filter refreshes). When updating business logic, update both.

`ProfitLossViewController` is an exception: it directly instantiates `ProfitLossController` (`new PLController()`) and delegates to it rather than duplicating query logic.

### Route files

- `routes/web.php` — All UI routes AND all `/api/*` AJAX routes (session auth). Roles enforced via `role:admin,owner,manager` middleware.
- `routes/api.php` — **Legacy only.** Registers COA, vendor, and expense routes at the same `/api/*` paths as `web.php`. These are duplicates. Prefer `routes/web.php` for all new routes.

### Main layout

`resources/views/layouts/tabler.blade.php` — Main shell and navbar. All nav changes go here.

### Blade components

`resources/views/components/` contains reusable table and button components: `table.blade.php`, `table-row.blade.php`, `table-cell.blade.php`, `pagination.blade.php`, `button-add`, `button-edit`, `button-delete`, `button-view`, `button-search`, `button-group-actions`. Use these before writing inline table/button HTML.

### Key models

- `ExpenseTransaction` — core financial record linking a daily report, vendor, COA, store, and amount. Also used for third-party platform fee expenses (`third_party_statement_id`) and bank import expenses (`import_batch_id`).
- `DailyReport` — daily sales data per store; always eager-loads `store` and `creator` via `$with`. Contains `credit_cards` field used for merchant fee % calculations. Appended attributes compute totals (net sales, short/over, etc.) from related records.
- `ThirdPartyStatement` — imported Grubhub/UberEats/DoorDash CSV data.
- `ChartOfAccount` — COA tree; `merchantProcessingFeesAccount()` resolves account codes `6100`/`6000`. `totalRollupAccountCodes()` lists rollup-total accounts that must be hidden from transaction-type/COA dropdowns (they represent sums of child rows, not individual line items).
- `OwnerCcStatementImport` / `OwnerCcStatementLine` — imported owner credit card statements; card platform (City Bank, Chase, Amex) determines column mapping.
- `ImportBatch` — bank statement imports (type `bank_statement`).
- `BankTransaction` — individual bank rows from imported CSV; has nullable `coa_id` for credit lines; debit lines link `coa_id` via associated `ExpenseTransaction`.
- `TransactionMappingRule` — auto-categorization rules created from Exceptions Report "Remember for future uploads".

### Role & access control

Roles: `admin > owner > manager` (enum `App\Enums\UserRole`). DB permissions in `role_permissions` table, with hardcoded fallback in `UserRole::hasPermission()`.

`User::isFranchisor()` detects the special brand-owner user by checking `role === OWNER && strtolower($name) === 'franchisor'`. This user sees all stores/owners/managers as if admin. `User::getOrCreateFranchisor()` idempotently provisions this user.

Store access:
- `User::accessibleStores()` — returns a query builder (not a collection) scoped to what the user can see.
- `User::getAccessibleStoreIds()` — returns an array of IDs; used in most filter queries.
- Managers have two assignment mechanisms that must stay in sync: `store_id` (direct FK) and `manager_store` pivot table. `User::getAssignedStoresAttribute()` also reads a legacy JSON `assigned_stores` column.

Impersonation: Admin can impersonate any user via `ImpersonationController`. Session keys `impersonating_admin_id` / `impersonating_user_id` grant impersonated users full admin-level store access via `User::isBeingImpersonated()`. **The `/impersonate/stop` route must be registered before `/impersonate/{user}` in `routes/web.php`** to avoid route conflict.

Middleware aliases:
- `role` → `RoleMiddleware` (comma-separated roles: `role:admin,owner`)
- `permission` → `PermissionMiddleware`
- `admin_or_owner` → `AdminOrOwnerMiddleware`
- `daily_report_access` → `CheckDailyReportAccess`
- `convert_date_format` → `ConvertDateFormat`

### Merchant Fee Analytics (`/merchant-fees`)

The index page server-renders initial data from the view controller, then immediately refreshes via four AJAX calls (`/api/merchant-fees/summary`, `/api/merchant-fees/trends`, `/api/merchant-fees/by-processor`, `/api/merchant-fees/transactions`). Filter form submit is intercepted by JS — it does NOT do a page reload; it re-fetches from the API. If the API call fails (e.g., COA not found → 404), the JS falls back silently to the server-rendered data.

Merchant processing fee % = `SUM(ExpenseTransaction.amount where coa=6100) / SUM(DailyReport.credit_cards)`.
Third-party fee % = `SUM(third_party_statements fees) / SUM(gross_sales)`.

`app/Support/MerchantFeeOwnerCcProcessingFees` — merges Owner CC statement lines assigned to processing-fee COAs into merchant fee trends and by-processor aggregates. `app/Support/MerchantFeeRecentRows` — builds the "Recent transactions" list from both `ExpenseTransaction` and statement summary rows. Both are used by both the view controller (initial load) and the API controller (AJAX refresh).

### P&L Report (`/reports/profit-loss`)

`ProfitLossViewController` delegates to `ProfitLossController` (API layer) by direct instantiation. Five views: `index` (monthly), `annual`, `drill-down` (COA detail with expense rows), `comparison`, `snapshots`. `PlSnapshot` stores point-in-time P&L captures. Managers can view but not export or see comparison/snapshots. Export routes (`/reports/profit-loss/export/csv`, `.../pdf`) are admin/owner-only.

### Bank Statement Imports

Supports Bank of the West CSV format (columns: Account, ChkRef, Debit, Credit, Balance, Date, Description). `BankImportController::runBankStatementImportForAdmin` parses rows and creates `BankTransaction` entries. Debit rows that match expenses get a linked `ExpenseTransaction`; credit rows get `coa_id` directly on the bank transaction. COA can be assigned per-line from the batch detail UI via AJAX to `POST /bank-statement-imports/batches/{importBatch}/bank-transactions/{bankTransaction}/coa`.

### Owner CC Statement Imports

Card platform (`card_platform`) determines column mapping:
- **City Bank:** Status, Date, Description, Debit, Credit, Member Name
- **Chase:** Card, Transaction/Post Date, Description, Category, Type, signed Amount, Memo
- **Amex:** Date, Receipt, Description, signed Amount

`app/Imports/OwnerCcStatementRowsImport` handles parsing. `OwnerCcDescriptionMapping` rules auto-assign COA for known description patterns.

### Import filename uniqueness

`app/Support/ImportUniqueFileName` blocks re-uploading a file with the same original name (trimmed, case-insensitive) per channel:
- Owner CC: checks `owner_cc_statement_imports`
- Bank: checks `import_batches` where `import_type = bank_statement`; also rejects duplicate `file_hash`
- Third-party/online: checks `third_party_statements`

### Date handling

`convert_date_format` middleware normalizes US-style dates (MM-DD-YYYY primary; slashes allowed) to `Y-m-d` on allowed fields: `report_date`, `corporate_creation_date`, `date_from`, `date_to`, `start_date`, `end_date`, `from_date`, `to_date`, `transaction_date`. Applied to daily reports, owners, audit logs, expenses, merchant fees, P&L, and bank account filters. User-visible dates use `config/dates.php` (`m-d-Y` by default). `[type="date"]` inputs are upgraded by `public/js/date-formatter.js` to MM-DD-YYYY text with hidden ISO values for submissions.
