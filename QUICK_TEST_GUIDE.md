# Quick Testing Guide - Milestone 1 & 2

## Start the Application

```bash
cd d:\xampp\htdocs\resturantbackend
php artisan serve
```

Access: http://localhost:8000

---

## Test Milestone 1: Chart of Accounts

### 1. Access the Interface
- Login as admin
- Click **Transactions** → **Chart of Accounts**
- URL: http://localhost:8000/chart-of-accounts

### 2. Quick Tests

**A. View List**
- ✅ Should see 19 accounts displayed
- ✅ Verify system account badges shown
- ✅ Check types: Revenue, COGS, Expense, Other Income

**B. Filters**
1. Select "Revenue" from Account Type → Click Filter
   - Should show 3 accounts only
2. Type "Food" in search → Filter
   - Should show Food-related accounts
3. Clear filters → Should show all accounts

**C. Create New Account**
1. Click "Add Account" button
2. Fill form:
   - Code: `7100`
   - Name: `Advertising`
   - Type: `Expense`
3. Click "Save Account"
4. ✅ Verify success toast
5. ✅ Verify account appears in list

**D. Edit Account**
1. Click edit icon on newly created account
2. Change name to "Marketing & Advertising"
3. Click Save
4. ✅ Verify updated in list

**E. Delete/Deactivate**
1. Click delete icon
2. Confirm deactivation
3. ✅ Verify status changed to Inactive
4. Apply "Inactive" filter → should see the account

---

## Test Milestone 2: Vendors

### 1. Access the Interface
- Login as admin
- Click **Transactions** → **Vendors**
- URL: http://localhost:8000/vendors

### 2. Quick Tests

**A. View List**
- ✅ Should see 8 vendors displayed
- ✅ Verify types: Food, Beverage, Utilities, Services
- ✅ Check default COA assignments shown

**B. Filters**
1. Select "Food" from Vendor Type → Filter
   - Should show Sam's Club and Sysco Foods
2. Type "Grubhub" in search → Filter
   - Should show Grubhub vendor
3. Select "Active" from Status → Filter
   - Should show all active vendors

**C. Create New Vendor**
1. Click "Add Vendor" button
2. Fill form:
   - Name: `Costco Wholesale`
   - Identifier: `COSTCO`
   - Type: `Food`
   - Default COA: Should auto-select "5000 - COGS - Food Purchases"
   - Contact: Expand section, add details
   - Notes: `Test vendor`
3. Click "Save Vendor"
4. ✅ Verify success toast
5. ✅ Verify vendor appears in list with COA assigned

**D. Edit Vendor**
1. Click edit icon on vendor
2. Change contact email
3. Add address
4. Click Save
5. ✅ Verify updated

**E. COA Auto-Suggestion**
1. Create new vendor
2. Change type to "Beverage"
   - ✅ COA should auto-select "5100 - COGS - Beverage Purchases"
3. Change type to "Utilities"
   - ✅ COA should auto-select "6300 - Utilities - Electric"
4. Cancel form

**F. Store Assignment**
1. Edit any vendor
2. Uncheck "Available to All Stores"
3. Select 2 stores
4. Save
5. ✅ Verify "2 store(s)" badge shown in list

---

## Test Fuzzy Matching API

### Using Browser Console (F12)

**Open Console and test:**

```javascript
// Test 1: Exact Match
fetch('/api/vendors-match?description=SAMSCLUB', {
    headers: {'Accept': 'application/json'}
}).then(r => r.json()).then(console.log);
// Expected: Match with 100% confidence

// Test 2: Partial Match
fetch('/api/vendors-match?description=Sams', {
    headers: {'Accept': 'application/json'}
}).then(r => r.json()).then(console.log);
// Expected: Match with high confidence

// Test 3: Square with special chars
fetch('/api/vendors-match?description=SQ *SQUARE SOMEPAYMENT', {
    headers: {'Accept': 'application/json'}
}).then(r => r.json()).then(console.log);
// Expected: Match Square vendor

// Test 4: Coca-Cola variations
fetch('/api/vendors-match?description=COCA COLA', {
    headers: {'Accept': 'application/json'}
}).then(r => r.json()).then(console.log);
// Expected: Match despite space differences

// Test 5: No Match
fetch('/api/vendors-match?description=XYZUnknownVendor', {
    headers: {'Accept': 'application/json'}
}).then(r => r.json()).then(console.log);
// Expected: no match, confidence 0
```

**Expected Response Format:**
```json
{
  "match": true,
  "confidence": 85.5,
  "vendor": {...},
  "match_type": "name"
}
```

---

## Database Verification

### Check Tables
```sql
SELECT COUNT(*) FROM chart_of_accounts;
-- Expected: 19

SELECT COUNT(*) FROM vendors;
-- Expected: 8

SELECT COUNT(*) FROM vendor_aliases;
-- Expected: 13

SELECT v.vendor_name, v.vendor_type, coa.account_code, coa.account_name
FROM vendors v
LEFT JOIN chart_of_accounts coa ON v.default_coa_id = coa.id
ORDER BY v.vendor_type, v.vendor_name;
-- Expected: 8 rows with COA assignments
```

### Check Relationships
```sql
-- Vendor Aliases
SELECT v.vendor_name, va.alias, va.source
FROM vendors v
JOIN vendor_aliases va ON v.id = va.vendor_id
ORDER BY v.vendor_name, va.source;

-- Expected: Each vendor should have at least 1 alias
```

---

## API Testing with Postman/Thunder Client

### Setup
- Base URL: http://localhost:8000/api
- Headers:
  - Accept: application/json
  - X-CSRF-TOKEN: [get from page source]

### Test Endpoints

1. **List Vendors**
   ```
   GET /api/vendors
   ```

2. **Get Single Vendor**
   ```
   GET /api/vendors/1
   ```

3. **Filter Vendors**
   ```
   GET /api/vendors?vendor_type=Food&is_active=1
   ```

4. **Create Vendor**
   ```
   POST /api/vendors
   Content-Type: application/json
   
   {
     "vendor_name": "Test Supplier",
     "vendor_type": "Supplies",
     "default_coa_id": 3
   }
   ```

5. **Update Vendor**
   ```
   PUT /api/vendors/1
   Content-Type: application/json
   
   {
     "vendor_name": "Updated Name",
     "vendor_type": "Food"
   }
   ```

6. **Deactivate Vendor**
   ```
   DELETE /api/vendors/1
   ```

7. **Add Alias**
   ```
   POST /api/vendors/1/aliases
   Content-Type: application/json
   
   {
     "alias": "NEW ALIAS",
     "source": "bank"
   }
   ```

8. **Fuzzy Match**
   ```
   GET /api/vendors-match?description=SQ *SQUARE
   ```

---

## Common Issues & Solutions

### Issue 1: Routes Not Working
**Solution:** Clear route cache
```bash
php artisan route:clear
php artisan config:clear
```

### Issue 2: 419 CSRF Error
**Solution:** Make sure you're logged in and have valid CSRF token

### Issue 3: Database Errors
**Solution:** Re-run migrations
```bash
php artisan migrate:fresh --seed
```

### Issue 4: Modal Not Opening
**Solution:** Check browser console for JavaScript errors
```javascript
// Test in console
new bootstrap.Modal(document.getElementById('vendorModal')).show();
```

### Issue 5: API Returns 404
**Solution:** Verify routes registered
```bash
php artisan route:list | findstr vendors
```

---

## Expected Results Checklist

### Chart of Accounts
- [ ] 19 system accounts visible
- [ ] Filters work correctly
- [ ] Create/Edit/Delete functional
- [ ] Store assignment works
- [ ] Parent account selection works
- [ ] System accounts cannot be edited
- [ ] Toast notifications appear
- [ ] Pagination works (if more than 25)

### Vendors
- [ ] 8 vendors visible
- [ ] Filters work correctly
- [ ] Create/Edit/Delete functional
- [ ] COA auto-suggestion works by type
- [ ] Store assignment works
- [ ] Contact section collapsible
- [ ] Toast notifications appear
- [ ] Fuzzy matching API returns results

### General
- [ ] No console errors
- [ ] No linter errors
- [ ] All migrations ran successfully
- [ ] Database relationships work
- [ ] Authorization enforced
- [ ] Responsive on mobile
- [ ] Loading states appear
- [ ] Form validation works

---

## Performance Check

### API Response Times
- List endpoints should respond in <500ms
- Single item endpoints should respond in <200ms
- Fuzzy matching should respond in <1000ms

### Page Load Times
- Initial page load should be <2s
- Table data load should be <1s
- Modal open should be instant

---

## Browser Compatibility

Test in:
- [x] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Edge (latest)
- [ ] Safari (if on Mac)
- [ ] Mobile browser (Chrome on Android)

---

## Next Steps After Testing

1. Document any defects found
2. Update test plan with results
3. Create improvement recommendations
4. Plan for Milestone 3: Expense Transactions

---

**Ready for Production?** ✅
Once all tests pass, the system is ready for Milestone 3 development.

