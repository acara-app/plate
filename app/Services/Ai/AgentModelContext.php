<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Contracts\Ai\ResolvesAgentModel;
use App\Data\Ai\AgentModelData;
use App\Models\User;
use Closure;

final class AgentModelContext
{
    private ?User $user = null;

    /** @var array<string, array<string, mixed>> */
    private array $models = [];

    /** @var array<string, AgentModelData> */
    private array $resolved = [];

    /**
     * @template TResult
     *
     * @param  array<string, array<string, mixed>>  $models
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(?User $user, array $models, Closure $callback): mixed
    {
        $previous = [$this->user, $this->models, $this->resolved];
        $this->user = $user;
        $this->models = $models;
        $this->resolved = [];

        try {
            return $callback();
        } finally {
            [$this->user, $this->models, $this->resolved] = $previous;
        }
    }

    /** @return array<string, array{provider: string, model: string}> */
    public function selections(): array
    {
        return resolve(ConfiguredAgentModels::class)->selections($this->models);
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function resolve(string $agentClass, ?User $user = null): AgentModelData
    {
        $user ??= $this->user;

        return $this->resolved[$agentClass.':'.($user->id ?? '')] ??= (! $user instanceof User || $user->is($this->user)) && isset($this->models[$agentClass])
            ? AgentModelData::from($this->models[$agentClass])
            : resolve(ResolvesAgentModel::class)->resolve($agentClass, $user);
    }
}
