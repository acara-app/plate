<?php

declare(strict_types=1);

namespace App\Ai\Concerns;

use App\Data\Ai\AgentModelData;
use App\Models\User;
use App\Services\Ai\AgentModelContext;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Enums\Lab;
use ReflectionClass;

trait UsesConfiguredAgentModel
{
    private ?AgentModelData $selectedAgentModel = null;

    private ?User $agentModelUser = null;

    public function forModelUser(?User $user): static
    {
        $agent = clone $this;
        $agent->setModelUser($user);

        return $agent;
    }

    public function usingAgentModel(AgentModelData $model, ?User $user = null): static
    {
        $agent = $this->forModelUser($user);
        $agent->selectedAgentModel = $model;

        return $agent;
    }

    public function modelUser(): ?User
    {
        return $this->agentModelUser ?? resolve(AgentModelContext::class)->user();
    }

    public function agentModel(): AgentModelData
    {
        return $this->selectedAgentModel ?? resolve(AgentModelContext::class)->resolve(static::class, $this->agentModelUser);
    }

    public function provider(): string
    {
        return $this->agentModel()->provider;
    }

    public function model(): string
    {
        return $this->agentModel()->model;
    }

    public function maxTokens(): ?int
    {
        $attributes = (new ReflectionClass($this))->getAttributes(MaxTokens::class);
        $requested = $attributes === [] ? null : $attributes[0]->newInstance()->value;

        return $requested === null ? null : min($requested, $this->agentModel()->maxOutputTokens);
    }

    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        $provider = $provider instanceof Lab ? $provider->value : $provider;

        return $provider === $this->provider() ? $this->agentModel()->options : [];
    }

    protected function setModelUser(?User $user): void
    {
        $this->agentModelUser = $user;
        $this->selectedAgentModel = null;
    }
}
