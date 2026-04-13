<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\AggregateUserDayJob;
use App\Models\User;

final readonly class DispatchAggregateUserUtcDatesAction
{
    /**
     * @param  list<string>  $utcDates
     */
    public function handle(User $user, array $utcDates): int
    {
        $utcDates = collect($utcDates)
            ->filter(static fn (string $date): bool => $date !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach ($utcDates as $utcDate) {
            dispatch(new AggregateUserDayJob($user->id, $utcDate))->afterCommit();
        }

        return count($utcDates);
    }
}
