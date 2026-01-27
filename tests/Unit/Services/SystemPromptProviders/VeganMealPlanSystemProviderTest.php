<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\VeganMealPlanSystemProvider;

it('returns a system prompt string with Vegan diet content', function (): void {
    $provider = new VeganMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Plant-Based Culinary Team')
        ->and($result)->toContain('Vegan Nutritionist')
        ->and($result)->toContain('Innovative Chef')
        ->and($result)->toContain('Complete Proteins')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new VeganMealPlanSystemProvider(DietType::Vegan);
    $result = $provider->run();

    expect($result)
        ->toContain('60% Carb')
        ->and($result)->toContain('14% Protein')
        ->and($result)->toContain('26% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new VeganMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new VeganMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new VeganMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
