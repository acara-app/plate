<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\UpdateHealthSampleAction;
use App\Data\HealthLogData;
use App\Data\ReviewedMealData;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
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

        $data = $request->validated();

        // @phpstan-ignore nullsafe.neverNull
        $glucoseUnit = $this->currentUser->profile?->units_preference ?? GlucoseUnit::MmolL;
        if ($glucoseUnit === GlucoseUnit::MmolL && isset($data['glucose_value'])) {
            $glucoseValue = is_numeric($data['glucose_value']) ? (float) $data['glucose_value'] : 0;
            $data['glucose_value'] = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        $metadata = HealthSyncSample::firstMetadataIn($healthSyncSample->groupMembers(), 'snap_to_track');
        $healthData = $data['log_type'] === HealthEntryType::Food->value && isset($data['items'])
            ? ReviewedMealData::fromValidated($data)->toHealthLogDataWithMetadata(is_array($metadata) ? $metadata : [])
            : HealthLogData::fromParsedArray(array_merge($data, ['is_health_data' => true]));

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
