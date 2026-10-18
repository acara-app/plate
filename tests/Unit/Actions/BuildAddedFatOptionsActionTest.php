<?php

declare(strict_types=1);

use App\Actions\BuildAddedFatOptionsAction;
use App\Data\AddedFatOptionData;
use App\Data\FoodAnalysisData;

covers(BuildAddedFatOptionsAction::class);

function analysisWith(array ...$items): FoodAnalysisData
{
    return FoodAnalysisData::from([
        'items' => array_map(fn (array $item): array => [
            'portion' => '1 serving', 'protein' => 0, 'carbs' => 0, ...$item,
        ], $items),
        'total_calories' => 0, 'total_protein' => 0, 'total_carbs' => 0, 'total_fat' => 0, 'confidence' => 80,
    ]);
}

it('offers amounts for added fats the photo cannot show', function (array $item): void {
    $options = resolve(BuildAddedFatOptionsAction::class)->handle(analysisWith($item));

    expect($options)->toHaveKey(0)
        ->and(array_column(array_map(fn (AddedFatOptionData $option): array => $option->toArray(), $options[0]), 'key'))
        ->toBe(['none', 'half_tbsp', 'one_tbsp', 'two_tbsp']);
})->with([
    'olive oil dressing' => [['name' => 'Olive Oil Dressing', 'match_name' => 'olive oil', 'calories' => 119, 'fat' => 13.5, 'grams' => 14]],
    'vinaigrette' => [['name' => 'Balsamic vinaigrette', 'calories' => 72, 'fat' => 7.5, 'carbs' => 1.5, 'grams' => 16]],
    'butter' => [['name' => 'Butter', 'match_name' => 'butter, salted', 'calories' => 102, 'fat' => 11.5, 'grams' => 14]],
    'mayonnaise' => [['name' => 'Mayo', 'calories' => 94, 'fat' => 10.3, 'grams' => 14]],
]);

it('leaves foods that are not added fats alone', function (array $item): void {
    expect(resolve(BuildAddedFatOptionsAction::class)->handle(analysisWith($item)))->toBe([]);
})->with([
    'salad greens' => [['name' => 'Mixed Salad Greens', 'calories' => 17, 'fat' => 0.3, 'grams' => 100]],
    'butter chicken (not mostly fat)' => [['name' => 'Butter chicken', 'calories' => 440, 'fat' => 28, 'protein' => 30, 'grams' => 250]],
    'tuna in oil (not mostly fat)' => [['name' => 'Tuna in oil', 'calories' => 200, 'fat' => 8, 'protein' => 29, 'grams' => 100]],
    'boiled eggs (no whole-word match)' => [['name' => 'Boiled eggs', 'calories' => 155, 'fat' => 11, 'protein' => 13, 'grams' => 100]],
    'no grams' => [['name' => 'Olive oil', 'calories' => 119, 'fat' => 13.5, 'grams' => null]],
    'no calories' => [['name' => 'Olive oil', 'calories' => 0, 'fat' => 0, 'grams' => 14]],
]);

it('rescales the item for each amount and preselects the model guess', function (): void {
    $options = resolve(BuildAddedFatOptionsAction::class)->handle(analysisWith(
        ['name' => 'Mixed Salad Greens', 'calories' => 17, 'fat' => 0.3, 'grams' => 100],
        ['name' => 'Olive Oil Dressing', 'match_name' => 'olive oil', 'calories' => 119, 'fat' => 13.5, 'grams' => 14],
    ));

    expect($options)->toHaveKeys([1])->not->toHaveKey(0);

    $byKey = collect($options[1])->keyBy('key');

    expect($byKey['none']->toArray())->toMatchArray(['grams' => 0.0, 'calories' => 0.0, 'fat' => 0.0, 'selected' => false])
        ->and($byKey['half_tbsp']->toArray())->toMatchArray(['grams' => 7.0, 'calories' => 59.5, 'fat' => 6.8, 'selected' => false])
        ->and($byKey['one_tbsp']->toArray())->toMatchArray(['grams' => 14.0, 'calories' => 119.0, 'fat' => 13.5, 'selected' => true])
        ->and($byKey['two_tbsp']->toArray())->toMatchArray(['grams' => 28.0, 'calories' => 238.0, 'fat' => 27.0, 'selected' => false]);
});

it('returns nothing when there is no analysis', function (): void {
    expect(resolve(BuildAddedFatOptionsAction::class)->handle(null))->toBe([]);
});
