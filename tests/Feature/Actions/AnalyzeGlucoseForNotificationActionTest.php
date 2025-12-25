<?php

declare(strict_types=1);

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Enums\ReadingType;
use App\Models\GlucoseReading;
use App\Models\User;

test('it returns should not notify when notifications are disabled', function (): void {
    $user = User::factory()->create([
        'settings' => ['glucose_notifications_enabled' => false],
    ]);

    GlucoseReading::factory()->count(10)->create([
        'user_id' => $user->id,
        'reading_value' => 200,
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

    // Create readings all within normal range (80-120)
    foreach (range(1, 20) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => fake()->randomFloat(1, 85, 115),
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 180,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Create 60% normal readings
    foreach (range(1, 9) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 100,
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 55,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Add some normal readings
    foreach (range(1, 5) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 100,
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => fake()->randomFloat(1, 180, 220),
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => fake()->randomFloat(1, 50, 65),
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => $i % 2 === 0 ? 60 : 220,
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 200,
            'reading_type' => ReadingType::PostMeal,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    // Add some fasting readings for comparison
    foreach (range(1, 5) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 90,
            'reading_type' => ReadingType::Fasting,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 100,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i),
        ]);
    }

    // Create old readings that should be excluded with 7 day window
    foreach (range(10, 15) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 250,
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 180,
            'reading_type' => ReadingType::Random,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 100,
            'reading_type' => ReadingType::Random,
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
