<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\RecordHealthEntryAction;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntrySource;
use App\Http\Requests\StoreHealthEntryRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreHealthEntryController
{
    public function __construct(
        private RecordHealthEntryAction $recordHealthEntry,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(StoreHealthEntryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var array<string, mixed> $recordData */
        $recordData = collect($data + ['user_id' => $this->currentUser->id])->except('log_type')->toArray();

        // @phpstan-ignore nullsafe.neverNull
        $glucoseUnit = $this->currentUser->profile?->units_preference ?? GlucoseUnit::MmolL;
        if ($glucoseUnit === GlucoseUnit::MmolL && isset($recordData['glucose_value'])) {
            // @phpstan-ignore nullCoalesce.offset,cast.double
            $glucoseValue = (float) ($recordData['glucose_value'] ?? 0);
            $recordData['glucose_value'] = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        $this->recordHealthEntry->handle($recordData, HealthEntrySource::Web);

        return back()->with('success', 'Health entry recorded successfully.');
    }
}
