<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\CalculateDailyNutritionGoals;
use App\Actions\GetDailyWellnessAction;
use App\Actions\GetUserHealthEntriesAction;
use App\Actions\GetUserLoggedDatesAction;
use App\Enums\MealPhotoRoute;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class ListHealthEntryController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        private GetUserLoggedDatesAction $getUserLoggedDates,
        private CalculateDailyNutritionGoals $calculateDailyNutritionGoals,
        private GetDailyWellnessAction $getDailyWellness,
    ) {}

    public function __invoke(ListHealthEntriesRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $timezone = $user->resolveTimezone();
        $day = $request->selectedDay($timezone);
        $weekStart = $request->selectedWeekStart($timezone);
        $now = CarbonImmutable::now($timezone);

        return response()->json([
            'date' => $day->toDateString(),
            'today' => $now->toDateString(),
            'timezone' => $timezone,
            'week_start' => $weekStart->toDateString(),
            'new_entry_at' => $day->isSameDay($now) ? null : $day->setTimeFrom($now)->toIso8601String(),
            'glucose_unit' => $user->glucoseUnit()->value,
            'entries' => $this->getUserHealthEntries->handle($user, $day, MealPhotoRoute::Api, $user->glucoseUnit())->all(),
            'logged_dates' => $this->getUserLoggedDates->handle($user, $weekStart, $weekStart->addDays(6)),
            'goals' => $this->calculateDailyNutritionGoals->handle($user->profile)?->toArray(),
            'wellness' => $this->getDailyWellness->handle($user, $day)->toArray(),
        ]);
    }
}
