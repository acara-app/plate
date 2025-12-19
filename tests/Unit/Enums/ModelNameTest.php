<?php

declare(strict_types=1);

use App\Enums\ModelName;
use Prism\Prism\Enums\Provider;

it('has correct model values', function (): void {
    expect(ModelName::GPT_5_MINI->value)->toBe('gpt-5-mini')
        ->and(ModelName::GPT_5_NANO->value)->toBe('gpt-5-nano')
        ->and(ModelName::GEMINI_2_5_FLASH->value)->toBe('gemini-2.5-flash')
        ->and(ModelName::GEMINI_3_FLASH->value)->toBe('gemini-3-flash-preview');
});

it('returns correct names', function (): void {
    expect(ModelName::GPT_5_MINI->getName())->toBe('GPT-5 mini')
        ->and(ModelName::GPT_5_NANO->getName())->toBe('GPT-5 Nano')
        ->and(ModelName::GEMINI_2_5_FLASH->getName())->toBe('Gemini 2.5 Flash')
        ->and(ModelName::GEMINI_3_FLASH->getName())->toBe('Gemini 3 Flash');
});

it('returns correct descriptions', function (): void {
    expect(ModelName::GPT_5_MINI->getDescription())->toBe('Cheapest model, best for smarter tasks')
        ->and(ModelName::GPT_5_NANO->getDescription())->toBe('Cheapest model, best for simpler tasks')
        ->and(ModelName::GEMINI_2_5_FLASH->getDescription())->toBe('Fast and versatile performance across a variety of tasks')
        ->and(ModelName::GEMINI_3_FLASH->getDescription())->toBe("Google's latest model with frontier intelligence built for speed that helps everyone learn, build, and plan anything — faster");
});

it('returns correct providers', function (): void {
    expect(ModelName::GPT_5_MINI->getProvider())->toBe(Provider::OpenAI)
        ->and(ModelName::GPT_5_NANO->getProvider())->toBe(Provider::OpenAI)
        ->and(ModelName::GEMINI_2_5_FLASH->getProvider())->toBe(Provider::Gemini)
        ->and(ModelName::GEMINI_3_FLASH->getProvider())->toBe(Provider::Gemini);
});

it('identifies models that require thinking mode', function (): void {
    expect(ModelName::GEMINI_3_FLASH->requiresThinkingMode())->toBeTrue()
        ->and(ModelName::GEMINI_2_5_FLASH->requiresThinkingMode())->toBeFalse()
        ->and(ModelName::GPT_5_MINI->requiresThinkingMode())->toBeFalse()
        ->and(ModelName::GPT_5_NANO->requiresThinkingMode())->toBeFalse();
});

it('returns correct thinking budget for thinking models', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getThinkingBudget())->toBe(8192)
        ->and(ModelName::GEMINI_2_5_FLASH->getThinkingBudget())->toBeNull()
        ->and(ModelName::GPT_5_MINI->getThinkingBudget())->toBeNull();
});

it('returns correct recommended temperature', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getRecommendedTemperature())->toBe(1.0)
        ->and(ModelName::GEMINI_2_5_FLASH->getRecommendedTemperature())->toBe(0.7)
        ->and(ModelName::GPT_5_MINI->getRecommendedTemperature())->toBe(0.7);
});

it('returns correct minimum max tokens', function (): void {
    expect(ModelName::GEMINI_3_FLASH->getMinMaxTokens())->toBe(16384)
        ->and(ModelName::GEMINI_2_5_FLASH->getMinMaxTokens())->toBe(8000)
        ->and(ModelName::GPT_5_MINI->getMinMaxTokens())->toBe(8000);
});

it('converts to array correctly', function (): void {
    $array = ModelName::GPT_5_MINI->toArray();

    expect($array)->toBeArray()
        ->and($array)->toHaveKeys(['id', 'name', 'description', 'provider'])
        ->and($array['id'])->toBe('gpt-5-mini')
        ->and($array['name'])->toBe('GPT-5 mini')
        ->and($array['description'])->toBe('Cheapest model, best for smarter tasks')
        ->and($array['provider'])->toBe('openai');
});

it('returns all available models', function (): void {
    $models = ModelName::getAvailableModels();

    expect($models)->toBeArray()
        ->and($models)->toHaveCount(4)
        ->and($models[0])->toHaveKeys(['id', 'name', 'description', 'provider'])
        ->and($models[0]['id'])->toBe('gpt-5-mini')
        ->and($models[1]['id'])->toBe('gpt-5-nano')
        ->and($models[2]['id'])->toBe('gemini-2.5-flash')
        ->and($models[3]['id'])->toBe('gemini-3-flash-preview');
});
