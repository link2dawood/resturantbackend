# Restaurant Backend — Cursor project reference

Use this file as the **single source of truth** for the project. When doing any task: **check here first** for structure, conventions, and recent changes. After making **core changes** (new routes, nav, major features, renames, new modules), **update the relevant sections and the **Changelog (Core Changes)** at the bottom of this file.

---

## Overview

| Item | Detail |
|------|--------|
| **Name** | Restaurant Backend Management System |
| **Stack** | Laravel 12, PHP 8.2+, MySQL/MariaDB, Blade + Bootstrap 5, jQuery, Laravel Mix/Vite |
| **Auth** | Laravel Auth + Google OAuth (Socialite) |
| **Roles** | Admin, Owner, Manager (enum-based; permissions in DB) |

**Purpose:** Multi-store restaurant backend: daily reports, expenses, COA/vendors, bank reconciliation, third-party platform fees (Grubhub/UberEats/DoorDash), owner CC statement imports, P&L, merchant fee analytics, review queue, exceptions report, import log, admin reports (audit logs).

---

## Where Docs Live

All detailed docs and markdown files live under **`docs/`** with this structure:

```
docs/
├── README.md                 # Index of all documentation
├── architecture/
│   └── codebase-analysis.md  # Full codebase analysis (reference)
├── guides/
│   ├── testing-guide.md
│   ├── quick-test-guide.md
│   ├── docker.md
│   ├── docker-setup.md
│   ├── permissions-implementation.md
│   ├── role-permissions.md
│   └── testing-roles.md
├── features/
│   └── online-fees-how-it-works.md  # Third-party platforms (Part C)
├── compliance/
│   ├── client-report.md
│   ├── phase2-requirements-compliance.md
│   ├── implementation-summary.md
│   └── security-improvements.md
├── development/
│   ├── table-component-usage.md
│   └── button-components-usage.md
└── data/                     # Sample PDFs/data (optional)
```

**When you need:** architecture, API list, DB schema, flows → use **`docs/architecture/codebase-analysis.md`**. For feature-specific behavior (e.g. online fees) → **`docs/features/`** or **`docs/guides/`**.

---

## Codebase Map (Important Paths)

| Path | Purpose |
|------|--------|
| `app/Http/Controllers/Admin/` | Admin UI controllers (expenses, COA, vendors, merchant fees, review queue, exceptions, import log, P&L, bank) |
| `app/Http/Controllers/Api/` | REST API (expenses, vendors, COA, bank import/reconciliation, third-party import, merchant fees, P&L) |
| `app/Models/` | Eloquent models (User, Store, DailyReport, ExpenseTransaction, Vendor, ChartOfAccount, BankAccount, ThirdPartyStatement, OwnerCcStatementImport, ImportBatch, TransactionMappingRule, AuditLog, etc.) |
| `app/Http/Middleware/` | Auth, role, permission, daily_report_access, convert_date_format, etc. |
| `resources/views/` | Blade templates; `layouts/tabler.blade.php` = main layout and **navbar** |
| `routes/web.php` | Web routes (auth, dashboard, daily reports, stores, owners, managers, **Merchant Fees** group, audit logs) |
| `routes/api.php` | API routes (expenses, resolve, review-queue, bank, third-party, merchant-fees, reports/pl) |

---

## Merchant Fees Nav & Routes

**Navbar:** One nav item **“Merchant Fees”** (Admin/Owner) with a single dropdown containing:

- **Owner CC Statements** → `admin.owner-cc-statements.index`
- **Bank Statement Imports** → `admin.bank-statement-imports.index` (`/bank-statement-imports`) — Bank of the West CSV; store + file; COA assignment on batch detail (debits with linked expenses)
- **Merchant Fee Analytics** → `admin.merchant-fees.index` (`/merchant-fees`)
- **Third-Party Platforms** → `admin.merchant-fees.third-party` (`/merchant-fees/third-party`)
- **Exceptions Report** → `admin.exceptions-report.index` (`/merchant-fees/exceptions`)
- **Download/Upload Log** → `admin.import-log.index` (`/merchant-fees/import-log`)
- **Admin Reports** (Admin only, `view_audit_logs`) → `audit-logs.index` (`/audit-logs`)

**Routes** (all under `role:admin,owner` in `web.php`):
- `GET /merchant-fees` → `MerchantFeeViewController@index`
- `GET /merchant-fees/third-party` → `MerchantFeeViewController@thirdParty`
- `GET /merchant-fees/third-party/statements/{statement}` → `MerchantFeeViewController@thirdPartyStatementShow` → `admin.merchant-fees.third-party.show` (detail page UI)
- `DELETE /merchant-fees/third-party/statements/{statement}` → `MerchantFeeViewController@thirdPartyStatementDestroy` → `admin.merchant-fees.third-party.destroy` (deletes statement, linked expenses, expected deposit bank transaction)
- `GET /merchant-fees/exceptions` → `ReviewQueueViewController@exceptionsReport`
- `GET /merchant-fees/import-log` → `ImportLogController@index`

**Audit logs** are **renamed in UI only** to **“Admin Reports”** (route names and URLs unchanged: `audit-logs.index`, `/audit-logs`). Views: `resources/views/audit-logs/index.blade.php`, `show.blade.php` — titles/headings say “Admin Reports”.

---

## Exceptions Report (Unrecognized Transactions)

- **Page:** Exceptions Report lists transactions the system doesn’t recognize (e.g. new “Spotify” or “Walmart” charges); same data source as review queue (`needs_review`), different view.
- **Controller:** `ReviewQueueViewController@exceptionsReport` → view `admin.exceptions-report.index`.
- **Resolve:** Uses existing `POST /api/expenses/{id}/resolve`; modal has “Remember for future uploads” (**checked by default**), sent as `create_mapping_rule`. When true, **TransactionMappingRule** is created so future uploads get the same category/vendor.

---

## Import Log (Download/Upload History)

- **Page:** History of credit card, bank, and online platform imports (paginated).
- **Controller:** `ImportLogController@index`; aggregates `OwnerCcStatementImport`, `ThirdPartyStatement`, and `ImportBatch` (type `bank_statement`), filtered by accessible stores; view `admin.import-log.index`.
- **Back link:** “← Back to Merchant Fees” to `admin.merchant-fees.index`.

---

## Run & Test (Commands)

- **App:** `php artisan serve` (or `composer dev` for server + queue + logs + vite).
- **DB:** `php artisan migrate`, `php artisan db:seed`, `php artisan migrate:fresh --seed`.
- **Tests:** `php artisan test`, `php artisan test --filter=TestName`.
- **Route audit:** `php artisan security:audit-routes` (if available).

---

## Changelog (Core Changes)

Record **core changes** here (new routes, nav changes, renames, new modules, major behavior changes) so the next task can rely on this file.

| Date | Change |
|------|--------|
| 2026-06-14 | **Phase 4 — Tenant migration + QA.** **Migration utilities:** `tenant:migrate-existing` ([MigrateExistingTenants](app/Console/Commands/MigrateExistingTenants.php)) provisions the Franchisor, assigns unowned stores (no `owner_store` pivot) to it, seeds the standard chart, and audits untenanted (`NULL store_id`) records — idempotent, `--dry-run` supported. **CoA template:** [CoaTemplateService](app/Services/CoaTemplateService.php) holds Fann's Philly's chart as the canonical source (the seeder now sources from it); `coa:export-template` snapshots the live chart to `database/data/coa-template.json`; new sign-ups idempotently get the chart (CoA is global/shared, not per-tenant). **Fixed a real pre-existing bug:** `User::getOrCreateFranchisor()` never set `role` (it's `$guarded`, so `create()`/`update()` dropped it) — the provisioned franchisor had a null role and failed `isFranchisor()`/`owners()` checks; now set explicitly. **QA suites:** `MultiTenantSecurityAuditTest` (no cross-tenant leaks across 6 scoped models), `EndToEndSaasFlowTest` (signup→verify→trial→lockout→paid→renewal→cancel), `TenantMigrationTest`. Coverage map in `docs/qa-checklist.md` (incl. manual Stripe live-sanity steps). |
| 2026-06-14 | **Phase 4 — RBAC: Owner CoA add-but-not-modify.** Tightened chart-of-accounts access so **owners can VIEW and ADD accounts but cannot modify existing ones** (admin-only edit/update/destroy) — the rest of RBAC (admin full, manager assigned-store-only) already matched. Web: the `coa` resource is split — `index/create/store/show` stay `role:admin,owner`; `edit/update/destroy` moved to a `role:admin` group ([routes/web.php](routes/web.php)). API: [Api\ChartOfAccountController](app/Http/Controllers/Api/ChartOfAccountController.php) `store()` now allows admin **or** owner (was admin-only — owners couldn't add); `update()`/`destroy()` stay admin-only — resolving a prior web/API inconsistency. The `@can` matrix gives owner `coa => ['view','create']`; new `create_coa` permission added to PermissionSeeder and granted to owners. Tests: `tests/Feature/RoleBasedAccessControlTest`. |
| 2026-06-13 | **Phase 4 — Sales Projection Calendar:** New **monthly calendar** at **`/sales-projections`** ([SalesProjectionController](app/Http/Controllers/SalesProjectionController.php), `sales-projections/index.blade.php`, nav: "Sales Projections") for entering a **daily sales projection** per store with **inline AJAX quick-entry** (blur/Enter saves one day) and **projection-vs-actual** comparison (per-day variance badge + month totals). Projections stored in the new **`sales_projections`** table (per-store, unique store+date) via the **TenantScoped** [SalesProjection](app/Models/SalesProjection.php) model; **backfilled** from existing `daily_reports.projected_sales` on migrate. Actuals read from the raw `daily_reports.net_sales` column (the model's `net_sales` accessor recomputes from relations, so raw column is used). Store selector + prev/next month nav. Admin/owner/manager (scoped to accessible stores). Self-contained — does not change the dashboard Sales ring (which still reads report-level projections). Tests: `tests/Feature/SalesProjectionCalendarTest`. |
| 2026-06-13 | **Phase 4 — KPI configuration:** Owners/admins can set **per-store (per-tenant) target percentages** for Food Cost %, Payroll %, Rent % on a setup screen at **`/kpi-settings`** ([KpiController](app/Http/Controllers/KpiController.php), `kpi/edit.blade.php`, nav: "KPI Targets"). Stored in the new **`kpi_targets`** table (one row per store, nullable columns) via the **TenantScoped** [KpiTarget](app/Models/KpiTarget.php) model — a user only sees/edits targets for their own stores, and can't write another tenant's. The dashboard rings now read these user-defined targets ([DashboardMetricsService::resolveTarget](app/Services/DashboardMetricsService.php) averages the accessible stores' targets) and **fall back to `config/dashboard.php`** when none are set. Tests: `tests/Feature/KpiConfigurationTest`. |
| 2026-06-12 | **Phase 4 — Dashboard circular metrics:** The `/home` dashboard now leads with **four circular ring metrics** for the current month — **Sales** (net sales vs `projected_sales`, $ variance ahead/behind), **Food Cost %**, **Payroll Cost %**, **Rental Cost %** (spend ÷ net sales vs a target %, variance in points under/over). Computed by [DashboardMetricsService](app/Services/DashboardMetricsService.php) over the tenant-scoped DailyReport/ExpenseTransaction models (each user sees only their stores). Cost categories + targets are configurable in `config/dashboard.php` (Food = COGS `5100/5200/5300`, Payroll = `6600/6610`, Rent = `6500`; targets 30/30/10%). Metrics render **greyed-out** ("No data yet") when there are no reports in the period. SVG ring partial: `resources/views/dashboard/partials/circular-metrics.blade.php`. Tests: `tests/Feature/DashboardMetricsTest`. |
| 2026-06-12 | **Phase 4 — Multi-tenant isolation:** The tenant boundary is the **store** (`store_id` is the tenant key). Added a row-level **global scope** ([TenantScoped](app/Models/Concerns/TenantScoped.php) trait, driven by [TenantManager](app/Tenancy/TenantManager.php)) so every Eloquent query on the core financial models is auto-constrained to the current user's accessible stores — defense-in-depth over the ~66 existing manual `getAccessibleStoreIds()` filters. Applied to **ExpenseTransaction, DailyReport, ThirdPartyStatement, BankAccount, ImportBatch, OwnerCcStatementImport**. Admins, the franchisor, and admin-level impersonation are **exempt**; non-web contexts (console, queue, Stripe webhooks) are unscoped; `NULL store_id` (corporate/shared) stays visible. Escape hatch: `Model::withoutTenantScope()`. Route-model-bound records of another tenant now 404. **Tenant identification**: [InitializeTenant](app/Http/Middleware/InitializeTenant.php) middleware (web stack) primes the per-request context; `TenantManager` is a singleton. **Tenant-scoped file uploads**: [TenantStorage](app/Support/TenantStorage.php) writes new owner-CC & third-party statement files under `tenant/{storeId}/{category}/...` (was a flat shared path); existing files keep their stored path (no data migration). Single-DB, no schema change, no `tenant_id` column. Tests: `tests/Feature/TenantIsolationTest`. |
| 2026-06-12 | **Phase 4 — Payments / Stripe (Laravel Cashier):** Installed `laravel/cashier` v16 (Stripe). `User` is now `Billable`; trial state and Stripe billing share the existing `trial_ends_at` column (Cashier's customer-columns migration was edited to not re-add it). **Card capture** is PCI-compliant via Stripe Elements + SetupIntent on `/billing` ([BillingController](app/Http/Controllers/BillingController.php), [billing/show.blade.php](resources/views/billing/show.blade.php)) — raw card data never hits our server. **Conversion** ([SubscriptionService](app/Services/SubscriptionService.php)): subscriptions are **anchored to the 1st** (`billing_cycle_anchor`) with a **prorated first charge** from the conversion date; `trials:check` **auto-converts** lapsed trials that have a card on file (else expires them). **Webhooks**: Cashier's `/stripe/webhook` syncs the DB; [HandleStripeWebhook](app/Listeners/HandleStripeWebhook.php) (on `WebhookHandled`) layers receipts (payment_succeeded), **dunning** emails (payment_failed, no lockout while Stripe retries), and lockout on subscription deleted/canceled/unpaid — mirrored onto `users.subscription_status`. Billing emails via [BillingMailer](app/Support/BillingMailer.php) + `resources/views/emails/billing/*`. **Customer Portal** (update card / invoices / cancel) via `billing.portal`. **Admin dashboard** `/admin/subscriptions` ([SubscriptionAdminController](app/Http/Controllers/Admin/SubscriptionAdminController.php)): active subs, **MRR/ARR**, **churn (30d)** from the local `subscriptions` table. Nav: owners get "Billing & Subscription", admins get "Subscriptions". Config in `config/subscription.php` + `config/cashier.php`; env `STRIPE_KEY/SECRET/WEBHOOK_SECRET/PRICE_ID`, `SUBSCRIPTION_MONTHLY_AMOUNT`. **Manual Stripe setup required** — see `docs/stripe-setup.md`. Tests: `tests/Feature/PaymentSubscriptionTest`. |
| 2026-06-12 | **Phase 4 — Trial System (SaaS):** Self-serve signups now get a **30-day free trial** (`User::startTrial()`, length from `config/trial.php` → `TRIAL_DAYS`). Trial state lives on the **owner** (workspace/billing entity) via new `users` columns `subscription_status` (trialing/active/expired), `trial_started_at`, `trial_ends_at`, `trial_extension_requested_at`, `trial_*_notified_at` (migration `2026_06_12_000002`, existing users backfilled to `active`). **Real-time lockout:** `EnsureTrialActive` middleware (alias `trial`) on all authenticated route groups redirects expired workspaces to the **Trial Expired** screen (`/trial/expired`, `TrialController`); admins/franchisor exempt, managers gated by their owner via `User::billingOwner()`. The expired screen has a **Request to Continue** CTA (`POST /trial/request-continue`) that records the request and emails sales + the client. **Countdown banner** in the Tabler layout shows days left. **Notifications** (generic `App\Mail\TrialMail` + markdown views in `resources/views/emails/trial/`, dispatched by `App\Support\TrialMailer`): signup → client welcome + sales alert; expiry → client + sales; plus expiring-soon reminder and continue-request/confirm. **Scheduled** `trials:check` command (daily 08:00, `routes/console.php`) sends expiring-soon + expiry emails and flips lapsed trials to `expired`. New owners via admin (`OwnerController`) stay `active` (column default). UserFactory defaults `subscription_status` to `active`. Config: `SALES_TEAM_EMAIL`, `TRIAL_EXPIRING_SOON_DAYS`. Tests: `tests/Feature/TrialSystemTest`. |
| 2026-06-12 | **Phase 4 — Auth & User Management (SaaS):** Email verification is now actually wired and enforced. `Auth::routes(['verify' => true])` registers the verify routes; `AppServiceProvider` listens `Registered` → `SendEmailVerificationNotification` to send the link on signup; **all authenticated route groups now require `verified`** (unverified users are bounced to `verification.notice`). **Public signup is self-serve SaaS:** `RegisterController` assigns `UserRole::OWNER` to new registrants (their own workspace). `GoogleController` hardened (no `dd()`, random 40-char password instead of `123456dummy`, sets OWNER + pre-verified since Google verifies email). Admin-provisioned users stay verified: `ManagerController` sets `email_verified_at` on create (Owner already did); migration `2026_06_12_000001_verify_existing_users_email` backfills existing users so enforcement doesn't lock anyone out. **Session management:** `AuthenticateSession` added to the web stack and `ProfileController::updatePassword` calls `Auth::logoutOtherDevices()`. Mail switched to SMTP in `.env.example`. Test `tests/Feature/AuthRegistrationVerificationTest`. Also driver-guarded three MySQL-only enum/MODIFY migrations so the SQLite test suite boots. |
| 2026-04-26 | **Annual P&L COA Activity Summary:** On `/reports/profit-loss/annual`, the **Expense by COA** table now replaces parent/type/entry-count columns with **Jan–Dec monthly breakdown columns** plus annual total, backed by annual API expense COA activity payloads that include `monthly_amounts` and `monthly_totals`. |
| 2026-04-25 | **Franchisor P&L tracking:** Profit & Loss page now presents explicit franchisor tracking context for brand-wide vs single-store views, keeps **Store / All Stores** wording in the filter for franchisor users, and has regression tests covering franchisor access to other owners’ store P&L statements. |
| 2026-04-02 | **Import filename uniqueness:** `App\Support\ImportUniqueFileName` blocks a second upload with the same original file name (trimmed, case-insensitive) per channel: Owner CC (`owner_cc_statement_imports`), bank statements (`import_batches` where `import_type = bank_statement`), online/third-party (`third_party_statements`). CC imports no longer dedupe by `file_hash` per store; they use this global-per-channel name rule. Bank API still also rejects duplicate `file_hash`. |
| 2026-04-02 | **Navbar (admin):** User profile dropdown includes **Bank accounts** → `admin.bank.accounts.index` after Profile Settings (`isAdmin()` only). **Bank accounts UI:** Store column renders “Corporate” as muted text (fixed escaped HTML in `admin/bank/accounts/index` and `show`). |
| 2026-04-02 | **Bank statement import create:** Lists active accounts where `store_id` is in accessible stores **or** `store_id` is null (corporate); import allows corporate + selected store. Page includes **Bank accounts** table + modal (POST/PUT `/api/bank-accounts`) for add/edit without leaving the page. |
| 2026-04-02 | **Navbar (admin):** Profile dropdown adds **Bank accounts** (after Profile Settings) for `isAdmin()` only → `admin.bank.accounts.index`. Bank accounts list/detail: store column shows muted “Corporate” / “Corporate account” (Blade was escaping HTML). |
| 2026-04-02 | **Bank Statement Import delete:** `DELETE /bank-statement-imports/batches/{importBatch}` (`BankStatementImportController@destroy`) removes expenses with `import_batch_id`, then `bank_transactions` for the batch, then the `ImportBatch`. Delete controls on index and batch show. |
| 2026-04-02 | **Bank transactions `coa_id`:** Nullable FK on `bank_transactions` for **credit** lines (debits keep COA on linked `ExpenseTransaction`; debit saves mirror `coa_id` on the bank row). Bank statement batch show lists COA for credits and debits-with-expense; `updateTransactionCoa` handles both. Credits apply `OwnerCcDescriptionMapping` on import when a pattern exists. Migration `2026_04_02_000002_add_coa_id_to_bank_transactions_table`. |
| 2026-04-02 | **Bank Statement Import (admin):** Routes under `role:admin,owner`: `GET/POST /bank-statement-imports`, batch show `GET /bank-statement-imports/batches/{importBatch}`, per-line COA `POST .../batches/{importBatch}/bank-transactions/{bankTransaction}/coa` (`updateTransactionCoa`, JSON body `coa_id`, saves on dropdown change in UI). Import form requires **`bank_account_id`** (active account for the chosen store), populated from JSON in the browser; BoW-like names tagged via `BankStatementSupportedBank::isLikelyBankOfTheWestName`. `BankImportController` parses CSV format `bank_west` (columns Account, ChkRef, Debit, Credit, Balance, Date, Description) and `runBankStatementImportForAdmin`; `ImportBatch::bankTransactions()`. Sample CSV: `docs/data/bank-of-the-west-activity-sample.csv`. |
| 2026-04-02 | **Owner CC statement import:** Create form requires **card issuer** (`card_platform`) from `App\Constants\OwnerCcStatementCardPlatform` (City Bank, American Express, Chase Bank). Column `owner_cc_statement_imports.card_platform`; list/show and Download/Upload log show the label. **Column mapping is issuer-specific:** City Bank (Status, Date, Description, Debit, Credit, Member Name); Chase (Card, Transaction/Post Date, Description, Category, Type, signed Amount, Memo); Amex (Date, Receipt, Description, signed Amount). |
| 2026-04-02 | **Merchant Fee Analytics (credit card only):** `ChartOfAccount::merchantFeeAnalyticsCoaIds()` = `merchantProcessingFeeCoaIds()` (6100/6000-style processing accounts only; not Grubhub/Uber/DoorDash COAs). Recent rows, trends, and by-processor merge **Owner CC statement lines** on those COAs via `MerchantFeeOwnerCcProcessingFees`; third-party statement expenses are excluded from those aggregates. `MerchantFeeRecentRows` no longer injects third-party statement–only rows. |
| 2026-04-02 | **Merchant Fee “Recent transactions” data source:** `App\Support\MerchantFeeRecentRows` merges (1) `ExpenseTransaction` rows on analytics COAs **or** linked to `third_party_statement_id`, and (2) **statement summary rows** when imports created statements but no expense lines (e.g. missing platform COA). API `transactions` no longer 404s when only statements exist; defaults date range to current month if omitted. |
| 2026-04-02 | **US date display (MM-DD-YYYY):** `config/dates.php` defines `dates.display` (`m-d-Y`) and datetime variants; Blade/Carbon use `config('dates.display')` etc. after a bulk update. `public/js/date-formatter.js` shows **MM-DD-YYYY** (dash) with hidden `YYYY-MM-DD` for submits. `ConvertDateFormat` middleware still accepts slash or dash US input. Optional env: `DATE_DISPLAY_FORMAT`, `DATE_DISPLAY_DATETIME_FORMAT`. |
| 2025-03-04 | **Merchant Fees navbar:** Added dropdown “Merchant Fees” with Merchant Fee Analytics, Third-Party Platforms, Exceptions Report, Download/Upload Log, Admin Reports (admin only). Removed standalone Audit Logs link for Admin/Owner. |
| 2025-03-04 | **Exceptions Report:** New page at `/merchant-fees/exceptions` (`ReviewQueueViewController@exceptionsReport`, view `admin.exceptions-report.index`). “Remember for future uploads” default on; uses `POST /api/expenses/{id}/resolve` with `create_mapping_rule` and `TransactionMappingRule`. |
| 2025-03-04 | **Download/Upload log:** New page at `/merchant-fees/import-log` (`ImportLogController@index`, view `admin.import-log.index`). Aggregates CC, bank, and online platform imports; back link to Merchant Fees. |
| 2025-03-04 | **Audit Logs → Admin Reports:** UI only rename in nav and in `audit-logs/index.blade.php`, `audit-logs/show.blade.php`. Routes unchanged (`audit-logs.index`, `/audit-logs`). |
| 2025-03-04 | **CURSOR.md and docs:** Created `CURSOR.md` at project root; moved/organized all .md files under `docs/` with structure (architecture, guides, features, compliance, development). |
| 2025-03-04 | **CURSOR.md section names:** Renamed sections to Overview, Where Docs Live, Codebase Map (Important Paths), Merchant Fees Nav & Routes, Exceptions Report (Unrecognized Transactions), Import Log (Download/Upload History), Run & Test (Commands), Changelog (Core Changes). |
| 2025-03-04 | **Nav: Merchant Fees single dropdown:** Owner CC Statements and Merchant Fees merged into one nav item “Merchant Fees” with sub-items: Owner CC Statements, divider, Merchant Fee Analytics, Third-Party Platforms, Exceptions Report, Download/Upload Log, Admin Reports (admin only). |
