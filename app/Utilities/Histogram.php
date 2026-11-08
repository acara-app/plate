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
        $total = array_sum($counts);

        if ($total === 0) {
            return null;
        }

        ksort($counts);

        return (self::valueAt($counts, intdiv($total - 1, 2)) + self::valueAt($counts, intdiv($total, 2))) / 2;
    }

    /**
     * @param  array<int, int>  $counts
     */
    private static function valueAt(array $counts, int $position): int
    {
        $value = 0;

        foreach ($counts as $bucket => $count) {
            $value = $bucket;
            $position -= $count;

            if ($position < 0) {
                break;
            }
        }

        return $value;
    }
}
