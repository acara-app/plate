<?php

declare(strict_types=1);

use Illuminate\Contracts\Queue\ShouldQueue;
use App\DataObjects\GlucoseAnalysis\AveragesData;
use App\DataObjects\GlucoseAnalysis\DateRangeData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\GlucoseAnalysis\GlucoseGoalsData;
use App\DataObjects\GlucoseAnalysis\PatternsData;
use App\DataObjects\GlucoseAnalysis\RangesData;
use App\DataObjects\GlucoseAnalysis\TimeInRangeData;
use App\DataObjects\GlucoseAnalysis\TimeOfDayData;
use App\DataObjects\GlucoseAnalysis\TimeOfDayPeriodData;
use App\DataObjects\GlucoseAnalysis\TrendData;
use App\DataObjects\GlucoseAnalysis\VariabilityData;
use App\DataObjects\GlucoseNotificationAnalysisData;
use App\Models\User;
use App\Notifications\GlucoseReportNotification;
use Illuminate\Support\Facades\Notification;

function createNotificationAnalysisData(array $concerns = []): GlucoseNotificationAnalysisData
{
    $analysisData = new GlucoseAnalysisData(
        hasData: true,
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

    return new GlucoseNotificationAnalysisData(
        shouldNotify: $concerns !== [],
        concerns: $concerns,
        analysisData: $analysisData
    );
}

test('notification uses mail and database channels', function (): void {
    $analysisResult = createNotificationAnalysisData(['High readings detected']);
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $channels = $notification->via($user);

    expect($channels)->toBe(['mail', 'database']);
});

test('notification can be sent via mail', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $analysisResult = createNotificationAnalysisData(['High readings detected']);

    $user->notify(new GlucoseReportNotification($analysisResult));

    Notification::assertSentTo($user, GlucoseReportNotification::class, fn ($notification, $channels): bool => in_array('mail', $channels));
});

test('notification can be sent to database', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $analysisResult = createNotificationAnalysisData(['High readings detected']);

    $user->notify(new GlucoseReportNotification($analysisResult));

    Notification::assertSentTo($user, GlucoseReportNotification::class, fn ($notification, $channels): bool => in_array('database', $channels));
});

test('mail notification contains average glucose', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->viewData['averageGlucose'])->toBe(105.0);
});

test('mail notification contains time in range data', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->viewData['timeInRangePercentage'])->toBe(75.0)
        ->and($mailMessage->viewData['aboveRangePercentage'])->toBe(20.0)
        ->and($mailMessage->viewData['belowRangePercentage'])->toBe(5.0);
});

test('mail notification contains total readings', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->viewData['totalReadings'])->toBe(50);
});

test('mail notification contains concerns when present', function (): void {
    $concerns = ['High readings detected', 'Post-meal spikes observed'];
    $analysisResult = createNotificationAnalysisData($concerns);
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->viewData['concerns'])->toBe($concerns)
        ->and($mailMessage->viewData['concerns'])->toContain('High readings detected')
        ->and($mailMessage->viewData['concerns'])->toContain('Post-meal spikes observed');
});

test('mail notification has correct subject', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->subject)->toBe('Your Weekly Glucose Report');
});

test('mail notification contains action button to glucose action page', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $mailMessage = $notification->toMail($user);

    expect($mailMessage->viewData['mealPlanUrl'])->toContain('health-entries/insights');
});

test('database notification contains correct structure', function (): void {
    $concerns = ['High readings detected'];
    $analysisResult = createNotificationAnalysisData($concerns);
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $databaseData = $notification->toDatabase($user);

    expect($databaseData)->toHaveKeys([
        'type',
        'days_analyzed',
        'total_readings',
        'average_glucose',
        'time_in_range_percentage',
        'above_range_percentage',
        'below_range_percentage',
        'concerns',
        'has_concerns',
    ])
        ->and($databaseData['type'])->toBe('glucose_report')
        ->and($databaseData['days_analyzed'])->toBe(7)
        ->and($databaseData['total_readings'])->toBe(50)
        ->and($databaseData['average_glucose'])->toBe(105.0)
        ->and($databaseData['time_in_range_percentage'])->toBe(75.0)
        ->and($databaseData['above_range_percentage'])->toBe(20.0)
        ->and($databaseData['below_range_percentage'])->toBe(5.0)
        ->and($databaseData['concerns'])->toBe(['High readings detected'])
        ->and($databaseData['has_concerns'])->toBeTrue();
});

test('database notification has_concerns is false when no concerns', function (): void {
    $analysisResult = createNotificationAnalysisData([]);
    $notification = new GlucoseReportNotification($analysisResult);
    $user = User::factory()->create();

    $databaseData = $notification->toDatabase($user);

    expect($databaseData['has_concerns'])->toBeFalse()
        ->and($databaseData['concerns'])->toBeEmpty();
});

test('notification is queueable', function (): void {
    $analysisResult = createNotificationAnalysisData();
    $notification = new GlucoseReportNotification($analysisResult);

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});
