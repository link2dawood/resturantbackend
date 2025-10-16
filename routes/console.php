<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('create:test-users', function () {
    $this->info('Creating test users...');

    // Create admin user
    $admin = User::firstOrCreate(
        ['email' => 'admin@admin.com'],
        [
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => bcrypt('12345678'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]
    );
    $this->info('Admin user created/found: '.$admin->email);

    // Create owner user
    $owner = User::firstOrCreate(
        ['email' => 'owner@owner.com'],
        [
            'name' => 'Owner User',
            'email' => 'owner@owner.com',
            'password' => bcrypt('12345678'),
            'role' => UserRole::OWNER,
            'email_verified_at' => now(),
        ]
    );
    $this->info('Owner user created/found: '.$owner->email);

    // Create manager user
    $manager = User::firstOrCreate(
        ['email' => 'manager@manager.com'],
        [
            'name' => 'Manager User',
            'email' => 'manager@manager.com',
            'password' => bcrypt('12345678'),
            'role' => UserRole::MANAGER,
            'email_verified_at' => now(),
        ]
    );
    $this->info('Manager user created/found: '.$manager->email);

    $this->info('All test users created successfully!');
})->purpose('Create test users for testing');

Artisan::command('create:test-data', function () {
    $this->info('Creating test data...');

    // Get users
    $owner = User::where('email', 'owner@owner.com')->first();
    $manager = User::where('email', 'manager@manager.com')->first();

    if (! $owner || ! $manager) {
        $this->error('Test users not found. Run create:test-users first.');

        return;
    }

    // Create a test store
    $store = \App\Models\Store::firstOrCreate(
        ['store_info' => 'Test Restaurant #1'],
        [
            'store_info' => 'Test Restaurant #1',
            'contact_name' => 'John Doe',
            'phone' => '555-0123',
            'address' => '123 Main St',
            'city' => 'Test City',
            'state' => 'TX',
            'zip' => '12345',
            'sales_tax_rate' => 8.25,
            'medicare_tax_rate' => 1.45,
            'created_by' => $owner->id,
        ]
    );
    $this->info('Test store created/found: '.$store->store_info);

    // Assign manager to store
    if (! $store->managers()->where('users.id', $manager->id)->exists()) {
        $store->managers()->attach($manager->id);
        $this->info('Manager assigned to store');
    } else {
        $this->info('Manager already assigned to store');
    }

    // Create transaction types if they don't exist
    $transactionTypes = [
        'Food Cost',
        'Rent',
        'Accounting',
        'Taxes',
        'Other',
    ];

    foreach ($transactionTypes as $type) {
        \App\Models\TransactionType::firstOrCreate(
            ['name' => $type],
            ['name' => $type]
        );
    }
    $this->info('Transaction types created');

    // Create revenue income types if they don't exist
    $revenueTypes = [
        ['name' => 'Food Sales', 'sort_order' => 1, 'is_active' => 1],
        ['name' => 'Beverage Sales', 'sort_order' => 2, 'is_active' => 1],
        ['name' => 'Other Income', 'sort_order' => 3, 'is_active' => 1],
    ];

    foreach ($revenueTypes as $type) {
        \App\Models\RevenueIncomeType::firstOrCreate(
            ['name' => $type['name']],
            $type
        );
    }
    $this->info('Revenue income types created');

    $this->info('Test data created successfully!');
})->purpose('Create test store and assign manager');
