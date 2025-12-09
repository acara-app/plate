<?php

declare(strict_types=1);

namespace App\Workflows;

use App\DataObjects\DayMealsData;
use App\Models\MealPlan;
use Workflow\Activity;

/**
 * @codeCoverageIgnore Activity classes are executed by the workflow engine
 */
final class SaveDayMealsActivity extends Activity
{
    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 30;

    /**
     * Store meals for a single day to an existing meal plan.
     *
     * @return array{day_number: int, meals_count: int}
     */
    public function execute(
        MealPlan $mealPlan,
        DayMealsData $dayMeals,
        int $dayNumber,
    ): array {
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
