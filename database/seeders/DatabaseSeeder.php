<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users first (needed for stores and other relationships)
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'role' => 'admin',
        ]);
        
        $owner = User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@owner.com',
            'role' => 'owner',
        ]);
        
        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@manager.com',
            'role' => 'manager',
        ]);

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
