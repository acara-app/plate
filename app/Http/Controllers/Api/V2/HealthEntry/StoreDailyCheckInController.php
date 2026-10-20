<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\GetDailyWellnessAction;
use App\Actions\RecordDailyCheckInAction;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Http\Requests\StoreDailyCheckInRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class StoreDailyCheckInController
{
    public function __construct(
        private RecordDailyCheckInAction $recordDailyCheckIn,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        private GetDailyWellnessAction $getDailyWellness,
    ) {}

    public function __invoke(StoreDailyCheckInRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $day = $request->selectedDay();

        $affectedUtcDates = $this->recordDailyCheckIn->handle(
            $user,
            $day,
            $request->sleepHours(),
            $request->enum('sleep_quality', SleepQuality::class),
            $request->enum('stress_level', StressLevel::class),
        );

        $this->dispatchAggregateUserUtcDates->handle($user, $affectedUtcDates);

        return response()->json(['wellness' => $this->getDailyWellness->handle($user, $day)->toArray()]);
    }
}
