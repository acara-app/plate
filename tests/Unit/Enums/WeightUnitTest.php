<?php

declare(strict_types=1);

use App\Enums\WeightUnit;

covers(WeightUnit::class);

it('returns same value for kg', function (): void {
    expect(WeightUnit::Kg->toKg(75.0))->toBe(75.0);
});

it('converts lb to kg', function (): void {
    $result = WeightUnit::Lb->toKg(100.0);

    expect($result)->toBe(45.359237);
});

it('converts kilograms back to the unit', function (): void {
    expect(WeightUnit::Kg->fromKg(90.0))->toBe(90.0)
        ->and(WeightUnit::Lb->fromKg(45.359237))->toEqualWithDelta(100.0, 0.000001);
});

it('reads common spellings of weight units', function (mixed $input, ?WeightUnit $unit): void {
    expect(WeightUnit::fromInput($input))->toBe($unit);
})->with([
    'kg' => ['kg', WeightUnit::Kg],
    'kilograms' => [' Kilograms ', WeightUnit::Kg],
    'lbs' => ['lbs', WeightUnit::Lb],
    'pounds' => ['Pounds', WeightUnit::Lb],
    'stone' => ['stone', null],
    'not a string' => [12, null],
]);
