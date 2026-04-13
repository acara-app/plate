<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthAggregationFunction;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\HealthMetricRegistry;
use App\ValueObjects\HealthMetricDescriptorData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class AggregateHealthDailySamplesAction
{
    private const int CUMULATIVE_BUCKET_SECONDS = 300;

    public function __construct(
        private HealthMetricRegistry $registry,
    ) {}

    public function handle(User $user, CarbonImmutable $utcDate): int
    {
        $utcDate = $utcDate->copy()->utc()->startOfDay();
        $utcDateString = $utcDate->toDateString();

        $samples = $this->loadSamplesForUtcDay($user, $utcDate);

        if ($samples->isEmpty()) {
            return 0;
        }

        $upserted = 0;

        DB::transaction(function () use ($samples, $user, $utcDateString, &$upserted): void {
            $rows = [];

            foreach ($samples->groupBy('type_identifier') as $typeIdentifier => $typeSamples) {
                $descriptor = $this->registry->descriptorOrUnknown((string) $typeIdentifier);
                /** @var Collection<int, HealthSyncSample> $typeSamples */
                $row = $this->aggregateOneType(
                    descriptor: $descriptor,
                    samples: $typeSamples,
                    user: $user,
                    utcDateString: $utcDateString,
                );

                if ($row !== null) {
                    $rows[] = $row;
                }
            }

            if ($rows === []) {
                // @codeCoverageIgnoreStart
                return;
                // @codeCoverageIgnoreEnd
            }

            HealthDailyAggregate::query()->upsert(
                $rows,
                ['user_id', HealthDailyAggregate::UTC_DAY_COLUMN, 'type_identifier'],
                [
                    'date',
                    'timezone',
                    'value_sum',
                    'value_sum_canonical',
                    'value_avg',
                    'value_min',
                    'value_max',
                    'value_last',
                    'value_count',
                    'source_primary',
                    'unit',
                    'canonical_unit',
                    'aggregation_function',
                    'aggregation_version',
                    'metadata',
                    'updated_at',
                ],
            );

            $upserted = count($rows);
        });

        return $upserted;
    }

    public function handleDateRange(User $user, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $total = 0;
        $current = $from->copy()->utc()->startOfDay();
        $to = $to->copy()->utc()->startOfDay();

        while ($current->lte($to)) {
            $total += $this->handle($user, $current);
            $current = $current->addDay();
        }

        return $total;
    }

    // @codeCoverageIgnoreStart
    public function handleAllUsers(CarbonImmutable $utcDate): int
    {
        $total = 0;

        $rangeStart = $utcDate->copy()->utc()->startOfDay();
        $rangeEnd = $rangeStart->copy()->addDay();

        $userIds = DB::table('health_sync_samples')
            ->select('user_id')
            ->where('measured_at', '>=', $rangeStart)
            ->where('measured_at', '<', $rangeEnd)
            ->distinct()
            ->pluck('user_id');

        foreach ($userIds as $userId) {
            /** @var User|null $user */
            $user = User::query()->find($userId);

            if ($user === null) {
                continue;
            }

            $total += $this->handle($user, $rangeStart);
        }

        return $total;
    }

    // @codeCoverageIgnoreEnd

    /**
     * @return Collection<int, HealthSyncSample>
     */
    private function loadSamplesForUtcDay(User $user, CarbonImmutable $utcDate): Collection
    {
        $rangeStart = $utcDate->copy()->utc()->startOfDay();
        $rangeEnd = $rangeStart->copy()->addDay();

        return $user->healthSyncSamples()
            ->where('measured_at', '>=', $rangeStart)
            ->where('measured_at', '<', $rangeEnd)
            ->get();
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array<string, mixed>|null
     */
    private function aggregateOneType(
        HealthMetricDescriptorData $descriptor,
        Collection $samples,
        User $user,
        string $utcDateString,
    ): ?array {
        if ($descriptor->function === HealthAggregationFunction::None) {
            $lastSample = $samples->sortByDesc(
                static fn (HealthSyncSample $s): int => $s->measured_at->getTimestamp()
            )->first();

            return $this->buildRow(
                descriptor: $descriptor,
                user: $user,
                utcDateString: $utcDateString,
                scalars: [
                    'value_sum' => null,
                    'value_sum_canonical' => null,
                    'value_avg' => null,
                    'value_min' => null,
                    'value_max' => null,
                    'value_last' => null,
                    'value_count' => $samples->count(),
                    'source_primary' => $lastSample?->source,
                ],
                metadata: null,
                aggregationFunction: HealthAggregationFunction::None,
            );
        }

        if ($descriptor->isCumulative() && $descriptor->sourcePreference !== []) {
            $samples = $this->intervalDedupBySource($samples, $descriptor->sourcePreference);
        }

        if ($samples->isEmpty()) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $sortedDesc = $samples->sortByDesc(
            static fn (HealthSyncSample $s): int => $s->measured_at->getTimestamp()
        )->values();
        $lastSample = $sortedDesc->first();

        $valuesNumeric = $samples->map(static fn (HealthSyncSample $s): float => (float) $s->value);

        /** @var float $sum */
        $sum = $valuesNumeric->sum();
        /** @var float $avg */
        $avg = $valuesNumeric->avg() ?? 0;
        /** @var float $min */
        $min = $valuesNumeric->min() ?? 0;
        /** @var float $max */
        $max = $valuesNumeric->max() ?? 0;

        $scalars = [
            'value_sum' => round($sum, 4),
            'value_sum_canonical' => round($sum, 4),
            'value_avg' => round($avg, 4),
            'value_min' => round($min, 4),
            'value_max' => round($max, 4),
            'value_last' => $lastSample !== null ? round((float) $lastSample->value, 4) : null,
            'value_count' => $samples->count(),
            'source_primary' => $lastSample?->source,
        ];

        $metadata = null;
        if ($descriptor->isEvent()) {
            $metadata = $this->buildEventMetadata($samples);
        }

        return $this->buildRow(
            descriptor: $descriptor,
            user: $user,
            utcDateString: $utcDateString,
            scalars: $scalars,
            metadata: $metadata,
            aggregationFunction: $descriptor->function,
        );
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @param  list<string>  $priority
     * @return Collection<int, HealthSyncSample>
     */
    private function intervalDedupBySource(Collection $samples, array $priority): Collection
    {
        if ($samples->isEmpty()) {
            // @codeCoverageIgnoreStart
            return $samples;
            // @codeCoverageIgnoreEnd
        }

        $buckets = [];

        foreach ($samples as $sample) {
            $startEpoch = $sample->measured_at->getTimestamp();
            $startBucket = (int) floor($startEpoch / self::CUMULATIVE_BUCKET_SECONDS);

            $endBucket = $startBucket;
            if ($sample->ended_at !== null) {
                // @codeCoverageIgnoreStart
                $endEpoch = $sample->ended_at->getTimestamp();
                $endBucket = (int) floor($endEpoch / self::CUMULATIVE_BUCKET_SECONDS);
                // @codeCoverageIgnoreEnd
            }

            for ($b = $startBucket; $b <= $endBucket; $b++) {
                $buckets[$b] ??= [];
                $buckets[$b][] = $sample;
            }
        }

        $keptIds = [];

        foreach ($buckets as $group) {
            $bestRank = PHP_INT_MAX;
            $bestSamples = [];

            foreach ($group as $sample) {
                $rank = $this->sourceRank($sample->source, $priority);

                if ($rank < $bestRank) {
                    $bestRank = $rank;
                    $bestSamples = [$sample];
                } elseif ($rank === $bestRank) {
                    $bestSamples[] = $sample;
                }
            }

            foreach ($bestSamples as $sample) {
                $id = spl_object_id($sample);
                $keptIds[$id] ??= $sample;
            }
        }

        return new Collection(array_values($keptIds));
    }

    /**
     * @param  list<string>  $priority
     */
    private function sourceRank(?string $source, array $priority): int
    {
        // @codeCoverageIgnoreStart
        if ($source === null || $source === '') {
            return PHP_INT_MAX;
        }

        // @codeCoverageIgnoreEnd

        foreach ($priority as $i => $preferredSubstring) {
            if (str_contains($source, $preferredSubstring)) {
                return $i + 1;
            }
        }

        // @codeCoverageIgnoreStart
        return PHP_INT_MAX;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $samples
     * @return array<int, array<string, mixed>>
     */
    private function buildEventMetadata(Collection $samples): array
    {
        return $samples
            ->sortBy(static fn (HealthSyncSample $s): int => $s->measured_at->getTimestamp())
            ->map(static fn (HealthSyncSample $s): array => [
                'id' => $s->id,
                'measured_at' => $s->measured_at->toIso8601String(),
                'value' => (float) $s->value,
                'metadata' => $s->metadata ?? [],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array{value_sum: float|null, value_sum_canonical: float|null, value_avg: float|null, value_min: float|null, value_max: float|null, value_last: float|null, value_count: int, source_primary: string|null}  $scalars
     * @param  array<int, array<string, mixed>>|null  $metadata
     * @return array<string, mixed>
     */
    private function buildRow(
        HealthMetricDescriptorData $descriptor,
        User $user,
        string $utcDateString,
        array $scalars,
        ?array $metadata,
        HealthAggregationFunction $aggregationFunction,
    ): array {
        $now = now();

        return [
            'user_id' => $user->id,
            'date' => $utcDateString,
            HealthDailyAggregate::UTC_DAY_COLUMN => $utcDateString,
            'timezone' => 'UTC',
            'type_identifier' => $descriptor->identifier,
            'value_sum' => $scalars['value_sum'],
            'value_sum_canonical' => $scalars['value_sum_canonical'],
            'value_avg' => $scalars['value_avg'],
            'value_min' => $scalars['value_min'],
            'value_max' => $scalars['value_max'],
            'value_last' => $scalars['value_last'],
            'value_count' => $scalars['value_count'],
            'source_primary' => $scalars['source_primary'],
            'unit' => $descriptor->displayUnit !== '' ? $descriptor->displayUnit : null,
            'canonical_unit' => $descriptor->canonicalUnit !== '' ? $descriptor->canonicalUnit : null,
            'aggregation_function' => $aggregationFunction->value,
            'aggregation_version' => HealthMetricRegistry::CURRENT_AGGREGATION_VERSION,
            'metadata' => $metadata !== null ? json_encode($metadata) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
