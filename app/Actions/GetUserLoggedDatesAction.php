<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\HealthSyncType;
use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class GetUserLoggedDatesAction
{
    /**
     * @return list<string>
     */
    public function handle(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $timezone = $from->getTimezone()->getName();

        $dates = $user->healthSyncSamples()
            ->whereIn('type_identifier', HealthSyncType::entryTypeValues())
            ->where('measured_at', '>=', $from->startOfDay()->utc())
            ->where('measured_at', '<', $to->startOfDay()->addDay()->utc())
            ->toBase()
            ->pluck('measured_at')
            ->map(fn (mixed $measuredAt): string => CarbonImmutable::parse(is_string($measuredAt) ? $measuredAt : '', 'UTC')->setTimezone($timezone)->toDateString())
            ->unique()
            ->sort()
            ->all();

        return array_values($dates);
    }
}
