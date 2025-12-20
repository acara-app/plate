<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
use App\Workflows\MealPlanDayWorkflow;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Workflow\WorkflowStub;

final readonly class RegenerateMealPlanDayController
{
    public function __construct(
        #[CurrentUser] private \App\Models\User $user,
    ) {}

    public function __invoke(Request $request, MealPlan $mealPlan): RedirectResponse
    {
        abort_if($mealPlan->user_id !== $this->user->id, 403);

        $dayNumber = $request->integer('day', 1);

        if ($dayNumber < 1 || $dayNumber > $mealPlan->duration_days) {
            return back()->withErrors(['day' => 'Invalid day number']);
        }

        $mealPlan->meals()
            ->where('day_number', $dayNumber)
            ->delete();

        $metadata = $mealPlan->metadata ?? [];
        $dayStatusKey = "day_{$dayNumber}_status";

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
