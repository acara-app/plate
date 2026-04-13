<?php

declare(strict_types=1);

use App\Console\Commands\AggregateHealthDailyCommand;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(AggregateHealthDailyCommand::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('aggregates UTC yesterday by default', function (): void {
    CarbonImmutable::setTestNow('2026-04-13 08:00:00 UTC');

    $user = User::factory()->create(['timezone' => 'Asia/Tokyo']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => CarbonImmutable::parse('2026-04-12 14:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily')
        ->expectsOutputToContain('Repaired UTC daily aggregates from 2026-04-11 to 2026-04-12')
        ->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->where('local_date', '2026-04-12')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->timezone)->toBe('UTC')
        ->and($aggregate->value_avg)->toBe(72.0);
});

it('repairs the day before yesterday in default mode', function (): void {
    CarbonImmutable::setTestNow('2026-04-13 08:00:00 UTC');

    $user = User::factory()->create();

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 3000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse('2026-04-11 20:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily')->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'stepCount')
        ->where('local_date', '2026-04-11')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_sum)->toBe(3000.0);
});

it('aggregates for a specific UTC date', function (): void {
    $user = User::factory()->create();
    $date = '2026-03-15';

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 5000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse($date.' 10:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily --date='.$date)
        ->expectsOutputToContain('Aggregated UTC daily health data for 2026-03-15')
        ->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'stepCount')
        ->where('local_date', $date)
        ->first();

    expect($aggregate)->not->toBeNull();
});

it('aggregates for a specific user on UTC yesterday by default', function (): void {
    CarbonImmutable::setTestNow('2026-04-13 08:00:00 UTC');

    $user = User::factory()->create(['timezone' => 'America/Regina']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 80,
        'measured_at' => CarbonImmutable::parse('2026-04-12 01:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily --user_id='.$user->id)
        ->expectsOutputToContain('user '.$user->id.' on 2026-04-12')
        ->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->where('local_date', '2026-04-12')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_avg)->toBe(80.0);
});

it('fails with invalid user id', function (): void {
    $this->artisan('health:aggregate-daily --user_id=99999')
        ->assertFailed();
});

it('aggregates a UTC date range', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 3000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse('2026-03-01 01:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 5000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse('2026-03-02 01:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily --from=2026-03-01 --to=2026-03-02')
        ->assertSuccessful();

    expect(HealthDailyAggregate::query()->count())->toBe(2);
});

it('requires both from and to for range', function (): void {
    $this->artisan('health:aggregate-daily --from=2026-03-01')
        ->assertFailed();
});
