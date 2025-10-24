<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HealthCondition>
 */
final class HealthConditionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $conditions = [
            'Type 2 Diabetes' => [
                'recommended' => ['Fiber', 'Chromium', 'Magnesium'],
                'limit' => ['Simple Carbs', 'Sugar', 'Saturated Fat'],
            ],
            'Hypertension' => [
                'recommended' => ['Potassium', 'Magnesium', 'Calcium'],
                'limit' => ['Sodium', 'Saturated Fat'],
            ],
            'Heart Disease' => [
                'recommended' => ['Omega-3', 'Fiber', 'Antioxidants'],
                'limit' => ['Saturated Fat', 'Trans Fat', 'Sodium'],
            ],
            'Celiac Disease' => [
                'recommended' => ['Iron', 'Folate', 'Vitamin B12', 'Calcium'],
                'limit' => ['Gluten'],
            ],
        ];

        $name = fake()->randomElement(array_keys($conditions));

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'nutritional_impact' => fake()->paragraph(),
            'recommended_nutrients' => $conditions[$name]['recommended'],
            'nutrients_to_limit' => $conditions[$name]['limit'],
        ];
    }
}
