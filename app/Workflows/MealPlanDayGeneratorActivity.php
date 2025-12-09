<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Ai\Agents\MealPlanGeneratorAgent;
use App\DataObjects\DayMealsData;
use App\DataObjects\PreviousDayContext;
use App\Models\User;
use Workflow\Activity;

/**
 * @codeCoverageIgnore Activity classes are executed by the workflow engine
 */
final class MealPlanDayGeneratorActivity extends Activity
{
    /** @var int */
    public $tries = 3;

    /** @var int */
    public $timeout = 300; // 5 minutes per day

    public function execute(
        User $user,
        int $dayNumber,
        int $totalDays,
        PreviousDayContext $previousDaysContext,
    ): DayMealsData {
        /** @var MealPlanGeneratorAgent $generateMealPlan */
        $generateMealPlan = app(MealPlanGeneratorAgent::class);

        return $generateMealPlan->generateForDay(
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
        );
    }
}
