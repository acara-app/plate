<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\DeleteHealthSampleAction;
use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class DestroyHealthEntryController
{
    public function __construct(
        private DeleteHealthSampleAction $deleteHealthSample,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateUserUtcDates,
    ) {}

    public function __invoke(HealthSyncSample $healthSyncSample, #[CurrentUser] User $user): JsonResponse
    {
        Gate::authorize('delete', $healthSyncSample);

        $affectedUtcDates = $healthSyncSample->groupUtcDates();
        $localDate = $healthSyncSample->measured_at->toImmutable()->setTimezone($user->resolveTimezone())->toDateString();

        $this->deleteHealthSample->handle($healthSyncSample);
        $this->dispatchAggregateUserUtcDates->handle($user, $affectedUtcDates);

        return response()->json(['date' => $localDate]);
    }
}
