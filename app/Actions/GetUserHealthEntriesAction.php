<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GlucoseUnit;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
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
    public function handle(User $user, CarbonImmutable $day, MealPhotoRoute $photoRoute = MealPhotoRoute::Web, GlucoseUnit $glucoseUnit = GlucoseUnit::MgDl, bool $includeSyncedExerciseMinutes = true): Collection
    {
        $timezone = $day->getTimezone()->getName();
        $start = $day->startOfDay();

        $samples = $user->healthSyncSamples()
            ->logbookEntries()
            ->where('measured_at', '>=', $start->utc())
            ->where('measured_at', '<', $start->addDay()->utc())
            ->when(! $includeSyncedExerciseMinutes, fn (Builder $query): Builder => $query
                ->where(fn (Builder $entries): Builder => $entries
                    ->where('type_identifier', '!=', HealthSyncType::ExerciseMinutes->value)
                    ->orWhere('entry_source', '!=', HealthEntrySource::MobileSync->value)
                    ->orWhereNull('entry_source')))
            ->get();

        return $this->assembler->assemble($samples, $timezone, $photoRoute, $glucoseUnit);
    }
}
