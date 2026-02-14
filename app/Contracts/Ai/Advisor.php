<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Ai\Agents\NutritionAdvisor;
use App\Enums\AgentMode;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Responses\StreamableAgentResponse;

#[Bind(NutritionAdvisor::class)]
interface Advisor extends Agent, Conversational, HasTools
{
    public function withMode(AgentMode $mode): self;

    public function forUser(User $user): self;

    public function stream(string $prompt, array $attachments = [], array|string|null $provider = null, ?string $model = null): StreamableAgentResponse;
}
