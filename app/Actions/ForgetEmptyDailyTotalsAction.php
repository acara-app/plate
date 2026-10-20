<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;

final readonly class ForgetEmptyDailyTotalsAction
{
    /**
     * @param  list<string>  $utcDates
     * @param  list<string>  $typeIdentifiers
     */
    public function handle(User $user, array $utcDates, array $typeIdentifiers): void
    {
        foreach ($utcDates as $utcDate) {
            $utcDay = CarbonImmutable::parse($utcDate, 'UTC');

            $answeredTypes = $user->healthSyncSamples()
                ->whereIn('type_identifier', $typeIdentifiers)
                ->measuredWithinDay($utcDay)
                ->distinct()
                ->pluck('type_identifier');

            $emptyTypes = array_values(array_filter(
                $typeIdentifiers,
                static fn (string $typeIdentifier): bool => ! $answeredTypes->contains($typeIdentifier),
            ));

            if ($emptyTypes === []) {
                continue;
            }

            $user->healthDailyAggregates()
                ->forDate($utcDay)
                ->whereIn('type_identifier', $emptyTypes)
                ->delete();
        }
    }
}
