<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GlucoseReadingType;
use App\Models\HealthSyncSample;
use Illuminate\Support\Collection;

final readonly class GlucoseStatisticsService
{
    public const int NORMAL_RANGE_MIN = 70;

    public const int NORMAL_RANGE_MAX = 140;

    public const int HYPOGLYCEMIA_THRESHOLD = 70;

    public const int HYPERGLYCEMIA_THRESHOLD = 140;

    public const int FASTING_NORMAL_MAX = 100;

    public const int FASTING_PREDIABETIC_MAX = 125;

    public const int HIGH_VARIABILITY_STDDEV = 30;

    public const int POST_MEAL_SPIKE_THRESHOLD = 140;

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     * @return array{
     *     timeInRange: float,
     *     timeAboveRange: float,
     *     timeBelowRange: float,
     *     inRangeCount: int,
     *     aboveRangeCount: int,
     *     belowRangeCount: int,
     *     total: int
     * }
     */
    public function calculateTimeInRange(Collection $readings): array
    {
        if ($readings->isEmpty()) {
            return [
                'timeInRange' => 0.0,
                'timeAboveRange' => 0.0,
                'timeBelowRange' => 0.0,
                'inRangeCount' => 0,
                'aboveRangeCount' => 0,
                'belowRangeCount' => 0,
                'total' => 0,
            ];
        }

        // @codeCoverageIgnoreStart
        $total = $readings->count();
        $inRangeCount = $readings->filter(
            fn (HealthSyncSample $r): bool => $r->value >= self::NORMAL_RANGE_MIN
                && $r->value <= self::NORMAL_RANGE_MAX
        )->count();

        $belowRangeCount = $readings->filter(
            fn (HealthSyncSample $r): bool => $r->value < self::HYPOGLYCEMIA_THRESHOLD
        )->count();

        $aboveRangeCount = $readings->filter(
            fn (HealthSyncSample $r): bool => $r->value > self::HYPERGLYCEMIA_THRESHOLD
        )->count();

        return [
            'timeInRange' => round(($inRangeCount / $total) * 100, 1),
            'timeAboveRange' => round(($aboveRangeCount / $total) * 100, 1),
            'timeBelowRange' => round(($belowRangeCount / $total) * 100, 1),
            'inRangeCount' => $inRangeCount,
            'aboveRangeCount' => $aboveRangeCount,
            'belowRangeCount' => $belowRangeCount,
            'total' => $total,
        ];
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     * @return array{min: float|null, max: float|null, average: float|null, stdDev: float|null}
     */
    public function calculateBasicStats(Collection $readings): array
    {
        if ($readings->isEmpty()) {
            return [
                'min' => null,
                'max' => null,
                'average' => null,
                'stdDev' => null,
            ];
        }

        // @codeCoverageIgnoreStart
        /** @var Collection<int, float> $values */
        $values = $readings->pluck('value');

        $min = $values->min();
        $max = $values->max();
        $average = $values->avg();

        return [
            'min' => is_numeric($min) ? round((float) $min, 1) : null,
            'max' => is_numeric($max) ? round((float) $max, 1) : null,
            'average' => is_numeric($average) ? round((float) $average, 1) : null,
            'stdDev' => $this->calculateStandardDeviation($values),
        ];
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, float>  $values
     */
    public function calculateStandardDeviation(Collection $values): ?float
    {
        if ($values->count() < 2) {
            return null;
        }

        // @codeCoverageIgnoreStart
        $mean = (float) $values->avg();
        $variance = (float) $values->map(fn (float $value): float => ($value - $mean) ** 2)->avg();

        return round(sqrt($variance), 1);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     */
    public function calculateCoefficientOfVariation(Collection $readings): ?float
    {
        if ($readings->isEmpty()) {
            return null;
        }

        /** @var Collection<int, float> $values */
        $values = $readings->pluck('value');
        $mean = (float) $values->avg();
        $stdDev = $this->calculateStandardDeviation($values);

        if ($stdDev === null || $mean === 0.0) {
            return null;
        }

        // @codeCoverageIgnoreStart
        return round(($stdDev / $mean) * 100, 1);
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     * @return array{
     *     morning: array{count: int, average: float|null},
     *     afternoon: array{count: int, average: float|null},
     *     evening: array{count: int, average: float|null},
     *     night: array{count: int, average: float|null}
     * }
     */
    public function analyzeTimeOfDay(Collection $readings): array
    {
        $grouped = $readings->groupBy(function (HealthSyncSample $reading): string {
            $hour = (int) $reading->measured_at->format('H');

            return match (true) {
                $hour >= 5 && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 17 => 'afternoon',
                $hour >= 17 && $hour < 21 => 'evening',
                default => 'night',
            };
        });

        /** @var array{morning: array{count: int, average: float|null}, afternoon: array{count: int, average: float|null}, evening: array{count: int, average: float|null}, night: array{count: int, average: float|null}} $result */
        $result = [];
        foreach (['morning', 'afternoon', 'evening', 'night'] as $period) {
            $periodReadings = $grouped->get($period, collect());
            $avg = $periodReadings->avg('value');

            $result[$period] = [
                'count' => $periodReadings->count(),
                'average' => is_numeric($avg) ? round((float) $avg, 1) : null,
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     * @return array<string, array{count: int, percentage: float, average: float|null}>
     */
    public function analyzeReadingTypeFrequency(Collection $readings): array
    {
        if ($readings->isEmpty()) {
            return [];
        }

        // @codeCoverageIgnoreStart
        $total = $readings->count();
        $grouped = $readings->groupBy(fn (HealthSyncSample $reading): string => is_string($reading->metadata['glucose_reading_type'] ?? null) ? $reading->metadata['glucose_reading_type'] : GlucoseReadingType::Random->value);

        /** @var array<string, array{count: int, percentage: float, average: float|null}> $result */
        $result = [];
        foreach ($grouped as $type => $typeReadings) {
            $avg = $typeReadings->avg('value');
            $result[(string) $type] = [
                'count' => $typeReadings->count(),
                'percentage' => round(($typeReadings->count() / $total) * 100, 1),
                'average' => is_numeric($avg) ? round((float) $avg, 1) : null,
            ];
        }

        return $result;
        // @codeCoverageIgnoreEnd
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     * @return array{
     *     slopePerDay: float|null,
     *     slopePerWeek: float|null,
     *     direction: string|null,
     *     firstValue: float|null,
     *     lastValue: float|null,
     *     daysDifference: int|null
     * }
     */
    public function calculateTrend(Collection $readings): array
    {
        if ($readings->count() < 2) {
            return [
                'slopePerDay' => null,
                'slopePerWeek' => null,
                'direction' => null,
                'firstValue' => null,
                'lastValue' => null,
                'daysDifference' => null,
            ];
        }

        $sorted = $readings->sortBy('measured_at')->values();

        /** @var HealthSyncSample $first */
        $first = $sorted->first();
        /** @var HealthSyncSample $last */
        $last = $sorted->last();

        $firstTimestamp = (float) $first->measured_at->timestamp;
        $daysDiff = (int) ceil(((float) $last->measured_at->timestamp - $firstTimestamp) / 86400);

        if ($daysDiff === 0) {
            return [
                'slopePerDay' => null,
                'slopePerWeek' => null,
                'direction' => 'stable',
                'firstValue' => round($first->value, 1),
                'lastValue' => round($last->value, 1),
                'daysDifference' => 0,
            ];
        }

        // @codeCoverageIgnoreStart
        $points = $sorted->map(function (HealthSyncSample $reading) use ($firstTimestamp): array {
            $daysSinceFirst = ((float) $reading->measured_at->timestamp - $firstTimestamp) / 86400;

            return [
                'x' => $daysSinceFirst,
                'y' => $reading->value,
            ];
        });

        $meanX = $points->avg('x');
        $meanY = $points->avg('y');

        $numerator = $points->sum(fn (array $point): float => ($point['x'] - $meanX) * ($point['y'] - $meanY));
        $denominator = $points->sum(fn (array $point): float => ($point['x'] - $meanX) ** 2);

        $slopePerDay = $denominator === 0.0 ? 0.0 : $numerator / $denominator;

        $slopePerWeek = $slopePerDay * 7;

        $direction = match (true) {
            abs($slopePerDay) < 0.5 => 'stable',
            $slopePerDay > 0 => 'rising',
            default => 'falling',
        };

        return [
            'slopePerDay' => round($slopePerDay, 2),
            'slopePerWeek' => round($slopePerWeek, 1),
            'direction' => $direction,
            'firstValue' => round($first->value, 1),
            'lastValue' => round($last->value, 1),
            'daysDifference' => $daysDiff,
        ];
        // @codeCoverageIgnoreEnd
    }
}
