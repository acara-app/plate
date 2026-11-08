<?php

declare(strict_types=1);

namespace App\Utilities;

final class Histogram
{
    /**
     * @param  array<int, int>  $counts
     * @return array<int, int>
     */
    public static function add(array $counts, float $value): array
    {
        $bucket = (int) round($value);
        $counts[$bucket] = ($counts[$bucket] ?? 0) + 1;

        return $counts;
    }

    /**
     * @param  array<int, int>  $counts
     * @param  array<int, int>  $more
     * @return array<int, int>
     */
    public static function merge(array $counts, array $more): array
    {
        foreach ($more as $bucket => $count) {
            $counts[$bucket] = ($counts[$bucket] ?? 0) + $count;
        }

        return $counts;
    }

    /**
     * @param  array<int, int>  $counts
     */
    public static function median(array $counts): ?float
    {
        ksort($counts);
        $values = [];

        foreach ($counts as $bucket => $count) {
            array_push($values, ...array_fill(0, $count, $bucket));
        }

        if ($values === []) {
            return null;
        }

        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1 ? (float) $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
