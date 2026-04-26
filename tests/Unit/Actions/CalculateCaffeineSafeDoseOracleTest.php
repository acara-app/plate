<?php

declare(strict_types=1);

use App\Actions\CalculateCaffeineSafeDose;

covers(CalculateCaffeineSafeDose::class);

it('matches the constructed cup-count oracle for every sensitivity step', function (int $step) {
    $action = new CalculateCaffeineSafeDose;

    $weightKg = 72.5;
    $perCupMg = 88.0;

    $base = CalculateCaffeineSafeDose::BASE_MG_PER_KG;
    $multiplier = CalculateCaffeineSafeDose::SENSITIVITY_MULTIPLIERS[$step];

    $expectedCups = (int) floor($weightKg * $base * $multiplier / $perCupMg);

    $result = $action->handle(weightKg: $weightKg, sensitivityStep: $step, perCupMg: $perCupMg);

    expect($result->cups)->toBe($expectedCups);
})->with(array_keys(CalculateCaffeineSafeDose::SENSITIVITY_MULTIPLIERS));
