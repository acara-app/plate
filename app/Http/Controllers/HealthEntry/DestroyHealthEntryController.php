<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DeleteHealthSampleAction;
use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Models\HealthSyncSample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
        $affectedUtcDates = $this->affectedUtcDatesForDeletion($healthSyncSample);

        $this->deleteHealthSample->handle($healthSyncSample);
        $this->dispatchAggregateUserUtcDates->handle($user, $affectedUtcDates);

        return back()->with('success', 'Health entry deleted successfully.');
    }

    /**
     * @return list<string>
     */
    private function affectedUtcDatesForDeletion(HealthSyncSample $healthSyncSample): array
    {
        $samples = $healthSyncSample->group_id !== null
            ? HealthSyncSample::query()->where('group_id', $healthSyncSample->group_id)->get()
            : new Collection([$healthSyncSample]);

        /** @var list<string> */
        return $samples
            ->map(static fn (HealthSyncSample $sample): string => $sample->measured_at->copy()->utc()->toDateString())
            ->unique()
            ->values()
            ->all();
    }
}
