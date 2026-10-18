<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Data\FoodItemEstimateData;

covers(FoodItemEstimatorAgent::class);

it('asks for nutrition of one named food at the stated weight', function (): void {
    expect((new FoodItemEstimatorAgent)->instructions())
        ->toContain('given weight in grams')
        ->toContain('not per 100 g')
        ->toContain('Round every number to 1 decimal place');
});

it('returns the estimate for the renamed food', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 20, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'grams' => 35]]);

    $estimate = (new FoodItemEstimatorAgent)->estimate('Blueberries', 35.0, '8-9 pieces');

    expect($estimate)->toBeInstanceOf(FoodItemEstimateData::class)
        ->and($estimate->toArray())->toBe(['calories' => 20.0, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'grams' => 35.0]);
});
