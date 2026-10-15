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

    /**
     * @template TResult
     *
     * @param  array<string, array<string, mixed>>  $models
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(?User $user, array $models, Closure $callback): mixed
    {
        $previousUser = $this->user;
        $previousModels = $this->models;
        $this->user = $user;
        $this->models = $models;

        try {
            return $callback();
        } finally {
            $this->user = $previousUser;
            $this->models = $previousModels;
        }
    }

    /** @return array<string, array<string, mixed>> */
    public function snapshot(): array
    {
        return $this->models;
    }

    public function user(): ?User
    {
        return $this->user;
    }

    public function resolve(string $agentClass, ?User $user = null): AgentModelData
    {
        if (($user === null || $user->is($this->user)) && isset($this->models[$agentClass])) {
            return AgentModelData::from($this->models[$agentClass]);
        }

        return resolve(ResolvesAgentModel::class)->resolve($agentClass, $user ?? $this->user);
    }
}
