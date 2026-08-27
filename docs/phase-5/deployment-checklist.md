# Phase 5 Part 1 — Deployment Checklist

Run top to bottom. Every step is either a command you paste or a box you tick.
Nothing here needs downtime.

---

## Before you start

- [ ] Confirm the client has sent the final cleaned spreadsheet.
- [ ] Confirm which store the spreadsheet belongs to (`php artisan tinker --execute="App\Models\Store::all(['id','store_info'])->each(fn(\$s) => print(\$s->id.' '.\$s->store_info.PHP_EOL));"`).
- [ ] Confirm the queue worker is running in production. Notifications are queued now, so without a worker no reminder or order email is delivered.

---

## 1. Back up (non-negotiable)

```bash
mysqldump -u "$DB_USERNAME" -p "$DB_DATABASE" > backup-pre-phase5-$(date +%Y%m%d-%H%M).sql
ls -lh backup-pre-phase5-*.sql        # confirm it is not 0 bytes
```

Keep this file until the client has signed off. It is the rollback.

---

## 2. Deploy the code

```bash
php artisan down --render="errors::503"   # optional; migrations here are additive
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run production
```

---

## 3. Migrate

Ten migrations ship in this phase. All are **additive**: new tables and new
nullable columns. None drops or renames anything, so existing data is untouched.

```bash
php artisan migrate --force
```

| Migration | What it does |
|---|---|
| `2026_08_25_000001` | `vendors`: adds `website`, `deleted_at` |
| `2026_08_25_000002` | creates `inventory_categories`, seeds the 8 order-guide categories |
| `2026_08_25_000003` | `inventory_items`: adds category FK, portion fields, notes, `deleted_at`; backfills the category from the legacy string |
| `2026_08_25_000004` | creates `inventory_item_vendor` |
| `2026_08_25_000005` | backfills that pivot from existing `vendor_prices` and preferred vendors |
| `2026_08_25_000006` | creates `store_inventory_targets` |
| `2026_08_25_000007` | `inventory_stock`: adds `notes` |
| `2026_08_25_000008` | `order_items`: adds `suggested_quantity`, `is_manual_override` |
| `2026_08_25_000009` | `orders`: adds `notes`; `order_items`: adds `line_total`, `notes`; backfills line totals |
| `2026_08_26_000001` | creates `notifications` |

Verify:

```bash
php artisan migrate:status | tail -12
```

---

## 4. Clear caches (required after route changes)

```bash
php artisan route:clear && php artisan config:clear && php artisan view:clear && php artisan cache:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 5. Seed reference data

Safe to run on an existing database. Every seeder is idempotent.

```bash
php artisan db:seed --class=VendorsSeeder --force
php artisan db:seed --class=InventoryCategoriesSeeder --force
```

Do **not** run `InventoryItemsSeeder` or `StoreInventoryTargetsSeeder` in
production. Those are sample data for development; the real items come from the
client's spreadsheet in the next step.

---

## 6. Import the client's data

Dry run first. It writes nothing.

```bash
php artisan inventory:import-from-excel /path/to/client-file.xlsx --store=1
```

Read the preview. Check specifically:

- [ ] Row count matches what the client expects.
- [ ] "Vendors to create" contains nothing that is a misspelling of an existing vendor.
- [ ] "Retired vendors remapped" shows Sysco / Cisco / K&M / Nogales going to their replacement.
- [ ] "Categories to create" are real categories, not typos.
- [ ] Warnings are acceptable. Rows with warnings still import, using a default pack size of 1.

Then commit:

```bash
php artisan inventory:import-from-excel /path/to/client-file.xlsx --store=1 --commit
```

The whole import is one transaction. If it fails halfway, nothing is written.

Useful flags: `--no-new-vendors` and `--no-new-categories` skip rows rather than
creating anything new.

---

## 7. Set the stock targets

The import does not set targets, because only the client knows them.

- [ ] Open `/stores/{id}/inventory-targets`.
- [ ] Use **Set default for all** for a starting number, then adjust the items that differ.
- [ ] For a second store, use **Copy from another store**.

Without targets, order suggestions come back as zero. This step is what makes the
Monday workflow produce real numbers.

---

## 8. Confirm the schedule

```bash
php artisan schedule:list
```

- [ ] `inventory:remind` — Mondays 06:00
- [ ] `inventory:remind-overdue` — Wednesdays 08:00
- [ ] `inventory:open-week` — Mondays 00:05
- [ ] `inventory:generate-variance` — Tuesdays 02:00

And that cron is actually calling the scheduler:

```bash
crontab -l | grep schedule:run
# expected: * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Smoke test in production

- [ ] Sign in as the manager. The Inventory menu shows Weekly Count, Items, Categories, Stock Targets, Prices, Price Comparison, Orders.
- [ ] `/inventory/weekly-count` lists the imported items, grouped by category.
- [ ] Enter one count. Wait 30 seconds. Confirm "Draft saved at ...".
- [ ] `/inventory/weekly-count/suggestions` shows a suggested quantity.
- [ ] Generate an order, open its vendor report, download the PDF.
- [ ] Mark it placed. Confirm the admin receives the notification.
- [ ] Delete that test order (`/orders` → Cancel, then delete the draft).

```bash
php artisan up
```

---

## Rollback

**If migrations are the problem** (before any real data is entered):

```bash
php artisan migrate:rollback --step=10
```

All ten `down()` methods are implemented and were verified to round-trip.

**If the import went wrong** but the schema is fine, undo just the import:

```bash
php artisan tinker
>>> $storeId = 1;
>>> App\Models\InventoryItem::where('store_id', $storeId)->forceDelete();  // cascades mappings and prices
```

Then re-run the import with a corrected file.

**If anything else is wrong**, restore the backup:

```bash
mysql -u "$DB_USERNAME" -p "$DB_DATABASE" < backup-pre-phase5-YYYYMMDD-HHMM.sql
git checkout <previous-release-tag>
composer install --no-dev --optimize-autoloader
php artisan route:clear && php artisan config:clear && php artisan view:clear
```

Phase 5 Part 1 adds no destructive migration, so a code-only rollback plus cache
clear returns the app to its prior behaviour with the new tables sitting unused.

---

## Known limitations to tell the client

1. **Pack size defaults to 1** for any spreadsheet row with a blank Pack Size. Their current sheet leaves that column empty throughout, so every item will need its real pack size entered under Inventory → Items before order quantities are meaningful.
2. **Prices are only imported when the Cost column has a value.** Their current sheet has none, so prices need entering under Pricing → Update.
3. **Targets are not imported.** See step 7.
