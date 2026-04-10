<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Tests\Fixtures\HealthSyncSamplesFixture;

it('aggregates the real health_sync_samples-003.csv fixture end-to-end', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Regina']);

    $loaded = HealthSyncSamplesFixture::load($user);
    expect($loaded)->toBe(12912);

    $action = resolve(AggregateHealthDailySamplesAction::class);

    $total = $action->handleDateRange(
        $user,
        CarbonImmutable::parse('2026-04-03'),
        CarbonImmutable::parse('2026-04-09'),
    );

    expect($total)->toBeGreaterThan(0);

    $distinctDays = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->distinct('local_date')
        ->count('local_date');

    expect($distinctDays)->toBeGreaterThanOrEqual(6);

    $invalid = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where(function ($q): void {
            $q->whereNull('aggregation_function')->orWhereNull('aggregation_version');
        })
        ->count();

    expect($invalid)->toBe(0);
});

it('respects interval-based source dedup when aggregating real stepCount data', function (): void {
    $user = User::factory()->create(['timezone' => 'America/Regina']);
    HealthSyncSamplesFixture::load($user);

    $action = resolve(AggregateHealthDailySamplesAction::class);
    $action->handleDateRange(
        $user,
        CarbonImmutable::parse('2026-04-03'),
        CarbonImmutable::parse('2026-04-09'),
    );

    foreach (['2026-04-04', '2026-04-05', '2026-04-06', '2026-04-07', '2026-04-08'] as $day) {
        $groundTruth = HealthSyncSamplesFixture::cumulativeGroundTruth('stepCount', $day);

        $aggregate = HealthDailyAggregate::query()
            ->where('user_id', $user->id)
            ->where('type_identifier', 'stepCount')
            ->where('local_date', $day)
            ->first();

        if ($groundTruth === 0.0) {
            expect($aggregate)->toBeNull(sprintf('Expected no stepCount aggregate for %s when ground-truth is 0', $day));

            continue;
        }

        expect($aggregate)->not->toBeNull('Expected a stepCount aggregate for '.$day)
            ->and((float) $aggregate->value_sum)->toBe(round($groundTruth, 4));
    }
});
