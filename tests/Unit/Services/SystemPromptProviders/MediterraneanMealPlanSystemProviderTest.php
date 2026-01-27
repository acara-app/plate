<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\MediterraneanMealPlanSystemProvider;

it('returns a system prompt string with Mediterranean diet content', function (): void {
    $provider = new MediterraneanMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Mediterranean Dietitian')
        ->and($result)->toContain('Head Chef')
        ->and($result)->toContain('Score Card')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new MediterraneanMealPlanSystemProvider(DietType::Mediterranean);
    $result = $provider->run();

    expect($result)
        ->toContain('45% Carbs')
        ->and($result)->toContain('18% Protein')
        ->and($result)->toContain('37% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new MediterraneanMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new MediterraneanMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new MediterraneanMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
