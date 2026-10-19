<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DailyCheckInMetric;
use App\Enums\HealthEntrySource;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RecordDailyCheckInAction
{
    public function handle(
        User $user,
        CarbonImmutable $day,
        ?float $sleepHours,
        ?SleepQuality $sleepQuality,
        ?StressLevel $stressLevel,
    ): CarbonImmutable {
        $measuredAt = $day->startOfDay()->utc();

        $answers = [
            [DailyCheckInMetric::SleepHours, $sleepHours],
            [DailyCheckInMetric::SleepQuality, $sleepQuality?->value],
            [DailyCheckInMetric::StressLevel, $stressLevel?->value],
        ];

        DB::transaction(function () use ($user, $measuredAt, $answers): void {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            foreach ($answers as [$metric, $value]) {
                if ($value === null) {
                    continue;
                }

                $user->healthSyncSamples()->updateOrCreate(
                    [
                        'type_identifier' => $metric->value,
                        'measured_at' => $measuredAt,
                        'entry_source' => HealthEntrySource::Web,
                    ],
                    [
                        'value' => $value,
                        'unit' => $metric->unit(),
                    ],
                );
            }
        });

        return $measuredAt;
    }
}
