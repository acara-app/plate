<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Ai\Agents\MealPlanAgent;
use App\Data\DayMealsData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
use App\Models\MealPlan;
use App\Models\User;

final class MealPlanDayGeneratorActivity
{
    public function handle(
        User $user,
        int $dayNumber,
        int $totalDays,
        PreviousDayContext $previousDaysContext,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
        ?DietType $dietType = null,
    ): DayMealsData {
        /** @var MealPlanAgent $generateMealPlan */
        $generateMealPlan = resolve(MealPlanAgent::class);

        $effectiveDietType = $dietType;

        if (! $effectiveDietType instanceof DietType && $mealPlan instanceof MealPlan) {
            $dietTypeValue = $mealPlan->metadata['diet_type'] ?? null;

            if (is_string($dietTypeValue)) {
                $effectiveDietType = DietType::tryFrom($dietTypeValue);
            }
        }

        if ($effectiveDietType instanceof DietType) {
            $generateMealPlan = $generateMealPlan->withDietType($effectiveDietType);
        }

        return $generateMealPlan->generateForDay(
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
            $glucoseAnalysis,
            $mealPlan,
        );
    }
}
