<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\UpdateHealthSampleAction;
use App\Http\Requests\HealthEntryRequest;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateHealthEntryController
{
    public function __construct(
        private UpdateHealthSampleAction $updateHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthEntryRequest $request, HealthSyncSample $healthSyncSample): RedirectResponse
    {
        Gate::authorize('update', $healthSyncSample);
        $previousUtcDate = $healthSyncSample->measured_at->copy()->utc()->toDateString();

        $metadata = HealthSyncSample::firstMetadataIn($healthSyncSample->groupMembers(), 'snap_to_track');
        $healthData = $request->healthLogData(is_array($metadata) ? $metadata : []);

        $updatedSample = $this->updateHealthSample->handle($healthSyncSample, $healthData);

        $this->dispatchAggregateUserUtcDates->handle(
            $this->currentUser,
            [
                $previousUtcDate,
                $updatedSample->measured_at->copy()->utc()->toDateString(),
            ],
        );

        return back()->with('success', 'Health entry updated successfully.');
    }
}
