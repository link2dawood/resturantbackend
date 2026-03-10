# Testing Guide: Admin Store Assignment Prevention & Franchisor Owner

## Prerequisites
1. Make sure you have at least one Admin user
2. Make sure you have at least one Owner user (not Franchisor)
3. Have some stores created in the system

## Test 1: Verify Franchisor Owner is Created

### Steps:
1. Log in as Admin
2. Go to Owners section
3. Look for an owner named "Franchisor"
4. If it doesn't exist, try assigning/unassigning stores to an owner (this will trigger creation)

### Expected Result:
- A "Franchisor" owner should exist with email "franchisor@system.local"
- This owner should appear in the owners list

---

## Test 2: Admin Cannot Be Assigned When Creating a Store

### Steps:
1. Log in as Admin
2. Go to Stores → Create Store
3. Fill in all required fields
4. In the "Owners" dropdown, try to select an Admin user
5. Submit the form

### Expected Result:
- The dropdown should ONLY show Owner users (not Admin users)
- If you somehow submit with an admin ID, you should get validation error: "Only owners can be assigned to stores. Admins cannot be assigned."

---

## Test 3–8 and checklists

See full guide in repo history or run: `git show HEAD:TESTING_GUIDE.md` (file was moved to `docs/guides/testing-guide.md`). Covers: Admin not assignable via store form, owner store assignment, unassigned stores → Franchisor, pivot table, validation, DB verification, troubleshooting.

## Quick Test Checklist

- [ ] Franchisor owner exists in system
- [ ] Store creation form only shows owners (not admins)
- [ ] Store assignment form only shows owners (not admins)
- [ ] Cannot assign admin to store via validation
- [ ] Owner-store assignments appear on owner profile
- [ ] Unassigned stores automatically go to Franchisor
- [ ] Store creation assigns owner via pivot table
- [ ] Store update validates owner-only assignment

## Troubleshooting

- **Franchisor doesn't exist:** Assign/unassign stores to trigger creation, or `php artisan tinker` → `\App\Models\User::getOrCreateFranchisor();`
- **Validation not working:** `php artisan cache:clear`; check StoreController and UpdateStoreRequest
- **Stores don't show on owner profile:** Verify `owner_store` pivot and `OwnerController::show()` uses `$owner->ownedStores`
