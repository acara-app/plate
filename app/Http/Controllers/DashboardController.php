<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CalculateDailyNutritionGoals;
use App\Actions\GetUserHealthEntriesAction;
use App\Data\DailyNutritionGoalsData;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        private CalculateDailyNutritionGoals $calculateDailyNutritionGoals,
    ) {}

    public function show(#[CurrentUser] User $user): Response
    {
        $needsHealthDataConsent = $user->needsHealthDataConsent();
        $today = CarbonImmutable::now($user->resolveTimezone());

        return Inertia::render('dashboard', [
            'showHealthDataConsent' => $needsHealthDataConsent,
            'showPrivacyUpdate' => ! $needsHealthDataConsent && $user->needsPrivacyPolicyNotice(),
            'entries' => fn (): array => $this->getUserHealthEntries->handle($user, $today)->all(),
            'goals' => fn (): ?DailyNutritionGoalsData => $this->calculateDailyNutritionGoals->handle($user->profile),
        ]);
    }
}
