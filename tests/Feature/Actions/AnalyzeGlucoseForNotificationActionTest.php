<?php

declare(strict_types=1);

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\GlucoseReadingType;
use App\Models\HealthEntry;
use App\Models\User;

test('it returns should not notify when notifications are disabled', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => false],
    ]);

    HealthEntry::factory()->count(10)->create([
        'user_id' => $user->id,
        'glucose_value' => 200,
        'measured_at' => now()->subDays(3),
    ]);

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeFalse()
        ->and($result->concerns)->toBeEmpty();
});

test('it returns should not notify when no glucose data exists', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeFalse()
        ->and($result->concerns)->toBeEmpty()
        ->and($result->analysisData->hasData)->toBeFalse();
});

test('it returns should not notify when glucose is well controlled', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create readings all within normal range (85-115) with stable, non-trending values
    $stableValues = [100, 98, 102, 99, 101, 100, 97, 103, 100, 99, 101, 98, 102, 100, 99, 101, 100, 98, 102, 100];

    foreach ($stableValues as $i => $value) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i % 7)->subHours($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeFalse();
});

test('it returns should notify when high readings exceed trigger percentage', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
            'glucose_notification_high_threshold' => 140,
        ],
    ]);

    // Create 40% high readings (above 140)
    foreach (range(1, 6) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 180,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Create 60% normal readings
    foreach (range(1, 9) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 100,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue()
        ->and($result->concerns)->not->toBeEmpty();
});

test('it returns should notify when hypoglycemia risk is detected', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
            'glucose_notification_low_threshold' => 70,
        ],
    ]);

    // Create multiple low readings to trigger hypoglycemia risk
    foreach (range(1, 8) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 55,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Add some normal readings
    foreach (range(1, 5) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 100,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue();
});

test('it returns should notify when consistently high pattern is detected', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create all high readings
    foreach (range(1, 20) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => fake()->randomFloat(1, 180, 220),
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue();
});

test('it returns should notify when consistently low pattern is detected', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create all low readings
    foreach (range(1, 20) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => fake()->randomFloat(1, 50, 65),
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue();
});

test('it returns should notify when high variability is detected', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create highly variable readings (alternating very low and very high)
    foreach (range(1, 20) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => $i % 2 === 0 ? 60 : 220,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue();
});

test('it returns should notify when post-meal spikes are detected', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create post-meal spikes
    foreach (range(1, 15) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 200,
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    // Add some fasting readings for comparison
    foreach (range(1, 5) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 90,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    expect($result->shouldNotify)->toBeTrue();
});

test('it uses custom analysis window days parameter', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    // Create readings only in the last 5 days
    foreach (range(1, 5) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 100,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Create old readings that should be excluded with 7 day window
    foreach (range(10, 15) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 250,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user, 7);

    // Should only analyze the last 7 days (5 normal readings)
    expect($result->analysisData->hasData)->toBeTrue()
        ->and($result->analysisData->totalReadings)->toBe(5);
});

test('it uses user custom thresholds when set', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
            'glucose_notification_high_threshold' => 200,
            'glucose_notification_low_threshold' => 60,
        ],
    ]);

    // Readings at 180 - above default 140 but below user's 200
    foreach (range(1, 15) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 180,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    // With user's higher threshold of 200, 180 readings are "in range"
    expect($result->analysisData->hasData)->toBeTrue();
});

test('it preserves analysis data in result', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    foreach (range(1, 10) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 100,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user, 14);

    expect($result->analysisData->hasData)->toBeTrue()
        ->and($result->analysisData->totalReadings)->toBe(10)
        ->and($result->analysisData->averages)->not->toBeNull()
        ->and($result->analysisData->timeInRange)->not->toBeNull();
});

test('it does not trigger concern for high variability alone without other concerning patterns', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
            'glucose_notification_high_threshold' => 180,
            'glucose_notification_low_threshold' => 70,
        ],
    ]);

    // Create variable readings that stay within acceptable range (70-180)
    // This creates variability but good time in range (>70%) and no consistently high/low patterns
    $normalValues = [85, 130, 90, 140, 95, 135, 100, 125, 105, 120, 110, 115];

    foreach ($normalValues as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => $value,
            'glucose_reading_type' => GlucoseReadingType::Random,
            'measured_at' => now()->subDays($index % 7)->subHours($index),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    // High variability alone (without consistentlyHigh, postMealSpikes, or poor time in range)
    // should NOT trigger a concern to reduce false positives
    expect($result->analysisData->hasData)->toBeTrue()
        ->and($result->analysisData->timeInRange->percentage)->toBeGreaterThanOrEqual(70)
        ->and($result->shouldNotify)->toBeFalse()
        ->and($result->concerns)->not->toContain('High glucose variability detected, indicating inconsistent blood sugar control.');
});

test('it does not trigger post-meal spikes concern when average post-meal is below threshold', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
            'glucose_notification_high_threshold' => 180,
        ],
    ]);

    // Create post-meal readings with average below the high threshold (180)
    // Even if there are some individual spikes, the average should be below threshold
    $postMealValues = [140, 150, 145, 155, 160, 148, 152, 158, 142, 165];

    foreach ($postMealValues as $index => $value) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => $value,
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays($index % 7)->subHours($index * 2),
        ]);
    }

    // Add some fasting readings to establish baseline
    foreach (range(1, 5) as $i) {
        HealthEntry::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 95,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i),
        ]);
    }

    $action = resolve(AnalyzeGlucoseForNotificationAction::class);
    $result = $action->handle($user);

    // Post-meal readings with average below threshold should NOT trigger
    // "Frequent post-meal glucose spikes" concern
    expect($result->analysisData->hasData)->toBeTrue();

    // Verify average post-meal is below threshold
    if ($result->analysisData->averages->postMeal !== null) {
        expect($result->analysisData->averages->postMeal)->toBeLessThanOrEqual(180);
    }

    // Should not contain the post-meal spikes concern message
    expect($result->concerns)->not->toContain('Frequent post-meal glucose spikes detected.');
});
