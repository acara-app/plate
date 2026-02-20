<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meal>
 */
final class MealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $calories = fake()->randomFloat(2, 200, 800);
        $proteinGrams = fake()->randomFloat(2, 10, 60);
        $carbsGrams = fake()->randomFloat(2, 20, 100);
        $fatGrams = fake()->randomFloat(2, 5, 40);

        return [
            'meal_plan_id' => MealPlan::factory(),
            'day_number' => fake()->numberBetween(1, 7),
            'type' => fake()->randomElement(MealType::cases()),
            'name' => fake()->words(3, true),
            'description' => fake()->optional(0.7)->sentence(),
            'preparation_instructions' => fake()->optional(0.8)->paragraphs(2, true),
            'ingredients' => fake()->optional(0.8)->paragraphs(1, true),
            'portion_size' => fake()->optional(0.7)->randomElement(['1 cup', '200g', '1 serving', '2 pieces', '150ml']),
            'calories' => $calories,
            'protein_grams' => $proteinGrams,
            'carbs_grams' => $carbsGrams,
            'fat_grams' => $fatGrams,
            'preparation_time_minutes' => fake()->optional(0.8)->numberBetween(5, 60),
            'metadata' => fake()->optional(0.3)->randomElements([
                'fiber_grams' => fake()->randomFloat(2, 2, 15),
                'sugar_grams' => fake()->randomFloat(2, 1, 30),
                'sodium_mg' => fake()->numberBetween(100, 1500),
            ]),
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the meal is breakfast.
     */
    public function breakfast(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealType::Breakfast,
            'name' => fake()->randomElement([
                'Oatmeal with Berries',
                'Greek Yogurt Parfait',
                'Scrambled Eggs with Toast',
                'Avocado Toast',
                'Protein Smoothie Bowl',
            ]),
            'calories' => fake()->randomFloat(2, 300, 600),
            'sort_order' => 0,
        ]);
    }

    /**
     * Indicate that the meal is lunch.
     */
    public function lunch(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealType::Lunch,
            'name' => fake()->randomElement([
                'Grilled Chicken Salad',
                'Quinoa Buddha Bowl',
                'Turkey Sandwich',
                'Veggie Wrap',
                'Salmon with Rice',
            ]),
            'calories' => fake()->randomFloat(2, 400, 700),
            'sort_order' => 1,
        ]);
    }

    /**
     * Indicate that the meal is dinner.
     */
    public function dinner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealType::Dinner,
            'name' => fake()->randomElement([
                'Baked Salmon with Vegetables',
                'Chicken Stir Fry',
                'Beef and Broccoli',
                'Pasta with Marinara',
                'Grilled Steak with Sweet Potato',
            ]),
            'calories' => fake()->randomFloat(2, 500, 800),
            'sort_order' => 2,
        ]);
    }

    /**
     * Indicate that the meal is a snack.
     */
    public function snack(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MealType::Snack,
            'name' => fake()->randomElement([
                'Apple with Almond Butter',
                'Protein Bar',
                'Mixed Nuts',
                'Greek Yogurt',
                'Carrots with Hummus',
            ]),
            'calories' => fake()->randomFloat(2, 100, 300),
            'sort_order' => 3,
        ]);
    }

    /**
     * Indicate that the meal is for a specific day.
     */
    public function forDay(int $dayNumber): static
    {
        return $this->state(fn (array $attributes): array => [
            'day_number' => $dayNumber,
        ]);
    }

    /**
     * Indicate that the meal is high protein.
     */
    public function highProtein(): static
    {
        return $this->state(fn (array $attributes): array => [
            'protein_grams' => fake()->randomFloat(2, 40, 80),
        ]);
    }

    /**
     * Indicate that the meal is low carb.
     */
    public function lowCarb(): static
    {
        return $this->state(fn (array $attributes): array => [
            'carbs_grams' => fake()->randomFloat(2, 5, 25),
        ]);
    }
}
