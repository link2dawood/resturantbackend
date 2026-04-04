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
| 2026-04-02 | **Import filename uniqueness:** `App\Support\ImportUniqueFileName` blocks a second upload with the same original file name (trimmed, case-insensitive) per channel: Owner CC (`owner_cc_statement_imports`), bank statements (`import_batches` where `import_type = bank_statement`), online/third-party (`third_party_statements`). CC imports no longer dedupe by `file_hash` per store; they use this global-per-channel name rule. Bank API still also rejects duplicate `file_hash`. |
| 2026-04-02 | **Navbar (admin):** User profile dropdown includes **Bank accounts** → `admin.bank.accounts.index` after Profile Settings (`isAdmin()` only). **Bank accounts UI:** Store column renders “Corporate” as muted text (fixed escaped HTML in `admin/bank/accounts/index` and `show`). |
| 2026-04-02 | **Bank statement import create:** Lists active accounts where `store_id` is in accessible stores **or** `store_id` is null (corporate); import allows corporate + selected store. Page includes **Bank accounts** table + modal (POST/PUT `/api/bank-accounts`) for add/edit without leaving the page. |
| 2026-04-02 | **Navbar (admin):** Profile dropdown adds **Bank accounts** (after Profile Settings) for `isAdmin()` only → `admin.bank.accounts.index`. Bank accounts list/detail: store column shows muted “Corporate” / “Corporate account” (Blade was escaping HTML). |
| 2026-04-02 | **Bank Statement Import delete:** `DELETE /bank-statement-imports/batches/{importBatch}` (`BankStatementImportController@destroy`) removes expenses with `import_batch_id`, then `bank_transactions` for the batch, then the `ImportBatch`. Delete controls on index and batch show. |
| 2026-04-02 | **Bank transactions `coa_id`:** Nullable FK on `bank_transactions` for **credit** lines (debits keep COA on linked `ExpenseTransaction`; debit saves mirror `coa_id` on the bank row). Bank statement batch show lists COA for credits and debits-with-expense; `updateTransactionCoa` handles both. Credits apply `OwnerCcDescriptionMapping` on import when a pattern exists. Migration `2026_04_02_000002_add_coa_id_to_bank_transactions_table`. |
| 2026-04-02 | **Bank Statement Import (admin):** Routes under `role:admin,owner`: `GET/POST /bank-statement-imports`, batch show `GET /bank-statement-imports/batches/{importBatch}`, per-line COA `POST .../batches/{importBatch}/bank-transactions/{bankTransaction}/coa` (`updateTransactionCoa`, JSON body `coa_id`, saves on dropdown change in UI). Import form requires **`bank_account_id`** (active account for the chosen store), populated from JSON in the browser; BoW-like names tagged via `BankStatementSupportedBank::isLikelyBankOfTheWestName`. `BankImportController` parses CSV format `bank_west` (columns Account, ChkRef, Debit, Credit, Balance, Date, Description) and `runBankStatementImportForAdmin`; `ImportBatch::bankTransactions()`. Sample CSV: `docs/data/bank-of-the-west-activity-sample.csv`. |
| 2026-04-02 | **Owner CC statement import:** Create form requires **card issuer** (`card_platform`) from `App\Constants\OwnerCcStatementCardPlatform` (City Bank, American Express, Chase Bank). Column `owner_cc_statement_imports.card_platform`; list/show and Download/Upload log show the label. **Column mapping is issuer-specific:** City Bank (Status, Date, Description, Debit, Credit, Member Name); Chase (Card, Transaction/Post Date, Description, Category, Type, signed Amount, Memo); Amex (Date, Receipt, Description, signed Amount). |
| 2026-04-02 | **Merchant Fee Analytics transactions:** Recent transactions, by-processor list, trends chart, and `GET /api/merchant-fees/transactions` now include expenses on **platform fee COAs** (Grubhub/Uber/DoorDash expense accounts), not only merchant processing (6100/6000). `ChartOfAccount::merchantFeeAnalyticsCoaIds()` centralizes the COA set. |
| 2026-04-02 | **Merchant Fee “Recent transactions” data source:** `App\Support\MerchantFeeRecentRows` merges (1) `ExpenseTransaction` rows on analytics COAs **or** linked to `third_party_statement_id`, and (2) **statement summary rows** when imports created statements but no expense lines (e.g. missing platform COA). API `transactions` no longer 404s when only statements exist; defaults date range to current month if omitted. |
| 2026-04-02 | **US date display (MM-DD-YYYY):** `config/dates.php` defines `dates.display` (`m-d-Y`) and datetime variants; Blade/Carbon use `config('dates.display')` etc. after a bulk update. `public/js/date-formatter.js` shows **MM-DD-YYYY** (dash) with hidden `YYYY-MM-DD` for submits. `ConvertDateFormat` middleware still accepts slash or dash US input. Optional env: `DATE_DISPLAY_FORMAT`, `DATE_DISPLAY_DATETIME_FORMAT`. |
| 2025-03-04 | **Merchant Fees navbar:** Added dropdown “Merchant Fees” with Merchant Fee Analytics, Third-Party Platforms, Exceptions Report, Download/Upload Log, Admin Reports (admin only). Removed standalone Audit Logs link for Admin/Owner. |
| 2025-03-04 | **Exceptions Report:** New page at `/merchant-fees/exceptions` (`ReviewQueueViewController@exceptionsReport`, view `admin.exceptions-report.index`). “Remember for future uploads” default on; uses `POST /api/expenses/{id}/resolve` with `create_mapping_rule` and `TransactionMappingRule`. |
| 2025-03-04 | **Download/Upload log:** New page at `/merchant-fees/import-log` (`ImportLogController@index`, view `admin.import-log.index`). Aggregates CC, bank, and online platform imports; back link to Merchant Fees. |
| 2025-03-04 | **Audit Logs → Admin Reports:** UI only rename in nav and in `audit-logs/index.blade.php`, `audit-logs/show.blade.php`. Routes unchanged (`audit-logs.index`, `/audit-logs`). |
| 2025-03-04 | **CURSOR.md and docs:** Created `CURSOR.md` at project root; moved/organized all .md files under `docs/` with structure (architecture, guides, features, compliance, development). |
| 2025-03-04 | **CURSOR.md section names:** Renamed sections to Overview, Where Docs Live, Codebase Map (Important Paths), Merchant Fees Nav & Routes, Exceptions Report (Unrecognized Transactions), Import Log (Download/Upload History), Run & Test (Commands), Changelog (Core Changes). |
| 2025-03-04 | **Nav: Merchant Fees single dropdown:** Owner CC Statements and Merchant Fees merged into one nav item “Merchant Fees” with sub-items: Owner CC Statements, divider, Merchant Fee Analytics, Third-Party Platforms, Exceptions Report, Download/Upload Log, Admin Reports (admin only). |
