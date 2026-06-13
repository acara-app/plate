<?php

declare(strict_types=1);

use App\Ai\ThinkingOptions;
use App\Enums\ModelName;
use Laravel\Ai\Enums\Lab;

covers(ThinkingOptions::class);

it('enables Gemini thinking config for a thinking-capable model', function (): void {
    expect(ThinkingOptions::forModel(ModelName::GEMINI_3_5_FLASH, Lab::Gemini))
        ->toBe([
            'thinkingConfig' => [
                'thinkingBudget' => 8192,
                'includeThoughts' => true,
            ],
        ]);
});

it('returns no options for a model that does not use thinking mode', function (): void {
    expect(ThinkingOptions::forModel(ModelName::GPT_5_4_MINI, Lab::OpenAI))->toBe([]);
});
