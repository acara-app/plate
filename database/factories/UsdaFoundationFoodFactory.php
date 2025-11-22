<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsdaFoundationFood>
 */
final class UsdaFoundationFoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->words(5, true),
            'food_category' => fake()->randomElement(['Vegetables', 'Fruits', 'Proteins', 'Grains', 'Dairy', null]),
            'publication_date' => fake()->date(),
            'nutrients' => [],
        ];
    }
}
