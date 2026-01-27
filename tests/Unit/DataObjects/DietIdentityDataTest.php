<?php

declare(strict_types=1);

use App\DataObjects\DietIdentityData;

test('it can be created with values', function (): void {
    $data = new DietIdentityData(
        goal_choice: 'Lose weight',
        animal_product_choice: 'Omnivore',
        intensity_choice: 'Moderate',
    );

    expect($data->goal_choice)->toBe('Lose weight')
        ->and($data->animal_product_choice)->toBe('Omnivore')
        ->and($data->intensity_choice)->toBe('Moderate');
});

test('it can be created from array', function (): void {
    $data = DietIdentityData::from([
        'goal_choice' => 'Gain muscle',
        'animal_product_choice' => 'Vegan',
        'intensity_choice' => 'High',
    ]);

    expect($data->goal_choice)->toBe('Gain muscle')
        ->and($data->animal_product_choice)->toBe('Vegan')
        ->and($data->intensity_choice)->toBe('High');
});

test('it can be converted to array', function (): void {
    $data = new DietIdentityData(
        goal_choice: 'Maintain',
        animal_product_choice: 'Vegetarian',
        intensity_choice: 'Low',
    );

    expect($data->toArray())->toBe([
        'goal_choice' => 'Maintain',
        'animal_product_choice' => 'Vegetarian',
        'intensity_choice' => 'Low',
    ]);
});
