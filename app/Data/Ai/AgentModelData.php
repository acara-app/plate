<?php

declare(strict_types=1);

namespace App\Data\Ai;

use App\Ai\Agents\AgentRunner;
use App\Models\History;
use Laravel\Ai\AiManager;
use Spatie\LaravelData\Data;

final class AgentModelData extends Data
{
    public function __construct(
        public string $provider,
        public string $model,
    ) {}

    public static function current(): self
    {
        $provider = config()->string('ai.default');

        return new self($provider, resolve(AiManager::class)->textProvider($provider)->defaultTextModel());
    }

    public static function forTurn(?History $paused): self
    {
        $meta = $paused?->chatStreamMeta() ?? [];
        $agentModels = $meta['agent_models'] ?? null;
        $recorded = is_array($agentModels) ? ($agentModels[AgentRunner::class] ?? null) : null;

        if (is_array($recorded) && is_string($recorded['provider'] ?? null) && is_string($recorded['model'] ?? null)) {
            return new self($recorded['provider'], $recorded['model']);
        }

        $model = $meta['model'] ?? $paused?->meta['model'] ?? null;
        $provider = $paused?->meta['provider'] ?? $meta['provider'] ?? null;

        return is_string($model)
            ? new self(is_string($provider) ? $provider : config()->string('ai.default'), $model)
            : self::current();
    }

    /** @return array{provider: string, model: string} */
    public function toSelection(): array
    {
        return ['provider' => $this->provider, 'model' => $this->model];
    }
}
