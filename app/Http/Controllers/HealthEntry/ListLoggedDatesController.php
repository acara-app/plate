<?php

declare(strict_types=1);

namespace App\Http\Controllers\HealthEntry;

use App\Actions\GetUserLoggedDatesAction;
use App\Http\Requests\ListHealthEntriesRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class ListLoggedDatesController
{
    public function __construct(private GetUserLoggedDatesAction $getUserLoggedDates) {}

    public function __invoke(ListHealthEntriesRequest $request, #[CurrentUser()] User $user): JsonResponse
    {
        $weekStart = $request->selectedWeekStart($user->resolveTimezone());

        return response()->json($this->getUserLoggedDates->handle($user, $weekStart, $weekStart->addDays(6)));
    }
}
