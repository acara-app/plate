<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\GetUserHealthEntriesAction;
use App\Enums\HealthEntryType;
use App\Http\Layouts\DiabetesLayout;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListHealthEntryController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(ListHealthEntriesRequest $request): Response
    {
        $type = $request->enum('type', HealthEntryType::class);

        return Inertia::render('health-entries/index', [
            'logs' => Inertia::scroll(fn (): LengthAwarePaginator => $this->getUserHealthEntries->handle($this->currentUser, type: $type)),
            'filters' => ['type' => $type?->value],
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
