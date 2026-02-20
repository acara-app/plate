<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\MealPlanDayWorkflow;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Workflow\WorkflowStub;

final readonly class GenerateMealDayController
{
    public function __construct(
        #[CurrentUser] private User $user,
    ) {
        //
    }

    public function __invoke(Request $request, MealPlan $mealPlan): JsonResponse
    {
        abort_if($mealPlan->user_id !== $this->user->id, 403);

        $dayNumber = $request->integer('day', 1);

        if ($dayNumber < 1 || $dayNumber > $mealPlan->duration_days) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid day number',
            ], 422);
        }

        $existingMeals = $mealPlan->meals()
            ->where('day_number', $dayNumber)
            ->exists();

        if ($existingMeals) {
            return response()->json([
                'success' => true,
                'status' => MealPlanGenerationStatus::Completed->value,
                'message' => 'Day already generated',
            ]);
        }

        $metadata = $mealPlan->metadata ?? [];
        $dayStatusKey = sprintf('day_%d_status', $dayNumber);

        if (($metadata[$dayStatusKey] ?? '') === MealPlanGenerationStatus::Generating->value) {
            return response()->json([
                'success' => true,
                'status' => MealPlanGenerationStatus::Generating->value,
                'message' => 'Day is currently being generated',
            ]);
        }

        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                $dayStatusKey => MealPlanGenerationStatus::Generating->value,
            ]),
        ]);

        WorkflowStub::make(MealPlanDayWorkflow::class)
            ->start($mealPlan, $dayNumber);

        return response()->json([
            'success' => true,
            'status' => MealPlanGenerationStatus::Generating->value,
            'message' => 'Generation started',
        ]);
    }
}
