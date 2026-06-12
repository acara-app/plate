<?php

declare(strict_types=1);

use App\Models\ReferenceFood;

function foundationFixture(): string
{
    return base_path('tests/Fixtures/reference-foods/foundation-sample-2026-04-30.json');
}

it('imports foundation foods with macros extracted to columns', function (): void {
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])
        ->assertSuccessful();

    expect(ReferenceFood::count())->toBe(3);

    $hummus = ReferenceFood::query()->where('external_id', '321358')->sole();

    expect($hummus->source)->toBe('usda')
        ->and($hummus->data_type)->toBe('foundation')
        ->and($hummus->match_name)->toBe('hummus commercial')
        ->and($hummus->food_category)->toBe('Legumes and Legume Products')
        ->and($hummus->calories_per_100g)->toBe(229.0)
        ->and($hummus->protein_per_100g)->toBe(7.35)
        ->and($hummus->carbs_per_100g)->toBe(14.9)
        ->and($hummus->fat_per_100g)->toBe(17.1)
        ->and($hummus->release)->toBe('USDA Foundation 2026-04-30');
});

it('retains the full nutrient set as a number-keyed map', function (): void {
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])
        ->assertSuccessful();

    $hummus = ReferenceFood::query()->where('external_id', '321358')->sole();

    expect($hummus->nutrients)->toHaveKey('208')
        ->and($hummus->nutrients['208']['name'])->toBe('Energy')
        ->and($hummus->nutrients['208']['unit'])->toBe('kcal')
        ->and((float) $hummus->nutrients['208']['amount'])->toBe(229.0);
});

it('handles null categories, missing macros, and a null nutrient entry', function (): void {
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])
        ->assertSuccessful();

    $spinach = ReferenceFood::query()->where('external_id', '999001')->sole();

    expect($spinach->food_category)->toBeNull()
        ->and($spinach->fat_per_100g)->toBeNull()
        ->and($spinach->protein_per_100g)->toBe(2.86)
        ->and($spinach->calories_per_100g)->toBe(23.0);
});

it('falls back to Atwater energy and tolerates a missing publication date', function (): void {
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])
        ->assertSuccessful();

    $oil = ReferenceFood::query()->where('external_id', '999002')->sole();

    expect($oil->calories_per_100g)->toBe(884.0)
        ->and($oil->publication_date)->toBeNull();
});

it('is idempotent across re-imports', function (): void {
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])->assertSuccessful();
    $this->artisan('nutrition:import-references', ['path' => foundationFixture()])->assertSuccessful();

    expect(ReferenceFood::count())->toBe(3);
});

it('lets an explicit release override the filename-derived default', function (): void {
    $this->artisan('nutrition:import-references', [
        'path' => foundationFixture(),
        '--release' => 'USDA Foundation Foods October 2025',
    ])->assertSuccessful();

    expect(ReferenceFood::query()->where('external_id', '321358')->sole()->release)
        ->toBe('USDA Foundation Foods October 2025');
});

it('fails cleanly when the export file is missing', function (): void {
    $this->artisan('nutrition:import-references', ['path' => '/no/such/export.json'])
        ->assertFailed();

    expect(ReferenceFood::count())->toBe(0);
});
