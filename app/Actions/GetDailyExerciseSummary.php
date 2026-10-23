<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\DailyExerciseSummaryData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\CumulativeSampleDeduplicator;
use App\Services\HealthMetricRegistry;
use Carbon\CarbonImmutable;

final readonly class GetDailyExerciseSummary
{
    public function __construct(
        private HealthMetricRegistry $registry,
        private CumulativeSampleDeduplicator $deduplicator,
    ) {}

    public function handle(User $user, CarbonImmutable $day): ?DailyExerciseSummaryData
    {
        $samples = $user->healthSyncSamples()
            ->ofType(HealthSyncType::ExerciseMinutes)
            ->forEntrySource(HealthEntrySource::MobileSync)
            ->measuredWithinDay($day->startOfDay())
            ->get();

        if ($samples->isEmpty()) {
            return null;
        }

        $sourcePreference = $this->registry->descriptorOrUnknown(HealthSyncType::ExerciseMinutes->value)->sourcePreference;
        $totalMinutes = $this->deduplicator->handle($samples, $sourcePreference)
            ->sum(static fn (HealthSyncSample $sample): float => $sample->value);

        return new DailyExerciseSummaryData(
            date: $day->toDateString(),
            totalMinutes: round($totalMinutes, 1),
        );
    }
}
