<?php

declare(strict_types=1);

use App\Console\Commands\RebuildHealthDailyAggregatesCommand;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(RebuildHealthDailyAggregatesCommand::class);

it('rebuilds all aggregates from raw samples with UTC-day semantics', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Regina']);

    HealthDailyAggregate::factory()->for($user)->create([
        'type_identifier' => 'heartRate',
        'local_date' => '2020-01-01',
        'date' => '2020-01-01',
        'timezone' => 'America/Regina',
        'value_avg' => 999,
    ]);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => CarbonImmutable::parse('2026-04-05 12:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:rebuild-daily-aggregates')
        ->expectsOutputToContain('Cleared all existing health_daily_aggregates rows.')
        ->assertSuccessful();

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->where('local_date', '2026-04-05')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->timezone)->toBe('UTC')
        ->and((float) $aggregate->value_avg)->toBe(72.0)
        ->and(HealthDailyAggregate::query()->where('local_date', '2020-01-01')->count())->toBe(0);
});

it('supports scoped rebuild by user and UTC date range', function (): void {
    $targetUser = User::factory()->create();
    $otherUser = User::factory()->create();

    HealthSyncSample::factory()->for($targetUser)->heartRate()->create([
        'value' => 80,
        'measured_at' => CarbonImmutable::parse('2026-04-05 09:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($targetUser)->heartRate()->create([
        'value' => 60,
        'measured_at' => CarbonImmutable::parse('2026-04-06 09:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($otherUser)->heartRate()->create([
        'value' => 90,
        'measured_at' => CarbonImmutable::parse('2026-04-05 09:00:00 UTC'),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->artisan('health:rebuild-daily-aggregates --user_id='.$targetUser->id.' --from=2026-04-05 --to=2026-04-05')
        ->assertSuccessful();

    expect(HealthDailyAggregate::query()
        ->where('user_id', $targetUser->id)
        ->where('local_date', '2026-04-05')
        ->exists())->toBeTrue()
        ->and(HealthDailyAggregate::query()
            ->where('user_id', $targetUser->id)
            ->where('local_date', '2026-04-06')
            ->exists())->toBeFalse()
        ->and(HealthDailyAggregate::query()
            ->where('user_id', $otherUser->id)
            ->exists())->toBeFalse();
});
