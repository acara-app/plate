<?php

declare(strict_types=1);

use App\Services\AiUsageService;

covers(AiUsageService::class);

beforeEach(function (): void {
    config()->set('plate.model_pricing.models.priced-model', [
        'input' => 1.00,
        'output' => 4.00,
        'reasoning' => 2.00,
        'cache_read' => 0.10,
        'cache_write' => 1.50,
    ]);
});

it('charges each token category at its own per-million rate', function (): void {
    $cost = new AiUsageService()->calculateCost('priced-model', [
        'prompt_tokens' => 1_000_000,
        'completion_tokens' => 500_000,
        'reasoning_tokens' => 250_000,
        'cache_read_input_tokens' => 2_000_000,
        'cache_write_input_tokens' => 100_000,
    ]);

    expect($cost)->toEqualWithDelta(3.85, 0.000001);
});

it('prices a dated model snapshot at its base model rate', function (): void {
    $cost = new AiUsageService()->calculateCost('priced-model-2026-03-17', [
        'prompt_tokens' => 1_000_000,
    ]);

    expect($cost)->toBe(1.0);
});

it('uses default pricing for unknown model', function (): void {
    $service = new AiUsageService;

    $usage = [
        'prompt_tokens' => 1000000,
        'completion_tokens' => 1000000,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('unknown-model', $usage);

    expect($cost)->toBe(0.50 + 2.00);
});

it('calculates cost with zero tokens', function (): void {
    $service = new AiUsageService;

    $usage = [
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3.5-flash', $usage);

    expect($cost)->toBe(0.0);
});

it('includes Gemini thinking and current rates in actual photo costs', function (): void {
    expect((new AiUsageService)->calculateCost('gemini-3.5-flash', [
        'prompt_tokens' => 2000,
        'completion_tokens' => 1000,
        'reasoning_tokens' => 1000,
    ]))->toBe(0.021);
});
