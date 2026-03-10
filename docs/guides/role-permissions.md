# Role-Based Access Control (RBAC) System

## Role Hierarchy and Permissions

### ADMIN
**Full System Access**
- User/Store/Transaction/Revenue management, Daily reports (all), Audit logs (Admin Reports), system config

### OWNER
**Store and Manager Management + Daily Reports**
- Stores (they own), Managers (assign to stores), Daily reports (create/view/edit/approve/export)
- Cannot: manage other owners, transaction/revenue types, audit logs

### MANAGER
**Daily Reports Only**
- Assigned stores only; daily reports create/view/edit for those stores
- Cannot: manage stores/users, config, audit logs, approve reports

---

## Many-to-Many: Manager ↔ Store

- `manager_store` pivot; one manager → multiple stores, one store → multiple managers
- Owner assigns manager to stores; manager sees only those stores’ reports

---

## Route Protection Summary

- **Admin only:** `/owners/*`, `/transaction-types/*`, `/revenue-income-types/*`, `/audit-logs/*` (Admin Reports)
- **Admin + Owner:** `/stores/*`, `/managers/*`, Merchant Fees (analytics, third-party, exceptions, import log)
- **Admin + Owner + Manager:** `/daily-reports/*`, `/profile/*`, P&L view

---

## Implementation Status

- User role enum, permission system, manager-store pivot, role middleware, audit logging, soft deletes.
- Commands: `php artisan migrate`, `php artisan db:seed --class=PermissionSeeder`, `php artisan security:audit-routes`

**Last Updated:** September 2025 | **Status:** Ready for deployment
