# User Guide: Expense Management & P&L Module

## Table of Contents

1. [Getting Started](#getting-started)
2. [Chart of Accounts Setup](#chart-of-accounts-setup)
3. [Vendor Management](#vendor-management)
4. [Uploading Credit Card Statements](#uploading-credit-card-statements)
5. [Bank Reconciliation Process](#bank-reconciliation-process)
6. [Understanding the Review Queue](#understanding-the-review-queue)
7. [Generating P&L Reports](#generating-pl-reports)
8. [Role-Based Features Guide](#role-based-features-guide)

---

## Getting Started

### Overview

The Expense Management & P&L Module helps you track all business expenses, reconcile bank accounts, and generate comprehensive profit & loss statements.

### Key Features

- **Expense Tracking**: Track all cash and credit card expenses
- **Vendor Management**: Organize suppliers and vendors
- **Bank Reconciliation**: Match bank transactions with expenses
- **P&L Reports**: Generate detailed profit & loss statements
- **Multi-Store Support**: Manage expenses across multiple locations

### First Steps

1. **Set Up Chart of Accounts**: Create your expense categories
2. **Add Vendors**: Register your suppliers
3. **Configure Bank Accounts**: Add your bank accounts for reconciliation
4. **Start Tracking Expenses**: Import or manually enter expenses

---

## Chart of Accounts Setup

### What is a Chart of Accounts?

A Chart of Accounts (COA) is a list of all account categories used to organize financial transactions. It includes:
- **Revenue Accounts**: Sales, other income
- **COGS (Cost of Goods Sold)**: Food purchases, beverage purchases
- **Operating Expenses**: Payroll, rent, utilities, etc.

### Creating Accounts

1. Navigate to **Admin → Chart of Accounts**
2. Click **Add Account**
3. Fill in the details:
   - **Account Code**: Unique identifier (e.g., "6000")
   - **Account Name**: Descriptive name (e.g., "Food Purchases")
   - **Account Type**: Select from Revenue, COGS, Expense, or Other Income
   - **Parent Account**: (Optional) For hierarchical organization
   - **Store Assignment**: Assign to specific stores or all stores
4. Click **Save Account**

### Account Types Explained

- **Revenue**: All income sources (sales, catering, etc.)
- **COGS**: Direct costs of products sold (food, beverages, packaging)
- **Expense**: Operating expenses (rent, utilities, marketing)
- **Other Income**: Non-operating income (interest, refunds)

### Store Assignment

- **Global Accounts**: Available to all stores (default)
- **Store-Specific**: Assign accounts to specific stores only

### Tips

- Use consistent naming conventions
- Group related accounts together
- Review system accounts before creating custom ones
- Deactivate accounts instead of deleting them

---

## Vendor Management

### Adding Vendors

1. Navigate to **Admin → Vendors**
2. Click **Add Vendor**
3. Fill in vendor information:
   - **Vendor Name**: Company name
   - **Vendor Identifier**: Unique ID or tax ID
   - **Vendor Type**: Food, Beverage, Supplies, Utilities, Services, Other
   - **Contact Information**: Name, email, phone
   - **Default COA**: Assign a default expense category
   - **Store Assignment**: Which stores use this vendor
4. Click **Save Vendor**

### Vendor Aliases

Vendors may appear with different names on bank statements. Add aliases to help the system match transactions:

1. Open vendor details
2. Click **Add Alias**
3. Enter alternative names (e.g., "SYSCO", "SYSCO FOOD SERVICE", "SYSCO CORP")
4. The system will match transactions to this vendor using any alias

### Default COA Assignment

When a vendor has a default COA assigned, expenses from that vendor are automatically categorized. This saves time during review.

### Searching Vendors

Use the search bar to find vendors by:
- Name
- Identifier
- Contact name
- Email

### Best Practices

- Add all common suppliers
- Include vendor aliases for statement variations
- Assign default COAs for automatic categorization
- Keep vendor information up to date

---

## Uploading Credit Card Statements

### Supported Formats

The system supports CSV imports from:
- **Chase Bank**: Automatic format detection
- **Generic Format**: Date, Description, Amount, Category
- **Custom Format**: Configurable column mapping

### Upload Process

1. Navigate to **Admin → Expenses → Import**
2. Click **Upload CSV**
3. Select your bank statement file
4. Choose the store
5. Review the preview:
   - Check detected columns
   - Verify first few transactions
   - Adjust mapping if needed
6. Click **Import**

### Column Mapping

The system automatically detects common columns:
- **Date**: Transaction date
- **Description**: Vendor or transaction description
- **Amount**: Transaction amount
- **Category**: (Optional) Expense category

If columns aren't detected correctly, manually map them using the dropdown menus.

### Import Preview

Before importing, review:
- **Total Transactions**: Number of rows found
- **Auto-Matched**: Transactions matched to existing vendors
- **Needs Review**: Transactions requiring categorization
- **Duplicates**: Potential duplicate transactions

### Handling Duplicates

The system detects duplicates based on:
- Store
- Date
- Vendor name
- Amount

Duplicate transactions are flagged and can be reviewed before import.

### After Import

1. Check the **Review Queue** for unmatched transactions
2. Review auto-categorized transactions for accuracy
3. Resolve any flagged items

---

## Bank Reconciliation Process

### Setting Up Bank Accounts

1. Navigate to **Admin → Bank Accounts**
2. Click **Add Bank Account**
3. Enter account details:
   - **Bank Name**: (e.g., "Chase", "Bank of America")
   - **Account Number Last Four**: Last 4 digits for identification
   - **Account Type**: Checking, Savings, or Credit Card
   - **Store**: (Optional) Corporate or store-specific
   - **Opening Balance**: Starting balance
4. Click **Save**

### Uploading Bank Statements

1. Open the bank account
2. Click **Upload Statement**
3. Select your CSV file
4. Choose date range
5. Click **Upload**

### Auto-Matching

The system automatically matches:
- **Deposits**: Matches to daily report credit card sales (net after fees)
- **Withdrawals**: Matches to expense transactions by date, amount, and vendor

### Manual Matching

For unmatched transactions:

1. Open the reconciliation dashboard
2. Select an unmatched transaction
3. Review potential matches shown on the right
4. Click **Match** to link transactions
5. Add notes if needed

### Marking as Reviewed

For transactions that don't need matching (fees, interest):

1. Select the transaction
2. Click **Mark as Reviewed**
3. Add notes explaining the transaction
4. Optionally create an expense entry

### Reconciliation Summary

View the reconciliation status:
- **Total Transactions**: All bank transactions
- **Matched**: Successfully matched
- **Unmatched**: Requiring attention
- **Reviewed**: Manually reviewed
- **Balance Discrepancy**: Difference between expected and actual

### Best Practices

- Reconcile monthly
- Review all unmatched transactions
- Document exceptions with notes
- Keep statements organized by date

---

## Understanding the Review Queue

### What is the Review Queue?

The Review Queue contains expense transactions that need attention, such as:
- **Vendor Not Found**: Transaction couldn't be matched to a vendor
- **COA Not Assigned**: No expense category assigned
- **Possible Duplicate**: Similar transaction already exists
- **Needs Verification**: Requires manual review

### Accessing the Review Queue

1. Navigate to **Admin → Expenses → Review Queue**
2. View summary cards showing counts by issue type
3. Review transactions grouped by issue type

### Resolving Transactions

1. Click **Review** on a transaction
2. Review transaction details:
   - Date, amount, store
   - Original description
   - Transaction type
3. Assign:
   - **Vendor**: Select or create vendor
   - **Category (COA)**: Select expense category
4. (Optional) Check **Create Mapping Rule** to remember this categorization
5. Add notes if needed
6. Click **Resolve**

### Bulk Categorization

For multiple transactions with the same vendor/category:

1. Select multiple transactions using checkboxes
2. Click **Bulk Categorize**
3. Select vendor and category
4. Click **Apply to All**

### Mapping Rules

When you check "Create Mapping Rule", the system learns:
- **Description Pattern**: Recognizes similar transactions
- **Vendor**: Auto-assigns vendor
- **Category**: Auto-assigns COA

This improves automatic categorization over time.

### Skipping Transactions

If a transaction doesn't need immediate resolution:
- Click **Skip for Now**
- It will remain in the queue for later review

---

## Generating P&L Reports

### Overview

The Profit & Loss (P&L) Report shows your financial performance:
- **Revenue**: Total sales
- **COGS**: Cost of goods sold
- **Gross Profit**: Revenue minus COGS
- **Operating Expenses**: All business expenses
- **Net Profit**: Final profit after all expenses

### Generating a Report

1. Navigate to **Reports → P&L Report**
2. Select options:
   - **Store**: Specific store or all stores
   - **Date Range**: Start and end dates
   - **Comparison Period**: (Optional) Compare to previous period
3. Click **Generate Report**

### Report Sections

#### Revenue Section
- Food Sales
- Beverage Sales
- Third-Party Sales (Grubhub, UberEats, DoorDash)
- Other Income

#### COGS Section
- Food Purchases
- Beverage Purchases
- Packaging Supplies

#### Operating Expenses
- Payroll
- Rent
- Utilities
- Marketing
- Credit Card Processing Fees
- Insurance
- Maintenance & Repairs
- Supplies
- Professional Services
- Other Expenses

### Understanding Margins

- **Gross Margin**: (Gross Profit / Revenue) × 100
  - Shows profitability before operating expenses
  - Target: 60-70% for restaurants

- **Net Margin**: (Net Profit / Revenue) × 100
  - Shows final profitability
  - Target: 5-10% for restaurants

### Drill-Down

Click any line item to see:
- All transactions in that category
- Date, vendor, amount, description
- Export options

### Comparison Periods

Compare current period to:
- Previous month
- Previous year
- Custom date range

View:
- Current period amounts
- Comparison period amounts
- Variance (difference)
- Variance percentage

### Saving Snapshots

Save P&L reports for future reference:

1. Generate the report
2. Click **Save Snapshot**
3. Enter a name (e.g., "Q1 2025 P&L")
4. Click **Save**

Access saved snapshots from **Reports → P&L Snapshots**

### Multi-Store Comparison

View side-by-side comparison:

1. Select **Multi-Store Comparison**
2. Choose stores to compare
3. Select date range
4. View comparative metrics

### Exporting Reports

Export formats:
- **PDF**: For printing or sharing
- **Excel**: For further analysis
- **CSV**: For data import

1. Generate the report
2. Click **Export**
3. Choose format
4. Download file

---

## Role-Based Features Guide

### Manager Role

**Can View:**
- Chart of Accounts (read-only)
- Vendors (read-only)
- Expenses (own store only)
- P&L Reports (own store only)

**Can Create:**
- Manual expense entries
- Daily reports

**Cannot Access:**
- Bank reconciliation
- CSV imports
- Review queue
- System configuration

**Use Cases:**
- Enter daily cash expenses
- View expense ledger
- Generate store P&L

### Owner Role

**Can View:**
- Chart of Accounts
- Vendors
- Expenses (own stores)
- P&L Reports (own stores)
- Bank reconciliation
- Review queue

**Can Create/Update:**
- Vendors
- Expenses
- Bank accounts
- CSV imports

**Can Manage:**
- Review queue transactions
- Bank reconciliation
- P&L snapshots

**Cannot Access:**
- System-wide COA management
- All stores (only own stores)

**Use Cases:**
- Full expense management
- Bank reconciliation
- Multi-store P&L (if multiple stores)

### Admin/Super Admin Role

**Full Access:**
- All stores
- All features
- System configuration
- User management

**Special Capabilities:**
- Create/edit/delete COA
- Manage all vendors
- View all expenses
- System-wide reports
- Import troubleshooting

**Use Cases:**
- System administration
- Multi-store management
- Troubleshooting
- Data analysis

---

## FAQ

### Import & Matching

**Q: Why isn't my vendor being matched automatically?**
A: The system uses fuzzy matching. Add vendor aliases with common variations of the name to improve matching.

**Q: How does duplicate detection work?**
A: The system creates a hash from store ID, date, vendor name, and amount. If an identical hash exists, it's flagged as a duplicate.

**Q: Can I import the same file twice?**
A: No, the system checks file hashes to prevent duplicate imports. If you need to re-import, you'll need to modify the file slightly.

### Bank Reconciliation

**Q: Why aren't deposits matching my daily reports?**
A: The system matches net deposits (after merchant fees). Check that merchant fees are being calculated correctly (2.45% default).

**Q: What if I have bank fees or interest?**
A: Mark these transactions as "Reviewed" and add notes. You can optionally create expense entries for fees.

**Q: How often should I reconcile?**
A: Monthly reconciliation is recommended, but you can reconcile more frequently for better accuracy.

### P&L Reports

**Q: Why is my revenue different from daily reports?**
A: P&L reports aggregate all revenue sources. Check that all daily reports are included in the date range.

**Q: How do I see transactions for a specific category?**
A: Click on any line item in the P&L report to drill down and see all transactions.

**Q: Can I compare this month to last month?**
A: Yes, use the comparison period feature when generating the report.

### Fees & Calculations

**Q: How are merchant fees calculated?**
A: Default is 2.45% of credit card sales. This can be configured in system settings.

**Q: What fees are tracked for third-party platforms?**
A: Marketing fees (typically 15%), delivery fees (typically 10%), and processing fees are tracked separately.

**Q: How are gross margins calculated?**
A: Gross Margin = (Revenue - COGS) / Revenue × 100

---

## Getting Help

### Support Resources

- **Documentation**: Check this guide for detailed instructions
- **In-App Help**: Look for tooltips and help icons
- **Admin Support**: Contact your system administrator

### Common Issues

- **Import Errors**: Check file format and column mapping
- **Matching Issues**: Verify vendor names and aliases
- **Calculation Errors**: Review transaction dates and amounts

---

**Last Updated**: November 2025  
**Version**: 1.0.0




