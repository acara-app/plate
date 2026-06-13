<?php

declare(strict_types=1);

use App\Enums\Benchmark\DishType;
use App\Enums\Benchmark\Tranche;
use App\Enums\Benchmark\TruthScope;
use App\Enums\Benchmark\TruthSource;
use App\Models\BenchmarkMeal;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config()->set('filesystems.default', 'local');
    Storage::fake('local');

    $this->photoPath = sys_get_temp_dir().'/golden-plate-'.Str::random(8).'.jpg';
    File::put($this->photoPath, 'fake-image-bytes');
});

afterEach(function (): void {
    File::delete($this->photoPath);
});

it('records a per-item meal with photo, items, and derived truth totals', function (): void {
    $this->artisan('benchmark:add-meal', ['photo' => $this->photoPath])
        ->expectsQuestion('Tranche', 'hand')
        ->expectsQuestion('Collected on', '2026-06-12')
        ->expectsQuestion('Cuisine (lowercase tag, e.g. mongolian, western)', 'western')
        ->expectsQuestion('Dish type', 'whole')
        ->expectsQuestion('Lighting', 'bright')
        ->expectsQuestion('Camera angle', 'angled')
        ->expectsQuestion('Truth scope', 'per-item')
        ->expectsQuestion('Total meal weight (g)', '428')
        ->expectsQuestion('Item name (plain English, prepared state, e.g. "rice, white, cooked")', 'chicken breast, grilled')
        ->expectsConfirmation('Visible in the photo?', 'yes')
        ->expectsQuestion('Weight as served (g)', '150')
        ->expectsQuestion('kcal per 100g', '165')
        ->expectsQuestion('Carbs per 100g', '0')
        ->expectsQuestion('Protein per 100g', '31')
        ->expectsQuestion('Fat per 100g', '3.6')
        ->expectsQuestion('Truth source', 'reference')
        ->expectsQuestion('Truth reference (FDC id, product name, or recipe id)', 'FDC 2646170')
        ->expectsConfirmation('Add another item?', 'yes')
        ->expectsQuestion('Item name (plain English, prepared state, e.g. "rice, white, cooked")', 'rice, white, cooked')
        ->expectsConfirmation('Visible in the photo?', 'yes')
        ->expectsQuestion('Weight as served (g)', '278')
        ->expectsQuestion('kcal per 100g', '130')
        ->expectsQuestion('Carbs per 100g', '28.2')
        ->expectsQuestion('Protein per 100g', '2.7')
        ->expectsQuestion('Fat per 100g', '0.3')
        ->expectsQuestion('Truth source', 'reference')
        ->expectsQuestion('Truth reference (FDC id, product name, or recipe id)', 'FDC 2512381')
        ->expectsConfirmation('Add another item?', 'no')
        ->expectsQuestion('Notes (hidden-ingredient context, conversions, anything odd)', '')
        ->assertSuccessful();

    $meal = BenchmarkMeal::query()->sole();

    expect($meal->code)->toBe('m0001')
        ->and($meal->tranche)->toBe(Tranche::Hand)
        ->and($meal->dish_type)->toBe(DishType::Whole)
        ->and($meal->truth_scope)->toBe(TruthScope::PerItem)
        ->and($meal->total_weight_g)->toBe(428.0)
        ->and($meal->total_kcal)->toBeNull()
        ->and($meal->photo_path)->toBe('benchmark/golden-plates/m0001.jpg')
        ->and($meal->items)->toHaveCount(2);

    Storage::disk('local')->assertExists('benchmark/golden-plates/m0001.jpg');

    $totals = $meal->truthTotals();

    expect($totals->calories)->toBe(608.9)
        ->and($totals->carbs)->toBe(78.4)
        ->and($totals->protein)->toBe(54.0)
        ->and($totals->fat)->toBe(6.2);
});

it('records a meal-only meal with labelled totals and no items', function (): void {
    $this->artisan('benchmark:add-meal', ['photo' => $this->photoPath])
        ->expectsQuestion('Tranche', 'hand')
        ->expectsQuestion('Collected on', '2026-06-12')
        ->expectsQuestion('Cuisine (lowercase tag, e.g. mongolian, western)', 'western')
        ->expectsQuestion('Dish type', 'mixed')
        ->expectsQuestion('Lighting', 'indoor')
        ->expectsQuestion('Camera angle', 'top-down')
        ->expectsQuestion('Truth scope', 'meal-only')
        ->expectsQuestion('Total meal weight (g)', '400')
        ->expectsQuestion('Total kcal', '632')
        ->expectsQuestion('Total carbs (g)', '68')
        ->expectsQuestion('Total protein (g)', '28')
        ->expectsQuestion('Total fat (g)', '27.2')
        ->expectsQuestion('Truth source', 'label')
        ->expectsQuestion('Truth reference (product name as labelled)', 'Brand X beef lasagna 400g')
        ->expectsQuestion('Notes (hidden-ingredient context, conversions, anything odd)', 'label is per-100g')
        ->assertSuccessful();

    $meal = BenchmarkMeal::query()->sole();

    expect($meal->truth_scope)->toBe(TruthScope::MealOnly)
        ->and($meal->truth_source)->toBe(TruthSource::Label)
        ->and($meal->truth_ref)->toBe('Brand X beef lasagna 400g')
        ->and($meal->items)->toHaveCount(0)
        ->and($meal->truthTotals()->calories)->toBe(632.0)
        ->and($meal->truthTotals()->carbs)->toBe(68.0);
});

it('discards the meal when item weights disagree with the total and the collector declines', function (): void {
    $this->artisan('benchmark:add-meal', ['photo' => $this->photoPath])
        ->expectsQuestion('Tranche', 'hand')
        ->expectsQuestion('Collected on', '2026-06-12')
        ->expectsQuestion('Cuisine (lowercase tag, e.g. mongolian, western)', 'western')
        ->expectsQuestion('Dish type', 'whole')
        ->expectsQuestion('Lighting', 'bright')
        ->expectsQuestion('Camera angle', 'angled')
        ->expectsQuestion('Truth scope', 'per-item')
        ->expectsQuestion('Total meal weight (g)', '500')
        ->expectsQuestion('Item name (plain English, prepared state, e.g. "rice, white, cooked")', 'rice, white, cooked')
        ->expectsConfirmation('Visible in the photo?', 'yes')
        ->expectsQuestion('Weight as served (g)', '300')
        ->expectsQuestion('kcal per 100g', '130')
        ->expectsQuestion('Carbs per 100g', '28.2')
        ->expectsQuestion('Protein per 100g', '2.7')
        ->expectsQuestion('Fat per 100g', '0.3')
        ->expectsQuestion('Truth source', 'reference')
        ->expectsQuestion('Truth reference (FDC id, product name, or recipe id)', '')
        ->expectsConfirmation('Add another item?', 'no')
        ->expectsConfirmation('Record the meal anyway?', 'no')
        ->assertFailed();

    expect(BenchmarkMeal::query()->count())->toBe(0);

    Storage::disk('local')->assertMissing('benchmark/golden-plates/m0001.jpg');
});

it('rejects a photo that is not jpg or png', function (): void {
    $heicPath = sys_get_temp_dir().'/golden-plate-'.Str::random(8).'.heic';
    File::put($heicPath, 'fake');

    $this->artisan('benchmark:add-meal', ['photo' => $heicPath])->assertFailed();

    expect(BenchmarkMeal::query()->count())->toBe(0);

    File::delete($heicPath);
});
