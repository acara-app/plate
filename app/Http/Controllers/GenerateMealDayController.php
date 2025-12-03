<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MealPlanGenerationStatus;
use App\Jobs\GenerateMealDayJob;
use App\Models\MealPlan;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GenerateMealDayController
{
    public function __construct(
        #[CurrentUser] private \App\Models\User $user,
    ) {
        //
    }

    /**
     * Trigger generation for a specific day if not already generated or generating.
     */
    public function __invoke(Request $request, MealPlan $mealPlan): JsonResponse
    {
        // Ensure user owns this meal plan
        if ($mealPlan->user_id !== $this->user->id) {
            abort(403);
        }

        $dayNumber = $request->integer('day', 1);

        // Validate day number
        if ($dayNumber < 1 || $dayNumber > $mealPlan->duration_days) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid day number',
            ], 422);
        }

        // Check if day already has meals
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

        // Check if day is currently generating
        $metadata = $mealPlan->metadata ?? [];
        $dayStatusKey = "day_{$dayNumber}_status";

        if (($metadata[$dayStatusKey] ?? '') === MealPlanGenerationStatus::Generating->value) {
            return response()->json([
                'success' => true,
                'status' => MealPlanGenerationStatus::Generating->value,
                'message' => 'Day is currently being generated',
            ]);
        }

        // Mark as generating and dispatch job
        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                $dayStatusKey => MealPlanGenerationStatus::Generating->value,
            ]),
        ]);

        GenerateMealDayJob::dispatch($mealPlan, $dayNumber);

        return response()->json([
            'success' => true,
            'status' => MealPlanGenerationStatus::Generating->value,
            'message' => 'Generation started',
        ]);
    }
}
