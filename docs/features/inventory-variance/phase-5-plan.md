# Phase 5 — Inventory, Multi-Vendor Ordering & Variance: Build Plan

> **Status:** proposed, pending approval. No application code will be written
> until this plan and [variance-formula.md](variance-formula.md) are approved.

Phase 5 adds a full inventory → recipe → sales → ordering → variance loop on top
of the existing Laravel 12 / MySQL / Blade + Bootstrap (Tabler) app. It is 8
modules and ~12 tables; it will be built and reviewed **incrementally**, in the
order in §7, with the variance engine first (the spec's most-important module,
built test-first).

---

## 1. Decisions I need you to confirm

These change the build materially; the rest of the plan assumes the **recommended**
option.

| # | Decision | Options | Recommendation |
|---|----------|---------|----------------|
| D1 | **UI stack.** The spec says "Livewire or Inertia (match existing project)" and lists "Livewire components." But the existing app has **no** Livewire/Inertia — it's Blade + Bootstrap 5 (Tabler) + jQuery/AJAX (the two-layer controller pattern in CLAUDE.md). | (a) Match existing: Blade + Bootstrap + AJAX. (b) Introduce Livewire alongside it. | **(a) Match existing.** "Match existing project" wins over the literal "Livewire"; one paradigm is cheaper to maintain and the mobile forms work fine in Bootstrap. I'll flag any screen where Livewire would clearly help. |
| D2 | **Inventory item scope.** Are items a per-store list or a shared master? | (a) Per-store (`store_id` on `inventory_items`). (b) Global master + per-store stock. | **(a) Per-store.** Simplest, matches "single-store variance"; a store owns its item list, params, and counts. |
| D3 | **Vendor cleanup ambiguity.** Spec says *remove* Cisco, K&M Distributors, Nogales Produce and *keep* Lisanti, Restaurant Depot, Sam's Club, Coca-Cola, Walmart, HEB. The current seeder has **Sysco** (not "Cisco"), and Sysco is **not** in the keep-list. | (a) "Cisco" = typo for **Sysco** → remove Sysco. (b) Leave Sysco; only remove literal Cisco/K&M/Nogales (none of which exist yet). | **Needs your call.** I'll assume (a) *remove Sysco* unless you say otherwise, since it's absent from the keep-list. |
| D4 | **Employee role reach.** New `employee` role "restricted to inventory entry only." | Confirm employees may ONLY: log in, see their store's current inventory week, enter/save/submit counts. No reports, no orders, no P&L. | Proceed as stated. |
| D5 | **Week definition.** "Weekly" keyed by Monday. | `week_start_date` = the Monday (date), in the app timezone (`config/app.php`). Cutoff = Monday 23:59 local. | Proceed. |

---

## 2. Data model

New tables (Eloquent model in parentheses). Money = `decimal(12,2)`; **quantities =
`decimal(14,4)`** (variance precision, per the formula doc); every table
`store`-attributed + timestamped, consistent with Phase 3.

### Core (variance inputs)

- **`inventory_items`** (`InventoryItem`) — the per-store master list.
  `id, store_id, category (enum: meat|bread|cheese|veg|packaging|beverage),
  name, base_unit, purchase_unit, units_per_purchase decimal(14,4),
  min_stock_level, safety_buffer_pct, reorder_threshold, is_active, timestamps`.
  (min/safety/reorder are the module-4 stock-up params.)

- **`inventory_stock`** (`InventoryStock`) — one row per item · week.
  `id, inventory_item_id, store_id, week_start_date (Mon),
  starting_stock decimal(14,4), actual_ending_stock decimal(14,4) nullable,
  status (draft|submitted), counted_by, counted_at, timestamps`.
  Unique `(inventory_item_id, week_start_date)`. Starting stock defaults from the
  prior week's `actual_ending_stock`.

- **`menu_items`** (`MenuItem`) — sellable products.
  `id, store_id, name, category, square_name (for import matching), is_active, timestamps`.

- **`recipes`** (`Recipe`) — a **versioned** recipe for a `(menu_item, size_variant)`.
  `id, menu_item_id, size_variant (enum: mini|regular|large), version,
  is_current, created_by, notes, timestamps`.

- **`recipe_ingredients`** (`RecipeIngredient`) — the N ingredients of a recipe
  *(added beyond the named `recipes` table — required for "N ingredients")*.
  `id, recipe_id, inventory_item_id, quantity_base decimal(14,4),
  entered_quantity, entered_unit, timestamps`. `quantity_base` is the portion in
  the item's base unit; `entered_*` preserve what the admin typed.

- **`menu_items_sold`** (`MenuItemSold`) — Square "Items Sold" rows.
  `id, store_id, week_start_date, menu_item_id nullable, size_variant nullable,
  square_raw_name, quantity_sold decimal(14,4), import_batch_id, is_matched,
  timestamps`. Unmatched rows keep `menu_item_id = null` until an admin maps them.

### Ordering & pricing

- **`orders`** (`Order`) — `id, store_id, vendor_id, week_start_date,
  order_sequence (1|2), status (draft|placed|received), placed_at, received_at,
  created_by, timestamps`.
- **`order_items`** (`OrderItem`) — `id, order_id, inventory_item_id,
  quantity decimal(14,4), unit, unit_price decimal(12,2) nullable, timestamps`.
- **`vendor_prices`** (`VendorPrice`) — price history.
  `id, vendor_id, inventory_item_id, price decimal(12,2), price_unit,
  effective_date, entered_by, timestamps`. "Cheapest vendor" = min current price
  per item across vendors.

### Variance output

- **`variance_reports`** (`VarianceReport`) — snapshot header.
  `id, store_id, week_start_date, status, generated_by, generated_at, timestamps`.
- **`variance_report_lines`** (`VarianceReportLine`) — per-item snapshot
  *(added; the report needs per-item detail)*.
  `id, variance_report_id, inventory_item_id, starting_stock, ordered_qty,
  total_available, theoretical_usage, theoretical_ending, actual_ending,
  variance, variance_pct, severity (green|yellow|red), base_unit,
  is_incomplete, timestamps` — all quantities `decimal(14,4)`.

### Reused / extended

- **`sales_projections`** (exists) — extend with `week_start_date` for the
  weekly dollar projection that drives stock-up (module 4). Per-item stock-up
  params live on `inventory_items`.
- **`vendors`** (exists) — reused for orders/prices; seeder updated (D3).

---

## 3. Roles & permissions

Add `EMPLOYEE` to `App\Enums\UserRole` (below `manager`). Permissions:

| Capability | admin | owner | manager | employee |
|------------|:---:|:---:|:---:|:---:|
| Inventory entry (own store) | ✓ | ✓ | ✓ | ✓ (only this) |
| Recipes / menu items | ✓ | ✓ | – | – |
| Square import | ✓ | ✓ | ✓ | – |
| Orders / prices | ✓ | ✓ | ✓ | – |
| **Variance report** | ✓ | ✓ | ✓ (manager-only per spec) | – |
| Dashboard | ✓ | ✓ | ✓ | own-store status only |

Enforced via the existing `role:`/`permission:` middleware and the `TenantScoped`
store boundary. Employees are hard-limited to inventory routes.

## 4. Scheduled jobs

- **`OpenInventoryWeek`** — every Monday 00:05 local: create the new week's
  `inventory_stock` rows for each active item per store, seeding `starting_stock`
  from last week's `actual_ending_stock`.
- **`SendMondayInventoryReminder`** — Monday 07:00: email/in-app nudge to each
  store's employees + manager to complete the count before EOD cutoff.
- **`GenerateWeeklyVariance`** — e.g. Tuesday 02:00: build the prior week's
  `variance_report` once stock + sales are in; raise alerts for 🔴 lines.

All registered in `routes/console.php` (Laravel 12 scheduler) and idempotent.

## 5. Module → design summary

1. **Inventory entry** — mobile Blade form (large touch targets), drafts, Monday
   EOD cutoff guard, employee-scoped. Weekly rows via `OpenInventoryWeek`.
2. **Recipe & portion mapping** — admin CRUD, size variants, CSV/Excel import
   (`maatwebsite/excel`, already a dep), version history (new `recipes` version +
   `is_current` flip; old versions retained).
3. **Square sales import** — CSV wizard: upload → parse → match to `menu_items`
   by `square_name` → resolve unmatched → preview → commit into `menu_items_sold`.
   Reuses Phase 4 import patterns (dedupe, raw-file storage, decision-free).
4. **Sales projection & stock-up** — weekly $ projection → per-item required qty
   = `projected × historical_usage_ratio`, buffered by `safety_buffer_pct`, floored
   at `min_stock_level`; suggest order qty; manual override.
5. **Multi-vendor orders** — combined weekly list with vendor label per item,
   Order 1 & Order 2, placed/received states, per-vendor history, printable/copy view.
6. **Price entry & comparison** — per-vendor price form, bulk update, side-by-side
   A/B/C comparison, auto-flag cheapest, `vendor_prices` history.
7. **Variance report** — see [variance-formula.md](variance-formula.md).
   Filters (date range, item, store), color coding, drill-down (variance → the
   menu items + quantities that produced the usage), manager-only, PDF
   (`dompdf`) + Excel (`maatwebsite/excel`) export.
8. **Dashboard** — current-week status (stock progress, pending orders, variance
   alerts), Monday reminder, configurable large-variance alerts.

## 6. Deliverables → where they land

Migrations (§2) · models with relationships & casts · controllers + routes +
form requests · **Blade + Bootstrap** views (D1) · `VarianceCalculationService`
with 100% coverage · scheduled jobs (§4) · seeders (updated vendors D3, sample
recipes) · this README + the variance formula doc.

## 7. Build sequence (each a reviewable increment)

| Step | Delivers | Tests |
|------|----------|-------|
| **5.1** | Data foundation (all migrations + models + factories) **and** `VarianceCalculationService` **test-first, 100%** | variance unit tests (the critical gate) |
| 5.2 | Inventory entry + `employee` role + `OpenInventoryWeek` job | entry flow, cutoff, role isolation |
| 5.3 | Recipe & portion mapping (+ CSV import, versioning) | recipe versioning, import accuracy |
| 5.4 | Square sales import wizard | parser accuracy, unmatched handling |
| 5.5 | Sales projection & stock-up | stock-up algorithm |
| 5.6 | Multi-vendor order generation | Order 1/2, states |
| 5.7 | Price entry & comparison | cheapest-vendor logic |
| 5.8 | Variance report UI + PDF/Excel export | report assembly, export |
| 5.9 | Dashboard + Monday reminder + alerts | alert thresholds |

I'll pause for review after 5.1 (the variance engine) before continuing.

## 8. Open questions

1. **D3** — remove Sysco (treat "Cisco" as the typo), or keep it?
2. **Historical usage** for stock-up (module 4): how many prior weeks to average,
   and how to seed it before any history exists (fall back to projection-only)?
3. **Size variants** — is the fixed set mini/regular/large enough, or do you need
   arbitrary named sizes per menu item?
4. **Variance thresholds** — are the 2% / 5% defaults right, and set per-store,
   per-item, or global?
