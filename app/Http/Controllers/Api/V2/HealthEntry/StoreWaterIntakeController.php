<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\GetDailyWellnessAction;
use App\Actions\RecordWaterIntakeAction;
use App\Http\Requests\StoreWaterIntakeRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class StoreWaterIntakeController
{
    public function __construct(
        private RecordWaterIntakeAction $recordWaterIntake,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        private GetDailyWellnessAction $getDailyWellness,
    ) {}

    public function __invoke(StoreWaterIntakeRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $day = $request->selectedDay();

        $this->dispatchAggregateUserUtcDates->handle(
            $user,
            $this->recordWaterIntake->handle($user, $day, $request->amountMl()),
        );

        return response()->json(['wellness' => $this->getDailyWellness->handle($user, $day)->toArray()]);
    }
}
