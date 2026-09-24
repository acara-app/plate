<?php

declare(strict_types=1);

use App\Enums\ModelName;

covers(ModelName::class);

it('turns on high thinking for Gemini models only', function (ModelName $model, bool $thinks, ?string $thinkingLevel): void {
    expect($model->requiresThinkingMode())->toBe($thinks)
        ->and($model->getThinkingLevel())->toBe($thinkingLevel);
})->with([
    'a Gemini model' => [ModelName::GEMINI_3_5_FLASH, true, 'high'],
    'an OpenAI model' => [ModelName::GPT_5_4_MINI, false, null],
]);

it('prices every selectable model from its own rate card rather than the fallback', function (ModelName $model): void {
    expect(array_keys(config()->array('plate.model_pricing.models')))->toContain($model->value);
})->with(ModelName::cases());
