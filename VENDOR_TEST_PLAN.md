# Vendor Management System - Test Plan

## Overview
This document outlines comprehensive testing procedures for the Vendor Management System, Milestone 2 of Phase 2 (Expense Management and P&L Module).

## Test Environment Setup

### Prerequisites
1. Database migrations run successfully
2. Sample data seeded (8 vendors with COA assignments)
3. Admin user logged in
4. Modern web browser (Chrome, Firefox, Edge)

### Test Credentials
- Admin User: Has full access to create, edit, delete vendors
- Regular User: Can view vendors but cannot modify

---

## Test Section 1: Database & Backend API

### 1.1 Database Schema Testing

#### Test 1.1.1: Vendors Table Structure
**Objective:** Verify vendors table has correct columns and constraints

**Steps:**
1. Open database client (phpMyAdmin or MySQL Workbench)
2. Describe `vendors` table
3. Verify columns: id, vendor_name, vendor_identifier, default_coa_id, vendor_type, contact_name, contact_email, contact_phone, address, notes, is_active, created_by, timestamps
4. Check indexes on vendor_name, vendor_identifier, vendor_type, is_active
5. Verify foreign key to chart_of_accounts
6. Verify unique constraint on vendor_identifier

**Expected:** All columns, indexes, and constraints exist correctly

---

#### Test 1.1.2: Vendor Store Assignments Table
**Objective:** Verify vendor-store relationship table

**Steps:**
1. Describe `vendor_store_assignments` table
2. Check columns: id, vendor_id, store_id, is_global, timestamps
3. Verify foreign keys to vendors and stores
4. Verify unique constraint on (vendor_id, store_id)

**Expected:** Table structure correct with proper relationships

---

#### Test 1.1.3: Vendor Aliases Table
**Objective:** Verify aliases table for CSV matching

**Steps:**
1. Describe `vendor_aliases` table
2. Check columns: id, vendor_id, alias, source, timestamps
3. Verify indexes on alias and source
4. Verify unique constraint on (alias, source)
5. Verify foreign key to vendors

**Expected:** Table supports multiple aliases per vendor

---

### 1.2 Seeder Testing

#### Test 1.2.1: Sample Data Population
**Objective:** Verify vendors and aliases are created

**Steps:**
1. Run `php artisan tinker`
2. Execute: `App\Models\Vendor::count()` - should return 8
3. Execute: `App\Models\VendorAlias::count()` - should return 16 (8 names + 8 identifiers)
4. Check specific vendor: `App\Models\Vendor::where('vendor_name', "Sam's Club")->first()`
5. Verify COA assignments for Food, Beverage, Utilities, Services

**Expected:** All 8 vendors created with proper COA assignments and aliases

---

#### Test 1.2.2: COA Assignment Verification
**Objective:** Verify vendors have correct default COA

**Steps:**
1. Query vendor: `$vendor = App\Models\Vendor::where('vendor_name', 'Sysco Foods')->first()`
2. Access COA: `$vendor->defaultCoa`
3. Verify account_code is '5000' for Food vendors
4. Test Grubhub has COA '6100' (Marketing Fees)

**Expected:** COA assignments match vendor types correctly

---

### 1.3 API Endpoint Testing

#### Test 1.3.1: GET /api/vendors - List All Vendors
**Objective:** Retrieve paginated list of vendors

**URL:** `GET /api/vendors`

**Headers:**
- Accept: application/json
- X-CSRF-TOKEN: [token from page]

**Steps:**
1. Open browser developer tools (F12)
2. Navigate to vendor page
3. Check Network tab
4. Verify request returns 200 status
5. Verify JSON response contains:
   - data array with vendor objects
   - default_coa relationship loaded
   - stores relationship loaded
   - pagination meta (current_page, total, per_page)

**Expected Response:**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "vendor_name": "Sam's Club",
      "vendor_identifier": "SAMSCLUB",
      "vendor_type": "Food",
      "default_coa": {...},
      "stores": [...]
    }
  ],
  "total": 8
}
```

---

#### Test 1.3.2: GET /api/vendors - Filtering Tests
**Objective:** Test various filters work correctly

**Test Cases:**

**A. Filter by Store ID**
```
GET /api/vendors?store_id=1
```
- Expected: Return only vendors assigned to store 1

**B. Filter by Vendor Type**
```
GET /api/vendors?vendor_type=Food
```
- Expected: Return only Food vendors (Sam's Club, Sysco Foods)

**C. Filter by Status**
```
GET /api/vendors?is_active=1
```
- Expected: Return only active vendors

**D. Filter by Has COA**
```
GET /api/vendors?has_coa=1
```
- Expected: Return vendors with default_coa_id set
```
GET /api/vendors?has_coa=0
```
- Expected: Return vendors without COA

**E. Search by Name**
```
GET /api/vendors?search=Sams
```
- Expected: Return Sam's Club vendor

**F. Combine Filters**
```
GET /api/vendors?vendor_type=Services&is_active=1
```
- Expected: Return active Services vendors only

**Steps for Each Test:**
1. Execute filter in browser Network tab or Postman
2. Verify correct vendors returned
3. Verify count matches expectations

---

#### Test 1.3.3: GET /api/vendors/{id} - Show Single Vendor
**Objective:** Retrieve detailed vendor information

**URL:** `GET /api/vendors/1`

**Steps:**
1. Make request with valid vendor ID
2. Verify 200 response
3. Verify all relationships loaded: default_coa, stores, aliases, creator

**Expected Response:**
```json
{
  "id": 1,
  "vendor_name": "Sam's Club",
  "vendor_identifier": "SAMSCLUB",
  "vendor_type": "Food",
  "contact_name": null,
  "contact_email": null,
  "contact_phone": null,
  "address": null,
  "notes": null,
  "is_active": true,
  "default_coa": {
    "id": ...,
    "account_code": "5000",
    "account_name": "COGS - Food Purchases"
  },
  "stores": [...],
  "aliases": [
    {
      "id": ...,
      "alias": "Sam's Club",
      "source": "manual"
    },
    {
      "alias": "SAMSCLUB",
      "source": "manual"
    }
  ]
}
```

**Error Cases:**
- Test invalid ID: `GET /api/vendors/999`
- Expected: 404 Not Found

---

#### Test 1.3.4: POST /api/vendors - Create New Vendor
**Objective:** Create vendor with validation

**URL:** `POST /api/vendors`

**Valid Request Body:**
```json
{
  "vendor_name": "Test Vendor",
  "vendor_identifier": "TESTVENDOR",
  "vendor_type": "Supplies",
  "default_coa_id": 5,
  "store_ids": [1, 2],
  "contact_name": "John Doe",
  "contact_email": "john@testvendor.com",
  "contact_phone": "555-1234",
  "address": "123 Test St",
  "notes": "Test vendor notes"
}
```

**Steps:**
1. Open vendor create form
2. Fill all fields
3. Submit form
4. Verify 201 response
5. Verify vendor created in database
6. Verify aliases created automatically
7. Verify store assignments created

**Validation Test Cases:**

**A. Missing Required Field**
```json
{
  "vendor_type": "Food"
  // missing vendor_name
}
```
- Expected: 422 with error for vendor_name

**B. Invalid Vendor Type**
```json
{
  "vendor_name": "Test",
  "vendor_type": "InvalidType"
}
```
- Expected: 422 with validation error

**C. Invalid COA ID**
```json
{
  "vendor_name": "Test",
  "vendor_type": "Food",
  "default_coa_id": 99999
}
```
- Expected: 422 validation error

**D. Duplicate Vendor Identifier**
```json
{
  "vendor_name": "Test",
  "vendor_identifier": "SAMSCLUB",  // already exists
  "vendor_type": "Food"
}
```
- Expected: 422 validation error

**E. Unauthorized Access**
- Logout admin, try to create vendor
- Expected: 403 Forbidden

---

#### Test 1.3.5: PUT /api/vendors/{id} - Update Vendor
**Objective:** Update existing vendor

**URL:** `PUT /api/vendors/1`

**Request Body:**
```json
{
  "vendor_name": "Sam's Club Updated",
  "vendor_identifier": "SAMSCLUB",
  "vendor_type": "Food",
  "contact_name": "Jane Doe",
  "contact_email": "jane@samsclub.com"
}
```

**Steps:**
1. Edit vendor form
2. Update fields
3. Submit
4. Verify 200 response
5. Check database updated correctly

**Test Cases:**

**A. Update With Same Identifier**
- Should succeed (same vendor)

**B. Update With Other Vendor's Identifier**
- Expected: 422 validation error

**C. Update Store Assignments**
```json
{
  "store_ids": [3, 4]
}
```
- Expected: Old assignments removed, new ones created

---

#### Test 1.3.6: DELETE /api/vendors/{id} - Deactivate Vendor
**Objective:** Soft delete (deactivate) vendor

**URL:** `DELETE /api/vendors/1`

**Steps:**
1. Click deactivate button
2. Confirm action
3. Verify 200 response
4. Check vendor still exists but is_active = false
5. Verify vendor no longer appears in active list

---

#### Test 1.3.7: POST /api/vendors/{id}/aliases - Add Alias
**Objective:** Add alias for CSV matching

**URL:** `POST /api/vendors/1/aliases`

**Request Body:**
```json
{
  "alias": "SAMSCLUB.COM",
  "source": "bank"
}
```

**Steps:**
1. Call API endpoint
2. Verify 201 response
3. Check alias created in database
4. Verify alias appears in vendor details

**Test Cases:**

**A. Duplicate Alias Same Source**
```json
{
  "alias": "SAMSCLUB",
  "source": "manual"  // already exists
}
```
- Expected: 422 validation error

**B. Same Alias Different Source**
```json
{
  "alias": "SAMSCLUB",
  "source": "bank"  // OK
}
```
- Expected: 201 success

**C. Invalid Source**
```json
{
  "alias": "TEST",
  "source": "invalid"
}
```
- Expected: 422 validation error

---

#### Test 1.3.8: GET /api/vendors/match/match - Fuzzy Matching
**Objective:** Test intelligent vendor matching for CSV imports

**URL:** `GET /api/vendors/match/match?description={description}`

**Test Cases:**

**A. Exact Match**
```
GET /api/vendors/match/match?description=SAMSCLUB
```
- Expected: Match with 100% confidence

**B. Partial Match**
```
GET /api/vendors/match/match?description=Sams
```
- Expected: Match with 80%+ confidence

**C. Alias Match**
```
GET /api/vendors/match/match?description=SQ *SQUARE
```
- Expected: Match Square vendor with high confidence

**D. No Match**
```
GET /api/vendors/match/match?description=XYZRandomVendor
```
- Expected: no match (confidence < 60%)

**E. Close Match**
```
GET /api/vendors/match/match?description=COCA COLA
```
- Expected: Match despite space differences

**Expected Response:**
```json
{
  "match": true,
  "confidence": 85.5,
  "vendor": {
    "id": 3,
    "vendor_name": "Coca-Cola",
    "default_coa": {...}
  },
  "match_type": "name"
}
```

---

## Test Section 2: Frontend Interface

### 2.1 Vendor List Page

#### Test 2.1.1: Page Load and Display
**URL:** `/vendors`

**Steps:**
1. Navigate to vendors page
2. Verify page loads without errors
3. Check vendor table displays correctly
4. Verify columns: Name, Type, Default COA, Stores, Status, Actions
5. Check pagination controls appear
6. Verify "Add Vendor" button visible

**Expected:** Clean table with all 8 vendors displayed

---

#### Test 2.1.2: Table Data Display
**Steps:**
1. Verify each vendor row shows:
   - Vendor name
   - Badge for vendor type
   - Default COA account name
   - Store count or "All Stores"
   - Active/Inactive status badge
   - Edit and Delete buttons
2. Verify badges styled correctly (type, status)

---

#### Test 2.1.3: Filter Functionality

**A. Type Filter**
1. Select "Food" from type dropdown
2. Click Filter
3. Verify only 2 vendors shown (Sam's Club, Sysco Foods)
4. Clear filter, verify all vendors shown

**B. Status Filter**
1. Select "Active"
2. Verify all active vendors shown
3. Select "Inactive"
4. Verify empty result (no inactive vendors yet)

**C. Search Filter**
1. Type "Sam" in search box
2. Click Filter
3. Verify only Sam's Club shown
4. Try "COCA" - should show Coca-Cola

**D. Combined Filters**
1. Select type "Services"
2. Type "Square" in search
3. Verify filtered correctly

---

#### Test 2.1.4: Pagination
**Steps:**
1. If vendors exceed page size, verify pagination appears
2. Click "Next" - verify next page loads
3. Click "Previous" - verify previous page loads
4. Click page number - verify that page loads
5. Verify page highlights current page number

---

#### Test 2.1.5: Responsive Design
**Steps:**
1. Open browser DevTools
2. Toggle device toolbar
3. Test on iPhone SE (375px width)
4. Verify table responsive, filters stack vertically
5. Test on iPad (768px width)
6. Verify layout adjusts appropriately

---

### 2.2 Vendor Create/Edit Form

#### Test 2.2.1: Create Form Display
**Steps:**
1. Click "Add Vendor" button
2. Verify modal opens
3. Check all form fields present:
   - Vendor Name* (required)
   - Vendor Identifier
   - Vendor Type* (dropdown)
   - Default COA Category (searchable)
   - Store Assignment (multi-select)
   - Contact Information section (collapsible)
   - Notes (textarea)
   - Status (toggle)

---

#### Test 2.2.2: Form Validation - Client Side

**Test Cases:**

**A. Submit Empty Form**
1. Click Save without filling required fields
2. Expected: Required field indicators appear

**B. Invalid Email**
1. Fill contact_email with "invalid-email"
2. Expected: Email validation error

**C. Vendor Type Selection**
1. Leave vendor type empty
2. Expected: Required field error

**D. Character Limits**
1. Enter 101 characters in vendor name
2. Expected: Max length validation error

---

#### Test 2.2.3: COA Dropdown Functionality
**Steps:**
1. Open vendor form
2. Click COA dropdown
3. Verify COAs load (19 system accounts)
4. Type "Food" in search
5. Verify filtering works
6. Select COA
7. Verify selected value displays

---

#### Test 2.2.4: Store Assignment
**Steps:**
1. Expand store assignment section
2. Check "Available to All Stores"
3. Verify store list hides
4. Uncheck "Available to All Stores"
5. Verify store checkboxes appear
6. Select multiple stores
7. Submit form
8. Verify stores assigned correctly

---

#### Test 2.2.5: Successful Vendor Creation
**Test Data:**
```
Vendor Name: Test Vendor Inc
Vendor Identifier: TESTVENDOR
Type: Supplies
COA: COGS - Packaging Supplies
Stores: All Stores
Contact: John Doe, john@test.com, 555-1234
Notes: Testing vendor creation
Status: Active
```

**Steps:**
1. Fill all fields
2. Click Save
3. Verify loading spinner appears
4. Verify success toast notification
5. Verify modal closes
6. Verify vendor appears in list
7. Verify aliases created automatically

---

#### Test 2.2.6: Edit Vendor Form
**Steps:**
1. Click Edit button on Sam's Club
2. Verify form opens pre-filled
3. Modify vendor name
4. Change COA assignment
5. Update contact info
6. Save changes
7. Verify updates reflected in table

---

### 2.3 Vendor Details View

#### Test 2.3.1: Details Modal Display
**Steps:**
1. Click on a vendor row (if view link exists)
2. Or click "View Details" button
3. Verify modal opens
4. Check all information displays:
   - Vendor name and identifier
   - Type and COA
   - Store assignments
   - Contact information
   - Notes
   - Status
   - Created by user

---

#### Test 2.3.2: Aliases Section
**Steps:**
1. Open vendor details
2. Scroll to Aliases section
3. Verify existing aliases shown
4. Click "Add Alias" button
5. Enter new alias: "NEWALIAS"
6. Select source: "bank"
7. Save
8. Verify alias added to list

---

#### Test 2.3.3: Delete Alias
**Steps:**
1. Open vendor with multiple aliases
2. Click delete icon next to alias
3. Confirm deletion
4. Verify alias removed from list
5. Verify removed from database

---

### 2.4 Delete/Deactivate Flow

#### Test 2.4.1: Deactivation Confirmation
**Steps:**
1. Click Delete button on vendor
2. Verify confirmation modal appears
3. Read warning message
4. Click "Cancel"
5. Verify vendor still active
6. Click Delete again
7. Click "Deactivate"
8. Verify success message
9. Verify vendor status changed to Inactive

---

#### Test 2.4.2: Reactivation
**Steps:**
1. Find inactive vendor
2. Click Edit
3. Toggle Status to Active
4. Save
5. Verify vendor shows as Active
6. Verify vendor appears in active filter

---

### 2.5 Permission Testing

#### Test 2.5.1: Admin Access
**Steps:**
1. Login as admin
2. Verify all buttons visible: Add, Edit, Delete
3. Verify form submission works
4. Verify API calls succeed

---

#### Test 2.5.2: Non-Admin Access
**Steps:**
1. Create regular user account
2. Login as regular user
3. Navigate to vendors page (if accessible)
4. Verify Edit and Delete buttons hidden or disabled
5. Verify API returns 403 for unauthorized actions

---

## Test Section 3: Integration Testing

### 3.1 End-to-End Workflows

#### Test 3.1.1: Complete Vendor Lifecycle
**Objective:** Test complete vendor management workflow

**Steps:**
1. Create new vendor "Lobster Shack Supplier"
2. Assign to store 1 and 2
3. Set up contact info
4. Add alias "LOBSTER SHACK"
5. View vendor details
6. Update vendor info
7. Add another alias
8. Create transaction linked to vendor (when available)
9. Deactivate vendor
10. Reactivate vendor

**Expected:** All steps complete successfully

---

#### Test 3.1.2: CSV Import Matching Simulation
**Objective:** Test fuzzy matching with bank/credit card transactions

**Steps:**
1. Simulate bank transaction: "SQ *SQUARE SOMEPAYMENT"
2. Send to: `GET /api/vendors/match/match?description=SQ *SQUARE SOMEPAYMENT`
3. Verify Square vendor matched
4. Test: "SAMSCLUB.COM #123"
5. Verify Sam's Club matched
6. Test: "UNKNOWNXYZ VENDOR"
7. Verify no match returned
8. Test: "COCA COLA COMPANY"
9. Verify Coca-Cola matched

**Expected:** Fuzzy matching works for various transaction formats

---

#### Test 3.1.3: COA Assignment Workflow
**Steps:**
1. Create vendor without COA
2. Verify warning icon shows
3. Edit vendor
4. Assign appropriate COA
5. Verify COA displays in list
6. Change vendor type
7. Suggest new COA based on type
8. Apply suggestion
9. Verify COA updated

---

## Test Section 4: Performance & Edge Cases

### 4.1 Performance Testing

#### Test 4.1.1: Large Dataset
**Steps:**
1. Create 100 vendors using seeder or loop
2. Load vendor list page
3. Measure load time
4. Apply filters
5. Verify response time acceptable (<2 seconds)
6. Test search performance

---

#### Test 4.1.2: Pagination Performance
**Steps:**
1. Verify pagination handles 1000+ vendors
2. Test page navigation speed
3. Verify queries optimized (N+1 prevention)

---

### 4.2 Edge Cases

#### Test 4.2.1: Special Characters
**Test Cases:**
1. Create vendor with single quote: "O'Brien Foods"
2. Create vendor with symbols: "Vendor & Co."
3. Create vendor with emoji: "Vendor 😊"
4. Verify all save and display correctly

---

#### Test 4.2.2: Long Text
**Steps:**
1. Create vendor with 100 char name
2. Create vendor with 5000 char notes
3. Verify database handles correctly
4. Verify display truncated if needed

---

#### Test 4.2.3: Case Sensitivity
**Steps:**
1. Create vendor "Test Vendor"
2. Search "test vendor" (lowercase)
3. Verify found
4. Search "TEST VENDOR"
5. Verify found

---

#### Test 4.2.4: Concurrent Updates
**Steps:**
1. Open vendor edit form in two browser tabs
2. Edit in tab 1, save
3. Edit in tab 2, save
4. Verify last save wins or proper conflict handling

---

## Test Section 5: Browser Compatibility

### 5.1 Cross-Browser Testing

**Test in each browser:**
1. Chrome (latest)
2. Firefox (latest)
3. Edge (latest)
4. Safari (if on Mac)

**Verify:**
- Layout renders correctly
- JavaScript functions work
- Modals open/close
- Forms submit
- Filters work
- No console errors

---

## Test Section 6: Mobile Responsiveness

### 6.1 Mobile Testing

**Devices to test:**
1. iPhone (375px, 414px)
2. Android Phone (360px, 420px)
3. iPad (768px, 1024px)

**Verify:**
1. Table scrolls horizontally if needed
2. Forms usable on mobile
3. Touch targets adequate size
4. Filters accessible
5. Modals display correctly
6. No horizontal scroll on body

---

## Defect Reporting Template

When defects are found, report using this format:

**Severity:** Critical / High / Medium / Low

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**

**Actual Result:**

**Screenshots/Video:** [attach if available]

**Environment:**
- Browser:
- Screen Size:
- User Role:

---

## Test Completion Checklist

- [ ] All database schema tests passed
- [ ] All seeder tests passed
- [ ] All API endpoint tests passed (8 endpoints)
- [ ] All frontend page tests passed
- [ ] All form validation tests passed
- [ ] Fuzzy matching algorithm tested
- [ ] Permission tests passed
- [ ] Integration tests passed
- [ ] Performance acceptable
- [ ] Edge cases handled
- [ ] Cross-browser tested
- [ ] Mobile responsive tested
- [ ] No critical defects found
- [ ] Code reviewed
- [ ] Documentation updated

---

## Acceptance Criteria Validation

**✅ All tables created with proper relationships**
- Test 1.1.1, 1.1.2, 1.1.3

**✅ Indexes created**
- Test 1.1.1, 1.1.2, 1.1.3

**✅ Migration and rollback scripts**
- Verified in Test 1.1.1

**✅ Sample data inserted (8 vendors)**
- Test 1.2.1

**✅ All endpoints implemented**
- Test 1.3.1 through 1.3.7

**✅ Fuzzy matching algorithm**
- Test 1.3.8

**✅ COA dropdown with search**
- Test 2.2.3

**✅ Store assignment working**
- Test 2.2.4

**✅ Responsive design**
- Test 2.1.5, Test 6.1

**✅ Permission-based UI**
- Test 2.5.1, 2.5.2

**✅ Integration complete**
- Test 3.1.1, 3.1.2, 3.1.3

---

## Notes for Testers

1. Always test with sample data first
2. Keep browser console open to catch JavaScript errors
3. Check network tab for failed API calls
4. Test edge cases before marking complete
5. Document any unexpected behavior
6. Test all user roles if applicable

---

## Post-Testing Review

After testing, provide:
1. Summary of defects found
2. Performance metrics
3. Areas of concern
4. Recommendations for improvements
5. Sign-off if acceptable for production

