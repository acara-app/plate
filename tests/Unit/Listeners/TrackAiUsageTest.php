<?php

declare(strict_types=1);

use App\Services\AiUsageService;

it('calculates cost correctly via service', function (): void {
    $service = new AiUsageService;

    $usage = [
        'prompt_tokens' => 1000,
        'completion_tokens' => 500,
        'cache_read_input_tokens' => 0,
        'reasoning_tokens' => 0,
    ];

    $cost = $service->calculateCost('gemini-3-flash-preview', $usage);

    expect($cost)->toBeGreaterThan(0);
});
