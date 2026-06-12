<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ReferenceFood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferenceFood>
 */
final class ReferenceFoodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $description = ucfirst(fake()->unique()->words(2, true));

        return [
            'source' => 'usda',
            'external_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'data_type' => 'foundation',
            'description' => $description,
            'match_name' => ReferenceFood::normalizeName($description),
            'food_category' => fake()->randomElement(['Vegetables', 'Dairy and Egg Products', 'Legumes and Legume Products', 'Nuts and Seeds']),
            'calories_per_100g' => fake()->randomFloat(2, 10, 600),
            'protein_per_100g' => fake()->randomFloat(2, 0, 30),
            'carbs_per_100g' => fake()->randomFloat(2, 0, 80),
            'fat_per_100g' => fake()->randomFloat(2, 0, 60),
            'nutrients' => [],
            'embedding' => null,
            'release' => 'USDA Foundation 2026-04-30',
            'publication_date' => now(),
        ];
    }
}
