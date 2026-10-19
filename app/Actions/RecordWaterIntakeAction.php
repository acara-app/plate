<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RecordWaterIntakeAction
{
    public const string WATER_TYPE = 'water';

    public const string WATER_UNIT = 'mL';

    /**
     * @return list<string>
     */
    public function handle(User $user, CarbonImmutable $day, int $amountMl): array
    {
        $start = $day->startOfDay();

        return DB::transaction(function () use ($user, $start, $amountMl): array {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $logged = $user->healthSyncSamples()
                ->where('type_identifier', self::WATER_TYPE)
                ->where('entry_source', HealthEntrySource::Web)
                ->where('measured_at', '>=', $start->utc())
                ->where('measured_at', '<', $start->addDay()->utc())
                ->get();

            $affectedUtcDates = array_values(array_unique([
                $start->utc()->toDateString(),
                ...$logged->toBase()->map(static fn (HealthSyncSample $sample): string => $sample->measured_at->copy()->utc()->toDateString())->all(),
            ]));

            $user->healthSyncSamples()->whereKey($logged->modelKeys())->delete();

            if ($amountMl > 0) {
                $user->healthSyncSamples()->create([
                    'type_identifier' => self::WATER_TYPE,
                    'measured_at' => $start->utc(),
                    'entry_source' => HealthEntrySource::Web,
                    'value' => $amountMl,
                    'unit' => self::WATER_UNIT,
                ]);
            }

            $this->forgetEmptyWaterTotals($user, $affectedUtcDates);

            return $affectedUtcDates;
        });
    }

    /**
     * @param  list<string>  $utcDates
     */
    private function forgetEmptyWaterTotals(User $user, array $utcDates): void
    {
        foreach ($utcDates as $utcDate) {
            $utcDay = CarbonImmutable::parse($utcDate, 'UTC');

            $hasWater = $user->healthSyncSamples()
                ->where('type_identifier', self::WATER_TYPE)
                ->where('measured_at', '>=', $utcDay)
                ->where('measured_at', '<', $utcDay->addDay())
                ->exists();

            if (! $hasWater) {
                $user->healthDailyAggregates()
                    ->where(HealthDailyAggregate::UTC_DAY_COLUMN, $utcDate)
                    ->where('type_identifier', self::WATER_TYPE)
                    ->delete();
            }
        }
    }
}
