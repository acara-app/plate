<?php

declare(strict_types=1);

namespace App\Enums;

use Laravel\Ai\Enums\Lab;

enum ModelName: string
{
    case GPT_5_MINI = 'gpt-5-mini';
    case GPT_5_NANO = 'gpt-5-nano';
    case GEMINI_2_5_FLASH = 'gemini-2.5-flash';
    case GEMINI_3_FLASH = 'gemini-3-flash-preview';
    case GEMINI_3_1_PRO = 'gemini-3.1-pro-preview';

    /**
     * @return array{id: string, name: string, description: string, provider: string}[]
     */
    public static function getAvailableModels(): array
    {
        return array_map(
            fn (ModelName $model): array => $model->toArray(),
            self::cases()
        );
    }

    public function getName(): string
    {
        return match ($this) {
            self::GPT_5_MINI => 'GPT-5 mini',
            self::GPT_5_NANO => 'GPT-5 Nano',
            self::GEMINI_2_5_FLASH => 'Gemini 2.5 Flash',
            self::GEMINI_3_FLASH => 'Gemini 3 Flash',
            self::GEMINI_3_1_PRO => 'Gemini 3.1 Pro',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::GPT_5_MINI => 'Cheapest model, best for smarter tasks',
            self::GPT_5_NANO => 'Cheapest model, best for simpler tasks',
            self::GEMINI_2_5_FLASH => 'Fast and versatile performance across a variety of tasks',
            self::GEMINI_3_FLASH => 'Google\'s latest model with frontier intelligence built for speed that helps everyone learn, build, and plan anything — faster',
            self::GEMINI_3_1_PRO => "Google's latest Pro model with advanced reasoning and frontier capabilities",
        };
    }

    public function getProvider(): string
    {
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_NANO => 'openai',
            self::GEMINI_2_5_FLASH, self::GEMINI_3_FLASH, self::GEMINI_3_1_PRO => 'google',
        };
    }

    public function labProvider(): string
    {
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_NANO => Lab::OpenAI->value,
            default => Lab::Gemini->value,
        };
    }

    public function supportsWebSearch(): bool
    {
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_NANO => true,
            default => false,
        };
    }

    public function requiresThinkingMode(): bool
    {
        return match ($this) {
            self::GEMINI_3_FLASH, self::GEMINI_3_1_PRO => true,
            default => false,
        };
    }

    public function getThinkingBudget(): ?int
    {
        return match ($this) {
            self::GEMINI_3_FLASH, self::GEMINI_3_1_PRO => 8192,
            default => null,
        };
    }

    public function supportsTemperature(): bool
    {
        return match ($this) {
            self::GPT_5_MINI, self::GPT_5_NANO => false,
            default => true,
        };
    }

    public function getRecommendedTemperature(): float
    {
        return match ($this) {
            self::GEMINI_3_FLASH, self::GEMINI_3_1_PRO => 1.0,
            default => 0.7,
        };
    }

    public function getMinMaxTokens(): int
    {
        return match ($this) {
            self::GEMINI_3_FLASH, self::GEMINI_3_1_PRO => 16384,
            default => 8000,
        };
    }

    /**
     * @return array{input: float, output: float, reasoning: float, cache_read: float}
     */
    public function getPricing(): array
    {
        return match ($this) {
            self::GPT_5_MINI => [
                'input' => 0.15,
                'output' => 0.60,
                'reasoning' => 0.0,
                'cache_read' => 0.075,
            ],
            self::GPT_5_NANO => [
                'input' => 0.10,
                'output' => 0.40,
                'reasoning' => 0.0,
                'cache_read' => 0.05,
            ],
            self::GEMINI_2_5_FLASH => [
                'input' => 0.30,
                'output' => 2.50,
                'reasoning' => 0.0,
                'cache_read' => 0.03,
            ],
            self::GEMINI_3_FLASH => [
                'input' => 0.50,
                'output' => 3.00,
                'reasoning' => 0.0,
                'cache_read' => 0.05,
            ],
            self::GEMINI_3_1_PRO => [
                'input' => 2.00,
                'output' => 12.00,
                'reasoning' => 0.0,
                'cache_read' => 0.20,
            ],
        };
    }

    /**
     * @return array{id: string, name: string, description: string, provider: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->value,
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'provider' => $this->getProvider(),
        ];
    }
}
