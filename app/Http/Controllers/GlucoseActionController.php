<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GlucoseActionController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private AnalyzeGlucoseForNotificationAction $analyzeAction,
    ) {}

    public function show(): Response
    {
        $analysisResult = $this->analyzeAction->handle($this->user);

        return Inertia::render('glucose/action', [
            'glucoseAnalysis' => $analysisResult->analysisData,
            'concerns' => $analysisResult->concerns,
            'hasMealPlan' => $this->user->has_meal_plan,
            'mealPlan' => $this->user->mealPlans()->latest()->first(),
        ]);
    }
}
