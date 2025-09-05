# Role-Based Access Control (RBAC) System

## Role Hierarchy and Permissions

### 🔴 ADMIN (admin@admin.com)
**Full System Access - Can do everything**

#### User Management
- ✅ Add/Edit/Delete Owners
- ✅ Add/Edit/Delete Managers  
- ✅ View all users
- ✅ Change user roles

#### Store Management
- ✅ Add/Edit/Delete Stores
- ✅ View all stores
- ✅ Assign managers to stores

#### System Configuration
- ✅ Add/Edit/Delete Transaction Types
- ✅ Add/Edit/Delete Revenue Income Types
- ✅ Manage system permissions

#### Daily Reports
- ✅ Create daily reports
- ✅ View all daily reports
- ✅ Edit/Delete reports
- ✅ Approve/Reject reports
- ✅ Export reports (PDF/CSV)

#### Audit & Security
- ✅ View audit logs
- ✅ Manage system security

---

### 🟠 OWNER
**Store and Manager Management + Daily Reports**

#### Store Management
- ✅ Add/Edit/Delete Stores (they own)
- ✅ View stores they created

#### Manager Management
- ✅ Add/Edit/Delete Managers
- ✅ Assign managers to stores (many-to-many)
- ✅ View manager details
- ✅ One manager can be assigned to multiple stores

#### Daily Reports
- ✅ Create daily reports
- ✅ View reports for their stores
- ✅ Edit their reports
- ✅ Approve reports
- ✅ Export reports

#### Restrictions
- ❌ Cannot manage other owners
- ❌ Cannot add/edit Transaction Types
- ❌ Cannot add/edit Revenue Income Types
- ❌ Cannot view audit logs
- ❌ Cannot access admin-only features

---

### 🟢 MANAGER
**Daily Reports Only**

#### Store Access
- ✅ View stores they are assigned to
- ✅ Can be assigned to multiple stores by owners

#### Daily Reports
- ✅ Create daily reports for assigned stores
- ✅ View reports for assigned stores
- ✅ Edit their own reports

#### Restrictions
- ❌ Cannot manage stores
- ❌ Cannot manage users
- ❌ Cannot access configuration settings
- ❌ Cannot view audit logs
- ❌ Cannot approve reports
- ❌ Limited to assigned stores only

---

## Many-to-Many Relationships

### Manager ↔ Store Assignment
```
One Manager → Multiple Stores ✅
One Store → Multiple Managers ✅
```

**Database Structure:**
- `manager_store` pivot table
- Fields: `manager_id`, `store_id`
- Managed through Owner interface

**Assignment Process:**
1. Owner creates manager account
2. Owner assigns manager to one or more stores
3. Manager gains access to daily reports for assigned stores only

---

## Route Protection Summary

### Admin-Only Routes
```
/owners/*                    - Owner management
/transaction-types/*         - Transaction type management  
/revenue-income-types/*      - Revenue income type management
/audit-logs/*               - System audit logs
```

### Admin + Owner Routes
```
/stores/*                   - Store management
/managers/*                 - Manager management
/managers/{id}/assign-stores - Manager-store assignment
```

### Admin + Owner + Manager Routes  
```
/daily-reports/*            - Daily report management
/profile/*                  - User profile management
```

---

## Implementation Status

### ✅ Completed Features
- User role enum with type safety
- Comprehensive permission system
- Many-to-many manager-store relationships
- Role-based route protection
- Secure role assignment with audit logging
- Soft delete support for users and stores

### 🔄 Database Commands
```bash
# Run migrations (when DB is available)
php artisan migrate

# Seed permissions
php artisan db:seed --class=PermissionSeeder

# Audit route security
php artisan security:audit-routes
```

### 🛡️ Security Features
- Comprehensive audit logging
- Automatic logout of deleted accounts
- Role change authorization and logging
- Store access validation with soft-delete checks
- IP tracking for security events

---

## Usage Examples

### Admin Creating an Owner
```php
// Only admin can create owners
$owner = User::create($validatedData);
$owner->changeRole(UserRole::OWNER, auth()->user());
```

### Owner Creating and Assigning Manager
```php
// Owner creates manager
$manager = User::create($validatedData);
$manager->changeRole(UserRole::MANAGER, auth()->user());

// Owner assigns manager to multiple stores
$manager->stores()->sync([1, 3, 5]); // Store IDs
```

### Manager Accessing Reports
```php
// Manager can only see reports for assigned stores
$reports = DailyReport::whereIn('store_id', 
    auth()->user()->stores()->pluck('store_id')
)->get();
```

---

**Last Updated:** September 5, 2025  
**Status:** ✅ READY FOR DEPLOYMENT