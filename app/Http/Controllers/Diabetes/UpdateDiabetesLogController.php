<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Actions\UpdateDiabetesLogAction;
use App\Http\Requests\UpdateDiabetesLogRequest;
use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class UpdateDiabetesLogController
{
    public function __construct(
        private UpdateDiabetesLogAction $updateDiabetesLog,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(UpdateDiabetesLogRequest $request, DiabetesLog $diabetesLog): RedirectResponse
    {
        abort_if($diabetesLog->user_id !== $this->currentUser->id, 403);

        $data = $request->validated();

        /** @var array<string, mixed> $updateData */
        $updateData = collect($data)->except('log_type')->toArray();

        $this->updateDiabetesLog->handle(
            diabetesLog: $diabetesLog,
            data: $updateData
        );

        return back()->with('success', 'Diabetes log entry updated successfully.');
    }
}
