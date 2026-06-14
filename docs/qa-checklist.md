# Phase 4 — Testing & QA Checklist

Coverage for the SaaS conversion. Automated tests live in `tests/Feature/` and run
with `php artisan test`. Items that require a live Stripe account or a browser are
listed as **manual**.

## 1. Multi-tenant security audit (no data leaks between tenants)
**Automated:** `MultiTenantSecurityAuditTest`, `TenantIsolationTest`
- [x] Each tenant sees only its own rows across every scoped model
  (ExpenseTransaction, DailyReport, ThirdPartyStatement, BankAccount, KpiTarget,
  SalesProjection, ImportBatch, OwnerCcStatementImport).
- [x] Foreign records can't be loaded by id (route-model-binding 404 / null find).
- [x] Admin, franchisor, and background (console/queue/webhook) contexts are unscoped.
- [x] Tenant-scoped file uploads land under `tenant/{storeId}/...`.
- **Manual:** spot-check the live app logged in as two different owners — confirm
  dashboards, reports, imports, and downloads never show the other's data.

## 2. End-to-end sign-up → trial → paid → renewal
**Automated:** `EndToEndSaasFlowTest`, `AuthRegistrationVerificationTest`, `TrialSystemTest`
- [x] Sign up → Owner created, unverified, on a 30-day trial.
- [x] Unverified user blocked from the app; verifying grants access.
- [x] Trial expiry → lockout to the Trial Expired screen.
- [x] Payment webhook → access restored; renewal webhook keeps access.
- [x] Cancellation webhook → locked out again.
- **Manual:** run the real flow in a browser with a Stripe **test** card
  (`4242 4242 4242 4242`): register, verify (check `storage/logs` or inbox),
  add card on `/billing`, confirm subscription + prorated first charge.

## 3. Stripe payment testing
**Automated:** `PaymentSubscriptionTest` (billing anchor, webhook receipts/dunning/cancel, MRR/churn)
- [x] Billing anchors to the 1st with a prorated first charge (`SubscriptionService`).
- [x] `invoice.payment_succeeded` → receipt + access; `invoice.payment_failed` → dunning, no lockout.
- [x] `customer.subscription.deleted` → lockout + cancellation email.
- [x] Managers can't manage billing.
- **Manual — test mode:** trigger events via `stripe trigger` or the dashboard;
  confirm webhooks land at `/stripe/webhook` and emails send.
- **Manual — live sanity:** with live keys, make one real low-value subscription,
  verify the invoice/proration in the Stripe dashboard and the Customer Portal
  (update card, view invoices, cancel), then refund/cancel. See `docs/stripe-setup.md`.

## 4. Role-based access testing
**Automated:** `RoleBasedAccessControlTest`, `PermissionMiddlewareTest`, `DailyReportSecurityTest`
- [x] Admin: full access incl. modify/delete CoA.
- [x] Owner: manages own stores/managers/transactions; can **add** CoA but **not modify** existing.
- [x] Manager: limited to assigned-store operations; can't manage CoA.

## 5. Dashboard calculation accuracy
**Automated:** `DashboardMetricsTest`, `KpiConfigurationTest`, `SalesProjectionCalendarTest`
- [x] Sales vs projection variance (ahead/behind).
- [x] Food/Payroll/Rent % = category spend ÷ net sales, vs user-defined KPI targets.
- [x] Greyed-out state when no data exists.
- [x] Projection-vs-actual on the calendar (per-day + month totals).

## 6. Tenant migration utilities
**Automated:** `TenantMigrationTest`
- [x] `tenant:migrate-existing` assigns unowned stores to the Franchisor + seeds CoA (idempotent; `--dry-run` supported).
- [x] `coa:export-template` snapshots the live chart to `database/data/coa-template.json`.
- [x] New sign-ups get the standard chart of accounts.

---

### Known pre-existing issue (not Phase 4)
`ProfitLossCalculationTest` (10 tests) fails under the SQLite test DB because the
P&L/dashboard analytics use MySQL-only SQL functions (`YEAR`, `MONTH`, `YEARWEEK`).
These pass on MySQL. Tracked separately from the Phase 4 work.
