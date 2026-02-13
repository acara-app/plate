<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class InsightsHealthEntryController
{
    public function __construct(
        private AnalyzeGlucoseForNotificationAction $analyzeAction,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(): Response
    {
        $analysisResult = $this->analyzeAction->handle($this->currentUser);

        return Inertia::render('health-entries/insights', [
            'glucoseAnalysis' => $analysisResult->analysisData,
            'concerns' => $analysisResult->concerns,
            'hasMealPlan' => $this->currentUser->has_meal_plan,
            'mealPlan' => $this->currentUser->mealPlans()->latest()->first(),
        ]);
    }
}
