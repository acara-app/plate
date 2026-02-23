<?php

declare(strict_types=1);

use App\Enums\ModelName;

it('has correct model values', function (): void {
    expect(ModelName::GPT_5_MINI->value)->toBe('gpt-5-mini')
        ->and(ModelName::GPT_5_NANO->value)->toBe('gpt-5-nano')
        ->and(ModelName::GEMINI_2_5_FLASH->value)->toBe('gemini-2.5-flash')
        ->and(ModelName::GEMINI_3_FLASH->value)->toBe('gemini-3-flash-preview')
        ->and(ModelName::GEMINI_3_1_PRO->value)->toBe('gemini-3.1-pro-preview');
});

it('returns non-empty name for all models', function (ModelName $model): void {
    expect($model->getName())
        ->toBeString()
        ->not->toBeEmpty();
})->with([
    'GPT-5 Mini' => [ModelName::GPT_5_MINI],
    'GPT-5 Nano' => [ModelName::GPT_5_NANO],
    'Gemini 2.5 Flash' => [ModelName::GEMINI_2_5_FLASH],
    'Gemini 3 Flash' => [ModelName::GEMINI_3_FLASH],
    'Gemini 3.1 Pro' => [ModelName::GEMINI_3_1_PRO],
]);

it('returns non-empty description for all models', function (ModelName $model): void {
    expect($model->getDescription())
        ->toBeString()
        ->not->toBeEmpty();
})->with([
    'GPT-5 Mini' => [ModelName::GPT_5_MINI],
    'GPT-5 Nano' => [ModelName::GPT_5_NANO],
    'Gemini 2.5 Flash' => [ModelName::GEMINI_2_5_FLASH],
    'Gemini 3 Flash' => [ModelName::GEMINI_3_FLASH],
    'Gemini 3.1 Pro' => [ModelName::GEMINI_3_1_PRO],
]);

it('returns correct providers', function (): void {
    expect(ModelName::GPT_5_MINI->getProvider())->toBe('openai')
        ->and(ModelName::GPT_5_NANO->getProvider())->toBe('openai')
        ->and(ModelName::GEMINI_2_5_FLASH->getProvider())->toBe('google')
        ->and(ModelName::GEMINI_3_FLASH->getProvider())->toBe('google')
        ->and(ModelName::GEMINI_3_1_PRO->getProvider())->toBe('google');
});

it('identifies models that require thinking mode', function (): void {
    expect(ModelName::GEMINI_3_FLASH->requiresThinkingMode())->toBeTrue()
        ->and(ModelName::GEMINI_3_1_PRO->requiresThinkingMode())->toBeTrue()
        ->and(ModelName::GEMINI_2_5_FLASH->requiresThinkingMode())->toBeFalse()
        ->and(ModelName::GPT_5_MINI->requiresThinkingMode())->toBeFalse()
        ->and(ModelName::GPT_5_NANO->requiresThinkingMode())->toBeFalse();
});

it('identifies models that support temperature', function (): void {
    expect(ModelName::GPT_5_MINI->supportsTemperature())->toBeFalse()
        ->and(ModelName::GPT_5_NANO->supportsTemperature())->toBeFalse()
        ->and(ModelName::GEMINI_2_5_FLASH->supportsTemperature())->toBeTrue()
        ->and(ModelName::GEMINI_3_FLASH->supportsTemperature())->toBeTrue()
        ->and(ModelName::GEMINI_3_1_PRO->supportsTemperature())->toBeTrue();
});

it('returns correct thinking budget for thinking models', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getThinkingBudget())->toBe(8192)
        ->and(ModelName::GEMINI_3_1_PRO->getThinkingBudget())->toBe(8192)
        ->and(ModelName::GEMINI_2_5_FLASH->getThinkingBudget())->toBeNull()
        ->and(ModelName::GPT_5_MINI->getThinkingBudget())->toBeNull();
});

it('returns correct recommended temperature', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getRecommendedTemperature())->toBe(1.0)
        ->and(ModelName::GEMINI_3_1_PRO->getRecommendedTemperature())->toBe(1.0)
        ->and(ModelName::GEMINI_2_5_FLASH->getRecommendedTemperature())->toBe(0.7)
        ->and(ModelName::GPT_5_MINI->getRecommendedTemperature())->toBe(0.7);
});

it('returns correct minimum max tokens', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getMinMaxTokens())->toBe(16384)
        ->and(ModelName::GEMINI_3_1_PRO->getMinMaxTokens())->toBe(16384)
        ->and(ModelName::GEMINI_2_5_FLASH->getMinMaxTokens())->toBe(8000)
        ->and(ModelName::GPT_5_MINI->getMinMaxTokens())->toBe(8000);
});

it('converts to array correctly', function (): void {
    $array = ModelName::GPT_5_MINI->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['id', 'name', 'description', 'provider'])
        ->and($array['id'])->toBe('gpt-5-mini')
        ->and($array['name'])->toBeString()->not->toBeEmpty()
        ->and($array['description'])->toBeString()->not->toBeEmpty()
        ->and($array['provider'])->toBe('openai');
});

it('returns all available models', function (): void {
    $models = ModelName::getAvailableModels();

    expect($models)->toBeArray()
        ->and($models)->toHaveCount(5)
        ->and($models[0])->toHaveKeys(['id', 'name', 'description', 'provider'])
        ->and($models[0]['id'])->toBe('gpt-5-mini')
        ->and($models[1]['id'])->toBe('gpt-5-nano')
        ->and($models[2]['id'])->toBe('gemini-2.5-flash')
        ->and($models[3]['id'])->toBe('gemini-3-flash-preview')
        ->and($models[4]['id'])->toBe('gemini-3.1-pro-preview');
});

it('returns valid pricing structure for all models', function (ModelName $model): void {
    $pricing = $model->getPricing();

    expect($pricing)
        ->toBeArray()
        ->toHaveKeys(['input', 'output', 'reasoning', 'cache_read'])
        ->and($pricing['input'])->toBeFloat()->toBeGreaterThanOrEqual(0)
        ->and($pricing['output'])->toBeFloat()->toBeGreaterThanOrEqual(0)
        ->and($pricing['reasoning'])->toBeFloat()->toBeGreaterThanOrEqual(0)
        ->and($pricing['cache_read'])->toBeFloat()->toBeGreaterThanOrEqual(0);
})->with([
    'GPT-5 Mini' => [ModelName::GPT_5_MINI],
    'GPT-5 Nano' => [ModelName::GPT_5_NANO],
    'Gemini 2.5 Flash' => [ModelName::GEMINI_2_5_FLASH],
    'Gemini 3 Flash' => [ModelName::GEMINI_3_FLASH],
    'Gemini 3.1 Pro' => [ModelName::GEMINI_3_1_PRO],
]);

it('has reasonable pricing ratios', function (ModelName $model): void {
    $pricing = $model->getPricing();

    // Output should typically be >= input (sanity check)
    expect($pricing['output'])->toBeGreaterThanOrEqual($pricing['input'] * 0.5);

    // Cache read should be cheaper than regular input
    expect($pricing['cache_read'])->toBeLessThan($pricing['input']);
})->with([
    'GPT-5 Mini' => [ModelName::GPT_5_MINI],
    'GPT-5 Nano' => [ModelName::GPT_5_NANO],
    'Gemini 2.5 Flash' => [ModelName::GEMINI_2_5_FLASH],
    'Gemini 3 Flash' => [ModelName::GEMINI_3_FLASH],
    'Gemini 3.1 Pro' => [ModelName::GEMINI_3_1_PRO],
]);
