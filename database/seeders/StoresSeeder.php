<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class StoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the admin user (created first in DatabaseSeeder)
        $admin = User::where('email', 'admin@admin.com')->first();
        $owner = User::where('email', 'owner@owner.com')->first();
        
        // Use admin as fallback if owner doesn't exist
        $createdBy = $owner ?? $admin;

        $stores = [
            [
                'store_info' => 'Main Street Restaurant',
                'contact_name' => 'John Smith',
                'phone' => '(555) 123-4567',
                'address' => '123 Main Street',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19101',
                'sales_tax_rate' => 8.25,
                'medicare_tax_rate' => 1.45,
                'created_by' => $createdBy->id,
            ],
            [
                'store_info' => 'Downtown Cafe',
                'contact_name' => 'Jane Doe',
                'phone' => '(555) 234-5678',
                'address' => '456 Market Street',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19102',
                'sales_tax_rate' => 8.25,
                'medicare_tax_rate' => 1.45,
                'created_by' => $createdBy->id,
            ],
            [
                'store_info' => 'Riverside Bistro',
                'contact_name' => 'Robert Johnson',
                'phone' => '(555) 345-6789',
                'address' => '789 River Road',
                'city' => 'Philadelphia',
                'state' => 'PA',
                'zip' => '19103',
                'sales_tax_rate' => 8.25,
                'medicare_tax_rate' => 1.45,
                'created_by' => $createdBy->id,
            ],
        ];

        foreach ($stores as $storeData) {
            Store::create($storeData);
        }
    }
}
