<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use App\Data\DayMealsData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
use App\Models\MealPlan;
use App\Models\User;

final class MealPlanDayContext
{
    public ?DayMealsData $dayMeals = null;

    public function __construct(
        public readonly User $user,
        public readonly MealPlan $mealPlan,
        public readonly int $dayNumber,
        public readonly int $totalDays,
        public PreviousDayContext $previousDaysContext,
        public readonly ?GlucoseAnalysisData $glucoseAnalysis = null,
        public readonly ?DietType $dietType = null,
    ) {}
}
