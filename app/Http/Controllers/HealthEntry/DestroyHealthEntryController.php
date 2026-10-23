<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DeleteHealthSampleAction;
use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Models\HealthSyncSample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class DestroyHealthEntryController
{
    public function __construct(
        private DeleteHealthSampleAction $deleteHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
    ) {}

    public function __invoke(HealthSyncSample $healthSyncSample): RedirectResponse
    {
        Gate::authorize('delete', $healthSyncSample);
        $user = $healthSyncSample->user;
        $affectedUtcDates = $healthSyncSample->groupUtcDates();

        $localDate = $healthSyncSample->measured_at->toImmutable()->setTimezone($user->resolveTimezone())->toDateString();

        $this->deleteHealthSample->handle($healthSyncSample);
        $this->dispatchAggregateUserUtcDates->handle($user, $affectedUtcDates);

        return to_route('health-entries.index', ['date' => $localDate])->with('success', 'Health entry deleted successfully.');
    }
}
