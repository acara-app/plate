<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\ModelPricing;
use Laravel\Ai\Enums\Lab;

enum ModelName: string
{
    case GPT_5_MINI = 'gpt-5-mini';
    case GPT_5_4_MINI = 'gpt-5.4-mini';
    case GPT_5_NANO = 'gpt-5-nano';
    case GEMINI_3_5_FLASH = 'gemini-3.5-flash';
    case GEMINI_3_1_PRO = 'gemini-3.1-pro-preview';

    public static function default(): self
    {
        return self::GPT_5_4_MINI;
    }

    public function labProvider(): string
    {
        // @codeCoverageIgnoreStart
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_4_MINI, self::GPT_5_NANO => Lab::OpenAI->value,
            default => Lab::Gemini->value,
        };
        // @codeCoverageIgnoreEnd
    }

    public function supportsWebSearch(): bool
    {
        // @codeCoverageIgnoreStart
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_4_MINI, self::GPT_5_NANO => true,
            default => false,
        };
        // @codeCoverageIgnoreEnd
    }

    public function requiresThinkingMode(): bool
    {
        return match ($this) {
            self::GEMINI_3_5_FLASH, self::GEMINI_3_1_PRO => true,
            default => false,
        };
    }

    public function getThinkingLevel(): ?string
    {
        return match ($this) {
            self::GEMINI_3_5_FLASH, self::GEMINI_3_1_PRO => 'high',
            default => null,
        };
    }

    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float, cache_write?: float}
     */
    public function getPricing(): array
    {
        return ModelPricing::forModel($this->value);
    }
}
