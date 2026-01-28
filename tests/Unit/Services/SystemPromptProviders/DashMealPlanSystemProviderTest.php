<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\DashMealPlanSystemProvider;

it('returns a system prompt string with DASH diet content', function (): void {
    $provider = new DashMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Clinical Team')
        ->and($result)->toContain('Hypertension Specialist')
        ->and($result)->toContain('Sodium')
        ->and($result)->toContain('Potassium')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new DashMealPlanSystemProvider(DietType::Dash);
    $result = $provider->run();

    expect($result)
        ->toContain('52% Carb')
        ->and($result)->toContain('18% Protein')
        ->and($result)->toContain('30% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new DashMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new DashMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('valid JSON and ONLY JSON')
        ->and($result)->toContain('json_decode()');
});

it('includes tools usage rules', function (): void {
    $provider = new DashMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
