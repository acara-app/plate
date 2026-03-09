<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Http\Requests\StreamChatRequest;
use App\Models\User;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class BuildAssistantAgentAction
{
    public function __construct(
        private AgentRunner $agentRunner,
    ) {}

    public function handle(StreamChatRequest $request, User $user, string $conversationId): StreamableAgentResponse
    {
        $agentPayload = new AgentPayload(
            userId: $user->id,
            message: $request->userMessage(),
            images: $request->userAttachments(),
            mode: $request->mode(),
            modelName: $request->modelName(),
        );

        return $this->agentRunner->runWithConversation($agentPayload, $user, $conversationId);
    }
}
