# API Documentation

## Base URL

```
http://your-domain.com/api
```

## Authentication

All API endpoints require authentication via session-based authentication. Include the session cookie in your requests.

### CSRF Protection

All POST, PUT, PATCH, and DELETE requests require a CSRF token. Include the token in your request headers:

```http
X-CSRF-TOKEN: {token}
```

Get the token from: `<meta name="csrf-token" content="{token}">`

---

## Chart of Accounts API

### List Chart of Accounts

```http
GET /api/coa
```

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 25)
- `account_type` (string): Filter by type (Revenue, COGS, Expense, Other Income)
- `is_active` (boolean): Filter by active status
- `search` (string): Search by code or name
- `store_id` (integer): Filter by store

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "account_code": "4000",
      "account_name": "Revenue - Food Sales",
      "account_type": "Revenue",
      "parent_account_id": null,
      "is_active": true,
      "is_system_account": true,
      "stores": []
    }
  ],
  "current_page": 1,
  "total": 50,
  "per_page": 25
}
```

### Get Single COA

```http
GET /api/coa/{id}
```

**Response:**
```json
{
  "id": 1,
  "account_code": "4000",
  "account_name": "Revenue - Food Sales",
  "account_type": "Revenue",
  "parent_account_id": null,
  "is_active": true,
  "is_system_account": true,
  "stores": [
    {
      "id": 1,
      "store_info": "Main Store"
    }
  ]
}
```

### Create COA

```http
POST /api/coa
```

**Request Body:**
```json
{
  "account_code": "6100",
  "account_name": "Payroll",
  "account_type": "Expense",
  "parent_account_id": null,
  "is_global": true,
  "is_active": true,
  "store_ids": [1, 2]
}
```

**Response:** `201 Created`
```json
{
  "message": "Chart of Account created successfully",
  "data": {
    "id": 25,
    "account_code": "6100",
    "account_name": "Payroll",
    ...
  }
}
```

### Update COA

```http
PUT /api/coa/{id}
```

**Request Body:** Same as Create

**Response:** `200 OK`

### Delete COA

```http
DELETE /api/coa/{id}
```

**Response:** `200 OK`
```json
{
  "message": "Chart of Account deactivated successfully"
}
```

---

## Vendors API

### List Vendors

```http
GET /api/vendors
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `store_id` (integer): Filter by store
- `vendor_type` (string): Filter by type
- `is_active` (boolean): Filter by active status
- `search` (string): Search by name, identifier, or contact
- `has_coa` (boolean): Filter by COA assignment

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "vendor_name": "SYSCO",
      "vendor_identifier": "TAX123456",
      "vendor_type": "Food",
      "default_coa_id": 5,
      "is_active": true,
      "default_coa": {
        "id": 5,
        "account_name": "Food Purchases"
      },
      "stores": []
    }
  ],
  "current_page": 1,
  "total": 20
}
```

### Get Single Vendor

```http
GET /api/vendors/{id}
```

### Create Vendor

```http
POST /api/vendors
```

**Request Body:**
```json
{
  "vendor_name": "SYSCO",
  "vendor_identifier": "TAX123456",
  "vendor_type": "Food",
  "contact_name": "John Doe",
  "email": "contact@sysco.com",
  "phone": "555-1234",
  "default_coa_id": 5,
  "is_active": true,
  "store_ids": [1, 2]
}
```

**Response:** `201 Created`

### Update Vendor

```http
PUT /api/vendors/{id}
```

### Delete Vendor

```http
DELETE /api/vendors/{id}
```

### Add Vendor Alias

```http
POST /api/vendors/{id}/aliases
```

**Request Body:**
```json
{
  "alias": "SYSCO FOOD SERVICE"
}
```

### Match Vendor (Fuzzy Matching)

```http
GET /api/vendors/match?description={description}
```

**Response:**
```json
{
  "match": true,
  "vendor": {
    "id": 1,
    "vendor_name": "SYSCO"
  },
  "confidence": 85,
  "match_type": "fuzzy"
}
```

---

## Expenses API

### List Expenses

```http
GET /api/expenses
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page
- `store_id` (integer): Filter by store
- `start_date` (date): Start date (YYYY-MM-DD)
- `end_date` (date): End date (YYYY-MM-DD)
- `transaction_type` (string): cash, credit_card, bank_transfer, check
- `needs_review` (boolean): Filter by review status
- `vendor_id` (integer): Filter by vendor
- `coa_id` (integer): Filter by COA

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "transaction_type": "credit_card",
      "transaction_date": "2025-11-01",
      "store_id": 1,
      "vendor_id": 1,
      "vendor_name_raw": "SYSCO",
      "coa_id": 5,
      "amount": "1000.00",
      "description": "Food purchase",
      "reference_number": "1234",
      "payment_method": "credit_card",
      "is_reconciled": false,
      "needs_review": false,
      "store": {
        "id": 1,
        "store_info": "Main Store"
      },
      "vendor": {
        "id": 1,
        "vendor_name": "SYSCO"
      },
      "coa": {
        "id": 5,
        "account_name": "Food Purchases"
      }
    }
  ],
  "current_page": 1,
  "total": 100
}
```

### Get Single Expense

```http
GET /api/expenses/{id}
```

### Create Manual Expense

```http
POST /api/expenses/manual
```

**Request Body:**
```json
{
  "transaction_date": "2025-11-01",
  "store_id": 1,
  "vendor_id": 1,
  "coa_id": 5,
  "amount": "1000.00",
  "payment_method": "credit_card",
  "description": "Food purchase",
  "reference_number": "1234",
  "notes": "Monthly order",
  "receipt_url": "https://example.com/receipt.pdf"
}
```

**Response:** `201 Created`

### Update Expense

```http
PUT /api/expenses/{id}
```

**Request Body:**
```json
{
  "vendor_id": 1,
  "coa_id": 5,
  "notes": "Updated notes"
}
```

### Sync Cash Expenses

```http
POST /api/expenses/sync-cash-expenses
```

**Query Parameters:**
- `start_date` (date): Start date
- `end_date` (date): End date
- `store_id` (integer): Store ID

**Response:**
```json
{
  "message": "Cash expenses synced successfully",
  "imported": 50,
  "skipped": 5,
  "needs_review": 3
}
```

### Review Queue

```http
GET /api/expenses/review-queue
```

**Query Parameters:**
- `store_id` (integer): Filter by store
- `review_reason` (string): Filter by reason

**Response:**
```json
{
  "total": 25,
  "by_reason": {
    "Vendor not found": 10,
    "COA not assigned": 12,
    "Possible duplicate": 3
  },
  "by_store": {
    "Main Store": 15,
    "Second Store": 10
  },
  "transactions": [
    {
      "id": 1,
      "transaction_date": "2025-11-01",
      "amount": "100.00",
      "vendor_name_raw": "Unknown Vendor",
      "review_reason": "Vendor not found",
      ...
    }
  ]
}
```

### Resolve Review Transaction

```http
POST /api/expenses/{id}/resolve
```

**Request Body:**
```json
{
  "vendor_id": 1,
  "coa_id": 5,
  "notes": "Resolved",
  "create_mapping_rule": true
}
```

**Response:** `200 OK`

---

## Bank Accounts API

### List Bank Accounts

```http
GET /api/bank/accounts
```

**Query Parameters:**
- `store_id` (integer): Filter by store

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "bank_name": "Chase",
      "account_number_last_four": "8741",
      "account_type": "checking",
      "store_id": 1,
      "current_balance": "50000.00",
      "last_reconciled_date": "2025-10-31",
      "is_active": true
    }
  ]
}
```

### Create Bank Account

```http
POST /api/bank/accounts
```

**Request Body:**
```json
{
  "bank_name": "Chase",
  "account_number_last_four": "8741",
  "account_type": "checking",
  "store_id": 1,
  "opening_balance": "50000.00"
}
```

### Get Bank Account

```http
GET /api/bank/accounts/{id}
```

### Update Bank Account

```http
PUT /api/bank/accounts/{id}
```

### Delete Bank Account

```http
DELETE /api/bank/accounts/{id}
```

---

## Bank Import API

### Import Bank Statement

```http
POST /api/bank/import
```

**Request:** `multipart/form-data`
- `file` (file): CSV file
- `bank_account_id` (integer): Bank account ID

**Response:**
```json
{
  "message": "Bank statement imported successfully",
  "imported": 50,
  "duplicates": 2,
  "unmatched": 5
}
```

---

## Bank Reconciliation API

### Get Reconciliation Data

```http
GET /api/bank/reconciliation
```

**Query Parameters:**
- `bank_account_id` (integer): Bank account ID
- `start_date` (date): Start date
- `end_date` (date): End date
- `status` (string): unmatched, matched, reviewed, exception

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "transaction_date": "2025-11-01",
      "description": "DEPOSIT",
      "amount": "5000.00",
      "transaction_type": "credit",
      "reconciliation_status": "unmatched",
      "potential_matches": [
        {
          "type": "daily_report",
          "id": 123,
          "date": "2025-11-01",
          "amount": "5000.00",
          "confidence": 95
        }
      ]
    }
  ]
}
```

### Match Transaction

```http
POST /api/bank/reconciliation/{id}/match
```

**Request Body:**
```json
{
  "expense_id": 123,
  "notes": "Matched to expense"
}
```

OR

```json
{
  "revenue_id": 456,
  "notes": "Matched to daily report"
}
```

### Mark as Reviewed

```http
POST /api/bank/reconciliation/{id}/mark-reviewed
```

**Request Body:**
```json
{
  "notes": "Bank fee"
}
```

---

## P&L Reports API

### Get P&L Report

```http
GET /api/reports/pl
```

**Query Parameters:**
- `store_id` (integer): Store ID (optional)
- `start_date` (date): Start date (required)
- `end_date` (date): End date (required)
- `comparison_period` (string): previous_month, previous_year, custom
- `comparison_start_date` (date): Custom comparison start
- `comparison_end_date` (date): Custom comparison end

**Response:**
```json
{
  "period": {
    "start_date": "2025-11-01",
    "end_date": "2025-11-30",
    "store_id": 1
  },
  "pl": {
    "revenue": {
      "items": [
        {
          "name": "Food Sales",
          "amount": 50000.00,
          "coa_id": 1
        }
      ],
      "total": 75000.00
    },
    "cogs": {
      "items": [
        {
          "name": "Food Purchases",
          "amount": 20000.00
        }
      ],
      "total": 25000.00
    },
    "gross_profit": 50000.00,
    "gross_margin": 66.67,
    "operating_expenses": {
      "items": [
        {
          "name": "Payroll",
          "amount": 15000.00
        }
      ],
      "total": 30000.00
    },
    "net_profit": 20000.00,
    "net_margin": 26.67
  },
  "comparison": {
    "gross_profit_variance": 5000.00,
    "gross_profit_variance_percent": 11.11,
    ...
  }
}
```

### Get P&L Summary

```http
GET /api/reports/pl/summary
```

**Query Parameters:** Same as full P&L

**Response:**
```json
{
  "revenue": 75000.00,
  "cogs": 25000.00,
  "gross_profit": 50000.00,
  "operating_expenses": 30000.00,
  "net_profit": 20000.00,
  "gross_margin": 66.67,
  "net_margin": 26.67
}
```

### Save P&L Snapshot

```http
POST /api/reports/pl/snapshot
```

**Request Body:**
```json
{
  "name": "November 2025 P&L",
  "store_id": 1,
  "start_date": "2025-11-01",
  "end_date": "2025-11-30"
}
```

### List P&L Snapshots

```http
GET /api/reports/pl/snapshots
```

**Query Parameters:**
- `store_id` (integer): Filter by store

### Get Consolidated P&L

```http
GET /api/reports/pl/consolidated
```

**Query Parameters:**
- `start_date` (date)
- `end_date` (date)
- `store_ids` (array): Array of store IDs

### Get Store Comparison

```http
GET /api/reports/pl/store-comparison
```

**Query Parameters:**
- `store_ids` (array): Array of store IDs
- `start_date` (date)
- `end_date` (date)

### Drill Down

```http
GET /api/reports/pl/drill-down
```

**Query Parameters:**
- `coa_id` (integer): COA ID
- `store_id` (integer): Store ID
- `start_date` (date)
- `end_date` (date)

**Response:**
```json
{
  "coa": {
    "id": 5,
    "account_name": "Food Purchases"
  },
  "transactions": [
    {
      "id": 1,
      "transaction_date": "2025-11-01",
      "amount": "1000.00",
      "vendor": {
        "vendor_name": "SYSCO"
      },
      ...
    }
  ],
  "total": 20000.00
}
```

---

## Merchant Fees API

### Get Merchant Fee Summary

```http
GET /api/reports/merchant-fees
```

**Query Parameters:**
- `store_id` (integer): Store ID
- `start_date` (date): Start date
- `end_date` (date): End date

**Response:**
```json
{
  "total_fees": 1837.50,
  "total_sales": 75000.00,
  "average_fee_percent": 2.45,
  "fee_count": 30,
  "by_processor": {
    "Square": {
      "fees": 1837.50,
      "sales": 75000.00,
      "percent": 2.45
    }
  }
}
```

### Get Merchant Fee Trends

```http
GET /api/reports/merchant-fees/trends
```

**Query Parameters:** Same as summary

**Response:**
```json
{
  "daily": [
    {
      "date": "2025-11-01",
      "fees": 61.25,
      "sales": 2500.00
    }
  ],
  "monthly": [...]
}
```

---

## Third-Party Integration API

### Import Third-Party Statement

```http
POST /api/third-party/import
```

**Request:** `multipart/form-data`
- `file` (file): PDF or CSV file
- `platform` (string): grubhub, ubereats, doordash
- `store_id` (integer): Store ID

**Response:**
```json
{
  "message": "Statement imported successfully",
  "statement_id": 1,
  "gross_sales": 5000.00,
  "total_fees": 1250.00,
  "net_deposit": 3750.00,
  "expenses_created": 3
}
```

### List Third-Party Statements

```http
GET /api/third-party/statements
```

**Query Parameters:**
- `platform` (string): Filter by platform
- `store_id` (integer): Filter by store
- `start_date` (date): Start date
- `end_date` (date): End date

---

## Error Responses

### 400 Bad Request

```json
{
  "message": "Validation failed",
  "errors": {
    "vendor_name": ["The vendor name field is required."],
    "amount": ["The amount must be a number."]
  }
}
```

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
  "message": "You do not have permission to perform this action."
}
```

### 404 Not Found

```json
{
  "message": "Resource not found."
}
```

### 422 Unprocessable Entity

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "store_id": ["The selected store is invalid."]
  }
}
```

### 500 Internal Server Error

```json
{
  "message": "Server Error",
  "error": "Error details..."
}
```

---

## Rate Limits

- Default: 60 requests per minute per IP
- Authenticated users: 120 requests per minute
- Admin users: 200 requests per minute

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1633024800
```

---

## Pagination

All list endpoints support pagination:

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `per_page` (integer): Items per page (default: 25, max: 100)

**Response Headers:**
```
Link: <http://example.com/api/resource?page=2>; rel="next",
      <http://example.com/api/resource?page=1>; rel="prev"
```

**Response Body:**
```json
{
  "data": [...],
  "current_page": 1,
  "from": 1,
  "last_page": 10,
  "per_page": 25,
  "to": 25,
  "total": 250
}
```

---

## Versioning

Current API version: **v1**

All endpoints are under `/api` without version prefix. Future versions will use `/api/v2`, etc.

---

**Last Updated**: November 2025  
**Version**: 1.0.0




