<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Services\SystemPromptProviders\PaleoMealPlanSystemProvider;

covers(PaleoMealPlanSystemProvider::class);

it('returns a system prompt string with Paleo diet content', function (): void {
    $provider = new PaleoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toBeString()
        ->and($result)->toContain('Evolutionary Biologist')
        ->and($result)->toContain('Farm-to-Table Chef')
        ->and($result)->toContain('Elimination')
        ->and($result)->toContain('USDA')
        ->and($result)->toContain('IDENTITY AND PURPOSE');
});

it('includes macro nutrient targets in the prompt', function (): void {
    $provider = new PaleoMealPlanSystemProvider(DietType::Paleo);
    $result = $provider->run();

    expect($result)
        ->toContain('30% Carbs')
        ->and($result)->toContain('35% Protein')
        ->and($result)->toContain('35% Fat');
});

it('includes internal assistant steps', function (): void {
    $provider = new PaleoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)->toContain('INTERNAL ASSISTANT STEPS');
});

it('includes output instructions', function (): void {
    $provider = new PaleoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('OUTPUT INSTRUCTIONS')
        ->and($result)->toContain('structured response requested by the schema')
        ->and($result)->toContain('correctly typed nutrition values');
});

it('includes tools usage rules', function (): void {
    $provider = new PaleoMealPlanSystemProvider;
    $result = $provider->run();

    expect($result)
        ->toContain('TOOLS USAGE RULES')
        ->and($result)->toContain('file_search');
});
