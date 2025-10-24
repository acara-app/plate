<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserProfile>
 */
final class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'age' => fake()->numberBetween(18, 80),
            'height' => fake()->randomFloat(2, 150, 200),
            'weight' => fake()->randomFloat(2, 50, 120),
            'sex' => fake()->randomElement([\App\Enums\Sex::Male, \App\Enums\Sex::Female]),
            'goal_id' => \App\Models\Goal::factory(),
            'target_weight' => fake()->randomFloat(2, 50, 100),
            'additional_goals' => fake()->optional()->sentence(),
            'lifestyle_id' => \App\Models\Lifestyle::factory(),
            'onboarding_completed' => false,
            'onboarding_completed_at' => null,
        ];
    }
}
