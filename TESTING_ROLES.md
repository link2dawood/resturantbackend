## Fann’s Philly Grill Role & Permissions Test Plan

This document describes how to manually test the full roles and permissions model for the Fann’s Philly Grill system.

Roles:
- **Admin** – controls the entire dashboard.
- **Franchisor** – controls the entire Fann’s Philly Grill brand.
- **Franchisee Owner** – controls one or more locations, reports to the Franchisor.
- **Manager** – runs a specific location.

---

## 1. Scope & Objectives

- **Verify** that each role sees the correct navigation and pages.
- **Verify** that each role can only access data it is allowed to see:
  - Stores, Owners, Managers.
  - Daily Reports and Profit & Loss (P&L).
  - Chart of Accounts, Transaction Types, Revenue / Income Types.
- **Verify** that brand hierarchy rules are respected:
  - Franchisor can see and control the whole brand.
  - Franchisee Owners only see their locations and managers.
  - Managers only see / operate their assigned store(s).
- **Verify** middleware and permission responses:
  - 401 for unauthenticated.
  - 403 for insufficient permissions.

---

## 2. Environment & Seed Data

### 2.1 Environment

- Database migrated:
  - `php artisan migrate`
- Application keys, env, etc. configured.
- Web app reachable at something like:
  - `https://stores.fannsphilly.com`

### 2.2 Test Users

You should have at least these users:

- **Admin**
  - Role: `admin`
  - Controls entire dashboard.
- **Franchisor**
  - Role: `owner`
  - Name: `Franchisor`
  - Email: `franchisor@system.local`
  - Represents the brand owner. Controls all Corporate Stores and has business visibility across the brand.
- **Franchisee Owner 1**
  - Role: `owner`
  - Controls one or more franchisee locations.
- **Franchisee Owner 2**
  - Role: `owner`
  - Another franchisee owner for cross‑store tests.
- **Manager 1**, **Manager 2**, **Manager 3**
  - Role: `manager`
  - Each assigned to a specific store.

### 2.3 Test Stores & Assignments

Create at least these stores:

- **Store A** – Corporate Store  
  - `store_type = corporate`  
  - Controlled by Franchisor, reports to Franchisor, run by Managers.
- **Store B** – Franchisee Location for Owner1  
  - `store_type = franchisee`  
  - Controlled by Owner1 (Franchisee), reports to Franchisor, can have Managers.
- **Store C** – Franchisee Location for Owner2  
  - `store_type = franchisee`  
  - Controlled by Owner2 (Franchisee), reports to Franchisor, can have Managers.

Assign managers:

- Manager A → Store A
- Manager B → Store B
- Manager C → Store C

---

## 3. Authentication & Account State

### 3.1 Basic Login (All Roles)

For each user (Admin, Franchisor, Owner1, Owner2, each Manager):

1. Go to the login page.  
2. Log in with valid credentials.

**Expected:**
- Redirect to dashboard (no 403/500).
- User is shown navigation appropriate for their role (see section 4).

### 3.2 Soft‑Deleted / Inactive Accounts

1. In the database, set `deleted_at` for a test user (e.g., an Owner).
2. While logged in as that user, try to navigate to any authenticated route.

**Expected:**
- User is logged out.
- Browser requests:
  - Redirect to login with message about account being inactive.
- JSON/API requests:
  - HTTP 401, response `{"error":"Account no longer active"}` (from middleware).

---

## 4. Navigation Visibility Matrix

Check the left navigation for each role. This is rendered mainly from `resources/views/layouts/tabler.blade.php` (and `google-dashboard` layout if used).

### 4.1 Admin

**Steps:**
- Log in as Admin.
- Look at the sidebar / main navigation.

**Expected visible items (examples, adjust to your menu):**
- **Stores**
- **Owners**
- **Managers**
- **Daily Reports**
- **Reports / P&L**
- **Transaction Types**
- **Revenue / Income Types**
- **Chart of Accounts**
- Any audit/system menus (if present).

Check link targets:
- `/stores`
- `/owners`
- `/managers`
- `/daily-reports`
- `/reports/profit-loss`
- `/transaction-types`
- `/revenue-income-types`
- `/chart-of-accounts`

### 4.2 Franchisor

**Steps:**
- Log in as Franchisor (Owner named “Franchisor”).

**Expected:**
- Sees:
  - Stores
  - Owners
  - Managers
  - Daily Reports
  - Reports / P&L
  - Transaction Types
  - Revenue / Income Types
- Does **not** see any pure “system admin only” items if you separated them.

### 4.3 Franchisee Owner

**Steps:**
- Log in as Owner1 (Franchisee).

**Expected:**
- Sees:
  - Stores (only their locations).
  - Managers.
  - Daily Reports.
  - Reports / P&L (limited to their stores).
- Does **not** see:
  - Global system configuration.
  - Other owners’ stores or managers.

### 4.4 Manager

**Steps:**
- Log in as a Manager.

**Expected:**
- Sees:
  - Daily Reports.
  - Possibly a P&L / Reports menu (view only, for their store).
- Does **not** see:
  - Stores.
  - Owners.
  - Managers management.
  - Chart of Accounts, Transaction Types, Revenue / Income Types.

If any of these are wrong, inspect the Blade `@if(Auth::user()->...)` conditions in the layouts.

---

## 5. Route Access Matrix – Core Business

This section validates route‑level access and controller logic.

### 5.1 Stores (`/stores` and children)

#### Admin

- `GET /stores`  
  - **Expected:** 200, list of **all** stores (A, B, C).
- `GET /stores/create`  
  - **Expected:** 200, create form.
- Create **Corporate Store**:
  - Select `store_type = corporate`.
  - **Expected:** Store created successfully, Franchisor is controlling owner.
- Create **Franchisee Location**:
  - Select `store_type = franchisee`.
  - Choose an Owner as controlling owner.
  - **Expected:** Store created successfully, Owner controls, Franchisor also attached for reporting.

#### Franchisor

- `GET /stores`  
  - **Expected:** 200, sees all corporate + franchisee stores (A, B, C).
- `GET /stores/create`  
  - **Expected:** 200.
- Create Corporate Store:
  - `store_type = corporate`.
  - **Expected:** Validation passes, store assigned to Franchisor as controlling owner.

#### Franchisee Owner

- `GET /stores`  
  - **Expected:** 200, sees only:
    - Stores they created.
    - Stores assigned to them via owner_store pivot (e.g., Store B).
- `GET /stores/create`  
  - **Expected:** 200, but only able to create **Franchisee Location**.
- **Negative:** Try creating a **Corporate Store** (`store_type = corporate`).
  - **Expected:** Validation fails with message like “Corporate Stores must be created by the Franchisor.”

#### Manager

- `GET /stores`  
  - Depending on design:
    - Option A: 200 with only their assigned store(s).
    - Option B: 403 if managers are not allowed to access store index.

#### Cross‑Store Access Check

1. Log in as Owner1.
2. Access `GET /stores/{id-of-Owner2-store}` (Store C).

**Expected:** 403 (or a “no permission” page).  
This ensures `hasStoreAccess()` and `accessibleStores()` are correctly restricted.

---

### 5.2 Owners (`/owners`)

#### Admin

- `GET /owners` – 200, full list of owners.
- `GET /owners/create` – 200, can create new owners.
- `GET /owners/{id}/edit` – 200, can edit any owner.

#### Franchisor

- `GET /owners` – 200, sees all Franchisee Owners.
- Can assign stores to owners (if supported by UI).

#### Franchisee Owner

- `GET /owners` – 403 (or link not visible).
- Cannot create or edit other owners.

#### Manager

- Any `/owners*` route – 403.

---

### 5.3 Managers (`/managers`)

#### Admin

- Full CRUD:
  - `GET /managers`
  - `GET /managers/create`
  - `POST /managers`
  - `GET /managers/{id}/edit`
  - `PUT /managers/{id}`
  - `DELETE /managers/{id}`
- Can assign any store to a manager.

#### Franchisor

- Can assign managers to any store (Corporate or Franchisee) via the assign‑stores form:
  - `GET /managers/{manager}/assign-stores`
  - `POST /managers/{manager}/assign-stores`

#### Franchisee Owner

- `GET /managers` – 200, sees managers for their stores.
- `GET /managers/create` / `POST /managers` – can create managers and assign to **their stores only**.
- **Negative:** Try to assign a manager to a store owned by a different owner:
  - **Expected:** 403 or validation error.

#### Manager

- Any `/managers*` route (except maybe their own profile) – 403.

---

## 6. Business Configuration – COA / Transaction / Revenue Types

### 6.1 Chart of Accounts (`/chart-of-accounts`)

#### Admin

- `GET /chart-of-accounts`
  - **Expected:** 200, COA index page.
- Create / Update / Delete:
  - **Expected:** Authorized by `StoreChartOfAccountRequest` and `UpdateChartOfAccountRequest`.

#### Owner / Franchisor

- `GET /chart-of-accounts` – 200.
- Create/Update COA:
  - **Expected:** Allowed (request `authorize()` allows admin **or** owner).

#### Manager

- Any `/chart-of-accounts*` – 403.

### 6.2 Transaction Types (`/transaction-types`)

#### Admin

- `GET /transaction-types` – 200 (route uses `role:admin,owner`, plus admin bypass in RoleMiddleware).
- Full CRUD.

#### Owner / Franchisor

- `GET /transaction-types` – 200.
- CRUD operations allowed per business rules.

#### Manager

- `/transaction-types` – 403.

### 6.3 Revenue / Income Types (`/revenue-income-types`)

Same logic as Transaction Types.

#### Admin / Owner / Franchisor

- Access and manage revenue / income types screens.

#### Manager

- All `/revenue-income-types*` – 403.

---

## 7. Brand Hierarchy Tests

Use Store A (corporate), Store B (Owner1), Store C (Owner2), with assigned managers.

### 7.1 Franchisor

**As Franchisor:**
- `/stores` – sees A, B, C.
- `/owners` – sees all owners.
- `/managers` – sees all managers.

Check:
- Can assign any store to any owner via owner assign‑stores.
- Corporate Stores are controlled by Franchisor (controlling owner is Franchisor).

### 7.2 Franchisee Owners

**As Owner1:**
- `/stores` – sees only their stores (e.g., Store B).
- `/managers` – sees only managers attached to their stores (e.g., Manager B).
- Cannot see Store C or Manager C.

Repeat similarly for Owner2.

### 7.3 Managers

**As Manager B (assigned to Store B):**
- Sees only Store B in any store lists (if visible).
- Daily Reports:
  - Can create and view only for Store B.
- P&L:
  - Can view P&L only for Store B (no export).
- Cannot:
  - Access any other store details.
  - Access Owners or configuration sections.

---

## 8. Daily Reports & P&L

### 8.1 Admin

- `/daily-reports` – can see all reports for all stores.
- `/reports/profit-loss` – can select any store, run reports.
- Export CSV/PDF routes:
  - `/reports/profit-loss/export/csv`
  - `/reports/profit-loss/export/pdf`
  - **Expected:** 200, export works.

### 8.2 Franchisor

- Same business visibility as Admin across the brand:
  - All stores’ daily reports and P&L.

### 8.3 Franchisee Owner

- `/daily-reports` – only reports for their stores.
- `/reports/profit-loss` – only allowed for their stores.
- **Negative:** Request P&L for another owner’s store:
  - **Expected:** 403 (checked via `hasStoreAccess()`).

### 8.4 Manager

- `/daily-reports` – can create/view for their assigned store only.
- `/reports/profit-loss` – can view (if enabled) only for their store; no export.

---

## 9. Error & Middleware Behaviour (HTML vs JSON)

### 9.1 API / JSON Responses

Use Postman or curl with `Accept: application/json`.

**Not logged in:**
- Hit an authenticated route (e.g., `/stores`, `/chart-of-accounts`).
- **Expected:**  
  - HTTP 401  
  - `{"error":"Unauthorized"}`

**Logged in but wrong role or permission:**
- Log in as Manager.
- Hit `/chart-of-accounts`, `/transaction-types`, `/revenue-income-types`.
- **Expected:**  
  - HTTP 403  
  - `{"error":"Forbidden - Insufficient permissions"}`

These come from `RoleMiddleware` / `PermissionMiddleware`.

### 9.2 Browser / HTML Responses

With a browser:

- Not logged in:
  - Visiting protected routes redirects to login.
- Logged in but not allowed:
  - Shows a 403 page or “You do not have permission” message (HTML).

---

## 10. Quick Regression Checklist

Use this short list after any roles/permissions change:

1. **Admin**
   - Log in.
   - Click through:
     - Stores, Owners, Managers, Daily Reports, Reports, Transaction Types, Revenue Types, Chart of Accounts.
   - **Expected:** never see 403.

2. **Franchisor**
   - Confirms:
     - Can see all stores, owners, managers.
     - Can configure brand‑level items (COA, transaction types, revenue types) if required.

3. **Franchisee Owner**
   - Sees only:
     - Their stores and managers.
   - Cannot access:
     - Other owners’ stores.
     - Global configuration routes.

4. **Manager**
   - Can:
     - Do daily reports and limited P&L for their store.
   - Cannot:
     - Access Stores, Owners, configuration screens, or other managers.

If all items in this checklist pass, the role and permission system is behaving as designed.




