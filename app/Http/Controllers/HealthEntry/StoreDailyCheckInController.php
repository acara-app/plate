<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\RecordDailyCheckInAction;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Http\Requests\StoreDailyCheckInRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreDailyCheckInController
{
    public function __construct(
        private RecordDailyCheckInAction $recordDailyCheckIn,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(StoreDailyCheckInRequest $request): RedirectResponse
    {
        $affectedUtcDates = $this->recordDailyCheckIn->handle(
            $this->currentUser,
            $request->selectedDay(),
            $request->sleepHours(),
            $request->enum('sleep_quality', SleepQuality::class),
            $request->enum('stress_level', StressLevel::class),
        );

        $this->dispatchAggregateUserUtcDates->handle($this->currentUser, $affectedUtcDates);

        return back()->with('success', 'Daily check-in saved.');
    }
}
