<?php

declare(strict_types=1);

use App\Actions\SleepSessionAggregator;
use App\Models\HealthDailyAggregate;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;

it('computes per-stage durations from raw sleep events for a single night', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $aggregator = resolve(SleepSessionAggregator::class);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-05 22:00:00',
        'ended_at' => '2026-04-05 23:30:00',
        'stage' => SleepSession::STAGE_ASLEEP_CORE,
        'source' => 'Apple Watch',
    ]);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-05 23:30:00',
        'ended_at' => '2026-04-06 01:00:00',
        'stage' => SleepSession::STAGE_ASLEEP_DEEP,
        'source' => 'Apple Watch',
    ]);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-06 01:00:00',
        'ended_at' => '2026-04-06 02:00:00',
        'stage' => SleepSession::STAGE_ASLEEP_REM,
        'source' => 'Apple Watch',
    ]);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-06 02:00:00',
        'ended_at' => '2026-04-06 02:15:00',
        'stage' => SleepSession::STAGE_AWAKE,
        'source' => 'Apple Watch',
    ]);

    $upserted = $aggregator->handle($user, CarbonImmutable::parse('2026-04-05'));

    expect($upserted)->toBeGreaterThan(0);

    $core = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'coreSleep')
        ->where('local_date', '2026-04-05')
        ->first();

    $deep = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'deepSleep')
        ->where('local_date', '2026-04-05')
        ->first();

    $rem = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'remSleep')
        ->where('local_date', '2026-04-05')
        ->first();

    $awake = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'awakeTime')
        ->where('local_date', '2026-04-05')
        ->first();

    $totalAsleep = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'timeAsleep')
        ->where('local_date', '2026-04-05')
        ->first();

    expect($core)->not->toBeNull()
        ->and((float) $core->value_last)->toBe(1.5);

    expect($deep)->not->toBeNull()
        ->and((float) $deep->value_last)->toBe(1.5);

    expect($rem)->not->toBeNull()
        ->and((float) $rem->value_last)->toBe(1.0);

    expect($awake)->not->toBeNull()
        ->and((float) $awake->value_last)->toBe(0.25);

    expect($totalAsleep)->not->toBeNull()
        ->and((float) $totalAsleep->value_last)->toBe(4.0);
});

it('returns zero when no sleep events exist for the night', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $aggregator = resolve(SleepSessionAggregator::class);

    $upserted = $aggregator->handle($user, CarbonImmutable::parse('2026-04-05'));

    expect($upserted)->toBe(0);
});
