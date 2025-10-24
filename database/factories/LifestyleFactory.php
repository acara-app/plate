<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lifestyle>
 */
final class LifestyleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activityLevels = [
            ['name' => 'Sedentary', 'multiplier' => 1.2],
            ['name' => 'Lightly Active', 'multiplier' => 1.375],
            ['name' => 'Moderately Active', 'multiplier' => 1.55],
            ['name' => 'Very Active', 'multiplier' => 1.725],
            ['name' => 'Extremely Active', 'multiplier' => 1.9],
        ];

        /** @var array<string, mixed> $activity */
        $activity = fake()->randomElement($activityLevels);

        return [
            'name' => $activity['name'],
            'activity_level' => $activity['name'],
            'sleep_hours' => fake()->randomElement(['4-5 hours', '5-6 hours', '6-7 hours', '7-8 hours', '8-9 hours', '9+ hours']),
            'occupation' => fake()->randomElement(['Desk Job', 'Physical Labor', 'Mixed Activity', 'Athlete', 'Healthcare Worker', 'Retail', 'Student']),
            'description' => fake()->sentence(),
            'activity_multiplier' => $activity['multiplier'],
        ];
    }
}
