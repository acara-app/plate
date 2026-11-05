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
