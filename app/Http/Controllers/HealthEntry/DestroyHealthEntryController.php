<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DeleteHealthSampleAction;
use App\Models\HealthSyncSample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class DestroyHealthEntryController
{
    public function __construct(
        private DeleteHealthSampleAction $deleteHealthSample,
    ) {}

    public function __invoke(HealthSyncSample $healthSyncSample): RedirectResponse
    {
        Gate::authorize('delete', $healthSyncSample);

        $this->deleteHealthSample->handle($healthSyncSample);

        return back()->with('success', 'Health entry deleted successfully.');
    }
}
