<?php

declare(strict_types=1);

use App\Actions\GetDailyWellnessAction;
use App\Data\DailyWellnessData;
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
    ]);
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
