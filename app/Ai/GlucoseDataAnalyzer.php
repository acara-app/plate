<?php

declare(strict_types=1);

namespace App\Ai;

use App\Data\GlucoseAnalysis\AveragesData;
use App\Data\GlucoseAnalysis\DateRangeData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\GlucoseAnalysis\GlucoseGoalsData;
use App\Data\GlucoseAnalysis\PatternsData;
use App\Data\GlucoseAnalysis\RangesData;
use App\Data\GlucoseAnalysis\ReadingTypeStatsData;
use App\Data\GlucoseAnalysis\TimeInRangeData;
use App\Data\GlucoseAnalysis\TimeOfDayData;
use App\Data\GlucoseAnalysis\TimeOfDayPeriodData;
use App\Data\GlucoseAnalysis\TrendData;
use App\Data\GlucoseAnalysis\VariabilityData;
use App\Enums\GlucoseReadingType;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\GlucoseStatisticsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

final readonly class GlucoseDataAnalyzer
{
    public function __construct(private GlucoseStatisticsService $statistics)
    {
        //
    }

    public function handle(User $user, int $daysBack = 30): GlucoseAnalysisData
    {
        $cutoffDate = Date::now()->subDays($daysBack);

        $readings = $user->healthSyncSamples()
            ->ofType(HealthSyncType::BloodGlucose)
            ->where('measured_at', '>=', $cutoffDate)
            ->latest('measured_at')
            ->get();

        if ($readings->isEmpty()) {
            return $this->emptyAnalysis($daysBack);
        }

        $basicStats = $this->statistics->calculateBasicStats($readings);
        $timeInRangeArray = $this->statistics->calculateTimeInRange($readings);
        $coefficientOfVariation = $this->statistics->calculateCoefficientOfVariation($readings);
        $trendArray = $this->statistics->calculateTrend($readings);
        $timeOfDay = $this->statistics->analyzeTimeOfDay($readings);
        $readingTypes = $this->statistics->analyzeReadingTypeFrequency($readings);

        $averages = $this->calculateAverages($readings);

        $ranges = new RangesData(
            min: $basicStats['min'],
            max: $basicStats['max'],
        );

        $timeInRange = new TimeInRangeData(
            percentage: $timeInRangeArray['timeInRange'],
            abovePercentage: $timeInRangeArray['timeAboveRange'],
            belowPercentage: $timeInRangeArray['timeBelowRange'],
            inRangeCount: $timeInRangeArray['inRangeCount'],
            aboveRangeCount: $timeInRangeArray['aboveRangeCount'],
            belowRangeCount: $timeInRangeArray['belowRangeCount'],
        );

        $variability = new VariabilityData(
            stdDev: $basicStats['stdDev'],
            coefficientOfVariation: $coefficientOfVariation,
            classification: $this->classifyVariability($coefficientOfVariation),
        );

        $trend = new TrendData(
            slopePerDay: $trendArray['slopePerDay'],
            slopePerWeek: $trendArray['slopePerWeek'],
            direction: $trendArray['direction'],
            firstValue: $trendArray['firstValue'],
            lastValue: $trendArray['lastValue'],
        );

        $timeOfDayDto = new TimeOfDayData(
            morning: new TimeOfDayPeriodData($timeOfDay['morning']['count'], $timeOfDay['morning']['average']),
            afternoon: new TimeOfDayPeriodData($timeOfDay['afternoon']['count'], $timeOfDay['afternoon']['average']),
            evening: new TimeOfDayPeriodData($timeOfDay['evening']['count'], $timeOfDay['evening']['average']),
            night: new TimeOfDayPeriodData($timeOfDay['night']['count'], $timeOfDay['night']['average']),
        );

        $readingTypesDtos = [];
        foreach ($readingTypes as $type => $stats) {
            $readingTypesDtos[$type] = new ReadingTypeStatsData(
                count: $stats['count'],
                percentage: $stats['percentage'],
                average: $stats['average']
            );
        }

        $patterns = $this->detectPatterns($readings, $timeInRange, $variability);

        /** @var HealthSyncSample $firstReading */
        $firstReading = $readings->first();
        /** @var HealthSyncSample $lastReading */
        $lastReading = $readings->last();

        $actualDays = (int) $lastReading->measured_at->diffInDays($firstReading->measured_at) + 1;

        $insights = $this->generateInsights(
            $averages,
            $ranges,
            $patterns,
            $timeInRange,
            $trend,
            $variability,
            $timeOfDayDto,
            $readingTypesDtos,
            $readings->count(),
            $actualDays
        );

        $concerns = $this->identifyConcerns($averages, $patterns, $timeInRange, $trend);
        $glucoseGoals = $this->determineGlucoseGoals($averages, $patterns, $timeInRange, $trend);

        return new GlucoseAnalysisData(
            hasData: true,
            totalReadings: $readings->count(),
            daysAnalyzed: $actualDays,
            dateRange: new DateRangeData(
                start: $lastReading->measured_at->toDateString(),
                end: $firstReading->measured_at->toDateString(),
            ),
            averages: $averages,
            ranges: $ranges,
            timeInRange: $timeInRange,
            variability: $variability,
            trend: $trend,
            timeOfDay: $timeOfDayDto,
            readingTypes: $readingTypesDtos,
            patterns: $patterns,
            insights: $insights,
            concerns: $concerns,
            glucoseGoals: $glucoseGoals,
        );
    }

    private function emptyAnalysis(int $daysBack): GlucoseAnalysisData
    {
        return new GlucoseAnalysisData(
            hasData: false,
            totalReadings: 0,
            daysAnalyzed: $daysBack,
            dateRange: new DateRangeData(start: null, end: null),
            averages: new AveragesData(
                fasting: null,
                beforeMeal: null,
                postMeal: null,
                random: null,
                overall: null,
            ),
            ranges: new RangesData(min: null, max: null),
            timeInRange: new TimeInRangeData(
                percentage: 0.0,
                abovePercentage: 0.0,
                belowPercentage: 0.0,
                inRangeCount: 0,
                aboveRangeCount: 0,
                belowRangeCount: 0,
            ),
            variability: new VariabilityData(
                stdDev: null,
                coefficientOfVariation: null,
                classification: null,
            ),
            trend: new TrendData(
                slopePerDay: null,
                slopePerWeek: null,
                direction: null,
                firstValue: null,
                lastValue: null,
            ),
            timeOfDay: new TimeOfDayData(
                morning: new TimeOfDayPeriodData(count: 0, average: null),
                afternoon: new TimeOfDayPeriodData(count: 0, average: null),
                evening: new TimeOfDayPeriodData(count: 0, average: null),
                night: new TimeOfDayPeriodData(count: 0, average: null),
            ),
            readingTypes: [],
            patterns: new PatternsData(
                consistentlyHigh: false,
                consistentlyLow: false,
                highVariability: false,
                postMealSpikes: false,
                hypoglycemiaRisk: 'none',
                hyperglycemiaRisk: 'none',
            ),
            insights: [sprintf('No glucose data recorded in the past %d days', $daysBack)],
            concerns: [],
            glucoseGoals: new GlucoseGoalsData(
                target: 'Establish baseline glucose monitoring',
                reasoning: 'Insufficient data to determine specific glucose management goals',
            ),
        );
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     */
    private function calculateAverages(Collection $readings): AveragesData
    {
        $grouped = $readings->groupBy(fn (HealthSyncSample $reading): string => is_string($reading->metadata['glucose_reading_type'] ?? null) ? $reading->metadata['glucose_reading_type'] : GlucoseReadingType::Random->value);

        $overallAvg = $readings->avg('value');

        return new AveragesData(
            fasting: $this->calculateAverage($grouped->get(GlucoseReadingType::Fasting->value)),
            beforeMeal: $this->calculateAverage($grouped->get(GlucoseReadingType::BeforeMeal->value)),
            postMeal: $this->calculateAverage($grouped->get(GlucoseReadingType::PostMeal->value)),
            random: $this->calculateAverage($grouped->get(GlucoseReadingType::Random->value)),
            overall: is_numeric($overallAvg) ? round((float) $overallAvg, 1) : null,
        );
    }

    /**
     * @param  Collection<int, HealthSyncSample>|null  $readings
     */
    private function calculateAverage(?Collection $readings): ?float
    {
        if (! $readings || $readings->isEmpty()) {
            return null;
        }

        $avg = $readings->avg('value');

        return is_numeric($avg) ? round((float) $avg, 1) : null;
    }

    /**
     * @param  Collection<int, HealthSyncSample>  $readings
     */
    private function detectPatterns(Collection $readings, TimeInRangeData $timeInRange, VariabilityData $variability): PatternsData
    {
        $postMealReadings = $readings->filter(
            fn (HealthSyncSample $r): bool => ($r->metadata['glucose_reading_type'] ?? null) === GlucoseReadingType::PostMeal->value
        );
        $highPostMeal = $postMealReadings->filter(
            fn (HealthSyncSample $r): bool => $r->value > GlucoseStatisticsService::POST_MEAL_SPIKE_THRESHOLD
        )->count();

        $hypoglycemiaRisk = match (true) {
            $timeInRange->belowPercentage >= 10 => 'high',
            $timeInRange->belowPercentage >= 5 => 'moderate',
            $timeInRange->belowPercentage > 0 => 'low',
            default => 'none',
        };

        $hyperglycemiaRisk = match (true) {
            $timeInRange->abovePercentage >= 50 => 'high',
            $timeInRange->abovePercentage >= 25 => 'moderate',
            $timeInRange->abovePercentage > 0 => 'low',
            default => 'none',
        };

        return new PatternsData(
            consistentlyHigh: $timeInRange->abovePercentage > 50,
            consistentlyLow: $timeInRange->belowPercentage > 10,
            highVariability: $variability->stdDev !== null && $variability->stdDev > GlucoseStatisticsService::HIGH_VARIABILITY_STDDEV,
            postMealSpikes: $postMealReadings->isNotEmpty() && ($highPostMeal / $postMealReadings->count()) > 0.5,
            hypoglycemiaRisk: $hypoglycemiaRisk,
            hyperglycemiaRisk: $hyperglycemiaRisk,
        );
    }

    private function classifyVariability(?float $cv): ?string
    {
        if ($cv === null) {
            return null;
        }

        return match (true) {
            $cv < 36 => 'stable',
            $cv <= 50 => 'moderate',
            default => 'high',
        };
    }

    /**
     * @param  array<string, ReadingTypeStatsData>  $readingTypes
     * @return array<int, string>
     */
    private function generateInsights(
        AveragesData $averages,
        RangesData $ranges,
        PatternsData $patterns,
        TimeInRangeData $timeInRange,
        TrendData $trend,
        VariabilityData $variability,
        TimeOfDayData $timeOfDay,
        array $readingTypes,
        int $readingsCount,
        int $actualDays
    ): array {
        $insights = [];

        $dayLabel = $actualDays === 1 ? 'day' : 'days';
        $insights[] = sprintf('Analyzed %d glucose readings over %d %s', $readingsCount, $actualDays, $dayLabel);

        if ($averages->overall !== null) {
            $insights[] = sprintf('Average glucose level: %s mg/dL', $averages->overall);
        }

        if ($ranges->min !== null && $ranges->max !== null) {
            $insights[] = sprintf('Glucose range: %s-%s mg/dL', $ranges->min, $ranges->max);
        }

        $tirStatus = match (true) {
            $timeInRange->percentage >= 70 => 'excellent',
            $timeInRange->percentage >= 50 => 'good',
            default => 'needs improvement',
        };
        $insights[] = sprintf('Time in range (70-140 mg/dL): %s%% (%s)', $timeInRange->percentage, $tirStatus);

        if ($averages->fasting !== null) {
            $status = match (true) {
                $averages->fasting < GlucoseStatisticsService::HYPOGLYCEMIA_THRESHOLD => 'low',
                $averages->fasting <= GlucoseStatisticsService::FASTING_NORMAL_MAX => 'normal',
                $averages->fasting <= GlucoseStatisticsService::FASTING_PREDIABETIC_MAX => 'elevated',
                default => 'high',
            };
            $insights[] = sprintf('Average fasting glucose: %s mg/dL (%s)', $averages->fasting, $status);
        }

        if ($averages->postMeal !== null) {
            $status = $averages->postMeal <= GlucoseStatisticsService::POST_MEAL_SPIKE_THRESHOLD ? 'normal' : 'elevated';
            $insights[] = sprintf('Average post-meal glucose: %s mg/dL (%s)', $averages->postMeal, $status);
        }

        if ($variability->coefficientOfVariation !== null) {
            $cvStatus = $variability->classification;
            $insights[] = sprintf('Glucose variability: %s (CV: %s%%)', $cvStatus, $variability->coefficientOfVariation);
        }

        if ($trend->direction === 'rising' && $trend->slopePerWeek !== null) {
            $insights[] = sprintf('Trend: glucose levels rising by approximately %s mg/dL per week', $trend->slopePerWeek);
        } elseif ($trend->direction === 'falling' && $trend->slopePerWeek !== null) {
            $absSlope = abs($trend->slopePerWeek);
            $insights[] = sprintf('Trend: glucose levels decreasing by approximately %s mg/dL per week', $absSlope);
        } elseif ($trend->direction === 'stable') {
            $insights[] = 'Trend: glucose levels are stable over the analysis period';
        }

        $timeOfDayInsights = [];
        $periods = [
            'morning' => $timeOfDay->morning,
            'afternoon' => $timeOfDay->afternoon,
            'evening' => $timeOfDay->evening,
            'night' => $timeOfDay->night,
        ];
        foreach ($periods as $period => $data) {
            if ($data->count > 0 && $data->average !== null) {
                $timeOfDayInsights[] = sprintf('%s: %s mg/dL (%d readings)', $period, $data->average, $data->count);
            }
        }

        if ($timeOfDayInsights !== []) {
            $insights[] = 'Average by time of day: '.implode(', ', $timeOfDayInsights);
        }

        if ($readingTypes !== []) {
            $mostCommon = collect($readingTypes)->sortByDesc(fn (ReadingTypeStatsData $stats): int => $stats->count)->first();
            if ($mostCommon !== null) {
                $type = collect($readingTypes)->search($mostCommon);
                $insights[] = sprintf('Most frequent reading type: %s (%s%%)', $type, $mostCommon->percentage);
            }
        }

        if ($patterns->postMealSpikes) {
            $insights[] = 'Frequent post-meal glucose spikes detected';
        }

        if ($patterns->hypoglycemiaRisk !== 'none') {
            $insights[] = ucfirst($patterns->hypoglycemiaRisk).' risk of hypoglycemia detected';
        }

        if ($patterns->hyperglycemiaRisk !== 'none') {
            $insights[] = ucfirst($patterns->hyperglycemiaRisk).' risk of hyperglycemia detected';
        }

        return $insights;
    }

    /**
     * @return array<int, string>
     */
    private function identifyConcerns(
        AveragesData $averages,
        PatternsData $patterns,
        TimeInRangeData $timeInRange,
        TrendData $trend
    ): array {
        $concerns = [];

        if ($timeInRange->percentage < 50) {
            $concerns[] = sprintf('Low time in range (%s%%) indicates poor glucose control requiring attention', $timeInRange->percentage);
        }

        if ($patterns->consistentlyHigh && $averages->overall !== null) {
            $concerns[] = sprintf('Consistently elevated glucose levels (average: %s mg/dL, %s%% time above range) may indicate need for dietary intervention', $averages->overall, $timeInRange->abovePercentage);
        }

        if ($patterns->postMealSpikes) {
            $concerns[] = 'Frequent post-meal glucose spikes detected, suggesting sensitivity to certain carbohydrate sources';
        }

        if ($patterns->consistentlyLow && $averages->overall !== null) {
            $concerns[] = sprintf('Consistently low glucose levels (average: %s mg/dL, %s%% time below range) may indicate insufficient carbohydrate intake', $averages->overall, $timeInRange->belowPercentage);
        }

        if ($patterns->hypoglycemiaRisk === 'high') {
            $concerns[] = 'High risk of hypoglycemia detected - consult healthcare provider about carbohydrate intake';
        } elseif ($patterns->hypoglycemiaRisk === 'moderate') {
            $concerns[] = 'Moderate risk of hypoglycemia - monitor closely and consider adjusting meal timing';
        }

        if ($patterns->highVariability) {
            $concerns[] = 'High glucose variability indicates inconsistent blood sugar control and may benefit from meal timing optimization';
        }

        if ($averages->fasting !== null && $averages->fasting > GlucoseStatisticsService::FASTING_NORMAL_MAX) {
            $concerns[] = sprintf('Elevated fasting glucose (%s mg/dL) may be influenced by evening eating patterns', $averages->fasting);
        }

        if ($trend->direction === 'rising' && $trend->slopePerWeek !== null && $trend->slopePerWeek > 5) {
            $concerns[] = sprintf('Glucose levels are rising by %s mg/dL per week - early intervention recommended', $trend->slopePerWeek);
        }

        return $concerns;
    }

    private function determineGlucoseGoals(
        AveragesData $averages,
        PatternsData $patterns,
        TimeInRangeData $timeInRange,
        TrendData $trend
    ): GlucoseGoalsData {
        if ($patterns->consistentlyLow && $averages->overall !== null) {
            return new GlucoseGoalsData(
                target: 'Maintain glucose levels above 70 mg/dL',
                reasoning: sprintf('Current average of %s mg/dL with %s%% time below range indicates need for increased carbohydrate intake', $averages->overall, $timeInRange->belowPercentage),
            );
        }

        if ($timeInRange->percentage < 50) {
            return new GlucoseGoalsData(
                target: 'Increase time in range to at least 70%',
                reasoning: sprintf('Current time in range of %s%% is below target; requires comprehensive meal planning', $timeInRange->percentage),
            );
        }

        if ($patterns->postMealSpikes && $averages->postMeal !== null) {
            return new GlucoseGoalsData(
                target: 'Reduce post-meal glucose spikes to below 140 mg/dL',
                reasoning: sprintf('Current post-meal average of %s mg/dL exceeds recommended threshold', $averages->postMeal),
            );
        }

        if ($patterns->highVariability) {
            return new GlucoseGoalsData(
                target: 'Stabilize glucose levels with reduced variability',
                reasoning: 'High fluctuations can be improved through consistent meal timing and composition',
            );
        }

        if ($trend->direction === 'rising' && $trend->slopePerWeek !== null && $trend->slopePerWeek > 3) {
            return new GlucoseGoalsData(
                target: 'Reverse rising glucose trend',
                reasoning: sprintf('Levels are increasing by %s mg/dL per week; early intervention can prevent further elevation', $trend->slopePerWeek),
            );
        }

        if ($averages->overall !== null) {
            return new GlucoseGoalsData(
                target: 'Maintain current glucose control',
                reasoning: sprintf('Current average of %s mg/dL with %s%% time in range shows good control', $averages->overall, $timeInRange->percentage),
            );
        }

        // @codeCoverageIgnoreStart
        return new GlucoseGoalsData(
            target: 'Establish glucose monitoring routine',
            reasoning: 'Consistent monitoring will help identify patterns and inform personalized goals',
        );
        // @codeCoverageIgnoreEnd
    }
}
