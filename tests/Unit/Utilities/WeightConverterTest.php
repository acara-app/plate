<?php

declare(strict_types=1);

namespace Tests\Unit\Utilities;

use App\Utilities\WeightConverter;
use InvalidArgumentException;

covers(WeightConverter::class);

it('exposes the pound to kilogram conversion factor', function (): void {
    expect(WeightConverter::LB_TO_KG)->toBe(0.45359237);
});

it('returns kilograms unchanged', function (): void {
    expect(WeightConverter::convertToKg(70.0, 'kg'))->toBe(70.0);
});

it('converts pounds to kilograms using the shared constant', function (): void {
    expect(WeightConverter::convertToKg(150.0, 'lb'))
        ->toBe(150.0 * WeightConverter::LB_TO_KG);
});

it('accepts the lbs alias and is case insensitive', function (): void {
    expect(WeightConverter::convertToKg(10.0, 'LBS'))
        ->toBe(10.0 * WeightConverter::LB_TO_KG)
        ->and(WeightConverter::convertToKg(5.0, 'KG'))->toBe(5.0);
});

it('throws on unsupported units', function (): void {
    WeightConverter::convertToKg(1.0, 'stone');
})->throws(InvalidArgumentException::class, 'Unsupported weight unit: stone');
