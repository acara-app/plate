<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\VegetarianMealPlanSystemProvider;

it('returns a system prompt string with Vegetarian diet content', function (): void {
    $provider = new VegetarianMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Vegetarian Team')
        ->and($result)->toContain('Wellness Dietitian')
        ->and($result)->toContain('Bistro Chef')
        ->and($result)->toContain('No flesh foods')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new VegetarianMealPlanSystemProvider(DietType::Vegetarian);
    $result = $provider->run();

    expect($result)
        ->toContain('55% Carbs')
        ->and($result)->toContain('15% Protein')
        ->and($result)->toContain('30% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new VegetarianMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new VegetarianMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new VegetarianMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
