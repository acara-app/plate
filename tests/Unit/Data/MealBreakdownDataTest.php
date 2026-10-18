<?php

declare(strict_types=1);

use App\Data\MealBreakdownData;
use App\Data\ReviewedMealData;

covers(MealBreakdownData::class, ReviewedMealData::class);

it('recovers the weight of an older saved item from its portion text', function (?string $portion, ?float $grams): void {
    $breakdown = MealBreakdownData::fromMetadata(['items' => [['name' => 'Blueberries', 'portion' => $portion, 'calories' => 20]]]);

    expect($breakdown->items[0]['grams'])->toBe($grams);
})->with([
    'grams with a note' => ['25g (approx. 6 olives)', 25.0],
    'spaced grams' => ['35 g (about ¼ cup)', 35.0],
    'spelled out' => ['100 grams', 100.0],
    'decimal comma' => ['12,5 g', 12.5],
    'a count' => ['2 garlic cloves', null],
    'a container' => ['1 bowl', null],
    'no portion' => [null, null],
]);

it('prefers the saved weight over the portion text', function (): void {
    $breakdown = MealBreakdownData::fromMetadata(['items' => [['name' => 'Blueberries', 'portion' => '25g (approx. 6 olives)', 'grams' => 35]]]);

    expect($breakdown->items[0]['grams'])->toBe(35.0);
});
