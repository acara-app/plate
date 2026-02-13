<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

final readonly class GlucoseStatisticsService
{
    // Glucose ranges (mg/dL) based on clinical standards
    public const int NORMAL_RANGE_MIN = 70;

    public const int NORMAL_RANGE_MAX = 140;

    public const int HYPOGLYCEMIA_THRESHOLD = 70;

    public const int HYPERGLYCEMIA_THRESHOLD = 140;

    public const int FASTING_NORMAL_MAX = 100;

    public const int FASTING_PREDIABETIC_MAX = 125;

    public const int HIGH_VARIABILITY_STDDEV = 30;

    public const int POST_MEAL_SPIKE_THRESHOLD = 140;

    /**
     * Calculate time-in-range, time-above-range, and time-below-range percentages.
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
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

        $total = $readings->count();
        $inRangeCount = $readings->filter(
            fn (\App\Models\HealthEntry $r): bool => $r->glucose_value >= self::NORMAL_RANGE_MIN
                && $r->glucose_value <= self::NORMAL_RANGE_MAX
        )->count();

        $belowRangeCount = $readings->filter(
            fn (\App\Models\HealthEntry $r): bool => $r->glucose_value < self::HYPOGLYCEMIA_THRESHOLD
        )->count();

        $aboveRangeCount = $readings->filter(
            fn (\App\Models\HealthEntry $r): bool => $r->glucose_value > self::HYPERGLYCEMIA_THRESHOLD
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
    }

    /**
     * Calculate min, max, average, and standard deviation.
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
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

        /** @var Collection<int, float> $values */
        $values = $readings->pluck('glucose_value');

        $min = $values->min();
        $max = $values->max();
        $average = $values->avg();

        return [
            'min' => is_numeric($min) ? round((float) $min, 1) : null,
            'max' => is_numeric($max) ? round((float) $max, 1) : null,
            'average' => is_numeric($average) ? round((float) $average, 1) : null,
            'stdDev' => $this->calculateStandardDeviation($values),
        ];
    }

    /**
     * Calculate standard deviation of glucose readings.
     *
     * @param  Collection<int, float>  $values
     */
    public function calculateStandardDeviation(Collection $values): ?float
    {
        if ($values->count() < 2) {
            return null;
        }

        $mean = (float) $values->avg();
        $variance = (float) $values->map(fn (float $value): float => ($value - $mean) ** 2)->avg();

        return round(sqrt($variance), 1);
    }

    /**
     * Calculate coefficient of variation (CV) as percentage.
     * CV = (stdDev / mean) × 100
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
     */
    public function calculateCoefficientOfVariation(Collection $readings): ?float
    {
        if ($readings->isEmpty()) {
            return null;
        }

        /** @var Collection<int, float> $values */
        $values = $readings->pluck('glucose_value');
        $mean = (float) $values->avg();
        $stdDev = $this->calculateStandardDeviation($values);

        if ($stdDev === null || $mean === 0.0) {
            return null;
        }

        return round(($stdDev / $mean) * 100, 1);
    }

    /**
     * Analyze time-of-day patterns.
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
     * @return array{
     *     morning: array{count: int, average: float|null},
     *     afternoon: array{count: int, average: float|null},
     *     evening: array{count: int, average: float|null},
     *     night: array{count: int, average: float|null}
     * }
     */
    public function analyzeTimeOfDay(Collection $readings): array
    {
        $grouped = $readings->groupBy(function (\App\Models\HealthEntry $reading): string {
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
            $avg = $periodReadings->avg('glucose_value');

            $result[$period] = [
                'count' => $periodReadings->count(),
                'average' => is_numeric($avg) ? round((float) $avg, 1) : null,
            ];
        }

        return $result;
    }

    /**
     * Analyze frequency by reading type.
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
     * @return array<string, array{count: int, percentage: float, average: float|null}>
     */
    public function analyzeReadingTypeFrequency(Collection $readings): array
    {
        if ($readings->isEmpty()) {
            return [];
        }

        $total = $readings->count();
        $grouped = $readings->groupBy(fn (\App\Models\HealthEntry $reading): string => $reading->glucose_reading_type->value ?? \App\Enums\GlucoseReadingType::Random->value);

        /** @var array<string, array{count: int, percentage: float, average: float|null}> $result */
        $result = [];
        foreach ($grouped as $type => $typeReadings) {
            $avg = $typeReadings->avg('glucose_value');
            $result[(string) $type] = [
                'count' => $typeReadings->count(),
                'percentage' => round(($typeReadings->count() / $total) * 100, 1),
                'average' => is_numeric($avg) ? round((float) $avg, 1) : null,
            ];
        }

        return $result;
    }

    /**
     * Calculate linear trend over time (glucose change per day).
     * Uses simple linear regression: slope = sum((x - x_mean)(y - y_mean)) / sum((x - x_mean)^2)
     *
     * @param  Collection<int, \App\Models\HealthEntry>  $readings
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

        // Sort by measured_at to ensure proper ordering
        $sorted = $readings->sortBy('measured_at')->values();

        /** @var \App\Models\HealthEntry $first */
        $first = $sorted->first();
        /** @var \App\Models\HealthEntry $last */
        $last = $sorted->last();

        $firstTimestamp = (float) $first->measured_at->timestamp;
        $daysDiff = (int) ceil(((float) $last->measured_at->timestamp - $firstTimestamp) / 86400);

        if ($daysDiff === 0) {
            return [
                'slopePerDay' => null,
                'slopePerWeek' => null,
                'direction' => 'stable',
                'firstValue' => round((float) $first->glucose_value, 1),
                'lastValue' => round((float) $last->glucose_value, 1),
                'daysDifference' => 0,
            ];
        }

        // Convert timestamps to days from first reading
        $points = $sorted->map(function (\App\Models\HealthEntry $reading) use ($firstTimestamp): array {
            $daysSinceFirst = ((float) $reading->measured_at->timestamp - $firstTimestamp) / 86400;

            return [
                'x' => $daysSinceFirst,
                'y' => $reading->glucose_value,
            ];
        });

        $points->count();
        $meanX = $points->avg('x');
        $meanY = $points->avg('y');

        // Calculate slope using least squares method
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
            'firstValue' => round((float) $first->glucose_value, 1),
            'lastValue' => round((float) $last->glucose_value, 1),
            'daysDifference' => $daysDiff,
        ];
    }
}
