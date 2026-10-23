<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GlucoseUnit;
use App\Enums\MealPhotoRoute;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class GetUserHealthEntriesAction
{
    public function __construct(private HealthEntryAssembler $assembler) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(User $user, CarbonImmutable $day, MealPhotoRoute $photoRoute = MealPhotoRoute::Web, GlucoseUnit $glucoseUnit = GlucoseUnit::MgDl, bool $includeSyncedExerciseMinutes = false): Collection
    {
        $timezone = $day->getTimezone()->getName();

        $samples = $user->healthSyncSamples()
            ->logbookEntries()
            ->measuredWithinDay($day->startOfDay())
            ->unless($includeSyncedExerciseMinutes, fn (Builder $query): Builder => $query->excludingSyncedExerciseMinutes())
            ->get();

        return $this->assembler->assemble($samples, $timezone, $photoRoute, $glucoseUnit);
    }
}
