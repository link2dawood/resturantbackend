# Weekly Ordering — Owner Quick Start

For the owner (and admin). The manager counts the shelf; you decide what to buy.

---

## Monday: the counts come to you

The manager counts every item and submits. You do not have to chase it: the
dashboard tells you where each store is up to.

Open **Inventory → Weekly Count → Order Suggestions**, or go straight to
`/inventory/weekly-count/suggestions`.

For each item the system works out:

```
Suggested order  =  your stock target  −  what the manager counted
```

Everything is in the unit you buy in, so "11" against Steak means **11 boxes**.
Nothing is ever suggested as part of a box, because you cannot buy part of one.

- Items that need nothing are hidden. Untick **Hide items that need no order** to
  see them all.
- **Change any number you like.** Type over it. Anything you change is marked
  *overridden*, so later you can tell a judgement call from a system number.
- Check the vendor on each line. Any item can be bought from any vendor that
  sells it, so if one is cheaper this week, switch it here.
- An item with no stock target suggests nothing and says so. Set the target in
  **Inventory → Stock Targets**.

Tap **Generate Order**. You get one draft order per vendor.

---

## Check the drafts, then approve

You land on **Orders** with a draft per vendor.

1. Tap **View** to check the lines. While it is a draft you can change
   quantities, prices, add notes, or move a line to a different vendor's order.
2. When it is right, tap **Mark placed**.

Marking it placed does two things:

- It locks the lines, because that is what the store is about to tell the vendor.
- It **sends the order to the store manager** as a notification and an email with
  the order sheet attached as a PDF, along with how that vendor takes orders.

The manager places it with the vendor and checks the delivery in.

> Need a second order this week? On any Order 1, tap **Duplicate for Order 2**.
> It copies the same lines at the same quantities into a new draft; edit it down
> to what you actually need.

---

## After the delivery

The manager records what actually arrived, item by item. Anything over or short
is flagged on the order and on the Orders list as **mismatch**.

Check those before you pay the invoice. A vendor sending more than was ordered is
exactly what this step is there to catch.

---

## Order history

**Orders → Order history** lists everything ordered, grouped by vendor.

- Tap the arrow on any row to see its line items.
- Tap **Reorder** to copy that order into a new draft for this week.
- Filter by vendor, week, or status.

---

## Setting stock targets

A target is **how much you want on the shelf after the delivery**, in the unit
you buy in. Steak target 15 means "I want 15 boxes in the walk-in".

- Per store: **Inventory → Stock Targets**.
- In bulk from the command line:

  ```
  php artisan inventory:set-targets --store=1 --category=Meats
  ```

  It shows what it would do and changes nothing. Add `--apply` to write, and
  `--overwrite` to replace targets that are already set.

Targets are the single biggest lever on order quality. If the suggestions look
wrong, the target is usually the reason, not the maths.
