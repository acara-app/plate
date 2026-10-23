<?php

declare(strict_types=1);

use App\Actions\GetDailyExerciseSummary;
use App\Enums\HealthEntrySource;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(GetDailyExerciseSummary::class);

it('totals the user’s local day using preferred device sources and rounds only the final sum', function (): void {
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $day = CarbonImmutable::parse('2026-10-21', 'Asia/Ulaanbaatar');
    foreach ([
        ['2026-10-20 16:00:00', 1.04],
        ['2026-10-21 00:00:00', 1.04],
        ['2026-10-21 15:59:59', 2.04],
        ['2026-10-20 15:59:59', 50],
        ['2026-10-21 16:00:00', 50],
    ] as [$measuredAt, $value]) {
        HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
            'user_id' => $user->id, 'source' => 'Apple Watch', 'value' => $value, 'measured_at' => $measuredAt,
        ]);
    }
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $user->id, 'source' => 'iPhone', 'value' => 9, 'measured_at' => '2026-10-20 16:00:30',
    ]);
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $user->id, 'type_identifier' => 'workouts', 'value' => 45, 'measured_at' => '2026-10-21 01:00:00',
    ]);
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'source' => 'Apple Watch', 'value' => 50, 'measured_at' => '2026-10-21 01:00:00',
    ]);

    $summary = resolve(GetDailyExerciseSummary::class)->handle($user, $day);

    expect($summary?->toArray())->toBe(['date' => '2026-10-21', 'total_minutes' => 4.1]);
});

it('returns no summary without synced exercise minutes', function (int $count, ?HealthEntrySource $source, string $type): void {
    $user = User::factory()->create();
    $day = CarbonImmutable::parse('2026-10-21', 'UTC');
    HealthSyncSample::factory()->exercise()->count($count)->create([
        'user_id' => $user->id,
        'entry_source' => $source,
        'type_identifier' => $type,
        'measured_at' => $day->setTime(12, 0),
    ]);

    $summary = resolve(GetDailyExerciseSummary::class)->handle($user, $day);

    expect($summary)->toBeNull();
})->with([
    'empty day' => [0, null, 'exerciseMinutes'],
    'web exercise' => [1, HealthEntrySource::Web, 'exerciseMinutes'],
    'chat exercise' => [1, HealthEntrySource::Chat, 'exerciseMinutes'],
    'telegram exercise' => [1, HealthEntrySource::Telegram, 'exerciseMinutes'],
    'mobile exercise' => [1, HealthEntrySource::Mobile, 'exerciseMinutes'],
    'unspecified source' => [1, null, 'exerciseMinutes'],
    'synced workout' => [1, HealthEntrySource::MobileSync, 'workouts'],
    'synced steps' => [1, HealthEntrySource::MobileSync, 'stepCount'],
]);
