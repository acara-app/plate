<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Ai\Agents\MealPlanGeneratorAgent;
use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\PreviousDayContext;
use App\Enums\DietType;
use App\Models\MealPlan;
use App\Models\User;
use Workflow\Activity;

/**
 * @codeCoverageIgnore Activity classes are executed by the workflow engine
 */
final class MealPlanDayGeneratorActivity extends Activity
{
    /** @var int */
    public $tries = 2;

    public function execute(
        User $user,
        int $dayNumber,
        int $totalDays,
        PreviousDayContext $previousDaysContext,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $mealPlan = null,
        ?DietType $dietType = null,
    ): DayMealsData {
        /** @var MealPlanGeneratorAgent $generateMealPlan */
        $generateMealPlan = resolve(MealPlanGeneratorAgent::class);

        if ($dietType instanceof DietType) {
            $generateMealPlan = $generateMealPlan->withDietType($dietType);
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
