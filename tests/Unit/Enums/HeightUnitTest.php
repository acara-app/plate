<?php

declare(strict_types=1);

use App\Enums\HeightUnit;

covers(HeightUnit::class);

it('converts heights to centimeters', function (HeightUnit $unit, float $value, float $centimeters): void {
    expect($unit->toCentimeters($value))->toEqualWithDelta($centimeters, 0.0001);
})->with([
    'centimeters stay as they are' => [HeightUnit::Cm, 175.0, 175.0],
    'inches' => [HeightUnit::In, 70.0, 177.8],
    'metres' => [HeightUnit::M, 1.75, 175.0],
]);

it('converts centimeters back to the unit', function (HeightUnit $unit, float $centimeters, float $value): void {
    expect($unit->fromCentimeters($centimeters))->toEqualWithDelta($value, 0.0001);
})->with([
    'centimeters' => [HeightUnit::Cm, 175.0, 175.0],
    'inches' => [HeightUnit::In, 177.8, 70.0],
    'metres' => [HeightUnit::M, 175.0, 1.75],
]);

it('reads common spellings of height units', function (mixed $input, ?HeightUnit $unit): void {
    expect(HeightUnit::fromInput($input))->toBe($unit);
})->with([
    'cm' => ['cm', HeightUnit::Cm],
    'centimetres' => ['Centimetres', HeightUnit::Cm],
    'inches' => [' inches ', HeightUnit::In],
    'metres' => ['meters', HeightUnit::M],
    'feet' => ['ft', null],
    'not a string' => [70, null],
]);
