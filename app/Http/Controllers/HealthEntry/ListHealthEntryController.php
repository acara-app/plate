<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\GetUserHealthEntriesAction;
use App\Http\Layouts\DiabetesLayout;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListHealthEntryController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('health-entries/index', [
            'logs' => Inertia::scroll(fn (): \Illuminate\Contracts\Pagination\LengthAwarePaginator => $this->getUserHealthEntries->handle($this->currentUser)),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
