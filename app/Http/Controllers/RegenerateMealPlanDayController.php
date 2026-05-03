<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MealPlanGenerationStatus;
use App\Http\Requests\RegenerateMealPlanDayRequest;
use App\Models\MealPlan;
use App\Workflows\MealPlanDayWorkflow;
use Illuminate\Http\RedirectResponse;
use Workflow\WorkflowStub;

final readonly class RegenerateMealPlanDayController
{
    public function __invoke(RegenerateMealPlanDayRequest $request, MealPlan $mealPlan): RedirectResponse
    {
        $dayNumber = $request->integer('day', 1);

        $mealPlan->meals()
            ->where('day_number', $dayNumber)
            ->delete();

        $metadata = $mealPlan->metadata ?? [];
        $dayStatusKey = sprintf('day_%d_status', $dayNumber);

        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                $dayStatusKey => MealPlanGenerationStatus::Generating->value,
            ]),
        ]);

        WorkflowStub::make(MealPlanDayWorkflow::class)
            ->start($mealPlan, $dayNumber);

        return back();
    }
}
