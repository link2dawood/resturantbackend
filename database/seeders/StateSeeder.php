<?php

namespace Database\Seeders;

use App\Helpers\USStates;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = USStates::getStates();
        
        foreach ($states as $code => $name) {
            State::updateOrCreate(
                ['code' => $code],
                ['name' => $name]
            );
        }
        
        $this->command->info('All ' . count($states) . ' US states have been seeded successfully.');
    }
}
