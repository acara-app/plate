<?php

declare(strict_types=1);

use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Enums\GlucoseReadingType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Notifications\GlucoseReportNotification;
use Illuminate\Support\Facades\Notification;

test('it processes users with glucose notifications enabled', function (): void {
    Notification::fake();

    $userWithNotifications = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    foreach (range(1, 15) as $i) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $userWithNotifications->id,
            'value' => 200,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i % 7)->subMinutes($i),
        ]);
    }

    $userWithoutNotifications = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => ['glucose_notifications_enabled' => false],
    ]);

    foreach (range(1, 15) as $i) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $userWithoutNotifications->id,
            'value' => 200,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i % 7)->subMinutes($i),
        ]);
    }

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    Notification::assertSentTo($userWithNotifications, GlucoseReportNotification::class);
    Notification::assertNotSentTo($userWithoutNotifications, GlucoseReportNotification::class);
});

test('it does not notify users without verified email', function (): void {
    Notification::fake();

    $unverifiedUser = User::factory()->create([
        'email_verified_at' => null,
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    foreach (range(1, 15) as $i) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $unverifiedUser->id,
            'value' => 200,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i % 7)->subMinutes($i),
        ]);
    }

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($unverifiedUser, GlucoseReportNotification::class);
});

test('it does not notify users with no glucose data', function (): void {
    Notification::fake();

    $userWithNoData = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($userWithNoData, GlucoseReportNotification::class);
});

test('it does not notify users with well-controlled glucose', function (): void {
    Notification::fake();

    $userWithGoodControl = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => ['glucose_notifications_enabled' => true],
    ]);

    foreach (range(1, 20) as $i) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $userWithGoodControl->id,
            'value' => 100,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i % 7)->subMinutes($i),
        ]);
    }

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($userWithGoodControl, GlucoseReportNotification::class);
});

test('it does not process users with null settings', function (): void {
    Notification::fake();

    $userWithNullSettings = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => null,
    ]);

    foreach (range(1, 15) as $i) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $userWithNullSettings->id,
            'value' => 200,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
            'measured_at' => now()->subDays($i % 7)->subMinutes($i),
        ]);
    }

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    Notification::assertNotSentTo($userWithNullSettings, GlucoseReportNotification::class);
});

test('it processes multiple users with concerns', function (): void {
    Notification::fake();

    $users = [];
    foreach (range(1, 3) as $i) {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'settings' => ['glucose_notifications_enabled' => true],
        ]);

        foreach (range(1, 15) as $j) {
            HealthSyncSample::factory()->bloodGlucose()->create([
                'user_id' => $user->id,
                'value' => 200,
                'metadata' => ['glucose_reading_type' => GlucoseReadingType::Random->value],
                'measured_at' => now()->subDays($j % 7)->subMinutes($j),
            ]);
        }

        $users[] = $user;
    }

    $this->artisan(ProcessGlucoseNotificationsCommand::class)
        ->assertSuccessful();

    foreach ($users as $user) {
        Notification::assertSentTo($user, GlucoseReportNotification::class);
    }
});
