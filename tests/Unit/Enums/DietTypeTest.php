<?php

declare(strict_types=1);

use App\Enums\DietType;

it('has correct values', function (): void {
    expect(DietType::Mediterranean->value)->toBe('mediterranean')
        ->and(DietType::LowCarb->value)->toBe('low_carb')
        ->and(DietType::Keto->value)->toBe('keto')
        ->and(DietType::Dash->value)->toBe('dash')
        ->and(DietType::Vegetarian->value)->toBe('vegetarian')
        ->and(DietType::Vegan->value)->toBe('vegan')
        ->and(DietType::Paleo->value)->toBe('paleo')
        ->and(DietType::Balanced->value)->toBe('balanced');
});

it('returns correct labels', function (DietType $diet, string $label): void {
    expect($diet->label())->toBe($label);
})->with([
    'Mediterranean' => [DietType::Mediterranean, 'Mediterranean (Gold Standard)'],
    'Low Carb' => [DietType::LowCarb, 'Low Carb (Diabetic Friendly)'],
    'Keto' => [DietType::Keto, 'Ketogenic (Strict)'],
    'Dash' => [DietType::Dash, 'DASH Diet'],
    'Vegetarian' => [DietType::Vegetarian, 'Vegetarian'],
    'Vegan' => [DietType::Vegan, 'Vegan'],
    'Paleo' => [DietType::Paleo, 'Paleo'],
    'Balanced' => [DietType::Balanced, 'Standard Balanced (USDA)'],
]);

it('returns correct focus descriptions', function (DietType $diet, string $focus): void {
    expect($diet->focus())->toBe($focus);
})->with([
    'Mediterranean' => [DietType::Mediterranean, 'High healthy fats (olive oil), fiber, lean proteins. Low red meat.'],
    'Low Carb' => [DietType::LowCarb, 'Reduced carbohydrates (<130g), increased protein and fats.'],
    'Keto' => [DietType::Keto, 'Extremely low carbohydrate (<20g), very high fat.'],
    'Dash' => [DietType::Dash, 'Low sodium, high potassium, rich in fruits and vegetables.'],
    'Vegetarian' => [DietType::Vegetarian, 'No meat/poultry/fish. Eggs and dairy allowed.'],
    'Vegan' => [DietType::Vegan, 'Strictly plant-based. No animal products.'],
    'Paleo' => [DietType::Paleo, 'Ancestral eating. No grains, legumes, or dairy.'],
    'Balanced' => [DietType::Balanced, 'Moderate mix of all macronutrients per dietary guidelines.'],
]);

it('returns correct macro targets', function (DietType $diet, array $expected): void {
    expect($diet->macroTargets())->toBe($expected);
})->with([
    'Mediterranean' => [DietType::Mediterranean, ['carbs' => 45, 'protein' => 18, 'fat' => 37]],
    'Low Carb' => [DietType::LowCarb, ['carbs' => 20, 'protein' => 35, 'fat' => 45]],
    'Keto' => [DietType::Keto, ['carbs' => 5, 'protein' => 20, 'fat' => 75]],
    'Dash' => [DietType::Dash, ['carbs' => 52, 'protein' => 18, 'fat' => 30]],
    'Vegetarian' => [DietType::Vegetarian, ['carbs' => 55, 'protein' => 15, 'fat' => 30]],
    'Vegan' => [DietType::Vegan, ['carbs' => 60, 'protein' => 14, 'fat' => 26]],
    'Paleo' => [DietType::Paleo, ['carbs' => 30, 'protein' => 35, 'fat' => 35]],
    'Balanced' => [DietType::Balanced, ['carbs' => 50, 'protein' => 20, 'fat' => 30]],
]);

it('macro targets sum to 100 percent', function (DietType $diet): void {
    $targets = $diet->macroTargets();
    $sum = $targets['carbs'] + $targets['protein'] + $targets['fat'];

    expect($sum)->toBe(100);
})->with([
    DietType::Mediterranean,
    DietType::LowCarb,
    DietType::Keto,
    DietType::Dash,
    DietType::Vegetarian,
    DietType::Vegan,
    DietType::Paleo,
    DietType::Balanced,
]);

it('correctly identifies diabetic friendly diets', function (DietType $diet, bool $isFriendly): void {
    expect($diet->isDiabeticFriendly())->toBe($isFriendly);
})->with([
    'Mediterranean (friendly)' => [DietType::Mediterranean, true],
    'Low Carb (friendly)' => [DietType::LowCarb, true],
    'Keto (friendly)' => [DietType::Keto, true],
    'Dash (friendly)' => [DietType::Dash, true],
    'Balanced (friendly)' => [DietType::Balanced, true],
    'Vegetarian (friendly)' => [DietType::Vegetarian, true],
    'Vegan (not friendly)' => [DietType::Vegan, false],
    'Paleo (not friendly)' => [DietType::Paleo, false],
]);

it('returns correct short names', function (DietType $diet, string $shortName): void {
    expect($diet->shortName())->toBe($shortName);
})->with([
    'Mediterranean' => [DietType::Mediterranean, 'Mediterranean'],
    'Low Carb' => [DietType::LowCarb, 'Low Carb'],
    'Keto' => [DietType::Keto, 'Keto'],
    'Dash' => [DietType::Dash, 'DASH'],
    'Vegetarian' => [DietType::Vegetarian, 'Vegetarian'],
    'Vegan' => [DietType::Vegan, 'Vegan'],
    'Paleo' => [DietType::Paleo, 'Paleo'],
    'Balanced' => [DietType::Balanced, 'Balanced'],
]);

it('returns array with all diet types and labels', function (): void {
    $array = DietType::toArray();

    expect($array)->toBeArray()
        ->toHaveCount(8)
        ->toHaveKey('mediterranean', 'Mediterranean (Gold Standard)')
        ->toHaveKey('low_carb', 'Low Carb (Diabetic Friendly)')
        ->toHaveKey('keto', 'Ketogenic (Strict)')
        ->toHaveKey('dash', 'DASH Diet')
        ->toHaveKey('vegetarian', 'Vegetarian')
        ->toHaveKey('vegan', 'Vegan')
        ->toHaveKey('paleo', 'Paleo')
        ->toHaveKey('balanced', 'Standard Balanced (USDA)');
});
