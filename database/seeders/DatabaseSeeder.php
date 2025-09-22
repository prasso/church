<?php

namespace Prasso\Church\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FinancialSeeder::class,
            // Add other seeders here
        ]);
    }
}
