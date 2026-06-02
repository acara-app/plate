<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Actions\CreateMealPlan;
use App\Enums\DietType;
use App\Http\Requests\StoreMealPlanRequest;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreMealPlanController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private AnalyzeGlucoseForNotificationAction $analyzeGlucose,
        private CreateMealPlan $createMealPlan,
    ) {}

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
        $mealPlan = $this->createMealPlan->handle($user, $durationDays, $dietType);

        if ($prompt !== '') {
            $mealPlan->update([
                'metadata->custom_prompt' => $prompt,
            ]);
        }

        GenerateInitialMealPlanJob::dispatch(
            $user,
            $mealPlan,
            $glucoseAnalysis->analysisData,
            $dietType,
        );

        return to_route('meal-plans.index');
    }
}
