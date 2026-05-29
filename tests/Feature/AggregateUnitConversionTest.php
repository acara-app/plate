<?php

declare(strict_types=1);

use App\Exceptions\HealthUnitConversionException;
use App\Services\HealthMetricUnitConverter;

covers(HealthMetricUnitConverter::class);

beforeEach(function (): void {
    $this->converter = resolve(HealthMetricUnitConverter::class);
});

it('converts mmol/L blood glucose to the mg/dL canonical unit', function (): void {
    $result = $this->converter->toCanonical('bloodGlucose', 6.7, 'mmol/L');

    expect($result['canonical_unit'])->toBe('mg/dL')
        ->and($result['value'])->toBeGreaterThan(120.7)
        ->and($result['value'])->toBeLessThan(120.8);
});

it('passes mg/dL glucose through untouched', function (): void {
    $result = $this->converter->toCanonical('bloodGlucose', 100.0, 'mg/dL');

    expect($result['value'])->toBe(100.0)
        ->and($result['canonical_unit'])->toBe('mg/dL')
        ->and($result['original_unit'])->toBe('mg/dL');
});

it('converts pounds to kilograms for weight', function (): void {
    $result = $this->converter->toCanonical('weight', 150.0, 'lb');

    expect($result['canonical_unit'])->toBe('kg')
        ->and(round($result['value'], 2))->toBe(68.04);
});

it('converts miles to kilometres for walking distance', function (): void {
    $result = $this->converter->toCanonical('walkingRunningDistance', 1.0, 'mi');

    expect($result['canonical_unit'])->toBe('km')
        ->and(round($result['value'], 4))->toBe(1.6093);
});

it('converts fahrenheit to celsius with the correct affine offset', function (): void {
    $result = $this->converter->toCanonical('wristTemperature', 98.6, '°F');

    expect($result['canonical_unit'])->toBe('°C')
        ->and(round($result['value'], 1))->toBe(37.0);
});

it('throws HealthUnitConversionException for unknown unit pairs', function (): void {
    $this->converter->toCanonical('weight', 10.0, 'stone');
})->throws(HealthUnitConversionException::class);

it('normalises the iOS shorthand "hrs" to "hours" for sleep types', function (): void {
    $result = $this->converter->toCanonical('timeAsleep', 7.5, 'hrs');

    expect($result['canonical_unit'])->toBe('hours')
        ->and($result['value'])->toBe(7.5);
});

it('passes unknown types through without complaint', function (): void {
    $result = $this->converter->toCanonical('someNewHealthKitMetric', 42.0, 'units');

    expect($result['value'])->toBe(42.0)
        ->and($result['canonical_unit'])->toBe('units');
});

it('converts milligram vitamin D to the microgram canonical unit', function (): void {
    $result = $this->converter->toCanonical('vitaminD', 0.015, 'mg');

    expect($result['canonical_unit'])->toBe('mcg')
        ->and($result['value'])->toBe(15.0)
        ->and($result['original_unit'])->toBe('mg');
});

it('converts milligram folate to micrograms', function (): void {
    $result = $this->converter->toCanonical('folate', 0.4, 'mg');

    expect($result['canonical_unit'])->toBe('mcg')
        ->and($result['value'])->toBe(400.0);
});

it('folds the microgram sign onto the canonical mcg unit for trace minerals', function (): void {
    $result = $this->converter->toCanonical('selenium', 55.0, 'µg');

    expect($result['canonical_unit'])->toBe('mcg')
        ->and($result['value'])->toBe(55.0)
        ->and($result['original_unit'])->toBe('mcg');
});

it('converts sub-gram milligrams to the gram canonical unit for macronutrients', function (): void {
    $result = $this->converter->toCanonical('fiber', 2000.0, 'mg');

    expect($result['canonical_unit'])->toBe('g')
        ->and($result['value'])->toBe(2.0)
        ->and($result['original_unit'])->toBe('mg');
});
