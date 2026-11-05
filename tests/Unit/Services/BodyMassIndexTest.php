<?php

declare(strict_types=1);

use App\Services\BodyMassIndex;

covers(BodyMassIndex::class);

it('decides whether a weight and height make a plausible adult BMI', function (float $weightKg, float $heightCm, bool $isPlausible): void {
    expect(BodyMassIndex::isPlausible($weightKg, $heightCm))->toBe($isPlausible);
})->with([
    'typical adult' => [70.0, 175.0, true],
    'pounds typed as kilograms' => [193.0, 175.0, false],
    'the same weight read as pounds' => [87.54, 175.0, true],
    'just under the upper limit' => [137.81, 175.0, true],
    'at the upper limit' => [137.82, 175.0, false],
    'at the lower limit' => [45.94, 175.0, true],
    'just under the lower limit' => [45.93, 175.0, false],
    'height typed in inches' => [80.0, 70.0, false],
    'zero height' => [70.0, 0.0, false],
    'zero weight' => [0.0, 175.0, false],
    'negative weight' => [-70.0, 175.0, false],
]);

it('recognises weights that only make sense when read as pounds', function (float $weightKg, float $heightCm, bool $looksLikePounds): void {
    expect(BodyMassIndex::looksLikePounds($weightKg, $heightCm))->toBe($looksLikePounds);
})->with([
    'pounds typed as kilograms' => [193.0, 175.0, true],
    'typical adult' => [70.0, 175.0, false],
    'height typed in inches' => [80.0, 70.0, false],
    'implausibly low weight' => [30.0, 175.0, false],
    'too high even as pounds' => [450.0, 150.0, false],
]);

it('accepts weights from 20 to 500 kg', function (float $weightKg, bool $inRange): void {
    expect(BodyMassIndex::isWeightInRange($weightKg))->toBe($inRange);
})->with([
    'below the minimum' => [19.99, false],
    'the minimum' => [20.0, true],
    'the maximum' => [500.0, true],
    'above the maximum' => [500.01, false],
]);

it('accepts heights from 50 to 300 cm', function (float $heightCm, bool $inRange): void {
    expect(BodyMassIndex::isHeightInRange($heightCm))->toBe($inRange);
})->with([
    'below the minimum' => [49.99, false],
    'the minimum' => [50.0, true],
    'the maximum' => [300.0, true],
    'above the maximum' => [300.01, false],
]);
