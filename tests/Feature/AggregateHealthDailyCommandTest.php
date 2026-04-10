<?php

declare(strict_types=1);

use App\Console\Commands\AggregateHealthDailyCommand;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(AggregateHealthDailyCommand::class);

it('aggregates health data for all users by default for yesterday', function (): void {
    $user = User::factory()->create();
    $yesterday = CarbonImmutable::yesterday();

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => $yesterday->addHour(),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily')
        ->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->value_avg)->toBe(72.0);
});

it('aggregates for a specific date', function (): void {
    $user = User::factory()->create();
    $date = '2026-03-15';

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 5000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse($date)->addHour(),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily --date='.$date)
        ->assertSuccessful();
});

it('aggregates for a specific user', function (): void {
    $user = User::factory()->create();
    $yesterday = CarbonImmutable::yesterday();

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => $yesterday->addHour(),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:aggregate-daily --user_id='.$user->id)
        ->assertSuccessful();
});

it('fails with invalid user id', function (): void {
    $this->artisan('health:aggregate-daily --user_id=99999')
        ->assertFailed();
});

it('aggregates a date range', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 3000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse('2026-03-01')->addHour(),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($user)->stepCount()->create([
        'value' => 5000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => CarbonImmutable::parse('2026-03-02')->addHour(),
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
