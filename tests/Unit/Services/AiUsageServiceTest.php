<?php

declare(strict_types=1);

use App\Services\AiUsageService;
use App\Services\ModelPricing;

covers(AiUsageService::class, ModelPricing::class);

it('charges each token category at its own per-million rate', function (): void {
    $cost = new AiUsageService()->calculateCost('gpt-6-sol', [
        'prompt_tokens' => 1_000_000,
        'completion_tokens' => 500_000,
        'reasoning_tokens' => 250_000,
        'cache_read_input_tokens' => 2_000_000,
        'cache_write_input_tokens' => 100_000,
    ]);

    expect($cost)->toEqualWithDelta(10.15, 0.000001);
});

it('prices dated snapshots at their base model and unknown models conservatively', function (string $model, float $expected): void {
    expect(new AiUsageService()->calculateCost($model, ['prompt_tokens' => 1_000_000]))->toBe($expected);
})->with([
    'dated snapshot' => ['gpt-6-luna-2026-03-17', 0.10],
    'unknown model' => ['unknown-model', 2.0],
]);

it('applies scheduled price changes from their effective date', function (): void {
    $this->travelTo('2026-12-31');
    $before = new AiUsageService()->calculateCost('gemini-3.6-flash', ['prompt_tokens' => 1_000_000]);

    $this->travelTo('2027-01-01');

    expect($before)->toBe(0.75)
        ->and(new AiUsageService()->calculateCost('gemini-3.6-flash', ['prompt_tokens' => 1_000_000]))->toBe(1.5);
});
