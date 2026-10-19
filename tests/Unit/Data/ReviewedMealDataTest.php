<?php

declare(strict_types=1);

use App\Data\ReviewedMealData;

covers(ReviewedMealData::class);

it('totals fiber only when at least one ingredient has a fiber value', function (array $fibers, ?float $total): void {
    $meal = new ReviewedMealData(
        array_map(fn (?float $fiber): array => ['name' => 'Food', 'calories' => 100, 'fiber' => $fiber], $fibers),
        now()->toImmutable(),
    );

    $data = $meal->toHealthLogDataWithMetadata([]);

    expect($data->fiberGrams)->toBe($total)
        ->and(array_column($data->foodItems ?? [], 'fiber'))->toBe($fibers);
})->with([
    'every ingredient known' => [[3.0, 1.5], 4.5],
    'one ingredient known' => [[null, 2.0], 2.0],
    'none known' => [[null, null], null],
]);
