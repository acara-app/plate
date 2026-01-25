<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GlucoseUnit;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class UserProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'age' => fake()->numberBetween(18, 80),
            'height' => fake()->numberBetween(150, 200),
            'weight' => fake()->numberBetween(50, 120),
            'sex' => fake()->randomElement(Sex::cases())->value,
            'goal_choice' => fake()->randomElement(GoalChoice::cases())->value,
            'units_preference' => fake()->randomElement(GlucoseUnit::cases())->value,
        ];
    }
}
