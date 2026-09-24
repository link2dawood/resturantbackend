# Open questions from the 2026-09-22 client meeting

Everything else from that meeting is built. These are the items the client did
not settle, kept here so they do not live only in a chat log.

---

## 1. Employee meals (meeting item 11)

**Why it matters more than it sounds.** Variance assumes every piece that leaves
the shelf was sold. Staff food is not sold, so it reads as loss. Ten staff steak
sandwiches a week is about 20 pieces of "missing" steak, on the single item the
client watches hardest. Left alone, the report cries wolf where it can least
afford to.

The client raised two shapes and settled on neither: a manager logging it
separately, or an "employee consumption" input.

### Option A: a menu item called "Employee Meal" (works today, nothing to build)

Create a menu item per staff meal type, give it the same recipe as the sandwich,
and enter the count on **Square Import → Enter sales by hand** each week.

- Nothing to build; it can start this Monday.
- The stock is deducted correctly, so variance stops flagging staff food.
- Verified by `staff_meals_can_be_recorded_today_as_a_menu_item`
  in `tests/Feature/ManualSalesEntryTest.php`.
- Limit: staff food is mixed in with sales, so no report separates the two, and
  the cost of staff meals cannot be totalled on its own.

### Option B: a staff-meals column on the count screen (moderate build)

The manager logs meals as they happen; variance subtracts them as their own
term, and the report shows sold, staff, and unexplained separately.

- Honest reporting: the owner sees what staff food actually costs per week.
- Needs a new table, a variance term, a column on the report, and the manager's
  screen to carry it. Roughly a day and a half.

### Option C: per-employee logging

Who ate what, against a per-person allowance.

- Only worth it if the client intends to cap or bill staff meals.
- Not worth building before the weekly loop is running with real numbers.

**Recommendation:** start with A on Monday so the variance numbers are not
poisoned from week one, and decide between B and C once a few weeks of real
figures show what staff food actually costs.

---

## 2. Which other items to group (meeting item 6)

8" and 10" bread are grouped, along with tortillas and pita, under
"Bread & Wraps". The client said "similar grouping for other logically-related
items" without naming them. This is data, not code: set `item_group` on each
item and they cluster on the count screen. Needs the client's list.

---

## 3. Bread's order range (meeting item 7)

Bread's order dropdown counts to 10. 8" and 10" bread are ordered separately, so
confirm whether 10 is the cap per size or across both.

---

## 4. Bottle case sizes (meeting item 5)

The six drinks now exist as "(Bag-in-Box)" and "(20oz Bottle)". The bottle rows
carry a pack size of 1 until the client says how many bottles are in a case.
Until then those items count with the partial dropdown rather than loose pieces.

---

## 5. Chicken is counted in pounds

Chicken is 40 **lb** per box, not 40 pieces, so its partial column counts pounds.
If the kitchen counts chicken in pieces, we need the pieces-per-box figure.

---

## 6. Variance colour bands

The percentage is now measured against assumed on hand, which makes every
percentage larger than under the old formula. The 2% and 5% bands are unchanged,
so expect more yellow and red than the old numbers implied. Worth resetting once
real counts arrive.
