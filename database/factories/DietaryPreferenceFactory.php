<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DietaryPreferenceType;
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
        $types = [
            DietaryPreferenceType::Allergy->value,
            DietaryPreferenceType::Intolerance->value,
            DietaryPreferenceType::Pattern->value,
            DietaryPreferenceType::Dislike->value,
        ];
        /** @var string $type */
        $type = fake()->randomElement($types);

        $preferences = [
            DietaryPreferenceType::Allergy->value => ['Peanuts', 'Tree Nuts', 'Dairy', 'Eggs', 'Soy', 'Wheat', 'Shellfish', 'Fish', 'Sesame'],
            DietaryPreferenceType::Intolerance->value => ['Lactose', 'Gluten', 'FODMAPs', 'Histamine', 'Fructose', 'Caffeine'],
            DietaryPreferenceType::Pattern->value => ['Vegan', 'Vegetarian', 'Pescatarian', 'Keto', 'Paleo', 'Mediterranean', 'Low-Carb', 'High-Protein'],
            DietaryPreferenceType::Dislike->value => ['Mushrooms', 'Cilantro', 'Olives', 'Blue Cheese', 'Anchovies', 'Liver', 'Brussels Sprouts'],
        ];

        $name = fake()->randomElement($preferences[$type]);

        return [
            'name' => $name,
            'type' => $type,
            'description' => fake()->sentence(),
        ];
    }
}
