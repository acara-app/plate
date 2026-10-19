<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\CalculateDailyNutritionGoals;
use App\Actions\GetDailyWellnessAction;
use App\Actions\GetUserHealthEntriesAction;
use App\Actions\GetUserLoggedDatesAction;
use App\Data\DailyNutritionGoalsData;
use App\Data\DailyWellnessData;
use App\Http\Layouts\DiabetesLayout;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListHealthEntryController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        private GetUserLoggedDatesAction $getUserLoggedDates,
        private CalculateDailyNutritionGoals $calculateDailyNutritionGoals,
        private GetDailyWellnessAction $getDailyWellness,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(ListHealthEntriesRequest $request): Response
    {
        $timezone = $this->currentUser->resolveTimezone();
        $day = $request->selectedDay($timezone);
        $weekStart = $request->selectedWeekStart($timezone);
        $now = CarbonImmutable::now($timezone);

        return Inertia::render('health-entries/index', [
            'date' => $day->toDateString(),
            'today' => $now->toDateString(),
            'newEntryAt' => $day->isSameDay($now) ? null : $day->setTimeFrom($now)->toIso8601String(),
            'entries' => fn (): array => $this->getUserHealthEntries->handle($this->currentUser, $day)->all(),
            'loggedDates' => fn (): array => $this->getUserLoggedDates->handle($this->currentUser, $weekStart, $weekStart->addDays(6)),
            'goals' => fn (): ?DailyNutritionGoalsData => $this->calculateDailyNutritionGoals->handle($this->currentUser->profile),
            'wellness' => fn (): DailyWellnessData => $this->getDailyWellness->handle($this->currentUser, $day),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
