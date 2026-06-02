<?php

declare(strict_types=1);

use App\Enums\MealPlanType;

covers(MealPlanType::class);

it('maps day counts to the correct plan type', function (int $days, MealPlanType $expected): void {
    expect(MealPlanType::fromDays($days))->toBe($expected);
})->with([
    'one day is weekly' => [1, MealPlanType::Weekly],
    'seven days is weekly' => [7, MealPlanType::Weekly],
    'eight days is monthly' => [8, MealPlanType::Monthly],
    'thirty days is monthly' => [30, MealPlanType::Monthly],
    'thirty one days is custom' => [31, MealPlanType::Custom],
    'ninety days is custom' => [90, MealPlanType::Custom],
]);
