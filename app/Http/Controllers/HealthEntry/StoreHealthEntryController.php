<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\RecordHealthSampleAction;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntrySource;
use App\Http\Requests\HealthEntryRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreHealthEntryController
{
    public function __construct(
        private RecordHealthSampleAction $recordHealthSample,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthEntryRequest $request): RedirectResponse
    {
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

        $this->recordHealthSample->handle($healthData, $this->currentUser, HealthEntrySource::Web);

        return back()->with('success', 'Health entry recorded successfully.');
    }
}
