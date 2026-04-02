<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DeleteHealthEntryAction;
use App\Models\HealthEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class DestroyHealthEntryController
{
    public function __construct(
        private DeleteHealthEntryAction $deleteHealthEntry,
    ) {}

    public function __invoke(HealthEntry $healthEntry): RedirectResponse
    {
        Gate::authorize('delete', $healthEntry);

        $this->deleteHealthEntry->handle($healthEntry);

        return back()->with('success', 'Health entry deleted successfully.');
    }
}
