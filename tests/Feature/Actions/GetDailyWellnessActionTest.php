<?php

declare(strict_types=1);

use App\Actions\GetDailyWellnessAction;
use App\Data\DailyWellnessData;
use App\Enums\HealthEntrySource;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Models\HealthSyncSample;
use App\Models\SleepSession;
use App\Models\User;
use App\Services\CumulativeSampleDeduplicator;
use Carbon\CarbonImmutable;

covers(GetDailyWellnessAction::class, CumulativeSampleDeduplicator::class, DailyWellnessData::class);

beforeEach(function (): void {
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $this->day = CarbonImmutable::parse('2026-10-19', 'Asia/Ulaanbaatar');
});

function syncSleep(User $user, string $stage, string $startedAt, string $endedAt, string $source = 'Apple Watch'): void
{
    SleepSession::factory()->create([
        'user_id' => $user->id,
        'stage' => $stage,
        'started_at' => $startedAt,
        'ended_at' => $endedAt,
        'source' => $source,
    ]);
}

function logCheckIn(User $user, string $type, float $value, string $measuredAt = '2026-10-18 16:00:00'): void
{
    HealthSyncSample::factory()->fromWeb()->create([
        'user_id' => $user->id,
        'type_identifier' => $type,
        'value' => $value,
        'unit' => $type === 'selfReportedSleep' ? 'hours' : 'score',
        'measured_at' => $measuredAt,
    ]);
}

it('shows an empty day before anything is synced or logged', function (): void {
    expect(resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day)->toArray())->toBe([
        'steps' => 0,
        'step_goal' => 10000,
        'sleep_hours' => null,
        'sleep_synced' => false,
        'sleep_quality' => null,
        'stress_level' => null,
        'water_ml' => 0,
        'water_synced_ml' => 0,
        'water_goal_ml' => 2000,
        'synced_exercise_minutes' => null,
    ]);
});

it('adds the water logged on the web to the local day’s water synced from Apple Health', function (): void {
    foreach ([
        [HealthEntrySource::MobileSync, 300, '2026-10-19 02:00:00'],
        [HealthEntrySource::MobileSync, 200.4, '2026-10-19 09:00:00'],
        [HealthEntrySource::MobileSync, 900, '2026-10-18 15:00:00'],
        [HealthEntrySource::Web, 750, '2026-10-18 16:00:00'],
        [HealthEntrySource::Web, 1000, '2026-10-19 16:00:00'],
    ] as [$source, $millilitres, $measuredAt]) {
        HealthSyncSample::factory()->create([
            'user_id' => $this->user->id,
            'type_identifier' => 'water',
            'value' => $millilitres,
            'unit' => 'mL',
            'entry_source' => $source,
            'measured_at' => $measuredAt,
        ]);
    }

    $wellness = resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day);

    expect([$wellness->waterMl, $wellness->waterSyncedMl, $wellness->waterGoalMl])->toBe([1250, 500, 2000]);
});

it('totals the local day’s steps without counting Apple Watch and iPhone twice', function (): void {
    foreach ([
        ['Apple Watch', 1000, '2026-10-19 01:00:00'],
        ['Apple Watch', 200, '2026-10-19 01:02:00'],
        ['iPhone', 900, '2026-10-19 01:01:00'],
        ['iPhone', 500, '2026-10-19 03:00:00'],
        ['Apple Watch', 300, '2026-10-19 15:30:00'],
        ['Apple Watch', 700, '2026-10-18 15:00:00'],
        ['Apple Watch', 800, '2026-10-19 16:00:00'],
    ] as [$source, $steps, $measuredAt]) {
        HealthSyncSample::factory()->stepCount()->fromMobileSync()->create([
            'user_id' => $this->user->id,
            'source' => $source,
            'value' => $steps,
            'measured_at' => $measuredAt,
        ]);
    }

    expect(resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day)->steps)->toBe(2000);
});

it('totals the local day’s standalone exercise minutes synced from Apple Health and rounds only the final sum', function (): void {
    foreach ([
        ['2026-10-18 16:00:00', 1.04],
        ['2026-10-19 00:00:00', 1.04],
        ['2026-10-19 15:59:59', 2.04],
        ['2026-10-18 15:59:59', 50],
        ['2026-10-19 16:00:00', 50],
    ] as [$measuredAt, $minutes]) {
        HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
            'user_id' => $this->user->id, 'source' => "Tuvshinjargal’s Apple\u{00A0}Watch", 'value' => $minutes, 'measured_at' => $measuredAt,
        ]);
    }

    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $this->user->id, 'type_identifier' => 'workouts', 'value' => 45, 'measured_at' => '2026-10-19 01:00:00',
    ]);
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $this->user->id, 'group_id' => 'blood-pressure', 'value' => 1, 'measured_at' => '2026-10-19 02:00:00',
    ]);
    HealthSyncSample::factory()->exercise()->fromWeb()->create([
        'user_id' => $this->user->id, 'value' => 30, 'measured_at' => '2026-10-19 03:00:00',
    ]);
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'value' => 50, 'measured_at' => '2026-10-19 01:00:00',
    ]);

    expect(resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day)->syncedExerciseMinutes)->toBe(4.1);
});

it('counts each synced exercise minute once across the watch and the phone', function (array $samples, float $minutes): void {
    foreach ($samples as [$source, $startedAt, $endedAt, $value]) {
        HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
            'user_id' => $this->user->id,
            'source' => $source === 'watch' ? "Tuvshinjargal’s Apple\u{00A0}Watch" : 'Tuvshinjargal’s iPhone',
            'value' => $value,
            'measured_at' => "2026-10-19 {$startedAt}",
            'ended_at' => "2026-10-19 {$endedAt}",
        ]);
    }

    expect(resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day)->syncedExerciseMinutes)->toBe($minutes);
})->with([
    'the watch wins the minutes both recorded' => [[['phone', '02:00:10', '02:02:00', 1.5], ['watch', '02:00:00', '02:02:00', 2]], 2.0],
    'separate minutes in the same five minutes' => [[['watch', '02:00:00', '02:01:00', 1], ['phone', '02:03:00', '02:04:00', 1]], 2.0],
    'a watch minute ending as the phone’s begins' => [[['watch', '02:04:00', '02:05:00', 1], ['phone', '02:05:00', '02:06:00', 1]], 2.0],
    'only the phone time the watch missed' => [[['watch', '02:00:00', '02:01:00', 1], ['phone', '02:00:30', '02:02:30', 2]], 2.5],
    'a minute moved after its recorded end' => [[['watch', '09:00:00', '02:30:00', 30]], 30.0],
]);

it('has no synced exercise total without standalone exercise minutes from Apple Health', function (int $count, ?HealthEntrySource $source, string $type, ?string $groupId): void {
    HealthSyncSample::factory()->exercise()->count($count)->create([
        'user_id' => $this->user->id,
        'entry_source' => $source,
        'type_identifier' => $type,
        'group_id' => $groupId,
        'measured_at' => '2026-10-19 02:00:00',
    ]);

    expect(resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day)->syncedExerciseMinutes)->toBeNull();
})->with([
    'empty day' => [0, null, 'exerciseMinutes', null],
    'web exercise' => [1, HealthEntrySource::Web, 'exerciseMinutes', null],
    'chat exercise' => [1, HealthEntrySource::Chat, 'exerciseMinutes', null],
    'telegram exercise' => [1, HealthEntrySource::Telegram, 'exerciseMinutes', null],
    'mobile exercise' => [1, HealthEntrySource::Mobile, 'exerciseMinutes', null],
    'unspecified source' => [1, null, 'exerciseMinutes', null],
    'synced minute grouped with a reading' => [1, HealthEntrySource::MobileSync, 'exerciseMinutes', 'blood-pressure'],
    'synced workout' => [1, HealthEntrySource::MobileSync, 'workouts', null],
    'synced steps' => [1, HealthEntrySource::MobileSync, 'stepCount', null],
]);

it('keeps a synced minute on its own local day when daylight saving time starts at midnight', function (string $date, ?float $minutes): void {
    $user = User::factory()->create(['timezone' => 'America/Santiago']);
    HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $user->id, 'value' => 1, 'measured_at' => '2026-09-07 03:30:00',
    ]);

    $wellness = resolve(GetDailyWellnessAction::class)->handle($user, CarbonImmutable::parse($date, 'America/Santiago'));

    expect($wellness->syncedExerciseMinutes)->toBe($minutes);
})->with([
    'the day the clocks moved forward' => ['2026-09-06', null],
    'the next day' => ['2026-09-07', 1.0],
]);

it('measures last night’s synced sleep once even when stages overlap, ahead of a logged value', function (): void {
    syncSleep($this->user, SleepSession::STAGE_ASLEEP_CORE, '2026-10-18 15:00:00', '2026-10-18 18:00:00');
    syncSleep($this->user, SleepSession::STAGE_ASLEEP_DEEP, '2026-10-18 18:00:00', '2026-10-18 19:00:00');
    syncSleep($this->user, SleepSession::STAGE_ASLEEP_UNSPECIFIED, '2026-10-18 17:30:00', '2026-10-18 19:30:00', 'Sleep Cycle');
    syncSleep($this->user, SleepSession::STAGE_AWAKE, '2026-10-18 19:30:00', '2026-10-18 19:45:00');
    syncSleep($this->user, SleepSession::STAGE_IN_BED, '2026-10-18 14:30:00', '2026-10-18 22:00:00');
    syncSleep($this->user, SleepSession::STAGE_ASLEEP_CORE, '2026-10-17 15:00:00', '2026-10-17 23:00:00');
    logCheckIn($this->user, 'selfReportedSleep', 8);

    $wellness = resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day);

    expect($wellness->sleepHours)->toBe(4.5)
        ->and($wellness->sleepSynced)->toBeTrue();
});

it('falls back to time in bed when only the phone tracked the night', function (): void {
    syncSleep($this->user, SleepSession::STAGE_IN_BED, '2026-10-18 15:00:00', '2026-10-18 22:30:00', 'iPhone');

    $wellness = resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day);

    expect($wellness->sleepHours)->toBe(7.5)
        ->and($wellness->sleepSynced)->toBeTrue();
});

it('reads sleep totals sent by older versions of the iOS app', function (array $samples, float $hours): void {
    foreach ($samples as [$type, $value]) {
        HealthSyncSample::factory()->fromMobileSync()->create([
            'user_id' => $this->user->id,
            'type_identifier' => $type,
            'value' => $value,
            'unit' => 'hours',
            'measured_at' => '2026-10-18 23:00:00',
        ]);
    }

    logCheckIn($this->user, 'selfReportedSleep', 5);

    $wellness = resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day);

    expect($wellness->sleepHours)->toBe($hours)
        ->and($wellness->sleepSynced)->toBeTrue();
})->with([
    'time asleep wins over time in bed' => [[['timeInBed', 8.0], ['timeAsleep', 7.0]], 7.0],
    'time in bed when that is all the phone sent' => [[['timeInBed', 8.0]], 8.0],
]);

it('shows the day’s logged check-in when nothing was synced', function (): void {
    logCheckIn($this->user, 'selfReportedSleep', 6.5);
    logCheckIn($this->user, 'sleepQuality', 2);
    logCheckIn($this->user, 'stressLevel', 3);
    logCheckIn($this->user, 'stressLevel', 1, '2026-10-17 16:00:00');

    $wellness = resolve(GetDailyWellnessAction::class)->handle($this->user, $this->day);

    expect($wellness->sleepHours)->toBe(6.5)
        ->and($wellness->sleepSynced)->toBeFalse()
        ->and($wellness->sleepQuality)->toBe(SleepQuality::Fair)
        ->and($wellness->stressLevel)->toBe(StressLevel::High);
});
