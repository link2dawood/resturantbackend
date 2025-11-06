# Testing Guide for Restaurant Backend System

## Overview

This document outlines the comprehensive testing strategy for the Restaurant Backend Phase 2 features: Expense Management and P&L Module.

## Test Structure

### Test Categories

1. **Unit Tests** (`tests/Unit/`)
   - Model business logic
   - Utility functions
   - Calculation methods

2. **Feature Tests** (`tests/Feature/`)
   - API endpoints
   - Integration flows
   - Permission checks
   - End-to-end scenarios

## Key Test Files

### Created Test Files

1. **ProfitLossCalculationTest.php**
   - Tests P&L calculation accuracy
   - Revenue, COGS, and expense calculations
   - Store access filtering
   - Summary generation

2. **PermissionMiddlewareTest.php**
   - Role-based access control
   - Manager permissions
   - Owner permissions
   - Admin full access

3. **ExpenseImportTest.php**
   - CSV upload functionality
   - Duplicate detection
   - Vendor matching
   - Auto-categorization

4. **BankReconciliationTest.php**
   - Bank transaction matching
   - Merchant fee calculation
   - Expected deposit creation

### Factory Files Created

All factories are set up for generating test data:

- `DailyReportFactory.php` - Daily sales reports
- `ExpenseTransactionFactory.php` - Expense transactions
- `BankAccountFactory.php` - Bank accounts
- `BankTransactionFactory.php` - Bank transactions
- `ThirdPartyStatementFactory.php` - Third-party platform statements

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

### Run Specific Test Class
```bash
php artisan test --filter=ProfitLossCalculationTest
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run and Stop on First Failure
```bash
php artisan test --stop-on-failure
```

## Test Data Setup

### Seeders Required

Before running tests, ensure seeders are run:

```bash
php artisan db:seed --class=ChartOfAccountsSeeder
php artisan db:seed --class=VendorsSeeder
```

### Test Database

Tests use SQLite in-memory database by default. Configuration is in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

## Test Coverage Goals

### Target Coverage: 85%+

**Current Coverage Breakdown:**
- API Endpoints: 60%+ (in progress)
- Business Logic: 80%+ (in progress)
- Utility Functions: 90%+ (in progress)
- Frontend Components: Manual testing

### Critical Paths to Test

1. **P&L Calculation Engine**
   - Revenue aggregation from daily reports
   - COGS calculations
   - Operating expenses
   - Margin calculations
   - Multi-store scenarios

2. **Permission System**
   - Manager access restrictions
   - Owner store access
   - Admin full access
   - Store-level data filtering

3. **Bank Reconciliation**
   - Transaction matching algorithms
   - Merchant fee auto-calculation
   - Expected deposit creation
   - Duplicate detection

4. **Import System**
   - CSV parsing
   - Vendor matching (exact, fuzzy, alias)
   - Duplicate detection
   - Auto-categorization

## Manual Testing Checklist

### Chart of Accounts (COA)

- [ ] Create new account
- [ ] Edit account
- [ ] Assign to stores
- [ ] Deactivate account
- [ ] Search and filter
- [ ] Permission checks

### Vendor Management

- [ ] Create vendor
- [ ] Add aliases
- [ ] Assign default COA
- [ ] Store assignment
- [ ] Fuzzy matching test
- [ ] Bulk operations

### Expense Ledger

- [ ] Sync cash expenses from daily reports
- [ ] Manual expense entry
- [ ] Filter and search
- [ ] Export to CSV
- [ ] Categorize expense
- [ ] View details

### Review Queue

- [ ] View pending reviews
- [ ] Resolve transaction
- [ ] Create mapping rule
- [ ] Bulk categorization
- [ ] Filter by reason

### Bank Reconciliation

- [ ] Add bank account
- [ ] Upload statement
- [ ] Auto-match transactions
- [ ] Manual match
- [ ] Mark as reviewed
- [ ] Reconciliation summary

### P&L Reports

- [ ] Generate P&L
- [ ] Date range filtering
- [ ] Store selection
- [ ] Comparison period
- [ ] Drill-down details
- [ ] Save snapshot
- [ ] Export to PDF/Excel
- [ ] Multi-store comparison

### Third-Party Integration

- [ ] Upload Grubhub statement
- [ ] Upload UberEats CSV
- [ ] Upload DoorDash CSV
- [ ] Fee parsing
- [ ] Expected deposit creation

## Performance Testing

### Large Dataset Tests

```bash
# Create 1000+ expense transactions
php artisan tinker
>>> ExpenseTransaction::factory()->count(1000)->create();

# Generate P&L with 1 year of data
# Test should complete in < 5 seconds
```

### Load Testing with JMeter

1. Set up JMeter test plan
2. Test concurrent user scenarios
3. Monitor response times
4. Check database query performance

## Security Testing

### SQL Injection
- Test all user inputs
- Verify parameterized queries
- Check raw query usage

### XSS Prevention
- Test form inputs
- Verify output escaping
- Check file upload handling

### CSRF Protection
- Verify CSRF tokens on forms
- Test POST/PUT/DELETE endpoints
- Check middleware application

### Permission Bypass
- Attempt unauthorized access
- Try store access violations
- Test role elevation

## Integration Testing

### CSV Import Flow
1. Upload sample CSV
2. Verify format detection
3. Check preview
4. Complete import
5. Verify transactions
6. Check review queue

### Bank Reconciliation Flow
1. Create bank account
2. Upload statement
3. Auto-match transactions
4. Review unmatched items
5. Complete reconciliation
6. Generate report

### P&L Generation Flow
1. Create transactions
2. Select date range
3. Generate P&L
4. Verify calculations
5. Drill-down details
6. Save snapshot

## Continuous Integration

### GitHub Actions Setup

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      
      - name: Install Dependencies
        run: composer install
      
      - name: Run Tests
        run: php artisan test --coverage
```

## Test Data Management

### Factory States

Factories support states for common scenarios:

```php
// Create reconciled transaction
ExpenseTransaction::factory()->reconciled()->create();

// Create transaction needing review
ExpenseTransaction::factory()->needsReview()->create();

// Create active vendor with COA
Vendor::factory()->active()->withCoa()->create();
```

### Test Fixtures

Reusable test fixtures in `tests/Fixtures/`:

- Sample CSV files
- Test PDF statements
- Expected outputs

## Troubleshooting

### Common Issues

1. **Seeder not found**
   - Run `php artisan db:seed` first
   - Check seeder file exists

2. **Factory not found**
   - Run `composer dump-autoload`
   - Check factory class name

3. **Observer not firing**
   - Verify AppServiceProvider registration
   - Check model events

4. **Permission failures**
   - Verify user role setup
   - Check middleware registration
   - Verify store assignments

## Next Steps

1. Complete missing test implementations
2. Achieve 85%+ code coverage
3. Add performance benchmarks
4. Set up CI/CD pipeline
5. Document edge cases
6. Create test report template

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Faker Documentation](https://github.com/FakerPHP/Faker)




