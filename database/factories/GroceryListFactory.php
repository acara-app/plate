<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GroceryListStatus;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroceryList>
 */
final class GroceryListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var list<string> $words */
        $words = fake()->words(3);

        return [
            'user_id' => User::factory(),
            'meal_plan_id' => MealPlan::factory(),
            'name' => 'Grocery List for '.implode(' ', $words),
            'status' => GroceryListStatus::Active,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function generating(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GroceryListStatus::Generating,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GroceryListStatus::Completed,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GroceryListStatus::Failed,
        ]);
    }
}
