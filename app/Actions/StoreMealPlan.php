<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\MealData;
use App\DataTransferObjects\MealPlanData;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class StoreMealPlan
{
    public function handle(User $user, MealPlanData $mealPlanData): MealPlan
    {
        return DB::transaction(function () use ($user, $mealPlanData): MealPlan {
            /** @var MealPlan $mealPlan */
            $mealPlan = $user->mealPlans()->create([
                'type' => $mealPlanData->type,
                'name' => $mealPlanData->name,
                'description' => $mealPlanData->description,
                'duration_days' => $mealPlanData->durationDays,
                'target_daily_calories' => $mealPlanData->targetDailyCalories,
                'macronutrient_ratios' => $mealPlanData->macronutrientRatios,
                'metadata' => $mealPlanData->metadata,
            ]);

            foreach ($mealPlanData->meals as $mealData) {
                $this->storeMeal($mealPlan, $mealData);
            }

            return $mealPlan->load('meals');
        });
    }

    private function storeMeal(MealPlan $mealPlan, MealData $mealData): void
    {
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
    }
}
