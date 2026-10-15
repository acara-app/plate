<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Data\Ai\AgentModelData;
use App\Models\User;

interface UsesAgentModelProfiles
{
    public function forModelUser(?User $user): static;

    public function usingAgentModel(AgentModelData $model, ?User $user = null): static;

    public function modelUser(): ?User;

    public function agentModel(): AgentModelData;
}
