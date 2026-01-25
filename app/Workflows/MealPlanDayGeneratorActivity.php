<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Ai\Agents\MealPlanGeneratorAgent;
use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\PreviousDayContext;
use App\Models\MealPlan;
use App\Models\User;
use Workflow\Activity;

/**
 * @codeCoverageIgnore Activity classes are executed by the workflow engine
 */
final class MealPlanDayGeneratorActivity extends Activity
{
    public function execute(
        User $user,
        int $dayNumber,
        int $totalDays,
        PreviousDayContext $previousDaysContext,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
    ): DayMealsData {
        /** @var MealPlanGeneratorAgent $generateMealPlan */
        $generateMealPlan = resolve(MealPlanGeneratorAgent::class);

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
