<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Data\Ai\AgentModelData;
use App\Models\User;

interface ResolvesAgentModel
{
    public function resolve(string $agentClass, ?User $user = null): AgentModelData;
}
