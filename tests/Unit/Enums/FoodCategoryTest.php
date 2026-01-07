<?php

declare(strict_types=1);

use App\Enums\FoodCategory;

it('returns all categories as options', function (): void {
    $options = FoodCategory::options();

    expect($options)
        ->toBeArray()
        ->toHaveKey('fruits', 'Fruits')
        ->toHaveKey('vegetables', 'Vegetables')
        ->toHaveKey('grains_starches', 'Grains & Starches')
        ->toHaveKey('dairy_alternatives', 'Dairy & Alternatives')
        ->toHaveKey('proteins_legumes', 'Proteins & Legumes')
        ->toHaveKey('nuts_seeds', 'Nuts & Seeds')
        ->toHaveKey('beverages', 'Beverages')
        ->toHaveKey('condiments_sauces', 'Condiments & Sauces')
        ->toHaveKey('snacks_sweets', 'Snacks & Sweets')
        ->toHaveKey('other', 'Other');
});

it('returns correct labels for all categories', function (FoodCategory $category, string $label): void {
    expect($category->label())->toBe($label);
})->with([
    'Fruits' => [FoodCategory::Fruits, 'Fruits'],
    'Vegetables' => [FoodCategory::Vegetables, 'Vegetables'],
    'Grains & Starches' => [FoodCategory::GrainsStarches, 'Grains & Starches'],
    'Dairy & Alternatives' => [FoodCategory::DairyAlternatives, 'Dairy & Alternatives'],
    'Proteins & Legumes' => [FoodCategory::ProteinsLegumes, 'Proteins & Legumes'],
    'Nuts & Seeds' => [FoodCategory::NutsSeeds, 'Nuts & Seeds'],
    'Beverages' => [FoodCategory::Beverages, 'Beverages'],
    'Condiments & Sauces' => [FoodCategory::CondimentsSauces, 'Condiments & Sauces'],
    'Snacks & Sweets' => [FoodCategory::SnacksSweets, 'Snacks & Sweets'],
    'Other' => [FoodCategory::Other, 'Other'],
]);

it('returns correct order for all categories', function (FoodCategory $category, int $order): void {
    expect($category->order())->toBe($order);
})->with([
    'Fruits' => [FoodCategory::Fruits, 1],
    'Vegetables' => [FoodCategory::Vegetables, 2],
    'Grains & Starches' => [FoodCategory::GrainsStarches, 3],
    'Dairy & Alternatives' => [FoodCategory::DairyAlternatives, 4],
    'Proteins & Legumes' => [FoodCategory::ProteinsLegumes, 5],
    'Nuts & Seeds' => [FoodCategory::NutsSeeds, 6],
    'Beverages' => [FoodCategory::Beverages, 7],
    'Condiments & Sauces' => [FoodCategory::CondimentsSauces, 8],
    'Snacks & Sweets' => [FoodCategory::SnacksSweets, 9],
    'Other' => [FoodCategory::Other, 99],
]);
