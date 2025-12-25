<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\MealPlan;
use App\Workflows\MealPlanDayWorkflow;
use Illuminate\Support\Facades\DB;
use Workflow\WorkflowStub;

final readonly class RegenerateDayMealsAction
{
    /**
     * Regenerate meals for a specific day in a meal plan.
     *
     * @return array{meal_plan_id: int, day_number: int, deleted_count: int}
     */
    public function handle(MealPlan $mealPlan, int $dayNumber): array
    {
        $deletedCount = DB::transaction(fn (): int => $mealPlan->meals()
            ->where('day_number', $dayNumber)
            ->delete());

        WorkflowStub::make(MealPlanDayWorkflow::class)
            ->start($mealPlan, $dayNumber);

        return [
            'meal_plan_id' => $mealPlan->id,
            'day_number' => $dayNumber,
            'deleted_count' => $deletedCount,
        ];
    }
}
