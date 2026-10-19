<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Actions\GetHealthEntryAction;
use App\Enums\MealPhotoRoute;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final readonly class ShowHealthEntryController
{
    public function __construct(private GetHealthEntryAction $getHealthEntry) {}

    public function __invoke(HealthSyncSample $healthSyncSample, #[CurrentUser] User $user): JsonResponse
    {
        Gate::authorize('view', $healthSyncSample);

        abort_unless($healthSyncSample->isLogbookEntry(), 404);

        $timezone = $user->resolveTimezone();

        return response()->json([
            'entry' => $this->getHealthEntry->handle($healthSyncSample, $timezone, MealPhotoRoute::Api),
            'date' => $healthSyncSample->measured_at->toImmutable()->setTimezone($timezone)->toDateString(),
            'glucose_unit' => $user->glucoseUnit()->value,
        ]);
    }
}
