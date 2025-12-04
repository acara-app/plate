<?php

declare(strict_types=1);

namespace App\Workflows;

use App\DataObjects\DayMealsData;
use App\DataObjects\PreviousDayContext;
use App\Enums\AiModel;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use Generator;
use Workflow\ActivityStub;
use Workflow\Workflow;

final class GenerateSingleDayWorkflow extends Workflow
{
    public int $timeout = 300; // 5 minutes per day

    /**
     * Generate meals for a specific day in an existing meal plan.
     *
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     */
    public function execute(
        MealPlan $mealPlan,
        int $dayNumber,
        AiModel $model = AiModel::Gemini25Flash,
    ): Generator {
        $user = $mealPlan->user;
        $totalDays = $mealPlan->duration_days;

        $previousDaysContext = $this->buildPreviousDaysContext($mealPlan, $dayNumber);

        /** @var DayMealsData $dayMeals */
        $dayMeals = yield ActivityStub::make(
            GenerateDayMealsActivity::class,
            $user,
            $dayNumber,
            $totalDays,
            $model,
            $previousDaysContext,
        );

        yield ActivityStub::make(
            StoreDayMealsActivity::class,
            $mealPlan,
            $dayMeals,
            $dayNumber,
        );

        $daysCompleted = max(
            $mealPlan->metadata['days_completed'] ?? 0,
            $dayNumber
        );

        $isCompleted = $daysCompleted >= $totalDays;

        $metadata = $mealPlan->metadata ?? [];
        unset($metadata["day_{$dayNumber}_status"]);

        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                'days_completed' => $daysCompleted,
                'status' => $isCompleted
                    ? MealPlanGenerationStatus::Completed->value
                    : MealPlanGenerationStatus::Pending->value,
                "day_{$dayNumber}_generated_at" => now()->toIso8601String(),
            ]),
        ]);

        return [
            'meal_plan_id' => $mealPlan->id,
            'day_number' => $dayNumber,
            'status' => MealPlanGenerationStatus::Completed->value,
        ];
    }

    private function buildPreviousDaysContext(MealPlan $mealPlan, int $currentDay): PreviousDayContext
    {
        $context = new PreviousDayContext;

        $previousMeals = $mealPlan->meals()
            ->where('day_number', '<', $currentDay)
            ->orderBy('day_number')
            ->get()
            ->groupBy('day_number');

        foreach ($previousMeals as $dayNumber => $meals) {
            /** @var array<string> $mealNames */
            $mealNames = $meals->pluck('name')->toArray();
            $context->addDayMeals($dayNumber, $mealNames);
        }

        return $context;
    }
}
