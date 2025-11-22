<?php

declare(strict_types=1);

namespace App\DataObjects\GlucoseAnalysis;

use Spatie\LaravelData\Data;

final class GlucoseAnalysisData extends Data
{
    /**
     * @param  array<string, ReadingTypeStatsData>  $readingTypes
     * @param  array<int, string>  $insights
     * @param  array<int, string>  $concerns
     */
    public function __construct(
        public bool $hasData,
        public int $totalReadings,
        public int $daysAnalyzed,
        public DateRangeData $dateRange,
        public AveragesData $averages,
        public RangesData $ranges,
        public TimeInRangeData $timeInRange,
        public VariabilityData $variability,
        public TrendData $trend,
        public TimeOfDayData $timeOfDay,
        public array $readingTypes,
        public PatternsData $patterns,
        public array $insights,
        public array $concerns,
        public GlucoseGoalsData $glucoseGoals,
    ) {}
}
