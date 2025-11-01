<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DietaryPreference>
 */
final class DietaryPreferenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['allergy', 'intolerance', 'pattern', 'dislike'];
        /** @var string $type */
        $type = fake()->randomElement($types);

        $preferences = [
            'allergy' => ['Peanuts', 'Tree Nuts', 'Dairy', 'Eggs', 'Soy', 'Wheat', 'Shellfish', 'Fish', 'Sesame'],
            'intolerance' => ['Lactose', 'Gluten', 'FODMAPs', 'Histamine', 'Fructose', 'Caffeine'],
            'pattern' => ['Vegan', 'Vegetarian', 'Pescatarian', 'Keto', 'Paleo', 'Mediterranean', 'Low-Carb', 'High-Protein'],
            'dislike' => ['Mushrooms', 'Cilantro', 'Olives', 'Blue Cheese', 'Anchovies', 'Liver', 'Brussels Sprouts'],
        ];

        $name = fake()->randomElement($preferences[$type]);

        return [
            'name' => $name,
            'type' => $type,
            'description' => fake()->sentence(),
        ];
    }
}
