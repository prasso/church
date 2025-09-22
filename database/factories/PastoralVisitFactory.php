<?php

namespace Prasso\Church\Database\Factories;

use Prasso\Church\Models\PastoralVisit;
use Prasso\Church\Models\Member;
use Prasso\Church\Models\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class PastoralVisitFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PastoralVisit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $member = Member::inRandomOrder()->first() ?? Member::factory()->create();
        $family = Family::inRandomOrder()->first() ?? Family::factory()->create();
        $staff = Member::where('is_staff', true)->inRandomOrder()->first() ?? 
                Member::factory()->create(['is_staff' => true]);
        
        $statuses = ['scheduled', 'in_progress', 'completed', 'canceled'];
        $locationTypes = ['home', 'hospital', 'church', 'other'];
        $spiritualNeeds = [
            'prayer', 'counseling', 'communion', 'bible_study', 
            'spiritual_guidance', 'hospital_visit', 'bereavement'
        ];
        
        $scheduledFor = $this->faker->dateTimeBetween('now', '+30 days');
        $startedAt = $this->faker->optional(0.7, null)
            ->dateTimeBetween($scheduledFor, '+1 hour');
        $endedAt = $startedAt ? 
            $this->faker->dateTimeBetween($startedAt, '+2 hours') : null;
        $duration = $endedAt ? $endedAt->diffInMinutes($startedAt) : null;
        
        return [
            'title' => $this->faker->sentence(3),
            'purpose' => $this->faker->paragraph,
            'scheduled_for' => $scheduledFor,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'location_type' => $this->faker->randomElement($locationTypes),
            'location_details' => $this->faker->optional()->address,
            'member_id' => $member->id,
            'family_id' => $family->id,
            'assigned_to' => $staff->id,
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->optional()->paragraph,
            'follow_up_actions' => $this->faker->optional()->paragraph,
            'follow_up_date' => $this->faker->optional(0.3)->dateTimeBetween('now', '+14 days'),
            'spiritual_needs' => $this->faker->randomElements(
                $spiritualNeeds, 
                $this->faker->numberBetween(1, 3)
            ),
            'outcome_summary' => $this->faker->optional()->paragraph,
            'is_confidential' => $this->faker->boolean(20),
        ];
    }

    /**
     * Indicate that the visit is scheduled.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function scheduled()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'scheduled',
                'started_at' => null,
                'ended_at' => null,
                'duration_minutes' => null,
            ];
        });
    }

    /**
     * Indicate that the visit is in progress.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inProgress()
    {
        return $this->state(function (array $attributes) {
            $startedAt = now()->subMinutes(30);
            return [
                'status' => 'in_progress',
                'started_at' => $startedAt,
                'ended_at' => null,
                'duration_minutes' => null,
            ];
        });
    }

    /**
     * Indicate that the visit is completed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function completed()
    {
        return $this->state(function (array $attributes) {
            $startedAt = now()->subHours(2);
            $endedAt = now()->subHours(1);
            return [
                'status' => 'completed',
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_minutes' => $endedAt->diffInMinutes($startedAt),
            ];
        });
    }

    /**
     * Indicate that the visit is confidential.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function confidential()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_confidential' => true,
            ];
        });
    }
}
