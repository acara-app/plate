<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use App\Workflows\MealPlanInitializeWorkflow;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Workflow\WorkflowStub;

final readonly class RegenerateMealPlanController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private AnalyzeGlucoseForNotificationAction $analyzeGlucose,
    ) {
        //
    }

    public function store(): RedirectResponse
    {
        $this->user->mealPlans()->delete();

        $glucoseAnalysis = $this->analyzeGlucose->handle($this->user);

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($this->user, 7, $glucoseAnalysis->analysisData);

        return to_route('meal-plans.index')
            ->with('success', 'Your new glucose-optimized meal plan is being generated. This may take a few minutes.');
    }
}
