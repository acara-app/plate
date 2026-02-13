<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\DeleteHealthEntryAction;
use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class DestroyHealthEntryController
{
    public function __construct(
        private DeleteHealthEntryAction $deleteHealthEntry,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(HealthEntry $healthEntry): RedirectResponse
    {
        $user = $this->currentUser;

        abort_if($healthEntry->user_id !== $user->id, 403);

        $this->deleteHealthEntry->handle($healthEntry);

        return back()->with('success', 'Health entry deleted successfully.');
    }
}
