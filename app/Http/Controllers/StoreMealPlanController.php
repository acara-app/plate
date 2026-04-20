<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\DietType;
use App\Http\Requests\StoreMealPlanRequest;
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

    public function __invoke(StoreMealPlanRequest $request): RedirectResponse
    {
        $user = $this->user;

        $glucoseAnalysis = $this->analyzeGlucose->handle($user);
        $dietTypeInput = $request->string('diet_type')->toString();
        $dietType = $dietTypeInput !== ''
            ? DietType::tryFrom($dietTypeInput)
            : ($user->profile->calculated_diet_type ?? DietType::Balanced);

        $prompt = $request->string('prompt')->toString();
        $durationDays = $request->integer('duration_days');
        $mealPlan = MealPlanInitializeWorkflow::createMealPlan($user, $durationDays, $dietType);

        if ($prompt !== '') {
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
