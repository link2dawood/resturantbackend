# Architecture Documentation

## System Overview

The Restaurant Backend Phase 2: Expense Management & P&L Module is built on Laravel 12 with a MySQL database, providing a comprehensive financial management system for restaurant operations.

## Technology Stack

### Backend
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: MySQL 8.0+
- **ORM**: Eloquent
- **Authentication**: Session-based
- **PDF Parsing**: smalot/pdfparser

### Frontend
- **CSS Framework**: Bootstrap 5.3+
- **JavaScript**: Vanilla JS with Alpine.js
- **Templating**: Blade
- **Charts**: Chart.js

### Testing
- **Framework**: PHPUnit
- **Factories**: Laravel Factories
- **Coverage**: Aim for 85%+

---

## Architecture Patterns

### MVC (Model-View-Controller)

```
┌─────────────┐
│   Routes    │
└──────┬──────┘
       │
       ├──────────────┐
       │              │
┌──────▼──────┐  ┌────▼─────┐
│ Controllers │  │  Models  │
│  (Logic)    │  │  (Data)  │
└──────┬──────┘  └────┬─────┘
       │              │
       └──────┬───────┘
              │
       ┌──────▼──────┐
       │   Views     │
       │  (Blade)    │
       └─────────────┘
```

### Repository Pattern (Implicit)

Models act as repositories with query scopes:
- `ExpenseTransaction::needsReview()`
- `ExpenseTransaction::byDateRange()`
- `Vendor::search()`

### Observer Pattern

**DailyReportObserver**:
- Automatically processes credit card deposits
- Calculates merchant fees
- Creates expense transactions
- Creates expected bank transactions

### Service Layer

Business logic is encapsulated in:
- Controllers (API endpoints)
- Models (business rules)
- Observers (event handling)
- Middleware (permissions)

---

## Data Flow Diagrams

### Expense Import Flow

```
┌─────────────┐
│  CSV Upload │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│  Format Detect  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Parse & Validate│
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Duplicate Check │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Vendor Matching │
│  (Exact/Fuzzy)  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Auto-Categorize │
│  (COA Assign)   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Create Expenses │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Review Queue   │
│  (if needed)    │
└─────────────────┘
```

### Bank Reconciliation Flow

```
┌─────────────────┐
│ Bank Statement  │
│   CSV Upload    │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Parse & Import  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Auto-Match     │
│  Algorithm      │
└──────┬──────────┘
       │
       ├──────────────┐
       │              │
       ▼              ▼
┌─────────────┐  ┌─────────────┐
│   Deposits  │  │ Withdrawals │
│ Match to    │  │ Match to    │
│ Daily Reports│  │  Expenses   │
└──────┬──────┘  └──────┬──────┘
       │                │
       └────────┬───────┘
                │
                ▼
         ┌──────────────┐
         │  Unmatched   │
         │  Items       │
         └──────┬───────┘
                │
                ▼
         ┌──────────────┐
         │ Manual Review│
         └──────────────┘
```

### P&L Generation Flow

```
┌─────────────────┐
│  User Request   │
│  (Date Range)   │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Fetch Revenue   │
│ (Daily Reports) │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Fetch COGS      │
│ (Expenses)      │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│ Fetch Expenses  │
│ (Operating)     │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Calculate      │
│  Margins        │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Format &       │
│  Return Data    │
└─────────────────┘
```

---

## Security Architecture

### Authentication Flow

```
┌─────────────┐
│   Login     │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Session Create  │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Middleware     │
│  Check Auth     │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Permission     │
│  Middleware     │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Store Access   │
│  Check          │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Controller     │
└─────────────────┘
```

### Permission System

```
┌─────────────┐
│   Request   │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│ Check User Role │
└──────┬──────────┘
       │
       ├──────────────┐
       │              │
       ▼              ▼
┌─────────────┐  ┌─────────────┐
│   Admin?    │  │  Check      │
│  (Full)     │  │  Permission │
│   Access    │  │  Matrix     │
└──────┬──────┘  └──────┬──────┘
       │                │
       └────────┬───────┘
                │
                ▼
         ┌──────────────┐
         │ Store Access │
         │   Check      │
         └──────┬───────┘
                │
                ▼
         ┌──────────────┐
         │ Allow/Deny   │
         └──────────────┘
```

---

## Integration Points

### Internal Integrations

1. **Daily Reports → Expenses**
   - Observer automatically creates expense transactions
   - Links via `daily_report_id`

2. **Expenses → Bank Reconciliation**
   - Bank transactions match to expenses
   - Links via `matched_expense_id`

3. **Vendors → Expenses**
   - Default COA assignment
   - Fuzzy matching for auto-categorization

4. **COA → Expenses**
   - Categorization of all expenses
   - Used in P&L generation

### External Integrations

1. **CSV Import**
   - Bank statements
   - Credit card statements
   - Third-party platform statements

2. **PDF Import**
   - Grubhub statements
   - Other platform PDFs

3. **File Storage**
   - Receipt uploads
   - Statement storage
   - Export files

---

## Deployment Architecture

### Development Environment

```
┌─────────────────┐
│  Local Machine  │
│  (XAMPP/Laravel │
│   Valet)        │
└─────────────────┘
```

### Production Environment

```
                    ┌──────────────┐
                    │   Load       │
                    │   Balancer   │
                    └──────┬───────┘
                           │
            ┌──────────────┼──────────────┐
            │              │              │
     ┌──────▼──────┐ ┌─────▼─────┐ ┌─────▼─────┐
     │   Web       │ │   Web     │ │   Web     │
     │   Server 1  │ │  Server 2 │ │  Server 3 │
     └──────┬──────┘ └─────┬─────┘ └─────┬─────┘
            │              │              │
            └──────────────┼──────────────┘
                           │
                    ┌──────▼──────┐
                    │  Database   │
                    │  (MySQL)    │
                    └─────────────┘
```

### File Storage

```
┌─────────────────┐
│  Application    │
│  Server         │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  File Storage   │
│  (Local/S3)     │
└─────────────────┘
```

---

## Code Organization

### Directory Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ChartOfAccountController.php
│   │   │   ├── VendorController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── BankAccountController.php
│   │   │   ├── BankReconciliationController.php
│   │   │   ├── ProfitLossController.php
│   │   │   └── ...
│   │   └── Admin/
│   │       ├── ChartOfAccountViewController.php
│   │       ├── VendorViewController.php
│   │       ├── ExpenseViewController.php
│   │       └── ...
│   └── Middleware/
│       ├── CheckPermission.php
│       └── SecurityHeaders.php
├── Models/
│   ├── ChartOfAccount.php
│   ├── Vendor.php
│   ├── ExpenseTransaction.php
│   ├── BankAccount.php
│   └── ...
├── Observers/
│   └── DailyReportObserver.php
└── Providers/
    └── AppServiceProvider.php

database/
├── migrations/
├── seeders/
└── factories/

resources/
└── views/
    └── admin/
        ├── coa/
        ├── vendors/
        ├── expenses/
        └── ...

routes/
├── web.php
└── api.php (empty, moved to web.php)
```

---

## Performance Optimization

### Database Optimization

1. **Indexing**
   - Composite indexes on frequently queried columns
   - Single column indexes on filter columns
   - Foreign key indexes

2. **Query Optimization**
   - Eager loading to prevent N+1 queries
   - Query scopes for reusable filters
   - Pagination for large datasets

3. **Caching**
   - COA list caching
   - Vendor list caching
   - Store list caching

### Application Optimization

1. **Server-Side Rendering**
   - All data rendered server-side
   - Reduced AJAX calls
   - Faster initial page load

2. **Lazy Loading**
   - Images loaded on demand
   - Charts loaded when visible
   - Modals loaded on open

3. **Asset Optimization**
   - Minified CSS/JS
   - CDN for static assets
   - Image optimization

---

## Error Handling

### Error Flow

```
┌─────────────┐
│   Error     │
│   Occurs    │
└──────┬──────┘
       │
       ▼
┌─────────────────┐
│  Try-Catch      │
│  Block          │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Log Error      │
│  (Log File)     │
└──────┬──────────┘
       │
       ▼
┌─────────────────┐
│  Return Error   │
│  Response       │
└─────────────────┘
```

### Error Types

1. **Validation Errors** (422)
   - Missing required fields
   - Invalid data types
   - Business rule violations

2. **Authentication Errors** (401)
   - Unauthenticated requests
   - Session expired

3. **Authorization Errors** (403)
   - Insufficient permissions
   - Store access denied

4. **Not Found Errors** (404)
   - Resource doesn't exist
   - Invalid IDs

5. **Server Errors** (500)
   - Database errors
   - Application errors
   - Unexpected exceptions

---

## Monitoring & Logging

### Logging Strategy

1. **Application Logs**
   - Errors and exceptions
   - Important business events
   - Performance metrics

2. **Audit Logs**
   - User actions
   - Data changes
   - Permission denials

3. **Access Logs**
   - API requests
   - Page views
   - File downloads

### Monitoring Points

1. **Performance**
   - Response times
   - Database query times
   - Memory usage

2. **Errors**
   - Error rates
   - Exception types
   - Failed requests

3. **Business Metrics**
   - Import success rates
   - Matching accuracy
   - Review queue size

---

## Future Enhancements

### Planned Features

1. **Machine Learning**
   - Improved categorization
   - Predictive analytics
   - Anomaly detection

2. **API Enhancements**
   - GraphQL support
   - Webhook notifications
   - Real-time updates

3. **Integration**
   - Accounting software (QuickBooks, Xero)
   - Payment processors
   - Inventory systems

4. **Mobile App**
   - Receipt scanning
   - Mobile expense entry
   - Push notifications

---

**Last Updated**: November 2025  
**Version**: 1.0.0




