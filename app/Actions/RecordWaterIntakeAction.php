<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthEntrySource;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RecordWaterIntakeAction
{
    public const string WATER_TYPE = 'water';

    private const string WATER_UNIT = 'mL';

    public function __construct(private ForgetEmptyDailyTotalsAction $forgetEmptyDailyTotals) {}

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
                ->forEntrySource(HealthEntrySource::Web)
                ->measuredWithinDay($start)
                ->get();

            $affectedUtcDates = array_values(array_unique([
                $start->utc()->toDateString(),
                ...$logged->toBase()->map(static fn (HealthSyncSample $sample): string => $sample->measured_at->copy()->utc()->toDateString())->all(),
            ]));

            if ($logged->isNotEmpty()) {
                $user->healthSyncSamples()->whereKey($logged->modelKeys())->delete();
            }

            if ($amountMl > 0) {
                $user->healthSyncSamples()->create([
                    'type_identifier' => self::WATER_TYPE,
                    'measured_at' => $start->utc(),
                    'entry_source' => HealthEntrySource::Web,
                    'value' => $amountMl,
                    'unit' => self::WATER_UNIT,
                ]);
            }

            $this->forgetEmptyDailyTotals->handle($user, $affectedUtcDates, [self::WATER_TYPE]);

            return $affectedUtcDates;
        });
    }
}
