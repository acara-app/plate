<?php

declare(strict_types=1);

use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Enums\ReadingType;
use App\Models\GlucoseReading;
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
        GlucoseReading::factory()->create([
            'user_id' => $userWithNotifications->id,
            'reading_value' => 200,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
        ]);
    }

    $userWithoutNotifications = User::factory()->create([
        'email_verified_at' => now(),
        'settings' => ['glucose_notifications_enabled' => false],
    ]);

    foreach (range(1, 15) as $i) {
        GlucoseReading::factory()->create([
            'user_id' => $userWithoutNotifications->id,
            'reading_value' => 200,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
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
        GlucoseReading::factory()->create([
            'user_id' => $unverifiedUser->id,
            'reading_value' => 200,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
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
        GlucoseReading::factory()->create([
            'user_id' => $userWithGoodControl->id,
            'reading_value' => 100,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
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
        GlucoseReading::factory()->create([
            'user_id' => $userWithNullSettings->id,
            'reading_value' => 200,
            'reading_type' => ReadingType::Random,
            'measured_at' => now()->subDays($i % 7),
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
            GlucoseReading::factory()->create([
                'user_id' => $user->id,
                'reading_value' => 200,
                'reading_type' => ReadingType::Random,
                'measured_at' => now()->subDays($j % 7),
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
