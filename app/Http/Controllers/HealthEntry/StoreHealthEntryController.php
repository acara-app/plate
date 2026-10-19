<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\RecordHealthSampleAction;
use App\Enums\HealthEntrySource;
use App\Http\Requests\HealthEntryRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreHealthEntryController
{
    public function __construct(
        private RecordHealthSampleAction $recordHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthEntryRequest $request): RedirectResponse
    {
        $sample = $this->recordHealthSample->handle($request->healthLogData(), $this->currentUser, HealthEntrySource::Web);

        $this->dispatchAggregateUserUtcDates->handle(
            $this->currentUser,
            [$sample->measured_at->copy()->utc()->toDateString()],
        );

        return back()->with('success', 'Health entry recorded successfully.');
    }
}
