<?php

declare(strict_types=1);

namespace App\Ai\Concerns;

use App\Models\User;
use App\Services\Ai\AgentModelContext;

trait HasUsageUser
{
    private ?User $agentModelUser = null;

    public function forModelUser(?User $user): static
    {
        $agent = clone $this;
        $agent->setModelUser($user);

        return $agent;
    }

    public function modelUser(): ?User
    {
        return $this->agentModelUser ?? resolve(AgentModelContext::class)->user();
    }

    protected function setModelUser(?User $user): void
    {
        $this->agentModelUser = $user;
    }
}
