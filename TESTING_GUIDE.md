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

## Test 3: Admin Cannot Be Assigned via Store Assignment Form

### Steps:
1. Log in as Admin
2. Go to Stores → Select any store
3. Click "Assign Owners" (or similar button)
4. Check the list of available owners

### Expected Result:
- Only Owner users should appear in the checkbox list
- Admin users should NOT be visible
- If you try to submit with an admin ID (via form manipulation), validation should fail

---

## Test 4: Owner Store Assignment Works Correctly

### Steps:
1. Log in as Admin
2. Go to Owners → Select an owner
3. Click "Assign Stores" or "Manage Stores"
4. Select some stores and save
5. Go back to the owner profile page

### Expected Result:
- Selected stores should appear in the "Assigned Stores" section on the owner profile
- Stores should be properly linked via the pivot table

---

## Test 5: Unassigned Stores Go to Franchisor

### Steps:
1. Log in as Admin
2. Go to Owners → Select an owner that has stores assigned
3. Click "Assign Stores"
4. Uncheck all stores (deselect everything)
5. Save
6. Go to Stores → Check one of those unassigned stores
7. Go to "Assign Owners" for that store

### Expected Result:
- The unassigned store should now be assigned to "Franchisor" owner
- When viewing the store's owners, "Franchisor" should appear

---

## Test 6: Store Creation Also Assigns via Pivot Table

### Steps:
1. Log in as Admin
2. Go to Stores → Create Store
3. Fill in all fields including selecting an Owner
4. Create the store
5. Go to the newly created store
6. Click "Assign Owners" or view owners

### Expected Result:
- The selected owner should appear in the store's assigned owners list
- This should work via the pivot table, not just `created_by`

---

## Test 7: Validation at API/Form Level

### Steps (Using Browser DevTools):
1. Log in as Admin
2. Go to Stores → Create Store
3. Open Browser DevTools (F12) → Network tab
4. Fill form and submit
5. Before submitting, intercept the request or modify form data
6. Try to set `created_by` to an admin user ID
7. Submit

### Expected Result:
- Server should return validation error: "Only owners can be assigned to stores. Admins cannot be assigned."
- Store should NOT be created

---

## Test 8: Update Store with Owner Validation

### Steps:
1. Log in as Admin
2. Go to Stores → Edit a store
3. Try to change the owner to an Admin user (if dropdown shows admins, which it shouldn't)
4. Save

### Expected Result:
- If admin is somehow selected, validation should fail
- Error message: "Only owners can be assigned to stores. Admins cannot be assigned."

---

## Quick Test Checklist

- [ ] Franchisor owner exists in system
- [ ] Store creation form only shows owners (not admins)
- [ ] Store assignment form only shows owners (not admins)
- [ ] Cannot assign admin to store via validation
- [ ] Owner-store assignments appear on owner profile
- [ ] Unassigned stores automatically go to Franchisor
- [ ] Store creation assigns owner via pivot table
- [ ] Store update validates owner-only assignment

---

## Database Verification (Optional)

### Check Pivot Table:
```sql
SELECT * FROM owner_store;
```
- Should show owner-store relationships
- Should NOT have any admin user IDs

### Check Franchisor:
```sql
SELECT * FROM users WHERE LOWER(name) = 'franchisor' AND role = 'owner';
```
- Should return one row with the Franchisor owner

### Check Store Assignments:
```sql
SELECT s.id, s.store_info, u.name as owner_name, u.role 
FROM stores s 
LEFT JOIN owner_store os ON s.id = os.store_id 
LEFT JOIN users u ON os.owner_id = u.id;
```
- All stores should have at least one owner assigned
- No admin users should appear as owners

---

## Troubleshooting

### If Franchisor doesn't exist:
- Try assigning/unassigning stores to trigger creation
- Or manually create via: `php artisan tinker` → `\App\Models\User::getOrCreateFranchisor();`

### If validation isn't working:
- Clear cache: `php artisan cache:clear`
- Check that validation rules are in place in StoreController and UpdateStoreRequest

### If stores don't show on owner profile:
- Verify pivot table has entries: `SELECT * FROM owner_store WHERE owner_id = [owner_id];`
- Check that OwnerController::show() uses `$owner->ownedStores`
