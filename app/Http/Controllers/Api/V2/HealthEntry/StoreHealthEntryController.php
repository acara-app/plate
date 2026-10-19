<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\GetHealthEntryAction;
use App\Actions\RecordHealthSampleAction;
use App\Enums\HealthEntrySource;
use App\Enums\MealPhotoRoute;
use App\Http\Requests\HealthEntryRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class StoreHealthEntryController
{
    public function __construct(
        private RecordHealthSampleAction $recordHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        private GetHealthEntryAction $getHealthEntry,
    ) {}

    public function __invoke(HealthEntryRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $sample = $this->recordHealthSample->handle($request->healthLogData(), $user, HealthEntrySource::Mobile);

        $this->dispatchAggregateUserUtcDates->handle($user, [$sample->measured_at->copy()->utc()->toDateString()]);

        $timezone = $user->resolveTimezone();

        return response()->json([
            'entry' => $this->getHealthEntry->handle($sample, $timezone, MealPhotoRoute::Api),
            'date' => $sample->measured_at->toImmutable()->setTimezone($timezone)->toDateString(),
            'glucose_unit' => $user->glucoseUnit()->value,
        ], 201);
    }
}
