<?php

declare(strict_types=1);

use App\Ai\Agents\FoodItemEstimatorAgent;
use App\Data\FoodItemEstimateData;
use Laravel\Ai\Prompts\AgentPrompt;

covers(FoodItemEstimatorAgent::class);

it('asks for nutrition and a portion of one named food at the stated weight', function (): void {
    expect((new FoodItemEstimatorAgent)->instructions())
        ->toContain('given weight in grams')
        ->toContain('not per 100 g')
        ->toContain('Round every number to 1 decimal place')
        ->toContain('`portion` describes only this food')
        ->toContain('never mention any other food');
});

it('writes the portion in the user language', function (): void {
    expect((new FoodItemEstimatorAgent)->withLanguage('Français', 'fr')->instructions())
        ->toContain('Write `portion` in Français (language code: `fr`)');
});

it('returns the estimate and portion for the renamed food from its name and weight only', function (): void {
    FoodItemEstimatorAgent::fake([['calories' => 20, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'grams' => 35, 'portion' => '35 g (about ¼ cup)']]);

    $estimate = (new FoodItemEstimatorAgent)->estimate('Blueberries', 35.0);

    expect($estimate)->toBeInstanceOf(FoodItemEstimateData::class)
        ->and($estimate->toArray())->toBe(['calories' => 20.0, 'protein' => 0.3, 'carbs' => 5.1, 'fat' => 0.1, 'grams' => 35.0, 'portion' => '35 g (about ¼ cup)']);

    FoodItemEstimatorAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Food: "Blueberries". Weight: 35 g.'));
});
