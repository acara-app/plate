<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\HealthSyncSample;
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
            ->logbookEntries()
            ->where('measured_at', '>=', $from->startOfDay()->utc())
            ->where('measured_at', '<', $to->startOfDay()->addDay()->utc())
            ->get(['id', 'measured_at'])
            ->map(fn (HealthSyncSample $sample): string => $sample->measured_at->toImmutable()->setTimezone($timezone)->toDateString())
            ->unique()
            ->sort()
            ->all();

        return array_values($dates);
    }
}
