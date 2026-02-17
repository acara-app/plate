<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\UpdateHealthEntryAction;
use App\Enums\GlucoseUnit;
use App\Http\Requests\UpdateHealthEntryRequest;
use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateHealthEntryController
{
    public function __construct(
        private UpdateHealthEntryAction $updateHealthEntry,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(UpdateHealthEntryRequest $request, HealthEntry $healthEntry): RedirectResponse
    {
        abort_if($healthEntry->user_id !== $this->currentUser->id, 403);

        $data = $request->validated();

        /** @var array<string, mixed> $updateData */
        $updateData = collect($data)->except('log_type')->toArray();

        // @phpstan-ignore nullsafe.neverNull
        $glucoseUnit = $this->currentUser->profile?->units_preference ?? GlucoseUnit::MmolL;
        if ($glucoseUnit === GlucoseUnit::MmolL && isset($updateData['glucose_value'])) {
            // @phpstan-ignore nullCoalesce.offset,cast.double
            $glucoseValue = (float) ($updateData['glucose_value'] ?? 0);
            $updateData['glucose_value'] = GlucoseUnit::mmolLToMgDl($glucoseValue);
        }

        $this->updateHealthEntry->handle(
            healthEntry: $healthEntry,
            data: $updateData
        );

        return back()->with('success', 'Health entry updated successfully.');
    }
}
