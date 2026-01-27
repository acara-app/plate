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

final readonly class RegenerateMealPlanController
{
    private const int DEFAULT_DURATION_DAYS = 7;

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

        $dietType = $this->user->profile?->calculated_diet_type ?? DietType::Balanced;

        $mealPlan = MealPlanInitializeWorkflow::createMealPlan(
            $this->user,
            self::DEFAULT_DURATION_DAYS,
            $dietType,
        );

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($this->user, $mealPlan, $glucoseAnalysis->analysisData, $dietType);

        return to_route('meal-plans.index')
            ->with('success', 'Your new glucose-optimized meal plan is being generated. This may take a few minutes.');
    }
}
