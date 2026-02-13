<?php

declare(strict_types=1);

use App\Models\HealthEntry;
use App\Models\User;
use App\Services\GlucoseStatisticsService;

beforeEach(function (): void {
    $this->service = new GlucoseStatisticsService;
    $this->user = User::factory()->create();
});

it('returns empty stats for empty collection', function (): void {
    $result = $this->service->calculateTimeInRange(collect());

    expect($result['timeInRange'])->toBe(0.0)
        ->and($result['total'])->toBe(0);
});

it('returns null stats for empty basic stats', function (): void {
    $result = $this->service->calculateBasicStats(collect());

    expect($result['min'])->toBeNull()
        ->and($result['max'])->toBeNull()
        ->and($result['average'])->toBeNull()
        ->and($result['stdDev'])->toBeNull();
});

it('returns null for standard deviation with single reading', function (): void {
    $readings = collect([
        HealthEntry::factory()->make(['glucose_value' => 100.0]),
    ]);

    $stdDev = $this->service->calculateStandardDeviation(collect([100.0]));

    expect($stdDev)->toBeNull();
});

it('returns null for coefficient of variation with empty readings', function (): void {
    $cv = $this->service->calculateCoefficientOfVariation(collect());

    expect($cv)->toBeNull();
});

it('returns null for coefficient of variation when mean is zero', function (): void {
    $readings = collect([
        HealthEntry::factory()->make(['glucose_value' => 0.0]),
    ]);

    $cv = $this->service->calculateCoefficientOfVariation($readings);

    expect($cv)->toBeNull();
});

it('calculates time of day for all periods', function (): void {
    $readings = collect([
        HealthEntry::factory()->make([
            'glucose_value' => 90.0,
            'measured_at' => now()->setTime(8, 0),
        ]),
        HealthEntry::factory()->make([
            'glucose_value' => 100.0,
            'measured_at' => now()->setTime(14, 0),
        ]),
        HealthEntry::factory()->make([
            'glucose_value' => 110.0,
            'measured_at' => now()->setTime(19, 0),
        ]),
        HealthEntry::factory()->make([
            'glucose_value' => 85.0,
            'measured_at' => now()->setTime(23, 0),
        ]),
    ]);

    $result = $this->service->analyzeTimeOfDay($readings);

    expect($result)->toHaveKeys(['morning', 'afternoon', 'evening', 'night'])
        ->and($result['morning']['count'])->toBe(1)
        ->and($result['afternoon']['count'])->toBe(1)
        ->and($result['evening']['count'])->toBe(1)
        ->and($result['night']['count'])->toBe(1);
});

it('returns empty frequency for empty readings', function (): void {
    $result = $this->service->analyzeReadingTypeFrequency(collect());

    expect($result)->toBeEmpty();
});

it('returns null trend for less than 2 readings', function (): void {
    $readings = collect([
        HealthEntry::factory()->make([
            'glucose_value' => 100.0,
            'measured_at' => now(),
        ]),
    ]);

    $result = $this->service->calculateTrend($readings);

    expect($result['slopePerDay'])->toBeNull()
        ->and($result['direction'])->toBeNull();
});

it('detects stable trend when days difference is zero', function (): void {
    $now = now();
    $readings = collect([
        HealthEntry::factory()->make([
            'glucose_value' => 100.0,
            'measured_at' => $now,
        ]),
        HealthEntry::factory()->make([
            'glucose_value' => 105.0,
            'measured_at' => $now,
        ]),
    ]);

    $result = $this->service->calculateTrend($readings);

    expect($result['direction'])->toBe('stable')
        ->and($result['daysDifference'])->toBe(0);
});

it('handles zero denominator in trend calculation', function (): void {
    $now = now();
    // All readings at same time with same value - should return stable with null slope
    $readings = collect([
        HealthEntry::factory()->make([
            'glucose_value' => 100.0,
            'measured_at' => $now,
        ]),
        HealthEntry::factory()->make([
            'glucose_value' => 100.0,
            'measured_at' => $now,
        ]),
    ]);

    $result = $this->service->calculateTrend($readings);

    expect($result['direction'])->toBe('stable')
        ->and($result['slopePerDay'])->toBeNull();
});
