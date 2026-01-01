<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\RecordDiabetesLogAction;
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

        $this->recordDiabetesLog->handle(
            collect($data + ['user_id' => $this->currentUser->id])->except('log_type')->toArray(),
        );

        return back()->with('success', 'Diabetes log entry recorded successfully.');
    }
}
