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
        $this->call(TransactionTypeTableSeeder::class);
        $this->call(StateSeeder::class);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'role' => 'admin'
        ]);
        User::factory()->create([
            'name' => 'Owner',
            'email' => 'owner@owner.com',
            'role' => 'owner'
        ]);
        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@manager.com',
            'role' => 'manager'
        ]);
    }
}
