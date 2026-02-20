<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MealPlanType;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
final class MealPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(MealPlanType::cases());
        $durationDays = match ($type) {
            MealPlanType::Weekly => 7,
            MealPlanType::Monthly => 30,
            MealPlanType::Custom => fake()->numberBetween(3, 14),
            default => 7,
        };

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'name' => fake()->optional(0.7)->words(3, true),
            'description' => fake()->optional(0.5)->sentence(),
            'duration_days' => $durationDays,
            'target_daily_calories' => fake()->randomFloat(2, 1500, 3000),
            'macronutrient_ratios' => [
                'protein' => fake()->numberBetween(20, 40),
                'carbs' => fake()->numberBetween(30, 50),
                'fat' => fake()->numberBetween(20, 35),
            ],
            'metadata' => [
                'bmi' => fake()->randomFloat(2, 18, 35),
                'bmr' => fake()->randomFloat(2, 1200, 2200),
                'tdee' => fake()->randomFloat(2, 1500, 3000),
            ],
        ];
    }

    /**
     * Indicate that the meal plan is for a weekly duration.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealPlanType::Weekly,
            'duration_days' => 7,
        ]);
    }

    /**
     * Indicate that the meal plan is for a monthly duration.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealPlanType::Monthly,
            'duration_days' => 30,
        ]);
    }

    /**
     * Indicate that the meal plan is custom duration.
     */
    public function custom(int $days = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealPlanType::Custom,
            'duration_days' => $days,
        ]);
    }
}
