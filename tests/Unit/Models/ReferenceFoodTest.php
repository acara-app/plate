<?php

declare(strict_types=1);

use App\Models\ReferenceFood;

it('scales per-100g macros by gram weight', function (): void {
    $food = new ReferenceFood([
        'calories_per_100g' => 200,
        'protein_per_100g' => 10,
        'carbs_per_100g' => 20,
        'fat_per_100g' => 5,
    ]);

    $macros = $food->macrosFor(150);

    expect($macros->calories)->toBe(300.0)
        ->and($macros->protein)->toBe(15.0)
        ->and($macros->carbs)->toBe(30.0)
        ->and($macros->fat)->toBe(7.5);
});

it('rounds computed macros to one decimal', function (): void {
    $food = new ReferenceFood([
        'calories_per_100g' => 100,
        'protein_per_100g' => 0,
        'carbs_per_100g' => 13.3,
        'fat_per_100g' => 0,
    ]);

    expect($food->macrosFor(90)->carbs)->toBe(12.0);
});

it('excludes foods missing any macro from the nutritionally-complete scope', function (): void {
    ReferenceFood::factory()->create();
    ReferenceFood::factory()->create(['carbs_per_100g' => null]);

    expect(ReferenceFood::query()->nutritionallyComplete()->count())->toBe(1);
});
