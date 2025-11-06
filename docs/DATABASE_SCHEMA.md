# Database Schema Documentation

## Overview

This document describes the database schema for the Restaurant Backend Phase 2: Expense Management & P&L Module.

## Entity Relationship Diagram

```
Users
  ├── DailyReports (created_by)
  ├── ExpenseTransactions (created_by)
  ├── BankAccounts (created_by)
  └── Stores (created_by)

Stores
  ├── DailyReports
  ├── ExpenseTransactions
  ├── BankAccounts
  ├── COAStoreAssignments
  ├── VendorStoreAssignments
  └── PlSnapshots

ChartOfAccounts
  ├── ExpenseTransactions (coa_id)
  ├── Vendors (default_coa_id)
  └── COAStoreAssignments

Vendors
  ├── ExpenseTransactions (vendor_id)
  ├── VendorAliases
  └── VendorStoreAssignments

ExpenseTransactions
  ├── DailyReports (daily_report_id)
  ├── ImportBatches (import_batch_id)
  ├── ThirdPartyStatements (third_party_statement_id)
  └── BankTransactions (matched_expense_id)

BankAccounts
  └── BankTransactions

ImportBatches
  ├── ExpenseTransactions
  ├── BankTransactions
  └── ThirdPartyStatements

ThirdPartyStatements
  └── ExpenseTransactions
```

---

## Tables

### chart_of_accounts

Stores accounting categories for organizing financial transactions.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| account_code | varchar(10) | UNIQUE, NOT NULL | Account identifier |
| account_name | varchar(100) | NOT NULL | Account name |
| account_type | enum | NOT NULL | Revenue, COGS, Expense, Other Income |
| parent_account_id | bigint | FK → chart_of_accounts.id | Parent account for hierarchy |
| is_active | boolean | DEFAULT true | Active status |
| is_system_account | boolean | DEFAULT false | System account flag |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `account_code` (UNIQUE)
- `account_type`
- `is_active`
- `parent_account_id`

**Relationships:**
- `parent_account_id` → `chart_of_accounts.id`
- One-to-many with `expense_transactions` (via `coa_id`)
- Many-to-many with `stores` (via `coa_store_assignments`)

---

### coa_store_assignments

Pivot table for store-specific COA assignments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| coa_id | bigint | FK → chart_of_accounts.id | COA ID |
| store_id | bigint | FK → stores.id | Store ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `coa_id`
- `store_id`
- `(coa_id, store_id)` (UNIQUE)

---

### vendors

Stores supplier and vendor information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| vendor_name | varchar(100) | NOT NULL | Vendor name |
| vendor_identifier | varchar(50) | NULL | Tax ID or identifier |
| vendor_type | enum | NOT NULL | Food, Beverage, Supplies, Utilities, Services, Other |
| contact_name | varchar(100) | NULL | Contact person |
| email | varchar(100) | NULL | Email address |
| phone | varchar(20) | NULL | Phone number |
| address | text | NULL | Address |
| default_coa_id | bigint | FK → chart_of_accounts.id | Default expense category |
| is_active | boolean | DEFAULT true | Active status |
| notes | text | NULL | Notes |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `vendor_name`
- `vendor_identifier`
- `vendor_type`
- `is_active`
- `default_coa_id`

**Relationships:**
- `default_coa_id` → `chart_of_accounts.id`
- One-to-many with `vendor_aliases`
- One-to-many with `expense_transactions` (via `vendor_id`)
- Many-to-many with `stores` (via `vendor_store_assignments`)

---

### vendor_aliases

Alternative names for vendors (for fuzzy matching).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| vendor_id | bigint | FK → vendors.id | Vendor ID |
| alias | varchar(100) | NOT NULL | Alias name |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `vendor_id`
- `alias`

---

### vendor_store_assignments

Pivot table for store-specific vendor assignments.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| vendor_id | bigint | FK → vendors.id | Vendor ID |
| store_id | bigint | FK → stores.id | Store ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `vendor_id`
- `store_id`
- `(vendor_id, store_id)` (UNIQUE)

---

### expense_transactions

Unified expense ledger for all transaction types.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| transaction_type | enum | NOT NULL | cash, credit_card, bank_transfer, check |
| transaction_date | date | NOT NULL | Transaction date |
| post_date | date | NULL | Post date (for credit cards) |
| store_id | bigint | FK → stores.id | Store ID |
| vendor_id | bigint | FK → vendors.id | NULL | Vendor ID |
| vendor_name_raw | varchar(255) | NULL | Original vendor name from import |
| coa_id | bigint | FK → chart_of_accounts.id | NULL | COA ID |
| amount | decimal(10,2) | NOT NULL | Transaction amount |
| description | text | NULL | Description |
| reference_number | varchar(50) | NULL | Check number, transaction ID |
| payment_method | enum | NOT NULL | cash, credit_card, debit_card, check, eft, other |
| card_last_four | varchar(4) | NULL | Last 4 digits of card |
| receipt_url | varchar(255) | NULL | Receipt URL |
| notes | text | NULL | Notes |
| is_reconciled | boolean | DEFAULT false | Reconciled status |
| reconciled_date | timestamp | NULL | Reconciliation date |
| reconciled_by | bigint | FK → users.id | NULL | User who reconciled |
| needs_review | boolean | DEFAULT false | Review flag |
| review_reason | varchar(100) | NULL | Review reason |
| duplicate_check_hash | varchar(255) | NULL | Duplicate detection hash |
| import_batch_id | bigint | FK → import_batches.id | NULL | Import batch ID |
| daily_report_id | bigint | FK → daily_reports.id | NULL | Daily report ID |
| third_party_statement_id | bigint | FK → third_party_statements.id | NULL | Third-party statement ID |
| created_by | bigint | FK → users.id | NOT NULL | Creator user ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `transaction_date`
- `store_id`
- `vendor_id`
- `coa_id`
- `(transaction_date, store_id)` (composite)
- `duplicate_check_hash`
- `(needs_review, store_id)` (composite)
- `is_reconciled`
- `import_batch_id`
- `daily_report_id`
- `third_party_statement_id`

**Relationships:**
- `store_id` → `stores.id`
- `vendor_id` → `vendors.id`
- `coa_id` → `chart_of_accounts.id`
- `reconciled_by` → `users.id`
- `created_by` → `users.id`
- `import_batch_id` → `import_batches.id`
- `daily_report_id` → `daily_reports.id`
- `third_party_statement_id` → `third_party_statements.id`
- One-to-one with `bank_transactions` (via `matched_expense_id`)

---

### import_batches

Tracks CSV import batches to prevent duplicates.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| import_type | enum | NOT NULL | credit_card, bank_statement |
| file_name | varchar(255) | NOT NULL | Original file name |
| file_hash | varchar(255) | NOT NULL | MD5 hash of file |
| store_id | bigint | FK → stores.id | NULL | Store ID |
| transaction_count | integer | DEFAULT 0 | Total transactions |
| imported_count | integer | DEFAULT 0 | Successfully imported |
| duplicate_count | integer | DEFAULT 0 | Duplicates found |
| error_count | integer | DEFAULT 0 | Errors |
| needs_review_count | integer | DEFAULT 0 | Needs review |
| date_range_start | date | NULL | Import date range start |
| date_range_end | date | NULL | Import date range end |
| status | enum | DEFAULT processing | processing, completed, failed |
| error_log | text | NULL | Error log |
| imported_at | timestamp | NULL | Import timestamp |
| imported_by | bigint | FK → users.id | NOT NULL | Importer user ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `file_hash` (UNIQUE)
- `import_type`
- `store_id`
- `status`
- `imported_by`

**Relationships:**
- `store_id` → `stores.id`
- `imported_by` → `users.id`
- One-to-many with `expense_transactions`
- One-to-many with `bank_transactions`

---

### transaction_mapping_rules

Machine learning rules for auto-categorization.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| description_pattern | varchar(255) | NOT NULL | Pattern or keywords |
| vendor_id | bigint | FK → vendors.id | NULL | Vendor ID |
| coa_id | bigint | FK → chart_of_accounts.id | NOT NULL | COA ID |
| confidence_score | decimal(3,2) | DEFAULT 0.00 | Confidence (0.00-1.00) |
| times_used | integer | DEFAULT 0 | Usage count |
| times_correct | integer | DEFAULT 0 | Correct matches |
| times_incorrect | integer | DEFAULT 0 | Incorrect matches |
| last_used | timestamp | NULL | Last usage timestamp |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `description_pattern`
- `vendor_id`
- `coa_id`
- `confidence_score`

**Relationships:**
- `vendor_id` → `vendors.id`
- `coa_id` → `chart_of_accounts.id`

---

### bank_accounts

Bank account tracking for reconciliation.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| bank_name | varchar(100) | NOT NULL | Bank name |
| account_number_last_four | varchar(4) | NOT NULL | Last 4 digits |
| account_type | enum | NOT NULL | checking, savings, credit_card |
| store_id | bigint | FK → stores.id | NULL | Store ID (NULL = corporate) |
| opening_balance | decimal(10,2) | DEFAULT 0.00 | Opening balance |
| current_balance | decimal(10,2) | DEFAULT 0.00 | Current balance |
| last_reconciled_date | date | NULL | Last reconciliation date |
| is_active | boolean | DEFAULT true | Active status |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `store_id`
- `account_type`
- `is_active`

**Relationships:**
- `store_id` → `stores.id`
- One-to-many with `bank_transactions`

---

### bank_transactions

Bank statement transactions for reconciliation.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| bank_account_id | bigint | FK → bank_accounts.id | NOT NULL | Bank account ID |
| transaction_date | date | NOT NULL | Transaction date |
| post_date | date | NULL | Post date |
| description | text | NOT NULL | Transaction description |
| transaction_type | enum | NOT NULL | debit, credit |
| amount | decimal(10,2) | NOT NULL | Transaction amount |
| balance | decimal(10,2) | NULL | Account balance after transaction |
| reference_number | varchar(50) | NULL | Reference number |
| matched_expense_id | bigint | FK → expense_transactions.id | NULL | Matched expense |
| matched_revenue_id | bigint | FK → daily_reports.id | NULL | Matched revenue |
| reconciliation_status | enum | DEFAULT unmatched | unmatched, matched, reviewed, exception |
| reconciliation_notes | text | NULL | Reconciliation notes |
| import_batch_id | bigint | FK → import_batches.id | NULL | Import batch ID |
| duplicate_check_hash | varchar(255) | NULL | Duplicate detection hash |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `bank_account_id`
- `transaction_date`
- `reconciliation_status`
- `duplicate_check_hash`
- `matched_expense_id`
- `matched_revenue_id`
- `import_batch_id`

**Relationships:**
- `bank_account_id` → `bank_accounts.id`
- `matched_expense_id` → `expense_transactions.id`
- `matched_revenue_id` → `daily_reports.id`
- `import_batch_id` → `import_batches.id`

---

### third_party_statements

Third-party platform statements (Grubhub, UberEats, DoorDash).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| platform | enum | NOT NULL | grubhub, ubereats, doordash |
| store_id | bigint | FK → stores.id | NOT NULL | Store ID |
| statement_date | date | NOT NULL | Statement date |
| statement_id | varchar(100) | NOT NULL | Statement identifier |
| gross_sales | decimal(10,2) | NOT NULL | Gross sales |
| marketing_fees | decimal(10,2) | DEFAULT 0.00 | Marketing fees |
| delivery_fees | decimal(10,2) | DEFAULT 0.00 | Delivery fees |
| processing_fees | decimal(10,2) | DEFAULT 0.00 | Processing fees |
| net_deposit | decimal(10,2) | NOT NULL | Net deposit amount |
| sales_tax_collected | decimal(10,2) | DEFAULT 0.00 | Sales tax collected |
| import_batch_id | bigint | FK → import_batches.id | NULL | Import batch ID |
| file_name | varchar(255) | NULL | Original file name |
| file_hash | varchar(255) | NULL | File hash |
| imported_by | bigint | FK → users.id | NOT NULL | Importer user ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `platform`
- `store_id`
- `statement_date`
- `statement_id`
- `import_batch_id`
- `file_hash`

**Relationships:**
- `store_id` → `stores.id`
- `imported_by` → `users.id`
- `import_batch_id` → `import_batches.id`
- One-to-many with `expense_transactions`

---

### pl_snapshots

Saved P&L reports for historical reference.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | bigint | PK, AI | Primary key |
| name | varchar(255) | NOT NULL | Snapshot name |
| store_id | bigint | FK → stores.id | NULL | Store ID (NULL = all stores) |
| start_date | date | NOT NULL | Report start date |
| end_date | date | NOT NULL | Report end date |
| pl_data | json | NOT NULL | P&L data (JSON) |
| created_by | bigint | FK → users.id | NOT NULL | Creator user ID |
| created_at | timestamp | | Created timestamp |
| updated_at | timestamp | | Updated timestamp |

**Indexes:**
- `store_id`
- `created_at`

**Relationships:**
- `store_id` → `stores.id`
- `created_by` → `users.id`

---

## Data Types

### Enums

**account_type:**
- `Revenue`
- `COGS`
- `Expense`
- `Other Income`

**vendor_type:**
- `Food`
- `Beverage`
- `Supplies`
- `Utilities`
- `Services`
- `Other`

**transaction_type (expense_transactions):**
- `cash`
- `credit_card`
- `bank_transfer`
- `check`

**payment_method:**
- `cash`
- `credit_card`
- `debit_card`
- `check`
- `eft`
- `other`

**account_type (bank_accounts):**
- `checking`
- `savings`
- `credit_card`

**transaction_type (bank_transactions):**
- `debit`
- `credit`

**reconciliation_status:**
- `unmatched`
- `matched`
- `reviewed`
- `exception`

**import_type:**
- `credit_card`
- `bank_statement`

**status (import_batches):**
- `processing`
- `completed`
- `failed`

**platform:**
- `grubhub`
- `ubereats`
- `doordash`

**review_reason:**
- `Vendor not found`
- `COA not assigned`
- `Possible duplicate`
- `Needs verification`

---

## Constraints

### Foreign Keys

All foreign key constraints use `ON DELETE CASCADE` or `ON DELETE SET NULL` as appropriate:

- `expense_transactions.store_id` → `stores.id` (CASCADE)
- `expense_transactions.vendor_id` → `vendors.id` (SET NULL)
- `expense_transactions.coa_id` → `chart_of_accounts.id` (SET NULL)
- `expense_transactions.created_by` → `users.id` (CASCADE)
- `bank_transactions.bank_account_id` → `bank_accounts.id` (CASCADE)
- `bank_transactions.matched_expense_id` → `expense_transactions.id` (SET NULL)
- `bank_transactions.matched_revenue_id` → `daily_reports.id` (SET NULL)

### Unique Constraints

- `chart_of_accounts.account_code` (UNIQUE)
- `import_batches.file_hash` (UNIQUE)
- `coa_store_assignments(coa_id, store_id)` (UNIQUE)
- `vendor_store_assignments(vendor_id, store_id)` (UNIQUE)

---

## Indexes

### Performance Indexes

- Composite indexes on frequently queried columns:
  - `(transaction_date, store_id)` on `expense_transactions`
  - `(needs_review, store_id)` on `expense_transactions`
  - `(coa_id, store_id)` on `coa_store_assignments`
  - `(vendor_id, store_id)` on `vendor_store_assignments`

- Single column indexes on filter/search columns:
  - `vendor_name`, `vendor_identifier` on `vendors`
  - `account_code`, `account_type` on `chart_of_accounts`
  - `reconciliation_status` on `bank_transactions`
  - `duplicate_check_hash` on `expense_transactions` and `bank_transactions`

---

## Data Integrity

### Business Rules

1. **COA Assignment**: Expense transactions must have a COA (can be assigned during review)
2. **Vendor Matching**: Vendor matching is optional but recommended
3. **Store Isolation**: Transactions are filtered by store access
4. **Duplicate Detection**: Hash-based duplicate detection prevents duplicate imports
5. **Reconciliation**: Bank transactions can match to expenses or revenue
6. **System Accounts**: System accounts cannot be deleted, only deactivated

### Validation Rules

- Account codes must be unique
- Amounts must be positive (negative amounts handled via transaction type)
- Dates must be valid
- File hashes prevent duplicate imports
- Store assignments must reference valid stores

---

## Migration History

All migrations are tracked in `database/migrations/`:

- `2025_10_31_204814_create_chart_of_accounts_table.php`
- `2025_10_31_204828_create_coa_store_assignments_table.php`
- `2025_11_01_142901_create_vendors_table.php`
- `2025_11_01_142927_create_vendor_store_assignments_table.php`
- `2025_11_01_142935_create_vendor_aliases_table.php`
- `2025_11_01_151028_create_expense_transactions_table.php`
- `2025_11_01_184237_create_import_batches_table.php`
- `2025_11_01_184315_create_transaction_mapping_rules_table.php`
- `2025_11_01_203057_create_bank_accounts_table.php`
- `2025_11_01_203110_create_bank_transactions_table.php`
- `2025_11_02_165343_create_third_party_statements_table.php`
- `2025_11_03_194450_create_pl_snapshots_table.php`

---

**Last Updated**: November 2025  
**Version**: 1.0.0




