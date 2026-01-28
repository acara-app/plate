<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\BalancedMealPlanSystemProvider;

it('returns a system prompt string with Balanced diet content', function (): void {
    $provider = new BalancedMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Lifestyle Team')
        ->and($result)->toContain('Dietitian')
        ->and($result)->toContain('Home Cook Chef')
        ->and($result)->toContain('MyPlate')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new BalancedMealPlanSystemProvider(DietType::Balanced);
    $result = $provider->run();

    expect($result)
        ->toContain('50% Carb')
        ->and($result)->toContain('20% Protein')
        ->and($result)->toContain('30% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new BalancedMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new BalancedMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new BalancedMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
