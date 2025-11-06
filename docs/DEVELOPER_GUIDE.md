# Developer Guide

## Table of Contents

1. [Local Setup](#local-setup)
2. [Running Tests](#running-tests)
3. [Code Standards](#code-standards)
4. [Contributing Guidelines](#contributing-guidelines)

---

## Local Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js and NPM (for frontend assets)
- Git

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd resturantbackend
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure Database**
   Edit `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=restaurant_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed Database**
   ```bash
   php artisan db:seed --class=ChartOfAccountsSeeder
   php artisan db:seed --class=VendorsSeeder
   ```

7. **Start Development Server**
   ```bash
   php artisan serve
   ```

8. **Build Frontend Assets** (if needed)
   ```bash
   npm run dev
   # or
   npm run build
   ```

### Using XAMPP

If using XAMPP:

1. Place project in `htdocs/resturantbackend`
2. Access via `http://localhost/resturantbackend`
3. Configure Apache to point to `public/` directory
4. Update `.env` with XAMPP MySQL credentials

---

## Running Tests

### Setup Test Environment

1. **Test Database**
   Tests use SQLite in-memory database by default (configured in `phpunit.xml`)

2. **Run All Tests**
   ```bash
   php artisan test
   ```

3. **Run Specific Test Suite**
   ```bash
   php artisan test --testsuite=Unit
   php artisan test --testsuite=Feature
   ```

4. **Run Specific Test Class**
   ```bash
   php artisan test --filter=ProfitLossCalculationTest
   ```

5. **Run Specific Test Method**
   ```bash
   php artisan test --filter=test_calculates_complete_p_and_l
   ```

6. **Run with Coverage**
   ```bash
   php artisan test --coverage
   php artisan test --coverage-html
   ```

7. **Stop on First Failure**
   ```bash
   php artisan test --stop-on-failure
   ```

### Test Data

1. **Seeders**
   ```bash
   php artisan db:seed --class=ChartOfAccountsSeeder
   php artisan db:seed --class=VendorsSeeder
   ```

2. **Factories**
   Use factories in tests:
   ```php
   $expense = ExpenseTransaction::factory()->create();
   $vendor = Vendor::factory()->active()->create();
   ```

### Writing Tests

1. **Feature Tests**
   ```php
   <?php
   
   namespace Tests\Feature;
   
   use Tests\TestCase;
   use Illuminate\Foundation\Testing\RefreshDatabase;
   
   class ExampleTest extends TestCase
   {
       use RefreshDatabase;
       
       public function test_example(): void
       {
           $response = $this->get('/api/endpoint');
           $response->assertStatus(200);
       }
   }
   ```

2. **Unit Tests**
   ```php
   <?php
   
   namespace Tests\Unit;
   
   use Tests\TestCase;
   
   class ExampleTest extends TestCase
   {
       public function test_example(): void
       {
           $this->assertTrue(true);
       }
   }
   ```

---

## Code Standards

### PHP Standards

Follow **PSR-12** coding standards:

1. **Naming Conventions**
   - Classes: `PascalCase` (e.g., `ExpenseTransaction`)
   - Methods: `camelCase` (e.g., `getExpenses`)
   - Variables: `camelCase` (e.g., `$expenseAmount`)
   - Constants: `UPPER_SNAKE_CASE` (e.g., `MAX_AMOUNT`)

2. **File Structure**
   ```php
   <?php
   
   namespace App\Http\Controllers\Api;
   
   use App\Http\Controllers\Controller;
   use App\Models\ExpenseTransaction;
   use Illuminate\Http\Request;
   
   class ExpenseController extends Controller
   {
       public function index(Request $request)
       {
           // Implementation
       }
   }
   ```

3. **Type Hints**
   - Always use type hints for parameters and return types
   ```php
   public function calculateTotal(float $amount1, float $amount2): float
   {
       return $amount1 + $amount2;
   }
   ```

4. **Docblocks**
   - Use docblocks for complex methods
   ```php
   /**
    * Calculate merchant fee for credit card sales
    *
    * @param float $grossAmount
    * @param float $feeRate
    * @return float
    */
   public function calculateMerchantFee(float $grossAmount, float $feeRate = 0.0245): float
   {
       return $grossAmount * $feeRate;
   }
   ```

### Laravel Conventions

1. **Controllers**
   - Use resource controllers where possible
   - Keep controllers thin (delegate to models/services)
   - Use Form Requests for validation

2. **Models**
   - Use Eloquent relationships
   - Use query scopes for reusable queries
   - Keep business logic in models

3. **Routes**
   - Use route groups for organization
   - Use middleware for authentication/authorization
   - Name all routes

4. **Migrations**
   - Use descriptive names
   - Include rollback logic
   - Add indexes for performance

5. **Seeders**
   - Use seeders for reference data
   - Make seeders idempotent
   - Document seeders

### JavaScript Standards

1. **Naming**
   - Variables: `camelCase`
   - Functions: `camelCase`
   - Constants: `UPPER_SNAKE_CASE`

2. **Code Style**
   - Use `const` and `let` (avoid `var`)
   - Use arrow functions where appropriate
   - Use template literals for strings

3. **Example**
   ```javascript
   const expenseAmount = 1000.00;
   const calculateTotal = (amount1, amount2) => amount1 + amount2;
   const message = `Total: $${calculateTotal(expenseAmount, 500)}`;
   ```

### Database Standards

1. **Naming**
   - Tables: `snake_case`, plural (e.g., `expense_transactions`)
   - Columns: `snake_case` (e.g., `transaction_date`)
   - Foreign keys: `{table}_id` (e.g., `store_id`)

2. **Indexes**
   - Index foreign keys
   - Index frequently queried columns
   - Use composite indexes for common queries

3. **Migrations**
   - One migration per table/change
   - Use descriptive names
   - Include indexes in initial migration

---

## Contributing Guidelines

### Git Workflow

1. **Branch Naming**
   - Feature: `feature/expense-import`
   - Bugfix: `bugfix/fix-duplicate-detection`
   - Hotfix: `hotfix/security-patch`

2. **Commit Messages**
   - Use present tense ("Add feature" not "Added feature")
   - Be descriptive
   - Reference issue numbers if applicable

   Examples:
   ```
   Add CSV import functionality
   Fix duplicate detection hash calculation
   Update vendor matching algorithm
   ```

3. **Pull Request Process**
   - Create feature branch
   - Make changes
   - Write/update tests
   - Ensure all tests pass
   - Update documentation
   - Create pull request
   - Request code review

### Code Review Checklist

- [ ] Code follows PSR-12 standards
- [ ] All tests pass
- [ ] New tests added for new features
- [ ] Documentation updated
- [ ] No security vulnerabilities
- [ ] Performance considerations addressed
- [ ] Error handling implemented
- [ ] Logging added where appropriate

### Adding New Features

1. **Plan the Feature**
   - Define requirements
   - Design database schema
   - Plan API endpoints
   - Design UI/UX

2. **Create Database Migration**
   ```bash
   php artisan make:migration create_feature_table
   ```

3. **Create Model**
   ```bash
   php artisan make:model Feature
   ```

4. **Create Controller**
   ```bash
   php artisan make:controller Api/FeatureController
   php artisan make:controller Admin/FeatureViewController
   ```

5. **Create Routes**
   - Add to `routes/web.php`

6. **Create Views**
   - Create Blade templates

7. **Write Tests**
   - Feature tests for API
   - Unit tests for business logic

8. **Update Documentation**
   - API documentation
   - User guide
   - Database schema

### Adding New API Endpoint

1. **Define Endpoint**
   ```php
   Route::get('/api/feature', [FeatureController::class, 'index']);
   ```

2. **Implement Controller Method**
   ```php
   public function index(Request $request)
   {
       // Implementation
   }
   ```

3. **Add Validation**
   - Use Form Requests or inline validation

4. **Add Authorization**
   - Use middleware or policy

5. **Write Tests**
   ```php
   public function test_can_list_features()
   {
       $response = $this->getJson('/api/feature');
       $response->assertStatus(200);
   }
   ```

6. **Document API**
   - Update `API_DOCUMENTATION.md`

### Debugging

1. **Enable Debug Mode**
   ```env
   APP_DEBUG=true
   ```

2. **View Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Use Tinker**
   ```bash
   php artisan tinker
   ```

4. **Database Queries**
   - Enable query logging in `.env`:
   ```env
   DB_LOG_QUERIES=true
   ```

### Common Issues

1. **Migration Errors**
   ```bash
   php artisan migrate:rollback
   php artisan migrate
   ```

2. **Cache Issues**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```

3. **Permission Errors**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

---

## Performance Tips

1. **Database Queries**
   - Use eager loading to prevent N+1 queries
   - Use query scopes for reusable filters
   - Add indexes for frequently queried columns

2. **Caching**
   - Cache expensive queries
   - Cache configuration
   - Cache views when appropriate

3. **Code Optimization**
   - Use collections efficiently
   - Avoid unnecessary loops
   - Use database aggregations when possible

---

## Security Best Practices

1. **Input Validation**
   - Always validate user input
   - Use Form Requests for complex validation
   - Sanitize output

2. **Authentication**
   - Use Laravel's built-in authentication
   - Protect routes with middleware
   - Use CSRF protection

3. **Authorization**
   - Check permissions at multiple levels
   - Use policies for complex authorization
   - Filter data by store access

4. **SQL Injection**
   - Use Eloquent ORM (prevents SQL injection)
   - Use parameterized queries if using raw SQL
   - Never concatenate user input into queries

5. **XSS Prevention**
   - Use Blade's `{{ }}` escaping
   - Use `{!! !!}` only when necessary
   - Sanitize user input

---

## Resources

- [Laravel Documentation](https://laravel.com/docs)
- [PHP The Right Way](https://phptherightway.com/)
- [PSR Standards](https://www.php-fig.org/psr/)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)

---

**Last Updated**: November 2025  
**Version**: 1.0.0




