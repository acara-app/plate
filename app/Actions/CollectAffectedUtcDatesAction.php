<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;

final readonly class CollectAffectedUtcDatesAction
{
    /**
     * @param  array<string, true>  $affectedUtcDates  Accumulator map (mutated by reference).
     */
    public function handle(
        CarbonImmutable $start,
        ?CarbonImmutable $end,
        array &$affectedUtcDates,
    ): void {
        $rangeStart = $start->copy()->utc()->startOfDay();
        $rangeEnd = ($end ?? $start)->copy()->utc()->startOfDay();

        if ($rangeEnd->lt($rangeStart)) {
            $rangeEnd = $rangeStart;
        }

        $current = $rangeStart;

        while ($current->lte($rangeEnd)) {
            $affectedUtcDates[$current->toDateString()] = true;
            $current = $current->addDay();
        }
    }
}
