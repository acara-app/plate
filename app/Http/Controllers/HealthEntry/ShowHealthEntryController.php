<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\GetHealthEntryAction;
use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ShowHealthEntryController
{
    public function __construct(
        private GetHealthEntryAction $getHealthEntry,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthSyncSample $healthSyncSample): Response
    {
        Gate::authorize('view', $healthSyncSample);

        abort_unless($healthSyncSample->isLogbookEntry(), 404);

        $timezone = $this->currentUser->resolveTimezone();

        return Inertia::render('health-entries/show', [
            'entry' => $this->getHealthEntry->handle($healthSyncSample, $timezone),
            'date' => $healthSyncSample->measured_at->toImmutable()->setTimezone($timezone)->toDateString(),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
