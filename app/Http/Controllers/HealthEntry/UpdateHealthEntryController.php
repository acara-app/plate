<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\UpdateHealthSampleAction;
use App\Data\HealthLogData;
use App\Enums\GlucoseUnit;
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
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthEntryRequest $request, HealthSyncSample $healthSyncSample): RedirectResponse
    {
        Gate::authorize('update', $healthSyncSample);

        $data = $request->validated();

        // @phpstan-ignore nullsafe.neverNull
        $glucoseUnit = $this->currentUser->profile?->units_preference ?? GlucoseUnit::MmolL;
        if ($glucoseUnit === GlucoseUnit::MmolL && isset($data['glucose_value'])) {
            $glucoseValue = is_numeric($data['glucose_value']) ? (float) $data['glucose_value'] : 0;
            $data['glucose_value'] = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        $healthData = HealthLogData::fromParsedArray(array_merge(
            $data,
            ['is_health_data' => true],
        ));

        $this->updateHealthSample->handle($healthSyncSample, $healthData);

        return back()->with('success', 'Health entry updated successfully.');
    }
}
