<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\DietType;
use App\Models\User;
use App\Workflows\MealPlanInitializeWorkflow;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Workflow\WorkflowStub;

final readonly class StoreMealPlanController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private AnalyzeGlucoseForNotificationAction $analyzeGlucose,
    ) {
        //
    }

    public function __invoke(): RedirectResponse
    {
        $user = $this->user;

        $glucoseAnalysis = $this->analyzeGlucose->handle($user);
        $dietTypeInput = request()->input('diet_type');

        if ($dietTypeInput) {
            $dietType = DietType::tryFrom($dietTypeInput);
        } else {
            $dietType = $user->profile?->calculated_diet_type ?? DietType::Balanced;
        }

        $prompt = request()->input('prompt');
        $durationDays = (int) request()->input('duration_days', 3);
        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, $durationDays, $dietType);

        if ($prompt) {
            $mealPlan->update([
                'metadata->custom_prompt' => $prompt,
            ]);
        }

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start(
                $user,
                $mealPlan,
                $glucoseAnalysis->analysisData,
                $dietType,
            );

        return to_route('meal-plans.index');
    }
}
