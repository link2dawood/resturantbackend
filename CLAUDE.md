# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Single Source of Truth

**CURSOR.md** is the living reference for this project. After any core change (new routes, nav items, major features, renames, new modules) **update CURSOR.md's Changelog section**. Full docs index is at `docs/README.md`.

## Stack

Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade + Bootstrap 5 (Tabler theme), jQuery, Laravel Mix. Auth via Laravel Auth + Google OAuth (Socialite). Roles: `admin`, `owner`, `manager` (enum-based, DB permissions).

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

### Route files

- `routes/web.php` — All UI routes AND all `/api/*` AJAX routes (session auth). Roles enforced via `role:admin,owner,manager` middleware.
- `routes/api.php` — Only COA, vendor, and expense endpoints (legacy, also uses `auth:web`).

### Main layout

`resources/views/layouts/tabler.blade.php` — Main shell and navbar. All nav changes go here.

### Key models

`ExpenseTransaction` — core financial record linking a daily report, vendor, COA, store, and amount. `DailyReport` — daily sales data per store including `credit_cards` field used for merchant fee % calculations. `ThirdPartyStatement` — imported Grubhub/UberEats/DoorDash CSV data. `ChartOfAccount` — COA tree; `merchantProcessingFeesAccount()` resolves account codes `6100`/`6000`.

### Merchant Fee Analytics (`/merchant-fees`)

The index page server-renders initial data from the view controller, then immediately refreshes via four AJAX calls (`/api/merchant-fees/summary`, `/api/merchant-fees/trends`, `/api/merchant-fees/by-processor`, `/api/merchant-fees/transactions`). Filter form submit is intercepted by JS — it does NOT do a page reload; it re-fetches from the API. The merchant processing fee % = `SUM(ExpenseTransaction.amount where coa=6100) / SUM(DailyReport.credit_cards)`. Third-party fee % = `SUM(third_party_statements fees) / SUM(gross_sales)`. If the API call fails (e.g., COA not found → 404), the JS falls back silently to the server-rendered data.

### Role/permission middleware

`role:admin,owner` — checked via `app/Http/Middleware/`. Impersonation is supported via `ImpersonationController`; the `/impersonate/stop` route must be registered before `/impersonate/{user}`.

### Date handling

`convert_date_format` middleware is applied to daily report and audit log routes to normalize incoming date strings. Use `report_date` on `DailyReport` and `transaction_date` on `ExpenseTransaction` for date filtering.
