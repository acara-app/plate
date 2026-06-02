<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Data\DayMealsData;
use App\Models\MealPlan;

final class SaveDayMealsActivity
{
    /**
     * @return array{day_number: int, meals_count: int}
     */
    public function handle(
        MealPlan $mealPlan,
        DayMealsData $dayMeals,
        int $dayNumber,
    ): array {
        $mealPlan->meals()
            ->where('day_number', $dayNumber)
            ->delete();

        $mealsCount = 0;

        foreach ($dayMeals->meals as $singleDayMeal) {
            $mealData = $singleDayMeal->toMealData($dayNumber);

            $mealPlan->meals()->create([
                'day_number' => $mealData->dayNumber,
                'type' => $mealData->type,
                'name' => $mealData->name,
                'description' => $mealData->description,
                'preparation_instructions' => $mealData->preparationInstructions,
                'ingredients' => $mealData->ingredients,
                'portion_size' => $mealData->portionSize,
                'calories' => $mealData->calories,
                'protein_grams' => $mealData->proteinGrams,
                'carbs_grams' => $mealData->carbsGrams,
                'fat_grams' => $mealData->fatGrams,
                'preparation_time_minutes' => $mealData->preparationTimeMinutes,
                'sort_order' => $mealData->sortOrder,
                'metadata' => $mealData->metadata,
            ]);

            $mealsCount++;
        }

        return [
            'day_number' => $dayNumber,
            'meals_count' => $mealsCount,
        ];
    }
}
