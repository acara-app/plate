<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\RecordWaterIntakeAction;
use App\Http\Requests\StoreWaterIntakeRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreWaterIntakeController
{
    public function __construct(
        private RecordWaterIntakeAction $recordWaterIntake,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(StoreWaterIntakeRequest $request): RedirectResponse
    {
        $measuredAt = $this->recordWaterIntake->handle(
            $this->currentUser,
            $request->selectedDay(),
            $request->amountMl(),
        );

        $this->dispatchAggregateUserUtcDates->handle($this->currentUser, [$measuredAt->toDateString()]);

        return back();
    }
}
