<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildAssistantAgentAction;
use App\Actions\BuildConversationMessagesAction;
use App\Enums\AgentMode;
use App\Http\Requests\StoreAgentConversationRequest;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Ai\Responses\StreamableAgentResponse;
use Illuminate\Support\Str;

final readonly class ChatController
{
    public function __construct(
        #[CurrentUser] private User $user,
        private BuildConversationMessagesAction $messagesAction,
        private BuildAssistantAgentAction $agentAction,
    ) {}

    public function create(
        Request $request,
        string $conversationId
    ): Response {
        $conversation = $conversationId !== ''
            ? Conversation::query()->with('messages')->find($conversationId)
            : null;

        return Inertia::render('chat/create-chat', [
            'conversationId' => $conversation?->id ?? $conversationId,
            'messages' => $this->messagesAction->handle($conversation),
            'mode' => $request->enum('mode', AgentMode::class),
        ]);
    }

    public function stream(
        StoreAgentConversationRequest $request,
        string $conversationId
    ): StreamableAgentResponse {
        $agent = $this->agentAction->handle($request, $this->user);
        $agent->continue($conversationId, as: $this->user);

        return $agent
            ->stream(
                prompt: $request->userMessage(),
                attachments: $request->userAttachments(),
                provider: $request->modelName()->labProvider(),
                model: $request->modelName()->value,
            )
            ->usingVercelDataProtocol();
    }
}
