# Admin Guide: System Configuration & Management

## Table of Contents

1. [System Configuration](#system-configuration)
2. [Managing Chart of Accounts](#managing-chart-of-accounts)
3. [User Permissions Setup](#user-permissions-setup)
4. [Import Troubleshooting](#import-troubleshooting)
5. [Best Practices](#best-practices)

---

## System Configuration

### Merchant Fee Rates

Configure merchant processing fee rates:

1. Navigate to **Settings → Merchant Fees**
2. Set default fee rate (default: 2.45%)
3. Configure processor-specific rates if needed
4. Save settings

**Note**: Fee rate changes only affect future transactions.

### Third-Party Platform Rates

Configure fee rates for delivery platforms:

1. Go to **Settings → Third-Party Platforms**
2. Set rates for:
   - **Marketing Fees**: Typically 15% (Grubhub), varies by platform
   - **Delivery Fees**: Typically 10%, varies by platform
   - **Processing Fees**: Fixed per transaction

### System Accounts

System accounts are pre-configured and cannot be deleted:
- Revenue accounts
- COGS accounts
- Operating expense accounts

These can be deactivated but should remain active for proper reporting.

---

## Managing Chart of Accounts

### Creating Custom Accounts

When creating custom accounts:

1. **Use Consistent Naming**:
   - Use clear, descriptive names
   - Follow existing conventions
   - Include account type in name if needed

2. **Account Codes**:
   - Use 4-digit codes
   - Reserve ranges:
     - 1000-1999: Assets
     - 2000-2999: Liabilities
     - 3000-3999: Equity
     - 4000-4999: Revenue
     - 5000-5999: COGS
     - 6000-7999: Operating Expenses
     - 8000-8999: Other Income/Expenses

3. **Hierarchical Structure**:
   - Use parent accounts for grouping
   - Keep hierarchy 2-3 levels deep
   - Example:
     - 6000: Operating Expenses (Parent)
       - 6100: Payroll
       - 6200: Rent & Utilities

### Store-Specific Accounts

Create store-specific accounts when:
- Different stores have different expense categories
- Store-specific reporting is needed
- Tax categories vary by location

### Deactivating Accounts

Instead of deleting accounts:
1. Deactivate the account
2. Historical data remains accessible
3. Account won't appear in new transactions
4. Can be reactivated if needed

---

## User Permissions Setup

### Permission Matrix

#### Manager
- **View Only**: COA, Vendors, Expenses (own store), Reports (own store)
- **Create**: Manual expenses, Daily reports
- **Restricted**: Cannot access imports, reconciliation, review queue

#### Owner
- **View**: All own store data
- **Create/Update**: Vendors, Expenses, Bank accounts
- **Manage**: Review queue, Reconciliation
- **Restricted**: Cannot access all stores, system COA management

#### Admin/Super Admin
- **Full Access**: All stores, all features
- **System Management**: COA, users, configuration

### Assigning Store Access

For Managers:
1. Navigate to **Users → Managers**
2. Select manager
3. Click **Assign Stores**
4. Select stores from list
5. Save

For Owners:
- Store access is automatically set based on `created_by` field
- Owners can only access stores they created

### Permission Troubleshooting

**Issue**: User can't see expected data
- Check store assignments
- Verify user role
- Check permission matrix

**Issue**: User can access unauthorized stores
- Review store access logic
- Check user role assignments
- Verify middleware is applied

---

## Import Troubleshooting

### Common Import Errors

#### Error: "File format not recognized"

**Solution**:
1. Verify file is CSV format
2. Check file encoding (should be UTF-8)
3. Ensure proper column headers
4. Try manual column mapping

#### Error: "Duplicate file detected"

**Solution**:
1. File has been imported before
2. Check import history
3. Modify file slightly if re-import needed
4. Or use different file name

#### Error: "No transactions found"

**Solution**:
1. Check file has data rows (not just headers)
2. Verify date format matches expected format
3. Check column mapping
4. Ensure amount column contains numeric values

### CSV Format Issues

#### Date Format Problems

**Supported Formats**:
- YYYY-MM-DD (preferred)
- MM/DD/YYYY
- DD/MM/YYYY (if configured)

**Solution**: Use date format detection in preview, or manually specify format.

#### Amount Format Problems

**Issued**:
- Currency symbols ($, €, etc.)
- Thousands separators (1,000.00)
- Negative amounts

**Solution**:
- System handles common formats
- Clean amounts before import if issues persist
- Check decimal separator (use . not ,)

#### Encoding Issues

**Problem**: Special characters appear as gibberish

**Solution**:
1. Save CSV as UTF-8 encoding
2. Avoid special characters in Excel
3. Use plain text editor if needed

### Vendor Matching Issues

#### Low Match Rate

**Causes**:
- Vendor names don't match exactly
- Missing vendor aliases
- Vendor not in system

**Solutions**:
1. Add vendor aliases for common variations
2. Review unmatched transactions in review queue
3. Create missing vendors
4. Use fuzzy matching suggestions

#### False Positives

**Problem**: Wrong vendor matched

**Solution**:
1. Review match in review queue
2. Correct the vendor assignment
3. Create mapping rule to prevent future errors
4. Add more specific vendor aliases

### Performance Issues

#### Large File Import

**Problem**: Import times out or is slow

**Solutions**:
1. Split large files into smaller batches
2. Import during off-peak hours
3. Increase PHP memory limit if needed
4. Check server resources

#### Memory Errors

**Solution**:
1. Increase `memory_limit` in php.ini
2. Process files in chunks
3. Use command-line import for very large files

---

## Best Practices

### Data Entry

1. **Enter Expenses Promptly**
   - Daily or weekly entry
   - Prevents backlog in review queue
   - More accurate financial reports

2. **Use Consistent Naming**
   - Vendor names: Use official company name
   - Descriptions: Be specific and clear
   - Reference numbers: Use consistent format

3. **Verify Before Import**
   - Check CSV preview before importing
   - Verify column mapping
   - Review sample transactions

### Vendor Management

1. **Complete Vendor Profiles**
   - Include all contact information
   - Add vendor identifiers (tax ID, etc.)
   - Assign default COA

2. **Maintain Aliases**
   - Add common name variations
   - Include statement variations
   - Review and update regularly

3. **Regular Cleanup**
   - Merge duplicate vendors
   - Deactivate inactive vendors
   - Update vendor information

### Bank Reconciliation

1. **Regular Reconciliation**
   - Monthly minimum
   - Weekly for high-volume stores
   - Daily for critical accounts

2. **Document Exceptions**
   - Add notes for unmatched items
   - Explain bank fees and interest
   - Track recurring exceptions

3. **Review Patterns**
   - Identify common unmatched items
   - Create mapping rules
   - Improve auto-matching

### Reporting

1. **Consistent Date Ranges**
   - Use calendar months for monthly reports
   - Compare like periods (month-to-month, year-to-year)
   - Use consistent cut-off dates

2. **Review Before Sharing**
   - Verify calculations
   - Check for anomalies
   - Review drill-down details

3. **Save Snapshots**
   - Save monthly P&L reports
   - Create comparison reports
   - Maintain historical records

### System Maintenance

1. **Regular Backups**
   - Database backups daily
   - Export critical reports
   - Store backups off-site

2. **Monitor Performance**
   - Check import times
   - Monitor review queue size
   - Review error logs

3. **User Training**
   - Train new users
   - Update documentation
   - Share best practices

### Security

1. **Access Control**
   - Assign appropriate roles
   - Review permissions regularly
   - Remove unused accounts

2. **Data Protection**
   - Secure file uploads
   - Validate all inputs
   - Monitor access logs

3. **Audit Trail**
   - Review audit logs
   - Track changes
   - Investigate anomalies

---

## Troubleshooting Guide

### Issue: Expenses not showing in P&L

**Check**:
1. Transaction dates are within report date range
2. COA is assigned to transactions
3. Transactions are not marked as needs_review
4. Store is selected correctly

### Issue: Bank reconciliation not matching

**Check**:
1. Merchant fees are calculated correctly
2. Date ranges match
3. Amounts are correct
4. Expected deposits are created

### Issue: Import creates duplicates

**Check**:
1. File hasn't been imported before
2. Duplicate detection is working
3. Transaction hashes are unique
4. Review duplicate detection logic

### Issue: Review queue growing

**Check**:
1. Vendor aliases are complete
2. Default COAs are assigned
3. Mapping rules are created
4. Fuzzy matching is working

---

## Support Contacts

### Technical Support
- System Administrator
- IT Department

### Data Issues
- Finance Department
- Account Manager

### Training
- System Documentation
- Video Tutorials
- User Guide

---

**Last Updated**: November 2025  
**Version**: 1.0.0




