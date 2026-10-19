<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\GetHealthEntryAction;
use App\Actions\UpdateHealthSampleAction;
use App\Enums\MealPhotoRoute;
use App\Http\Requests\HealthEntryRequest;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class UpdateHealthEntryController
{
    public function __construct(
        private UpdateHealthSampleAction $updateHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
        private GetHealthEntryAction $getHealthEntry,
    ) {}

    public function __invoke(HealthEntryRequest $request, HealthSyncSample $healthSyncSample, #[CurrentUser] User $user): JsonResponse
    {
        Gate::authorize('update', $healthSyncSample);

        abort_unless($healthSyncSample->isLogbookEntry(), 404);

        $previousUtcDate = $healthSyncSample->measured_at->copy()->utc()->toDateString();
        $metadata = HealthSyncSample::firstMetadataIn($healthSyncSample->groupMembers(), 'snap_to_track');

        $updatedSample = $this->updateHealthSample->handle(
            $healthSyncSample,
            $request->healthLogData(is_array($metadata) ? $metadata : []),
        );

        $this->dispatchAggregateUserUtcDates->handle($user, [
            $previousUtcDate,
            $updatedSample->measured_at->copy()->utc()->toDateString(),
        ]);

        $timezone = $user->resolveTimezone();

        return response()->json([
            'entry' => $this->getHealthEntry->handle($updatedSample, $timezone, MealPhotoRoute::Api),
            'date' => $updatedSample->measured_at->toImmutable()->setTimezone($timezone)->toDateString(),
            'glucose_unit' => $user->glucoseUnit()->value,
        ]);
    }
}
