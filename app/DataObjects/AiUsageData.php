<?php

declare(strict_types=1);

namespace App\DataObjects;

use Spatie\LaravelData\Data;

final class AiUsageData extends Data
{
    public function __construct(
        public ?int $userId,
        public string $agent,
        public string $model,
        public string $provider,
        public int $promptTokens,
        public int $completionTokens,
        public int $cacheReadInputTokens,
        public int $reasoningTokens,
        public float $cost,
    ) {}

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens + $this->cacheReadInputTokens + $this->reasoningTokens;
    }
}
