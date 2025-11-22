<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CorrectMealNutrition;
use App\Actions\VerifyIngredientNutrition;
use App\DataObjects\IngredientData;
use App\DataObjects\MealData;
use App\Models\MealPlan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Spatie\LaravelData\DataCollection;

final class VerifyAndCorrectMealsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(
        public readonly int $mealPlanId,
    ) {}

    public function handle(
        VerifyIngredientNutrition $verifyIngredients,
        CorrectMealNutrition $correctNutrition,
    ): void {
        /** @var MealPlan|null $mealPlan */
        $mealPlan = MealPlan::query()->find($this->mealPlanId);

        if (! $mealPlan) {
            return;
        }

        $meals = $mealPlan->meals()->get();

        if ($meals->isEmpty()) {
            return;
        }

        foreach ($meals as $meal) {
            // Skip if already verified
            if ($meal->food_data_verification !== null && isset($meal->food_data_verification['verified']) && $meal->food_data_verification['verified'] === true) {
                continue;
            }

            $ingredients = $meal->ingredients ?? [];

            if ($ingredients === []) {
                continue;
            }

            $verificationData = $verifyIngredients->handle($ingredients);

            $ingredientsCollection = $meal->ingredients !== null
                ? new DataCollection(IngredientData::class, $meal->ingredients)
                : null;

            $mealData = new MealData(
                dayNumber: $meal->day_number,
                type: $meal->type,
                name: $meal->name,
                description: $meal->description,
                preparationInstructions: $meal->preparation_instructions,
                ingredients: $ingredientsCollection,
                portionSize: $meal->portion_size,
                calories: (float) $meal->calories,
                proteinGrams: $meal->protein_grams !== null ? (float) $meal->protein_grams : null,
                carbsGrams: $meal->carbs_grams !== null ? (float) $meal->carbs_grams : null,
                fatGrams: $meal->fat_grams !== null ? (float) $meal->fat_grams : null,
                preparationTimeMinutes: $meal->preparation_time_minutes,
                sortOrder: $meal->sort_order,
                metadata: $meal->metadata,
            );

            $correctedMeal = $correctNutrition->handle($mealData, $verificationData);

            $meal->update([
                'calories' => $correctedMeal->calories,
                'protein_grams' => $correctedMeal->proteinGrams,
                'carbs_grams' => $correctedMeal->carbsGrams,
                'fat_grams' => $correctedMeal->fatGrams,
                'food_data_verification' => $correctedMeal->verificationMetadata,
            ]);
        }
    }
}
