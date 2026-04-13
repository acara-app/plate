<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(AggregateHealthDailySamplesAction::class);

beforeEach(function (): void {
    $this->action = resolve(AggregateHealthDailySamplesAction::class);
});

it('groups samples into UTC day regardless of user timezone', function (): void {
    $user = User::factory()->create(['timezone' => 'America/New_York']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 70,
        'measured_at' => CarbonImmutable::parse('2026-04-05 23:30:00', 'America/New_York')->setTimezone('UTC'),
        'timezone' => 'America/New_York',
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($user, CarbonImmutable::parse('2026-04-06', 'UTC'));

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->local_date?->toDateString())->toBe('2026-04-06')
        ->and($aggregate->timezone)->toBe('UTC');
});

it('ignores sample timezone for UTC day bucketing', function (): void {
    $user = User::factory()->create(['timezone' => 'America/New_York']);

    HealthSyncSample::factory()->for($user)->heartRate()->create([
        'value' => 75,
        'measured_at' => CarbonImmutable::parse('2026-04-05 23:30:00', 'Asia/Tokyo')->setTimezone('UTC'),
        'timezone' => 'Asia/Tokyo',
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($user, CarbonImmutable::parse('2026-04-05', 'UTC'));

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->local_date?->toDateString())->toBe('2026-04-05')
        ->and($aggregate->timezone)->toBe('UTC');
});

it('returns zero when no samples exist in the UTC date range', function (): void {
    $user = User::factory()->create(['timezone' => null]);

    $result = $this->action->handle($user, CarbonImmutable::parse('2026-04-05', 'UTC'));

    expect($result)->toBe(0);
});
