<?php

declare(strict_types=1);

use App\DataObjects\UserSettingsData;

test('it can be created with default values', function (): void {
    $settings = new UserSettingsData();

    expect($settings->glucoseNotificationsEnabled)->toBeTrue()
        ->and($settings->glucoseNotificationLowThreshold)->toBeNull()
        ->and($settings->glucoseNotificationHighThreshold)->toBeNull();
});

test('it can be created with custom values', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: false,
        glucoseNotificationLowThreshold: 70,
        glucoseNotificationHighThreshold: 180
    );

    expect($settings->glucoseNotificationsEnabled)->toBeFalse()
        ->and($settings->glucoseNotificationLowThreshold)->toBe(70)
        ->and($settings->glucoseNotificationHighThreshold)->toBe(180);
});

test('it can be created from array', function (): void {
    $settings = UserSettingsData::from([
        'glucoseNotificationsEnabled' => true,
        'glucoseNotificationLowThreshold' => 80,
        'glucoseNotificationHighThreshold' => 200,
    ]);

    expect($settings->glucoseNotificationsEnabled)->toBeTrue()
        ->and($settings->glucoseNotificationLowThreshold)->toBe(80)
        ->and($settings->glucoseNotificationHighThreshold)->toBe(200);
});

test('it can be created from empty array with defaults', function (): void {
    $settings = UserSettingsData::from([]);

    expect($settings->glucoseNotificationsEnabled)->toBeTrue()
        ->and($settings->glucoseNotificationLowThreshold)->toBeNull()
        ->and($settings->glucoseNotificationHighThreshold)->toBeNull();
});

test('it can be converted to array', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: false,
        glucoseNotificationLowThreshold: 70,
        glucoseNotificationHighThreshold: 180
    );

    $array = $settings->toArray();

    expect($array)->toEqual([
        'glucose_notifications_enabled' => false,
        'glucose_notification_low_threshold' => 70,
        'glucose_notification_high_threshold' => 180,
    ]);
});

test('it uses config default for low threshold when null', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: true,
        glucoseNotificationLowThreshold: null,
        glucoseNotificationHighThreshold: 180
    );

    expect($settings->effectiveLowThreshold())->toBe(config('glucose.hypoglycemia_threshold'));
});

test('it uses config default for high threshold when null', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: true,
        glucoseNotificationLowThreshold: 70
    );

    expect($settings->effectiveHighThreshold())->toBe(config('glucose.hyperglycemia_threshold'));
});

test('it uses user override for low threshold when set', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: true,
        glucoseNotificationLowThreshold: 80,
        glucoseNotificationHighThreshold: 180
    );

    expect($settings->effectiveLowThreshold())->toBe(80);
});

test('it uses user override for high threshold when set', function (): void {
    $settings = new UserSettingsData(
        glucoseNotificationsEnabled: true,
        glucoseNotificationLowThreshold: 70,
        glucoseNotificationHighThreshold: 200
    );

    expect($settings->effectiveHighThreshold())->toBe(200);
});
