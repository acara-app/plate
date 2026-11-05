<?php

declare(strict_types=1);

use App\Enums\HeightUnit;

covers(HeightUnit::class);

it('converts heights to centimeters', function (HeightUnit $unit, float $value, float $centimeters): void {
    expect($unit->toCentimeters($value))->toEqualWithDelta($centimeters, 0.0001);
})->with([
    'centimeters stay as they are' => [HeightUnit::Cm, 175.0, 175.0],
    'inches' => [HeightUnit::In, 70.0, 177.8],
]);
