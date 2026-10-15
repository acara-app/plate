<?php

declare(strict_types=1);

namespace App\Data\Billing;

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Ai\AgentModelData;
use App\Services\Ai\ConfiguredAgentModels;
use Spatie\LaravelData\Data;

final class PhotoModel extends Data
{
    /** @param array<string, mixed> $options */
    public function __construct(
        public string $provider,
        public string $model,
        public int $maxTokens = 35000,
        public array $options = [],
    ) {}

    public static function fromAgentModel(AgentModelData $model, int $maxTokens = 35000): self
    {
        return new self($model->provider, $model->model, min($maxTokens, $model->maxOutputTokens), $model->options);
    }

    public static function standard(): self
    {
        return self::fromAgentModel(resolve(ConfiguredAgentModels::class)->forAgent(FoodPhotoAnalyzerAgent::class));
    }
}
