<?php

namespace Prasso\Church\Database\Seeders;

use Illuminate\Database\Seeder;
use Prasso\Church\Models\PastoralVisit;
use Prasso\Church\Models\PrayerRequest;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Family;

class PastoralCareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Ensure we have some staff members
        $staffMembers = Member::where('is_staff', true)->get();
        
        if ($staffMembers->isEmpty()) {
            $staffMembers = Member::factory()
                ->count(3)
                ->create(['is_staff' => true]);
        }

        // Create prayer requests
        PrayerRequest::factory()
            ->count(20)
            ->create();

        // Create pastoral visits
        PastoralVisit::factory()
            ->count(30)
            ->create();

        // Create some confidential visits
        PastoralVisit::factory()
            ->confidential()
            ->count(5)
            ->create();

        $this->command->info('Pastoral care module seeded successfully!');
    }
}
