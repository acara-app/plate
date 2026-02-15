<?php

declare(strict_types=1);

namespace App\Contracts\Ai;

use App\Ai\Agents\AssistantAgent;
use App\Enums\AgentMode;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Responses\StreamableAgentResponse;

#[Bind(AssistantAgent::class)]
interface Advisor extends Agent, Conversational, HasTools
{
    public function withMode(AgentMode $mode): self;

    public function forUser(User $user): self;

    public function continue(string $conversationId, object $as): self;

    public function stream(string $prompt, array $attachments = [], array|string|null $provider = null, ?string $model = null): StreamableAgentResponse;
}
