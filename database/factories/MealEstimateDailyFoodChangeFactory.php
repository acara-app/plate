<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MealEstimateDailyFoodChange;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealEstimateDailyFoodChange>
 */
final class MealEstimateDailyFoodChangeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->subDay()->toDateString(),
            'ai_food' => 'white rice cooked',
            'corrected_food' => 'fried rice',
            'meals' => 1,
        ];
    }
}
