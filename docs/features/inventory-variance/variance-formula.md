# Variance Report — Formula & Unit Conventions

> **Status:** design (Phase 5, pending approval). This document defines the exact
> math for the variance engine. Per the project constraint, the
> `VarianceCalculationService` will be built **test-first** against these rules,
> with 100% coverage. This file is the single source of truth for the formula.

## 1. Purpose

Detect inventory loss — theft, waste, over-portioning, spillage, or miscounts —
by comparing **what should be left** (theoretical) with **what is actually left**
(a physical count). A large gap is a signal to investigate, not an accusation.

## 2. The formula (per inventory item · per store · per week)

All quantities are expressed in the item's **base unit** (see §4). For one item:

| Symbol | Name | Source |
|--------|------|--------|
| `S`  | Starting Stock | opening physical count for the week (= prior week's actual ending, or entered) |
| `O`  | Ordered Quantity | Σ received `order_items` for the item this week, converted to base unit |
| `A`  | **Total Available** | `A = S + O` |
| `U`  | **Theoretical Usage** | `U = Σ_m ( qty_sold(m) × portion(m → item) )` over every menu item `m` sold |
| `TE` | **Theoretical Ending** | `TE = A − U` |
| `AE` | Actual Ending Stock | closing physical count for the week |
| `V`  | **Variance** | `V = TE − AE` |

```
Total Available    = Starting Stock + Ordered Quantity
Theoretical Usage  = Σ (items sold × portion per item)
Theoretical Ending = Total Available − Theoretical Usage
Variance           = Theoretical Ending − Actual Ending Stock
```

`portion(m → item)` = how much of **this** ingredient one unit of menu item `m`
(at its **size variant**) consumes, in the item's base unit.

### Sign convention

- **`V > 0` → SHORT / loss.** Actual is *less* than theoretical: more was consumed
  than recipes account for (waste, theft, over-portioning, breakage). This is the
  "problem" direction.
- **`V < 0` → OVER.** Actual is *more* than theoretical: under-portioning,
  miscount, or unrecorded returns.
- **`V = 0` → perfect** (rare).

### Variance percentage

```
variance_pct = V / A × 100      (percent of total available; A = 0 → variance_pct = 0, line flagged)
```

We normalise against **total available** so a fixed absolute gap reads as small on
a high-volume item and large on a low-volume one. Thresholds may also be set as
absolute base-unit amounts (see §3).

## 3. Color coding (configurable per store, item defaults)

| Band | Condition (defaults) | Meaning |
|------|----------------------|---------|
| 🟢 GREEN | `|variance_pct| ≤ 2%` | acceptable |
| 🟡 YELLOW | `2% < |variance_pct| ≤ 5%` | investigate |
| 🔴 RED | `|variance_pct| > 5%` | problem |

Thresholds are configurable (per store, with per-item overrides) and can be
expressed as a percentage **or** an absolute base-unit quantity; the stricter of
the two applies. The large-variance dashboard alert uses the same thresholds.

## 4. Unit conventions — **never mix silently**

Every item declares its units up front:

| Field | Meaning | Example (Ribeye) |
|-------|---------|------------------|
| `base_unit` | the unit **all** variance math uses | `oz` |
| `purchase_unit` | how it is ordered | `case` |
| `units_per_purchase` | explicit conversion `purchase_unit → base_unit` | `640` (1 case = 640 oz) |

**Rules enforced by the engine:**

1. All of `S`, `O`, `U`, `AE` are converted to **base unit** before any arithmetic.
2. **Order quantities** entered in `purchase_unit` convert explicitly:
   `base_qty = order_qty × units_per_purchase`.
3. **Recipe portions** are stored in the item's base unit. The recipe UI performs
   the conversion at entry time and stores the base-unit value *plus* the
   human-entered value/unit for display (e.g. "1.5 portions × 3 oz = **4.5 oz**").
4. **If a quantity's unit has no explicit conversion to the item's base unit, the
   engine rejects it** — it never guesses or coerces. `VarianceCalculationService`
   asserts unit consistency and throws `UnitMismatchException`.
5. Internal quantities use `decimal(14,4)` to avoid rounding drift; results are
   rounded only for display.

## 5. Worked example

**Item:** Ribeye Steak · `base_unit = oz` · 1 case = 640 oz.

| Step | Value |
|------|-------|
| Starting stock `S` | 320 oz |
| Received `O` | 2 cases × 640 = **1280 oz** |
| Total Available `A` | 320 + 1280 = **1600 oz** |
| Sold | 200 × *Standard Steak Sandwich* |
| Recipe portion | 1 Standard = 1.5 portions × 3 oz = **4.5 oz** steak |
| Theoretical Usage `U` | 200 × 4.5 = **900 oz** |
| Theoretical Ending `TE` | 1600 − 900 = **700 oz** |
| Actual Ending `AE` (counted) | **680 oz** |
| **Variance `V`** | 700 − 680 = **+20 oz SHORT** |
| variance_pct | 20 / 1600 = **1.25% → 🟢 GREEN** |

If the count came back **600 oz**: `V = +100 oz`, `pct = 6.25% → 🔴 RED`.

## 6. Multiple menu items share an ingredient

Usage sums over **every** menu item and size variant that consumes the ingredient:

```
U(steak) =  qty(Standard Steak)  × 4.5 oz
          + qty(Mini Steak)      × 3.0 oz
          + qty(Large Steak)     × 6.0 oz
          + qty(Cheesesteak Egg Roll) × 1.0 oz
          + …
```

## 7. Edge cases & guards (each is a unit test)

| Case | Handling |
|------|----------|
| **Unmatched Square item** (no menu-item mapping) | excluded from `U`; report warns "N unmatched items — usage understated". Understated usage inflates `TE`, producing a false "OVER". |
| **Sold size variant has no recipe** | that line excluded, flagged in the report. |
| **Missing `S` or `AE`** | line marked *incomplete*; not computed, not colored. |
| **Unit with no conversion** | hard error (`UnitMismatchException`), never silent. |
| **`A = 0`** | `variance_pct = 0`, line flagged (no denominator). |
| **Negative theoretical ending** (`U > A`) | allowed and shown — it means recipes claim more usage than was available (bad data or big loss); surfaced prominently. |
| **Rounding** | math in `decimal(14,4)`; display rounded to the item's display precision. |

## 8. Service structure & test plan

`App\Services\Inventory\VarianceCalculationService`

- `calculate(int $storeId, int $itemId, CarbonInterface $weekStart): VarianceLine`
  — pure & deterministic; reads inputs, returns the computed line; **no writes**.
- Helpers, each independently tested: `totalAvailable()`, `theoreticalUsage()`,
  `convertToBase()`, `severityFor()`.
- Report generation (`variance_reports` + `variance_report_lines`) is a separate
  step that persists a snapshot of the computed lines.

**Tests written first (100% coverage):** the §5 worked example; multi-menu-item
usage (§6); each size variant; unmatched-item exclusion + warning; explicit
unit-conversion correctness; `UnitMismatchException` on unknown unit; missing
starting/ending count; `A = 0`; negative theoretical ending; each color-band
boundary (green/yellow/red edges); rounding to `decimal` scale.

## 9. What variance does **not** do

- It does not integrate with Square, vendor APIs, or scrape sites (CSV only).
- It is **single-store** — no cross-store comparison in this phase.
- It never auto-adjusts stock; it only reports. Corrections are a human action.
