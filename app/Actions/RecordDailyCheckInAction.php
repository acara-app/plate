<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DailyCheckInMetric;
use App\Enums\HealthEntrySource;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RecordDailyCheckInAction
{
    public function __construct(private ForgetEmptyDailyTotalsAction $forgetEmptyDailyTotals) {}

    /**
     * @return list<string>
     */
    public function handle(
        User $user,
        CarbonImmutable $day,
        ?float $sleepHours,
        ?SleepQuality $sleepQuality,
        ?StressLevel $stressLevel,
    ): array {
        $start = $day->startOfDay();

        $answers = [
            DailyCheckInMetric::SleepHours->value => $sleepHours,
            DailyCheckInMetric::SleepQuality->value => $sleepQuality?->value,
            DailyCheckInMetric::StressLevel->value => $stressLevel?->value,
        ];

        return DB::transaction(function () use ($user, $start, $answers): array {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $saved = $user->healthSyncSamples()
                ->whereIn('type_identifier', DailyCheckInMetric::values())
                ->forEntrySource(HealthEntrySource::Web)
                ->measuredWithinDay($start)
                ->oldest('updated_at')
                ->oldest('id')
                ->get();

            $latestSaved = $saved->keyBy('type_identifier');

            $affectedUtcDates = array_values(array_unique([
                $start->utc()->toDateString(),
                ...$saved->toBase()->map(static fn (HealthSyncSample $sample): string => $sample->measured_at->copy()->utc()->toDateString())->all(),
            ]));

            if ($saved->isNotEmpty()) {
                $user->healthSyncSamples()->whereKey($saved->modelKeys())->delete();
            }

            foreach (DailyCheckInMetric::cases() as $metric) {
                $value = $answers[$metric->value] ?? $latestSaved->get($metric->value)?->value;

                if ($value === null) {
                    continue;
                }

                $user->healthSyncSamples()->create([
                    'type_identifier' => $metric->value,
                    'measured_at' => $start->utc(),
                    'entry_source' => HealthEntrySource::Web,
                    'value' => $value,
                    'unit' => $metric->unit(),
                ]);
            }

            $this->forgetEmptyDailyTotals->handle($user, $affectedUtcDates, DailyCheckInMetric::values());

            return $affectedUtcDates;
        });
    }
}
