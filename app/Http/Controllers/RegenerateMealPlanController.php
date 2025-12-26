<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\MealPlanGenerationStatus;
use App\Models\MealPlan;
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

        $mealPlan = $this->createMealPlanWithGeneratingStatus();

        WorkflowStub::make(MealPlanInitializeWorkflow::class)
            ->start($this->user, self::DEFAULT_DURATION_DAYS, $glucoseAnalysis->analysisData, $mealPlan);

        return to_route('meal-plans.index')
            ->with('success', 'Your new glucose-optimized meal plan is being generated. This may take a few minutes.');
    }

    private function createMealPlanWithGeneratingStatus(): MealPlan
    {
        $mealPlanType = MealPlanInitializeWorkflow::getMealPlanType(self::DEFAULT_DURATION_DAYS);

        /** @var MealPlan $mealPlan */
        $mealPlan = $this->user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => self::DEFAULT_DURATION_DAYS.'-Day Personalized Meal Plan',
            'description' => 'AI-generated meal plan tailored to your nutritional needs and preferences.',
            'duration_days' => self::DEFAULT_DURATION_DAYS,
            'target_daily_calories' => null,
            'macronutrient_ratios' => null,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generation_method' => 'workflow',
                'status' => MealPlanGenerationStatus::Generating->value,
                'days_completed' => 0,
            ],
        ]);

        return $mealPlan;
    }
}
