<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\AgentRunner;
use App\Data\Ai\AgentModelData;
use App\Models\User;
use Closure;

final class AgentModelContext
{
    private ?User $user = null;

    private ?AgentModelData $model = null;

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(?User $user, ?AgentModelData $model, Closure $callback): mixed
    {
        $previous = [$this->user, $this->model];
        $this->user = $user;
        $this->model = $model;

        try {
            return $callback();
        } finally {
            [$this->user, $this->model] = $previous;
        }
    }

    /** @return array<string, array{provider: string, model: string}> */
    public function selections(): array
    {
        return $this->model instanceof AgentModelData
            ? [AgentRunner::class => $this->model->toSelection()]
            : [];
    }

    public function user(): ?User
    {
        return $this->user;
    }
}
