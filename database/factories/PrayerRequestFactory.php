<?php

namespace Prasso\Church\Database\Factories;

use Prasso\Church\Models\Member;
use Prasso\Church\Models\PrayerRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrayerRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PrayerRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'member_id' => Member::factory(),
            'requested_by' => fn (array $attributes) => $attributes['member_id'],
            'is_anonymous' => $this->faker->boolean(20), // 20% chance of being anonymous
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
            'status' => $this->faker->randomElement(['active', 'answered', 'inactive']),
            'prayer_count' => $this->faker->numberBetween(0, 100),
            'answer' => $this->faker->optional(0.3)->paragraph, // 30% chance of having an answer
            'answered_at' => $this->faker->optional(0.3)->dateTime, // 30% chance of being answered
            'metadata' => null,
        ];
    }

    /**
     * Indicate that the prayer request is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
            ];
        });
    }

    /**
     * Indicate that the prayer request is answered.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function answered()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'answered',
                'answered_at' => now(),
                'answer' => 'This prayer has been answered. ' . $this->faker->sentence,
            ];
        });
    }

    /**
     * Indicate that the prayer request is public.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function public()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => true,
            ];
        });
    }

    /**
     * Indicate that the prayer request is private.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function private()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_public' => false,
            ];
        });
    }

    /**
     * Indicate that the prayer request is anonymous.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function anonymous()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_anonymous' => true,
            ];
        });
    }
}
