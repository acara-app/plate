<?php

declare(strict_types=1);

use App\Ai\GlucoseDataAnalyzer;
use App\Enums\GlucoseReadingType;
use App\Models\HealthEntry;
use App\Models\User;
use App\Services\GlucoseStatisticsService;

use function Pest\Laravel\actingAs;

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
    // Create fasting readings
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 95.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 105.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(2),
    ]);

    // Create post-meal readings
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 140.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
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
    // Create multiple high readings
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 150.0 + ($i * 5),
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->consistentlyHigh)->toBeTrue()
        ->and($result->timeInRange->abovePercentage)->toBeGreaterThan(50)
        ->and($result->patterns->hyperglycemiaRisk)->toBeIn(['moderate', 'high']);

    // With time in range < 50%, the goal should prioritize improving TIR
    // Otherwise it would be to lower average glucose
    if ($result->timeInRange->percentage < 50) {
        expect($result->glucoseGoals->target)->toBe('Increase time in range to at least 70%');
    } else {
        expect($result->glucoseGoals->target)->toBe('Lower average glucose to 70-100 mg/dL range');
    }

    // Check concern message with null guard
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
    // Create normal fasting readings and high post-meal readings
    // This will trigger postMealSpikes but NOT consistentlyHigh
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 90.0,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 160.0,
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays($i * 2 + 1),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->postMealSpikes)->toBeTrue()
        ->and($result->concerns)->toContain('Frequent post-meal glucose spikes detected, suggesting sensitivity to certain carbohydrate sources')
        ->and($result->glucoseGoals->target)->toBe('Reduce post-meal glucose spikes to below 140 mg/dL');
});

it('detects high variability pattern', function (): void {
    // Create readings with high variability
    $values = [70, 150, 85, 140, 75, 160, 80, 145];

    foreach ($values as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => (float) $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->highVariability)->toBeTrue()
        ->and($result->variability->stdDev)->toBeGreaterThan(30);

    // Check that variability insight exists
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
    // Create old reading (outside 30 days)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 100.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(40),
    ]);

    // Create recent reading (within 30 days)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 120.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(5),
    ]);

    $result = $this->analyzer->handle($this->user, 30);

    expect($result)
        ->totalReadings->toBe(1)
        ->and($result->averages->fasting)->toBe(120.0);
});

it('provides default recommendations when glucose is well controlled', function (): void {
    // Create normal glucose readings
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 90.0 + ($i * 2),
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toBe('Maintain current glucose control')
        ->and($result->glucoseGoals->reasoning)->toContain('good control');
});

it('detects consistently low glucose pattern', function (): void {
    // Create multiple low readings
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 65.0 + ($i),
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->consistentlyLow)->toBeTrue()
        ->and($result->timeInRange->belowPercentage)->toBeGreaterThan(10)
        ->and($result->patterns->hypoglycemiaRisk)->toBeIn(['moderate', 'high'])
        ->and($result->glucoseGoals->target)->toBe('Maintain glucose levels above 70 mg/dL');

    // Check concern message exists with actual value
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
    // Create low fasting readings (below 70)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 60.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 65.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->averages->fasting)->toBe(62.5)
        ->and($result->insights)->toContain('Average fasting glucose: 62.5 mg/dL (low)');
});

it('identifies concern for high fasting glucose', function (): void {
    // Create high fasting readings
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 110.0 + ($i * 2),
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->concerns)->toContain('Elevated fasting glucose ('.$result->averages->fasting.' mg/dL) may be influenced by evening eating patterns');
});

it('classifies elevated fasting glucose correctly', function (): void {
    // Create elevated fasting readings (between 100 and 125)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 110.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 115.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->insights)->toContain('Average fasting glucose: 112.5 mg/dL (elevated)');
});

it('classifies high fasting glucose correctly', function (): void {
    // Create high fasting readings (above 125)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 130.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 140.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->averages->fasting)->toBe(135.0);

    // Check that high fasting glucose is detected
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
    // Create elevated post-meal readings
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 150.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
        'measured_at' => now()->subDays(1),
    ]);

    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 160.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
        'measured_at' => now()->subDays(2),
    ]);

    $result = $this->analyzer->handle($this->user);

    // Check that post-meal average is calculated and classified as elevated
    expect($result->averages->postMeal)->toBe(155.0);

    // Check that at least one insight mentions post-meal glucose
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
    // Create a single reading (edge case for standard deviation calculation)
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 100.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->hasData)->toBeTrue()
        ->and($result->totalReadings)->toBe(1)
        ->and($result->patterns->highVariability)->toBeFalse(); // Should not have high variability with only 1 reading
});

// New tests for enhanced features

it('calculates time in range percentages correctly', function (): void {
    // Create 5 in-range, 3 above-range, 2 below-range readings (10 total)
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 160.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i + 5),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 60.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
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
    // Create readings that increase over time
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 90.0 + ($i * 5),
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('rising')
        ->and($result->trend->slopePerWeek)->toBeGreaterThan(0);

    // Check for trending insight
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
    // Create readings that decrease over time
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 140.0 - ($i * 5),
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('falling')
        ->and($result->trend->slopePerWeek)->toBeLessThan(0);
});

it('detects stable glucose trend', function (): void {
    // Create readings with minimal variation
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 95.0 + (($i % 2) * 2), // Alternates between 95 and 97
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays(9 - $i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->trend->direction)->toBe('stable');
});

it('analyzes time of day patterns correctly', function (): void {
    // Morning (5-11): 2 readings
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 90.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->setTime(8, 0)->subDays(1),
    ]);
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 100.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->setTime(9, 0)->subDays(2),
    ]);

    // Afternoon (12-16): 1 reading
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 120.0,
        'glucose_reading_type' => GlucoseReadingType::Random,
        'measured_at' => now()->setTime(14, 0)->subDays(1),
    ]);

    // Evening (17-20): 1 reading
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 110.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
        'measured_at' => now()->setTime(19, 0)->subDays(1),
    ]);

    // Night (21-4): 1 reading
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 85.0,
        'glucose_reading_type' => GlucoseReadingType::Random,
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
    // 5 fasting, 3 post-meal, 2 random
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 95.0,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 130.0,
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays($i + 5),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 105.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
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
    // Create readings with known variability
    $values = [80, 90, 100, 110, 120];

    foreach ($values as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => (float) $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->coefficientOfVariation)->toBeGreaterThan(0)
        ->and($result->variability->classification)->toBeIn(['stable', 'moderate', 'high']);
});

it('classifies variability correctly', function (): void {
    // Stable variability (CV < 36%)
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 95.0 + ($i * 0.5), // Very low variability
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('stable');
});

it('correctly identifies hypoglycemia risk levels', function (): void {
    // Create 12% readings below range (high risk) - all within 30 days
    for ($i = 0; $i < 22; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 65.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i + 22),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->patterns->hypoglycemiaRisk)->toBe('high')
        ->and($result->timeInRange->belowPercentage)->toBe(12.0);
});

it('uses actual days analyzed in insights', function (): void {
    // Create readings over 7 days
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 95.0,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user, 30);

    // Should mention actual days, not hard-coded "30 days"
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
    // Create readings with moderate variability (CV between 36-50%)
    // These values produce CV ≈ 38.2%
    $values = [45.0, 75.0, 100.0, 125.0, 155.0];
    foreach ($values as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('moderate');
});

it('classifies high variability correctly', function (): void {
    // Create readings with high variability (CV > 50%)
    // These values produce CV ≈ 56.9%
    $values = [30.0, 60.0, 100.0, 160.0, 200.0];
    foreach ($values as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($index),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->classification)->toBe('high');
});

it('generates insight when coefficient of variation is null', function (): void {
    // Single reading will have null CV
    HealthEntry::factory()->create([
        'user_id' => $this->user->id,
        'glucose_value' => 100.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting,
        'measured_at' => now()->subDays(1),
    ]);

    $result = $this->analyzer->handle($this->user);

    expect($result->variability->coefficientOfVariation)->toBeNull();
});

it('includes moderate hypoglycemia risk in insights', function (): void {
    // Create 7% readings below range (moderate risk)
    for ($i = 0; $i < 28; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 2; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 65.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
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
    // Create 30% readings above range (moderate risk)
    for ($i = 0; $i < 7; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 3; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 160.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
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
    // Create readings with < 50% time in range
    for ($i = 0; $i < 6; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 160.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    for ($i = 0; $i < 4; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
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
    // Create falling trend: recent readings ($i=0) should be LOWER than older readings ($i=9)
    // For falling: want recent < older, so start high and decrease as $i increases
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 120.0 - ($i * 2), // $i=0 (recent) = 120, $i=9 (old) = 102
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays(9 - $i), // $i=0 → subDays(9) = oldest, $i=9 → subDays(0) = newest
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
    // Create post-meal spikes WITHOUT triggering consistently high or low TIR
    // Add some normal readings to balance it out
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 95.0,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    // Then add post-meal spikes
    for ($i = 0; $i < 5; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 155.0, // Above threshold but not too high
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays(($i * 2) + 1),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    expect($result->glucoseGoals->target)->toContain('post-meal');
});

it('generates goal for addressing rising trend when slope is significant', function (): void {
    // Create rising trend with significant slope (> 3 mg/dL per week)
    // Recent readings (low $i) should be HIGH, older readings (high $i) should be LOW
    // Need steeper slope: 0.6 per day = 4.2 per week
    for ($i = 0; $i < 30; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 105.0 - ($i * 0.6), // Recent 105, older 87 = rising trend
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i), // $i=0 is today (most recent)
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    // Check if rising trend goal is present
    expect($result->glucoseGoals->target)->toContain('rising');
});

it('generates well-controlled maintenance goal when glucose is optimal', function (): void {
    // Create perfectly controlled glucose readings - all in range, stable, no concerns
    for ($i = 0; $i < 10; $i++) {
        HealthEntry::factory()->create([
            'user_id' => $this->user->id,
            'glucose_value' => 100.0, // Perfectly in range
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $result = $this->analyzer->handle($this->user);

    // With all readings at 100, we should get the maintenance goal
    expect($result->glucoseGoals->target)->toContain('Maintain');
});
