<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\GetUserHealthEntriesAction;
use App\Actions\GetUserLoggedDatesAction;
use App\Http\Layouts\DiabetesLayout;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ListHealthEntryController
{
    public function __construct(
        private GetUserHealthEntriesAction $getUserHealthEntries,
        private GetUserLoggedDatesAction $getUserLoggedDates,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(ListHealthEntriesRequest $request): Response
    {
        $timezone = $this->currentUser->resolveTimezone();
        $day = $request->selectedDay($timezone);
        $weekStart = $day->startOfWeek(CarbonInterface::MONDAY);

        return Inertia::render('health-entries/index', [
            'date' => $day->toDateString(),
            'today' => CarbonImmutable::now($timezone)->toDateString(),
            'entries' => fn (): array => $this->getUserHealthEntries->handle($this->currentUser, $day)->all(),
            'loggedDates' => fn (): array => $this->getUserLoggedDates->handle($this->currentUser, $weekStart, $weekStart->addDays(6)),
            ...DiabetesLayout::props($this->currentUser),
        ]);
    }
}
