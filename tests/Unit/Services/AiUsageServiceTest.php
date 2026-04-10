<?php

declare(strict_types=1);

use App\Enums\ModelName;
use App\Services\AiUsageService;

covers(AiUsageService::class);

it('calculates cost for gemini-3-flash model', function (): void {
    $service = new AiUsageService;
    $pricing = ModelName::GEMINI_3_FLASH->getPricing();

    $usage = [
        'prompt_tokens' => 1000000,
        'completion_tokens' => 500000,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3-flash-preview', $usage);

    expect($cost)->toBe($pricing['input'] + ($pricing['output'] * 0.5));
});

it('calculates cost for gemini-3-1-pro model', function (): void {
    $service = new AiUsageService;
    $pricing = ModelName::GEMINI_3_1_PRO->getPricing();

    $usage = [
        'prompt_tokens' => 1000000,
        'completion_tokens' => 1000000,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3.1-pro-preview', $usage);

    expect($cost)->toBe($pricing['input'] + $pricing['output']);
});

it('calculates cost for gpt-5-mini model', function (): void {
    $service = new AiUsageService;
    $pricing = ModelName::GPT_5_MINI->getPricing();

    $usage = [
        'prompt_tokens' => 1000000,
        'completion_tokens' => 500000,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gpt-5-mini', $usage);

    expect($cost)->toBe($pricing['input'] + ($pricing['output'] * 0.5));
});

it('calculates cost with cache reads', function (): void {
    $service = new AiUsageService;
    $pricing = ModelName::GEMINI_3_FLASH->getPricing();

    $usage = [
        'prompt_tokens' => 500000,
        'completion_tokens' => 100000,
        'cache_read_input_tokens' => 500000,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3-flash-preview', $usage);

    expect($cost)->toBe(
        ($pricing['input'] * 0.5) +
        ($pricing['output'] * 0.1) +
        ($pricing['cache_read'] * 0.5)
    );
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

    $cost = $service->calculateCost('gemini-3-flash-preview', $usage);

    expect($cost)->toBe(0.0);
});

it('calculates cost with partial tokens', function (): void {
    $service = new AiUsageService;
    $pricing = ModelName::GEMINI_3_FLASH->getPricing();

    $usage = [
        'prompt_tokens' => 1000,
        'completion_tokens' => 500,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3-flash-preview', $usage);

    $expectedCost = (1000 / 1000000 * $pricing['input']) +
                    (500 / 1000000 * $pricing['output']);

    expect($cost)->toBe($expectedCost);
});
