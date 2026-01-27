<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\LowCarbMealPlanSystemProvider;

it('returns a system prompt string with Low Carb diet content', function (): void {
    $provider = new LowCarbMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Clinical Dietitian')
        ->and($result)->toContain('Metabolic Chef')
        ->and($result)->toContain('Net Carb')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new LowCarbMealPlanSystemProvider(DietType::LowCarb);
    $result = $provider->run();

    expect($result)
        ->toContain('20% Carbs')
        ->and($result)->toContain('35% Protein')
        ->and($result)->toContain('45% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new LowCarbMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new LowCarbMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new LowCarbMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
