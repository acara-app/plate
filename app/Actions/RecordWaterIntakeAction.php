<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthEntrySource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RecordWaterIntakeAction
{
    public const string WATER_TYPE = 'water';

    public const string WATER_UNIT = 'mL';

    public function handle(User $user, CarbonImmutable $day, int $amountMl): CarbonImmutable
    {
        $measuredAt = $day->startOfDay()->utc();
        $attributes = [
            'type_identifier' => self::WATER_TYPE,
            'measured_at' => $measuredAt,
            'entry_source' => HealthEntrySource::Web,
        ];

        DB::transaction(function () use ($user, $attributes, $amountMl): void {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($amountMl === 0) {
                $user->healthSyncSamples()->where($attributes)->delete();

                return;
            }

            $user->healthSyncSamples()->updateOrCreate($attributes, [
                'value' => $amountMl,
                'unit' => self::WATER_UNIT,
            ]);
        });

        return $measuredAt;
    }
}
