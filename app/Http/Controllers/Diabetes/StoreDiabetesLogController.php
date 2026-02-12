<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\RecordDiabetesLogAction;
use App\Enums\GlucoseUnit;
use App\Http\Requests\StoreDiabetesLogRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class StoreDiabetesLogController
{
    public function __construct(
        private RecordDiabetesLogAction $recordDiabetesLog,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(StoreDiabetesLogRequest $request): RedirectResponse
    {
        $data = $request->validated();

        /** @var array<string, mixed> $recordData */
        $recordData = collect($data + ['user_id' => $this->currentUser->id])->except('log_type')->toArray();

        $glucoseUnit = $this->currentUser->profile?->units_preference ?? GlucoseUnit::MmolL;
        if ($glucoseUnit === GlucoseUnit::MmolL && isset($recordData['glucose_value'])) {
            $recordData['glucose_value'] = GlucoseUnit::mmolLToMgDl((float) $recordData['glucose_value']);
        }

        $this->recordDiabetesLog->handle($recordData);

        return back()->with('success', 'Diabetes log entry recorded successfully.');
    }
}
