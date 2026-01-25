<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
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

        $prompt = request()->input('prompt');
        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, 3);

        if ($prompt) {
            $mealPlan->update([
                'metadata->custom_prompt' => $prompt,
            ]);
        }

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($user, $mealPlan, $glucoseAnalysis->analysisData);

        return to_route('meal-plans.index');
    }
}
