<?php

declare(strict_types=1);

use App\Models\UsdaFoundationFood;

uses()->group('models');

it('casts nutrients to array', function (): void {
    $food = UsdaFoundationFood::factory()->create([
        'nutrients' => ['test' => 'value'],
    ]);

    expect($food->nutrients)->toBeArray()
        ->and($food->nutrients)->toBe(['test' => 'value']);
});

it('casts publication date to date', function (): void {
    $food = UsdaFoundationFood::factory()->create([
        'publication_date' => '2025-11-22',
    ]);

    expect($food->publication_date)->toBeInstanceOf(DateTimeInterface::class);
});

it('can be searched by description', function (): void {
    UsdaFoundationFood::factory()->create(['description' => 'Brown Rice']);
    UsdaFoundationFood::factory()->create(['description' => 'White Rice']);
    UsdaFoundationFood::factory()->create(['description' => 'Quinoa']);

    $results = UsdaFoundationFood::query()
        ->where('description', 'LIKE', '%Rice%')
        ->get();

    expect($results)->toHaveCount(2);
});
