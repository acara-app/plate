<?php

declare(strict_types=1);

use App\Actions\CalculateCaffeineSleepCutoff;
use Carbon\CarbonImmutable;

covers(CalculateCaffeineSleepCutoff::class);

it('exposes the half-life and residual threshold constants', function (): void {
    expect(CalculateCaffeineSleepCutoff::HALF_LIFE_HOURS)->toBe(5.0)
        ->and(CalculateCaffeineSleepCutoff::RESIDUAL_MG_THRESHOLD)->toBe(50.0);
});

it('returns null when bedtime is null', function (): void {
    $action = new CalculateCaffeineSleepCutoff;

    expect($action->handle(null, 95.0, 2))->toBeNull();
});

it('returns the bedtime itself when total intake is below the residual threshold', function (): void {
    $action = new CalculateCaffeineSleepCutoff;
    $bedtime = CarbonImmutable::create(2026, 4, 26, 22, 0, 0);

    $result = $action->handle($bedtime, 25.0, 1);

    expect($result)->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->equalTo($bedtime))->toBeTrue();
});

it('returns the bedtime itself when zero cups are consumed', function (): void {
    $action = new CalculateCaffeineSleepCutoff;
    $bedtime = CarbonImmutable::create(2026, 4, 26, 22, 0, 0);

    $result = $action->handle($bedtime, 95.0, 0);

    expect($result)->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->equalTo($bedtime))->toBeTrue();
});

it('subtracts the expected hours based on half-life decay', function (): void {
    $action = new CalculateCaffeineSleepCutoff;
    $bedtime = CarbonImmutable::create(2026, 4, 26, 22, 0, 0);
    $perCupMg = 100.0;
    $cups = 2;

    $totalMg = $perCupMg * $cups;
    $expectedHoursBefore = CalculateCaffeineSleepCutoff::HALF_LIFE_HOURS
        * log($totalMg / CalculateCaffeineSleepCutoff::RESIDUAL_MG_THRESHOLD, 2);
    $expectedCutoff = $bedtime->subSeconds((int) round($expectedHoursBefore * 3600));

    $result = $action->handle($bedtime, $perCupMg, $cups);

    expect($result)->toBeInstanceOf(CarbonImmutable::class)
        ->and($result->equalTo($expectedCutoff))->toBeTrue();
});

it('is deterministic and does not depend on the current time', function (): void {
    $action = new CalculateCaffeineSleepCutoff;
    $bedtime = CarbonImmutable::create(2026, 4, 26, 22, 0, 0);

    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 26, 12, 0, 0));
    $first = $action->handle($bedtime, 95.0, 3);

    CarbonImmutable::setTestNow(CarbonImmutable::create(2030, 1, 1, 0, 0, 0));
    $second = $action->handle($bedtime, 95.0, 3);

    CarbonImmutable::setTestNow();

    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull()
        ->and($first->equalTo($second))->toBeTrue();
});
