<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Data\DayMealsData;
use App\Data\PreviousDayContext;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use Generator;
use Illuminate\Queue\Attributes\Timeout;
use Throwable;
use Workflow\ActivityStub;
use Workflow\Workflow;

#[Timeout(300)]
final class MealPlanDayWorkflow extends Workflow
{
    public function failed(Throwable $throwable): void
    {
        $arguments = $this->storedWorkflow->workflowArguments();
        $mealPlan = $arguments[0] ?? null;
        $dayNumber = $arguments[1] ?? null;

        if ($mealPlan instanceof MealPlan && is_int($dayNumber)) {
            $dayStatusKey = sprintf('day_%d_status', $dayNumber);

            $mealPlan->update([
                'metadata' => array_merge($mealPlan->metadata ?? [], [
                    $dayStatusKey => MealPlanGenerationStatus::Failed->value,
                ]),
            ]);
        }

        parent::failed($throwable);
    }

    /**
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     *
     * @phpstan-return Generator<mixed, mixed, mixed, mixed>
     */
    public function execute(
        MealPlan $mealPlan,
        int $dayNumber,
    ): Generator {
        $user = $mealPlan->user;
        $totalDays = $mealPlan->duration_days;

        $previousDaysContext = $this->buildPreviousDaysContext($mealPlan, $dayNumber);

        /** @var DayMealsData $dayMeals */
        $dayMeals = yield ActivityStub::make(
            MealPlanDayGeneratorActivity::class,
            $user,
            $dayNumber,
            $totalDays,
            $previousDaysContext,
            null,
            $mealPlan,
        );

        yield ActivityStub::make(
            SaveDayMealsActivity::class,
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
        unset($metadata[sprintf('day_%d_status', $dayNumber)]);

        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                'days_completed' => $daysCompleted,
                'status' => $isCompleted
                    ? MealPlanGenerationStatus::Completed->value
                    : MealPlanGenerationStatus::Pending->value,
                sprintf('day_%d_generated_at', $dayNumber) => now()->toIso8601String(),
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
