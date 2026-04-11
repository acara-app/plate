<?php

declare(strict_types=1);

use App\Data\GlucoseAnalysis\AveragesData;
use App\Data\GlucoseAnalysis\DateRangeData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\GlucoseAnalysis\GlucoseGoalsData;
use App\Data\GlucoseAnalysis\PatternsData;
use App\Data\GlucoseAnalysis\RangesData;
use App\Data\GlucoseAnalysis\TimeInRangeData;
use App\Data\GlucoseAnalysis\TimeOfDayData;
use App\Data\GlucoseAnalysis\TimeOfDayPeriodData;
use App\Data\GlucoseAnalysis\TrendData;
use App\Data\GlucoseAnalysis\VariabilityData;
use App\Data\GlucoseNotificationAnalysisData;

covers(GlucoseNotificationAnalysisData::class);

function createTestGlucoseAnalysisData(bool $hasData = true): GlucoseAnalysisData
{
    return new GlucoseAnalysisData(
        hasData: $hasData,
        totalReadings: 50,
        daysAnalyzed: 7,
        dateRange: new DateRangeData(start: '2025-12-18', end: '2025-12-25'),
        averages: new AveragesData(
            fasting: 95.0,
            beforeMeal: 100.0,
            postMeal: 130.0,
            random: 110.0,
            overall: 105.0
        ),
        ranges: new RangesData(min: 70.0, max: 180.0),
        timeInRange: new TimeInRangeData(
            percentage: 75.0,
            abovePercentage: 20.0,
            belowPercentage: 5.0,
            inRangeCount: 38,
            aboveRangeCount: 10,
            belowRangeCount: 2
        ),
        variability: new VariabilityData(
            stdDev: 25.0,
            coefficientOfVariation: 23.8,
            classification: 'moderate'
        ),
        trend: new TrendData(
            slopePerDay: 0.5,
            slopePerWeek: 3.5,
            direction: 'stable',
            firstValue: 100.0,
            lastValue: 103.5
        ),
        timeOfDay: new TimeOfDayData(
            morning: new TimeOfDayPeriodData(count: 15, average: 95.0),
            afternoon: new TimeOfDayPeriodData(count: 15, average: 105.0),
            evening: new TimeOfDayPeriodData(count: 15, average: 115.0),
            night: new TimeOfDayPeriodData(count: 5, average: 90.0)
        ),
        readingTypes: [],
        patterns: new PatternsData(
            consistentlyHigh: false,
            consistentlyLow: false,
            highVariability: false,
            postMealSpikes: false,
            hypoglycemiaRisk: 'none',
            hyperglycemiaRisk: 'none'
        ),
        insights: ['Good glucose control overall'],
        concerns: [],
        glucoseGoals: new GlucoseGoalsData(
            target: 'Maintain current glucose control',
            reasoning: 'Your glucose levels are well managed'
        )
    );
}

it('can be created with shouldNotify true', function (): void {
    $analysisData = createTestGlucoseAnalysisData();
    $concerns = ['High readings detected', 'Post-meal spikes observed'];

    $result = new GlucoseNotificationAnalysisData(
        shouldNotify: true,
        concerns: $concerns,
        analysisData: $analysisData
    );

    expect($result->shouldNotify)->toBeTrue()
        ->and($result->concerns)->toHaveCount(2)
        ->and($result->concerns[0])->toBe('High readings detected')
        ->and($result->analysisData->hasData)->toBeTrue();
});

it('can be created with shouldNotify false', function (): void {
    $analysisData = createTestGlucoseAnalysisData();

    $result = new GlucoseNotificationAnalysisData(
        shouldNotify: false,
        concerns: [],
        analysisData: $analysisData
    );

    expect($result->shouldNotify)->toBeFalse()
        ->and($result->concerns)->toBeEmpty();
});

it('can be converted to array', function (): void {
    $analysisData = createTestGlucoseAnalysisData();
    $concerns = ['Test concern'];

    $result = new GlucoseNotificationAnalysisData(
        shouldNotify: true,
        concerns: $concerns,
        analysisData: $analysisData
    );

    $array = $result->toArray();

    expect($array)->toHaveKeys(['should_notify', 'concerns', 'analysis_data'])
        ->and($array['should_notify'])->toBeTrue()
        ->and($array['concerns'])->toBe(['Test concern']);
});

it('preserves analysis data integrity', function (): void {
    $analysisData = createTestGlucoseAnalysisData();

    $result = new GlucoseNotificationAnalysisData(
        shouldNotify: true,
        concerns: ['Test'],
        analysisData: $analysisData
    );

    expect($result->analysisData->totalReadings)->toBe(50)
        ->and($result->analysisData->daysAnalyzed)->toBe(7)
        ->and($result->analysisData->averages->overall)->toBe(105.0)
        ->and($result->analysisData->timeInRange->percentage)->toBe(75.0);
});
