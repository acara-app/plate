<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->action = resolve(AggregateHealthDailySamplesAction::class);
});

it('groups samples into the user-local day, not the server day', function (): void {
    $user = User::factory()->create(['timezone' => 'America/New_York']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 70,
        'measured_at' => CarbonImmutable::parse('2026-04-05 23:30:00', 'America/New_York')->setTimezone('UTC'),
        'timezone' => 'America/New_York',
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($user, CarbonImmutable::parse('2026-04-05'));

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->local_date?->toDateString())->toBe('2026-04-05');
});

it('falls back to UTC when the user has no timezone set', function (): void {
    $user = User::factory()->create(['timezone' => null]);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 72,
        'measured_at' => CarbonImmutable::parse('2026-04-05 12:00:00', 'UTC'),
        'timezone' => null,
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($user, CarbonImmutable::parse('2026-04-05'));

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->timezone)->toBe('UTC');
});

it('prefers each samples own timezone field over the user timezone fallback', function (): void {
    $user = User::factory()->create(['timezone' => 'America/New_York']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 75,
        'measured_at' => CarbonImmutable::parse('2026-04-05 23:30:00', 'Asia/Tokyo')->setTimezone('UTC'),
        'timezone' => 'Asia/Tokyo',
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($user, CarbonImmutable::parse('2026-04-05'));

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->local_date?->toDateString())->toBe('2026-04-05');
});
