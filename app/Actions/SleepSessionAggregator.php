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

    public function handle(User $user, CarbonImmutable $nightDate): int
    {
        $fallbackTz = $user->resolveTimezone();
        $localDateString = $nightDate->toDateString();

        $nightStart = $nightDate->copy()->setTimezone($fallbackTz)->setTime(12, 0)->utc();
        $nightEnd = $nightDate->copy()->setTimezone($fallbackTz)->addDay()->setTime(12, 0)->utc();

        /** @var Collection<int, SleepSession> $sessions */
        $sessions = $user->sleepSessions()
            ->where('started_at', '>=', $nightStart)
            ->where('started_at', '<', $nightEnd)
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $durationsByStage = [];

        foreach ($sessions as $session) {
            $stage = $session->stage;
            $hours = $session->durationHours();

            $durationsByStage[$stage] = ($durationsByStage[$stage] ?? 0.0) + $hours;
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

            $rows[] = $this->buildRow($user, $localDateString, $fallbackTz, $typeIdentifier, $hours, $sessions->count());
        }

        if ($totalAsleep > 0.0) {
            $rows[] = $this->buildRow($user, $localDateString, $fallbackTz, 'timeAsleep', $totalAsleep, $sessions->count());
        }

        if ($rows === []) {
            return 0;
        }

        HealthDailyAggregate::query()->upsert(
            $rows,
            ['user_id', 'local_date', 'type_identifier'],
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
        string $localDateString,
        string $fallbackTz,
        string $typeIdentifier,
        float $hours,
        int $sessionCount,
    ): array {
        $now = now();
        $rounded = round($hours, 4);

        return [
            'user_id' => $user->id,
            'date' => $localDateString,
            'local_date' => $localDateString,
            'timezone' => $fallbackTz,
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
