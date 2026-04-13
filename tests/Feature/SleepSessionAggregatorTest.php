<?php

declare(strict_types=1);

use App\Actions\SleepSessionAggregator;
use App\Models\HealthDailyAggregate;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(SleepSessionAggregator::class);

it('splits cross-midnight sleep durations by UTC day', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Regina']);
    $aggregator = resolve(SleepSessionAggregator::class);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-05 22:00:00',
        'ended_at' => '2026-04-06 01:00:00',
        'stage' => SleepSession::STAGE_ASLEEP_CORE,
        'source' => 'Apple Watch',
    ]);

    SleepSession::query()->create([
        'user_id' => $user->id,
        'started_at' => '2026-04-06 01:00:00',
        'ended_at' => '2026-04-06 03:00:00',
        'stage' => SleepSession::STAGE_ASLEEP_DEEP,
        'source' => 'Apple Watch',
    ]);

    $upsertedDayFive = $aggregator->handle($user, CarbonImmutable::parse('2026-04-05', 'UTC'));
    $upsertedDaySix = $aggregator->handle($user, CarbonImmutable::parse('2026-04-06', 'UTC'));

    expect($upsertedDayFive)->toBeGreaterThan(0)
        ->and($upsertedDaySix)->toBeGreaterThan(0);

    $dayFiveCore = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'coreSleep')
        ->where('local_date', '2026-04-05')
        ->first();

    $dayFiveAsleep = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'timeAsleep')
        ->where('local_date', '2026-04-05')
        ->first();

    $daySixCore = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'coreSleep')
        ->where('local_date', '2026-04-06')
        ->first();

    $daySixDeep = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'deepSleep')
        ->where('local_date', '2026-04-06')
        ->first();

    $daySixAsleep = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'timeAsleep')
        ->where('local_date', '2026-04-06')
        ->first();

    expect($dayFiveCore)->not->toBeNull()
        ->and((float) $dayFiveCore->value_last)->toBe(2.0)
        ->and($dayFiveCore->timezone)->toBe('UTC')
        ->and($dayFiveAsleep)->not->toBeNull()
        ->and((float) $dayFiveAsleep->value_last)->toBe(2.0)
        ->and($daySixCore)->not->toBeNull()
        ->and((float) $daySixCore->value_last)->toBe(1.0)
        ->and($daySixDeep)->not->toBeNull()
        ->and((float) $daySixDeep->value_last)->toBe(2.0)
        ->and($daySixAsleep)->not->toBeNull()
        ->and((float) $daySixAsleep->value_last)->toBe(3.0);
});

it('returns zero when no sleep events exist for the UTC day', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $aggregator = resolve(SleepSessionAggregator::class);

    $upserted = $aggregator->handle($user, CarbonImmutable::parse('2026-04-05', 'UTC'));

    expect($upserted)->toBe(0);
});
