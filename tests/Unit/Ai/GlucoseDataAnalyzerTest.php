<?php

declare(strict_types=1);

use App\Ai\GlucoseDataAnalyzer;
use App\Enums\GlucoseReadingType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\GlucoseStatisticsService;

use function Pest\Laravel\actingAs;

covers(GlucoseDataAnalyzer::class);

beforeEach(function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    $this->user = $user;
    actingAs($user);
    $this->analyzer = new GlucoseDataAnalyzer(new GlucoseStatisticsService);
});

it('returns empty analysis when no glucose readings exist', function (): void {
    $result = $this->analyzer->handle($this->user);

    expect($result)
        ->hasData->toBeFalse()
        ->totalReadings->toBe(0)
        ->daysAnalyzed->toBe(30)
        ->averages->overall->toBeNull()
        ->timeInRange->percentage->toBe(0.0)
        ->variability->stdDev->toBeNull()
        ->trend->direction->toBeNull()
        ->and($result->insights)->toContain('No glucose data recorded in the past 30 days')
        ->and($result->concerns)->toBeEmpty()
        ->and($result->glucoseGoals->target)->toBe('Establish baseline glucose monitoring')
        ->and($result->glucoseGoals->reasoning)->toBe('Insufficient data to determine specific glucose management goals');
});

it('calculates average glucose levels correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 95.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 105.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(2),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 140.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result)
        ->hasData->toBeTrue()
        ->totalReadings->toBe(3)
        ->and($result->averages->fasting)->toBe(100.0)
        ->and($result->averages->postMeal)->toBe(140.0)
        ->and($result->averages->overall)->toBe(113.3);
});

it('detects consistently high glucose pattern', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 150.0 + ($i * 5),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->consistentlyHigh)->toBeTrue()
        ->and($result->timeInRange->abovePercentage)->toBeGreaterThan(50)
        ->and($result->patterns->hyperglycemiaRisk)->toBeIn(['moderate', 'high']);

    if ($result->timeInRange->percentage < 50) {
        expect($result->glucoseGoals->target)->toBe('Increase time in range to at least 70%');
    } else {
        expect($result->glucoseGoals->target)->toBe('Lower average glucose to 70-100 mg/dL range');
    }

    $concernFound = false;
    foreach ($result->concerns as $concern) {
        if (str_contains((string) $concern, 'Consistently elevated glucose levels') && str_contains((string) $concern, $result->averages->overall.' mg/dL')) {
            $concernFound = true;
            break;
        }
    }

    expect($concernFound)->toBeTrue();
});

it('detects post-meal spikes pattern', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 90.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 160.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
            'measured_at' => now()->subDays($i * 2 + 1),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->postMealSpikes)->toBeTrue()
        ->and($result->concerns)->toContain('Frequent post-meal glucose spikes detected, suggesting sensitivity to certain carbohydrate sources')
        ->and($result->glucoseGoals->target)->toBe('Reduce post-meal glucose spikes to below 140 mg/dL');
});

it('detects high variability pattern', function (): void {
    $values = [70, 150, 85, 140, 75, 160, 80, 145];

    foreach ($values as $index => $value) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => (float) $value,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->highVariability)->toBeTrue()
        ->and($result->variability->stdDev)->toBeGreaterThan(30);

    $hasVariabilityInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'variability')) {
            $hasVariabilityInsight = true;
            break;
        }
    }

    expect($hasVariabilityInsight)->toBeTrue();
});

it('only analyzes readings within specified time period', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 100.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(40),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 120.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(5),
    ]);

    $result = $this->analyzer->handle($this->user, 30);

    expect($result)
        ->totalReadings->toBe(1)
        ->and($result->averages->fasting)->toBe(120.0);
});

it('provides default recommendations when glucose is well controlled', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 90.0 + ($i * 2),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toBe('Maintain current glucose control')
        ->and($result->glucoseGoals->reasoning)->toContain('good control');
});

it('detects consistently low glucose pattern', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 65.0 + ($i),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->consistentlyLow)->toBeTrue()
        ->and($result->timeInRange->belowPercentage)->toBeGreaterThan(10)
        ->and($result->patterns->hypoglycemiaRisk)->toBeIn(['moderate', 'high'])
        ->and($result->glucoseGoals->target)->toBe('Maintain glucose levels above 70 mg/dL');

    $concernFound = false;
    foreach ($result->concerns as $concern) {
        if (str_contains((string) $concern, 'Consistently low glucose levels') && str_contains((string) $concern, $result->averages->overall.' mg/dL')) {
            $concernFound = true;
            break;
        }
    }

    expect($concernFound)->toBeTrue();
});

it('classifies low fasting glucose correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 60.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 65.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->averages->fasting)->toBe(62.5)
        ->and($result->insights)->toContain('Average fasting glucose: 62.5 mg/dL (low)');
});

it('identifies concern for high fasting glucose', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 110.0 + ($i * 2),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->concerns)->toContain('Elevated fasting glucose ('.$result->averages->fasting.' mg/dL) may be influenced by evening eating patterns');
});

it('classifies elevated fasting glucose correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 110.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 115.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->insights)->toContain('Average fasting glucose: 112.5 mg/dL (elevated)');
});

it('classifies high fasting glucose correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 130.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 140.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->averages->fasting)->toBe(135.0);

    $hasHighFastingInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'fasting') && str_contains((string) $insight, 'high')) {
            $hasHighFastingInsight = true;
            break;
        }
    }

    expect($hasHighFastingInsight)->toBeTrue();
});

it('classifies elevated post-meal glucose correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 150.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 160.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->averages->postMeal)->toBe(155.0);

    $hasPostMealInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'post-meal') && str_contains((string) $insight, 'elevated')) {
            $hasPostMealInsight = true;
            break;
        }
    }

    expect($hasPostMealInsight)->toBeTrue();
});

it('handles single glucose reading correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 100.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->hasData)->toBeTrue()
        ->and($result->totalReadings)->toBe(1)
        ->and($result->patterns->highVariability)->toBeFalse();
});

it('calculates time in range percentages correctly', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 160.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 5),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 60.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 8),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->timeInRange->percentage)->toBe(50.0)
        ->and($result->timeInRange->abovePercentage)->toBe(30.0)
        ->and($result->timeInRange->belowPercentage)->toBe(20.0)
        ->and($result->timeInRange->inRangeCount)->toBe(5)
        ->and($result->timeInRange->aboveRangeCount)->toBe(3)
        ->and($result->timeInRange->belowRangeCount)->toBe(2);
});

it('detects rising glucose trend', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 90.0 + ($i * 5),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('rising')
        ->and($result->trend->slopePerWeek)->toBeGreaterThan(0);

    $hasTrendInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'rising')) {
            $hasTrendInsight = true;
            break;
        }
    }

    expect($hasTrendInsight)->toBeTrue();
});

it('detects falling glucose trend', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 140.0 - ($i * 5),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('falling')
        ->and($result->trend->slopePerWeek)->toBeLessThan(0);
});

it('detects stable glucose trend', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 95.0 + (($i % 2) * 2),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('stable');
});

it('analyzes time of day patterns correctly', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 90.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->setTime(8, 0)->subDays(1),
    ]);
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 100.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->setTime(9, 0)->subDays(2),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 120.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
        'measured_at' => now()->setTime(14, 0)->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 110.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->setTime(19, 0)->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 85.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
        'measured_at' => now()->setTime(23, 0)->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->timeOfDay->morning->count)->toBe(2)
        ->and($result->timeOfDay->morning->average)->toBe(95.0)
        ->and($result->timeOfDay->afternoon->count)->toBe(1)
        ->and($result->timeOfDay->afternoon->average)->toBe(120.0)
        ->and($result->timeOfDay->evening->count)->toBe(1)
        ->and($result->timeOfDay->night->count)->toBe(1);
});

it('analyzes reading type frequency correctly', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 95.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 130.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
            'measured_at' => now()->subDays($i + 5),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 105.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 8),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->readingTypes)->toHaveKey('fasting')
        ->and($result->readingTypes['fasting']->count)->toBe(5)
        ->and($result->readingTypes['fasting']->percentage)->toBe(50.0)
        ->and($result->readingTypes['post-meal']->count)->toBe(3)
        ->and($result->readingTypes['post-meal']->percentage)->toBe(30.0);
});

it('calculates coefficient of variation correctly', function (): void {
    $values = [80, 90, 100, 110, 120];

    foreach ($values as $index => $value) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => (float) $value,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->coefficientOfVariation)->toBeGreaterThan(0)
        ->and($result->variability->classification)->toBeIn(['stable', 'moderate', 'high']);
});

it('classifies variability correctly', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 95.0 + ($i * 0.5),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('stable');
});

it('correctly identifies hypoglycemia risk levels', function (): void {
    for ($i = 0; $i < 22; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 65.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 22),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->hypoglycemiaRisk)->toBe('high')
        ->and($result->timeInRange->belowPercentage)->toBe(12.0);
});

it('uses actual days analyzed in insights', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 95.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user, 30);

    expect($result->daysAnalyzed)->toBeGreaterThan(0);

    $hasCorrectDaysInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, $result->daysAnalyzed.' day')) {
            $hasCorrectDaysInsight = true;
            break;
        }
    }

    expect($hasCorrectDaysInsight)->toBeTrue();
});

it('classifies moderate variability correctly', function (): void {
    $values = [45.0, 75.0, 100.0, 125.0, 155.0];
    foreach ($values as $index => $value) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => $value,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('moderate');
});

it('classifies high variability correctly', function (): void {
    $values = [30.0, 60.0, 100.0, 160.0, 200.0];
    foreach ($values as $index => $value) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => $value,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('high');
});

it('generates insight when coefficient of variation is null', function (): void {
    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $this->user->id,
        'value' => 100.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        'measured_at' => now()->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->coefficientOfVariation)->toBeNull();
});

it('includes moderate hypoglycemia risk in insights', function (): void {
    for ($i = 0; $i < 28; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 65.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 28),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->hypoglycemiaRisk)->toBe('moderate');

    $hasHypoglycemiaInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'Moderate') && str_contains((string) $insight, 'hypoglycemia')) {
            $hasHypoglycemiaInsight = true;
            break;
        }
    }

    expect($hasHypoglycemiaInsight)->toBeTrue();
});

it('includes moderate hyperglycemia risk in insights', function (): void {
    for ($i = 0; $i < 7; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 160.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 7),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->hyperglycemiaRisk)->toBe('moderate');

    $hasHyperglycemiaInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'Moderate') && str_contains((string) $insight, 'hyperglycemia')) {
            $hasHyperglycemiaInsight = true;
            break;
        }
    }

    expect($hasHyperglycemiaInsight)->toBeTrue();
});

it('generates concern for low time in range', function (): void {
    for ($i = 0; $i < 6; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 160.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 4; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i + 6),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    $hasTIRConcern = false;
    foreach ($result->concerns as $concern) {
        if (str_contains((string) $concern, 'Low time in range')) {
            $hasTIRConcern = true;
            break;
        }
    }

    expect($hasTIRConcern)->toBeTrue();
});

it('generates insight for falling trend with absolute slope', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 120.0 - ($i * 2),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('falling');

    $hasFallingInsight = false;
    foreach ($result->insights as $insight) {
        if (str_contains((string) $insight, 'decreasing') && str_contains((string) $insight, 'per week')) {
            $hasFallingInsight = true;
            break;
        }
    }

    expect($hasFallingInsight)->toBeTrue();
});

it('generates goal for addressing post-meal spikes when postMeal average exists', function (): void {
    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 95.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 155.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
            'measured_at' => now()->subDays(($i * 2) + 1),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toContain('post-meal');
});

it('generates goal for addressing rising trend when slope is significant', function (): void {
    for ($i = 0; $i < 30; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 105.0 - ($i * 0.6),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toContain('rising');
});

it('generates well-controlled maintenance goal when glucose is optimal', function (): void {
    for ($i = 0; $i < 10; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $this->user->id,
            'value' => 100.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toContain('Maintain');
});
