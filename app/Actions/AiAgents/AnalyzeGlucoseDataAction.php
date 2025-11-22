<?php

declare(strict_types=1);

namespace App\Actions\AiAgents;

use App\DataObjects\GlucoseAnalysis\AveragesData;
use App\DataObjects\GlucoseAnalysis\DateRangeData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\GlucoseAnalysis\GlucoseGoalsData;
use App\DataObjects\GlucoseAnalysis\PatternsData;
use App\DataObjects\GlucoseAnalysis\RangesData;
use App\DataObjects\GlucoseAnalysis\ReadingTypeStatsData;
use App\DataObjects\GlucoseAnalysis\TimeInRangeData;
use App\DataObjects\GlucoseAnalysis\TimeOfDayData;
use App\DataObjects\GlucoseAnalysis\TimeOfDayPeriodData;
use App\DataObjects\GlucoseAnalysis\TrendData;
use App\DataObjects\GlucoseAnalysis\VariabilityData;
use App\Enums\ReadingType;
use App\Models\User;
use App\Services\GlucoseStatisticsService;
use Illuminate\Support\Collection;

final readonly class AnalyzeGlucoseDataAction
{
    public function __construct(private GlucoseStatisticsService $statistics)
    {
        //
    }

    /**
     * Analyze user's glucose readings and return comprehensive insights.
     */
    public function handle(User $user, int $daysBack = 30): GlucoseAnalysisData
    {
        $cutoffDate = \Illuminate\Support\Facades\Date::now()->subDays($daysBack);

        $readings = $user->glucoseReadings()
            ->where('measured_at', '>=', $cutoffDate)
            ->latest('measured_at')
            ->get();

        if ($readings->isEmpty()) {
            return $this->emptyAnalysis($daysBack);
        }

        // Calculate comprehensive statistics using the service
        $basicStats = $this->statistics->calculateBasicStats($readings);
        $timeInRange = $this->statistics->calculateTimeInRange($readings);
        $coefficientOfVariation = $this->statistics->calculateCoefficientOfVariation($readings);
        $trend = $this->statistics->calculateTrend($readings);
        $timeOfDay = $this->statistics->analyzeTimeOfDay($readings);
        $readingTypes = $this->statistics->analyzeReadingTypeFrequency($readings);

        // Calculate type-specific averages
        $averages = $this->calculateAverages($readings);

        // Enhanced pattern detection
        $patterns = $this->detectPatterns($readings, $basicStats, $timeInRange);

        // Generate insights with actual date range
        /** @var \App\Models\GlucoseReading $firstReading */
        $firstReading = $readings->first();
        /** @var \App\Models\GlucoseReading $lastReading */
        $lastReading = $readings->last();

        $actualDays = (int) $lastReading->measured_at->diffInDays($firstReading->measured_at) + 1;

        $insights = $this->generateInsights(
            $averages,
            $basicStats,
            $patterns,
            $timeInRange,
            $trend,
            $timeOfDay,
            $readingTypes,
            $readings->count(),
            $actualDays,
            $coefficientOfVariation
        );

        $concerns = $this->identifyConcerns($averages, $patterns, $timeInRange, $trend);
        $glucoseGoals = $this->determineGlucoseGoals($averages, $patterns, $timeInRange, $trend);

        // Convert timeOfDay array to DTO
        $timeOfDayDto = new TimeOfDayData(
            morning: new TimeOfDayPeriodData($timeOfDay['morning']['count'], $timeOfDay['morning']['average']),
            afternoon: new TimeOfDayPeriodData($timeOfDay['afternoon']['count'], $timeOfDay['afternoon']['average']),
            evening: new TimeOfDayPeriodData($timeOfDay['evening']['count'], $timeOfDay['evening']['average']),
            night: new TimeOfDayPeriodData($timeOfDay['night']['count'], $timeOfDay['night']['average']),
        );

        // Convert readingTypes to DTOs
        $readingTypesDtos = [];
        foreach ($readingTypes as $type => $stats) {
            $readingTypesDtos[$type] = new ReadingTypeStatsData(
                count: $stats['count'],
                percentage: $stats['percentage'],
                average: $stats['average']
            );
        }

        return new GlucoseAnalysisData(
            hasData: true,
            totalReadings: $readings->count(),
            daysAnalyzed: $actualDays,
            dateRange: new DateRangeData(
                start: $lastReading->measured_at->toDateString(),
                end: $firstReading->measured_at->toDateString(),
            ),
            averages: $averages,
            ranges: new RangesData(
                min: $basicStats['min'],
                max: $basicStats['max'],
            ),
            timeInRange: new TimeInRangeData(
                percentage: $timeInRange['timeInRange'],
                abovePercentage: $timeInRange['timeAboveRange'],
                belowPercentage: $timeInRange['timeBelowRange'],
                inRangeCount: $timeInRange['inRangeCount'],
                aboveRangeCount: $timeInRange['aboveRangeCount'],
                belowRangeCount: $timeInRange['belowRangeCount'],
            ),
            variability: new VariabilityData(
                stdDev: $basicStats['stdDev'],
                coefficientOfVariation: $coefficientOfVariation,
                classification: $this->classifyVariability($coefficientOfVariation),
            ),
            trend: new TrendData(
                slopePerDay: $trend['slopePerDay'],
                slopePerWeek: $trend['slopePerWeek'],
                direction: $trend['direction'],
                firstValue: $trend['firstValue'],
                lastValue: $trend['lastValue'],
            ),
            timeOfDay: $timeOfDayDto,
            readingTypes: $readingTypesDtos,
            patterns: $patterns,
            insights: $insights,
            concerns: $concerns,
            glucoseGoals: $glucoseGoals,
        );
    }

    /**
     * Return empty analysis structure.
     */
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
            insights: ["No glucose data recorded in the past {$daysBack} days"],
            concerns: [],
            glucoseGoals: new GlucoseGoalsData(
                target: 'Establish baseline glucose monitoring',
                reasoning: 'Insufficient data to determine specific glucose management goals',
            ),
        );
    }

    /**
     * Calculate average glucose readings by type.
     */
    private function calculateAverages(Collection $readings): AveragesData
    {
        $grouped = $readings->groupBy(fn (\App\Models\GlucoseReading $reading): string => $reading->reading_type->value);

        $overallAvg = $readings->avg('reading_value');

        return new AveragesData(
            fasting: $this->calculateAverage($grouped->get(ReadingType::Fasting->value)),
            beforeMeal: $this->calculateAverage($grouped->get(ReadingType::BeforeMeal->value)),
            postMeal: $this->calculateAverage($grouped->get(ReadingType::PostMeal->value)),
            random: $this->calculateAverage($grouped->get(ReadingType::Random->value)),
            overall: is_numeric($overallAvg) ? round((float) $overallAvg, 1) : null,
        );
    }

    /**
     * Calculate average for a collection of readings.
     *
     * @param  Collection<int, \App\Models\GlucoseReading>|null  $readings
     */
    private function calculateAverage(?Collection $readings): ?float
    {
        if (! $readings || $readings->isEmpty()) {
            return null;
        }

        $avg = $readings->avg('reading_value');

        return is_numeric($avg) ? round((float) $avg, 1) : null;
    }

    /**
     * Detect patterns in glucose readings with enhanced TIR-based analysis.
     */
    private function detectPatterns(Collection $readings, array $basicStats, array $timeInRange): PatternsData
    {
        $postMealReadings = $readings->where('reading_type', ReadingType::PostMeal);
        $highPostMeal = $postMealReadings->filter(
            fn (\App\Models\GlucoseReading $r): bool => $r->reading_value > GlucoseStatisticsService::POST_MEAL_SPIKE_THRESHOLD
        )->count();

        // Determine hypoglycemia risk based on time-below-range
        $hypoglycemiaRisk = match (true) {
            $timeInRange['timeBelowRange'] >= 10 => 'high',
            $timeInRange['timeBelowRange'] >= 5 => 'moderate',
            $timeInRange['timeBelowRange'] > 0 => 'low',
            default => 'none',
        };

        // Determine hyperglycemia risk based on time-above-range
        $hyperglycemiaRisk = match (true) {
            $timeInRange['timeAboveRange'] >= 50 => 'high',
            $timeInRange['timeAboveRange'] >= 25 => 'moderate',
            $timeInRange['timeAboveRange'] > 0 => 'low',
            default => 'none',
        };

        return new PatternsData(
            consistentlyHigh: $timeInRange['timeAboveRange'] > 50,
            consistentlyLow: $timeInRange['timeBelowRange'] > 10,
            highVariability: $basicStats['stdDev'] !== null && $basicStats['stdDev'] > GlucoseStatisticsService::HIGH_VARIABILITY_STDDEV,
            postMealSpikes: $postMealReadings->isNotEmpty() && ($highPostMeal / $postMealReadings->count()) > 0.5,
            hypoglycemiaRisk: $hypoglycemiaRisk,
            hyperglycemiaRisk: $hyperglycemiaRisk,
        );
    }

    /**
     * Classify variability based on coefficient of variation.
     */
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
     * Generate comprehensive insights based on all available metrics.
     *
     * @param  array<string, array{count: int, percentage: float, average: float|null}>  $readingTypes
     * @return array<int, string>
     */
    private function generateInsights(
        AveragesData $averages,
        array $basicStats,
        PatternsData $patterns,
        array $timeInRange,
        array $trend,
        array $timeOfDay,
        array $readingTypes,
        int $readingsCount,
        int $actualDays,
        ?float $coefficientOfVariation
    ): array {
        $insights = [];

        // Overview
        $dayLabel = $actualDays === 1 ? 'day' : 'days';
        $insights[] = "Analyzed {$readingsCount} glucose readings over {$actualDays} {$dayLabel}";

        // Average and range
        if ($averages->overall !== null) {
            $insights[] = "Average glucose level: {$averages->overall} mg/dL";
        }

        if ($basicStats['min'] !== null && $basicStats['max'] !== null) {
            $insights[] = "Glucose range: {$basicStats['min']}-{$basicStats['max']} mg/dL";
        }

        // Time in range - critical metric
        $tirStatus = match (true) {
            $timeInRange['timeInRange'] >= 70 => 'excellent',
            $timeInRange['timeInRange'] >= 50 => 'good',
            default => 'needs improvement',
        };
        $insights[] = "Time in range (70-140 mg/dL): {$timeInRange['timeInRange']}% ({$tirStatus})";

        // Fasting glucose
        if ($averages->fasting !== null) {
            $status = match (true) {
                $averages->fasting < GlucoseStatisticsService::HYPOGLYCEMIA_THRESHOLD => 'low',
                $averages->fasting <= GlucoseStatisticsService::FASTING_NORMAL_MAX => 'normal',
                $averages->fasting <= GlucoseStatisticsService::FASTING_PREDIABETIC_MAX => 'elevated',
                default => 'high',
            };
            $insights[] = "Average fasting glucose: {$averages->fasting} mg/dL ({$status})";
        }

        // Post-meal glucose
        if ($averages->postMeal !== null) {
            $status = $averages->postMeal <= GlucoseStatisticsService::POST_MEAL_SPIKE_THRESHOLD ? 'normal' : 'elevated';
            $insights[] = "Average post-meal glucose: {$averages->postMeal} mg/dL ({$status})";
        }

        // Variability analysis
        if ($coefficientOfVariation !== null) {
            $cvStatus = $this->classifyVariability($coefficientOfVariation);
            $insights[] = "Glucose variability: {$cvStatus} (CV: {$coefficientOfVariation}%)";
        }

        // Trend analysis
        if ($trend['direction'] === 'rising' && $trend['slopePerWeek'] !== null) {
            $insights[] = "Trend: glucose levels rising by approximately {$trend['slopePerWeek']} mg/dL per week";
        } elseif ($trend['direction'] === 'falling' && $trend['slopePerWeek'] !== null) {
            $absSlope = abs($trend['slopePerWeek']);
            $insights[] = "Trend: glucose levels decreasing by approximately {$absSlope} mg/dL per week";
        } elseif ($trend['direction'] === 'stable') {
            $insights[] = 'Trend: glucose levels are stable over the analysis period';
        }

        // Time of day patterns
        $timeOfDayInsights = [];
        foreach ($timeOfDay as $period => $data) {
            if ($data['count'] > 0 && $data['average'] !== null) {
                $timeOfDayInsights[] = "{$period}: {$data['average']} mg/dL ({$data['count']} readings)";
            }
        }
        if ($timeOfDayInsights !== []) {
            $insights[] = 'Average by time of day: '.implode(', ', $timeOfDayInsights);
        }

        // Reading type frequency
        if ($readingTypes !== []) {
            $mostCommon = collect($readingTypes)->sortByDesc('count')->first();
            if ($mostCommon !== null) {
                $type = collect($readingTypes)->search($mostCommon);
                $insights[] = "Most frequent reading type: {$type} ({$mostCommon['percentage']}%)";
            }
        }

        // Specific patterns
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
     * Identify concerns based on comprehensive pattern analysis with null guards.
     *
     * @return array<int, string>
     */
    private function identifyConcerns(
        AveragesData $averages,
        PatternsData $patterns,
        array $timeInRange,
        array $trend
    ): array {
        $concerns = [];

        // Time in range concerns
        if ($timeInRange['timeInRange'] < 50) {
            $concerns[] = "Low time in range ({$timeInRange['timeInRange']}%) indicates poor glucose control requiring attention";
        }

        // Hyperglycemia concerns
        if ($patterns->consistentlyHigh && $averages->overall !== null) {
            $concerns[] = "Consistently elevated glucose levels (average: {$averages->overall} mg/dL, {$timeInRange['timeAboveRange']}% time above range) may indicate need for dietary intervention";
        }

        if ($patterns->postMealSpikes) {
            $concerns[] = 'Frequent post-meal glucose spikes detected, suggesting sensitivity to certain carbohydrate sources';
        }

        // Hypoglycemia concerns
        if ($patterns->consistentlyLow && $averages->overall !== null) {
            $concerns[] = "Consistently low glucose levels (average: {$averages->overall} mg/dL, {$timeInRange['timeBelowRange']}% time below range) may indicate insufficient carbohydrate intake";
        }

        if ($patterns->hypoglycemiaRisk === 'high') {
            $concerns[] = 'High risk of hypoglycemia detected - consult healthcare provider about carbohydrate intake';
        } elseif ($patterns->hypoglycemiaRisk === 'moderate') {
            $concerns[] = 'Moderate risk of hypoglycemia - monitor closely and consider adjusting meal timing';
        }

        // Variability concerns
        if ($patterns->highVariability) {
            $concerns[] = 'High glucose variability indicates inconsistent blood sugar control and may benefit from meal timing optimization';
        }

        // Fasting glucose concerns
        if ($averages->fasting !== null && $averages->fasting > GlucoseStatisticsService::FASTING_NORMAL_MAX) {
            $concerns[] = "Elevated fasting glucose ({$averages->fasting} mg/dL) may be influenced by evening eating patterns";
        }

        // Trend concerns
        if ($trend['direction'] === 'rising' && $trend['slopePerWeek'] !== null && $trend['slopePerWeek'] > 5) {
            $concerns[] = "Glucose levels are rising by {$trend['slopePerWeek']} mg/dL per week - early intervention recommended";
        }

        return $concerns;
    }

    /**
     * Determine glucose management goals based on comprehensive analysis with null guards.
     */
    private function determineGlucoseGoals(
        AveragesData $averages,
        PatternsData $patterns,
        array $timeInRange,
        array $trend
    ): GlucoseGoalsData {
        // Priority 1: Address hypoglycemia risk
        if ($patterns->consistentlyLow && $averages->overall !== null) {
            return new GlucoseGoalsData(
                target: 'Maintain glucose levels above 70 mg/dL',
                reasoning: "Current average of {$averages->overall} mg/dL with {$timeInRange['timeBelowRange']}% time below range indicates need for increased carbohydrate intake",
            );
        }

        // Priority 2: Improve time in range if poor
        if ($timeInRange['timeInRange'] < 50) {
            return new GlucoseGoalsData(
                target: 'Increase time in range to at least 70%',
                reasoning: "Current time in range of {$timeInRange['timeInRange']}% is below target; requires comprehensive meal planning",
            );
        }

        // Priority 3: Address post-meal spikes
        if ($patterns->postMealSpikes && $averages->postMeal !== null) {
            return new GlucoseGoalsData(
                target: 'Reduce post-meal glucose spikes to below 140 mg/dL',
                reasoning: "Current post-meal average of {$averages->postMeal} mg/dL exceeds recommended threshold",
            );
        }

        // Priority 4: Reduce variability
        if ($patterns->highVariability) {
            return new GlucoseGoalsData(
                target: 'Stabilize glucose levels with reduced variability',
                reasoning: 'High fluctuations can be improved through consistent meal timing and composition',
            );
        }

        // Priority 5: Address rising trend
        if ($trend['direction'] === 'rising' && $trend['slopePerWeek'] !== null && $trend['slopePerWeek'] > 3) {
            return new GlucoseGoalsData(
                target: 'Reverse rising glucose trend',
                reasoning: "Levels are increasing by {$trend['slopePerWeek']} mg/dL per week; early intervention can prevent further elevation",
            );
        }

        // Well-controlled glucose - this will always be reached if we have data since overall is always non-null
        if ($averages->overall !== null) {
            return new GlucoseGoalsData(
                target: 'Maintain current glucose control',
                reasoning: "Current average of {$averages->overall} mg/dL with {$timeInRange['timeInRange']}% time in range shows good control",
            );
        }

        // Fallback - shouldn't reach here with data
        return new GlucoseGoalsData(
            target: 'Establish glucose monitoring routine',
            reasoning: 'Consistent monitoring will help identify patterns and inform personalized goals',
        );
    }
}
