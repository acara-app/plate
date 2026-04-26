<?php

declare(strict_types=1);

use App\Models\CaffeineDrink;
use Database\Seeders\CaffeineDrinkSeeder;

beforeEach(function (): void {
    $this->csvPath = base_path('database/data/caffeine_drinks.csv');
    $this->originalCsv = file_get_contents($this->csvPath);
});

afterEach(function (): void {
    file_put_contents($this->csvPath, $this->originalCsv);
});

it('seeds all rows from the caffeine drinks CSV', function (): void {
    $expectedRows = count(file($this->csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) - 1;

    $this->seed(CaffeineDrinkSeeder::class);

    expect(CaffeineDrink::query()->count())->toBe($expectedRows);

    $coffee = CaffeineDrink::query()->where('slug', 'coffee-brewed')->firstOrFail();
    expect($coffee->source)->toBe('USDA FoodData Central')
        ->and($coffee->license_url)->toBe('https://fdc.nal.usda.gov/')
        ->and($coffee->verified_at)->not->toBeNull();
});

it('is idempotent on re-run', function (): void {
    $this->seed(CaffeineDrinkSeeder::class);
    $countAfterFirst = CaffeineDrink::query()->count();

    $this->seed(CaffeineDrinkSeeder::class);

    expect(CaffeineDrink::query()->count())->toBe($countAfterFirst);
});

it('throws when source is missing on a row', function (): void {
    file_put_contents(
        $this->csvPath,
        "name,slug,category,volume_oz,caffeine_mg,source,license_url,attribution,verified_at\n"
        ."\"Test Drink\",test-drink,coffee,8.00,80.00,,https://example.com/,Example,2026-04-26\n"
    );

    $this->seed(CaffeineDrinkSeeder::class);
})->throws(RuntimeException::class, "missing required 'source'");

it('throws when license_url is missing on a row', function (): void {
    file_put_contents(
        $this->csvPath,
        "name,slug,category,volume_oz,caffeine_mg,source,license_url,attribution,verified_at\n"
        ."\"Test Drink\",test-drink,coffee,8.00,80.00,USDA,,Example,2026-04-26\n"
    );

    $this->seed(CaffeineDrinkSeeder::class);
})->throws(RuntimeException::class, "missing required 'license_url'");

it('throws when verified_at is missing on a row', function (): void {
    file_put_contents(
        $this->csvPath,
        "name,slug,category,volume_oz,caffeine_mg,source,license_url,attribution,verified_at\n"
        ."\"Test Drink\",test-drink,coffee,8.00,80.00,USDA,https://example.com/,Example,\n"
    );

    $this->seed(CaffeineDrinkSeeder::class);
})->throws(RuntimeException::class, "missing required 'verified_at'");
