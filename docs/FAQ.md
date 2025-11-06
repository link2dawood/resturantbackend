# Frequently Asked Questions (FAQ)

## Import & Matching

### Q: Why isn't my vendor being matched automatically?

**A:** The system uses fuzzy matching to match vendors. Here's how to improve matching:

1. **Add Vendor Aliases**: Vendors may appear with different names on bank statements. Add common variations:
   - "SYSCO" → Add aliases: "SYSCO FOOD SERVICE", "SYSCO CORP", "SYSCO FOODS"
   - "US FOODS" → Add aliases: "US FOODSERVICE", "US FOOD"

2. **Check Vendor Name**: Ensure the vendor name in the system matches how it appears most commonly

3. **Review Fuzzy Match Threshold**: The system matches vendors with 85%+ similarity. If a vendor isn't matching, it may be below this threshold.

4. **Manual Review**: Check the Review Queue for unmatched transactions and manually assign vendors

### Q: How does duplicate detection work?

**A:** The system uses MD5 hashing to detect duplicates:

1. **Hash Generation**: Creates a hash from:
   - Store ID
   - Transaction date
   - Vendor name (or vendor_name_raw)
   - Amount

2. **Duplicate Check**: Compares the hash with existing transactions

3. **Duplicate Handling**:
   - If hash exists and is from the same import → Transaction is skipped
   - If hash exists from different source → Flagged for review as "Possible duplicate"

4. **Manual Review**: Review flagged duplicates in the Review Queue to confirm or resolve

### Q: Can I import the same file twice?

**A:** No, the system prevents duplicate imports:

1. **File Hash Check**: Each uploaded file is hashed (MD5)
2. **Duplicate Detection**: If a file with the same hash was previously imported, the import is rejected
3. **Workaround**: If you need to re-import:
   - Modify the file slightly (add a space, change a value)
   - Or use a different file name (though hash is checked, not name)

### Q: What CSV formats are supported?

**A:** The system supports multiple formats:

1. **Chase Bank Format**: Automatically detected
   - Card, Transaction Date, Post Date, Description, Category, Type, Amount, Memo

2. **Generic Format**: 
   - Date, Description, Amount, Category, Store

3. **Custom Format**: 
   - Manual column mapping available
   - Supports common date formats (YYYY-MM-DD, MM/DD/YYYY)
   - Handles currency symbols and formatting

### Q: What if my CSV has encoding issues?

**A:** Common encoding issues and solutions:

1. **Problem**: Special characters appear as gibberish
   - **Solution**: Save CSV as UTF-8 encoding

2. **Problem**: Excel exports in wrong encoding
   - **Solution**: Use "Save As" → "CSV UTF-8" in Excel

3. **Problem**: Commas in descriptions break parsing
   - **Solution**: Ensure CSV uses proper quoting (most tools handle this automatically)

---

## Bank Reconciliation

### Q: Why aren't deposits matching my daily reports?

**A:** The system matches **net deposits** (after merchant fees). Here's how it works:

1. **Credit Card Sales**: From daily reports
2. **Merchant Fee Calculation**: 2.45% of gross (default)
3. **Net Deposit**: Gross - Fee
4. **Matching**: System matches bank deposits to net deposit amount

**Check**:
- Verify merchant fee rate is correct (Settings → Merchant Fees)
- Check that daily reports have credit card sales entered
- Verify dates match (allows ±2 days for processing)
- Check amount tolerance (±$1 default)

### Q: What if I have bank fees or interest?

**A:** Handle bank fees and interest separately:

1. **Mark as Reviewed**:
   - Select the transaction
   - Click "Mark as Reviewed"
   - Add notes explaining the transaction (e.g., "Monthly service fee")

2. **Create Expense Entry** (Optional):
   - If you want to track fees as expenses
   - Create manual expense entry
   - Link to bank transaction if needed

3. **Common Bank Fees**:
   - Service fees
   - Overdraft fees
   - Interest charges
   - Wire transfer fees

### Q: How often should I reconcile?

**A:** Recommended reconciliation schedule:

1. **Monthly**: Minimum for most restaurants
2. **Weekly**: For high-volume operations
3. **Daily**: For critical accounts or high transaction volume

**Benefits of Regular Reconciliation**:
- Catch errors early
- Identify discrepancies quickly
- Maintain accurate financial records
- Easier to resolve issues while fresh

### Q: What if transactions don't match exactly?

**A:** The system allows for small differences:

1. **Date Tolerance**: ±2 days for deposits, ±3 days for withdrawals
2. **Amount Tolerance**: ±$1 for deposits, ±$0.50 for withdrawals

**If Still Not Matching**:
- Check for processing delays
- Verify amounts are correct
- Look for fees deducted
- Manually match if needed

---

## P&L Reports

### Q: Why is my revenue different from daily reports?

**A:** P&L reports aggregate all revenue sources:

1. **Check Date Range**: Ensure all daily reports are included
2. **Multiple Revenue Sources**:
   - Food Sales (from daily reports)
   - Beverage Sales (from daily reports)
   - Third-Party Sales (Grubhub, UberEats, DoorDash)
   - Other Income
3. **Verify Daily Reports**: Check that all reports are entered and approved

### Q: How do I see transactions for a specific category?

**A:** Use drill-down functionality:

1. **Generate P&L Report**: Select date range and store
2. **Click Line Item**: Click on any category (e.g., "Food Purchases")
3. **View Details**: See all transactions in that category
4. **Export**: Download transaction details if needed

### Q: Can I compare this month to last month?

**A:** Yes, use comparison periods:

1. **Generate Report**: Select current month date range
2. **Enable Comparison**: Toggle "Compare Period"
3. **Select Comparison**: Choose "Previous Month" or "Previous Year"
4. **View Results**: See current vs. comparison with variance

### Q: What's the difference between gross margin and net margin?

**A:** 

**Gross Margin**:
- Formula: (Gross Profit / Revenue) × 100
- Gross Profit = Revenue - COGS
- Shows profitability before operating expenses
- Target: 60-70% for restaurants

**Net Margin**:
- Formula: (Net Profit / Revenue) × 100
- Net Profit = Revenue - COGS - Operating Expenses
- Shows final profitability after all expenses
- Target: 5-10% for restaurants

### Q: How are operating expenses calculated?

**A:** Operating expenses include all non-COGS expenses:

1. **Payroll**: Wages, salaries, benefits
2. **Rent**: Lease payments
3. **Utilities**: Electricity, water, gas, internet
4. **Marketing**: Advertising, promotions
5. **Credit Card Processing Fees**: Merchant fees
6. **Insurance**: Business insurance
7. **Maintenance & Repairs**: Equipment, facility
8. **Supplies**: Non-food supplies
9. **Professional Services**: Legal, accounting
10. **Other Expenses**: Miscellaneous

All expenses must be categorized with a COA in the "Expense" account type.

---

## Fees & Calculations

### Q: How are merchant fees calculated?

**A:** Merchant fees are calculated automatically:

1. **Default Rate**: 2.45% (configurable in Settings)
2. **Calculation**: Fee = Credit Card Sales × Fee Rate
3. **Example**: $1,000 credit card sales × 2.45% = $24.50 fee
4. **Net Deposit**: $1,000 - $24.50 = $975.50

**When Calculated**:
- Automatically when daily report is saved
- Creates expense transaction for fee
- Creates expected bank transaction for net deposit

### Q: What fees are tracked for third-party platforms?

**A:** Third-party platforms have multiple fee types:

1. **Marketing Fees**: Typically 15% of gross sales
   - Grubhub: 15% (varies by market)
   - UberEats: 15-30% (varies)
   - DoorDash: 15-30% (varies)

2. **Delivery Fees**: Typically 10% of gross sales
   - Variable by platform and market

3. **Processing Fees**: Fixed per transaction
   - Usually $0.30-0.50 per order

4. **Total Fees**: Sum of all fee types

**Tracking**:
- Each fee type creates separate expense transaction
- Gross sales create revenue transaction
- Net deposit creates expected bank transaction

### Q: Can I change the merchant fee rate?

**A:** Yes, merchant fee rates can be configured:

1. **Navigate**: Settings → Merchant Fees
2. **Set Default Rate**: Update default percentage
3. **Processor-Specific**: Set rates for different processors if needed
4. **Note**: Changes only affect future transactions

### Q: How are gross margins calculated?

**A:** Gross margin calculation:

1. **Revenue**: Total sales from all sources
2. **COGS**: Cost of goods sold (food, beverages, packaging)
3. **Gross Profit**: Revenue - COGS
4. **Gross Margin**: (Gross Profit / Revenue) × 100

**Example**:
- Revenue: $10,000
- COGS: $3,500
- Gross Profit: $6,500
- Gross Margin: 65%

---

## Review Queue

### Q: Why are transactions in the review queue?

**A:** Transactions appear in review queue for several reasons:

1. **Vendor Not Found**: Transaction couldn't be matched to a vendor
   - **Solution**: Create vendor or add alias

2. **COA Not Assigned**: No expense category assigned
   - **Solution**: Assign appropriate COA

3. **Possible Duplicate**: Similar transaction already exists
   - **Solution**: Review and confirm if duplicate

4. **Needs Verification**: System flagged for manual review
   - **Solution**: Review and resolve

### Q: How do mapping rules work?

**A:** Mapping rules help auto-categorize future transactions:

1. **Creation**: Created when you check "Create mapping rule" during resolution

2. **Pattern Matching**: System learns from description patterns
   - Example: "SQ *SQUARE" → Matches to Square vendor

3. **Confidence Score**: 
   - > 0.80: Auto-applied
   - 0.50-0.80: Suggested with review
   - < 0.50: Manual categorization required

4. **Improvement**: Rules improve as you confirm/correct categorizations

### Q: Can I bulk categorize transactions?

**A:** Yes, use bulk categorization:

1. **Select Transactions**: Check multiple transactions in review queue
2. **Click "Bulk Categorize"**: Opens bulk categorization modal
3. **Select Vendor & COA**: Choose vendor and category for all selected
4. **Apply**: All transactions are updated at once

**Use Cases**:
- Multiple transactions from same vendor
- Same category needed for multiple items
- Quick resolution of similar issues

---

## Technical Issues

### Q: Import is slow or times out

**A:** Solutions for slow imports:

1. **File Size**: Split large files into smaller batches
2. **Server Resources**: Check PHP memory limit and execution time
3. **Database**: Ensure database is optimized and indexed
4. **Timing**: Import during off-peak hours

**Configuration**:
- Increase `memory_limit` in php.ini
- Increase `max_execution_time` in php.ini

### Q: I'm getting permission errors

**A:** Check the following:

1. **User Role**: Verify your role has required permissions
2. **Store Access**: Ensure you have access to the store
3. **Route Protection**: Check if route requires specific permission
4. **Contact Admin**: If issues persist, contact system administrator

### Q: Data is not showing correctly

**A:** Common causes:

1. **Store Filter**: Check if store filter is applied
2. **Date Range**: Verify date range includes desired data
3. **Permissions**: Ensure you have access to view the data
4. **Cache**: Clear cache if recent changes aren't showing

**Clear Cache**:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Best Practices

### Q: How should I organize my Chart of Accounts?

**A:** Best practices:

1. **Use Account Codes**: 4-digit codes for easy organization
   - 4000-4999: Revenue
   - 5000-5999: COGS
   - 6000-7999: Operating Expenses

2. **Hierarchical Structure**: Use parent accounts for grouping
   - Example: 6000 (Operating Expenses) → 6100 (Payroll) → 6110 (Hourly Wages)

3. **Consistent Naming**: Use clear, descriptive names
   - Good: "Food Purchases"
   - Bad: "Food", "FP", "Purchases - Food"

4. **Store-Specific**: Create store-specific accounts when needed
   - Different tax rates
   - Different expense categories

### Q: How often should I review the review queue?

**A:** Recommended schedule:

1. **Daily**: For high-volume operations
2. **Weekly**: For most restaurants
3. **Before Month-End**: Ensure all transactions are categorized

**Benefits**:
- Accurate financial reports
- Better categorization over time
- Improved auto-matching
- Cleaner data

### Q: Should I use vendor aliases or create separate vendors?

**A:** Use vendor aliases for the same vendor:

1. **Same Vendor, Different Names**: Use aliases
   - "SYSCO" → Add aliases for variations

2. **Different Vendors**: Create separate vendors
   - "SYSCO" and "US FOODS" are different vendors

3. **Best Practice**: 
   - One vendor per company
   - Multiple aliases for name variations
   - Easier to track total spending per vendor

---

## Getting Help

### Q: Where can I get support?

**A:** Support resources:

1. **Documentation**: Check user guide and admin guide
2. **In-App Help**: Look for tooltips and help icons
3. **System Administrator**: Contact your admin for technical issues
4. **Developer Documentation**: For technical questions

### Q: How do I report a bug?

**A:** Bug reporting process:

1. **Document the Issue**: 
   - What you were doing
   - What happened
   - Expected behavior
   - Screenshots if applicable

2. **Contact**: Report to system administrator or development team

3. **Include**:
   - Error messages
   - Steps to reproduce
   - Browser/device information
   - Date and time

---

**Last Updated**: November 2025  
**Version**: 1.0.0




