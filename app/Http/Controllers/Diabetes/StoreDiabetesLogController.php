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
        $user = $this->currentUser;

        $data = $request->validated();

        $this->recordDiabetesLog->handle(
            $data + ['user_id' => $user->id]
        );

        return back()->with('success', 'Diabetes log entry recorded successfully.');
    }
}
