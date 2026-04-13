<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthAggregationFunction;
use App\Models\HealthDailyAggregate;
use App\Models\SleepSession;
use App\Models\User;
use App\Services\HealthMetricRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class SleepSessionAggregator
{
    private const array STAGE_TO_TYPE = [
        SleepSession::STAGE_ASLEEP_CORE => 'coreSleep',
        SleepSession::STAGE_ASLEEP_DEEP => 'deepSleep',
        SleepSession::STAGE_ASLEEP_REM => 'remSleep',
        SleepSession::STAGE_AWAKE => 'awakeTime',
        SleepSession::STAGE_IN_BED => 'timeInBed',
    ];

    public function handle(User $user, CarbonImmutable $utcDate): int
    {
        $utcDate = $utcDate->copy()->utc()->startOfDay();
        $utcDateString = $utcDate->toDateString();

        $dayStart = $utcDate;
        $dayEnd = $dayStart->copy()->addDay();

        /** @var Collection<int, SleepSession> $sessions */
        $sessions = $user->sleepSessions()
            ->where('started_at', '<', $dayEnd)
            ->where('ended_at', '>', $dayStart)
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $durationsByStage = [];
        $sessionsContributing = 0;

        foreach ($sessions as $session) {
            $sessionStart = $session->started_at->copy()->utc();
            $sessionEnd = $session->ended_at->copy()->utc();

            if ($sessionEnd->lte($dayStart) || $sessionStart->gte($dayEnd)) {
                continue;
            }

            $overlapStart = $sessionStart->gt($dayStart) ? $sessionStart : $dayStart;
            $overlapEnd = $sessionEnd->lt($dayEnd) ? $sessionEnd : $dayEnd;
            $overlapSeconds = $overlapStart->diffInSeconds($overlapEnd, false);

            if ($overlapSeconds <= 0) {
                continue;
            }

            $sessionsContributing++;
            $hours = $overlapSeconds / 3600;
            $stage = $session->stage;

            $durationsByStage[$stage] = ($durationsByStage[$stage] ?? 0.0) + $hours;
        }

        if ($durationsByStage === []) {
            return 0;
        }

        $totalAsleep = 0.0;

        foreach ($durationsByStage as $stage => $hours) {
            if (in_array($stage, [
                SleepSession::STAGE_ASLEEP_CORE,
                SleepSession::STAGE_ASLEEP_DEEP,
                SleepSession::STAGE_ASLEEP_REM,
                SleepSession::STAGE_ASLEEP_UNSPECIFIED,
            ], true)) {
                $totalAsleep += $hours;
            }
        }

        $rows = [];

        foreach (self::STAGE_TO_TYPE as $stage => $typeIdentifier) {
            $hours = $durationsByStage[$stage] ?? 0.0;

            if ($hours <= 0.0) {
                continue;
            }

            $rows[] = $this->buildRow($user, $utcDateString, $typeIdentifier, $hours, $sessionsContributing);
        }

        if ($totalAsleep > 0.0) {
            $rows[] = $this->buildRow($user, $utcDateString, 'timeAsleep', $totalAsleep, $sessionsContributing);
        }

        if ($rows === []) {
            // @codeCoverageIgnoreStart
            return 0;
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
                'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRow(
        User $user,
        string $utcDateString,
        string $typeIdentifier,
        float $hours,
        int $sessionCount,
    ): array {
        $now = now();
        $rounded = round($hours, 4);

        return [
            'user_id' => $user->id,
            'date' => $utcDateString,
            HealthDailyAggregate::UTC_DAY_COLUMN => $utcDateString,
            'timezone' => 'UTC',
            'type_identifier' => $typeIdentifier,
            'value_sum' => $rounded,
            'value_sum_canonical' => $rounded,
            'value_avg' => $rounded,
            'value_min' => $rounded,
            'value_max' => $rounded,
            'value_last' => $rounded,
            'value_count' => $sessionCount,
            'source_primary' => null,
            'unit' => 'hours',
            'canonical_unit' => 'hours',
            'aggregation_function' => HealthAggregationFunction::Last->value,
            'aggregation_version' => HealthMetricRegistry::CURRENT_AGGREGATION_VERSION,
            'metadata' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
