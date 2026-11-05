<?php

declare(strict_types=1);

use App\Enums\MeasurementSystem;

covers(MeasurementSystem::class);

it('converts weight to kilograms rounded to the stored precision', function (MeasurementSystem $measurementSystem, float $weight, float $kilograms): void {
    expect($measurementSystem->weightInKilograms($weight))->toBe($kilograms);
})->with([
    'metric stays in kilograms' => [MeasurementSystem::Metric, 70.0, 70.0],
    'metric rounds to two decimals' => [MeasurementSystem::Metric, 70.126, 70.13],
    'imperial converts pounds' => [MeasurementSystem::Imperial, 200.0, 90.72],
    'imperial converts fractional pounds' => [MeasurementSystem::Imperial, 154.3, 69.99],
]);

it('converts feet and inches to centimeters rounded to the stored precision', function (int $feet, float $inches, float $centimeters): void {
    expect(MeasurementSystem::feetAndInchesToCentimeters($feet, $inches))->toBe($centimeters);
})->with([
    '5 ft 10 in' => [5, 10.0, 177.8],
    '6 ft 0 in' => [6, 0.0, 182.88],
    '5 ft 9.5 in' => [5, 9.5, 176.53],
]);
