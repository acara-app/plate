<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Actions\AiAgents\GenerateMealPlan;
use App\DataObjects\DayMealsData;
use App\DataObjects\PreviousDayContext;
use App\Enums\AiModel;
use App\Models\User;
use Workflow\Activity;

/**
 * @codeCoverageIgnore Activity classes are executed by the workflow engine
 */
final class GenerateDayMealsActivity extends Activity
{
    public $tries = 3;

    public $timeout = 300; // 5 minutes per day

    public function execute(
        User $user,
        int $dayNumber,
        int $totalDays,
        AiModel $model,
        PreviousDayContext $previousDaysContext,
    ): DayMealsData {
        /** @var GenerateMealPlan $generateMealPlan */
        $generateMealPlan = app(GenerateMealPlan::class);

        return $generateMealPlan->generateForDay(
            $user,
            $dayNumber,
            $model,
            $totalDays,
            $previousDaysContext,
        );
    }
}
