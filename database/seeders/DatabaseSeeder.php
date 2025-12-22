<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users - 1 user per role
        
        // 1. Admin User - Can create stores and owners
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);
        $this->command->info('Created Admin user: admin@admin.com / password');

        // 2. Franchisor User - Special owner who can see all stores, owners, and managers
        $franchisor = User::create([
            'name' => 'Franchisor',
            'email' => 'franchisor@franchisor.com',
            'password' => Hash::make('password'),
            'role' => UserRole::OWNER,
            'state' => 'PA', // Required field for owners
            'email_verified_at' => now(),
        ]);
        $this->command->info('Created Franchisor user: franchisor@franchisor.com / password');

        // 3. Owner/Franchisee User - Can see their stores and managers
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@owner.com',
            'password' => Hash::make('password'),
            'role' => UserRole::OWNER,
            'state' => 'PA', // Required field for owners
            'email_verified_at' => now(),
        ]);
        $this->command->info('Created Owner (Franchisee) user: owner@owner.com / password');

        // 4. Manager User - Can only see their assigned stores
        $manager = User::create([
            'name' => 'Manager',
            'email' => 'manager@manager.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'email_verified_at' => now(),
        ]);
        $this->command->info('Created Manager user: manager@manager.com / password');

        // Seed in order (respecting dependencies)
        $this->call([
            StateSeeder::class,
            TransactionTypeTableSeeder::class,
            ChartOfAccountsSeeder::class,
            PermissionSeeder::class,
            RevenueIncomeTypeSeeder::class,
            StoresSeeder::class,
            VendorsSeeder::class,
        ]);
    }
}
