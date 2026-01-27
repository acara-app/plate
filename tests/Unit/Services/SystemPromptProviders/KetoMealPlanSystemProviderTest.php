<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\KetoMealPlanSystemProvider;

it('returns a system prompt string with Keto diet content', function (): void {
    $provider = new KetoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Ketogenic Dietitian')
        ->and($result)->toContain('Gourmet Chef')
        ->and($result)->toContain('Ketosis')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new KetoMealPlanSystemProvider(DietType::Keto);
    $result = $provider->run();

    expect($result)
        ->toContain('5% Carb')
        ->and($result)->toContain('20% Protein')
        ->and($result)->toContain('75% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new KetoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new KetoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new KetoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
